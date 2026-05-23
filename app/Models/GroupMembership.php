<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupMembership extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_id', 'user_id', 'role', 'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
