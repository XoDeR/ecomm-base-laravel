<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Support\Shopper;
use Illuminate\Http\Request;

/**
 * Applies a promo code stored in the session to the cart total.
 */
class PromoService
{
    private const SESSION_KEY = 'promo_code';

    public function __construct(private readonly Request $request) {}

    public function code(): ?string
    {
        return $this->request->session()->get(self::SESSION_KEY);
    }

    /**
     * Validate and remember a code.
     *
     * @return array{success: bool, message: string}
     */
    public function apply(string $code, float $cartTotal): array
    {
        $promo = PromoCode::query()->where('code', strtoupper(trim($code)))->first();

        if (! $promo) {
            return ['success' => false, 'message' => 'Promo code not found.'];
        }

        $error = $promo->validationError($cartTotal, Shopper::userId($this->request), Shopper::token($this->request));
        if ($error) {
            return ['success' => false, 'message' => $error];
        }

        $this->request->session()->put(self::SESSION_KEY, $promo->code);

        return ['success' => true, 'message' => 'Promo code applied.'];
    }

    public function forget(): void
    {
        $this->request->session()->forget(self::SESSION_KEY);
    }

    /**
     * The applicable promo and its discount for the given cart total, or null when none applies.
     *
     * @return array{promo: PromoCode, discount: float}|null
     */
    public function resolve(float $cartTotal): ?array
    {
        $code = $this->code();
        $promo = $code ? PromoCode::query()->where('code', $code)->first() : null;

        if (! $promo || $promo->validationError($cartTotal, Shopper::userId($this->request), Shopper::token($this->request))) {
            return null;
        }

        return ['promo' => $promo, 'discount' => $promo->discountFor($cartTotal)];
    }
}
