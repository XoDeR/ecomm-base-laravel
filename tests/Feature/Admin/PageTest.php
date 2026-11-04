<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\UrlRedirect;
use App\Support\Html;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_page_with_sanitized_content(): void
    {
        $this->actingAs($this->admin())->post('/admin/pages', [
            'title' => 'Our Story',
            'content' => '<p onclick="steal()">Hello</p><script>alert(1)</script><a href="javascript:alert(2)">x</a>',
            'is_active' => true,
        ])->assertRedirect('/admin/pages');

        $page = Page::first();

        $this->assertSame('our-story', $page->slug);
        $this->assertStringNotContainsString('script', $page->content);
        $this->assertStringNotContainsString('onclick', $page->content);
        $this->assertStringNotContainsString('javascript:', $page->content);
        $this->assertStringContainsString('Hello', $page->content);

        $this->get('/our-story')->assertOk();
    }

    public function test_reserved_slugs_are_rejected_and_generated_slugs_avoid_them(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/pages', ['title' => 'X', 'slug' => 'cart', 'content' => 'c'])
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)->post('/admin/pages', ['title' => 'Cart', 'content' => 'c']);
        $this->assertSame('cart-page', Page::first()->slug);
    }

    public function test_unpublished_pages_are_hidden_and_renamed_slugs_redirect(): void
    {
        $admin = $this->admin();
        $page = Page::create(['title' => 'About', 'slug' => 'about', 'content' => 'x', 'is_active' => true]);

        $this->actingAs($admin)->put("/admin/pages/{$page->id}", ['title' => 'About', 'slug' => 'about-us', 'content' => 'x', 'is_active' => true]);

        $this->assertSame('about-us', UrlRedirect::where('old_slug', 'about')->value('new_slug'));
        $this->get('/about')->assertRedirect('/about-us')->assertStatus(301);

        $this->actingAs($admin)->put("/admin/pages/{$page->id}", ['title' => 'About', 'slug' => 'about-us', 'content' => 'x', 'is_active' => false]);
        $this->get('/about-us')->assertNotFound();
    }

    public function test_image_upload_returns_a_url(): void
    {
        $response = $this->actingAs($this->admin())->postJson('/admin/pages/upload', ['image' => UploadedFile::fake()->image('p.png')]);

        $response->assertOk();
        $path = $response->json('url');
        $this->assertStringStartsWith('/uploads/pages/', $path);
        @unlink(public_path(ltrim($path, '/')));
    }

    public function test_html_sanitizer_keeps_formatting(): void
    {
        $clean = Html::sanitize('<h2>Title</h2><ul><li>One</li></ul><img src="/uploads/a.jpg" onerror="x()"><iframe src="evil"></iframe>');

        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<li>One</li>', $clean);
        $this->assertStringContainsString('<img src="/uploads/a.jpg">', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
    }
}
