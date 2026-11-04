<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A value of an attribute set on a product (optionally a selectable variant
 * with its own price modifier and stock).
 *
 * @property int $id
 * @property string|null $sku
 * @property int $product_id
 * @property int $attribute_id
 * @property string|null $value
 * @property int|null $attribute_option_id
 * @property string $price_modifier
 * @property string $price_operation + or -
 * @property int|null $stock_quantity
 * @property bool $is_selectable
 */
#[Fillable([
    'sku', 'product_id', 'attribute_id', 'value', 'attribute_option_id',
    'price_modifier', 'price_operation', 'stock_quantity', 'is_selectable',
])]
class ProductAttribute extends Model
{
    protected function casts(): array
    {
        return [
            'price_modifier' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_selectable' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'attribute_option_id');
    }

    /** Signed price delta applied to the product price. */
    public function priceDelta(): float
    {
        $amount = (float) $this->price_modifier;

        return $this->price_operation === '-' ? -$amount : $amount;
    }

    /** Human readable value: option name or the free-text value. */
    public function displayValue(): string
    {
        return (string) ($this->option?->name ?? $this->value ?? '');
    }
}
