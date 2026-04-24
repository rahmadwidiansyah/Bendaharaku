<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property numeric $balance
 * @property string $group_type
 * @property string|null $icon
 * @property string|null $keyword
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereKeyword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wallet withoutTrashed()
 * @mixin \Eloquent
 */
class Wallet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'balance',
        'group_type',
        'icon',
        'keyword',
        'is_active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}