<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Substitution extends Model
{
    protected $fillable = ['event_id', 'replaced_user_id', 'replacement_user_id', 'created_by', 'source', 'reason'];
}
