<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property int $product_id
 */
#[Fillable(['user_id', 'session_id', 'product_id'])]
class CompareItem extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeOwnedBy(Builder $query, ?int $userId, string $sessionId): Builder
    {
        return $userId
            ? $query->where('user_id', $userId)
            : $query->whereNull('user_id')->where('session_id', $sessionId);
    }
}
