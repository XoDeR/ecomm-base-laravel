<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Currency;
use App\Models\PriceChangeBatch;
use App\Models\Product;
use App\Services\PriceBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PriceBatchController extends Controller
{
    public function __construct(private readonly PriceBatchService $batches) {}

    public function index(): Response
    {
        return Inertia::render('admin/price-batches/index', [
            'categories' => Category::orderBy('path')->get(['id', 'name', 'path'])->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => str_repeat('— ', substr_count((string) $category->path, '/') - 1).$category->name,
            ])->all(),
            'vendors' => Product::query()->whereNotNull('vendor')->where('vendor', '!=', '')->distinct()->orderBy('vendor')->pluck('vendor')->all(),
            'currencies' => Currency::orderByDesc('is_active')->orderBy('code')->get(['code', 'symbol', 'rate', 'is_active'])->all(),
            'history' => PriceChangeBatch::with(['category:id,name', 'admin:id,name'])->latest()->limit(30)->get()->map(fn (PriceChangeBatch $batch) => [
                'id' => $batch->id,
                'mode' => $batch->mode,
                'params' => $batch->mode_params,
                'category' => $batch->category?->name,
                'include_subcategories' => $batch->scope_include_subcategories,
                'vendor' => $batch->scope_vendor,
                'affected_count' => $batch->affected_count,
                'skipped_count' => $batch->skipped_count,
                'admin' => $batch->admin?->name,
                'created_at' => $batch->created_at?->toIso8601String(),
                'reverted_at' => $batch->reverted_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * How many products a scope would affect (shown live while the form is edited).
     */
    public function preview(Request $request): JsonResponse
    {
        return response()->json(['count' => $this->batches->scope($this->scope($request))->count()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(PriceBatchService::MODES)],
            'value' => ['required_unless:mode,currency', 'nullable', 'numeric', 'gt:0'],
            'direction' => ['nullable', Rule::in(['increase', 'decrease'])],
            'from_currency' => ['required_if:mode,currency', 'nullable', 'string', 'exists:currencies,code'],
            'round_price' => ['boolean'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'include_subcategories' => ['boolean'],
            'vendor' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $batch = $this->batches->apply(
                $this->scope($request),
                $data['mode'],
                [
                    'value' => (float) ($data['value'] ?? 0),
                    'direction' => $data['direction'] ?? 'increase',
                    'from_currency' => $data['from_currency'] ?? null,
                    'round_price' => $request->boolean('round_price'),
                ],
                $request->user()->id,
            );
        } catch (RuntimeException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return back();
        }

        $this->toast("Prices updated for {$batch->affected_count} products"
            .($batch->skipped_count ? " ({$batch->skipped_count} skipped)." : '.'));

        return back();
    }

    public function undo(PriceChangeBatch $batch): RedirectResponse
    {
        try {
            $count = $this->batches->revert($batch);
        } catch (RuntimeException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return back();
        }

        $this->toast("Prices restored for {$count} products.");

        return back();
    }

    /**
     * @return array{category_id: int|null, include_subcategories: bool, vendor: string|null}
     */
    private function scope(Request $request): array
    {
        return [
            'category_id' => $request->integer('category_id') ?: null,
            'include_subcategories' => $request->boolean('include_subcategories'),
            'vendor' => $request->input('vendor') ?: null,
        ];
    }
}
