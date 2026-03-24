<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Contributions extends Model
{
    protected $fillable = [
        'user_id',
        'tontine_group_id',
        'amount',
        'due_date',
        'paid'
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
