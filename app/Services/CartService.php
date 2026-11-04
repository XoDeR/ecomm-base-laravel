<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductStock;
use App\Models\UserActivityLog;
use App\Support\Shopper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CartService
{
    public function __construct(private readonly Request $request) {}

    /**
     * @return Collection<int, CartItem>
     */
    public function items(): Collection
    {
        return CartItem::query()
            ->ownedBy(Shopper::userId($this->request), Shopper::token($this->request))
            ->with(['product.discounts', 'product.stock'])
            ->orderBy('id')
            ->get()
            ->filter(fn (CartItem $item) => $item->product !== null && $item->product->is_visible)
            ->values();
    }

    public function count(): int
    {
        return (int) CartItem::query()
            ->ownedBy(Shopper::userId($this->request), Shopper::token($this->request))
            ->sum('quantity');
    }

    public function subtotal(): float
    {
        return $this->items()->sum(fn (CartItem $item) => $item->lineTotal());
    }

    /**
     * Add a product to the cart.
     *
     * @param  array<int, int|string>  $selectedOptionIds  ids of selectable product_attributes rows
     * @return array{success: bool, message: string}
     */
    public function add(Product $product, int $quantity, array $selectedOptionIds = []): array
    {
        $quantity = max(1, $quantity);

        $options = $this->resolveOptions($product, $selectedOptionIds);
        if ($options === null) {
            return ['success' => false, 'message' => 'The selected option is not available.'];
        }

        $existing = $this->findExisting($product, $options);
        $requested = $quantity + ($existing?->quantity ?? 0);

        if ($requested > $this->availableStock($product, $options)) {
            return ['success' => false, 'message' => 'Not enough items in stock.'];
        }

        if ($existing) {
            $existing->update(['quantity' => $requested]);
        } else {
            CartItem::create([
                'user_id' => Shopper::userId($this->request),
                'session_id' => Shopper::userId($this->request) ? null : Shopper::token($this->request),
                'product_id' => $product->id,
                'selected_options' => $options ?: null,
                'quantity' => $quantity,
            ]);
        }

        UserActivityLog::record(Shopper::userId($this->request), 'cart_add', "Added to cart: {$product->name}", ['product_id' => $product->id]);

        return ['success' => true, 'message' => 'Product added to cart.'];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function updateQuantity(int $cartItemId, int $quantity): array
    {
        $item = $this->owned()->with('product.stock')->find($cartItemId);

        if (! $item) {
            return ['success' => false, 'message' => 'Cart item not found.'];
        }

        if ($quantity < 1) {
            $item->delete();

            return ['success' => true, 'message' => 'Item removed from cart.'];
        }

        if ($quantity > $this->availableStock($item->product, $item->selected_options ?? [])) {
            return ['success' => false, 'message' => 'Not enough items in stock.'];
        }

        $item->update(['quantity' => $quantity]);

        return ['success' => true, 'message' => 'Cart updated.'];
    }

    public function remove(int $cartItemId): void
    {
        $this->owned()->whereKey($cartItemId)->delete();
    }

    public function clear(): void
    {
        $this->owned()->delete();
    }

    /**
     * Quantity that can still be bought for a product (and its chosen options).
     *
     * @param  array<int, array<string, mixed>>  $options
     */
    public function availableStock(Product $product, array $options = []): int
    {
        $available = $product->available_quantity;

        foreach ($options as $option) {
            $stock = ProductStock::query()
                ->where('product_id', $product->id)
                ->where('product_attribute_id', $option['product_attribute_id'])
                ->first();

            if ($stock) {
                $available = min($available, $stock->available());
            }
        }

        return $available;
    }

    private function owned()
    {
        return CartItem::query()->ownedBy(Shopper::userId($this->request), Shopper::token($this->request));
    }

    /**
     * Validates the chosen options and expands them into the snapshot stored with the cart item.
     *
     * @param  array<int, int|string>  $selectedOptionIds
     * @return array<int, array<string, mixed>>|null null when an option is invalid
     */
    private function resolveOptions(Product $product, array $selectedOptionIds): ?array
    {
        $ids = collect($selectedOptionIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $variants = ProductAttribute::query()
            ->with(['attribute', 'option'])
            ->where('product_id', $product->id)
            ->where('is_selectable', true)
            ->get();

        $chosen = $variants->whereIn('id', $ids->all());
        if ($chosen->count() !== $ids->count()) {
            return null;
        }

        // Exactly one choice is required per selectable attribute
        $selectableAttributes = $variants->pluck('attribute_id')->unique();
        if ($chosen->pluck('attribute_id')->unique()->count() !== $selectableAttributes->count()) {
            return null;
        }

        return $chosen->map(fn (ProductAttribute $variant) => [
            'attribute_id' => $variant->attribute_id,
            'product_attribute_id' => $variant->id,
            'name' => $variant->attribute->name,
            'value' => $variant->displayValue(),
            'price_delta' => $variant->priceDelta(),
        ])->sortBy('product_attribute_id')->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function findExisting(Product $product, array $options): ?CartItem
    {
        return $this->owned()
            ->where('product_id', $product->id)
            ->get()
            ->first(fn (CartItem $item) => ($item->selected_options ?? []) == $options);
    }
}
