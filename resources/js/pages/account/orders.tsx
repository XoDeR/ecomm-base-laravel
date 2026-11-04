import { Head, usePage } from '@inertiajs/react';
import { AccountNav } from '@/components/storefront/account-nav';
import { OrderStatusBadge } from '@/components/storefront/order-status-badge';
import { Pagination } from '@/components/storefront/pagination';
import { formatDate, formatPrice } from '@/lib/format';
import type { PaginationLink } from '@/types';

type Order = {
    id: number;
    status: string;
    status_label: string;
    total: number;
    created_at: string;
    tracking_number: string | null;
    delivery_method: string | null;
    payment_method: string | null;
    items: { id: number; name: string; qty: number; price: number; options: { name: string; value: string }[] }[];
};

type Props = { orders: { data: Order[]; links: PaginationLink[] } };

export default function Orders({ orders }: Props) {
    const { shop } = usePage().props;
    const money = (value: number) => formatPrice(value, shop.currency.symbol);

    return (
        <>
            <Head title="My orders" />
            <h1 className="mb-4 text-3xl font-bold">My orders</h1>
            <AccountNav />

            {orders.data.length === 0 ? (
                <p className="text-slate-500">You have not placed any orders yet.</p>
            ) : (
                <div className="space-y-4">
                    {orders.data.map((order) => (
                        <article key={order.id} className="rounded-xl border border-slate-200 p-5">
                            <header className="mb-3 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-semibold">Order #{order.id}</h2>
                                    <p className="text-sm text-slate-500">{formatDate(order.created_at)}</p>
                                </div>
                                <OrderStatusBadge status={order.status} label={order.status_label} />
                            </header>

                            <ul className="mb-3 divide-y divide-slate-100 text-sm">
                                {order.items.map((item) => (
                                    <li key={item.id} className="flex justify-between py-2">
                                        <span>
                                            {item.name} × {item.qty}
                                            {item.options.length > 0 && (
                                                <small className="block text-slate-500">
                                                    {item.options.map((option) => `${option.name}: ${option.value}`).join('; ')}
                                                </small>
                                            )}
                                        </span>
                                        <span>{money(item.price * item.qty)}</span>
                                    </li>
                                ))}
                            </ul>

                            <footer className="flex flex-wrap justify-between gap-2 text-sm text-slate-500">
                                <span>
                                    {order.delivery_method}
                                    {order.payment_method && ` · ${order.payment_method}`}
                                    {order.tracking_number && ` · Tracking: ${order.tracking_number}`}
                                </span>
                                <strong className="text-base text-slate-900">Total: {money(order.total)}</strong>
                            </footer>
                        </article>
                    ))}
                    <Pagination links={orders.links} />
                </div>
            )}
        </>
    );
}
