<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\InviteLink;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


test('cadastro por convite permite solicitar vinculo com conta fantasma do grupo', function () {
    $coord = User::factory()->create();
    $group = Group::create(['name' => 'Grupo Fantasma', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $ghost = User::create([
        'name' => 'José Servidor',
        'is_ghost' => true,
        'system_role' => 'membro',
    ]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $ghost->id, 'role' => 'membro', 'status' => 'aprovado']);

    $invite = InviteLink::create([
        'group_id' => $group->id,
        'token' => 'token-ghost-123',
        'role' => 'membro',
        'requires_approval' => false,
        'is_active' => true,
        'created_by' => $coord->id,
    ]);

    $this->post(route('invites.register', $invite->token), [
        'name' => 'José Servidor',
        'email' => 'jose.novo@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'claim_ghost_user_id' => $ghost->id,
    ])->assertRedirect(route('dashboard'));

    $realUser = User::where('email', 'jose.novo@test.com')->first();

    $this->assertDatabaseHas('ghost_account_claims', [
        'group_id' => $group->id,
        'ghost_user_id' => $ghost->id,
        'real_user_id' => $realUser->id,
        'status' => 'pendente',
    ]);
});
