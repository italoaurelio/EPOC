<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_id', 'location_id', 'type', 'audience', 'name', 'event_date', 'event_time',
        'notes', 'status', 'liturgical_color', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function slots(): HasMany { return $this->hasMany(EventFunctionSlot::class); }
    public function environmentSubmissions(): HasMany { return $this->hasMany(EventEnvironmentSubmission::class); }
    public function invitees(): HasMany { return $this->hasMany(EventInvitee::class); }
    public function attendanceRecords(): HasMany { return $this->hasMany(AttendanceRecord::class); }
}
