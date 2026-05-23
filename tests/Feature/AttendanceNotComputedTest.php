<?php

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

test('nao compareceu sem substituto vira nao_computado', function () {
    $coord = User::factory()->create();
    $member = User::factory()->create();

    $group = Group::create(['name' => 'G9', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);

    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa sem substituto',
        'event_date' => now()->toDateString(),
        'event_time' => '09:00',
        'created_by' => $coord->id,
    ]);

    $attendance = AttendanceRecord::create([
        'event_id' => $event->id,
        'user_id' => $member->id,
        'status' => 'pendente',
    ]);

    $this->actingAs($member)->post(route('attendance.confirm', $attendance->id), [
        'status' => 'nao_compareceu',
    ])->assertOk();

    $this->assertDatabaseHas('attendance_records', [
        'id' => $attendance->id,
        'status' => 'nao_computado',
    ]);
});
