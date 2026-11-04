<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use RuntimeException;

/**
 * Stock bookkeeping: quantities, reservations for open orders and an audit log.
 *
 * Methods that change data should run inside a database transaction.
 */
class StockService
{
    /**
     * Quantity currently on hand for the product (or one of its variants).
     */
    public function quantity(Product $product, ?int $variantId = null): int
    {
        return (int) $this->find($product->id, $variantId)?->quantity;
    }

    public function available(Product $product, ?int $variantId = null): int
    {
        return (int) $this->find($product->id, $variantId)?->available();
    }

    /**
     * Receive stock (positive) or write it off (negative).
     */
    public function adjust(Product $product, int $delta, ?int $variantId = null, string $comment = ''): ProductStock
    {
        $stock = $this->lock($product, $variantId);

        if ($stock->quantity + $delta < 0) {
            throw new RuntimeException("Not enough stock of {$product->name} to remove ".abs($delta).' items.');
        }

        $stock->increment('quantity', $delta);
        $this->log($product, $stock, $delta >= 0 ? 'add' : 'remove', abs($delta), $comment);

        return $stock;
    }

    /**
     * Set the on-hand quantity to an exact value (stock-taking).
     */
    public function set(Product $product, int $quantity, ?int $variantId = null, string $comment = ''): ProductStock
    {
        $stock = $this->lock($product, $variantId);
        $delta = $quantity - $stock->quantity;

        $stock->update(['quantity' => max(0, $quantity)]);
        $this->log($product, $stock, 'set', $delta, $comment);

        return $stock;
    }

    /**
     * Reserve stock for a new order.
     *
     * @throws RuntimeException when not enough items are available
     */
    public function reserve(Product $product, int $qty, ?int $variantId = null): void
    {
        $stock = $this->lock($product, $variantId);

        if ($stock->available() < $qty) {
            throw new RuntimeException("Not enough stock of {$product->name}.");
        }

        $stock->increment('reserved', $qty);
        $this->log($product, $stock, 'reserve', $qty, 'Reserved for an order');
    }

    public function release(Product $product, int $qty, ?int $variantId = null): void
    {
        $stock = $this->lock($product, $variantId);
        $qty = min($qty, $stock->reserved);

        if ($qty > 0) {
            $stock->decrement('reserved', $qty);
            $this->log($product, $stock, 'release', $qty, 'Reservation released');
        }
    }

    /**
     * Convert a reservation into a real sale: the goods leave the warehouse.
     */
    public function commit(Product $product, int $qty, ?int $variantId = null): void
    {
        $stock = $this->lock($product, $variantId);
        $reserved = min($qty, $stock->reserved);

        $stock->update([
            'quantity' => max(0, $stock->quantity - $qty),
            'reserved' => $stock->reserved - $reserved,
        ]);
        $this->log($product, $stock, 'remove', $qty, 'Sold');
    }

    /**
     * Reserve every line of an order (product stock plus chosen variants).
     *
     * @param  iterable<OrderItem>  $items
     */
    public function reserveOrderItems(iterable $items): void
    {
        $this->forEachStockLine($items, fn (Product $p, int $qty, ?int $variant) => $this->reserve($p, $qty, $variant));
    }

    /**
     * @param  iterable<OrderItem>  $items
     */
    public function releaseOrderItems(iterable $items): void
    {
        $this->forEachStockLine($items, fn (Product $p, int $qty, ?int $variant) => $this->release($p, $qty, $variant));
    }

    /**
     * @param  iterable<OrderItem>  $items
     */
    public function commitOrderItems(iterable $items): void
    {
        $this->forEachStockLine($items, fn (Product $p, int $qty, ?int $variant) => $this->commit($p, $qty, $variant));
    }

    /**
     * Put goods of an already completed order back on the shelf (customer return).
     *
     * @param  iterable<OrderItem>  $items
     */
    public function restockOrderItems(iterable $items): void
    {
        $this->forEachStockLine($items, fn (Product $p, int $qty, ?int $variant) => $this->adjust($p, $qty, $variant, 'Returned by the customer'));
    }

    /**
     * @param  iterable<OrderItem>  $items
     * @param  callable(Product, int, ?int): void  $callback
     */
    private function forEachStockLine(iterable $items, callable $callback): void
    {
        foreach ($items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            $callback($product, $item->qty, null);

            foreach ($item->selected_options ?? [] as $option) {
                if (! empty($option['product_attribute_id'])) {
                    $callback($product, $item->qty, (int) $option['product_attribute_id']);
                }
            }
        }
    }

    private function find(int $productId, ?int $variantId): ?ProductStock
    {
        return ProductStock::query()
            ->where('product_id', $productId)
            ->where('product_attribute_id', $variantId)
            ->first();
    }

    /**
     * Load the stock row with a row lock, creating it on first use.
     */
    private function lock(Product $product, ?int $variantId): ProductStock
    {
        $stock = ProductStock::query()
            ->where('product_id', $product->id)
            ->where('product_attribute_id', $variantId)
            ->lockForUpdate()
            ->first();

        return $stock ?? ProductStock::create([
            'product_id' => $product->id,
            'product_attribute_id' => $variantId,
            'sku' => $product->sku,
            'quantity' => 0,
            'reserved' => 0,
        ]);
    }

    private function log(Product $product, ProductStock $stock, string $event, int $qty, string $comment): void
    {
        InventoryLog::create([
            'product_id' => $product->id,
            'sku' => $stock->sku ?? $product->sku,
            'event_type' => $event,
            'qty' => $qty,
            'comment' => $comment,
        ]);
    }
}
