<?php

namespace Tests\Feature\Admin;

use App\Models\ShopMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShopMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_groups_methods_by_type(): void
    {
        ShopMethod::create(['type' => 'shipping', 'code' => 'courier', 'name' => 'Courier', 'settings' => ['cost' => 5]]);
        ShopMethod::create(['type' => 'payment', 'code' => 'cash', 'name' => 'Cash']);

        $this->actingAs($this->admin())->get('/admin/methods')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/methods')
                ->has('shipping', 1)
                ->has('payment', 1)
                ->where('shipping.0.cost', 5));
    }

    public function test_new_methods_get_a_unique_code_and_start_inactive(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/methods', ['type' => 'shipping', 'name' => 'Express Courier']);
        $this->actingAs($admin)->post('/admin/methods', ['type' => 'shipping', 'name' => 'Express Courier']);

        $this->assertSame(['express_courier', 'express_courier_2'], ShopMethod::orderBy('id')->pluck('code')->all());
        $this->assertSame(0, ShopMethod::where('is_active', true)->count());
    }

    public function test_updating_a_shipping_method_stores_its_cost(): void
    {
        $method = ShopMethod::create(['type' => 'shipping', 'code' => 'courier', 'name' => 'Courier', 'is_active' => false]);

        $this->actingAs($this->admin())->put("/admin/methods/{$method->id}", [
            'name' => 'Fast courier', 'is_active' => true, 'cost' => 12.5,
        ])->assertRedirect();

        $method->refresh();
        $this->assertSame('Fast courier', $method->name);
        $this->assertTrue($method->is_active);
        $this->assertSame(12.5, $method->shippingCost());
    }

    public function test_pickup_address_and_payment_keys_are_saved(): void
    {
        $admin = $this->admin();
        $pickup = ShopMethod::create(['type' => 'shipping', 'code' => 'self_pickup', 'name' => 'Pickup']);
        $card = ShopMethod::create(['type' => 'payment', 'code' => 'card', 'name' => 'Card']);

        $this->actingAs($admin)->put("/admin/methods/{$pickup->id}", ['name' => 'Pickup', 'address' => '1 Main St']);
        $this->actingAs($admin)->put("/admin/methods/{$card->id}", ['name' => 'Card', 'public_key' => 'pk_1', 'private_key' => 'sk_1', 'is_test_mode' => true]);

        $this->assertSame('1 Main St', $pickup->fresh()->setting('address'));
        $this->assertSame('sk_1', $card->fresh()->setting('private_key'));
        $this->assertTrue($card->fresh()->is_test_mode);
    }

    public function test_inactive_methods_are_not_offered_at_checkout(): void
    {
        ShopMethod::create(['type' => 'shipping', 'code' => 'off', 'name' => 'Off', 'is_active' => false]);

        $this->assertSame(0, ShopMethod::shipping()->active()->count());
    }

    public function test_method_can_be_deleted(): void
    {
        $method = ShopMethod::create(['type' => 'payment', 'code' => 'x', 'name' => 'X']);

        $this->actingAs($this->admin())->delete("/admin/methods/{$method->id}")->assertRedirect();

        $this->assertModelMissing($method);
    }
}
