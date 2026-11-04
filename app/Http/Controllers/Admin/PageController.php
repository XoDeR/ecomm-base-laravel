<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\Html;
use App\Support\SlugRedirects;
use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /** Slugs that would be shadowed by real routes. */
    private const RESERVED = ['admin', 'products', 'product', 'category', 'cart', 'compare', 'checkout', 'search', 'profile', 'settings', 'login', 'logout', 'register', 'forgot-password', 'reset-password', 'email', 'user', 'two-factor-challenge', 'order-success', 'place-order', 'favorites', 'up', 'storage', 'uploads', 'build'];

    public function index(): Response
    {
        return Inertia::render('admin/pages/index', [
            'pages' => Page::orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'slug', 'is_active', 'sort_order', 'updated_at'])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/pages/form', ['page' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Page::create($this->fields($data));

        $this->toast('Page created.');

        return redirect('/admin/pages');
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('admin/pages/form', [
            'page' => $page->only(['id', 'title', 'slug', 'content', 'meta_title', 'meta_description', 'is_active', 'sort_order']),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request, $page);
        $oldSlug = $page->slug;

        $page->update($this->fields($data, $page));

        SlugRedirects::record('page', $page, $oldSlug, $page->slug);

        $this->toast('Page updated.');

        return redirect('/admin/pages');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        $this->toast('Page deleted.');

        return redirect('/admin/pages');
    }

    /**
     * Image upload for the content editor; returns the public URL to embed.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:4096']]);

        return response()->json(['url' => Uploads::store($request->file('image'), 'pages')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Page $page = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(self::RESERVED), Rule::unique('pages', 'slug')->ignore($page)],
            'content' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fields(array $data, ?Page $page = null): array
    {
        return [
            'title' => $data['title'],
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['title'], $page),
            'content' => Html::sanitize($data['content']),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function uniqueSlug(string $title, ?Page $page): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = in_array($base, self::RESERVED, true) ? $base.'-page' : $base;
        $candidate = $slug;
        $i = 2;

        while (Page::where('slug', $candidate)->when($page, fn ($q) => $q->whereKeyNot($page->id))->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
