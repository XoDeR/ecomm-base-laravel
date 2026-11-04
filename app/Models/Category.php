<?php

namespace App\Models;

use App\Support\SlugRedirects;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property string $slug
 * @property string|null $path
 * @property string|null $description
 * @property int $sort_order
 * @property string|null $image
 * @property bool $is_active
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'parent_id', 'slug', 'description', 'sort_order', 'image', 'is_active',
    'meta_title', 'meta_description', 'meta_keywords',
])]
class Category extends Model
{
    /**
     * Default attribute values (mirror the column defaults).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $parentPath = $category->parent_id ? (static::find($category->parent_id)?->path ?? '') : '';
            $category->path = $parentPath.'/'.$category->slug;
        });

        static::saved(function (Category $category) {
            if ($category->wasChanged('path') && ($previous = $category->getPrevious()['path'] ?? null)) {
                SlugRedirects::record('category', $category, $previous, (string) $category->path);
            }

            if ($category->wasChanged(['slug', 'parent_id'])) {
                // Re-save children so their materialized paths follow the parent.
                $category->children->each->save();
            }
        });

        static::deleting(function (Category $category) {
            // Children move to the top level; saving recomputes their paths.
            $category->children->each->update(['parent_id' => null]);
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'category_attributes')
            ->withPivot(['is_required', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function filters(): HasMany
    {
        return $this->hasMany(CategoryFilter::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * The category id plus the ids of all of its descendants.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        return static::query()
            ->where('path', 'like', $this->path.'/%')
            ->pluck('id')
            ->prepend($this->id)
            ->all();
    }

    /**
     * Ancestors from the root down to (and including) this category.
     *
     * @return Collection<int, Category>
     */
    public function breadcrumbs(): Collection
    {
        $slugs = array_filter(explode('/', (string) $this->path));
        $byslug = static::query()->whereIn('slug', $slugs)->get()->keyBy('slug');

        return collect($slugs)->map(fn ($slug) => $byslug->get($slug))->filter()->values();
    }

    /**
     * Nested category tree (arrays with `children`), active categories only.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tree(): array
    {
        $categories = static::active()->orderBy('sort_order')->orderBy('name')->get();

        $build = function (?int $parentId) use (&$build, $categories): array {
            return $categories->where('parent_id', $parentId)->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url' => $category->url,
                'children' => $build($category->id),
            ])->values()->all();
        };

        return $build(null);
    }

    public function getUrlAttribute(): string
    {
        return '/category'.$this->path;
    }
}
