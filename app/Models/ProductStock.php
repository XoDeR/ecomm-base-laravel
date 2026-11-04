<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $product_attribute_id
 * @property string|null $sku
 * @property int $quantity
 * @property int $reserved
 */
#[Fillable(['product_id', 'product_attribute_id', 'sku', 'quantity', 'reserved'])]
class ProductStock extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function available(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }
}
