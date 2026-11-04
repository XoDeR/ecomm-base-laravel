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
 * @property array<int, array<string, mixed>>|null $selected_options
 * @property int $quantity
 */
#[Fillable(['user_id', 'session_id', 'product_id', 'selected_options', 'quantity'])]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'selected_options' => 'array',
            'quantity' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Items owned by the signed-in user, or by the guest session.
     */
    public function scopeOwnedBy(Builder $query, ?int $userId, string $sessionId): Builder
    {
        return $userId
            ? $query->where('user_id', $userId)
            : $query->whereNull('user_id')->where('session_id', $sessionId);
    }

    /**
     * Unit price including the price modifiers of the selected options.
     */
    public function unitPrice(): float
    {
        $price = $this->product->final_price;

        foreach ($this->selected_options ?? [] as $option) {
            $price += (float) ($option['price_delta'] ?? 0);
        }

        return max(0, $price);
    }

    public function lineTotal(): float
    {
        return $this->unitPrice() * $this->quantity;
    }
}
