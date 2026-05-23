<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideEventCandidateRequest;
use App\Http\Requests\StoreEventCandidateRequest;
use App\Models\AttendanceRecord;
use App\Models\EventAssignment;
use App\Models\EventCandidate;
use App\Models\EventFunctionSlot;
use App\Models\GroupMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EventCandidateController extends Controller
{
    public function store(StoreEventCandidateRequest $request): JsonResponse|RedirectResponse
    {
        $slot = EventFunctionSlot::with('event')->findOrFail($request->integer('event_function_slot_id'));

        $isMember = GroupMembership::where('group_id', $slot->event->group_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'aprovado')
            ->exists();

        abort_unless($isMember || $request->user()->system_role === 'admin_sistema', 403);

        $candidate = EventCandidate::firstOrCreate([
            'event_function_slot_id' => $slot->id,
            'user_id' => $request->user()->id,
        ], [
            'status' => 'pendente',
        ]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Candidatura enviada.');
        }

        return response()->json($candidate, 201);
    }

    public function decide(DecideEventCandidateRequest $request, EventFunctionSlot $slot): JsonResponse|RedirectResponse
    {
        $slot->load('event');
        $group = $slot->event->group;
        $this->authorize('manage', $group);

        $candidate = EventCandidate::where('id', $request->integer('candidate_id'))
            ->where('event_function_slot_id', $slot->id)
            ->firstOrFail();

        if ($request->string('decision')->toString() === 'rejeitar') {
            $candidate->update(['status' => 'rejeitado', 'decided_at' => now(), 'decided_by' => $request->user()->id]);
            if ($request->header('X-Inertia')) {
                return back()->with('success', 'Candidatura rejeitada.');
            }

            return response()->json($candidate);
        }

        $candidate->update(['status' => 'aprovado', 'decided_at' => now(), 'decided_by' => $request->user()->id]);

        $assignment = EventAssignment::updateOrCreate([
            'event_function_slot_id' => $slot->id,
        ], [
            'user_id' => $candidate->user_id,
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        $slot->update(['status' => 'preenchida', 'approved_candidate_user_id' => $candidate->user_id]);

        AttendanceRecord::firstOrCreate([
            'event_id' => $slot->event_id,
            'user_id' => $candidate->user_id,
        ], [
            'event_assignment_id' => $assignment->id,
            'status' => 'pendente',
        ]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Escala atualizada.');
        }

        return response()->json($assignment);
    }
}
