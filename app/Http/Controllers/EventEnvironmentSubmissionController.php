<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnvironmentSubmissionRequest;
use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\EventEnvironmentSubmission;
use App\Models\GroupMembership;
use Illuminate\Http\JsonResponse;

class EventEnvironmentSubmissionController extends Controller
{
    public function store(StoreEnvironmentSubmissionRequest $request, Event $event): JsonResponse
    {
        $user = $request->user();

        $isCoordinator = GroupMembership::where('group_id', $event->group_id)
            ->where('user_id', $user->id)
            ->where('role', 'coordenador')
            ->where('status', 'aprovado')
            ->exists();

        $isAssigned = EventAssignment::where('user_id', $user->id)
            ->whereIn('event_function_slot_id', $event->slots()->pluck('id'))
            ->exists();

        abort_unless($user->system_role === 'admin_sistema' || $isCoordinator || $isAssigned, 403);

        $environment = $request->string('environment')->toString();

        if ($environment === 'turibulo') {
            $requiredFunctions = $event->slots()->with('eventFunction')->get()->pluck('eventFunction.name')->map(fn ($n) => mb_strtolower($n));
            abort_unless($requiredFunctions->contains('turíbulo') || $requiredFunctions->contains('turibulo'), 422, 'Missa sem função turíbulo.');
            abort_unless($requiredFunctions->contains('naveta'), 422, 'Turíbulo exige naveta.');
        }

        $submission = EventEnvironmentSubmission::firstOrCreate(
            ['event_id' => $event->id, 'environment' => $environment],
            [
                'photo_path' => $request->string('photo_path')->toString(),
                'observation' => $request->string('observation')->toString(),
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ],
        );

        return response()->json($submission, 201);
    }
}
