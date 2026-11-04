<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const PERIODS = ['week' => 7, 'month' => 30, 'year' => 12];

    public function index(Request $request): Response
    {
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : 'month';

        // Cancelled and returned orders do not count towards revenue
        $revenueStatuses = collect(OrderStatus::cases())->reject(fn (OrderStatus $s) => $s->releasesStock())->map->value->all();

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'orders_new' => Order::where('status', OrderStatus::New->value)->count(),
                'revenue_month' => (float) Order::whereIn('status', $revenueStatuses)->where('created_at', '>=', now()->startOfMonth())->sum('total'),
                'customers' => User::whereHas('role', fn ($q) => $q->where('slug', UserRole::CUSTOMER))->count(),
                'products' => Product::count(),
                'reviews_pending' => ProductReview::where('is_visible', false)->count(),
            ],
            'period' => $period,
            'chart' => $this->salesChart($period, $revenueStatuses),
            'lowStock' => ProductStock::query()
                ->with('product:id,name,sku')
                ->whereNull('product_attribute_id')
                ->whereRaw('quantity - reserved <= ?', [5])
                ->orderByRaw('quantity - reserved')
                ->limit(8)
                ->get()
                ->map(fn (ProductStock $stock) => [
                    'id' => $stock->product_id,
                    'name' => $stock->product?->name,
                    'sku' => $stock->product?->sku,
                    'available' => $stock->available(),
                ])->all(),
            'recentOrders' => Order::latest()->limit(8)->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'total' => (float) $order->total,
                'created_at' => $order->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * Revenue and order count per day (week/month) or per month (year).
     *
     * @param  array<int, string>  $statuses
     * @return array<int, array{label: string, revenue: float, orders: int}>
     */
    private function salesChart(string $period, array $statuses): array
    {
        $buckets = self::PERIODS[$period];
        $byMonth = $period === 'year';

        $start = $byMonth
            ? now()->startOfMonth()->subMonths($buckets - 1)
            : now()->startOfDay()->subDays($buckets - 1);

        $orders = Order::whereIn('status', $statuses)->where('created_at', '>=', $start)->get(['total', 'created_at']);

        return collect(range(0, $buckets - 1))->map(function (int $i) use ($start, $byMonth, $orders) {
            /** @var Carbon $from */
            $from = $byMonth ? $start->copy()->addMonths($i) : $start->copy()->addDays($i);
            $to = $byMonth ? $from->copy()->endOfMonth() : $from->copy()->endOfDay();
            $inBucket = $orders->filter(fn (Order $o) => $o->created_at->between($from, $to));

            return [
                'label' => $from->format($byMonth ? 'M Y' : 'M d'),
                'revenue' => round((float) $inBucket->sum('total'), 2),
                'orders' => $inBucket->count(),
            ];
        })->all();
    }
}
