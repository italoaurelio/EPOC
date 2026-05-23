<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventFunctionSlot extends Model
{
    use SoftDeletes;

    protected $fillable = ['event_id', 'event_function_id', 'slot_order', 'status', 'approved_candidate_user_id'];

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function eventFunction(): BelongsTo { return $this->belongsTo(EventFunction::class, 'event_function_id'); }
    public function assignment(): HasOne { return $this->hasOne(EventAssignment::class, 'event_function_slot_id'); }
    public function candidates(): HasMany { return $this->hasMany(EventCandidate::class, 'event_function_slot_id'); }
}
