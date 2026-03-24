<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'reference',
        'description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
