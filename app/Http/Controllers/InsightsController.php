<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\GroupMembership;
use App\Models\Substitution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $groupIds = GroupMembership::where('user_id', $user->id)
            ->where('role', 'coordenador')
            ->where('status', 'aprovado')
            ->pluck('group_id');

        if ($user->system_role === 'admin_sistema') {
            $groupIds = GroupMembership::query()->pluck('group_id')->unique()->values();
        }

        $eventIds = Event::whereIn('group_id', $groupIds)->pluck('id');
        $records = AttendanceRecord::whereIn('event_id', $eventIds)->get();

        $byUser = $records->groupBy('user_id')->map(function (Collection $items, int $userId) {
            $present = $items->where('status', 'compareceu')->count();
            $absent = $items->where('status', 'nao_compareceu')->count();
            $notComputed = $items->where('status', 'nao_computado')->count();
            $total = $items->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            return [
                'user_id' => $userId,
                'name' => User::find($userId)?->name ?? 'Desconhecido',
                'presence' => $present,
                'absence' => $absent,
                'not_computed' => $notComputed,
                'total' => $total,
                'presence_rate' => $rate,
                'color' => $total < 3 ? 'gray' : ($rate >= 80 ? 'green' : ($rate >= 50 ? 'yellow' : 'red')),
            ];
        })->values();

        return Inertia::render('Insights/Index', [
            'byUser' => $byUser,
            'attendanceByMass' => $records->whereIn('event_id', Event::whereIn('id', $eventIds)->where('type', 'missa')->pluck('id'))->groupBy('event_id')->map->count(),
            'attendanceByMeeting' => $records->whereIn('event_id', Event::whereIn('id', $eventIds)->where('type', 'reuniao')->pluck('id'))->groupBy('event_id')->map->count(),
            'substitutionsCount' => Substitution::whereIn('event_id', $eventIds)->count(),
            'ranking' => $byUser->sortByDesc('presence_rate')->values(),
        ]);
    }
}
