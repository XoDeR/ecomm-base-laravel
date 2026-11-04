<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShopMethod;
use App\Services\CartService;
use App\Services\PromoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PromoService $promo,
    ) {}

    public function index(Request $request): Response
    {
        $items = $this->cart->items();
        $subtotal = $items->sum(fn (CartItem $item) => $item->lineTotal());
        $applied = $this->promo->resolve($subtotal);

        return Inertia::render('cart/index', [
            'items' => $items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'image' => $item->product->image
                    ? str_replace('/original/', '/thumb/', $item->product->image)
                    : null,
                'selected_options' => $item->selected_options ?? [],
                'unit_price' => $item->unitPrice(),
                'quantity' => $item->quantity,
                'stock' => $this->cart->availableStock($item->product, $item->selected_options ?? []),
                'total_price' => $item->lineTotal(),
            ])->values()->all(),
            'subtotal' => $subtotal,
            'promo' => $applied ? ['code' => $applied['promo']->code, 'discount' => $applied['discount']] : null,
            'total' => $subtotal - ($applied['discount'] ?? 0),
            'shippingIsFree' => $this->shippingIsFree(),
            'continueShoppingUrl' => $request->session()->get('last_shopping_url', '/products'),
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_visible, 404);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'selected_option_ids' => ['nullable', 'array'],
            'selected_option_ids.*' => ['integer'],
        ]);

        $result = $this->cart->add($product, (int) ($data['quantity'] ?? 1), $data['selected_option_ids'] ?? []);

        $this->toastResult($result);

        if ($result['success']) {
            $request->session()->put('last_shopping_url', url()->previous());
        }

        return back();
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cart_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $this->toastResult($this->cart->updateQuantity($data['cart_item_id'], $data['quantity']));

        return back();
    }

    public function remove(int $id): RedirectResponse
    {
        $this->cart->remove($id);

        return back();
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();
        $this->promo->forget();

        return back();
    }

    public function applyPromo(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        $this->toastResult($this->promo->apply($data['code'], $this->cart->subtotal()));

        return back();
    }

    public function removePromo(): RedirectResponse
    {
        $this->promo->forget();

        return back();
    }

    /**
     * Shipping is only advertised as free when every active method is free.
     */
    private function shippingIsFree(): bool
    {
        return ShopMethod::shipping()->active()->get()->every(fn (ShopMethod $method) => $method->shippingCost() <= 0);
    }

    /**
     * @param  array{success: bool, message: string}  $result
     */
    private function toastResult(array $result): void
    {
        Inertia::flash('toast', ['type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']]);
    }
}
