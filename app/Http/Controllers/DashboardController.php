<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\EventEnvironmentSubmission;
use App\Models\EventFunctionSlot;
use App\Models\GroupMembership;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->system_role === 'admin_sistema') {
            $approvedMemberships = collect();
            $groupIds = Event::query()->distinct()->pluck('group_id');
            $coordinatorGroupIds = $groupIds;
        } else {
            $approvedMemberships = GroupMembership::with('group')
                ->where('user_id', $user->id)
                ->where('status', 'aprovado')
                ->get();

            $groupIds = $approvedMemberships->pluck('group_id');
            $coordinatorGroupIds = $approvedMemberships->where('role', 'coordenador')->pluck('group_id');
        }

        $upcomingEvents = Event::with('group')
            ->whereIn('group_id', $groupIds)
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->limit(12)
            ->get();

        $myAssignments = EventAssignment::with(['slot.event.group', 'slot.eventFunction'])
            ->where('user_id', $user->id)
            ->latest('assigned_at')
            ->limit(10)
            ->get();

        $pendingAttendance = AttendanceRecord::with('event')
            ->where('user_id', $user->id)
            ->where('status', 'pendente')
            ->get();

        $myPendingPhotos = $myAssignments->filter(function (EventAssignment $assignment) {
            $event = $assignment->slot?->event;
            if (!$event || $event->type !== 'missa') {
                return false;
            }

            $submissionCount = EventEnvironmentSubmission::where('event_id', $event->id)->count();
            return $submissionCount === 0;
        })->map(fn (EventAssignment $assignment) => [
            'event_id' => $assignment->slot->event->id,
            'event_name' => $assignment->slot->event->name,
            'event_date' => $assignment->slot->event->event_date,
        ])->values();

        $coordinatorStats = null;

        if ($user->system_role === 'admin_sistema' || $coordinatorGroupIds->isNotEmpty()) {
            $scopeIds = $user->system_role === 'admin_sistema' ? $groupIds : $coordinatorGroupIds;

            $coordinatorStats = [
                'pending_memberships' => GroupMembership::whereIn('group_id', $scopeIds)->where('status', 'pendente')->count(),
                'pending_attendance' => AttendanceRecord::whereIn('event_id', Event::whereIn('group_id', $scopeIds)->pluck('id'))->where('status', 'pendente')->count(),
                'open_slots' => EventFunctionSlot::whereIn('event_id', Event::whereIn('group_id', $scopeIds)->pluck('id'))->where('status', 'aberta')->count(),
                'upcoming_masses' => Event::whereIn('group_id', $scopeIds)->where('type', 'missa')->whereDate('event_date', '>=', now()->toDateString())->count(),
                'upcoming_meetings' => Event::whereIn('group_id', $scopeIds)->where('type', 'reuniao')->whereDate('event_date', '>=', now()->toDateString())->count(),
                'unconfirmed_people' => AttendanceRecord::whereIn('event_id', Event::whereIn('group_id', $scopeIds)->pluck('id'))->where('status', 'pendente')->distinct('user_id')->count('user_id'),
                'pending_environment_photos' => Event::whereIn('group_id', $scopeIds)
                    ->where('type', 'missa')
                    ->get()
                    ->filter(fn (Event $event) => $event->environmentSubmissions()->count() === 0)
                    ->count(),
                'last_mass_summary' => Event::whereIn('group_id', $scopeIds)
                    ->where('type', 'missa')
                    ->whereDate('event_date', '<=', now()->toDateString())
                    ->latest('event_date')
                    ->first()?->only(['id', 'name', 'event_date', 'event_time']),
            ];
        }

        return Inertia::render('Dashboard', [
            'memberships' => $approvedMemberships,
            'upcomingEvents' => $upcomingEvents,
            'myAssignments' => $myAssignments,
            'pendingAttendance' => $pendingAttendance,
            'myPendingPhotos' => $myPendingPhotos,
            'coordinatorStats' => $coordinatorStats,
        ]);
    }
}
