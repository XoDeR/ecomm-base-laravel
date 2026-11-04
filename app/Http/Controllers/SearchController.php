<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\CompareService;
use App\Support\ProductPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request, CompareService $compare): Response
    {
        $term = trim((string) $request->query('q', ''));

        $paginator = null;

        if (mb_strlen($term) >= 2) {
            $paginator = $this->search($term)->paginate(self::PER_PAGE)->withQueryString();

            if ($paginator->currentPage() === 1) {
                SearchQuery::record($term, $paginator->total());
            }
        }

        return Inertia::render('search', [
            'query' => $term,
            'products' => $paginator ? [
                'data' => ProductPresenter::cards($paginator->items(), $compare->ids()),
                'links' => $paginator->linkCollection()->all(),
                'total' => $paginator->total(),
            ] : null,
            'popularQueries' => SearchQuery::query()->where('results_count', '>', 0)
                ->orderByDesc('search_count')->limit(8)->pluck('query'),
        ]);
    }

    /**
     * Name matches first, then SKU / vendor / description; ties broken by popularity.
     *
     * @return Builder<Product>
     */
    private function search(string $term): Builder
    {
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return Product::visible()
            ->with(['discounts', 'stock'])
            ->where(fn (Builder $q) => $q
                ->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('vendor', 'like', $like)
                ->orWhere('description', 'like', $like))
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 WHEN sku LIKE ? THEN 1 ELSE 2 END', [$like, $like])
            ->orderByDesc('views_count');
    }
}
