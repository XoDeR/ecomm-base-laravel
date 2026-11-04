<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\PromoCodeUsage;
use App\Models\ShopMethod;
use App\Models\UserActivityLog;
use App\Services\CartService;
use App\Services\PromoService;
use App\Services\StockService;
use App\Support\Shopper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CheckoutController extends Controller
{
    private const PICKUP_CODE = 'self_pickup';

    public function __construct(
        private readonly CartService $cart,
        private readonly PromoService $promo,
        private readonly StockService $stock,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect('/cart');
        }

        $subtotal = $items->sum(fn (CartItem $item) => $item->lineTotal());
        $applied = $this->promo->resolve($subtotal);
        $user = $request->user();

        return Inertia::render('checkout/index', [
            'items' => $items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'selected_options' => $item->selected_options ?? [],
                'total_price' => $item->lineTotal(),
            ])->values()->all(),
            'subtotal' => $subtotal,
            'discount' => $applied['discount'] ?? 0,
            'deliveryMethods' => $this->methods(ShopMethod::SHIPPING),
            'paymentMethods' => $this->methods(ShopMethod::PAYMENT),
            'defaults' => [
                'full_name' => $user?->name ?? '',
                'phone' => $user?->phone ?? '',
                'email' => $user?->email ?? '',
            ],
            'pickupCode' => self::PICKUP_CODE,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect('/cart');
        }

        $delivery = ShopMethod::shipping()->active()->find($request->input('delivery_id'));
        $needsAddress = $delivery !== null && $delivery->code !== self::PICKUP_CODE;

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'delivery_id' => ['required', 'integer', 'exists:shop_methods,id'],
            'payment_id' => ['required', 'integer', 'exists:shop_methods,id'],
            'delivery_city' => [$needsAddress ? 'required' : 'nullable', 'string', 'max:255'],
            'delivery_address' => [$needsAddress ? 'required' : 'nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = ShopMethod::payment()->active()->find($data['payment_id']);

        if (! $delivery || ! $payment) {
            throw ValidationException::withMessages(['delivery_id' => 'Please choose an available delivery and payment method.']);
        }

        try {
            $order = DB::transaction(fn () => $this->createOrder($request, $items, $delivery, $payment, $data));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['cart' => $exception->getMessage()]);
        }

        $request->session()->push('placed_orders', $order->id);

        return redirect("/order-success/{$order->id}");
    }

    public function success(Request $request, Order $order): Response
    {
        $owned = $order->user_id !== null && $order->user_id === $request->user()?->id;
        $placedHere = in_array($order->id, (array) $request->session()->get('placed_orders', []), true);

        abort_unless($owned || $placedHere, 404);

        return Inertia::render('checkout/success', [
            'order' => [
                'id' => $order->id,
                'total' => (float) $order->total,
                'customer_email' => $order->customer_email,
            ],
        ]);
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @param  array<string, mixed>  $data
     */
    private function createOrder(Request $request, $items, ShopMethod $delivery, ShopMethod $payment, array $data): Order
    {
        $subtotal = $items->sum(fn (CartItem $item) => $item->lineTotal());
        $applied = $this->promo->resolve($subtotal);
        $discount = $applied['discount'] ?? 0.0;
        $shipping = $delivery->shippingCost();
        $user = $request->user();

        $order = Order::create([
            'user_id' => $user?->id,
            'total' => $subtotal - $discount + $shipping,
            'shipping_cost' => $shipping,
            'discount_amount' => $discount,
            'promo_code_id' => $applied['promo']->id ?? null,
            'customer_name' => $data['full_name'],
            'customer_phone' => $data['phone'],
            'customer_email' => $data['email'],
            'delivery_id' => $delivery->id,
            'payment_id' => $payment->id,
            'delivery_method' => $delivery->name,
            'payment_method' => $payment->name,
            'delivery_city' => $data['delivery_city'] ?? null,
            'delivery_address' => $delivery->code === self::PICKUP_CODE
                ? (string) $delivery->setting('address')
                : ($data['delivery_address'] ?? null),
            'status' => OrderStatus::New,
            'comment' => $data['comment'] ?? null,
        ]);

        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'sku' => $item->product->sku,
                'selected_options' => $item->selected_options,
                'qty' => $item->quantity,
                'price' => $item->unitPrice(),
            ]);
        }

        // Throws (and rolls back the order) when anything went out of stock meanwhile
        $this->stock->reserveOrderItems($order->items()->with('product')->get());

        $order->statusHistory()->create([
            'old_status' => null,
            'new_status' => OrderStatus::New->value,
            'changed_by' => $user?->id,
            'changed_at' => now(),
        ]);

        if ($applied) {
            PromoCodeUsage::create([
                'promo_code_id' => $applied['promo']->id,
                'user_id' => $user?->id,
                'session_id' => $user ? null : Shopper::token($request),
                'order_id' => $order->id,
                'discount_amount' => $discount,
            ]);
            $applied['promo']->increment('used_count');
        }

        $this->cart->clear();
        $this->promo->forget();

        UserActivityLog::record($user?->id, 'order', "Placed order #{$order->id}", ['order_id' => $order->id]);

        return $order;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function methods(string $type): array
    {
        return ShopMethod::query()
            ->where('type', $type)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ShopMethod $method) => [
                'id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'cost' => $method->shippingCost(),
                'address' => $method->setting('address'),
            ])
            ->all();
    }
}
