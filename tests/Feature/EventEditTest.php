<?php

use App\Models\Event;
use App\Models\EventFunction;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;


test('coordenador consegue editar missa e trocar funcoes', function () {
    $coord = User::factory()->create();
    $group = Group::create(['name' => 'G8', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $missal = EventFunction::create(['group_id' => $group->id, 'name' => 'missal', 'is_default' => true, 'is_initially_active' => true]);
    $auxiliar = EventFunction::create(['group_id' => $group->id, 'name' => 'auxiliar', 'is_default' => true, 'is_initially_active' => true]);

    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa Antiga',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => '08:00',
        'liturgical_color' => 'verde',
        'created_by' => $coord->id,
    ]);

    $this->actingAs($coord)->patch(route('events.update', $event->id), [
        'name' => 'Missa Atualizada',
        'event_date' => now()->addDays(2)->toDateString(),
        'event_time' => '10:30',
        'notes' => 'Observação atualizada',
        'liturgical_color' => 'branco',
        'function_ids' => [$missal->id, $auxiliar->id],
    ])->assertOk();

    $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Missa Atualizada', 'notes' => 'Observação atualizada', 'liturgical_color' => 'branco']);
    $this->assertDatabaseCount('event_function_slots', 2);
});
