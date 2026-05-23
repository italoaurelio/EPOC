<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->system_role === 'admin_sistema') {
            $groups = Group::with(['memberships.user'])->get()->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'role' => 'admin_sistema',
                'memberships' => $group->memberships->map(fn (GroupMembership $item) => [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name,
                    'user_email' => $item->user?->email,
                    'role' => $item->role,
                    'status' => $item->status,
                ])->values(),
            ]);
        } else {
            $groups = GroupMembership::with(['group', 'group.memberships.user'])
                ->where('user_id', $user->id)
                ->where('status', 'aprovado')
                ->get()
                ->map(fn (GroupMembership $membership) => [
                    'id' => $membership->group->id,
                    'name' => $membership->group->name,
                    'description' => $membership->group->description,
                    'role' => $membership->role,
                    'memberships' => $membership->group->memberships->map(fn (GroupMembership $item) => [
                        'id' => $item->id,
                        'user_id' => $item->user_id,
                        'user_name' => $item->user?->name,
                        'user_email' => $item->user?->email,
                        'role' => $item->role,
                        'status' => $item->status,
                    ])->values(),
                ]);
        }

        return Inertia::render('Groups/Index', ['groups' => $groups]);
    }

    public function store(StoreGroupRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $group = Group::create([
            'name' => $request->string('name'),
            'description' => $request->string('description')->toString(),
            'created_by' => $user->id,
        ]);

        GroupMembership::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'coordenador',
            'status' => 'aprovado',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        if ($request->header('X-Inertia')) {
            return to_route('groups.index')->with('success', 'Grupo criado com sucesso.');
        }

        return response()->json($group, 201);
    }

    public function update(UpdateGroupRequest $request, Group $group): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', $group);

        $group->update([
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString(),
        ]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Grupo atualizado com sucesso.');
        }

        return response()->json($group);
    }
}
