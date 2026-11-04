<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Editable settings: key => [group, validation rules, default].
     *
     * @var array<string, array{0: string, 1: array<int, mixed>, 2: string}>
     */
    private const FIELDS = [
        'site_name' => ['general', ['required', 'string', 'max:100'], 'My Store'],
        'site_description' => ['general', ['nullable', 'string', 'max:500'], ''],
        'site_url' => ['general', ['nullable', 'url', 'max:255'], ''],
        'footer_about_text' => ['general', ['nullable', 'string', 'max:1000'], ''],
        'store_status' => ['general', ['required', 'in:open,closed'], 'open'],
        'maintenance_message' => ['general', ['nullable', 'string', 'max:500'], 'We apologize, but the store is temporarily closed for maintenance.'],

        'contact_phone' => ['contact', ['nullable', 'string', 'max:50'], ''],
        'contact_email' => ['contact', ['nullable', 'email', 'max:255'], ''],
        'contact_address' => ['contact', ['nullable', 'string', 'max:255'], ''],
        'phone_mask' => ['localization', ['required', 'string', 'max:40', 'regex:/#/'], '+1 (###) ###-####'],

        'compare_enabled' => ['catalog', ['boolean'], '1'],
        'reviews_enabled' => ['catalog', ['boolean'], '1'],
        'reviews_moderation' => ['catalog', ['boolean'], '0'],

        'seo_home_title' => ['seo', ['nullable', 'string', 'max:255'], ''],
        'seo_home_description' => ['seo', ['nullable', 'string', 'max:500'], ''],
        'seo_home_keywords' => ['seo', ['nullable', 'string', 'max:255'], ''],
        'seo_title_template' => ['seo', ['nullable', 'string', 'max:255'], ''],
        'seo_desc_template' => ['seo', ['nullable', 'string', 'max:500'], ''],
        'seo_category_title_template' => ['seo', ['nullable', 'string', 'max:255'], ''],
        'seo_category_desc_template' => ['seo', ['nullable', 'string', 'max:500'], ''],
    ];

    private const BOOLEANS = ['compare_enabled', 'reviews_enabled', 'reviews_moderation'];

    public function edit(): Response
    {
        $values = [];

        foreach (self::FIELDS as $key => [, , $default]) {
            $value = Setting::get($key, $default);
            $values[$key] = in_array($key, self::BOOLEANS, true) ? $value === '1' : $value;
        }

        return Inertia::render('admin/settings', [
            'settings' => $values,
            'logo' => Setting::get('active_logotype'),
            'socialLinks' => SocialLink::orderBy('sort_order')->get(['id', 'name', 'slug', 'url', 'is_active'])->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(self::FIELDS)->map(fn (array $field) => $field[1])->all();

        $data = $request->validate([
            ...$rules,
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['boolean'],
            'social_links' => ['nullable', 'array'],
            'social_links.*.id' => ['nullable', 'integer'],
            'social_links.*.name' => ['required', 'string', 'max:50'],
            'social_links.*.url' => ['nullable', 'url', 'max:255'],
            'social_links.*.is_active' => ['boolean'],
        ]);

        DB::transaction(function () use ($request, $data) {
            foreach (self::FIELDS as $key => [$group]) {
                $value = in_array($key, self::BOOLEANS, true)
                    ? ($request->boolean($key) ? '1' : '0')
                    : ($data[$key] ?? '');

                Setting::set($key, (string) $value, $group);
            }

            $this->syncLogo($request);
            $this->syncSocialLinks($data['social_links'] ?? []);
        });

        $this->toast('Settings saved.');

        return back();
    }

    private function syncLogo(Request $request): void
    {
        if ($request->hasFile('logo')) {
            Uploads::delete(Setting::get('active_logotype'));
            Setting::set('active_logotype', Uploads::store($request->file('logo'), 'logotypes'), 'general');
        } elseif ($request->boolean('remove_logo')) {
            Uploads::delete(Setting::get('active_logotype'));
            Setting::set('active_logotype', '', 'general');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncSocialLinks(array $rows): void
    {
        $keep = [];

        foreach (array_values($rows) as $position => $row) {
            $values = [
                'name' => $row['name'],
                'url' => $row['url'] ?? '',
                'is_active' => (bool) ($row['is_active'] ?? false),
                'sort_order' => $position + 1,
            ];

            $link = ! empty($row['id']) ? SocialLink::find($row['id']) : null;

            if ($link) {
                $link->update($values);
            } else {
                $link = SocialLink::create([...$values, 'slug' => $this->uniqueSlug($row['name'])]);
            }

            $keep[] = $link->id;
        }

        SocialLink::whereNotIn('id', $keep)->delete();
    }

    private function uniqueSlug(string $name): string
    {
        $base = str($name)->slug()->toString() ?: 'link';
        $slug = $base;
        $i = 2;

        while (SocialLink::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
