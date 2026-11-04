<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Support\SlugRedirects;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Persists a product together with its images, attribute values, discount and stock.
 */
class ProductService
{
    public function __construct(
        private readonly ImageService $images,
        private readonly StockService $stock,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated request data
     * @param  array<int, UploadedFile>  $uploads  new gallery images
     */
    public function save(?Product $product, array $data, array $uploads = []): Product
    {
        return DB::transaction(function () use ($product, $data, $uploads) {
            $oldSlug = $product?->slug;

            $fields = [
                'name' => $data['name'],
                'sku' => ($data['sku'] ?? '') !== '' ? $data['sku'] : null,
                'vendor' => $data['vendor'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'price' => $data['price'],
                'description' => $data['description'] ?? null,
                'is_visible' => (bool) ($data['is_visible'] ?? false),
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
            ];

            $slug = ($data['slug'] ?? '') !== '' ? $data['slug'] : null;

            if ($product) {
                // An empty slug field keeps the current slug
                $product->fill([...$fields, 'slug' => $slug ?? $product->slug])->save();
            } else {
                $product = Product::create([...$fields, 'slug' => $slug]);
            }

            if ($oldSlug !== null && $oldSlug !== $product->slug) {
                SlugRedirects::record('product', $product, $oldSlug, $product->slug);
            }

            $this->syncImages($product, $data, $uploads);
            $this->syncDiscount($product, $data['discount'] ?? null);
            $this->syncAttributes($product, $data['attributes'] ?? []);

            if (array_key_exists('stock_quantity', $data) && $data['stock_quantity'] !== null) {
                $this->stock->set($product, (int) $data['stock_quantity'], null, 'Set from the product form');
            }

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        foreach ($product->images as $image) {
            $this->images->deleteProductImage($image->image_path);
        }

        $product->delete();
    }

    /**
     * Remove deleted images, append new uploads and keep products.image pointing at the main one.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $uploads
     */
    private function syncImages(Product $product, array $data, array $uploads): void
    {
        $removeIds = array_map('intval', $data['remove_image_ids'] ?? []);

        foreach ($product->images()->whereIn('id', $removeIds)->get() as $image) {
            $this->images->deleteProductImage($image->image_path);
            $image->delete();
        }

        foreach ($uploads as $file) {
            $product->images()->create([
                'image_path' => $this->images->storeProductImage($file),
                'sort_order' => 9999,
            ]);
        }

        $images = $product->images()->get();

        // The chosen main image goes first, the rest keep their relative order
        $mainId = (int) ($data['main_image_id'] ?? 0);
        $ordered = $images->sortBy(fn (ProductImage $image) => [$image->id === $mainId ? 0 : 1, $image->sort_order, $image->id])->values();

        foreach ($ordered as $position => $image) {
            if ($image->sort_order !== $position + 1) {
                $image->update(['sort_order' => $position + 1]);
            }
        }

        $product->update(['image' => $ordered->first()?->image_path]);
    }

    /**
     * @param  array<string, mixed>|null  $discount
     */
    private function syncDiscount(Product $product, ?array $discount): void
    {
        $price = $discount['price'] ?? null;

        if ($price === null || $price === '') {
            $product->discounts()->delete();

            return;
        }

        $product->discounts()->updateOrCreate(
            ['product_id' => $product->id],
            [
                'discount_price' => $price,
                'start_date' => $discount['start_date'] ?? null ?: null,
                'end_date' => $discount['end_date'] ?? null ?: null,
                'is_active' => (bool) ($discount['is_active'] ?? true),
            ],
        );
    }

    /**
     * Create, update and remove attribute values so they match the submitted rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncAttributes(Product $product, array $rows): void
    {
        $keep = [];

        foreach ($rows as $row) {
            $selectable = (bool) ($row['is_selectable'] ?? false);
            $optionId = ! empty($row['attribute_option_id']) ? (int) $row['attribute_option_id'] : null;
            $value = $row['value'] ?? null;

            if ($optionId === null && ($value === null || $value === '')) {
                continue;
            }

            $values = [
                'attribute_id' => $row['attribute_id'],
                'attribute_option_id' => $optionId,
                'value' => $value,
                'is_selectable' => $selectable,
                'price_operation' => $selectable ? ($row['price_operation'] ?? '+') : '+',
                'price_modifier' => $selectable ? ($row['price_modifier'] ?? 0) : 0,
                'stock_quantity' => $selectable ? (int) ($row['stock_quantity'] ?? 0) : null,
                'sku' => $selectable && $product->sku ? $product->sku.'-'.$this->skuSuffix($row) : null,
            ];

            $attribute = ! empty($row['id']) ? $product->attributeValues()->find($row['id']) : null;

            if ($attribute) {
                $attribute->update($values);
            } else {
                $attribute = $product->attributeValues()->create($values);
            }

            $keep[] = $attribute->id;

            if ($selectable) {
                $this->stock->set($product, (int) $values['stock_quantity'], $attribute->id, 'Set from the product form');
            }
        }

        $product->attributeValues()->whereNotIn('id', $keep)->each(fn (ProductAttribute $attribute) => $attribute->delete());
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function skuSuffix(array $row): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($row['label'] ?? $row['value'] ?? $row['attribute_option_id'])) ?: 'X');
    }
}
