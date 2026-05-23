<?php

use App\Models\Group;
use App\Models\InviteLink;
use App\Models\User;

it('cadastro por convite de coordenador entra como coordenador', function () {
    $owner = User::factory()->create();
    $group = Group::create(['name' => 'G Convite Coord', 'created_by' => $owner->id]);

    $invite = InviteLink::create([
        'group_id' => $group->id,
        'token' => 'invite-coord-1',
        'role' => 'coordenador',
        'requires_approval' => false,
        'is_active' => true,
        'created_by' => $owner->id,
    ]);

    $this->post(route('invites.register', $invite->token), [
        'name' => 'Novo Coordenador',
        'email' => 'novo.coordenador@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'novo.coordenador@test.com')->firstOrFail();

    $this->assertDatabaseHas('group_memberships', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => 'coordenador',
        'status' => 'aprovado',
    ]);
});

it('cadastro por convite com aprovação gera membership pendente', function () {
    $owner = User::factory()->create();
    $group = Group::create(['name' => 'G Convite Pendente', 'created_by' => $owner->id]);

    $invite = InviteLink::create([
        'group_id' => $group->id,
        'token' => 'invite-pending-1',
        'role' => 'membro',
        'requires_approval' => true,
        'is_active' => true,
        'created_by' => $owner->id,
    ]);

    $this->post(route('invites.register', $invite->token), [
        'name' => 'Novo Membro Pendente',
        'email' => 'novo.pendente@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'novo.pendente@test.com')->firstOrFail();

    $this->assertDatabaseHas('group_memberships', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => 'membro',
        'status' => 'pendente',
    ]);
});
