import { Head } from '@inertiajs/react';
import { CategoryTree } from '@/components/storefront/category-tree';
import { Pagination } from '@/components/storefront/pagination';
import { ProductGrid } from '@/components/storefront/product-card';
import type { CategoryNode, Paginated, ProductCard } from '@/types';

type Props = {
    products: Paginated<ProductCard>;
    categories: CategoryNode[];
};

export default function ProductsIndex({ products, categories }: Props) {
    return (
        <>
            <Head title="Popular products" />

            <div className="mb-4 flex flex-wrap items-baseline justify-between gap-4">
                <h2 className="text-2xl font-bold">Popular products</h2>
                <div className="text-sm text-slate-500">
                    Products found: <strong>{products.total}</strong>
                </div>
            </div>

            <div className="grid items-start gap-5 lg:grid-cols-[300px_1fr]">
                <aside>
                    <CategoryTree nodes={categories} />
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
