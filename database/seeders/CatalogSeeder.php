<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductDiscount;
use App\Models\ProductImage;
use App\Models\ProductStock;
use Illuminate\Database\Seeder;

/**
 * Demo categories, attributes and products.
 */
class CatalogSeeder extends Seeder
{
    private const GALLERY = '/uploads/products/gallery/original/';

    public function run(): void
    {
        $categories = $this->categories();
        $attributes = $this->attributes();

        // Attributes available for products of a category
        foreach (['televisions' => ['brand', 'material'], 'smartphones' => ['color', 'brand']] as $slug => $attributeSlugs) {
            foreach ($attributeSlugs as $i => $attributeSlug) {
                $categories[$slug]->attributes()->syncWithoutDetaching([
                    $attributes[$attributeSlug]->id => ['is_required' => false, 'sort_order' => $i],
                ]);
            }
        }

        $this->products($categories, $attributes);
    }

    /**
     * @return array<string, Category>
     */
    private function categories(): array
    {
        $created = [];

        $definitions = [
            ['key' => 'smartphones', 'name' => 'Smartphones', 'slug' => 'smartphones', 'parent' => null, 'image' => '/uploads/categories/category_106c7944bb3e5632.jpg', 'description' => 'The latest smartphones from the best brands.', 'meta_title' => 'Buy Smartphones', 'meta_description' => 'A wide selection of smartphones!'],
            ['key' => 'flagship', 'name' => 'Flagship phones', 'slug' => 'flagship', 'parent' => 'smartphones', 'description' => 'Premium flagship smartphones.'],
            ['key' => 'refurbished', 'name' => 'Refurbished smartphones', 'slug' => 'refurbished-smartphones', 'parent' => 'smartphones', 'description' => 'Tested and certified used smartphones.'],
            ['key' => 'televisions', 'name' => 'Televisions', 'slug' => 'televisions', 'parent' => null, 'image' => '/uploads/categories/category_088f70fc1d096947.png', 'description' => 'The most modern televisions.', 'meta_title' => 'Televisions', 'meta_description' => 'Smart TVs and displays for every room.'],
            ['key' => 'headphones', 'name' => 'Headphones', 'slug' => 'headphones', 'parent' => null, 'image' => '/uploads/categories/category_11e098608661ab5a.jpg', 'description' => 'Wired and wireless headphones.'],
            ['key' => 'headphone-accessories', 'name' => 'Headphone accessories', 'slug' => 'headphone-accessories', 'parent' => 'headphones', 'description' => 'Cases, cables and ear tips.'],
        ];

        foreach ($definitions as $i => $definition) {
            $created[$definition['key']] = Category::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'parent_id' => $definition['parent'] ? $created[$definition['parent']]->id : null,
                    'description' => $definition['description'] ?? null,
                    'image' => $definition['image'] ?? null,
                    'meta_title' => $definition['meta_title'] ?? null,
                    'meta_description' => $definition['meta_description'] ?? null,
                    'sort_order' => $i,
                    'is_active' => true,
                ],
            );
        }

        // Keyed by slug as well, for convenience
        foreach ($created as $category) {
            $created[$category->slug] = $category;
        }

        return $created;
    }

    /**
     * @return array<string, Attribute>
     */
    private function attributes(): array
    {
        $definitions = [
            ['slug' => 'color', 'name' => 'Color', 'type' => 'select', 'description' => 'Product color', 'filterable' => true, 'options' => ['Black', 'White', 'Red', 'Blue']],
            ['slug' => 'size', 'name' => 'Size', 'type' => 'select', 'description' => 'Product size', 'filterable' => true, 'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']],
            ['slug' => 'material', 'name' => 'Material', 'type' => 'select', 'description' => 'Product material', 'filterable' => true, 'options' => ['Plastic', 'Metal', 'Glass']],
            ['slug' => 'brand', 'name' => 'Brand', 'type' => 'select', 'description' => 'Product manufacturer', 'filterable' => true, 'options' => ['Nova', 'Zenith', 'Orbit', 'Lumen']],
            ['slug' => 'warranty', 'name' => 'Warranty', 'type' => 'text', 'description' => 'Warranty period', 'filterable' => false, 'options' => []],
        ];

        $created = [];

        foreach ($definitions as $i => $definition) {
            $attribute = Attribute::updateOrCreate(['slug' => $definition['slug']], [
                'name' => $definition['name'],
                'type' => $definition['type'],
                'description' => $definition['description'],
                'is_filterable' => $definition['filterable'],
                'is_visible' => true,
                'sort_order' => $i + 1,
            ]);

            foreach ($definition['options'] as $j => $option) {
                AttributeOption::updateOrCreate(
                    ['attribute_id' => $attribute->id, 'value' => $option],
                    ['name' => $option, 'sort_order' => $j + 1],
                );
            }

            $created[$definition['slug']] = $attribute;
        }

        return $created;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, Attribute>  $attributes
     */
    private function products(array $categories, array $attributes): void
    {
        $products = [
            ['sku' => 'TV-55-ANDROID', 'name' => 'Smart TV 55" Android 15 120 Hz FullHD', 'category' => 'televisions', 'vendor' => 'Orbit', 'price' => 429.00, 'stock' => 4, 'reserved' => 1, 'images' => ['product_6a8e9f6d243fc044107860.webp', 'product_6a8e9f7fee947587998458.webp'], 'description' => 'The 55" Smart TV (Android 15) combines cutting-edge technology with a stylish design. It is ideal for home cinema, gaming and everyday use.', 'discount' => 379.00],
            ['sku' => 'ZENITH-TV-43', 'name' => 'Zenith Smart TV 43"', 'category' => 'televisions', 'vendor' => 'Zenith', 'price' => 349.00, 'stock' => 10, 'reserved' => 2, 'images' => ['product_69e608b6ddae3707094810.webp'], 'description' => 'A 43-inch 4K Smart TV with vivid colors, voice control and built-in streaming apps.', 'variants' => ['brand' => [['Nova', 15.00, 5], ['Zenith', 0, 8]]]],
            ['sku' => 'VERTEX-24H500', 'name' => 'Television Vertex 24H500', 'category' => 'televisions', 'vendor' => 'Vertex', 'price' => 124.00, 'stock' => 25, 'reserved' => 1, 'images' => ['product_6a48eefff07ba546360331.jpg'], 'description' => 'An ideal choice for small rooms, kitchens or as a second TV in the bedroom. It features a stylish, slim 24-inch design with a black bezel that complements any interior.'],
            ['sku' => 'LUMEN-32H3000', 'name' => 'Television Lumen 32H3000', 'category' => 'televisions', 'vendor' => 'Lumen', 'price' => 149.00, 'stock' => 40, 'reserved' => 0, 'images' => ['product_6a48eef337307640388684.jpg'], 'description' => 'The HD format delivers excellent image detail and clarity. Experience your favorite TV shows and movies in a whole new way.'],
            ['sku' => 'VERTEX-24H300-T2', 'name' => 'Television Vertex 24H300-T2', 'category' => 'televisions', 'vendor' => 'Vertex', 'price' => 114.00, 'stock' => 18, 'reserved' => 4, 'images' => ['product_6a482b3f04178722104981.jpg'], 'description' => 'A 24-inch screen with a resolution of 1366x768 (HD Ready), providing good image quality for small rooms or kitchens.'],
            ['sku' => 'ORBIT-PHONE-15-128-BLK', 'name' => 'Orbit Phone 15 128GB Black', 'category' => 'flagship', 'vendor' => 'Orbit', 'price' => 769.00, 'stock' => 25, 'reserved' => 0, 'images' => ['product_6a48edeb1719a274349597.jpg'], 'description' => 'Orbit Phone 15 is a smartphone that embodies innovation in every aspect, from design to performance. It will become your reliable partner every day.'],
            ['sku' => 'LINKR-LP152', 'name' => 'Headphone case Linkr LP152 Travel Storage Case Gray', 'category' => 'headphone-accessories', 'vendor' => 'Linkr', 'price' => 14.49, 'stock' => 25, 'reserved' => 3, 'images' => ['product_6a4cbe67e5901786626367.webp'], 'description' => 'Accessory organizer case Linkr LP152 Travel Storage Case. Storing and transporting electronic accessories has never been so simple and secure.'],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(['sku' => $data['sku']], [
                'name' => $data['name'],
                'category_id' => $categories[$data['category']]->id,
                'vendor' => $data['vendor'],
                'price' => $data['price'],
                'description' => $data['description'],
                'image' => self::GALLERY.$data['images'][0],
                'is_visible' => true,
                'meta_title' => $data['name'],
            ]);

            $product->images()->delete();
            foreach ($data['images'] as $i => $file) {
                ProductImage::create(['product_id' => $product->id, 'image_path' => self::GALLERY.$file, 'sort_order' => $i + 1]);
            }

            ProductStock::updateOrCreate(
                ['product_id' => $product->id, 'product_attribute_id' => null],
                ['sku' => $data['sku'], 'quantity' => $data['stock'], 'reserved' => $data['reserved']],
            );

            if (isset($data['discount'])) {
                ProductDiscount::updateOrCreate(['product_id' => $product->id], ['discount_price' => $data['discount'], 'is_active' => true]);
            }

            foreach ($data['variants'] ?? [] as $attributeSlug => $options) {
                foreach ($options as [$optionName, $delta, $quantity]) {
                    $option = AttributeOption::where('attribute_id', $attributes[$attributeSlug]->id)->where('value', $optionName)->first();

                    $variant = ProductAttribute::updateOrCreate(
                        ['product_id' => $product->id, 'attribute_id' => $attributes[$attributeSlug]->id, 'attribute_option_id' => $option?->id],
                        [
                            'sku' => $data['sku'].'-'.strtoupper($optionName),
                            'value' => $optionName,
                            'price_modifier' => $delta,
                            'price_operation' => '+',
                            'stock_quantity' => $quantity,
                            'is_selectable' => true,
                        ],
                    );

                    ProductStock::updateOrCreate(
                        ['product_id' => $product->id, 'product_attribute_id' => $variant->id],
                        ['sku' => $variant->sku, 'quantity' => $quantity, 'reserved' => 0],
                    );
                }
            }
        }
    }
}
