<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Models\AttendanceRecord;
use App\Models\EventAssignment;
use App\Models\EventFunctionSlot;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AssignmentController extends Controller
{
    public function store(StoreAssignmentRequest $request): JsonResponse|RedirectResponse
    {
        $slot = EventFunctionSlot::with('event')->findOrFail($request->integer('event_function_slot_id'));
        $group = $slot->event->group;
        $this->authorize('manage', $group);

        $userId = $request->input('user_id');
        if (!$userId && $request->filled('ghost_name')) {
            $ghost = User::create([
                'name' => $request->string('ghost_name'),
                'email' => null,
                'password' => null,
                'is_ghost' => true,
                'system_role' => 'membro',
            ]);

            GroupMembership::firstOrCreate([
                'group_id' => $group->id,
                'user_id' => $ghost->id,
            ], [
                'role' => 'membro',
                'status' => 'aprovado',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
            $userId = $ghost->id;
        }

        if ($userId) {
            $inGroup = GroupMembership::where('group_id', $group->id)
                ->where('user_id', $userId)
                ->where('status', 'aprovado')
                ->exists();

            abort_unless($inGroup || $request->user()->system_role === 'admin_sistema', 422, 'Usuário não pertence ao grupo.');
        }

        $assignment = EventAssignment::create([
            'event_function_slot_id' => $slot->id,
            'user_id' => $userId,
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        AttendanceRecord::firstOrCreate([
            'event_id' => $slot->event_id,
            'user_id' => $userId,
        ], [
            'event_assignment_id' => $assignment->id,
            'status' => 'pendente',
        ]);

        if ($request->header('X-Inertia')) {
            return to_route('events.index')->with('success', 'Escala atualizada.');
        }

        return response()->json($assignment, 201);
    }
}
