<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\SocialLink;
use Database\Seeders\StoreSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [
            'site_name' => 'Gadget Hub',
            'store_status' => 'open',
            'phone_mask' => '+1 (###) ###-####',
            ...$overrides,
        ];
    }

    public function test_settings_are_saved_and_shared_with_the_storefront(): void
    {
        $this->actingAs($this->admin())->post('/admin/settings', $this->payload([
            'contact_phone' => '+1 555 0100',
            'compare_enabled' => false,
            'reviews_enabled' => true,
        ]))->assertRedirect();

        $this->assertSame('Gadget Hub', Setting::get('site_name'));
        $this->assertSame('0', Setting::get('compare_enabled'));
        $this->assertSame('1', Setting::get('reviews_enabled'));

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('shop.name', 'Gadget Hub')
            ->where('shop.phone', '+1 555 0100')
            ->where('shop.compareEnabled', false));
    }

    public function test_validation_rejects_bad_values(): void
    {
        $this->actingAs($this->admin())->post('/admin/settings', $this->payload([
            'site_name' => '', 'store_status' => 'maybe', 'phone_mask' => 'no placeholders', 'contact_email' => 'nope',
        ]))->assertSessionHasErrors(['site_name', 'store_status', 'phone_mask', 'contact_email']);
    }

    public function test_logo_upload_and_removal(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/settings', $this->payload(['logo' => UploadedFile::fake()->image('logo.png')]));

        $logo = Setting::get('active_logotype');
        $this->assertStringStartsWith('/uploads/logotypes/', $logo);
        $this->assertFileExists(public_path(ltrim($logo, '/')));

        $this->actingAs($admin)->post('/admin/settings', $this->payload(['remove_logo' => true]));
        $this->assertFileDoesNotExist(public_path(ltrim($logo, '/')));
        $this->assertEmpty(Setting::get('active_logotype'));
    }

    public function test_social_links_are_synced(): void
    {
        $admin = $this->admin();
        $keep = SocialLink::create(['name' => 'Old', 'slug' => 'old', 'url' => 'https://old.test']);
        SocialLink::create(['name' => 'Gone', 'slug' => 'gone', 'url' => 'https://gone.test']);

        $this->actingAs($admin)->post('/admin/settings', $this->payload([
            'social_links' => [
                ['id' => $keep->id, 'name' => 'Renamed', 'url' => 'https://renamed.test', 'is_active' => true],
                ['name' => 'Brand New', 'url' => 'https://new.test', 'is_active' => true],
            ],
        ]));

        $this->assertSame(['Renamed', 'Brand New'], SocialLink::orderBy('sort_order')->pluck('name')->all());
        $this->assertSame('brand-new', SocialLink::where('name', 'Brand New')->value('slug'));
    }

    public function test_closed_store_shows_maintenance_page_except_to_admins_and_login(): void
    {
        $this->seed(StoreSettingsSeeder::class);
        Setting::set('store_status', 'closed');
        Setting::set('maintenance_message', 'Back soon!');

        $this->get('/')->assertStatus(503)->assertSee('Back soon!');
        $this->get('/products')->assertStatus(503);
        $this->get('/login')->assertOk();

        $this->actingAs($this->admin())->get('/')->assertOk();
    }
}
