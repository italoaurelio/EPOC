<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InviteLink extends Model
{
    protected $fillable = ['group_id', 'token', 'role', 'requires_approval', 'expires_at', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['requires_approval' => 'boolean', 'is_active' => 'boolean', 'expires_at' => 'datetime'];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
}
