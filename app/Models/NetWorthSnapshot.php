<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetWorthSnapshot extends Model
{
    protected $table = 'net_worth_snapshots';

    protected $fillable = [
        'user_id', 'snapshot_date', 'total_wallet_balance', 'total_receivables', 'total_debts', 'net_worth',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'total_wallet_balance' => 'float',
        'total_receivables' => 'float',
        'total_debts' => 'float',
        'net_worth' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
