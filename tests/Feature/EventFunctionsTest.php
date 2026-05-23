<?php

use App\Models\EventFunction;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

function groupWithCoordinator(): array {
    $coord = User::factory()->create();
    $group = Group::create(['name' => 'G7', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    return [$coord, $group];
}

test('coordenador cria funcao personalizada do grupo', function () {
    [$coord, $group] = groupWithCoordinator();

    $this->actingAs($coord)->post(route('groups.functions.store', $group->id), [
        'name' => 'cerimoniario 3',
        'is_initially_active' => true,
    ])->assertCreated();

    $this->assertDatabaseHas('event_functions', ['group_id' => $group->id, 'name' => 'cerimoniario 3']);
});

test('missa com turibulo sem naveta falha', function () {
    [$coord, $group] = groupWithCoordinator();

    $turibulo = EventFunction::create(['group_id' => $group->id, 'name' => 'turíbulo', 'is_default' => true, 'is_initially_active' => true]);

    $this->actingAs($coord)->post(route('groups.events.store', $group->id), [
        'type' => 'missa',
        'name' => 'Missa sem naveta',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => '09:00',
        'liturgical_color' => 'verde',
        'function_ids' => [$turibulo->id],
    ])->assertStatus(422);
});
