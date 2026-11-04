import { Head, Link } from '@inertiajs/react';
import { ProductGrid } from '@/components/storefront/product-card';
import type { ProductCard } from '@/types';

type PopularCategory = {
    id: number;
    name: string;
    url: string;
    image: string | null;
    products_count: number;
};

type Props = {
    popularCategories: PopularCategory[];
    newArrivals: ProductCard[];
    recommendedProducts: ProductCard[];
};

function SectionTitle({ children }: { children: string }) {
    return (
        <h2 className="relative mb-6 pb-2.5 text-2xl font-bold after:absolute after:bottom-0 after:left-0 after:h-[3px] after:w-[60px] after:bg-blue-600">
            {children}
        </h2>
    );
}

export default function Home({
    popularCategories,
    newArrivals,
    recommendedProducts,
}: Props) {
    return (
        <>
            <Head title="Home" />

            <section className="mb-14">
                <SectionTitle>Popular categories</SectionTitle>

                {popularCategories.length === 0 ? (
                    <p className="text-slate-500">No categories found.</p>
                ) : (
                    <div className="grid grid-cols-[repeat(auto-fill,minmax(200px,1fr))] gap-6">
                        {popularCategories.map((category) => (
                            <Link
                                key={category.id}
                                href={category.url}
                                className="group relative flex flex-col items-center overflow-hidden rounded-2xl border border-slate-100 bg-white px-6 py-10 text-center transition hover:-translate-y-2 hover:border-blue-600 hover:shadow-xl"
                            >
                                <div className="mb-4 flex size-[70px] items-center justify-center overflow-hidden rounded-full border border-blue-100 bg-blue-50 text-3xl font-extrabold text-blue-600 transition group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white">
                                    {category.image ? (
                                        <img
                                            src={category.image}
                                            alt={category.name}
                                            loading="lazy"
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        category.name.charAt(0)
                                    )}
                                </div>
                                <strong className="mb-2 text-lg">{category.name}</strong>
                                <span className="text-sm whitespace-nowrap text-slate-500">
                                    Products: {category.products_count}
                                </span>
                                <span className="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-blue-600 transition group-hover:scale-x-100" />
                            </Link>
                        ))}
                    </div>
                )}
            </section>

            <section id="new-arrivals" className="mb-8">
                <SectionTitle>New arrivals</SectionTitle>
                {newArrivals.length === 0 ? (
                    <p>No new products yet.</p>
                ) : (
                    <ProductGrid products={newArrivals} />
                )}
            </section>

            <section className="mb-4">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-4">
                    <SectionTitle>Recommended products</SectionTitle>
                    <Link
                        href="/products"
                        className="rounded-md border border-slate-300 px-3.5 py-2 text-sm text-slate-900 hover:bg-slate-50"
                    >
                        View all
                    </Link>
                </div>
                {recommendedProducts.length === 0 ? (
                    <p>No recommendations yet.</p>
                ) : (
                    <ProductGrid products={recommendedProducts} />
                )}
            </section>
        </>
    );
}
