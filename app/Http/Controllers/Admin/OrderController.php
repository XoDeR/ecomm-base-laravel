<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderController extends Controller
{
    private const PER_PAGE = 15;

    /** Statuses shown as columns of the kanban board. */
    private const BOARD = [OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped];

    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): Response
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('search', ''));

        $table = Order::query()
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%")
                ->orWhere('customer_email', 'like', "%{$search}%")
                ->when(ctype_digit($search), fn ($q) => $q->orWhere('id', (int) $search))))
            ->latest()
            ->paginate(self::PER_PAGE, pageName: 'page')
            ->withQueryString();

        $board = collect(self::BOARD)->map(fn (OrderStatus $column) => [
            'status' => $column->value,
            'label' => $column->label(),
            'orders' => Order::where('status', $column->value)->latest()->limit(30)->get()->map(fn (Order $order) => $this->summary($order))->all(),
            'total' => Order::where('status', $column->value)->count(),
        ])->all();

        return Inertia::render('admin/orders/index', [
            'table' => [
                'data' => $table->getCollection()->map(fn (Order $order) => $this->summary($order))->all(),
                'links' => $table->linkCollection()->all(),
                'total' => $table->total(),
            ],
            'board' => $board,
            'statuses' => OrderStatus::options(),
            'filters' => ['status' => $status?->value ?? '', 'search' => $search, 'view' => $request->query('view') === 'table' ? 'table' : 'board'],
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['items.product:id,slug', 'statusHistory.changedBy:id,name', 'user:id,name']);

        return Inertia::render('admin/orders/show', [
            'order' => [
                ...$this->summary($order),
                'customer_email' => $order->customer_email,
                'user' => $order->user ? ['id' => $order->user->id, 'name' => $order->user->name] : null,
                'delivery_city' => $order->delivery_city,
                'delivery_warehouse' => $order->delivery_warehouse,
                'delivery_address' => $order->delivery_address,
                'tracking_number' => $order->tracking_number,
                'comment' => $order->comment,
                'shipping_cost' => (float) $order->shipping_cost,
                'discount_amount' => (float) $order->discount_amount,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->product_name,
                    'sku' => $item->sku,
                    'product_slug' => $item->product?->slug,
                    'options' => $item->selected_options ?? [],
                    'qty' => $item->qty,
                    'price' => (float) $item->price,
                ])->all(),
                'history' => $order->statusHistory->map(fn (OrderStatusHistory $row) => [
                    'id' => $row->id,
                    'old_status' => $row->old_status,
                    'new_status' => $row->new_status,
                    'tracking_number' => $row->tracking_number,
                    'changed_by' => $row->changedBy?->name,
                    'changed_at' => $row->changed_at?->toIso8601String(),
                ])->all(),
            ],
            'statuses' => OrderStatus::options(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $this->orders->changeStatus($order, OrderStatus::from($data['status']), $data['tracking_number'] ?? null, $request->user()->id);
        } catch (RuntimeException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return back();
        }

        $this->toast('Order status updated.');

        return back();
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['required', 'email', 'max:255'],
            'delivery_city' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->update($data);

        $this->toast('Order details saved.');

        return back();
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->orders->delete($order);

        $this->toast('Order deleted.');

        return redirect('/admin/orders?view=table');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Order $order): array
    {
        return [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'total' => (float) $order->total,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'delivery_method' => $order->delivery_method,
            'payment_method' => $order->payment_method,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
