<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\PromoCode;
use App\Models\ShopMethod;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo orders, reviews and a promo code so the admin dashboard has something to show.
 *
 * Orders are inserted directly: stock levels from CatalogSeeder are left untouched.
 */
class DemoSalesSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::exists()) {
            return;
        }

        $customer = User::where('email', 'customer@example.com')->first();
        $delivery = ShopMethod::where('code', 'self_pickup')->first();
        $payment = ShopMethod::where('code', 'cash')->first();
        $products = Product::visible()->orderBy('id')->get();

        $orders = [
            // [days ago, status, product index, quantity, registered customer]
            [0, OrderStatus::New, 0, 1, true],
            [1, OrderStatus::Confirmed, 1, 2, false],
            [2, OrderStatus::Processing, 2, 1, true],
            [4, OrderStatus::Shipped, 3, 1, false],
            [6, OrderStatus::Completed, 5, 1, true],
            [9, OrderStatus::Completed, 6, 3, false],
            [12, OrderStatus::Cancelled, 4, 1, false],
        ];

        foreach ($orders as $position => [$daysAgo, $status, $productIndex, $quantity, $registered]) {
            $product = $products[$productIndex % $products->count()];
            $total = $product->final_price * $quantity;
            $created = now()->subDays($daysAgo)->subHours($position);

            $order = Order::create([
                'user_id' => $registered ? $customer?->id : null,
                'total' => $total,
                'shipping_cost' => 0,
                'customer_name' => $registered ? 'Jane Customer' : fake()->name(),
                'customer_phone' => '+1 (555) 01'.str_pad((string) ($position + 10), 2, '0', STR_PAD_LEFT).'-'.str_pad((string) ($position * 137 % 10000), 4, '0'),
                'customer_email' => $registered ? 'customer@example.com' : fake()->safeEmail(),
                'delivery_id' => $delivery?->id,
                'payment_id' => $payment?->id,
                'delivery_method' => $delivery?->name,
                'payment_method' => $payment?->name,
                'delivery_address' => $delivery?->setting('address'),
                'status' => $status,
                'tracking_number' => $status === OrderStatus::Shipped ? 'TRK-'.(100000 + $position) : null,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'qty' => $quantity,
                'price' => $product->final_price,
            ]);

            $order->statusHistory()->create(['old_status' => null, 'new_status' => OrderStatus::New->value, 'changed_at' => $created]);

            if ($status !== OrderStatus::New) {
                $order->statusHistory()->create(['old_status' => OrderStatus::New->value, 'new_status' => $status->value, 'changed_at' => $created->copy()->addHours(2)]);
            }

            // Backdate so the sales chart has a history
            $order->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();
        }

        if ($customer) {
            ProductReview::create([
                'product_id' => $products[5 % $products->count()]->id,
                'user_id' => $customer->id,
                'author_name' => $customer->name,
                'rating' => 5,
                'body' => 'Great phone, fast delivery. Highly recommended!',
            ]);
            ProductReview::create([
                'product_id' => $products[0]->id,
                'user_id' => $customer->id,
                'author_name' => $customer->name,
                'rating' => 4,
                'body' => 'Beautiful picture for the price. The remote could be better.',
            ]);
        }

        PromoCode::firstOrCreate(['code' => 'WELCOME10'], ['type' => 'percent', 'value' => 10, 'max_discount_amount' => 50, 'is_active' => true]);
    }
}
