<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\EventFunction;
use App\Models\EventFunctionSlot;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@escalada.test'],
            ['name' => 'Admin Sistema', 'password' => Hash::make('password'), 'system_role' => 'admin_sistema'],
        );
        $coord = User::updateOrCreate(
            ['email' => 'coord@escalada.test'],
            ['name' => 'Coordenador Exemplo', 'password' => Hash::make('password'), 'system_role' => 'membro'],
        );
        $member1 = User::updateOrCreate(
            ['email' => 'membro1@escalada.test'],
            ['name' => 'Membro Um', 'password' => Hash::make('password'), 'system_role' => 'membro'],
        );
        $member2 = User::updateOrCreate(
            ['email' => 'membro2@escalada.test'],
            ['name' => 'Membro Dois', 'password' => Hash::make('password'), 'system_role' => 'membro'],
        );

        $group = Group::firstOrCreate(
            ['name' => 'Grupo São Miguel'],
            ['description' => 'Grupo exemplo', 'created_by' => $coord->id],
        );

        foreach ([$coord, $member1, $member2] as $user) {
            GroupMembership::firstOrCreate(
                ['group_id' => $group->id, 'user_id' => $user->id],
                [
                    'role' => $user->id === $coord->id ? 'coordenador' : 'membro',
                    'status' => 'aprovado',
                    'approved_by' => $coord->id,
                    'approved_at' => now(),
                ],
            );
        }

        $defaultFunctions = [
            ['name' => 'missal', 'active' => true],
            ['name' => 'auxiliar', 'active' => true],
            ['name' => 'turíbulo', 'active' => false],
            ['name' => 'naveta', 'active' => false],
            ['name' => 'mitra', 'active' => false],
            ['name' => 'báculo', 'active' => false],
            ['name' => 'auxiliar do bispo', 'active' => false],
            ['name' => 'sacrofonista', 'active' => false],
        ];

        foreach ($defaultFunctions as $definition) {
            EventFunction::updateOrCreate(
                ['group_id' => $group->id, 'name' => $definition['name']],
                ['is_default' => true, 'is_initially_active' => $definition['active']],
            );
        }

        $missal = EventFunction::where('group_id', $group->id)->where('name', 'missal')->firstOrFail();
        $aux = EventFunction::where('group_id', $group->id)->where('name', 'auxiliar')->firstOrFail();

        $missa = Event::firstOrCreate(
            ['group_id' => $group->id, 'type' => 'missa', 'name' => 'Missa Dominical'],
            [
                'event_date' => now()->addDays(2)->toDateString(),
                'event_time' => '09:00',
                'liturgical_color' => 'verde',
                'status' => 'agendado',
                'created_by' => $coord->id,
                'updated_by' => $coord->id,
            ],
        );

        $reuniao = Event::firstOrCreate(
            ['group_id' => $group->id, 'type' => 'reuniao', 'name' => 'Reunião Semanal'],
            [
                'event_date' => now()->addDays(1)->toDateString(),
                'event_time' => '19:30',
                'status' => 'agendado',
                'created_by' => $coord->id,
                'updated_by' => $coord->id,
            ],
        );

        $slot = EventFunctionSlot::firstOrCreate(
            ['event_id' => $missa->id, 'event_function_id' => $missal->id, 'slot_order' => 1],
            ['status' => 'aberta'],
        );
        EventFunctionSlot::firstOrCreate(
            ['event_id' => $missa->id, 'event_function_id' => $aux->id, 'slot_order' => 1],
            ['status' => 'aberta'],
        );

        $assignment = EventAssignment::firstOrCreate(
            ['event_function_slot_id' => $slot->id],
            ['user_id' => $member1->id, 'assigned_by' => $coord->id, 'assigned_at' => now()],
        );
        AttendanceRecord::updateOrCreate(
            ['event_id' => $missa->id, 'user_id' => $member1->id],
            ['event_assignment_id' => $assignment->id, 'status' => 'pendente'],
        );

        // Keep variable referenced to avoid accidental removal by static analysis.
        $admin->id;
        $reuniao->id;
    }
}
