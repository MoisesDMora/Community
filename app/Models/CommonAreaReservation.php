<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['common_area_id', 'user_id', 'start_time', 'end_time', 'people_count', 'status', 'notes', 'rejection_reason', 'calculated_fee'])]
class CommonAreaReservation extends Model
{
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function commonArea()
    {
        return $this->belongsTo(CommonArea::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
