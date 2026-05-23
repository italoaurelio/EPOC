<?php

use App\Models\Group;
use App\Models\User;


test('admin sistema acessa grupos eventos e dashboard sem membership', function () {
    $admin = User::factory()->create(['system_role' => 'admin_sistema']);
    Group::create(['name' => 'Grupo Admin View', 'created_by' => $admin->id]);

    $this->actingAs($admin)->get(route('groups.index'))->assertOk();
    $this->actingAs($admin)->get(route('events.index'))->assertOk();
    $this->actingAs($admin)->get(route('dashboard'))->assertOk();
});
