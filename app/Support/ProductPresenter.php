<?php

namespace App\Support;

use App\Models\Product;

/**
 * Converts products to the plain arrays the React pages consume.
 *
 * Products must be loaded with the `discounts` and `stock` relations to avoid N+1 queries.
 */
class ProductPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(Product $product, array $compareIds = []): array
    {
        $discount = $product->activeDiscount();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'vendor' => $product->vendor,
            'image' => $product->image,
            'price' => (float) $product->price,
            'final_price' => $product->final_price,
            'has_discount' => $discount !== null,
            'in_stock' => $product->in_stock,
            'in_compare' => in_array($product->id, $compareIds, true),
        ];
    }

    /**
     * @param  iterable<Product>  $products
     * @return array<int, array<string, mixed>>
     */
    public static function cards(iterable $products, array $compareIds = []): array
    {
        $cards = [];

        foreach ($products as $product) {
            $cards[] = static::card($product, $compareIds);
        }

        return $cards;
    }
}
