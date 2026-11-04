import { Link, router, usePage } from '@inertiajs/react';
import { Scale } from 'lucide-react';
import { formatPrice } from '@/lib/format';
import type { ProductCard as ProductCardData } from '@/types';

export function ProductPrice({ product }: { product: ProductCardData }) {
    const { shop } = usePage().props;

    if (product.has_discount) {
        return (
            <p className="flex items-baseline gap-2">
                <strong className="text-lg text-red-600">
                    {formatPrice(product.final_price, shop.currency.symbol)}
                </strong>
                <s className="text-sm text-slate-400">
                    {formatPrice(product.price, shop.currency.symbol)}
                </s>
            </p>
        );
    }

    return (
        <p>
            <strong className="text-lg">
                {formatPrice(product.price, shop.currency.symbol)}
            </strong>
        </p>
    );
}

export function CompareButton({ product }: { product: ProductCardData }) {
    const { shop } = usePage().props;

    if (!shop.compareEnabled) {
        return null;
    }

    const toggle = () =>
        product.in_compare
            ? router.delete(`/compare/remove/${product.id}`, { preserveScroll: true })
            : router.post(`/compare/add/${product.id}`, {}, { preserveScroll: true });

    return (
        <button
            type="button"
            onClick={toggle}
            title={product.in_compare ? 'Remove from comparison' : 'Add to comparison'}
            className={`inline-flex items-center gap-1 rounded-md border px-2.5 py-2 text-sm ${
                product.in_compare
                    ? 'border-blue-600 bg-blue-50 text-blue-700'
                    : 'border-slate-300 text-slate-700 hover:bg-slate-50'
            }`}
        >
            <Scale className="size-4" />
            {product.in_compare ? 'Comparing' : 'Compare'}
        </button>
    );
}

export function ProductCard({ product }: { product: ProductCardData }) {
    const addToCart = () =>
        router.post(`/cart/add/${product.id}`, { quantity: 1 }, { preserveScroll: true });

    return (
        <article className="flex flex-col rounded-xl border border-slate-200 bg-white p-4">
            <Link href={`/product/${product.slug}`} className="mb-3 block">
                {product.image ? (
                    <img
                        src={product.image.replace('/original/', '/medium/')}
                        alt={product.name}
                        loading="lazy"
                        className={`h-[180px] w-full rounded-lg object-cover ${product.in_stock ? '' : 'opacity-55'}`}
                    />
                ) : (
                    <div className="flex h-[180px] items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                        No image
                    </div>
                )}
            </Link>
            <h3 className="mb-2 line-clamp-2 font-semibold text-slate-900">
                <Link href={`/product/${product.slug}`}>{product.name}</Link>
            </h3>
            {!product.in_stock && (
                <p className="mb-2 text-sm font-medium text-red-600">Out of stock</p>
            )}
            <div className="mt-auto mb-3">
                <ProductPrice product={product} />
            </div>
            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    disabled={!product.in_stock}
                    onClick={addToCart}
                    className="rounded-md bg-slate-900 px-3.5 py-2 text-sm text-white disabled:cursor-not-allowed disabled:bg-slate-400"
                >
                    {product.in_stock ? 'Add to cart' : 'Out of stock'}
                </button>
                <CompareButton product={product} />
            </div>
        </article>
    );
}

export function ProductGrid({ products }: { products: ProductCardData[] }) {
    return (
        <div className="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-4">
            {products.map((product) => (
                <ProductCard key={product.id} product={product} />
            ))}
        </div>
    );
}
