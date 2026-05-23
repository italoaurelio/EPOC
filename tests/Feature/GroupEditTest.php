<?php

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

test('coordenador edita grupo que coordena', function () {
    $coord = User::factory()->create();
    $group = Group::create(['name' => 'Antigo', 'description' => 'Desc', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $this->actingAs($coord)->patch(route('groups.update', $group->id), [
        'name' => 'Novo Nome',
        'description' => 'Nova Desc',
    ])->assertOk();

    $this->assertDatabaseHas('groups', ['id' => $group->id, 'name' => 'Novo Nome']);
});


test('membro nao edita grupo sem coordenacao', function () {
    $coord = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::create(['name' => 'Grupo', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);

    $this->actingAs($member)->patch(route('groups.update', $group->id), [
        'name' => 'Invalido',
        'description' => 'X',
    ])->assertForbidden();
});
