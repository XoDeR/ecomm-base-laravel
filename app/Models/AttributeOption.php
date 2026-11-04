<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $attribute_id
 * @property string $name
 * @property string $value
 * @property string|null $color_code
 * @property int $sort_order
 */
#[Fillable(['attribute_id', 'name', 'value', 'color_code', 'sort_order'])]
class AttributeOption extends Model
{
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
