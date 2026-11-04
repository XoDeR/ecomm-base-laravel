<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CompareService;
use App\Support\ProductPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompareController extends Controller
{
    public function __construct(private readonly CompareService $compare) {}

    public function index(): Response
    {
        $products = Product::visible()
            ->with(['discounts', 'stock', 'category', 'attributeValues.attribute', 'attributeValues.option'])
            ->whereIn('id', $this->compare->ids())
            ->get();

        // Row per attribute name, one column per product
        $rows = $products
            ->flatMap(fn (Product $product) => $product->attributeValues->where('attribute.is_visible', true))
            ->pluck('attribute.name')
            ->unique()
            ->values()
            ->map(fn (string $name) => [
                'name' => $name,
                'values' => $products->mapWithKeys(fn (Product $product) => [
                    $product->id => $product->attributeValues
                        ->filter(fn ($value) => $value->attribute->name === $name)
                        ->map(fn ($value) => $value->displayValue())
                        ->implode(', ') ?: null,
                ])->all(),
            ])
            ->all();

        return Inertia::render('compare/index', [
            'products' => ProductPresenter::cards($products, $products->pluck('id')->all()),
            'rows' => $rows,
            'max' => CompareService::MAX_ITEMS,
        ]);
    }

    public function add(Product $product): RedirectResponse
    {
        abort_unless($product->is_visible, 404);

        $result = $this->compare->add($product);

        Inertia::flash('toast', ['type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']]);

        return back();
    }

    public function remove(int $id): RedirectResponse
    {
        $this->compare->remove($id);

        return back();
    }

    public function clear(): RedirectResponse
    {
        $this->compare->clear();

        return back();
    }
}
