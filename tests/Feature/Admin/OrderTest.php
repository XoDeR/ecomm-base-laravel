<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An open order for 3 units of a product that has 10 on hand (3 reserved).
     *
     * @return array{0: Order, 1: Product}
     */
    private function openOrder(): array
    {
        $product = Product::create(['name' => 'Widget', 'price' => 10, 'sku' => 'W-1']);
        ProductStock::create(['product_id' => $product->id, 'sku' => 'W-1', 'quantity' => 10, 'reserved' => 3]);

        $order = Order::create([
            'total' => 30, 'customer_name' => 'Jane', 'customer_phone' => '1234567',
            'customer_email' => 'jane@example.com', 'status' => OrderStatus::New,
        ]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => 'Widget', 'sku' => 'W-1', 'qty' => 3, 'price' => 10]);

        return [$order, $product];
    }

    private function stock(Product $product): ProductStock
    {
        return ProductStock::where('product_id', $product->id)->whereNull('product_attribute_id')->first();
    }

    public function test_index_shows_a_board_and_a_filterable_table(): void
    {
        [$order] = $this->openOrder();
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/orders/index')
                ->where('board.0.status', 'new')
                ->where('board.0.total', 1));

        $this->actingAs($admin)->get('/admin/orders?view=table&status=completed')
            ->assertInertia(fn (Assert $page) => $page->where('table.total', 0));

        $this->actingAs($admin)->get('/admin/orders?view=table&search='.$order->id)
            ->assertInertia(fn (Assert $page) => $page->where('table.total', 1));
    }

    public function test_completing_an_order_removes_goods_from_stock(): void
    {
        [$order, $product] = $this->openOrder();

        $this->actingAs($this->admin())->post("/admin/orders/{$order->id}/status", ['status' => 'completed']);

        $stock = $this->stock($product);
        $this->assertSame(7, $stock->quantity);
        $this->assertSame(0, $stock->reserved);
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertSame(1, $order->statusHistory()->count());
    }

    public function test_cancelling_releases_the_reservation(): void
    {
        [$order, $product] = $this->openOrder();

        $this->actingAs($this->admin())->post("/admin/orders/{$order->id}/status", ['status' => 'cancelled']);

        $stock = $this->stock($product);
        $this->assertSame(10, $stock->quantity);
        $this->assertSame(0, $stock->reserved);
    }

    public function test_returning_a_completed_order_restocks_the_goods(): void
    {
        [$order, $product] = $this->openOrder();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", ['status' => 'completed']);
        $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", ['status' => 'returned']);

        $this->assertSame(10, $this->stock($product)->quantity);
    }

    public function test_shipping_requires_a_tracking_number(): void
    {
        [$order] = $this->openOrder();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", ['status' => 'shipped']);
        $this->assertSame(OrderStatus::New, $order->fresh()->status);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", ['status' => 'shipped', 'tracking_number' => 'TRK123']);
        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame('TRK123', $order->tracking_number);
    }

    public function test_cancelled_orders_cannot_be_reopened(): void
    {
        [$order, $product] = $this->openOrder();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", ['status' => 'cancelled']);
        $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", ['status' => 'processing']);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_invalid_status_is_rejected(): void
    {
        [$order] = $this->openOrder();

        $this->actingAs($this->admin())->post("/admin/orders/{$order->id}/status", ['status' => 'nonsense'])
            ->assertSessionHasErrors('status');
    }

    public function test_deleting_an_open_order_releases_its_reservation(): void
    {
        [$order, $product] = $this->openOrder();

        $this->actingAs($this->admin())->delete("/admin/orders/{$order->id}")->assertRedirect();

        $this->assertModelMissing($order);
        $this->assertSame(0, $this->stock($product)->reserved);
    }

    public function test_order_details_can_be_edited_and_the_page_renders(): void
    {
        [$order] = $this->openOrder();
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/orders/{$order->id}", [
            'customer_name' => 'Janet', 'customer_phone' => '7654321', 'customer_email' => 'janet@example.com',
        ])->assertRedirect();

        $this->assertSame('Janet', $order->fresh()->customer_name);

        $this->actingAs($admin)->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/orders/show')->has('order.items', 1));
    }

    public function test_stock_service_never_goes_negative(): void
    {
        [, $product] = $this->openOrder();

        $this->expectException(\RuntimeException::class);

        app(StockService::class)->adjust($product, -100);
    }
}
