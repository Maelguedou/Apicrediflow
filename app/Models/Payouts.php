<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payouts extends Model
{
    protected $fillable = [
        'user_id',
        'tontine_group_id',
        'round',
        'amount',
        'paid_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tontine()
    {
        return $this->belongsTo(TontineGroup::class);
    }
}
