<?php

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\EventFunction;
use App\Models\EventFunctionSlot;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

function coordinatorGroupWithPendingMember(): array {
    $coord = User::factory()->create();
    $group = Group::create(['name' => 'G2', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    $pending = User::factory()->create();
    $membership = GroupMembership::create(['group_id' => $group->id, 'user_id' => $pending->id, 'role' => 'membro', 'status' => 'pendente']);

    return [$coord, $group, $pending, $membership];
}

test('coordenador aprova membership pendente', function () {
    [$coord, $group, $pending, $membership] = coordinatorGroupWithPendingMember();

    $this->actingAs($coord)->patch(route('groups.memberships.update', [$group->id, $membership->id]), [
        'action' => 'approve',
        'role' => 'membro',
    ])->assertOk();

    $this->assertDatabaseHas('group_memberships', ['id' => $membership->id, 'status' => 'aprovado']);
});

test('substituto recebe pendencia de presenca apos nao compareceu', function () {
    $coord = User::factory()->create();
    $group = Group::create(['name' => 'G3', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $member = User::factory()->create();
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);

    $event = Event::create(['group_id' => $group->id, 'type' => 'missa', 'name' => 'Missa', 'event_date' => now()->toDateString(), 'event_time' => '09:00', 'created_by' => $coord->id]);
    $attendance = AttendanceRecord::create(['event_id' => $event->id, 'user_id' => $member->id, 'status' => 'pendente']);

    $this->actingAs($member)->post(route('attendance.confirm', $attendance->id), [
        'status' => 'nao_compareceu',
        'replacement_name' => 'Substituto Novo',
    ])->assertOk();

    $replacementUser = User::where('name', 'Substituto Novo')->first();
    expect($replacementUser)->not->toBeNull();

    $this->assertDatabaseHas('attendance_records', [
        'event_id' => $event->id,
        'user_id' => $replacementUser->id,
        'status' => 'pendente',
    ]);
});

test('envio de ambiente turibulo exige naveta', function () {
    $coord = User::factory()->create();
    $group = Group::create(['name' => 'G4', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $event = Event::create(['group_id' => $group->id, 'type' => 'missa', 'name' => 'Missa', 'event_date' => now()->toDateString(), 'event_time' => '09:00', 'created_by' => $coord->id]);

    $turibulo = EventFunction::create(['group_id' => $group->id, 'name' => 'turíbulo']);
    EventFunctionSlot::create(['event_id' => $event->id, 'event_function_id' => $turibulo->id]);

    $this->actingAs($coord)->post(route('events.environments.store', $event->id), [
        'environment' => 'turibulo',
        'photo_path' => 'turibulo.jpg',
        'observation' => 'ok',
    ])->assertStatus(422);

    $naveta = EventFunction::create(['group_id' => $group->id, 'name' => 'naveta']);
    EventFunctionSlot::create(['event_id' => $event->id, 'event_function_id' => $naveta->id]);

    $this->actingAs($coord)->post(route('events.environments.store', $event->id), [
        'environment' => 'turibulo',
        'photo_path' => 'turibulo.jpg',
        'observation' => 'ok',
    ])->assertCreated();
});
