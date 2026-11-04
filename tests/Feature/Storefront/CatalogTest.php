<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\StoreSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([StoreSettingsSeeder::class, CatalogSeeder::class]);
    }

    public function test_home_page_lists_categories_and_products(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('home')
                ->has('popularCategories', 3)
                ->has('newArrivals')
                ->has('recommendedProducts'));
    }

    public function test_category_page_resolves_nested_paths_and_includes_subcategory_products(): void
    {
        $this->get('/category/smartphones')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/category')
                ->where('category.name', 'Smartphones')
                ->where('products.total', 1));

        $this->get('/category/smartphones/flagship')->assertOk();
        $this->get('/category/does-not-exist')->assertNotFound();
    }

    public function test_category_path_follows_a_renamed_parent_slug(): void
    {
        $parent = Category::where('slug', 'smartphones')->first();
        $parent->update(['slug' => 'phones']);

        $this->assertSame('/phones/flagship', Category::where('slug', 'flagship')->value('path'));
        $this->get('/category/phones/flagship')->assertOk();
    }

    public function test_price_filter_narrows_the_category_products(): void
    {
        $this->get('/category/televisions?min_price=300')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('products.total', 2));

        $this->get('/category/televisions?min_price=300&max_price=400')
            ->assertInertia(fn (Assert $page) => $page->where('products.total', 1));
    }

    public function test_attribute_option_filter_returns_matching_products(): void
    {
        $category = Category::where('slug', 'televisions')->first();

        $page = $this->get('/category/televisions')->viewData('page');
        $brand = collect($page['props']['filterOptions'])->firstWhere('slug', 'brand');
        $nova = collect($brand['options'])->firstWhere('label', 'Nova');

        $this->assertNotNull($nova);
        $this->assertSame(1, $nova['count']);

        $this->get("/category/televisions?attr[{$brand['id']}][]={$nova['value']}")
            ->assertInertia(fn (Assert $inertia) => $inertia->where('products.total', 1));

        $this->assertNotNull($category);
    }

    public function test_product_page_shows_discount_price_and_selectable_options(): void
    {
        $this->get('/product/smart-tv-55-android-15-120-hz-fullhd')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/show')
                ->where('product.has_discount', true)
                ->where('product.final_price', 379));

        $this->get('/product/zenith-smart-tv-43')
            ->assertInertia(fn (Assert $page) => $page->has('product.selectable', 1));
    }

    public function test_hidden_products_are_not_reachable(): void
    {
        Product::where('slug', 'orbit-phone-15-128gb-black')->update(['is_visible' => false]);

        $this->get('/product/orbit-phone-15-128gb-black')->assertNotFound();
    }

    public function test_search_finds_products_by_name(): void
    {
        $this->get('/search?q=Phone+15')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('products.total', 1));

        $this->getJson('/search/autocomplete?q=lumen')
            ->assertOk()
            ->assertJsonCount(1, 'suggestions');
    }

    public function test_static_pages_are_served_by_slug(): void
    {
        $this->get('/about-us')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('page')->where('page.title', 'About Us'));

        $this->get('/no-such-page')->assertNotFound();
    }
}
