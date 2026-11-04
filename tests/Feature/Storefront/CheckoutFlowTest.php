<?php

namespace Tests\Feature\Storefront;

use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductStock;
use App\Models\PromoCode;
use App\Models\ShopMethod;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StoreSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([StoreSettingsSeeder::class, CatalogSeeder::class]);
    }

    private function orderPayload(array $overrides = []): array
    {
        return [
            'full_name' => 'Jane Customer',
            'phone' => '+1 (555) 123-4567',
            'email' => 'jane@example.com',
            'delivery_id' => ShopMethod::where('code', 'self_pickup')->value('id'),
            'payment_id' => ShopMethod::where('code', 'cash')->value('id'),
            ...$overrides,
        ];
    }

    public function test_guest_can_add_to_cart_and_place_an_order(): void
    {
        $product = Product::where('sku', 'ORBIT-PHONE-15-128-BLK')->first();

        $this->post("/cart/add/{$product->id}", ['quantity' => 2])->assertRedirect();
        $this->assertSame(2, CartItem::sum('quantity'));

        $this->get('/checkout')->assertOk();

        $response = $this->post('/place-order', $this->orderPayload());

        $order = Order::first();
        $response->assertRedirect("/order-success/{$order->id}");

        $this->assertEquals(1538.00, (float) $order->total);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertCount(1, $order->items);
        $this->assertSame('Orbit Phone 15 128GB Black', $order->items->first()->product_name);
        $this->assertSame(0, CartItem::count());

        // Stock is reserved, not yet deducted
        $stock = ProductStock::where('product_id', $product->id)->whereNull('product_attribute_id')->first();
        $this->assertSame(25, $stock->quantity);
        $this->assertSame(2, $stock->reserved);

        $this->get("/order-success/{$order->id}")->assertOk();
    }

    public function test_order_success_page_is_private_to_the_buyer(): void
    {
        $order = Order::create([
            'total' => 10, 'customer_name' => 'Someone', 'customer_phone' => '1234567',
            'customer_email' => 'someone@example.com', 'status' => OrderStatus::New,
        ]);

        $this->get("/order-success/{$order->id}")->assertNotFound();
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = Product::where('sku', 'TV-55-ANDROID')->first(); // 4 on hand, 1 reserved => 3 available

        $this->post("/cart/add/{$product->id}", ['quantity' => 4]);

        $this->assertSame(0, CartItem::count());
    }

    public function test_selectable_options_are_required_and_change_the_price(): void
    {
        $product = Product::where('sku', 'ZENITH-TV-43')->first();
        $variant = ProductAttribute::where('product_id', $product->id)->where('value', 'Nova')->first();

        $this->post("/cart/add/{$product->id}", ['quantity' => 1]);
        $this->assertSame(0, CartItem::count(), 'an option must be chosen');

        $this->post("/cart/add/{$product->id}", ['quantity' => 1, 'selected_option_ids' => [$variant->id]]);

        $item = CartItem::first();
        $this->assertEquals(364.00, $item->unitPrice()); // 349 + 15
        $this->assertSame('Nova', $item->selected_options[0]['value']);
    }

    public function test_courier_delivery_requires_an_address_and_adds_shipping(): void
    {
        $product = Product::where('sku', 'LINKR-LP152')->first();
        $this->post("/cart/add/{$product->id}", ['quantity' => 1]);

        $courier = ShopMethod::where('code', 'courier')->first();

        $this->post('/place-order', $this->orderPayload(['delivery_id' => $courier->id]))
            ->assertSessionHasErrors(['delivery_city', 'delivery_address']);

        $this->post('/place-order', $this->orderPayload([
            'delivery_id' => $courier->id,
            'delivery_city' => 'New York',
            'delivery_address' => '5th Avenue 1',
        ]));

        $this->assertEquals(14.49 + 9.99, (float) Order::first()->total);
    }

    public function test_promo_code_discounts_the_order(): void
    {
        $product = Product::where('sku', 'ORBIT-PHONE-15-128-BLK')->first();
        PromoCode::create(['code' => 'save10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $this->post("/cart/add/{$product->id}", ['quantity' => 1]);
        $this->post('/cart/promo', ['code' => 'SAVE10']);
        $this->post('/place-order', $this->orderPayload());

        $order = Order::first();
        $this->assertEquals(76.90, (float) $order->discount_amount);
        $this->assertEquals(692.10, (float) $order->total);
        $this->assertSame(1, PromoCode::first()->used_count);
    }

    public function test_cancelling_an_order_releases_the_reservation(): void
    {
        $product = Product::where('sku', 'ORBIT-PHONE-15-128-BLK')->first();
        $this->post("/cart/add/{$product->id}", ['quantity' => 3]);
        $this->post('/place-order', $this->orderPayload());

        $order = Order::first();
        $stock = fn () => ProductStock::where('product_id', $product->id)->whereNull('product_attribute_id')->first();

        $this->assertSame(3, $stock()->reserved);

        app(StockService::class)->releaseOrderItems($order->items()->with('product')->get());

        $this->assertSame(0, $stock()->reserved);
    }

    public function test_guest_cart_is_merged_into_the_account_on_login(): void
    {
        $this->seed(DatabaseSeeder::class);
        $product = Product::where('sku', 'ORBIT-PHONE-15-128-BLK')->first();

        $this->post("/cart/add/{$product->id}", ['quantity' => 2]);

        $this->post('/login', ['email' => 'customer@example.com', 'password' => 'password']);

        $user = User::where('email', 'customer@example.com')->first();
        $this->assertSame(2, (int) CartItem::where('user_id', $user->id)->sum('quantity'));
        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
