<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shipping and payment methods offered at checkout.
 */
class ShopMethodController extends Controller
{
    public function index(): Response
    {
        $methods = ShopMethod::orderBy('type')->orderBy('sort_order')->orderBy('id')->get();

        $present = fn (ShopMethod $method) => [
            ...$method->only(['id', 'type', 'code', 'name', 'description', 'is_active', 'is_test_mode', 'sort_order']),
            'cost' => $method->shippingCost(),
            'address' => (string) $method->setting('address', ''),
            'public_key' => (string) $method->setting('public_key', ''),
            'private_key' => (string) $method->setting('private_key', ''),
        ];

        return Inertia::render('admin/methods', [
            'shipping' => $methods->where('type', ShopMethod::SHIPPING)->map($present)->values()->all(),
            'payment' => $methods->where('type', ShopMethod::PAYMENT)->map($present)->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([ShopMethod::SHIPPING, ShopMethod::PAYMENT])],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $base = Str::slug($data['name'], '_') ?: $data['type'];
        $code = $base;
        $i = 2;

        while (ShopMethod::where('type', $data['type'])->where('code', $code)->exists()) {
            $code = $base.'_'.$i++;
        }

        ShopMethod::create([
            'type' => $data['type'],
            'code' => $code,
            'name' => $data['name'],
            'is_active' => false,
            'is_test_mode' => false,
            'settings' => $data['type'] === ShopMethod::SHIPPING ? ['cost' => 0] : [],
            'sort_order' => (int) ShopMethod::where('type', $data['type'])->max('sort_order') + 1,
        ]);

        $this->toast('Method added. Review its settings and activate it.');

        return back();
    }

    public function update(Request $request, ShopMethod $method): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'is_test_mode' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'address' => ['nullable', 'string', 'max:255'],
            'public_key' => ['nullable', 'string', 'max:255'],
            'private_key' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $method->settings ?? [];

        if ($method->type === ShopMethod::SHIPPING) {
            $settings['cost'] = (float) ($data['cost'] ?? 0);

            if ($method->code === 'self_pickup') {
                $settings['address'] = $data['address'] ?? '';
            }
        } elseif ($method->code !== 'cash') {
            $settings['public_key'] = $data['public_key'] ?? '';
            $settings['private_key'] = $data['private_key'] ?? '';
        }

        $method->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_test_mode' => (bool) ($data['is_test_mode'] ?? false),
            'sort_order' => $data['sort_order'] ?? $method->sort_order,
            'settings' => $settings,
        ]);

        $this->toast('Method saved.');

        return back();
    }

    public function destroy(ShopMethod $method): RedirectResponse
    {
        $method->delete();

        $this->toast('Method deleted.');

        return back();
    }
}
