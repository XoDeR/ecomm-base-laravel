import { Head, Link } from '@inertiajs/react';
import { Pagination } from '@/components/storefront/pagination';
import { ProductGrid } from '@/components/storefront/product-card';
import type { PaginationLink, ProductCard } from '@/types';

type Props = {
    query: string;
    products: { data: ProductCard[]; links: PaginationLink[]; total: number } | null;
    popularQueries: string[];
};

export default function Search({ query, products, popularQueries }: Props) {
    return (
        <>
            <Head title={query ? `Search: ${query}` : 'Search'} />

            <h1 className="mb-2 text-3xl font-bold">Search</h1>

            {products === null ? (
                <p className="mb-6 text-slate-500">Enter at least 2 characters to search for products.</p>
            ) : (
                <p className="mb-6 text-slate-500">
                    Results for “<strong>{query}</strong>”: {products.total}
                </p>
            )}

            {products && products.data.length > 0 && (
                <>
                    <ProductGrid products={products.data} />
                    <Pagination links={products.links} />
                </>
            )}

            {products && products.data.length === 0 && (
                <div className="rounded-lg bg-slate-50 p-8 text-center text-slate-500">
                    Nothing found. Try a different search term.
                </div>
            )}

            {popularQueries.length > 0 && (
                <section className="mt-8">
                    <h2 className="mb-3 text-lg font-semibold">Popular searches</h2>
                    <div className="flex flex-wrap gap-2">
                        {popularQueries.map((popular) => (
                            <Link
                                key={popular}
                                href={`/search?q=${encodeURIComponent(popular)}`}
                                className="rounded-full border border-slate-300 px-3 py-1 text-sm hover:bg-blue-50"
                            >
                                {popular}
                            </Link>
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
