<?php

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\EventFunction;
use App\Models\EventFunctionSlot;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createCoordinatorContext(): array {
    $coord = User::factory()->create(['password' => Hash::make('password')]);
    $group = Group::create(['name' => 'G1', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    return [$coord, $group];
}

test('criacao de grupo', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/groups', ['name' => 'Grupo Novo'])->assertCreated();
    $this->assertDatabaseHas('group_memberships', ['user_id' => $user->id, 'role' => 'coordenador']);
});

test('convite de membro', function () {
    [$coord, $group] = createCoordinatorContext();
    $this->actingAs($coord)->post("/groups/{$group->id}/invites", ['role' => 'membro', 'requires_approval' => true])->assertCreated();
    $this->assertDatabaseHas('invite_links', ['group_id' => $group->id, 'role' => 'membro']);
});

test('convite de coordenador', function () {
    [$coord, $group] = createCoordinatorContext();
    $this->actingAs($coord)->post("/groups/{$group->id}/invites", ['role' => 'coordenador', 'requires_approval' => false])->assertCreated();
    $this->assertDatabaseHas('invite_links', ['group_id' => $group->id, 'role' => 'coordenador']);
});

test('criacao de missa', function () {
    [$coord, $group] = createCoordinatorContext();
    $func = EventFunction::create(['group_id' => $group->id, 'name' => 'missal', 'is_default' => true, 'is_initially_active' => true]);

    $this->actingAs($coord)->post("/groups/{$group->id}/events", [
        'type' => 'missa', 'name' => 'Missa', 'event_date' => now()->toDateString(), 'event_time' => '09:00', 'notes' => 'Observação da missa', 'liturgical_color' => 'verde', 'function_ids' => [$func->id],
    ])->assertCreated();

    $this->assertDatabaseHas('events', ['group_id' => $group->id, 'type' => 'missa', 'notes' => 'Observação da missa']);
});

test('criacao de reuniao', function () {
    [$coord, $group] = createCoordinatorContext();
    $this->actingAs($coord)->post("/groups/{$group->id}/events", ['type' => 'reuniao', 'name' => 'Reunião', 'event_date' => now()->toDateString(), 'event_time' => '19:00'])->assertCreated();
    $this->assertDatabaseHas('events', ['group_id' => $group->id, 'type' => 'reuniao']);
});

test('escala com usuario real', function () {
    [$coord, $group] = createCoordinatorContext();
    $member = User::factory()->create();
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);
    $func = EventFunction::create(['group_id' => $group->id, 'name' => 'missal']);
    $event = Event::create(['group_id' => $group->id, 'type' => 'missa', 'name' => 'Missa', 'event_date' => now()->toDateString(), 'event_time' => '09:00', 'created_by' => $coord->id]);
    $slot = EventFunctionSlot::create(['event_id' => $event->id, 'event_function_id' => $func->id]);

    $this->actingAs($coord)->post('/assignments', ['event_function_slot_id' => $slot->id, 'user_id' => $member->id])->assertCreated();
    $this->assertDatabaseHas('event_assignments', ['user_id' => $member->id]);
});

test('escala com conta fantasma', function () {
    [$coord, $group] = createCoordinatorContext();
    $func = EventFunction::create(['group_id' => $group->id, 'name' => 'auxiliar']);
    $event = Event::create(['group_id' => $group->id, 'type' => 'missa', 'name' => 'Missa', 'event_date' => now()->toDateString(), 'event_time' => '09:00', 'created_by' => $coord->id]);
    $slot = EventFunctionSlot::create(['event_id' => $event->id, 'event_function_id' => $func->id]);

    $this->actingAs($coord)->post('/assignments', ['event_function_slot_id' => $slot->id, 'ghost_name' => 'Fulano'])->assertCreated();
    $this->assertDatabaseHas('users', ['name' => 'Fulano', 'is_ghost' => 1]);
});

test('confirmacao de presenca', function () {
    [$coord, $group] = createCoordinatorContext();
    $member = User::factory()->create();
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);
    $event = Event::create(['group_id' => $group->id, 'type' => 'missa', 'name' => 'Missa', 'event_date' => now()->toDateString(), 'event_time' => '09:00', 'created_by' => $coord->id]);
    $att = AttendanceRecord::create(['event_id' => $event->id, 'user_id' => $member->id, 'status' => 'pendente']);

    $this->actingAs($member)->post("/attendance/{$att->id}/confirm", ['status' => 'compareceu'])->assertOk();
    $this->assertDatabaseHas('attendance_records', ['id' => $att->id, 'status' => 'compareceu']);
});

test('substituicao', function () {
    [$coord, $group] = createCoordinatorContext();
    $member = User::factory()->create();
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);
    $event = Event::create(['group_id' => $group->id, 'type' => 'missa', 'name' => 'Missa', 'event_date' => now()->toDateString(), 'event_time' => '09:00', 'created_by' => $coord->id]);

    $this->actingAs($coord)->post('/substitutions', ['event_id' => $event->id, 'replaced_user_id' => $member->id, 'replacement_name' => 'Sub'])->assertCreated();
    $this->assertDatabaseHas('substitutions', ['event_id' => $event->id, 'replaced_user_id' => $member->id]);
});

test('permissoes coordenador membro', function () {
    [$coord, $group] = createCoordinatorContext();
    $other = User::factory()->create();
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $other->id, 'role' => 'membro', 'status' => 'aprovado']);

    $this->actingAs($other)->post("/groups/{$group->id}/invites", ['role' => 'membro', 'requires_approval' => false])->assertForbidden();
    $this->actingAs($coord)->post("/groups/{$group->id}/invites", ['role' => 'membro', 'requires_approval' => false])->assertCreated();
});
