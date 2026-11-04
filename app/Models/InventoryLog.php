<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $product_id
 * @property string|null $sku
 * @property string $event_type
 * @property int $qty
 * @property string|null $comment
 */
#[Fillable(['product_id', 'sku', 'event_type', 'qty', 'comment'])]
class InventoryLog extends Model
{
    protected $table = 'inventory_log';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
