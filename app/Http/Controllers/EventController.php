<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\EventFunction;
use App\Models\EventFunctionSlot;
use App\Models\EventInvitee;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $groupIds = $user->system_role === 'admin_sistema'
            ? Group::pluck('id')
            : GroupMembership::where('user_id', $user->id)->where('status', 'aprovado')->pluck('group_id');

        $events = Event::with(['group', 'invitees.user', 'attendanceRecords.user', 'slots.eventFunction', 'slots', 'slots.assignment', 'slots.assignment.user', 'slots.candidates.user'])
            ->whereIn('group_id', $groupIds)
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'groups' => Group::whereIn('id', $groupIds)->get(['id', 'name']),
            'groupFunctions' => EventFunction::whereIn('group_id', $groupIds)
                ->orderByDesc('is_initially_active')
                ->orderBy('name')
                ->get(['id', 'group_id', 'name', 'is_initially_active']),
            'groupMembers' => GroupMembership::with('user')
                ->whereIn('group_id', $groupIds)
                ->where('status', 'aprovado')
                ->get()
                ->map(fn (GroupMembership $membership) => [
                    'group_id' => $membership->group_id,
                    'user_id' => $membership->user_id,
                    'name' => $membership->user?->name,
                ])->values(),
        ]);
    }

    public function store(StoreEventRequest $request, Group $group): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', $group);
        $type = $request->string('type')->toString();
        $audience = $request->string('audience')->toString() ?: 'all';

        $functionIds = collect($request->input('function_ids', []))->map(fn ($id) => (int) $id)->values();
        $validFunctionIds = EventFunction::where('group_id', $group->id)->pluck('id');
        $invalidFunction = $functionIds->first(fn (int $id) => !$validFunctionIds->contains($id));
        if ($invalidFunction) {
            return response()->json(['message' => 'Função inválida para este grupo.'], 422);
        }

        if ($type === 'missa' && $functionIds->isEmpty()) {
            return response()->json(['message' => 'Missa requer ao menos uma função.'], 422);
        }

        if ($type === 'missa') {
            $selectedNames = EventFunction::whereIn('id', $functionIds)->pluck('name')->map(fn ($name) => mb_strtolower($name));
            $hasTuribulo = $selectedNames->contains('turíbulo') || $selectedNames->contains('turibulo');
            $hasNaveta = $selectedNames->contains('naveta');
            if ($hasTuribulo && !$hasNaveta) {
                return response()->json(['message' => 'Se houver turíbulo, naveta também deve ser marcada.'], 422);
            }
        }

        $event = Event::create([
            'group_id' => $group->id,
            'type' => $type,
            'audience' => $audience,
            'name' => $request->string('name')->toString(),
            'event_date' => $request->string('event_date')->toString(),
            'event_time' => $request->string('event_time')->toString(),
            'notes' => $request->string('notes')->toString() ?: null,
            'liturgical_color' => $request->string('liturgical_color')->toString() ?: null,
            'status' => 'agendado',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        if ($request->filled('location.name')) {
            $location = Location::create([
                'group_id' => $group->id,
                'name' => $request->input('location.name'),
                'street' => $request->input('location.street'),
                'number' => $request->input('location.number'),
                'district' => $request->input('location.district'),
                'city' => $request->input('location.city'),
                'state' => $request->input('location.state'),
                'complement' => $request->input('location.complement'),
            ]);
            $event->update(['location_id' => $location->id]);
        }

        foreach ($functionIds as $functionId) {
            $slot = EventFunctionSlot::create([
                'event_id' => $event->id,
                'event_function_id' => $functionId,
                'slot_order' => 1,
                'status' => 'aberta',
            ]);

            $this->applySlotAssignment($request, $group->id, $slot, (int) $functionId);
        }

        if ($type === 'reuniao') {
            if ($audience === 'all') {
                $userIds = GroupMembership::where('group_id', $group->id)
                    ->where('status', 'aprovado')
                    ->pluck('user_id');
            } else {
                $userIds = collect($request->input('invitee_user_ids', []));
                $validInviteeIds = GroupMembership::where('group_id', $group->id)
                    ->where('status', 'aprovado')
                    ->pluck('user_id');
                $invalidInvitee = $userIds->first(fn ($id) => !$validInviteeIds->contains((int) $id));
                if ($invalidInvitee) {
                    return response()->json(['message' => 'Convidado não pertence ao grupo.'], 422);
                }
            }

            foreach ($userIds as $userId) {
                EventInvitee::firstOrCreate(['event_id' => $event->id, 'user_id' => $userId]);
                AttendanceRecord::firstOrCreate(
                    ['event_id' => $event->id, 'user_id' => $userId],
                    ['status' => 'pendente'],
                );
            }
        }

        if ($request->header('X-Inertia')) {
            return to_route('events.index')->with('success', 'Evento salvo.');
        }

        return response()->json($event->load('slots'), 201);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse|RedirectResponse
    {
        $group = $event->group;
        $this->authorize('manage', $group);

        $type = $event->type;
        $audience = $request->string('audience')->toString() ?: $event->audience;
        $functionIds = collect($request->input('function_ids', []))->map(fn ($id) => (int) $id)->values();
        $validFunctionIds = EventFunction::where('group_id', $group->id)->pluck('id');
        $invalidFunction = $functionIds->first(fn (int $id) => !$validFunctionIds->contains($id));
        if ($invalidFunction) {
            return response()->json(['message' => 'Função inválida para este grupo.'], 422);
        }

        if ($type === 'missa' && $functionIds->isEmpty()) {
            return response()->json(['message' => 'Missa requer ao menos uma função.'], 422);
        }

        if ($type === 'missa') {
            $selectedNames = EventFunction::whereIn('id', $functionIds)->pluck('name')->map(fn ($name) => mb_strtolower($name));
            $hasTuribulo = $selectedNames->contains('turíbulo') || $selectedNames->contains('turibulo');
            $hasNaveta = $selectedNames->contains('naveta');
            if ($hasTuribulo && !$hasNaveta) {
                return response()->json(['message' => 'Se houver turíbulo, naveta também deve ser marcada.'], 422);
            }
        }

        DB::transaction(function () use ($request, $event, $type, $audience, $functionIds, $group) {
            $event->update([
                'name' => $request->string('name')->toString(),
                'event_date' => $request->string('event_date')->toString(),
                'event_time' => $request->string('event_time')->toString(),
                'notes' => $request->string('notes')->toString() ?: null,
                'liturgical_color' => $request->string('liturgical_color')->toString() ?: null,
                'audience' => $audience,
                'updated_by' => $request->user()->id,
            ]);

            if ($request->filled('location.name')) {
                $location = $event->location_id
                    ? Location::find($event->location_id)
                    : new Location(['group_id' => $group->id]);

                if (!$location) {
                    $location = new Location(['group_id' => $group->id]);
                }

                $location->fill([
                    'group_id' => $group->id,
                    'name' => $request->input('location.name'),
                    'street' => $request->input('location.street'),
                    'number' => $request->input('location.number'),
                    'district' => $request->input('location.district'),
                    'city' => $request->input('location.city'),
                    'state' => $request->input('location.state'),
                    'complement' => $request->input('location.complement'),
                ]);
                $location->save();
                $event->update(['location_id' => $location->id]);
            }

            if ($type === 'missa') {
                EventFunctionSlot::where('event_id', $event->id)->delete();
                foreach ($functionIds as $functionId) {
                    $slot = EventFunctionSlot::create([
                        'event_id' => $event->id,
                        'event_function_id' => $functionId,
                        'slot_order' => 1,
                        'status' => 'aberta',
                    ]);
                    $this->applySlotAssignment($request, $group->id, $slot, (int) $functionId);
                }
            }

            if ($type === 'reuniao') {
                $inviteeIds = $audience === 'all'
                    ? GroupMembership::where('group_id', $group->id)->where('status', 'aprovado')->pluck('user_id')
                    : collect($request->input('invitee_user_ids', []));

                if ($audience === 'specific') {
                    $validInviteeIds = GroupMembership::where('group_id', $group->id)
                        ->where('status', 'aprovado')
                        ->pluck('user_id');
                    $invalidInvitee = $inviteeIds->first(fn ($id) => !$validInviteeIds->contains((int) $id));
                    if ($invalidInvitee) {
                        abort(422, 'Convidado não pertence ao grupo.');
                    }
                }

                $event->invitees()->delete();
                $event->attendanceRecords()->delete();

                foreach ($inviteeIds as $userId) {
                    EventInvitee::firstOrCreate(['event_id' => $event->id, 'user_id' => $userId]);
                    AttendanceRecord::firstOrCreate(['event_id' => $event->id, 'user_id' => $userId], ['status' => 'pendente']);
                }
            }
        });

        if ($request->header('X-Inertia')) {
            return to_route('events.index')->with('success', 'Evento salvo.');
        }

        return response()->json($event->fresh(['slots', 'invitees', 'attendanceRecords']));
    }

    private function applySlotAssignment(Request $request, int $groupId, EventFunctionSlot $slot, int $functionId): void
    {
        $assignment = $request->input("slot_assignments.$functionId");
        if (!is_array($assignment)) {
            return;
        }

        $mode = $assignment['mode'] ?? 'vacancy';
        if ($mode === 'vacancy') {
            return;
        }

        $userId = null;
        if ($mode === 'member' && !empty($assignment['user_id'])) {
            $candidateId = (int) $assignment['user_id'];
            $belongs = GroupMembership::where('group_id', $groupId)
                ->where('user_id', $candidateId)
                ->where('status', 'aprovado')
                ->exists();
            if ($belongs) {
                $userId = $candidateId;
            }
        }

        if ($mode === 'ghost' && !empty($assignment['ghost_name'])) {
            $ghostName = trim((string) $assignment['ghost_name']);
            if ($ghostName !== '') {
                $ghost = User::create([
                    'name' => $ghostName,
                    'email' => null,
                    'password' => null,
                    'is_ghost' => true,
                    'system_role' => 'membro',
                ]);

                GroupMembership::firstOrCreate([
                    'group_id' => $groupId,
                    'user_id' => $ghost->id,
                ], [
                    'role' => 'membro',
                    'status' => 'aprovado',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);

                $userId = $ghost->id;
            }
        }

        if (!$userId) {
            return;
        }

        $eventAssignment = EventAssignment::create([
            'event_function_slot_id' => $slot->id,
            'user_id' => $userId,
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);

        $slot->update(['status' => 'preenchida']);

        AttendanceRecord::firstOrCreate([
            'event_id' => $slot->event_id,
            'user_id' => $userId,
        ], [
            'event_assignment_id' => $eventAssignment->id,
            'status' => 'pendente',
        ]);
    }
}
