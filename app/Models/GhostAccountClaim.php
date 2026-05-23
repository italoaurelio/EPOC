<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GhostAccountClaim extends Model
{
    protected $fillable = ['group_id', 'ghost_user_id', 'real_user_id', 'status', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }
}
