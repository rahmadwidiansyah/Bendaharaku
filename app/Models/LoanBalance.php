<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanBalance extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'loan_type',
        'balance',
        'opened_at',
        'last_transaction_at',
    ];

    protected $casts = [
        'balance' => 'float',
        'opened_at' => 'date',
        'last_transaction_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
