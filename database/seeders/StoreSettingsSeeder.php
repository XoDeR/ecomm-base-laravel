<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Page;
use App\Models\Setting;
use App\Models\ShopMethod;
use App\Models\SocialLink;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

/**
 * Roles, currencies, settings, checkout methods, social links and static pages.
 */
class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->roles();
        $this->currencies();
        $this->settings();
        $this->shopMethods();
        $this->socialLinks();
        $this->pages();
    }

    private function roles(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => UserRole::ADMIN, 'description' => 'Full access to every feature of the system'],
            ['name' => 'Moderator', 'slug' => UserRole::MODERATOR, 'description' => 'Can moderate content and manage orders'],
            ['name' => 'Customer', 'slug' => UserRole::CUSTOMER, 'description' => 'Regular user with customer permissions'],
        ];

        foreach ($roles as $role) {
            UserRole::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }

    private function currencies(): void
    {
        $currencies = [
            ['code' => 'USD', 'symbol' => '$', 'rate' => 1, 'is_active' => true],
            ['code' => 'EUR', 'symbol' => '€', 'rate' => 0.92, 'is_active' => false],
            ['code' => 'GBP', 'symbol' => '£', 'rate' => 0.79, 'is_active' => false],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(['code' => $currency['code']], $currency);
        }
    }

    private function settings(): void
    {
        $settings = [
            // group => [key => value]
            'general' => [
                'site_name' => 'My ECommerce Store',
                'site_description' => 'A fast, secure and flexible online store',
                'site_url' => config('app.url'),
                'email' => 'admin@example.com',
                'footer_about_text' => 'Your trusted online store for electronics and accessories.',
                'active_logotype' => '/uploads/logotypes/logotype_6a8099021b98c144124377.png',
                'date_format' => 'm/d/Y H:i',
                'store_status' => 'open',
                'maintenance_message' => 'We apologize, but the store is temporarily closed for maintenance.',
            ],
            'contact' => [
                'contact_phone' => '+1 (555) 000-0000',
                'contact_email' => 'admin@example.com',
                'contact_address' => '1 Main Street, New York, USA',
            ],
            'localization' => [
                'default_currency' => 'USD',
                'phone_mask' => '+1 (###) ###-####',
            ],
            'reviews' => [
                'reviews_enabled' => '1',
                'reviews_moderation' => '0',
            ],
            'catalog' => [
                'compare_enabled' => '1',
                'low_stock_threshold' => '5',
            ],
            'seo' => [
                'seo_title_template' => 'Buy {name} for {price} at {shop}',
                'seo_desc_template' => 'We offer {name} at the best price of {price}. Category: {category}. Fast delivery.',
                'seo_home_title' => '',
                'seo_home_description' => '',
                'seo_home_keywords' => '',
            ],
        ];

        foreach ($settings as $group => $values) {
            foreach ($values as $key => $value) {
                Setting::set($key, $value, $group);
            }
        }
    }

    private function shopMethods(): void
    {
        $methods = [
            ['type' => 'shipping', 'code' => 'courier', 'name' => 'Courier delivery', 'is_active' => true, 'is_test_mode' => false, 'settings' => ['cost' => 9.99], 'sort_order' => 1],
            ['type' => 'shipping', 'code' => 'self_pickup', 'name' => 'Self-pickup', 'is_active' => true, 'is_test_mode' => false, 'settings' => ['address' => '1 Main Street, New York, USA'], 'sort_order' => 2],
            ['type' => 'payment', 'code' => 'cash', 'name' => 'Payment upon receipt', 'is_active' => true, 'is_test_mode' => false, 'settings' => ['gateway_name' => 'cash'], 'sort_order' => 1],
            ['type' => 'payment', 'code' => 'card', 'name' => 'Online card payment', 'is_active' => false, 'is_test_mode' => true, 'settings' => ['public_key' => '', 'private_key' => '', 'gateway_name' => 'card'], 'sort_order' => 2],
        ];

        foreach ($methods as $method) {
            ShopMethod::updateOrCreate(['type' => $method['type'], 'code' => $method['code']], $method);
        }
    }

    private function socialLinks(): void
    {
        $links = [
            ['name' => 'Facebook', 'slug' => 'facebook', 'url' => 'https://facebook.com', 'sort_order' => 1],
            ['name' => 'Instagram', 'slug' => 'instagram', 'url' => 'https://instagram.com', 'sort_order' => 2],
            ['name' => 'Telegram', 'slug' => 'telegram', 'url' => 'https://t.me', 'sort_order' => 3],
            ['name' => 'YouTube', 'slug' => 'youtube', 'url' => 'https://youtube.com', 'sort_order' => 4],
        ];

        foreach ($links as $link) {
            SocialLink::updateOrCreate(['slug' => $link['slug']], $link + ['is_active' => true]);
        }
    }

    private function pages(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'sort_order' => 0,
                'meta_title' => 'About Us',
                'meta_description' => 'Learn more about our store.',
                'content' => '<p><strong>Ecomm Store</strong> is a modern online store focused on quality electronics, honest prices and fast delivery.</p><ul><li>Carefully selected assortment</li><li>Official warranty on every product</li><li>Friendly customer support</li></ul><p>Thank you for choosing us!</p>',
            ],
            [
                'title' => 'Delivery',
                'slug' => 'delivery',
                'sort_order' => 1,
                'meta_title' => 'Delivery and payment',
                'meta_description' => 'Delivery and payment options.',
                'content' => '<p>We deliver orders by courier or you can pick them up yourself at our store.</p><p>Orders placed before 3 PM are shipped the same day. Payment is possible upon receipt.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page + ['is_active' => true]);
        }
    }
}
