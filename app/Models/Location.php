<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = ['group_id', 'name', 'street', 'number', 'district', 'city', 'state', 'complement'];

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
}
