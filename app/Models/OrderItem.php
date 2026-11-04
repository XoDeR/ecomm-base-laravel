<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string|null $sku
 * @property array<int, array<string, mixed>>|null $selected_options
 * @property int $qty
 * @property string $price
 */
#[Fillable(['order_id', 'product_id', 'product_name', 'sku', 'selected_options', 'qty', 'price'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'selected_options' => 'array',
            'qty' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): float
    {
        return (float) $this->price * $this->qty;
    }
}
