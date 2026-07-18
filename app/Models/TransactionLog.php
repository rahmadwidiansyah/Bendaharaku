<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $reference_number
 * @property int $user_id
 * @property string $date
 * @property int $type_id
 * @property int $category_id
 * @property int $source_wallet_id
 * @property int|null $destination_wallet_id
 * @property numeric $amount
 * @property numeric $balance_before
 * @property numeric $balance_after
 * @property string $subject
 * @property string|null $notes
 * @property bool $is_cleared
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Wallet|null $destinationWallet
 * @property-read \App\Models\Wallet|null $sourceWallet
 * @property-read \App\Models\TransactionType $type
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereBalanceAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereBalanceBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereDestinationWalletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereIsCleared($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereSourceWalletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog withoutTrashed()
 * @mixin \Eloquent
 */
class TransactionLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'user_id',
        'date',
        'type_id',
        'category_id',
        'source_wallet_id',
        'destination_wallet_id',
        'amount',
        'balance_before',
        'balance_after',
        'subject',
        'notes',
        'is_cleared',
        'due_date',
        'due_date_type',
        'due_date_interval',
    ];

    protected $casts = [
        // PostgreSQL DECIMAL(15,2) dikembalikan sebagai string oleh PDO.
        // Cast ke float agar seluruh consumer (Formatter, Adapter, API) menerima tipe numerik.
        'amount'         => 'float',
        'balance_before' => 'float',
        'balance_after'  => 'float',
        'is_cleared'     => 'boolean',
        // Cast date ke Carbon agar ->toDateString() dan method Carbon lainnya tersedia.
        'date'           => 'date',
        'due_date'       => 'date',
    ];

    // Relasi
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function type(): BelongsTo { return $this->belongsTo(TransactionType::class, 'type_id'); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function sourceWallet(): BelongsTo { return $this->belongsTo(Wallet::class, 'source_wallet_id'); }
    public function destinationWallet(): BelongsTo { return $this->belongsTo(Wallet::class, 'destination_wallet_id'); }
}