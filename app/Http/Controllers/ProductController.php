<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Setting;
use App\Models\UrlRedirect;
use App\Models\UserActivityLog;
use App\Services\CompareService;
use App\Services\ProductFilterService;
use App\Services\SeoService;
use App\Support\ProductPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const PER_PAGE = 12;

    /**
     * Popular products: most ordered first, then most favorited, then newest.
     */
    public function index(Request $request, CompareService $compare): Response
    {
        $products = Product::visible()
            ->with(['discounts', 'stock'])
            ->withCount(['orderItems', 'favoritedBy'])
            ->orderByDesc('order_items_count')
            ->orderByDesc('favorited_by_count')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('products/index', [
            'products' => $this->paginated($products, $compare->ids()),
            'categories' => Category::tree(),
        ]);
    }

    public function category(Request $request, string $path, ProductFilterService $filters, CompareService $compare): Response|RedirectResponse
    {
        $category = Category::active()->where('path', '/'.$path)->first();

        if (! $category) {
            return $this->redirectOrFail('category', $path, fn (string $new) => '/category/'.ltrim($new, '/'));
        }

        $applied = $filters->parse($request);

        $products = $filters->query($category, $applied)
            ->with(['discounts', 'stock'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('products/category', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
            ],
            'breadcrumbs' => $category->breadcrumbs()->map(fn (Category $crumb) => [
                'id' => $crumb->id,
                'title' => $crumb->name,
                'href' => $crumb->id === $category->id ? null : $crumb->url,
            ])->all(),
            'categories' => Category::tree(),
            'products' => $this->paginated($products, $compare->ids()),
            'filterOptions' => $filters->options($category, $applied),
            'priceRange' => $filters->priceRange($category),
            'applied' => $applied,
            'sorts' => ProductFilterService::SORTS,
            'seo' => SeoService::forCategory($category),
        ]);
    }

    public function show(Request $request, string $slug, CompareService $compare): Response|RedirectResponse
    {
        $product = Product::visible()
            ->with(['category', 'images', 'discounts', 'stock', 'attributeValues.attribute', 'attributeValues.option'])
            ->where('slug', $slug)
            ->first();

        if (! $product) {
            return $this->redirectOrFail('product', $slug, fn (string $new) => '/product/'.$new);
        }

        $product->increment('views_count');

        $reviewsEnabled = Setting::get('reviews_enabled', '1') === '1';
        $reviews = $reviewsEnabled
            ? $product->reviews()->visible()->roots()->with('replies')->latest()->get()
            : collect();

        $rated = $reviews->whereNotNull('rating');

        return Inertia::render('products/show', [
            'product' => [
                ...ProductPresenter::card($product, $compare->ids()),
                'description' => $product->description,
                'category' => $product->category ? ['name' => $product->category->name, 'url' => $product->category->url] : null,
                'available_quantity' => $product->available_quantity,
                'images' => $product->images->pluck('image_path')->whenEmpty(
                    fn ($images) => $product->image ? $images->push($product->image) : $images,
                )->values()->all(),
                'selectable' => $this->selectableOptions($product),
                'specs' => $product->attributeValues
                    ->reject(fn ($value) => $value->is_selectable || ! $value->attribute->is_visible)
                    ->map(fn ($value) => ['name' => $value->attribute->name, 'value' => $value->displayValue()])
                    ->values()
                    ->all(),
            ],
            'breadcrumbs' => $this->productBreadcrumbs($product),
            'reviews' => $reviews->map(fn (ProductReview $review) => $this->reviewData($review))->values()->all(),
            'reviewsEnabled' => $reviewsEnabled,
            'rating' => ['average' => round((float) $rated->avg('rating'), 1), 'count' => $rated->count()],
            'isFavorite' => $request->user()?->favorites()->whereKey($product->id)->exists() ?? false,
            'related' => ProductPresenter::cards(
                Product::visible()->with(['discounts', 'stock'])
                    ->where('category_id', $product->category_id)
                    ->whereKeyNot($product->id)
                    ->latest('id')->limit(4)->get(),
                $compare->ids(),
            ),
            'seo' => SeoService::forProduct($product),
        ]);
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        abort_unless(Setting::get('reviews_enabled', '1') === '1', 404);

        $product = Product::visible()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'parent_id' => ['nullable', 'integer', 'exists:product_reviews,id'],
        ]);

        $user = $request->user();

        $product->reviews()->create([
            'user_id' => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            // Replies carry no rating
            'rating' => empty($data['parent_id']) ? ($data['rating'] ?? null) : null,
            'author_name' => $user->name,
            'body' => $data['body'],
            'is_visible' => Setting::get('reviews_moderation', '0') !== '1',
        ]);

        UserActivityLog::record($user->id, 'review', "Reviewed {$product->name}", ['product_id' => $product->id]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => Setting::get('reviews_moderation', '0') === '1'
                ? 'Thank you! Your review will appear after moderation.'
                : 'Thank you for your review!',
        ]);

        return back();
    }

    public function toggleFavorite(Request $request): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);

        $changes = $request->user()->favorites()->toggle($data['product_id']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($changes['attached']) ? 'Added to favorites.' : 'Removed from favorites.',
        ]);

        return back();
    }

    /**
     * Product suggestions for the header search box.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $products = Product::visible()
            ->with('discounts')
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
            ->limit(6)
            ->get();

        return response()->json([
            'suggestions' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'image' => $p->image ? str_replace('/original/', '/thumb/', $p->image) : null,
                'price' => $p->final_price,
            ]),
        ]);
    }

    /**
     * @param  LengthAwarePaginator<int, Product>  $paginator
     * @param  array<int, int>  $compareIds
     * @return array<string, mixed>
     */
    private function paginated($paginator, array $compareIds): array
    {
        return [
            'data' => ProductPresenter::cards($paginator->items(), $compareIds),
            'links' => $paginator->linkCollection()->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * Selectable variants grouped by attribute, e.g. Color: Black (+$0), White (+$10).
     *
     * @return array<int, array<string, mixed>>
     */
    private function selectableOptions(Product $product): array
    {
        $stocks = $product->stocks()->whereNotNull('product_attribute_id')->get()->keyBy('product_attribute_id');

        return $product->attributeValues
            ->where('is_selectable', true)
            ->groupBy('attribute_id')
            ->map(fn ($group) => [
                'attribute_id' => $group->first()->attribute_id,
                'name' => $group->first()->attribute->name,
                'options' => $group->map(fn ($variant) => [
                    'id' => $variant->id,
                    'label' => $variant->displayValue(),
                    'price_delta' => $variant->priceDelta(),
                    'color' => $variant->option?->color_code,
                    'available' => $stocks->get($variant->id)?->available() ?? 0,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productBreadcrumbs(Product $product): array
    {
        $crumbs = $product->category
            ? $product->category->breadcrumbs()->map(fn (Category $c) => ['title' => $c->name, 'href' => $c->url])->all()
            : [];

        $crumbs[] = ['title' => $product->name];

        return $crumbs;
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewData(ProductReview $review): array
    {
        return [
            'id' => $review->id,
            'author_name' => $review->author_name,
            'rating' => $review->rating,
            'body' => $review->body,
            'created_at' => $review->created_at?->toIso8601String(),
            'replies' => $review->replies->where('is_visible', true)->map(fn (ProductReview $reply) => [
                'id' => $reply->id,
                'author_name' => $reply->author_name,
                'body' => $reply->body,
                'created_at' => $reply->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function redirectOrFail(string $entityType, string $oldSlug, callable $target): RedirectResponse
    {
        $redirect = UrlRedirect::query()
            ->where('entity_type', $entityType)
            ->where('old_slug', $oldSlug)
            ->where('is_active', true)
            ->first();

        abort_unless($redirect, 404);

        return redirect($target($redirect->new_slug), $redirect->status_code);
    }
}
