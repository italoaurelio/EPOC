<?php

use App\Models\Event;
use App\Models\EventFunction;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

it('cria missa com vaga aberta em uma funcao e pessoa em outra', function () {
    $coord = User::factory()->create();
    $member = User::factory()->create();

    $group = Group::create(['name' => 'Grupo Slots', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);

    $missal = EventFunction::create(['group_id' => $group->id, 'name' => 'missal']);
    $auxiliar = EventFunction::create(['group_id' => $group->id, 'name' => 'auxiliar']);

    $this->actingAs($coord)->post(route('groups.events.store', $group->id), [
        'type' => 'missa',
        'name' => 'Missa com slots',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => '09:00',
        'liturgical_color' => 'verde',
        'function_ids' => [$missal->id, $auxiliar->id],
        'slot_assignments' => [
            $missal->id => ['mode' => 'vacancy'],
            $auxiliar->id => ['mode' => 'member', 'user_id' => $member->id],
        ],
    ])->assertCreated();

    $event = Event::where('group_id', $group->id)->where('name', 'Missa com slots')->firstOrFail();

    $this->assertDatabaseHas('event_function_slots', [
        'event_id' => $event->id,
        'event_function_id' => $missal->id,
        'status' => 'aberta',
    ]);

    $this->assertDatabaseHas('event_function_slots', [
        'event_id' => $event->id,
        'event_function_id' => $auxiliar->id,
        'status' => 'preenchida',
    ]);

    $this->assertDatabaseHas('event_assignments', ['user_id' => $member->id]);
});

it('cria conta fantasma ao escalar por nome no evento', function () {
    $coord = User::factory()->create();

    $group = Group::create(['name' => 'Grupo Ghost Slot', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $missal = EventFunction::create(['group_id' => $group->id, 'name' => 'missal']);

    $this->actingAs($coord)->post(route('groups.events.store', $group->id), [
        'type' => 'missa',
        'name' => 'Missa Ghost Slot',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => '10:00',
        'liturgical_color' => 'verde',
        'function_ids' => [$missal->id],
        'slot_assignments' => [
            $missal->id => ['mode' => 'ghost', 'ghost_name' => 'Servo Fantasma'],
        ],
    ])->assertCreated();

    $ghost = User::where('name', 'Servo Fantasma')->where('is_ghost', true)->first();
    expect($ghost)->not->toBeNull();

    $this->assertDatabaseHas('group_memberships', ['group_id' => $group->id, 'user_id' => $ghost->id]);
});
