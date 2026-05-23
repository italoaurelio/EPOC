<?php

use App\Jobs\SendEventNotificationJob;
use App\Mail\EventNotificationMail;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\EventFunction;
use App\Models\EventFunctionSlot;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('envia aviso de vagas para membros aprovados do grupo', function () {
    Mail::fake();

    $coord = User::factory()->create(['email' => 'coord-job@test.com']);
    $member1 = User::factory()->create(['email' => 'm1-job@test.com']);
    $member2 = User::factory()->create(['email' => 'm2-job@test.com']);

    $group = Group::create(['name' => 'Grupo Job', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member1->id, 'role' => 'membro', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member2->id, 'role' => 'membro', 'status' => 'aprovado']);

    $func = EventFunction::create(['group_id' => $group->id, 'name' => 'missal', 'is_default' => true, 'is_initially_active' => true]);
    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa com vagas',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => '09:00',
        'created_by' => $coord->id,
    ]);
    EventFunctionSlot::create(['event_id' => $event->id, 'event_function_id' => $func->id, 'status' => 'aberta']);

    (new SendEventNotificationJob($event->id, 'aviso_vagas'))->handle();

    Mail::assertQueued(EventNotificationMail::class, 3);
    Mail::assertQueued(EventNotificationMail::class, function (EventNotificationMail $mail) {
        return str_contains($mail->payload['subject'], 'aviso_vagas')
            && str_contains($mail->payload['function'], 'missal');
    });
});

it('envia email para escalado com nome da funcao', function () {
    Mail::fake();

    $coord = User::factory()->create();
    $member = User::factory()->create(['email' => 'escalado-job@test.com']);

    $group = Group::create(['name' => 'Grupo Escalado', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $func = EventFunction::create(['group_id' => $group->id, 'name' => 'auxiliar', 'is_default' => true, 'is_initially_active' => true]);
    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa Escalado',
        'event_date' => now()->addDay()->toDateString(),
        'event_time' => '09:00',
        'created_by' => $coord->id,
    ]);
    $slot = EventFunctionSlot::create(['event_id' => $event->id, 'event_function_id' => $func->id, 'status' => 'preenchida']);
    $assignment = EventAssignment::create(['event_function_slot_id' => $slot->id, 'user_id' => $member->id, 'assigned_by' => $coord->id, 'assigned_at' => now()]);
    AttendanceRecord::create(['event_id' => $event->id, 'user_id' => $member->id, 'event_assignment_id' => $assignment->id, 'status' => 'pendente']);

    (new SendEventNotificationJob($event->id, 'lembrete_1_dia'))->handle();

    Mail::assertQueued(EventNotificationMail::class, function (EventNotificationMail $mail) {
        return str_contains($mail->payload['subject'], 'lembrete_1_dia')
            && $mail->payload['function'] === 'auxiliar';
    });
});

it('resumo 24h de missa vai para coordenadores com resumo de presenca', function () {
    Mail::fake();

    $coord = User::factory()->create(['email' => 'coord-resumo@test.com']);
    $member = User::factory()->create(['email' => 'member-resumo@test.com']);

    $group = Group::create(['name' => 'Grupo Resumo', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'membro', 'status' => 'aprovado']);

    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa Resumo',
        'event_date' => now()->subDay()->toDateString(),
        'event_time' => '09:00',
        'created_by' => $coord->id,
    ]);

    AttendanceRecord::create(['event_id' => $event->id, 'user_id' => $member->id, 'status' => 'compareceu']);

    (new SendEventNotificationJob($event->id, 'resumo_24h'))->handle();

    Mail::assertQueued(EventNotificationMail::class, 1);
    Mail::assertQueued(EventNotificationMail::class, function (EventNotificationMail $mail) {
        return str_contains($mail->payload['subject'], 'resumo_24h')
            && str_contains($mail->payload['function'], 'Resumo:');
    });
});

it('cobranca presenca envia apenas para quem esta pendente', function () {
    Mail::fake();

    $coord = User::factory()->create();
    $pending = User::factory()->create(['email' => 'pending@test.com']);
    $present = User::factory()->create(['email' => 'present@test.com']);

    $group = Group::create(['name' => 'Grupo Cobranca', 'created_by' => $coord->id]);
    GroupMembership::create(['group_id' => $group->id, 'user_id' => $coord->id, 'role' => 'coordenador', 'status' => 'aprovado']);

    $event = Event::create([
        'group_id' => $group->id,
        'type' => 'missa',
        'audience' => 'all',
        'name' => 'Missa Cobranca',
        'event_date' => now()->toDateString(),
        'event_time' => '09:00',
        'created_by' => $coord->id,
    ]);

    AttendanceRecord::create(['event_id' => $event->id, 'user_id' => $pending->id, 'status' => 'pendente']);
    AttendanceRecord::create(['event_id' => $event->id, 'user_id' => $present->id, 'status' => 'compareceu']);

    (new SendEventNotificationJob($event->id, 'cobranca_presenca'))->handle();

    Mail::assertQueued(EventNotificationMail::class, 1);
});
