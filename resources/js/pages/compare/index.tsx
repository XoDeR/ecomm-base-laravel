import { Head, Link, router, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import { formatPrice } from '@/lib/format';
import type { ProductCard } from '@/types';

type Props = {
    products: ProductCard[];
    rows: { name: string; values: Record<number, string | null> }[];
    max: number;
};

export default function ComparePage({ products, rows, max }: Props) {
    const { shop } = usePage().props;

    return (
        <>
            <Head title="Compare products" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-3xl font-bold">Compare products</h1>
                {products.length > 0 && (
                    <button
                        type="button"
                        onClick={() => router.delete('/compare/clear')}
                        className="text-sm text-red-600 hover:underline"
                    >
                        Clear comparison
                    </button>
                )}
            </div>

            {products.length === 0 ? (
                <div className="rounded-xl border border-slate-200 p-10 text-center">
                    <p className="mb-4">There are no products to compare. You can compare up to {max} products.</p>
                    <Link href="/products" className="rounded-md bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                        Browse products
                    </Link>
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[640px] border-collapse text-sm">
                        <thead>
                            <tr>
                                <th className="w-40 border border-slate-200 bg-slate-50 p-3 text-left" />
                                {products.map((product) => (
                                    <th key={product.id} className="relative border border-slate-200 p-3 align-top font-normal">
                                        <button
                                            type="button"
                                            aria-label={`Remove ${product.name}`}
                                            onClick={() => router.delete(`/compare/remove/${product.id}`, { preserveScroll: true })}
                                            className="absolute top-2 right-2 text-slate-400 hover:text-red-500"
                                        >
                                            <X className="size-4" />
                                        </button>
                                        <Link href={`/product/${product.slug}`}>
                                            {product.image && (
                                                <img
                                                    src={product.image.replace('/original/', '/medium/')}
                                                    alt=""
                                                    className="mx-auto mb-2 h-32 w-full rounded-lg object-cover"
                                                />
                                            )}
                                            <span className="block font-semibold text-slate-900">{product.name}</span>
                                        </Link>
                                        <span className="mt-1 block font-bold">{formatPrice(product.final_price, shop.currency.symbol)}</span>
                                        <button
                                            type="button"
                                            disabled={!product.in_stock}
                                            onClick={() => router.post(`/cart/add/${product.id}`, { quantity: 1 }, { preserveScroll: true })}
                                            className="mt-2 rounded-md bg-slate-900 px-3 py-1.5 text-white disabled:bg-slate-400"
                                        >
                                            {product.in_stock ? 'Add to cart' : 'Out of stock'}
                                        </button>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {product_rows(rows, products)}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
}

function product_rows(rows: Props['rows'], products: ProductCard[]) {
    if (rows.length === 0) {
        return (
            <tr>
                <td colSpan={products.length + 1} className="border border-slate-200 p-4 text-center text-slate-500">
                    The selected products have no attributes to compare.
                </td>
            </tr>
        );
    }

    return rows.map((row) => {
        const unique = new Set(Object.values(row.values));
        const differs = unique.size > 1;

        return (
            <tr key={row.name} className={differs ? 'bg-amber-50/50' : ''}>
                <th className="border border-slate-200 bg-slate-50 p-3 text-left font-medium">{row.name}</th>
                {products.map((product) => (
                    <td key={product.id} className="border border-slate-200 p-3 text-center">
                        {row.values[product.id] ?? '—'}
                    </td>
                ))}
            </tr>
        );
    });
}
