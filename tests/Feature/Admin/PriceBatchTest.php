<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Currency;
use App\Models\PriceChangeBatch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceBatchTest extends TestCase
{
    use RefreshDatabase;

    private function products(): array
    {
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        return [
            Product::create(['name' => 'A', 'price' => 100, 'category_id' => $category->id, 'vendor' => 'Acme']),
            Product::create(['name' => 'B', 'price' => 50, 'vendor' => 'Other']),
        ];
    }

    public function test_percentage_increase_can_be_scoped_and_undone(): void
    {
        [$a, $b] = $this->products();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/price-batches', [
            'mode' => 'percent', 'value' => 10, 'direction' => 'increase', 'vendor' => 'Acme',
        ])->assertRedirect();

        $this->assertEquals(110, $a->fresh()->price);
        $this->assertEquals(50, $b->fresh()->price, 'products outside the scope are untouched');

        $batch = PriceChangeBatch::first();
        $this->assertSame(1, $batch->affected_count);

        $this->actingAs($admin)->post("/admin/price-batches/{$batch->id}/undo")->assertRedirect();

        $this->assertEquals(100, $a->fresh()->price);
        $this->assertNotNull($batch->fresh()->reverted_at);

        // A batch can only be reverted once
        $a->update(['price' => 120]);
        $this->actingAs($admin)->post("/admin/price-batches/{$batch->id}/undo");
        $this->assertEquals(120, $a->fresh()->price);
    }

    public function test_fixed_decrease_never_goes_below_zero_and_rounding_is_applied(): void
    {
        [$a, $b] = $this->products();
        $b->update(['price' => 10.40]);

        $this->actingAs($this->admin())->post('/admin/price-batches', [
            'mode' => 'fixed', 'value' => 20, 'direction' => 'decrease', 'round_price' => true,
        ]);

        $this->assertEquals(80, $a->fresh()->price);
        $this->assertEquals(0, $b->fresh()->price);
    }

    public function test_currency_conversion_uses_exchange_rates(): void
    {
        [$a] = $this->products();
        Currency::create(['code' => 'USD', 'symbol' => '$', 'rate' => 1, 'is_active' => true]);
        Currency::create(['code' => 'EUR', 'symbol' => '€', 'rate' => 0.5, 'is_active' => false]);

        // Prices are in EUR (rate 0.5), converted into the store currency USD (rate 1) => x0.5
        $this->actingAs($this->admin())->post('/admin/price-batches', ['mode' => 'currency', 'from_currency' => 'EUR']);

        $this->assertEquals(50, $a->fresh()->price);
    }

    public function test_validation_and_preview_count(): void
    {
        $this->products();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/price-batches', ['mode' => 'percent'])->assertSessionHasErrors('value');
        $this->actingAs($admin)->post('/admin/price-batches', ['mode' => 'currency'])->assertSessionHasErrors('from_currency');

        $this->actingAs($admin)->getJson('/admin/price-batches/preview?vendor=Acme')->assertJson(['count' => 1]);
        $this->actingAs($admin)->getJson('/admin/price-batches/preview')->assertJson(['count' => 2]);
    }
}
