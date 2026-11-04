<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\UrlRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_nested_category_with_attributes(): void
    {
        $admin = $this->admin();
        $attribute = Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'select']);
        $parent = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Android phones',
            'parent_id' => $parent->id,
            'is_active' => true,
            'attributes' => [['attribute_id' => $attribute->id, 'is_required' => true]],
        ])->assertRedirect('/admin/categories');

        $category = Category::where('name', 'Android phones')->first();

        $this->assertSame('android-phones', $category->slug);
        $this->assertSame('/phones/android-phones', $category->path);
        $this->assertTrue((bool) $category->attributes->first()->pivot->is_required);
    }

    public function test_slug_must_be_unique_and_a_category_cannot_be_its_own_ancestor(): void
    {
        $admin = $this->admin();
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->post('/admin/categories', ['name' => 'Other', 'slug' => 'parent'])
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)->put("/admin/categories/{$parent->id}", ['name' => 'Parent', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_renaming_a_slug_records_redirects_for_the_category_and_its_children(): void
    {
        $admin = $this->admin();
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->put("/admin/categories/{$parent->id}", [
            'name' => 'Parent', 'slug' => 'renamed', 'is_active' => true,
        ])->assertRedirect('/admin/categories');

        $this->assertSame('renamed', UrlRedirect::where('old_slug', 'parent')->value('new_slug'));
        $this->assertSame('renamed/child', UrlRedirect::where('old_slug', 'parent/child')->value('new_slug'));

        $this->get('/category/parent/child')->assertRedirect('/category/renamed/child')->assertStatus(301);
    }

    public function test_deleting_a_category_moves_children_to_the_top_level(): void
    {
        $admin = $this->admin();
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->delete("/admin/categories/{$parent->id}")->assertRedirect('/admin/categories');

        $child->refresh();
        $this->assertNull($child->parent_id);
        $this->assertSame('/child', $child->path);
    }

    public function test_image_upload_is_stored(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'With image',
            'image' => UploadedFile::fake()->image('cat.jpg'),
            'is_active' => true,
        ]);

        $path = Category::where('name', 'With image')->value('image');
        $this->assertStringStartsWith('/uploads/categories/', $path);
        $this->assertFileExists(public_path(ltrim($path, '/')));

        @unlink(public_path(ltrim($path, '/')));
    }
}
