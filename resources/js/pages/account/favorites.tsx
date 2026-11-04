import { Head } from '@inertiajs/react';
import { AccountNav } from '@/components/storefront/account-nav';
import { ProductGrid } from '@/components/storefront/product-card';
import type { ProductCard } from '@/types';

export default function Favorites({ products }: { products: ProductCard[] }) {
    return (
        <>
            <Head title="Favorites" />
            <h1 className="mb-4 text-3xl font-bold">Favorites</h1>
            <AccountNav />

            {products.length === 0 ? (
                <p className="text-slate-500">You have no favorite products yet.</p>
            ) : (
                <ProductGrid products={products} />
            )}
        </>
    );
}
