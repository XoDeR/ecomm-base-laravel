<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $promo_code_id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property int|null $order_id
 * @property string $discount_amount
 */
#[Fillable(['promo_code_id', 'user_id', 'session_id', 'order_id', 'discount_amount'])]
class PromoCodeUsage extends Model
{
    protected function casts(): array
    {
        return ['discount_amount' => 'decimal:2'];
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
