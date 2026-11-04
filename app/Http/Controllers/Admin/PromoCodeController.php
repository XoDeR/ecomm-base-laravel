<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PromoCodeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/promo-codes/index', [
            'promoCodes' => PromoCode::with('assignedUser:id,email')->latest('id')->get()->map(fn (PromoCode $promo) => [
                'id' => $promo->id,
                'code' => $promo->code,
                'type' => $promo->type,
                'value' => (float) $promo->value,
                'used_count' => $promo->used_count,
                'usage_limit' => $promo->usage_limit,
                'starts_at' => $promo->starts_at?->toIso8601String(),
                'expires_at' => $promo->expires_at?->toIso8601String(),
                'is_active' => $promo->is_active,
                'assigned_user' => $promo->assignedUser?->email,
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/promo-codes/form', ['promoCode' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        PromoCode::create($this->fields($request, $this->validated($request)));

        $this->toast('Promo code created.');

        return redirect('/admin/promo-codes');
    }

    public function edit(PromoCode $promoCode): Response
    {
        $promoCode->load('assignedUser:id,email');

        return Inertia::render('admin/promo-codes/form', [
            'promoCode' => [
                ...$promoCode->only(['id', 'code', 'type', 'usage_limit', 'usage_limit_per_user', 'is_active', 'used_count']),
                'value' => (float) $promoCode->value,
                'min_order_amount' => $promoCode->min_order_amount !== null ? (float) $promoCode->min_order_amount : null,
                'max_discount_amount' => $promoCode->max_discount_amount !== null ? (float) $promoCode->max_discount_amount : null,
                'starts_at' => $promoCode->starts_at?->format('Y-m-d\TH:i'),
                'expires_at' => $promoCode->expires_at?->format('Y-m-d\TH:i'),
                'assigned_user_email' => $promoCode->assignedUser?->email,
            ],
        ]);
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update($this->fields($request, $this->validated($request, $promoCode)));

        $this->toast('Promo code updated.');

        return redirect('/admin/promo-codes');
    }

    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->delete();

        $this->toast('Promo code deleted.');

        return redirect('/admin/promo-codes');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PromoCode $promo = null): array
    {
        // Codes are stored upper-case, so uniqueness must be checked against the upper-case form
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('promo_codes', 'code')->ignore($promo)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'gt:0', $request->input('type') === 'percent' ? 'max:100' : 'max:99999999'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'assigned_user_email' => ['nullable', 'email'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fields(Request $request, array $data): array
    {
        $userId = null;

        if (! empty($data['assigned_user_email'])) {
            $userId = User::where('email', $data['assigned_user_email'])->value('id');

            if (! $userId) {
                throw ValidationException::withMessages(['assigned_user_email' => 'No customer with this email address.']);
            }
        }

        return [
            'code' => $data['code'],
            'type' => $data['type'],
            'value' => $data['value'],
            'min_order_amount' => $data['min_order_amount'] ?? null,
            'max_discount_amount' => $data['type'] === 'percent' ? ($data['max_discount_amount'] ?? null) : null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_limit_per_user' => $data['usage_limit_per_user'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'assigned_user_id' => $userId,
        ];
    }
}
