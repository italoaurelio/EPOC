<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCandidate extends Model
{
    protected $fillable = ['event_function_slot_id', 'user_id', 'status', 'decided_at', 'decided_by'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
