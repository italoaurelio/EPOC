<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventFunctionRequest;
use App\Models\EventFunction;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EventFunctionController extends Controller
{
    public function index(Group $group): JsonResponse
    {
        $this->authorize('manage', $group);

        return response()->json(
            EventFunction::where('group_id', $group->id)
                ->orderByDesc('is_initially_active')
                ->orderBy('name')
                ->get(),
        );
    }

    public function store(StoreEventFunctionRequest $request, Group $group): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', $group);

        $function = EventFunction::create([
            'group_id' => $group->id,
            'name' => $request->string('name')->toString(),
            'is_default' => false,
            'is_initially_active' => $request->boolean('is_initially_active', true),
        ]);

        if ($request->header('X-Inertia')) {
            return to_route('events.index')->with('success', 'Função adicionada.');
        }

        return response()->json($function, 201);
    }
}
