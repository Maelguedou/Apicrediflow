<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TontineMembers extends Model
{
    protected $fillable = [
        'name',
        'contribution_amount',
        'frequency',
        'max_members'
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'tontine_members')
            ->withPivot('score', 'position');
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }
}
