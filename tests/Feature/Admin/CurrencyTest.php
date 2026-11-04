<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function currencies(): array
    {
        return [
            Currency::create(['code' => 'USD', 'symbol' => '$', 'rate' => 1, 'is_active' => true]),
            Currency::create(['code' => 'EUR', 'symbol' => '€', 'rate' => 0.9, 'is_active' => false]),
        ];
    }

    public function test_admin_can_add_a_currency_with_an_upper_cased_code(): void
    {
        $this->actingAs($this->admin())->post('/admin/currencies', ['code' => 'gbp', 'symbol' => '£', 'rate' => 0.8])->assertRedirect();

        $this->assertSame('GBP', Currency::where('symbol', '£')->value('code'));
    }

    public function test_invalid_currencies_are_rejected(): void
    {
        $this->currencies();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/currencies', ['code' => 'USD', 'symbol' => '$', 'rate' => 1])->assertSessionHasErrors('code');
        $this->actingAs($admin)->post('/admin/currencies', ['code' => 'TOOLONG', 'symbol' => 'x', 'rate' => 0])->assertSessionHasErrors(['code', 'rate']);
    }

    public function test_activating_switches_the_store_currency(): void
    {
        [$usd, $eur] = $this->currencies();

        $this->actingAs($this->admin())->post("/admin/currencies/{$eur->id}/activate");

        $this->assertTrue($eur->fresh()->is_active);
        $this->assertFalse($usd->fresh()->is_active);
        $this->assertSame('€', Currency::activeSymbol());
    }

    public function test_rate_can_be_updated_and_the_store_currency_cannot_be_deleted(): void
    {
        [$usd, $eur] = $this->currencies();
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/currencies/{$eur->id}", ['symbol' => '€', 'rate' => 0.95]);
        $this->assertEquals(0.95, $eur->fresh()->rate);

        $this->actingAs($admin)->delete("/admin/currencies/{$usd->id}");
        $this->assertModelExists($usd);

        $this->actingAs($admin)->delete("/admin/currencies/{$eur->id}");
        $this->assertModelMissing($eur);
    }
}
