<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

it('acoes de grupo por inertia retornam redirect com flash', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('groups.store'), [
        'name' => 'Grupo Inertia',
        'description' => 'Teste',
    ], [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertRedirect(route('groups.index'));

    $group = Group::where('name', 'Grupo Inertia')->firstOrFail();

    $this->actingAs($user)->post(route('groups.invites.store', $group->id), [
        'role' => 'membro',
        'requires_approval' => false,
    ], [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertRedirect();
});

it('confirmacao de presenca por inertia retorna redirect', function () {
    $coord = User::factory()->create();
    $member = User::factory()->create();

    $group = Group::create(['name' => 'G Inertia', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);

    $event = \App\Models\Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa',
        'event_date' => now()->toDateString(),
        'event_time' => '09:00',
        'created_by' => $coord->id,
    ]);

    $attendance = \App\Models\AttendanceRecord::create([
        'event_id' => $event->id,
        'user_id' => $member->id,
        'status' => 'pendente',
    ]);

    $this->actingAs($member)->post(route('attendance.confirm', $attendance->id), [
        'status' => 'compareceu',
    ], [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertRedirect();
});
