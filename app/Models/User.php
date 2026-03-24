<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_name',
        'pseudo',
        'phone',
        'email',
        'password',
        'activity_type',
        'location',
        'registry_url',
        'identity_url',
        'role',
        'otp_code',
        'otp_expires_at',
        'verified_at',
        'is_verified',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function tontines()
    {
        return $this->belongsToMany(TontineGroup::class, 'tontine_members')
            ->withPivot('score', 'position');
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function request()
    {
        return $this->hasMany(Request::class);
    }

    public function joinrequest()
    {
        return $this->hasMany(JoinRequest::class);
    }
}