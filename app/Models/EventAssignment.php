<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = ['event_function_slot_id', 'user_id', 'assigned_by', 'assigned_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function slot(): BelongsTo { return $this->belongsTo(EventFunctionSlot::class, 'event_function_slot_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
