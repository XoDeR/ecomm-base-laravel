import { Head, Link, usePage } from '@inertiajs/react';
import { AccountNav } from '@/components/storefront/account-nav';
import { OrderStatusBadge } from '@/components/storefront/order-status-badge';
import { formatDate, formatPrice } from '@/lib/format';
import type { User } from '@/types';

type OrderSummary = {
    id: number;
    status: string;
    status_label: string;
    total: number;
    created_at: string;
};

type Props = {
    stats: { orders: number; favorites: number; bonus: number };
    recentOrders: OrderSummary[];
};

export default function Profile({ stats, recentOrders }: Props) {
    const { auth, shop } = usePage().props;
    const user = auth.user as User;

    return (
        <>
            <Head title="My account" />
            <h1 className="mb-4 text-3xl font-bold">My account</h1>
            <AccountNav />

            <div className="mb-8 grid gap-4 md:grid-cols-3">
                <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-sm text-slate-500">Orders</p>
                    <p className="text-3xl font-bold">{stats.orders}</p>
                </div>
                <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-sm text-slate-500">Favorites</p>
                    <p className="text-3xl font-bold">{stats.favorites}</p>
                </div>
                <div className="rounded-xl border border-slate-200 p-5">
                    <p className="text-sm text-slate-500">Bonus points</p>
                    <p className="text-3xl font-bold">{stats.bonus}</p>
                </div>
            </div>

            <section className="mb-8 rounded-xl border border-slate-200 p-5">
                <h2 className="mb-3 text-lg font-semibold">Personal information</h2>
                <dl className="grid gap-2 text-sm md:grid-cols-[160px_1fr]">
                    <dt className="text-slate-500">Name</dt>
                    <dd>{user.name}</dd>
                    <dt className="text-slate-500">Email</dt>
                    <dd>{user.email}</dd>
                    <dt className="text-slate-500">Phone</dt>
                    <dd>{user.phone || '—'}</dd>
                </dl>
                <Link href="/settings/profile" className="mt-4 inline-block text-sm text-blue-600 hover:underline">
                    Edit profile
                </Link>
                {user.can_access_admin && (
                    <Link href="/admin" className="mt-4 ml-4 inline-block text-sm text-blue-600 hover:underline">
                        Open admin panel
                    </Link>
                )}
            </section>

            <section>
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-lg font-semibold">Recent orders</h2>
                    <Link href="/profile/orders" className="text-sm text-blue-600 hover:underline">
                        All orders
                    </Link>
                </div>
                {recentOrders.length === 0 ? (
                    <p className="text-slate-500">You have not placed any orders yet.</p>
                ) : (
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-left text-slate-500">
                                <th className="py-2">Order</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th className="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentOrders.map((order) => (
                                <tr key={order.id} className="border-b border-slate-100">
                                    <td className="py-2 font-medium">#{order.id}</td>
                                    <td>{formatDate(order.created_at)}</td>
                                    <td>
                                        <OrderStatusBadge status={order.status} label={order.status_label} />
                                    </td>
                                    <td className="text-right font-semibold">{formatPrice(order.total, shop.currency.symbol)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </section>
        </>
    );
}
