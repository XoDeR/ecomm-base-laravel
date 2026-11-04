<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A shipping or payment method offered at checkout.
 *
 * @property int $id
 * @property string $type shipping|payment
 * @property string $code
 * @property string $name
 * @property string|null $icon
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_test_mode
 * @property array<string, mixed>|null $settings
 * @property int $sort_order
 */
#[Fillable(['type', 'code', 'name', 'icon', 'description', 'is_active', 'is_test_mode', 'settings', 'sort_order'])]
class ShopMethod extends Model
{
    public const SHIPPING = 'shipping';

    public const PAYMENT = 'payment';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_test_mode' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeShipping(Builder $query): Builder
    {
        return $query->where('type', self::SHIPPING);
    }

    public function scopePayment(Builder $query): Builder
    {
        return $query->where('type', self::PAYMENT);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function shippingCost(): float
    {
        return (float) $this->setting('cost', 0);
    }
}
