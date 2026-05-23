<?php

namespace App\Console\Commands;

use App\Jobs\SendEventNotificationJob;
use App\Models\Event;
use Illuminate\Console\Command;

class DispatchScheduledNotifications extends Command
{
    protected $signature = 'escalada:dispatch-notifications {type}';
    protected $description = 'Dispara notificacoes agendadas de eventos';

    public function handle(): int
    {
        $type = (string) $this->argument('type');
        $events = Event::query()
            ->where('status', 'agendado')
            ->get()
            ->filter(fn (Event $event) => $this->shouldDispatchForType($event, $type));

        $events->each(function (Event $event) use ($type) {
            SendEventNotificationJob::dispatch($event->id, $type);
        });

        $this->info('Notificacoes enfileiradas: '.$type.' ('.$events->count().')');

        return self::SUCCESS;
    }

    private function shouldDispatchForType(Event $event, string $type): bool
    {
        $eventDateTime = $event->event_date->copy()->setTimeFromTimeString($event->event_time);
        $now = now();

        return match ($type) {
            'lembrete_1_dia' => $eventDateTime->between($now->copy()->addDay()->subMinutes(30), $now->copy()->addDay()->addMinutes(30)),
            'lembrete_1_hora' => $eventDateTime->between($now->copy()->addHour()->subMinutes(30), $now->copy()->addHour()->addMinutes(30)),
            'cobranca_presenca' => $eventDateTime->between($now->copy()->subHours(2), $now->copy()->subMinutes(10)),
            'resumo_24h' => $event->type === 'missa' && $eventDateTime->between($now->copy()->subDay()->subHour(), $now->copy()->subDay()->addHour()),
            'aviso_vagas' => $event->type === 'missa' && $eventDateTime->isFuture(),
            default => false,
        };
    }
}
