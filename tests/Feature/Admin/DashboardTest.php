<?php

namespace Tests\Feature\Admin;

use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_customers_cannot_open_the_admin_panel(): void
    {
        $this->actingAs($this->userWithRole(UserRole::CUSTOMER))->get('/admin')->assertForbidden();
    }

    public function test_admins_and_moderators_can_open_the_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->has('stats')
                ->has('chart', 30));

        $this->actingAs($this->userWithRole(UserRole::MODERATOR))->get('/admin?period=year')
            ->assertInertia(fn (Assert $page) => $page->has('chart', 12));
    }
}
