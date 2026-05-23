<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubstitutionRequest;
use App\Models\Event;
use App\Models\GroupMembership;
use App\Models\Substitution;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SubstitutionController extends Controller
{
    public function store(StoreSubstitutionRequest $request): JsonResponse|RedirectResponse
    {
        $event = Event::findOrFail($request->integer('event_id'));
        $groupId = $event->group_id;

        $isCoordinator = GroupMembership::where('group_id', $groupId)
            ->where('user_id', $request->user()->id)
            ->where('role', 'coordenador')
            ->where('status', 'aprovado')
            ->exists();

        $isReplacingSelf = $request->integer('replaced_user_id') === $request->user()->id;
        abort_unless($request->user()->system_role === 'admin_sistema' || $isCoordinator || $isReplacingSelf, 403);

        $replacementId = $request->input('replacement_user_id');
        if (!$replacementId && $request->filled('replacement_name')) {
            $ghost = User::create([
                'name' => $request->string('replacement_name'),
                'is_ghost' => true,
                'system_role' => 'membro',
            ]);
            $membership = GroupMembership::where('user_id', $request->integer('replaced_user_id'))->first();
            if ($membership) {
                GroupMembership::firstOrCreate(['group_id' => $membership->group_id, 'user_id' => $ghost->id], ['role' => 'membro', 'status' => 'aprovado']);
            }
            $replacementId = $ghost->id;
        }

        $substitution = Substitution::create([
            'event_id' => $event->id,
            'replaced_user_id' => $request->integer('replaced_user_id'),
            'replacement_user_id' => $replacementId,
            'created_by' => $request->user()->id,
            'source' => 'manual',
            'reason' => $request->string('reason')->toString(),
        ]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Substituição registrada.');
        }

        return response()->json($substitution, 201);
    }
}
