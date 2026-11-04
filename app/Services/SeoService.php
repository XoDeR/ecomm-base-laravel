<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;

/**
 * Builds page titles and meta tags, honoring the templates configured in the admin settings.
 */
class SeoService
{
    /**
     * @return array{title: string, description: string|null, keywords: string|null}
     */
    public static function forProduct(Product $product): array
    {
        $vars = [
            '{name}' => $product->name,
            '{price}' => Currency::activeSymbol().number_format($product->final_price, 2),
            '{category}' => $product->category?->name ?? '',
            '{shop}' => static::shopName(),
        ];

        return [
            'title' => $product->meta_title ?: static::fill(Setting::get('seo_title_template'), $vars) ?: $product->name,
            'description' => $product->meta_description ?: static::fill(Setting::get('seo_desc_template'), $vars) ?: null,
            'keywords' => $product->meta_keywords ?: null,
        ];
    }

    /**
     * @return array{title: string, description: string|null, keywords: string|null}
     */
    public static function forCategory(Category $category): array
    {
        $vars = ['{name}' => $category->name, '{shop}' => static::shopName()];

        return [
            'title' => $category->meta_title ?: static::fill(Setting::get('seo_category_title_template'), $vars) ?: $category->name,
            'description' => $category->meta_description ?: static::fill(Setting::get('seo_category_desc_template'), $vars) ?: null,
            'keywords' => $category->meta_keywords ?: null,
        ];
    }

    /**
     * @return array{title: string, description: string|null, keywords: string|null}
     */
    public static function forPage(Page $page): array
    {
        return [
            'title' => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: null,
            'keywords' => null,
        ];
    }

    /**
     * @return array{title: string, description: string|null, keywords: string|null}
     */
    public static function forHome(): array
    {
        return [
            'title' => Setting::get('seo_home_title') ?: static::shopName(),
            'description' => Setting::get('seo_home_description') ?: Setting::get('site_description'),
            'keywords' => Setting::get('seo_home_keywords') ?: null,
        ];
    }

    private static function shopName(): string
    {
        return (string) Setting::get('site_name', config('app.name'));
    }

    /**
     * @param  array<string, string>  $vars
     */
    private static function fill(?string $template, array $vars): string
    {
        return $template ? trim(strtr($template, $vars)) : '';
    }
}
