<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\UrlRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Test Phone',
            'sku' => 'TP-1',
            'price' => 100,
            'is_visible' => true,
            'stock_quantity' => 7,
            ...$overrides,
        ];
    }

    /**
     * Remove generated files for an image path (all three sizes).
     */
    private function cleanUpImage(string $path): void
    {
        foreach (['original', 'medium', 'thumb'] as $size) {
            @unlink(public_path(ltrim(str_replace('/original/', "/{$size}/", $path), '/')));
        }
    }

    public function test_admin_can_create_a_product_with_stock_and_a_generated_slug(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', $this->payload())->assertRedirect();

        $product = Product::first();

        $this->assertSame('test-phone', $product->slug);
        $this->assertSame(7, $product->stock->quantity);
        $this->assertSame(7, $product->available_quantity);
    }

    public function test_discount_price_must_be_lower_than_the_price(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/products', $this->payload(['discount' => ['price' => 150]]))
            ->assertSessionHasErrors('discount.price');

        $this->actingAs($admin)->post('/admin/products', $this->payload(['discount' => ['price' => 80, 'is_active' => true]]));

        $this->assertEquals(80, Product::first()->final_price);
    }

    public function test_clearing_the_discount_price_removes_the_discount(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/products', $this->payload(['discount' => ['price' => 80]]));
        $product = Product::first();

        $this->actingAs($admin)->put("/admin/products/{$product->id}", $this->payload(['discount' => ['price' => '']]));

        $this->assertSame(0, $product->discounts()->count());
    }

    public function test_attribute_values_and_selectable_variants_are_saved_with_stock(): void
    {
        $color = Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'select']);
        $red = $color->options()->create(['name' => 'Red', 'value' => 'Red']);

        $this->actingAs($this->admin())->post('/admin/products', $this->payload([
            'attributes' => [[
                'attribute_id' => $color->id,
                'attribute_option_id' => $red->id,
                'value' => 'Red',
                'is_selectable' => true,
                'price_operation' => '+',
                'price_modifier' => 5,
                'stock_quantity' => 3,
            ]],
        ]));

        $product = Product::first();
        $variant = $product->attributeValues->first();

        $this->assertTrue($variant->is_selectable);
        $this->assertSame('TP-1-RED', $variant->sku);
        $this->assertSame(3, $product->stocks()->where('product_attribute_id', $variant->id)->value('quantity'));
    }

    public function test_images_are_stored_in_three_sizes_and_the_main_image_is_first(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/products', $this->payload([
            'images' => [UploadedFile::fake()->image('a.jpg', 900, 900), UploadedFile::fake()->image('b.png', 300, 300)],
        ]));

        $product = Product::first();
        $images = $product->images;

        try {
            $this->assertCount(2, $images);
            $this->assertSame($images[0]->image_path, $product->image);

            foreach ($images as $image) {
                $this->assertFileExists(public_path(ltrim($image->image_path, '/')));
                $this->assertFileExists(public_path(ltrim(str_replace('/original/', '/medium/', $image->image_path), '/')));
                $this->assertFileExists(public_path(ltrim(str_replace('/original/', '/thumb/', $image->image_path), '/')));
            }

            // Make the second image the main one
            $this->actingAs($admin)->put("/admin/products/{$product->id}", $this->payload(['main_image_id' => $images[1]->id]));
            $this->assertSame($images[1]->image_path, $product->fresh()->image);

            // Remove the first image
            $this->actingAs($admin)->put("/admin/products/{$product->id}", $this->payload(['remove_image_ids' => [$images[0]->id]]));
            $this->assertSame(1, ProductImage::where('product_id', $product->id)->count());
            $this->assertFileDoesNotExist(public_path(ltrim($images[0]->image_path, '/')));
        } finally {
            foreach ($images as $image) {
                $this->cleanUpImage($image->image_path);
            }
        }
    }

    public function test_changing_the_slug_creates_a_redirect(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/products', $this->payload());
        $product = Product::first();

        $this->actingAs($admin)->put("/admin/products/{$product->id}", $this->payload(['slug' => 'new-phone']));

        $this->assertSame('new-phone', UrlRedirect::where('old_slug', 'test-phone')->value('new_slug'));
        $this->get('/product/test-phone')->assertRedirect('/product/new-phone')->assertStatus(301);
    }

    public function test_index_filters_by_search_and_visibility(): void
    {
        $admin = $this->admin();
        Product::create(['name' => 'Alpha', 'price' => 1, 'is_visible' => true]);
        Product::create(['name' => 'Beta', 'price' => 1, 'is_visible' => false]);

        $this->actingAs($admin)->get('/admin/products?q=alp')
            ->assertInertia(fn (Assert $page) => $page->where('products.total', 1));

        $this->actingAs($admin)->get('/admin/products?visibility=hidden')
            ->assertInertia(fn (Assert $page) => $page->where('products.total', 1)->where('products.data.0.name', 'Beta'));
    }

    public function test_category_filter_includes_subcategories(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);
        Product::create(['name' => 'In child', 'price' => 1, 'category_id' => $child->id]);
        Product::create(['name' => 'Elsewhere', 'price' => 1]);

        $this->actingAs($this->admin())->get("/admin/products?category_id={$parent->id}")
            ->assertInertia(fn (Assert $page) => $page->where('products.total', 1));
    }

    public function test_product_can_be_deleted(): void
    {
        $product = Product::create(['name' => 'Gone', 'price' => 1]);

        $this->actingAs($this->admin())->delete("/admin/products/{$product->id}")->assertRedirect('/admin/products');

        $this->assertModelMissing($product);
    }

    public function test_edit_page_renders(): void
    {
        $product = Product::create(['name' => 'Editable', 'price' => 10]);

        $this->actingAs($this->admin())->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/products/form')->where('product.name', 'Editable'));
    }
}
