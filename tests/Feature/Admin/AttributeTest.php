<?php

namespace Tests\Feature\Admin;

use App\Models\Attribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_select_attribute_with_options(): void
    {
        $this->actingAs($this->admin())->post('/admin/attributes', [
            'name' => 'Color',
            'type' => 'color',
            'is_filterable' => true,
            'options' => [
                ['name' => 'Red', 'color_code' => '#ff0000'],
                ['name' => 'Blue', 'value' => 'blue-ish', 'color_code' => '#0000ff'],
            ],
        ])->assertRedirect('/admin/attributes');

        $attribute = Attribute::where('slug', 'color')->first();

        $this->assertCount(2, $attribute->options);
        $this->assertSame('Red', $attribute->options[0]->value, 'value defaults to the name');
        $this->assertSame('#0000ff', $attribute->options[1]->color_code);
    }

    public function test_updating_syncs_options_and_text_attributes_have_none(): void
    {
        $admin = $this->admin();
        $attribute = Attribute::create(['name' => 'Size', 'slug' => 'size', 'type' => 'select']);
        $keep = $attribute->options()->create(['name' => 'S', 'value' => 'S']);
        $attribute->options()->create(['name' => 'M', 'value' => 'M']);

        $this->actingAs($admin)->put("/admin/attributes/{$attribute->id}", [
            'name' => 'Size',
            'type' => 'select',
            'options' => [['id' => $keep->id, 'name' => 'Small'], ['name' => 'Large']],
        ]);

        $this->assertEqualsCanonicalizing(['Small', 'Large'], $attribute->options()->pluck('name')->all());
        $this->assertTrue($attribute->options()->whereKey($keep->id)->exists());

        $this->actingAs($admin)->put("/admin/attributes/{$attribute->id}", [
            'name' => 'Size', 'type' => 'text', 'options' => [['name' => 'X']],
        ]);

        $this->assertSame(0, $attribute->options()->count());
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->actingAs($this->admin())->post('/admin/attributes', ['name' => '', 'type' => 'nope'])
            ->assertSessionHasErrors(['name', 'type']);
    }

    public function test_attribute_can_be_deleted(): void
    {
        $attribute = Attribute::create(['name' => 'Material', 'slug' => 'material', 'type' => 'text']);

        $this->actingAs($this->admin())->delete("/admin/attributes/{$attribute->id}")->assertRedirect('/admin/attributes');

        $this->assertModelMissing($attribute);
    }
}
