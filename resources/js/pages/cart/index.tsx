import { Head, Link, router, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { formatPrice } from '@/lib/format';

type CartLine = {
    id: number;
    product_id: number;
    name: string;
    slug: string;
    image: string | null;
    selected_options: { name: string; value: string }[];
    unit_price: number;
    quantity: number;
    stock: number;
    total_price: number;
};

type Props = {
    items: CartLine[];
    subtotal: number;
    promo: { code: string; discount: number } | null;
    total: number;
    shippingIsFree: boolean;
    continueShoppingUrl: string;
};

export default function CartPage({ items, subtotal, promo, total, shippingIsFree, continueShoppingUrl }: Props) {
    const { shop } = usePage().props;
    const money = (value: number) => formatPrice(value, shop.currency.symbol);
    const [code, setCode] = useState('');

    const updateQuantity = (item: CartLine, quantity: number) =>
        router.post('/cart/update', { cart_item_id: item.id, quantity }, { preserveScroll: true });

    const applyPromo = (event: FormEvent) => {
        event.preventDefault();
        router.post('/cart/promo', { code }, { preserveScroll: true, onSuccess: () => setCode('') });
    };

    return (
        <>
            <Head title="Cart" />
            <h1 className="mb-6 text-3xl font-bold">Cart</h1>

            {items.length === 0 ? (
                <div className="rounded-xl border border-slate-200 p-10 text-center">
                    <p className="mb-4">Your cart is empty.</p>
                    <Link href={continueShoppingUrl} className="rounded-md bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                        Continue shopping
                    </Link>
                </div>
            ) : (
                <div className="flex flex-wrap items-start gap-8">
                    <div className="min-w-0 flex-[1_1_650px]">
                        <div className="mb-2 hidden border-b border-slate-100 py-2 text-xs font-bold text-slate-400 uppercase md:flex">
                            <div className="w-20" />
                            <div className="flex-[2] pl-2.5">Product</div>
                            <div className="w-32 text-center">Options</div>
                            <div className="w-24 text-center">Price</div>
                            <div className="w-24 text-center">Quantity</div>
                            <div className="w-28 text-right">Sum</div>
                        </div>

                        {items.map((item) => (
                            <div
                                key={item.id}
                                className="relative mb-4 flex flex-wrap items-center gap-y-3 rounded-xl border border-slate-200 bg-white p-5 md:flex-nowrap"
                            >
                                <button
                                    type="button"
                                    aria-label={`Remove ${item.name}`}
                                    onClick={() => router.delete(`/cart/remove/${item.id}`, { preserveScroll: true })}
                                    className="absolute top-2.5 right-2.5 flex size-6 items-center justify-center rounded border border-red-100 text-red-400 hover:bg-red-50"
                                >
                                    <X className="size-3.5" />
                                </button>

                                <div className="w-20 shrink-0">
                                    {item.image ? (
                                        <img src={item.image} alt="" className="size-[70px] rounded-lg border border-slate-100 bg-slate-50 object-cover" />
                                    ) : (
                                        <div className="size-[70px] rounded-lg bg-slate-100" />
                                    )}
                                </div>

                                <div className="flex-[2] px-4">
                                    <Link href={`/product/${item.slug}`} className="block text-[15px] leading-tight font-bold text-slate-800">
                                        {item.name}
                                    </Link>
                                    <small className="mt-1 inline-block rounded bg-green-50 px-1.5 py-0.5 text-[11px] text-green-600">
                                        In stock: {item.stock}
                                    </small>
                                </div>

                                <div className="w-32 text-center text-xs text-slate-500">
                                    {item.selected_options.length > 0
                                        ? item.selected_options.map((option) => (
                                              <div key={option.name}>
                                                  <b>{option.name}:</b> {option.value}
                                              </div>
                                          ))
                                        : '—'}
                                </div>

                                <div className="w-24 text-center text-sm font-medium text-slate-600">{money(item.unit_price)}</div>

                                <div className="w-24 text-center">
                                    <input
                                        type="number"
                                        min={1}
                                        max={item.stock}
                                        defaultValue={item.quantity}
                                        key={`${item.id}-${item.quantity}`}
                                        onBlur={(event) => {
                                            const value = Number(event.target.value);

                                            if (value !== item.quantity) updateQuantity(item, value);
                                        }}
                                        className="w-14 rounded-md border border-slate-300 p-1 text-center font-bold"
                                        aria-label={`Quantity of ${item.name}`}
                                    />
                                </div>

                                <div className="w-28 text-right text-base font-extrabold">{money(item.total_price)}</div>
                            </div>
                        ))}

                        <div className="mt-4 flex justify-between">
                            <Link href={continueShoppingUrl} className="text-sm font-semibold text-blue-600">
                                ← Continue shopping
                            </Link>
                            <button
                                type="button"
                                onClick={() => router.delete('/cart/clear', { preserveScroll: true })}
                                className="text-sm text-red-600"
                            >
                                Clear cart
                            </button>
                        </div>
                    </div>

                    <div className="w-full flex-[0_0_350px] md:w-auto">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                            <h2 className="mb-5 text-lg font-bold">Order summary</h2>

                            <div className="mb-2.5 flex justify-between text-slate-600">
                                <span>Subtotal</span>
                                <span className="font-semibold text-slate-800">{money(subtotal)}</span>
                            </div>

                            {promo && (
                                <div className="mb-2.5 flex items-center justify-between text-green-600">
                                    <span>
                                        Promo {promo.code}{' '}
                                        <button type="button" className="text-xs text-slate-400 underline" onClick={() => router.delete('/cart/promo', { preserveScroll: true })}>
                                            remove
                                        </button>
                                    </span>
                                    <span className="font-semibold">-{money(promo.discount)}</span>
                                </div>
                            )}

                            <div className="mb-5 flex justify-between text-slate-600">
                                <span>Shipping</span>
                                {shippingIsFree ? (
                                    <span className="font-bold text-green-600">Free</span>
                                ) : (
                                    <span className="text-[13px] font-semibold text-slate-400">Varies by delivery method</span>
                                )}
                            </div>

                            {!promo && (
                                <form onSubmit={applyPromo} className="mb-5 flex gap-2">
                                    <input
                                        value={code}
                                        onChange={(event) => setCode(event.target.value)}
                                        placeholder="Promo code"
                                        className="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm uppercase"
                                    />
                                    <button type="submit" className="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
                                        Apply
                                    </button>
                                </form>
                            )}

                            <hr className="mb-5 border-slate-100" />

                            <div className="mb-6">
                                <span className="block text-xs text-slate-400 uppercase">Total</span>
                                <span className="text-3xl font-extrabold text-blue-600">{money(total)}</span>
                            </div>

                            <Link
                                href="/checkout"
                                className="block rounded-[10px] bg-blue-600 p-4 text-center text-base font-bold text-white transition hover:bg-blue-700"
                            >
                                Proceed to checkout
                            </Link>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
