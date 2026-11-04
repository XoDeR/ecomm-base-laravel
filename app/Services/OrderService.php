<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Order lifecycle: status transitions with their stock side effects.
 *
 * Stock is reserved when an order is placed. Completing the order removes the goods
 * from the warehouse; cancelling or returning it gives the reservation (or the goods) back.
 */
class OrderService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * @throws RuntimeException when the transition is not allowed
     */
    public function changeStatus(Order $order, OrderStatus $new, ?string $trackingNumber = null, ?int $changedBy = null): void
    {
        $old = $order->status;

        $trackingNumber = trim((string) $trackingNumber);

        if ($old === $new && $trackingNumber === '') {
            return;
        }

        if ($new === OrderStatus::Shipped && $trackingNumber === '' && blank($order->tracking_number)) {
            throw new RuntimeException('A tracking number is required to mark the order as shipped.');
        }

        if (in_array($old, [OrderStatus::Cancelled, OrderStatus::Returned], true) && $old !== $new) {
            throw new RuntimeException('A cancelled or returned order cannot be reopened.');
        }

        if ($old === OrderStatus::Completed && ! in_array($new, [OrderStatus::Completed, OrderStatus::Returned], true)) {
            throw new RuntimeException('A completed order can only be marked as returned.');
        }

        DB::transaction(function () use ($order, $old, $new, $trackingNumber, $changedBy) {
            $items = $order->items()->with('product')->get();

            if ($old !== $new) {
                if ($new === OrderStatus::Completed) {
                    $this->stock->commitOrderItems($items);
                } elseif ($new->releasesStock()) {
                    $old === OrderStatus::Completed
                        ? $this->stock->restockOrderItems($items)
                        : $this->stock->releaseOrderItems($items);
                }
            }

            $order->transitionTo($new, $trackingNumber, $changedBy);
        });
    }

    /**
     * Delete an order, releasing its reservation when it was still open.
     */
    public function delete(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $open = ! in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::Returned], true);

            if ($open) {
                $this->stock->releaseOrderItems($order->items()->with('product')->get());
            }

            $order->delete();
        });
    }
}
