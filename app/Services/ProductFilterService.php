<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Category product filtering by price and attributes.
 *
 * Query string contract:
 *   min_price, max_price      price bounds
 *   attr[<id>][]              selected option ids (or plain values) of an attribute
 *   range[<id>][min|max]      numeric bounds of a range attribute
 *   sort                      name_asc | price_asc | price_desc | newest
 */
class ProductFilterService
{
    public const SORTS = [
        'name_asc' => 'Name: A to Z',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'newest' => 'Newest first',
    ];

    /**
     * @return array{min_price: float|null, max_price: float|null, attr: array<int, array<int, string>>, range: array<int, array{min: float|null, max: float|null}>, sort: string}
     */
    public function parse(Request $request): array
    {
        $attr = [];
        foreach ((array) $request->query('attr', []) as $attributeId => $values) {
            $values = array_values(array_filter((array) $values, fn ($v) => $v !== '' && $v !== null && ! is_array($v)));
            if ((int) $attributeId > 0 && $values !== []) {
                $attr[(int) $attributeId] = array_map('strval', $values);
            }
        }

        $range = [];
        foreach ((array) $request->query('range', []) as $attributeId => $bounds) {
            $bounds = (array) $bounds;
            $min = is_numeric($bounds['min'] ?? null) ? (float) $bounds['min'] : null;
            $max = is_numeric($bounds['max'] ?? null) ? (float) $bounds['max'] : null;
            if ((int) $attributeId > 0 && ($min !== null || $max !== null)) {
                $range[(int) $attributeId] = ['min' => $min, 'max' => $max];
            }
        }

        $sort = (string) $request->query('sort', 'name_asc');

        return [
            'min_price' => is_numeric($request->query('min_price')) ? (float) $request->query('min_price') : null,
            'max_price' => is_numeric($request->query('max_price')) ? (float) $request->query('max_price') : null,
            'attr' => $attr,
            'range' => $range,
            'sort' => array_key_exists($sort, self::SORTS) ? $sort : 'name_asc',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function query(?Category $category, array $filters): Builder
    {
        $query = Product::query()->visible();

        if ($category) {
            $query->whereIn('category_id', $category->descendantIds());
        }

        if ($filters['min_price'] !== null) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if ($filters['max_price'] !== null) {
            $query->where('price', '<=', $filters['max_price']);
        }

        foreach ($filters['attr'] as $attributeId => $values) {
            $optionIds = array_filter($values, 'ctype_digit');
            $plain = array_diff($values, $optionIds);

            $query->whereHas('attributeValues', function (Builder $q) use ($attributeId, $optionIds, $plain) {
                $q->where('attribute_id', $attributeId)->where(function (Builder $match) use ($optionIds, $plain) {
                    if ($optionIds) {
                        $match->orWhereIn('attribute_option_id', $optionIds);
                    }
                    if ($plain) {
                        $match->orWhereIn('value', $plain);
                    }
                });
            });
        }

        foreach ($filters['range'] as $attributeId => $bounds) {
            $query->whereHas('attributeValues', function (Builder $q) use ($attributeId, $bounds) {
                $q->where('attribute_id', $attributeId);
                $numeric = 'CAST(REPLACE(value, \',\', \'.\') AS DECIMAL(12,2))';

                if ($bounds['min'] !== null) {
                    $q->whereRaw("{$numeric} >= ?", [$bounds['min']]);
                }
                if ($bounds['max'] !== null) {
                    $q->whereRaw("{$numeric} <= ?", [$bounds['max']]);
                }
            });
        }

        match ($filters['sort']) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest' => $query->orderByDesc('id'),
            default => $query->orderBy('name'),
        };

        return $query;
    }

    /**
     * Filterable attributes of the category with the selectable values and product counts.
     *
     * @param  array<string, mixed>  $filters  currently applied filters (counts honor them)
     * @return array<int, array<string, mixed>>
     */
    public function options(Category $category, array $filters): array
    {
        $categoryIds = $category->descendantIds();

        return $category->attributes()
            ->where('attributes.is_filterable', true)
            ->get()
            ->map(function (Attribute $attribute) use ($category, $categoryIds, $filters) {
                $base = [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'slug' => $attribute->slug,
                    'type' => $attribute->type,
                    'options' => [],
                    'range' => null,
                ];

                $values = ProductAttribute::query()
                    ->with('option')
                    ->where('attribute_id', $attribute->id)
                    ->whereHas('product', fn (Builder $q) => $q->visible()->whereIn('category_id', $categoryIds));

                if ($attribute->type === 'range') {
                    $numbers = $values->pluck('value')
                        ->map(fn ($v) => str_replace(',', '.', (string) $v))
                        ->filter(fn ($v) => is_numeric($v))
                        ->map(fn ($v) => (float) $v);

                    $base['range'] = $numbers->isEmpty() ? null : ['min' => $numbers->min(), 'max' => $numbers->max()];

                    return $base;
                }

                $base['options'] = $values->get()
                    ->groupBy(fn (ProductAttribute $pa) => $pa->attribute_option_id ?? 'v:'.$pa->value)
                    ->map(function ($group, $key) use ($attribute, $category, $filters) {
                        /** @var ProductAttribute $first */
                        $first = $group->first();
                        $value = $first->attribute_option_id ? (string) $first->attribute_option_id : (string) $first->value;

                        $countFilters = $filters;
                        $countFilters['attr'][$attribute->id] = [$value];

                        return [
                            'value' => $value,
                            'label' => $first->displayValue(),
                            'color' => $first->option?->color_code,
                            'count' => $this->query($category, $countFilters)->count(),
                        ];
                    })
                    ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();

                return $base;
            })
            ->filter(fn (array $attribute) => $attribute['options'] !== [] || $attribute['range'] !== null)
            ->values()
            ->all();
    }

    /**
     * @return array{min: float, max: float}
     */
    public function priceRange(?Category $category): array
    {
        $query = Product::query()->visible();

        if ($category) {
            $query->whereIn('category_id', $category->descendantIds());
        }

        return [
            'min' => (float) ($query->min('price') ?? 0),
            'max' => (float) ($query->max('price') ?? 0),
        ];
    }
}
