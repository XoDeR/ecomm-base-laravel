<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductStock;
use App\Models\StockDocument;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $lowOnly = $request->boolean('low');

        $products = Product::query()
            ->with(['stocks', 'attributeValues' => fn ($q) => $q->where('is_selectable', true)->with(['attribute:id,name', 'option:id,name'])])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($lowOnly, fn ($q) => $q->whereHas('stock', fn ($q) => $q->whereRaw('quantity - reserved <= ?', [5]))->orWhereDoesntHave('stock'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/stocks', [
            'products' => [
                'data' => $products->getCollection()->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => (int) $product->stock?->quantity,
                    'reserved' => (int) $product->stock?->reserved,
                    'variants' => $product->attributeValues->map(function (ProductAttribute $variant) use ($product) {
                        $stock = $product->stocks->firstWhere('product_attribute_id', $variant->id);

                        return [
                            'id' => $variant->id,
                            'label' => $variant->attribute->name.': '.$variant->displayValue(),
                            'quantity' => (int) $stock?->quantity,
                            'reserved' => (int) $stock?->reserved,
                        ];
                    })->all(),
                ])->all(),
                'links' => $products->linkCollection()->all(),
                'total' => $products->total(),
            ],
            'filters' => ['search' => $search, 'low' => $lowOnly],
            'log' => InventoryLog::with('product:id,name')->latest('id')->limit(25)->get()->map(fn (InventoryLog $entry) => [
                'id' => $entry->id,
                'product' => $entry->product?->name ?? $entry->sku,
                'event_type' => $entry->event_type,
                'qty' => $entry->qty,
                'comment' => $entry->comment,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_attributes,id'],
            'action' => ['required', Rule::in(['receipt', 'write_off', 'set'])],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variantId = $data['variant_id'] ?? null;
        $comment = $data['comment'] ?? '';

        try {
            DB::transaction(function () use ($product, $variantId, $data, $comment) {
                match ($data['action']) {
                    'receipt' => $this->stock->adjust($product, $data['quantity'], $variantId, $comment ?: 'Stock receipt'),
                    'write_off' => $this->stock->adjust($product, -$data['quantity'], $variantId, $comment ?: 'Write-off'),
                    'set' => $this->stock->set($product, $data['quantity'], $variantId, $comment ?: 'Stock-taking'),
                };

                StockDocument::create(['doc_type' => $data['action'], 'comment' => "{$product->name}: ".($comment ?: $data['action'])]);

                // Keep the denormalized variant quantity in sync with the stock row
                if ($variantId) {
                    ProductAttribute::whereKey($variantId)->update([
                        'stock_quantity' => ProductStock::where('product_id', $product->id)->where('product_attribute_id', $variantId)->value('quantity'),
                    ]);
                }
            });
        } catch (RuntimeException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return back();
        }

        $this->toast('Stock updated.');

        return back();
    }
}
