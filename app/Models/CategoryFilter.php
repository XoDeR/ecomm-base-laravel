<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $category_id
 * @property int $attribute_id
 * @property string $filter_type checkbox|range|color
 * @property string|null $min_value
 * @property string|null $max_value
 * @property bool $is_active
 * @property int $sort_order
 */
#[Fillable(['category_id', 'attribute_id', 'filter_type', 'min_value', 'max_value', 'is_active', 'sort_order'])]
class CategoryFilter extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
