<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\ProductPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The signed-in customer's area: overview, order history and favorites.
 */
class AccountController extends Controller
{
    public function profile(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('account/profile', [
            'stats' => [
                'orders' => $user->orders()->count(),
                'favorites' => $user->favorites()->count(),
                'bonus' => (int) $user->bonus?->balance,
            ],
            'recentOrders' => $user->orders()->latest()->limit(5)->get()->map(fn (Order $order) => $this->orderSummary($order))->all(),
        ]);
    }

    public function orders(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->with('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('account/orders', [
            'orders' => [
                'data' => $orders->getCollection()->map(fn (Order $order) => [
                    ...$this->orderSummary($order),
                    'tracking_number' => $order->tracking_number,
                    'delivery_method' => $order->delivery_method,
                    'payment_method' => $order->payment_method,
                    'items' => $order->items->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->product_name,
                        'qty' => $item->qty,
                        'price' => (float) $item->price,
                        'options' => $item->selected_options ?? [],
                    ])->all(),
                ])->all(),
                'links' => $orders->linkCollection()->all(),
            ],
        ]);
    }

    public function favorites(Request $request): Response
    {
        $products = $request->user()->favorites()
            ->visible()
            ->with(['discounts', 'stock'])
            ->latest('favorites.created_at')
            ->get();

        return Inertia::render('account/favorites', [
            'products' => ProductPresenter::cards($products),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'total' => (float) $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
