<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $type_id
 * @property string $category_name
 * @property string|null $icon
 * @property string|null $keyword
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransactionLog> $transactionLogs
 * @property-read int|null $transaction_logs_count
 * @property-read \App\Models\TransactionType $type
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereKeyword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withoutTrashed()
 * @mixin \Eloquent
 */
class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type_id',
        'category_name',
        'icon',
        'keyword',
        'is_active',
        'system_key',
        'custom_name',
        'custom_icon',
    ];

    /**
     * Get the category name (display custom name if set for system categories).
     */
    public function getCategoryNameAttribute($value)
    {
        return $this->custom_name ?? $value;
    }

    /**
     * Get the category icon (display custom icon if set for system categories).
     */
    public function getIconAttribute($value)
    {
        return $this->custom_icon ?? $value;
    }

    /**
     * Set the category name (write to custom_name if it is a system category).
     */
    public function setCategoryNameAttribute($value)
    {
        if ($this->exists && $this->system_key !== null) {
            $this->attributes['custom_name'] = $value;
        } else {
            $this->attributes['category_name'] = $value;
        }
    }

    /**
     * Set the category icon (write to custom_icon if it is a system category).
     */
    public function setIconAttribute($value)
    {
        if ($this->exists && $this->system_key !== null) {
            $this->attributes['custom_icon'] = $value;
        } else {
            $this->attributes['icon'] = $value;
        }
    }

    /**
     * Relasi ke User (Kategori dimiliki oleh User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke TransactionType (Kategori terikat pada satu tipe, misal Income/Expense).
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'type_id');
    }

    /**
     * Relasi ke TransactionLogs (Kategori digunakan di banyak log transaksi).
     */
    public function transactionLogs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }
}