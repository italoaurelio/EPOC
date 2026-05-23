<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;

class GroupPolicy
{
    public function manage(User $user, Group $group): bool
    {
        if ($user->system_role === 'admin_sistema') {
            return true;
        }

        return GroupMembership::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('role', 'coordenador')
            ->where('status', 'aprovado')
            ->exists();
    }
}
