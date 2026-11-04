<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()->withCount('products')->orderBy('path')->get();

        return Inertia::render('admin/categories/index', [
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'path' => $category->path,
                'depth' => substr_count((string) $category->path, '/') - 1,
                'is_active' => $category->is_active,
                'products_count' => $category->products_count,
                'image' => $category->image,
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/categories/form', [
            'category' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $category = Category::create([...$this->attributes($request, $data), 'image' => $this->storeImage($request)]);
        $this->syncAttributes($category, $data['attributes'] ?? []);

        $this->toast('Category created.');

        return redirect('/admin/categories');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('admin/categories/form', [
            'category' => [
                ...$category->only([
                    'id', 'name', 'slug', 'parent_id', 'description', 'sort_order', 'image', 'is_active',
                    'meta_title', 'meta_description', 'meta_keywords',
                ]),
                'attributes' => $category->attributes->map(fn ($attribute) => [
                    'attribute_id' => $attribute->id,
                    'is_required' => (bool) $attribute->pivot->is_required,
                ])->all(),
            ],
            ...$this->formOptions($category),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);

        $image = $category->image;
        if ($request->hasFile('image')) {
            Uploads::delete($image);
            $image = $this->storeImage($request);
        } elseif ($request->boolean('remove_image')) {
            Uploads::delete($image);
            $image = null;
        }

        $category->update([...$this->attributes($request, $data, $category), 'image' => $image]);
        $this->syncAttributes($category, $data['attributes'] ?? []);

        $this->toast('Category updated.');

        return redirect('/admin/categories');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Uploads::delete($category->image);
        $category->delete();

        $this->toast('Category deleted.');

        return redirect('/admin/categories');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?Category $category = null): array
    {
        // A category cannot be moved under itself or one of its own descendants
        $excluded = $category ? $category->descendantIds() : [];

        return [
            'parents' => Category::query()
                ->whereNotIn('id', $excluded)
                ->orderBy('path')
                ->get(['id', 'name', 'path'])
                ->map(fn (Category $parent) => [
                    'id' => $parent->id,
                    'name' => str_repeat('— ', substr_count((string) $parent->path, '/') - 1).$parent->name,
                ])->all(),
            'allAttributes' => Attribute::query()->orderBy('sort_order')->get(['id', 'name', 'type'])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $excluded = $category ? $category->descendantIds() : [];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories', 'slug')->ignore($category)],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn($excluded)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'attributes.*.is_required' => ['boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(Request $request, array $data, ?Category $category = null): array
    {
        return [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], $category),
            'parent_id' => $data['parent_id'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
        ];
    }

    private function uniqueSlug(string $name, ?Category $category): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (Category::where('slug', $slug)->when($category, fn ($q) => $q->whereKeyNot($category->id))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function storeImage(Request $request): ?string
    {
        return $request->hasFile('image') ? Uploads::store($request->file('image'), 'categories') : null;
    }

    /**
     * @param  array<int, array{attribute_id: int|string, is_required?: bool}>  $rows
     */
    private function syncAttributes(Category $category, array $rows): void
    {
        $sync = [];

        foreach (array_values($rows) as $position => $row) {
            $sync[(int) $row['attribute_id']] = [
                'is_required' => (bool) ($row['is_required'] ?? false),
                'sort_order' => $position,
            ];
        }

        $category->attributes()->sync($sync);
    }
}
