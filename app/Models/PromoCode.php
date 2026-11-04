<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int|null $assigned_user_id
 * @property string $type percent|fixed
 * @property string $value
 * @property string|null $min_order_amount
 * @property string|null $max_discount_amount
 * @property int|null $usage_limit
 * @property int|null $usage_limit_per_user
 * @property int $used_count
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 */
#[Fillable([
    'code', 'assigned_user_id', 'type', 'value', 'min_order_amount', 'max_discount_amount',
    'usage_limit', 'usage_limit_per_user', 'used_count', 'starts_at', 'expires_at', 'is_active',
])]
class PromoCode extends Model
{
    /**
     * Default attribute values (mirror the column defaults).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'percent',
        'used_count' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (PromoCode $promo) => $promo->code = strtoupper(trim($promo->code)));
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    /**
     * Check whether the code can be applied and return an error message, or null when valid.
     */
    public function validationError(float $cartTotal, ?int $userId, string $sessionId): ?string
    {
        $now = now();

        if (! $this->is_active
            || ($this->starts_at && $this->starts_at->gt($now))
            || ($this->expires_at && $this->expires_at->lt($now))) {
            return 'This promo code is not active.';
        }

        if ($this->assigned_user_id !== null && $this->assigned_user_id !== $userId) {
            return 'This promo code is not available for your account.';
        }

        if ($this->min_order_amount !== null && $cartTotal < (float) $this->min_order_amount) {
            return 'The order total is below the minimum required for this promo code.';
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'This promo code has reached its usage limit.';
        }

        if ($this->usage_limit_per_user !== null) {
            $used = $this->usages()
                ->when($userId, fn ($q) => $q->where('user_id', $userId), fn ($q) => $q->where('session_id', $sessionId))
                ->count();

            if ($used >= $this->usage_limit_per_user) {
                return 'You have already used this promo code the maximum number of times.';
            }
        }

        return null;
    }

    public function discountFor(float $cartTotal): float
    {
        $discount = $this->type === 'percent'
            ? $cartTotal * (float) $this->value / 100
            : (float) $this->value;

        if ($this->type === 'percent' && $this->max_discount_amount !== null) {
            $discount = min($discount, (float) $this->max_discount_amount);
        }

        return round(min($discount, $cartTotal), 2);
    }
}
