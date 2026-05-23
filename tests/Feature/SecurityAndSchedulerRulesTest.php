<?php

use App\Jobs\SendEventNotificationJob;
use App\Models\Event;
use App\Models\EventFunction;
use App\Models\EventFunctionSlot;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

test('nao coordenador nao cria substituicao para terceiro', function () {
    $coord = User::factory()->create();
    $member = User::factory()->create();
    $otherMember = User::factory()->create();

    $group = Group::create(['name' => 'Grupo Seg', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $otherMember->id, 'role' => 'membro', 'status' => 'aprovado']);

    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => '09:00',
        'created_by' => $coord->id,
    ]);

    $this->actingAs($member)->post(route('substitutions.store'), [
        'event_id' => $event->id,
        'replaced_user_id' => $otherMember->id,
        'replacement_name' => 'Nao Pode',
    ])->assertForbidden();
});

test('coordenador nao consegue escalar usuario fora do grupo', function () {
    $coord = User::factory()->create();
    $outsider = User::factory()->create();

    $group = Group::create(['name' => 'Grupo Escala Seg', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $func = EventFunction::create(['group_id' => $group->id, 'name' => 'missal']);
    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => '10:00',
        'created_by' => $coord->id,
    ]);
    $slot = EventFunctionSlot::create(['event_id' => $event->id, 'event_function_id' => $func->id, 'status' => 'aberta']);

    $this->actingAs($coord)->post(route('assignments.store'), [
        'event_function_slot_id' => $slot->id,
        'user_id' => $outsider->id,
    ])->assertStatus(422);
});

test('scheduler lembrete 1 dia dispara apenas para janela correta', function () {
    Bus::fake();

    $coord = User::factory()->create();
    $group = Group::create(['name' => 'Grupo Scheduler', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa D+1',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => now()->format('H:i'),
        'created_by' => $coord->id,
    ]);

    Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa D+3',
        'event_date' => now()->addDays(3)->toDateString(),
        'event_time' => now()->format('H:i'),
        'created_by' => $coord->id,
    ]);

    $this->artisan('escalada:dispatch-notifications lembrete_1_dia')->assertExitCode(0);

    Bus::assertDispatched(SendEventNotificationJob::class, 1);
});
