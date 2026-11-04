<?php

namespace Tests;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests must not depend on a prior `npm run build`.
        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Create a user holding the given role (admin by default).
     */
    protected function userWithRole(string $slug = UserRole::ADMIN): User
    {
        $role = UserRole::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function admin(): User
    {
        return $this->userWithRole(UserRole::ADMIN);
    }
}
