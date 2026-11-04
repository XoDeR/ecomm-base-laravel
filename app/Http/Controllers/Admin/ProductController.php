<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): Response
    {
        $visibility = in_array($request->query('visibility'), ['visible', 'hidden'], true) ? $request->query('visibility') : 'all';
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->integer('category_id') ?: null;

        $products = Product::query()
            ->with(['category:id,name', 'stock', 'discounts'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('vendor', 'like', "%{$search}%")))
            ->when($categoryId, fn ($q) => $q->whereIn('category_id', Category::find($categoryId)?->descendantIds() ?? [$categoryId]))
            ->when($visibility !== 'all', fn ($q) => $q->where('is_visible', $visibility === 'visible'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/products/index', [
            'products' => [
                'data' => $products->getCollection()->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image' => $product->image ? str_replace('/original/', '/thumb/', $product->image) : null,
                    'category' => $product->category?->name,
                    'price' => (float) $product->price,
                    'final_price' => $product->final_price,
                    'available' => $product->available_quantity,
                    'is_visible' => $product->is_visible,
                    'slug' => $product->slug,
                ])->all(),
                'links' => $products->linkCollection()->all(),
                'total' => $products->total(),
            ],
            'filters' => ['q' => $search, 'category_id' => $categoryId, 'visibility' => $visibility],
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/form', [
            'product' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = $this->products->save(null, $this->validated($request), $request->file('images', []));

        $this->toast('Product created.');

        return redirect("/admin/products/{$product->id}/edit");
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'discounts', 'stock', 'attributeValues.option']);
        $discount = $product->discounts->first();

        return Inertia::render('admin/products/form', [
            'product' => [
                ...$product->only([
                    'id', 'name', 'sku', 'vendor', 'category_id', 'price', 'description', 'is_visible', 'slug',
                    'meta_title', 'meta_description', 'meta_keywords',
                ]),
                'price' => (float) $product->price,
                'stock_quantity' => $product->stock?->quantity ?? 0,
                'reserved' => $product->stock?->reserved ?? 0,
                'images' => $product->images->map->only(['id', 'image_path'])->all(),
                'main_image_id' => $product->images->first()?->id,
                'discount' => $discount ? [
                    'price' => (float) $discount->discount_price,
                    'start_date' => $discount->start_date?->format('Y-m-d\TH:i'),
                    'end_date' => $discount->end_date?->format('Y-m-d\TH:i'),
                    'is_active' => $discount->is_active,
                ] : null,
                'attributes' => $product->attributeValues->map(fn (ProductAttribute $row) => [
                    'id' => $row->id,
                    'attribute_id' => $row->attribute_id,
                    'attribute_option_id' => $row->attribute_option_id,
                    'value' => $row->value,
                    'is_selectable' => $row->is_selectable,
                    'price_operation' => $row->price_operation,
                    'price_modifier' => (float) $row->price_modifier,
                    'stock_quantity' => $row->stock_quantity ?? 0,
                ])->all(),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->products->save($product, $this->validated($request, $product), $request->file('images', []));

        $this->toast('Product updated.');

        return redirect("/admin/products/{$product->id}/edit");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        $this->toast('Product deleted.');

        return redirect('/admin/products');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product)],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($product)],
            'vendor' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'description' => ['nullable', 'string'],
            'is_visible' => ['boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],

            'discount' => ['nullable', 'array'],
            // The promotional price must be strictly lower than the regular price
            'discount.price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'discount.start_date' => ['nullable', 'date'],
            'discount.end_date' => ['nullable', 'date', 'after_or_equal:discount.start_date'],
            'discount.is_active' => ['boolean'],

            'attributes' => ['nullable', 'array'],
            'attributes.*.id' => ['nullable', 'integer'],
            'attributes.*.attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'attributes.*.attribute_option_id' => ['nullable', 'integer', 'exists:attribute_options,id'],
            'attributes.*.value' => ['nullable', 'string', 'max:255'],
            'attributes.*.is_selectable' => ['boolean'],
            'attributes.*.price_operation' => ['nullable', Rule::in(['+', '-'])],
            'attributes.*.price_modifier' => ['nullable', 'numeric', 'min:0'],
            'attributes.*.stock_quantity' => ['nullable', 'integer', 'min:0'],

            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['image', 'max:6144'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
            'main_image_id' => ['nullable', 'integer'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => $this->categoryOptions(),
            // Attributes allowed per category, so the form can switch as the category changes
            'categoryAttributes' => Category::query()->with('attributes.options')->get()->mapWithKeys(fn (Category $category) => [
                $category->id => $category->attributes->map(fn (Attribute $attribute) => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'type' => $attribute->type,
                    'is_required' => (bool) $attribute->pivot->is_required,
                    'options' => $attribute->options->map->only(['id', 'name'])->all(),
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()->orderBy('path')->get(['id', 'name', 'path'])->map(fn (Category $category) => [
            'id' => $category->id,
            'name' => str_repeat('— ', substr_count((string) $category->path, '/') - 1).$category->name,
        ])->all();
    }
}
