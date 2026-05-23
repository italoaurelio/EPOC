<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventEnvironmentSubmission extends Model
{
    protected $fillable = ['event_id', 'environment', 'photo_path', 'observation', 'submitted_by', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }
}
