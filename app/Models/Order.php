<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $total
 * @property string $shipping_cost
 * @property string $discount_amount
 * @property int|null $promo_code_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property string $customer_email
 * @property int|null $delivery_id
 * @property int|null $payment_id
 * @property string|null $delivery_method
 * @property string|null $payment_method
 * @property string|null $delivery_city
 * @property string|null $delivery_warehouse
 * @property string|null $delivery_address
 * @property OrderStatus $status
 * @property string|null $tracking_number
 * @property string|null $comment
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'total', 'shipping_cost', 'discount_amount', 'promo_code_id',
    'customer_name', 'customer_phone', 'customer_email',
    'delivery_id', 'payment_id', 'delivery_method', 'payment_method',
    'delivery_city', 'delivery_warehouse', 'delivery_address',
    'status', 'tracking_number', 'comment', 'meta',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'status' => OrderStatus::class,
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('changed_at')->orderByDesc('id');
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(ShopMethod::class, 'delivery_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(ShopMethod::class, 'payment_id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /**
     * Change the status and keep a history record.
     */
    public function transitionTo(OrderStatus $status, ?string $trackingNumber = null, ?int $changedBy = null): void
    {
        $old = $this->status;

        $this->status = $status;
        if ($trackingNumber !== null && $trackingNumber !== '') {
            $this->tracking_number = $trackingNumber;
        }
        $this->save();

        $this->statusHistory()->create([
            'old_status' => $old?->value,
            'new_status' => $status->value,
            'tracking_number' => $this->tracking_number,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
