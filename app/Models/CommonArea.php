<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'time_limit', 'time_unit', 'max_people', 'is_active', 'fee_type', 'fee_amount'])]
class CommonArea extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
        'fee_amount' => 'decimal:2',
    ];

    public function reservations()
    {
        return $this->hasMany(CommonAreaReservation::class);
    }
}
