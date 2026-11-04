import { Head, Link, router, usePage } from '@inertiajs/react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { OrderStatusBadge } from '@/components/storefront/order-status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, formatPrice } from '@/lib/format';

type Props = {
    stats: {
        orders_today: number;
        orders_new: number;
        revenue_month: number;
        customers: number;
        products: number;
        reviews_pending: number;
    };
    period: 'week' | 'month' | 'year';
    chart: { label: string; revenue: number; orders: number }[];
    lowStock: { id: number; name: string; sku: string | null; available: number }[];
    recentOrders: { id: number; customer_name: string; status: string; status_label: string; total: number; created_at: string }[];
};

const periods: { value: Props['period']; label: string }[] = [
    { value: 'week', label: '7 days' },
    { value: 'month', label: '30 days' },
    { value: 'year', label: '12 months' },
];

export default function Dashboard({ stats, period, chart, lowStock, recentOrders }: Props) {
    const { shop } = usePage().props;
    const money = (value: number) => formatPrice(value, shop.currency.symbol);
    const max = Math.max(...chart.map((point) => point.revenue), 1);

    const tiles = [
        { label: 'Orders today', value: stats.orders_today, href: '/admin/orders' },
        { label: 'New orders', value: stats.orders_new, href: '/admin/orders?status=new' },
        { label: 'Revenue this month', value: money(stats.revenue_month), href: '/admin/orders' },
        { label: 'Customers', value: stats.customers, href: '/admin/users' },
        { label: 'Products', value: stats.products, href: '/admin/products' },
        { label: 'Reviews awaiting moderation', value: stats.reviews_pending, href: '/admin/reviews?visibility=hidden' },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <AdminContent>
                <PageHeader title="Dashboard" description="Store activity at a glance" />

                <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {tiles.map((tile) => (
                        <Link key={tile.label} href={tile.href}>
                            <Card className="gap-2 py-4 transition hover:shadow-md">
                                <CardHeader className="pb-0">
                                    <p className="text-sm text-muted-foreground">{tile.label}</p>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-3xl font-bold">{tile.value}</p>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                <Card className="mb-6">
                    <CardHeader className="flex-row items-center justify-between">
                        <CardTitle>Sales</CardTitle>
                        <div className="flex gap-1">
                            {periods.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => router.get('/admin', { period: option.value }, { preserveScroll: true, preserveState: true })}
                                    className={`rounded-md px-3 py-1 text-sm ${period === option.value ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="flex h-48 items-end gap-1" role="img" aria-label="Revenue chart">
                            {chart.map((point) => (
                                <div key={point.label} className="group relative flex h-full flex-1 items-end">
                                    <div
                                        className="w-full rounded-t bg-blue-500 transition group-hover:bg-blue-600"
                                        style={{ height: `${(point.revenue / max) * 100}%`, minHeight: point.revenue > 0 ? 2 : 0 }}
                                    />
                                    <span className="pointer-events-none absolute -top-9 left-1/2 z-10 hidden -translate-x-1/2 rounded bg-foreground px-2 py-1 text-xs whitespace-nowrap text-background group-hover:block">
                                        {point.label}: {money(point.revenue)} ({point.orders})
                                    </span>
                                </div>
                            ))}
                        </div>
                        <div className="mt-2 flex justify-between text-xs text-muted-foreground">
                            <span>{chart[0]?.label}</span>
                            <span>{chart[chart.length - 1]?.label}</span>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent orders</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentOrders.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No orders yet.</p>
                            ) : (
                                <ul className="divide-y">
                                    {recentOrders.map((order) => (
                                        <li key={order.id} className="flex items-center justify-between gap-3 py-2 text-sm">
                                            <div>
                                                <Link href={`/admin/orders/${order.id}`} className="font-medium hover:underline">
                                                    #{order.id} · {order.customer_name}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">{formatDate(order.created_at)}</p>
                                            </div>
                                            <OrderStatusBadge status={order.status} label={order.status_label} />
                                            <strong>{money(order.total)}</strong>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Low stock</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {lowStock.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Every product is well stocked.</p>
                            ) : (
                                <ul className="divide-y">
                                    {lowStock.map((item) => (
                                        <li key={item.id} className="flex items-center justify-between gap-3 py-2 text-sm">
                                            <Link href={`/admin/products/${item.id}/edit`} className="hover:underline">
                                                {item.name}
                                                {item.sku && <span className="ml-2 text-xs text-muted-foreground">{item.sku}</span>}
                                            </Link>
                                            <span className={item.available <= 0 ? 'font-semibold text-red-600' : 'text-amber-600'}>
                                                {item.available} left
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AdminContent>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: '/admin' }],
};
