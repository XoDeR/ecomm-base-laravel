<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CompareService;
use App\Support\ProductPresenter;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(CompareService $compare): Response
    {
        $compareIds = $compare->ids();

        return Inertia::render('home', [
            'popularCategories' => $this->popularCategories(),
            'newArrivals' => ProductPresenter::cards($this->newArrivals(), $compareIds),
            'recommendedProducts' => ProductPresenter::cards($this->recommended(), $compareIds),
        ]);
    }

    /**
     * Top-level categories ordered by the number of visible products (including subcategories).
     *
     * @return array<int, array<string, mixed>>
     */
    private function popularCategories(): array
    {
        return Category::active()->roots()->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url' => $category->url,
                'image' => $category->image,
                'products_count' => Product::visible()->whereIn('category_id', $category->descendantIds())->count(),
            ])
            ->sortByDesc('products_count')
            ->take(8)
            ->values()
            ->all();
    }

    private function newArrivals()
    {
        return Product::visible()->with(['discounts', 'stock'])->latest('id')->limit(12)->get();
    }

    private function recommended()
    {
        return Product::visible()
            ->with(['discounts', 'stock'])
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->latest('id')
            ->limit(8)
            ->get();
    }
}
