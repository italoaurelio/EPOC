<?php

use App\Models\Event;
use App\Models\EventFunction;
use App\Models\EventFunctionSlot;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

function makeGroupWithCoordinatorAndMember(): array {
    $coord = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::create(['name' => 'G5', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);
    return [$coord, $member, $group];
}

test('membro se candidata e coordenador aprova vaga', function () {
    [$coord, $member, $group] = makeGroupWithCoordinatorAndMember();

    $func = EventFunction::create(['group_id' => $group->id, 'name' => 'auxiliar']);
    $event = Event::create(['group_id' => $group->id, 'type' => 'missa', 'name' => 'Missa aberta', 'event_date' => now()->addDay()->toDateString(), 'event_time' => '10:00', 'created_by' => $coord->id]);
    $slot = EventFunctionSlot::create(['event_id' => $event->id, 'event_function_id' => $func->id, 'status' => 'aberta']);

    $this->actingAs($member)->post(route('event-candidates.store'), ['event_function_slot_id' => $slot->id])->assertCreated();

    $candidateId = \App\Models\EventCandidate::where('event_function_slot_id', $slot->id)->where('user_id', $member->id)->value('id');

    $this->actingAs($coord)->post(route('event-slots.decide-candidate', $slot->id), ['decision' => 'aprovar', 'candidate_id' => $candidateId])->assertOk();

    $this->assertDatabaseHas('event_assignments', ['event_function_slot_id' => $slot->id, 'user_id' => $member->id]);
    $this->assertDatabaseHas('attendance_records', ['event_id' => $event->id, 'user_id' => $member->id, 'status' => 'pendente']);
});

test('coordenador acessa insights', function () {
    [$coord, $member, $group] = makeGroupWithCoordinatorAndMember();

    $this->actingAs($coord)->get(route('insights.index'))->assertOk();
    $this->actingAs($member)->get(route('insights.index'))->assertOk();
});
