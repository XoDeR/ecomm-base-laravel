<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttributeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/attributes/index', [
            'attributes' => Attribute::query()
                ->withCount(['options', 'productValues as products_count'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (Attribute $attribute) => [
                    ...$attribute->only(['id', 'name', 'slug', 'type', 'is_filterable', 'is_visible']),
                    'options_count' => $attribute->options_count,
                    'products_count' => $attribute->products_count,
                ])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/attributes/form', ['attribute' => null, 'types' => Attribute::TYPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $attribute = Attribute::create($this->fields($data));
        $this->syncOptions($attribute, $data['options'] ?? []);

        $this->toast('Attribute created.');

        return redirect('/admin/attributes');
    }

    public function edit(Attribute $attribute): Response
    {
        return Inertia::render('admin/attributes/form', [
            'attribute' => [
                ...$attribute->only(['id', 'name', 'slug', 'type', 'description', 'is_filterable', 'is_visible', 'sort_order']),
                'options' => $attribute->options->map->only(['id', 'name', 'value', 'color_code', 'sort_order'])->all(),
            ],
            'types' => Attribute::TYPES,
        ]);
    }

    public function update(Request $request, Attribute $attribute): RedirectResponse
    {
        $data = $this->validated($request, $attribute);

        $attribute->update($this->fields($data, $attribute));
        $this->syncOptions($attribute, $data['options'] ?? []);

        $this->toast('Attribute updated.');

        return redirect('/admin/attributes');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $attribute->delete();

        $this->toast('Attribute deleted.');

        return redirect('/admin/attributes');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Attribute $attribute = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('attributes', 'slug')->ignore($attribute)],
            'type' => ['required', Rule::in(Attribute::TYPES)],
            'description' => ['nullable', 'string'],
            'is_filterable' => ['boolean'],
            'is_visible' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
            'options' => ['nullable', 'array'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.value' => ['nullable', 'string', 'max:255'],
            'options.*.color_code' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fields(array $data, ?Attribute $attribute = null): array
    {
        return [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['name'], $attribute),
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'is_filterable' => (bool) ($data['is_filterable'] ?? false),
            'is_visible' => (bool) ($data['is_visible'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function uniqueSlug(string $name, ?Attribute $attribute): string
    {
        $base = Str::slug($name) ?: 'attribute';
        $slug = $base;
        $i = 2;

        while (Attribute::where('slug', $slug)->when($attribute, fn ($q) => $q->whereKeyNot($attribute->id))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * Create, update and remove options so they match the submitted list.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncOptions(Attribute $attribute, array $rows): void
    {
        if (! $attribute->hasOptions()) {
            $rows = [];
        }

        $keep = [];

        foreach (array_values($rows) as $position => $row) {
            $values = [
                'name' => $row['name'],
                'value' => ($row['value'] ?? '') !== '' ? $row['value'] : $row['name'],
                'color_code' => $attribute->type === 'color' ? ($row['color_code'] ?? null) : null,
                'sort_order' => $position + 1,
            ];

            $option = ! empty($row['id']) ? $attribute->options()->find($row['id']) : null;

            if ($option) {
                $option->update($values);
            } else {
                $option = $attribute->options()->create($values);
            }

            $keep[] = $option->id;
        }

        $attribute->options()->whereNotIn('id', $keep)->delete();
    }
}
