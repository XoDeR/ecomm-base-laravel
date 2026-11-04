<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $discount_price
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property bool $is_active
 */
#[Fillable(['product_id', 'discount_price', 'start_date', 'end_date', 'is_active'])]
class ProductDiscount extends Model
{
    /**
     * Default attribute values (mirror the column defaults).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'discount_price' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isEffective(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        return ($this->start_date === null || $this->start_date->lte($now))
            && ($this->end_date === null || $this->end_date->gte($now));
    }
}
