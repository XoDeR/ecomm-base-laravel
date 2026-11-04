<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductStock;
use App\Models\StockDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $quantity = 10, int $reserved = 2): Product
    {
        $product = Product::create(['name' => 'Widget', 'price' => 5, 'sku' => 'W-1']);
        ProductStock::create(['product_id' => $product->id, 'sku' => 'W-1', 'quantity' => $quantity, 'reserved' => $reserved]);

        return $product;
    }

    private function onHand(Product $product): int
    {
        return ProductStock::where('product_id', $product->id)->whereNull('product_attribute_id')->value('quantity');
    }

    public function test_receipt_write_off_and_stock_taking(): void
    {
        $product = $this->product();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/stocks/adjust', ['product_id' => $product->id, 'action' => 'receipt', 'quantity' => 5]);
        $this->assertSame(15, $this->onHand($product));

        $this->actingAs($admin)->post('/admin/stocks/adjust', ['product_id' => $product->id, 'action' => 'write_off', 'quantity' => 3, 'comment' => 'Damaged']);
        $this->assertSame(12, $this->onHand($product));

        $this->actingAs($admin)->post('/admin/stocks/adjust', ['product_id' => $product->id, 'action' => 'set', 'quantity' => 40]);
        $this->assertSame(40, $this->onHand($product));

        $this->assertSame(3, StockDocument::count());
        $this->assertSame(['add', 'remove', 'set'], InventoryLog::orderBy('id')->pluck('event_type')->all());
        $this->assertSame('Damaged', InventoryLog::where('event_type', 'remove')->value('comment'));
    }

    public function test_cannot_write_off_more_than_on_hand(): void
    {
        $product = $this->product(quantity: 4);

        $this->actingAs($this->admin())->post('/admin/stocks/adjust', ['product_id' => $product->id, 'action' => 'write_off', 'quantity' => 9])
            ->assertRedirect();

        $this->assertSame(4, $this->onHand($product));
        $this->assertSame(0, StockDocument::count());
    }

    public function test_variant_stock_is_adjusted_and_the_denormalized_quantity_follows(): void
    {
        $product = $this->product();
        $attribute = Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'select']);
        $variant = ProductAttribute::create(['product_id' => $product->id, 'attribute_id' => $attribute->id, 'value' => 'Red', 'is_selectable' => true, 'stock_quantity' => 0]);

        $this->actingAs($this->admin())->post('/admin/stocks/adjust', [
            'product_id' => $product->id, 'variant_id' => $variant->id, 'action' => 'receipt', 'quantity' => 6,
        ]);

        $this->assertSame(6, ProductStock::where('product_attribute_id', $variant->id)->value('quantity'));
        $this->assertSame(6, $variant->fresh()->stock_quantity);
        $this->assertSame(10, $this->onHand($product), 'product-level stock is untouched');
    }

    public function test_validation(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin())->post('/admin/stocks/adjust', ['product_id' => $product->id, 'action' => 'nonsense', 'quantity' => -1])
            ->assertSessionHasErrors(['action', 'quantity']);
    }

    public function test_index_lists_products_with_availability_and_low_stock_filter(): void
    {
        $this->product(quantity: 10, reserved: 2);
        $low = Product::create(['name' => 'Scarce', 'price' => 1]);
        ProductStock::create(['product_id' => $low->id, 'quantity' => 2, 'reserved' => 0]);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/stocks')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/stocks')->where('products.total', 2));

        $this->actingAs($admin)->get('/admin/stocks?low=1')
            ->assertInertia(fn (Assert $page) => $page->where('products.total', 1)->where('products.data.0.name', 'Scarce'));
    }
}
