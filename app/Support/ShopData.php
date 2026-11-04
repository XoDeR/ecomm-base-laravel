<?php

namespace App\Support;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\CompareItem;
use App\Models\Currency;
use App\Models\Page;
use App\Models\Setting;
use App\Models\SocialLink;
use Illuminate\Http\Request;

/**
 * Storefront data shared with every Inertia page (header, footer, counters).
 */
class ShopData
{
    /**
     * @return array<string, mixed>
     */
    public static function forRequest(Request $request): array
    {
        $userId = Shopper::userId($request);
        $token = $request->hasSession() ? Shopper::token($request) : '';

        return [
            'name' => Setting::get('site_name', config('app.name')),
            'logo' => Setting::get('active_logotype'),
            'phone' => Setting::get('contact_phone', ''),
            'email' => Setting::get('contact_email', ''),
            'address' => Setting::get('contact_address', ''),
            'footerAbout' => Setting::get('footer_about_text', ''),
            'phoneMask' => Setting::get('phone_mask', '+1 (###) ###-####'),
            'currency' => [
                'symbol' => Currency::activeSymbol(),
                'code' => Currency::active()?->code ?? 'USD',
            ],
            'compareEnabled' => Setting::get('compare_enabled', '1') === '1',
            'socialLinks' => SocialLink::active()->get(['name', 'slug', 'url'])->all(),
            'footerPages' => Page::active()->get(['title', 'slug'])->all(),
            'categories' => static::categoryTree(),
            'cartCount' => (int) CartItem::query()->ownedBy($userId, $token)->sum('quantity'),
            'compareCount' => CompareItem::query()->ownedBy($userId, $token)->count(),
        ];
    }

    /**
     * Active categories as a nested tree for the header menu.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function categoryTree(): array
    {
        return Category::tree();
    }
}
