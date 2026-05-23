<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = ['event_id', 'user_id', 'event_assignment_id', 'status', 'answered_at'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventAssignment(): BelongsTo
    {
        return $this->belongsTo(EventAssignment::class);
    }
}
