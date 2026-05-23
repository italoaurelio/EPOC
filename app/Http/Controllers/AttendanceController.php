<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmAttendanceRequest;
use App\Http\Requests\ManualAttendanceUpdateRequest;
use App\Models\AttendanceRecord;
use App\Models\EventAssignment;
use App\Models\EventFunctionSlot;
use App\Models\GroupMembership;
use App\Models\Substitution;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AttendanceController extends Controller
{
    public function confirm(ConfirmAttendanceRequest $request, AttendanceRecord $attendance): JsonResponse|RedirectResponse
    {
        abort_unless($attendance->user_id === $request->user()->id || $request->user()->system_role === 'admin_sistema', 403);

        $attendance->update([
            'status' => $request->string('status'),
            'answered_at' => now(),
        ]);

        if ($request->string('status')->toString() === 'nao_compareceu') {
            $replacementId = $request->input('replacement_user_id');
            if (!$replacementId && $request->filled('replacement_name')) {
                $ghost = User::create([
                    'name' => $request->string('replacement_name'),
                    'is_ghost' => true,
                    'system_role' => 'membro',
                ]);
                $membership = GroupMembership::where('user_id', $attendance->user_id)->first();
                if ($membership) {
                    GroupMembership::firstOrCreate(['group_id' => $membership->group_id, 'user_id' => $ghost->id], ['role' => 'membro', 'status' => 'aprovado']);
                }
                $replacementId = $ghost->id;
            }

            if ($replacementId) {
                Substitution::create([
                    'event_id' => $attendance->event_id,
                    'replaced_user_id' => $attendance->user_id,
                    'replacement_user_id' => $replacementId,
                    'created_by' => $request->user()->id,
                    'source' => 'presenca',
                ]);

                $slotIds = EventFunctionSlot::where('event_id', $attendance->event_id)->pluck('id');
                $replacementAssignment = EventAssignment::whereIn('event_function_slot_id', $slotIds)
                    ->where('user_id', $replacementId)
                    ->first();

                AttendanceRecord::updateOrCreate(
                    ['event_id' => $attendance->event_id, 'user_id' => $replacementId],
                    ['event_assignment_id' => $replacementAssignment?->id, 'status' => 'pendente', 'answered_at' => null],
                );
            } else {
                // Regra de escopo: sem substituto informado, manter como não computado.
                $attendance->update(['status' => 'nao_computado']);
            }
        }

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Presença confirmada.');
        }

        return response()->json($attendance->fresh());
    }

    public function manualUpdate(ManualAttendanceUpdateRequest $request, AttendanceRecord $attendance): JsonResponse|RedirectResponse
    {
        $groupId = $attendance->event->group_id;

        $canManage = $request->user()->system_role === 'admin_sistema' || GroupMembership::where('group_id', $groupId)
            ->where('user_id', $request->user()->id)
            ->where('role', 'coordenador')
            ->where('status', 'aprovado')
            ->exists();

        abort_unless($canManage, 403);

        $attendance->update([
            'status' => $request->string('status')->toString(),
            'answered_at' => now(),
        ]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Presença atualizada.');
        }

        return response()->json($attendance->fresh());
    }
}
