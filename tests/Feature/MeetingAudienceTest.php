<?php

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

function setupMeetingGroup(): array {
    $coord = User::factory()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $group = Group::create(['name' => 'G6', 'created_by' => $coord->id]);

    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member1->id, 'role' => 'membro', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member2->id, 'role' => 'membro', 'status' => 'aprovado']);

    return [$coord, $member1, $member2, $group];
}

test('reuniao com convidados especificos cria presenca apenas para convidados', function () {
    [$coord, $member1, $member2, $group] = setupMeetingGroup();

    $response = $this->actingAs($coord)->post(route('groups.events.store', $group->id), [
        'type' => 'reuniao',
        'audience' => 'specific',
        'invitee_user_ids' => [$member1->id],
        'name' => 'Reunião Convocados',
        'event_date' => now()->addDays(3)->toDateString(),
        'event_time' => '20:00',
    ])->assertCreated();

    $eventId = $response->json('id');

    $this->assertDatabaseHas('attendance_records', ['event_id' => $eventId, 'user_id' => $member1->id]);
    $this->assertDatabaseMissing('attendance_records', ['event_id' => $eventId, 'user_id' => $member2->id]);
});

test('coordenador pode preencher presenca manualmente', function () {
    [$coord, $member1, $member2, $group] = setupMeetingGroup();

    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'reuniao',
        'audience' => 'all',
        'name' => 'Reunião Manual',
        'event_date' => now()->toDateString(),
        'event_time' => '19:00',
        'created_by' => $coord->id,
    ]);

    $record = AttendanceRecord::create(['event_id' => $event->id, 'user_id' => $member1->id, 'status' => 'pendente']);

    $this->actingAs($coord)->patch(route('attendance.manual', $record->id), [
        'status' => 'compareceu',
    ])->assertOk();

    $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'status' => 'compareceu']);
});
