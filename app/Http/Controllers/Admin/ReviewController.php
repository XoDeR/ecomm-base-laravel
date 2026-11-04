<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $visibility = in_array($request->query('visibility'), ['visible', 'hidden'], true) ? $request->query('visibility') : 'all';
        $search = trim((string) $request->query('search', ''));

        $reviews = ProductReview::query()
            ->with(['product:id,name,slug', 'user:id,email', 'parent:id,author_name'])
            ->when($visibility !== 'all', fn ($q) => $q->where('is_visible', $visibility === 'visible'))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('author_name', 'like', "%{$search}%")
                ->orWhere('body', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/reviews/index', [
            'reviews' => [
                'data' => $reviews->getCollection()->map(fn (ProductReview $review) => [
                    'id' => $review->id,
                    'author_name' => $review->author_name,
                    'user_email' => $review->user?->email,
                    'body' => $review->body,
                    'rating' => $review->rating,
                    'is_visible' => $review->is_visible,
                    'reply_to' => $review->parent?->author_name,
                    'product' => $review->product ? ['name' => $review->product->name, 'slug' => $review->product->slug] : null,
                    'created_at' => $review->created_at?->toIso8601String(),
                ])->all(),
                'links' => $reviews->linkCollection()->all(),
                'total' => $reviews->total(),
            ],
            'filters' => ['visibility' => $visibility, 'search' => $search],
            'pending' => ProductReview::where('is_visible', false)->count(),
        ]);
    }

    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $review->update([
            'body' => $data['body'],
            'rating' => $review->parent_id ? null : ($data['rating'] ?? null),
        ]);

        $this->toast('Review updated.');

        return back();
    }

    public function toggle(ProductReview $review): RedirectResponse
    {
        $review->update(['is_visible' => ! $review->is_visible]);

        $this->toast($review->is_visible ? 'Review published.' : 'Review hidden.');

        return back();
    }

    public function reply(Request $request, ProductReview $review): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:2000']]);

        $review->product->reviews()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $review->parent_id ?? $review->id,
            'author_name' => $request->user()->name.' (Store)',
            'body' => $data['body'],
            'is_visible' => true,
        ]);

        $this->toast('Reply published.');

        return back();
    }

    public function destroy(ProductReview $review): RedirectResponse
    {
        $review->delete();

        $this->toast('Review deleted.');

        return back();
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['show', 'hide', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:product_reviews,id'],
        ]);

        $query = ProductReview::whereIn('id', $data['ids']);

        match ($data['action']) {
            'show' => $query->update(['is_visible' => true]),
            'hide' => $query->update(['is_visible' => false]),
            'delete' => $query->delete(),
        };

        $this->toast(count($data['ids']).' review(s) updated.');

        return back();
    }
}
