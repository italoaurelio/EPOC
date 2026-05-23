<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInviteLinkRequest;
use App\Models\Group;
use App\Models\InviteLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class InviteLinkController extends Controller
{
    public function store(StoreInviteLinkRequest $request, Group $group): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', $group);

        $invite = InviteLink::create([
            'group_id' => $group->id,
            'token' => Str::random(40),
            'role' => $request->string('role'),
            'requires_approval' => $request->boolean('requires_approval'),
            'created_by' => $request->user()->id,
        ]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Convite gerado com sucesso.');
        }

        return response()->json($invite, 201);
    }
}
