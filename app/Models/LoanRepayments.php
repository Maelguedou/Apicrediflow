<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayments extends Model
{
    protected $fillable = [
        'loan_id',
        'amount',
        'due_date',
        'paid',
        'paid_at'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}