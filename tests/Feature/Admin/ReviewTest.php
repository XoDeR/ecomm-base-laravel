<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function review(array $overrides = []): ProductReview
    {
        $product = Product::first() ?? Product::create(['name' => 'Widget', 'price' => 5]);
        $author = $this->userWithRole('customer');

        return ProductReview::create([
            'product_id' => $product->id, 'user_id' => $author->id, 'author_name' => 'Pat',
            'body' => 'Works fine', 'rating' => 4, 'is_visible' => true, ...$overrides,
        ]);
    }

    public function test_index_filters_by_visibility_and_search(): void
    {
        $this->review();
        $this->review(['is_visible' => false, 'body' => 'Spam link']);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/reviews')
            ->assertInertia(fn (Assert $page) => $page->component('admin/reviews/index')->where('reviews.total', 2)->where('pending', 1));

        $this->actingAs($admin)->get('/admin/reviews?visibility=hidden')
            ->assertInertia(fn (Assert $page) => $page->where('reviews.total', 1));

        $this->actingAs($admin)->get('/admin/reviews?search=Widget')
            ->assertInertia(fn (Assert $page) => $page->where('reviews.total', 2));
    }

    public function test_toggle_update_and_delete(): void
    {
        $review = $this->review();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/reviews/{$review->id}/toggle");
        $this->assertFalse($review->fresh()->is_visible);

        $this->actingAs($admin)->put("/admin/reviews/{$review->id}", ['body' => 'Edited text', 'rating' => 5]);
        $this->assertSame('Edited text', $review->fresh()->body);
        $this->assertSame(5, $review->fresh()->rating);

        $this->actingAs($admin)->delete("/admin/reviews/{$review->id}");
        $this->assertModelMissing($review);
    }

    public function test_admin_reply_is_attached_to_the_top_level_review(): void
    {
        $review = $this->review();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/reviews/{$review->id}/reply", ['body' => 'Thanks for the feedback!']);

        $reply = ProductReview::whereNotNull('parent_id')->first();
        $this->assertSame($review->id, $reply->parent_id);
        $this->assertStringEndsWith('(Store)', $reply->author_name);
        $this->assertTrue($reply->is_visible);

        // Replying to a reply still attaches to the root review
        $this->actingAs($admin)->post("/admin/reviews/{$reply->id}/reply", ['body' => 'Anything else?']);
        $this->assertSame(2, $review->replies()->count());
    }

    public function test_bulk_actions(): void
    {
        $a = $this->review();
        $b = $this->review();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/reviews/bulk', ['action' => 'hide', 'ids' => [$a->id, $b->id]]);
        $this->assertSame(0, ProductReview::where('is_visible', true)->count());

        $this->actingAs($admin)->post('/admin/reviews/bulk', ['action' => 'delete', 'ids' => [$a->id]]);
        $this->assertSame(1, ProductReview::count());

        $this->actingAs($admin)->post('/admin/reviews/bulk', ['action' => 'explode', 'ids' => [$b->id]])
            ->assertSessionHasErrors('action');
    }
}
