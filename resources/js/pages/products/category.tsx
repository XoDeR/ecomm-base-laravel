import { router } from '@inertiajs/react';
import { CategoryTree } from '@/components/storefront/category-tree';
import { StorefrontBreadcrumbs } from '@/components/storefront/breadcrumbs';
import { Pagination } from '@/components/storefront/pagination';
import { ProductGrid } from '@/components/storefront/product-card';
import { ProductFilters } from '@/components/storefront/product-filters';
import type { AppliedFilters, FilterAttribute } from '@/components/storefront/product-filters';
import { SeoHead } from '@/components/storefront/seo-head';
import type { Breadcrumb, CategoryNode, Paginated, ProductCard, SeoMeta } from '@/types';

type Props = {
    category: { id: number; name: string; description: string | null };
    breadcrumbs: (Breadcrumb & { id: number })[];
    categories: CategoryNode[];
    products: Paginated<ProductCard>;
    filterOptions: FilterAttribute[];
    priceRange: { min: number; max: number };
    applied: AppliedFilters;
    sorts: Record<string, string>;
    seo: SeoMeta;
};

export default function CategoryPage({
    category,
    breadcrumbs,
    categories,
    products,
    filterOptions,
    priceRange,
    applied,
    sorts,
    seo,
}: Props) {
    const url = window.location.pathname;

    const changeSort = (sort: string) => {
        const query: Record<string, unknown> = { sort };

        if (applied.min_price !== null) query.min_price = applied.min_price;
        if (applied.max_price !== null) query.max_price = applied.max_price;
        if (Object.keys(applied.attr).length) query.attr = applied.attr;
        if (Object.keys(applied.range).length) query.range = applied.range;

        router.get(url, query as Record<string, never>, { preserveScroll: true, preserveState: true, replace: true });
    };

    return (
        <>
            <SeoHead seo={seo} fallbackTitle={category.name} />

            <StorefrontBreadcrumbs items={breadcrumbs} />

            <section className="mb-5">
                <div className="flex flex-wrap items-baseline justify-between gap-4">
                    <h1 className="text-3xl font-bold">{category.name}</h1>
                    <div className="flex items-center gap-4 text-sm text-slate-500">
                        <span>
                            Products found: <strong>{products.total}</strong>
                        </span>
                        <select
                            value={applied.sort}
                            onChange={(event) => changeSort(event.target.value)}
                            className="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-slate-800"
                            aria-label="Sort products"
                        >
                            {Object.entries(sorts).map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
                {category.description && (
                    <p className="mt-2 whitespace-pre-line text-slate-600">{category.description}</p>
                )}
            </section>

            <div className="grid items-start gap-6 lg:grid-cols-[300px_1fr]">
                <aside className="space-y-4">
                    <CategoryTree nodes={categories} activeId={category.id} />
                    <ProductFilters
                        url={url}
                        options={filterOptions}
                        priceRange={priceRange}
                        applied={applied}
                    />
                </aside>

                <section>
                    {products.data.length === 0 ? (
                        <p className="rounded-lg bg-slate-50 p-8 text-center text-slate-500">
                            No products found.
                        </p>
                    ) : (
                        <ProductGrid products={products.data} />
                    )}
                    <Pagination links={products.links} />
                </section>
            </div>
        </>
    );
}
