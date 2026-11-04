<?php

namespace Tests\Feature\Admin;

use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_promo_code_stored_in_upper_case(): void
    {
        $this->actingAs($this->admin())->post('/admin/promo-codes', [
            'code' => 'spring10', 'type' => 'percent', 'value' => 10, 'max_discount_amount' => 50, 'is_active' => true,
        ])->assertRedirect('/admin/promo-codes');

        $promo = PromoCode::first();
        $this->assertSame('SPRING10', $promo->code);
        $this->assertEquals(50, $promo->max_discount_amount);
    }

    public function test_validation_rules(): void
    {
        $admin = $this->admin();
        PromoCode::create(['code' => 'DUP', 'type' => 'fixed', 'value' => 5]);

        $this->actingAs($admin)->post('/admin/promo-codes', ['code' => 'dup', 'type' => 'fixed', 'value' => 5])->assertSessionHasErrors('code');
        $this->actingAs($admin)->post('/admin/promo-codes', ['code' => 'BIG', 'type' => 'percent', 'value' => 150])->assertSessionHasErrors('value');
        $this->actingAs($admin)->post('/admin/promo-codes', ['code' => 'BAD CODE', 'type' => 'fixed', 'value' => 5])->assertSessionHasErrors('code');
        $this->actingAs($admin)->post('/admin/promo-codes', [
            'code' => 'TIME', 'type' => 'fixed', 'value' => 5, 'starts_at' => '2030-01-02 00:00', 'expires_at' => '2030-01-01 00:00',
        ])->assertSessionHasErrors('expires_at');
    }

    public function test_code_can_be_assigned_to_a_customer_by_email(): void
    {
        $admin = $this->admin();
        $customer = $this->userWithRole('customer');

        $this->actingAs($admin)->post('/admin/promo-codes', [
            'code' => 'VIP', 'type' => 'fixed', 'value' => 5, 'assigned_user_email' => $customer->email,
        ]);
        $this->assertSame($customer->id, PromoCode::first()->assigned_user_id);

        $this->actingAs($admin)->post('/admin/promo-codes', [
            'code' => 'GHOST', 'type' => 'fixed', 'value' => 5, 'assigned_user_email' => 'nobody@example.com',
        ])->assertSessionHasErrors('assigned_user_email');
    }

    public function test_update_and_delete(): void
    {
        $admin = $this->admin();
        $promo = PromoCode::create(['code' => 'OLD', 'type' => 'fixed', 'value' => 5]);

        $this->actingAs($admin)->put("/admin/promo-codes/{$promo->id}", ['code' => 'OLD', 'type' => 'fixed', 'value' => 9, 'is_active' => false]);
        $this->assertEquals(9, $promo->fresh()->value);
        $this->assertFalse($promo->fresh()->is_active);

        $this->actingAs($admin)->delete("/admin/promo-codes/{$promo->id}");
        $this->assertModelMissing($promo);
    }

    public function test_discount_calculation_and_validity_rules(): void
    {
        $user = User::factory()->create();
        $percent = PromoCode::create(['code' => 'P', 'type' => 'percent', 'value' => 20, 'max_discount_amount' => 15]);
        $fixed = PromoCode::create(['code' => 'F', 'type' => 'fixed', 'value' => 500]);

        $this->assertSame(15.0, $percent->discountFor(100), 'percent discount honors the ceiling');
        $this->assertSame(100.0, $fixed->discountFor(100), 'a fixed discount never exceeds the cart total');

        $min = PromoCode::create(['code' => 'M', 'type' => 'fixed', 'value' => 5, 'min_order_amount' => 50]);
        $this->assertNotNull($min->validationError(10, $user->id, 'token'));
        $this->assertNull($min->validationError(60, $user->id, 'token'));

        $limited = PromoCode::create(['code' => 'L', 'type' => 'fixed', 'value' => 5, 'usage_limit' => 1, 'used_count' => 1]);
        $this->assertNotNull($limited->validationError(100, $user->id, 'token'));

        $personal = PromoCode::create(['code' => 'X', 'type' => 'fixed', 'value' => 5, 'assigned_user_id' => $user->id]);
        $this->assertNull($personal->validationError(100, $user->id, 'token'));
        $this->assertNotNull($personal->validationError(100, null, 'token'));
    }
}
