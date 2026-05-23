<?php

namespace App\Http\Controllers;

use App\Models\GhostAccountClaim;
use App\Models\GroupMembership;
use App\Models\InviteLink;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InviteRegistrationController extends Controller
{
    public function show(string $token): Response
    {
        $invite = InviteLink::with('group')->where('token', $token)->where('is_active', true)->firstOrFail();

        abort_if($invite->expires_at && $invite->expires_at->isPast(), 410, 'Convite expirado.');

        $ghostCandidates = User::query()
            ->where('is_ghost', true)
            ->whereIn('id', GroupMembership::where('group_id', $invite->group_id)->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Invites/RegisterFromInvite', [
            'invite' => [
                'token' => $invite->token,
                'group_name' => $invite->group->name,
                'role' => $invite->role,
                'requires_approval' => $invite->requires_approval,
            ],
            'ghostCandidates' => $ghostCandidates,
        ]);
    }

    public function register(Request $request, string $token): RedirectResponse
    {
        $invite = InviteLink::with('group')->where('token', $token)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'claim_ghost_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_ghost', true)),
            ],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'system_role' => 'membro',
        ]);

        GroupMembership::create([
            'group_id' => $invite->group_id,
            'user_id' => $user->id,
            'role' => $invite->role,
            'status' => $invite->requires_approval ? 'pendente' : 'aprovado',
            'approved_at' => $invite->requires_approval ? null : now(),
            'approved_by' => $invite->requires_approval ? null : $invite->created_by,
        ]);

        if (!empty($data['claim_ghost_user_id'])) {
            $belongsToGroup = GroupMembership::where('group_id', $invite->group_id)
                ->where('user_id', (int) $data['claim_ghost_user_id'])
                ->exists();

            abort_unless($belongsToGroup, 422, 'Conta fantasma não pertence ao grupo do convite.');

            GhostAccountClaim::create([
                'group_id' => $invite->group_id,
                'ghost_user_id' => $data['claim_ghost_user_id'],
                'real_user_id' => $user->id,
                'status' => 'pendente',
            ]);
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
