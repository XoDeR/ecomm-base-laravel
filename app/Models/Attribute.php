<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product attribute definition (color, size, brand, ...).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type text|select|multiselect|color|range
 * @property string|null $description
 * @property bool $is_filterable
 * @property bool $is_visible
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'type', 'description', 'is_filterable', 'is_visible', 'sort_order'])]
class Attribute extends Model
{
    /**
     * Default attribute values (mirror the column defaults).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'text',
        'is_filterable' => true,
        'is_visible' => true,
        'sort_order' => 0,
    ];

    public const TYPES = ['text', 'select', 'multiselect', 'color', 'range'];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('sort_order')->orderBy('name');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_attributes')
            ->withPivot(['is_required', 'sort_order']);
    }

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['select', 'multiselect', 'color'], true);
    }
}
