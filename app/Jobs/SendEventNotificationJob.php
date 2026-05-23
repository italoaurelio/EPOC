<?php

namespace App\Jobs;

use App\Mail\EventNotificationMail;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\GroupMembership;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEventNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $eventId, public string $notificationType) {}

    public function handle(): void
    {
        $event = Event::with(['group', 'location', 'slots.eventFunction'])->find($this->eventId);
        if (!$event) {
            return;
        }

        if ($this->notificationType === 'aviso_vagas') {
            $openFunctions = $event->slots
                ->where('status', 'aberta')
                ->map(fn ($slot) => $slot->eventFunction?->name)
                ->filter()
                ->values()
                ->all();

            if (count($openFunctions) === 0) {
                return;
            }

            $members = GroupMembership::with('user')
                ->where('group_id', $event->group_id)
                ->where('status', 'aprovado')
                ->get();

            foreach ($members as $member) {
                if (!$member->user?->email) {
                    continue;
                }

                Mail::to($member->user->email)->queue(new EventNotificationMail([
                    'subject' => "Escalada para o Céu - {$this->notificationType}",
                    'system_name' => 'Escalada para o Céu',
                    'group_name' => $event->group->name,
                    'event_name' => $event->name,
                    'event_date' => $event->event_date,
                    'event_time' => $event->event_time,
                    'location' => $event->location?->name ?? 'A definir',
                    'function' => implode(', ', $openFunctions),
                    'type' => $this->notificationType,
                    'app_url' => config('app.url'),
                ]));
            }

            return;
        }

        if ($this->notificationType === 'resumo_24h') {
            $this->sendSummaryToCoordinators($event);
            return;
        }

        $records = AttendanceRecord::where('event_id', $event->id)
            ->with(['user', 'eventAssignment.slot.eventFunction'])
            ->get();

        if ($this->notificationType === 'cobranca_presenca') {
            $records = $records->where('status', 'pendente')->values();
        }

        foreach ($records as $record) {
            if (!$record->user?->email) {
                continue;
            }

            Mail::to($record->user->email)->queue(new EventNotificationMail([
                'subject' => "Escalada para o Céu - {$this->notificationType}",
                'system_name' => 'Escalada para o Céu',
                'group_name' => $event->group->name,
                'event_name' => $event->name,
                'event_date' => $event->event_date,
                'event_time' => $event->event_time,
                'location' => $event->location?->name ?? 'A definir',
                'function' => $record->eventAssignment?->slot?->eventFunction?->name ?? 'A definir',
                'type' => $this->notificationType,
                'app_url' => config('app.url'),
                ]));
        }
    }

    private function sendSummaryToCoordinators(Event $event): void
    {
        if ($event->type !== 'missa') {
            return;
        }

        $coordinators = GroupMembership::with('user')
            ->where('group_id', $event->group_id)
            ->where('role', 'coordenador')
            ->where('status', 'aprovado')
            ->get();

        if ($coordinators->isEmpty()) {
            return;
        }

        $records = AttendanceRecord::where('event_id', $event->id)->get();
        $summary = $this->buildAttendanceSummary($records);

        foreach ($coordinators as $coordinator) {
            if (!$coordinator->user?->email) {
                continue;
            }

            Mail::to($coordinator->user->email)->queue(new EventNotificationMail([
                'subject' => "Escalada para o Céu - {$this->notificationType}",
                'system_name' => 'Escalada para o Céu',
                'group_name' => $event->group->name,
                'event_name' => $event->name,
                'event_date' => $event->event_date,
                'event_time' => $event->event_time,
                'location' => $event->location?->name ?? 'A definir',
                'function' => $summary,
                'type' => $this->notificationType,
                'app_url' => config('app.url'),
            ]));
        }
    }

    private function buildAttendanceSummary(Collection $records): string
    {
        $present = $records->where('status', 'compareceu')->count();
        $absent = $records->where('status', 'nao_compareceu')->count();
        $notComputed = $records->where('status', 'nao_computado')->count();
        $pending = $records->where('status', 'pendente')->count();

        return "Resumo: compareceram {$present}, faltaram {$absent}, não computados {$notComputed}, pendentes {$pending}.";
    }
}
