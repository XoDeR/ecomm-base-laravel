<?php

namespace Tests\Feature\Admin;

use App\Models\UserActionAudit;
use App\Models\UserActivityLog;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_and_filters_customers(): void
    {
        $admin = $this->admin();
        $customer = $this->userWithRole(UserRole::CUSTOMER);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/users/index')->where('users.total', 2));

        $this->actingAs($admin)->get('/admin/users?role=customer')
            ->assertInertia(fn (Assert $page) => $page->where('users.total', 1)->where('users.data.0.id', $customer->id));

        $this->actingAs($admin)->get('/admin/users?search='.urlencode($customer->email))
            ->assertInertia(fn (Assert $page) => $page->where('users.total', 1));
    }

    public function test_admin_can_update_a_profile_and_the_role_change_is_audited(): void
    {
        $admin = $this->admin();
        $customer = $this->userWithRole(UserRole::CUSTOMER);
        $moderator = UserRole::firstOrCreate(['slug' => UserRole::MODERATOR], ['name' => 'Moderator']);

        $this->actingAs($admin)->put("/admin/users/{$customer->id}", [
            'name' => 'New Name', 'email' => $customer->email, 'phone' => '555', 'role_id' => $moderator->id,
        ])->assertRedirect();

        $customer->refresh();
        $this->assertSame('New Name', $customer->name);
        $this->assertTrue($customer->canAccessAdmin());
        $this->assertSame('role', UserActionAudit::first()->action_type);
    }

    public function test_admins_cannot_change_their_own_role_or_block_or_delete_themselves(): void
    {
        $admin = $this->admin();
        $customerRole = UserRole::firstOrCreate(['slug' => UserRole::CUSTOMER], ['name' => 'Customer']);

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'role_id' => $customerRole->id,
        ]);
        $this->assertTrue($admin->fresh()->isAdmin());

        $this->actingAs($admin)->post("/admin/users/{$admin->id}/block", ['is_active' => false, 'reason' => 'oops']);
        $this->assertTrue($admin->fresh()->is_active);

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}");
        $this->assertModelExists($admin);
    }

    public function test_blocking_requires_a_reason_is_audited_and_logs_the_user_out(): void
    {
        $admin = $this->admin();
        $customer = $this->userWithRole(UserRole::CUSTOMER);

        $this->actingAs($admin)->post("/admin/users/{$customer->id}/block", ['is_active' => false])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)->post("/admin/users/{$customer->id}/block", ['is_active' => false, 'reason' => 'Fraud']);

        $this->assertFalse($customer->fresh()->is_active);
        $this->assertSame('block', UserActionAudit::first()->action_type);

        // A blocked customer is signed out on the next request
        $this->actingAs($customer->fresh())->get('/profile')->assertRedirect('/login');
        $this->assertGuest();

        $this->actingAs($admin)->post("/admin/users/{$customer->id}/block", ['is_active' => true, 'reason' => 'Resolved']);
        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_bonus_balance_update_is_audited(): void
    {
        $admin = $this->admin();
        $customer = $this->userWithRole(UserRole::CUSTOMER);

        $this->actingAs($admin)->post("/admin/users/{$customer->id}/bonus", ['balance' => 150, 'reason' => 'Goodwill']);

        $this->assertSame(150, $customer->fresh()->bonus->balance);
        $audit = UserActionAudit::first();
        $this->assertSame('0', $audit->old_value);
        $this->assertSame('150', $audit->new_value);
    }

    public function test_subscription_activity_and_deletion(): void
    {
        $admin = $this->admin();
        $customer = $this->userWithRole(UserRole::CUSTOMER);
        UserActivityLog::create(['user_id' => $customer->id, 'event_type' => 'login', 'description' => 'Logged in']);

        $this->actingAs($admin)->post("/admin/users/{$customer->id}/subscription", ['marketing_email' => true]);
        $this->assertTrue($customer->fresh()->subscription->marketing_email);

        $this->actingAs($admin)->get("/admin/users/{$customer->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/users/edit')->has('activity', 1));

        $this->actingAs($admin)->post("/admin/users/{$customer->id}/activity-log/clear");
        $this->assertSame(0, UserActivityLog::count());

        $this->actingAs($admin)->delete("/admin/users/{$customer->id}")->assertRedirect('/admin/users');
        $this->assertModelMissing($customer);
    }
}
