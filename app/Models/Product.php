<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $sku
 * @property string|null $vendor
 * @property bool $is_visible
 * @property int|null $category_id
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property string $slug
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string $price
 * @property int $views_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'sku', 'vendor', 'is_visible', 'category_id', 'name', 'description', 'image', 'slug',
    'meta_title', 'meta_description', 'meta_keywords', 'price', 'views_count',
])]
class Product extends Model
{
    /**
     * Default attribute values (mirror the column defaults).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_visible' => true,
        'price' => 0,
        'views_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'price' => 'decimal:2',
            'views_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->slug)) {
                $product->slug = static::uniqueSlug($product->name, $product->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 2;

        while (static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(ProductDiscount::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    /** Stock row of the product itself (not of a selectable variant). */
    public function stock(): HasOne
    {
        return $this->hasOne(ProductStock::class)->whereNull('product_attribute_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * Currently effective promotional price, if any.
     */
    public function activeDiscount(): ?ProductDiscount
    {
        return $this->discounts
            ->first(fn (ProductDiscount $discount) => $discount->isEffective());
    }

    /**
     * Price the customer actually pays (discount applied when effective).
     */
    protected function finalPrice(): CastAttribute
    {
        return CastAttribute::get(function () {
            $discount = $this->activeDiscount();

            return $discount ? (float) $discount->discount_price : (float) $this->price;
        });
    }

    protected function availableQuantity(): CastAttribute
    {
        return CastAttribute::get(function () {
            $stock = $this->stock;

            return $stock ? max(0, $stock->quantity - $stock->reserved) : 0;
        });
    }

    protected function inStock(): CastAttribute
    {
        return CastAttribute::get(fn () => $this->available_quantity > 0);
    }
}
