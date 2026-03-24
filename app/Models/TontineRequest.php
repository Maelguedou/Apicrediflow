<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TontineRequest extends Model
{
    protected $fillable = ['user_id','contribution_amount','frequency','duration'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
