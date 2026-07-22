<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $keyword
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Category> $categories
 * @property-read int|null $categories_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType whereKeyword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionType whereUserId($value)
 *
 * @mixin \Eloquent
 */
class TransactionType extends Model
{
    protected $fillable = ['user_id', 'name', 'keyword'];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'type_id');
    }
}
