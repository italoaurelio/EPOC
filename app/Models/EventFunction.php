<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventFunction extends Model
{
    use SoftDeletes;

    protected $fillable = ['group_id', 'name', 'is_default', 'is_initially_active'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_initially_active' => 'boolean'];
    }

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
}
