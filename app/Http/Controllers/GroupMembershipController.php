<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGroupMembershipRequest;
use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupMembershipController extends Controller
{
    public function index(Request $request, Group $group): JsonResponse
    {
        $this->authorize('manage', $group);

        $memberships = GroupMembership::with('user')
            ->where('group_id', $group->id)
            ->get();

        return response()->json($memberships);
    }

    public function update(UpdateGroupMembershipRequest $request, Group $group, GroupMembership $membership): JsonResponse|RedirectResponse
    {
        $this->authorize('manage', $group);
        abort_unless($membership->group_id === $group->id, 404);

        $action = $request->string('action')->toString();

        if ($action === 'approve') {
            $membership->update(['status' => 'aprovado', 'approved_at' => now(), 'approved_by' => $request->user()->id]);
        }

        if ($action === 'reject') {
            $membership->update(['status' => 'rejeitado']);
        }

        if ($action === 'role') {
            $membership->update(['role' => $request->string('role')->toString()]);
        }

        if ($action === 'remove') {
            $membership->delete();
        }

        if ($request->header('X-Inertia')) {
            $message = match ($action) {
                'approve' => 'Membro aprovado com sucesso.',
                'reject' => 'Solicitação rejeitada.',
                'role' => 'Função do membro atualizada.',
                'remove' => 'Membro removido.',
                default => 'Membro atualizado.',
            };

            return back()->with('success', $message);
        }

        return response()->json($membership->fresh());
    }
}
