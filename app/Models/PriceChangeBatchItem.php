<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $batch_id
 * @property int $product_id
 * @property string $old_price
 * @property string $new_price
 */
#[Fillable(['batch_id', 'product_id', 'old_price', 'new_price'])]
class PriceChangeBatchItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PriceChangeBatch::class, 'batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
