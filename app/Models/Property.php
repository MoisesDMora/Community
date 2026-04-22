<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = ['user_id', 'tower', 'apartment', 'type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
