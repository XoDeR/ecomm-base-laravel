import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { NativeSelect } from '@/components/admin/form-field';
import { OrderStatusBadge } from '@/components/storefront/order-status-badge';
import { Pagination } from '@/components/storefront/pagination';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate, formatPrice } from '@/lib/format';
import type { PaginationLink } from '@/types';

type OrderSummary = {
    id: number;
    customer_name: string;
    customer_phone: string;
    total: number;
    status: string;
    status_label: string;
    delivery_method: string | null;
    payment_method: string | null;
    created_at: string;
};

type Props = {
    table: { data: OrderSummary[]; links: PaginationLink[]; total: number };
    board: { status: string; label: string; orders: OrderSummary[]; total: number }[];
    statuses: { value: string; label: string }[];
    filters: { status: string; search: string; view: 'board' | 'table' };
};

export default function OrdersIndex({ table, board, statuses, filters }: Props) {
    const { shop } = usePage().props;
    const money = (value: number) => formatPrice(value, shop.currency.symbol);
    const [search, setSearch] = useState(filters.search);
    const [dragOver, setDragOver] = useState<string | null>(null);

    const visit = (patch: Partial<Props['filters']>) => {
        const next = { ...filters, search, ...patch };

        router.get(
            '/admin/orders',
            {
                view: next.view,
                ...(next.status ? { status: next.status } : {}),
                ...(next.search ? { search: next.search } : {}),
            },
            { preserveState: true, replace: true },
        );
    };

    const moveOrder = (orderId: number, status: string) => {
        let tracking_number: string | null = null;

        if (status === 'shipped') {
            tracking_number = prompt('Enter the tracking number for this shipment:');

            if (!tracking_number) {
                return;
            }
        }

        router.post(`/admin/orders/${orderId}/status`, { status, tracking_number }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Orders" />
            <AdminContent>
                <PageHeader
                    title="Orders"
                    actions={
                        <div className="inline-flex rounded-lg border p-1">
                            {(['board', 'table'] as const).map((view) => (
                                <button
                                    key={view}
                                    type="button"
                                    onClick={() => visit({ view })}
                                    className={`rounded-md px-4 py-1.5 text-sm font-semibold ${filters.view === view ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}
                                >
                                    {view === 'board' ? 'Board' : 'Table'}
                                </button>
                            ))}
                        </div>
                    }
                />

                {filters.view === 'board' ? (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {board.map((column) => (
                            <section
                                key={column.status}
                                onDragOver={(event) => {
                                    event.preventDefault();
                                    setDragOver(column.status);
                                }}
                                onDragLeave={() => setDragOver(null)}
                                onDrop={(event) => {
                                    event.preventDefault();
                                    setDragOver(null);
                                    const id = Number(event.dataTransfer.getData('text/plain'));
                                    const from = board.find((c) => c.orders.some((o) => o.id === id))?.status;

                                    if (id && from !== column.status) moveOrder(id, column.status);
                                }}
                                className={`rounded-xl border bg-card ${dragOver === column.status ? 'border-primary ring-1 ring-primary' : ''}`}
                            >
                                <header className="flex items-center justify-between border-b px-3.5 py-2.5 text-sm font-semibold">
                                    {column.label}
                                    <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">{column.total}</span>
                                </header>
                                <div className="grid min-h-[200px] content-start gap-2.5 p-2.5">
                                    {column.orders.map((order) => (
                                        <article
                                            key={order.id}
                                            draggable
                                            onDragStart={(event) => event.dataTransfer.setData('text/plain', String(order.id))}
                                            className="cursor-grab rounded-lg border bg-background p-3 text-sm shadow-xs active:cursor-grabbing"
                                        >
                                            <div className="mb-1 flex items-center justify-between">
                                                <Link href={`/admin/orders/${order.id}`} className="font-semibold hover:underline">
                                                    #{order.id}
                                                </Link>
                                                <strong>{money(order.total)}</strong>
                                            </div>
                                            <p>{order.customer_name}</p>
                                            <p className="text-xs text-muted-foreground">{order.customer_phone}</p>
                                            <p className="mt-1 text-xs text-muted-foreground">{formatDate(order.created_at)}</p>
                                        </article>
                                    ))}
                                    {column.orders.length === 0 && <p className="py-6 text-center text-sm text-muted-foreground">No orders</p>}
                                </div>
                            </section>
                        ))}
                    </div>
                ) : (
                    <>
                        <div className="mb-4 flex flex-wrap items-center gap-3 rounded-xl border p-3">
                            <form
                                onSubmit={(event: FormEvent) => {
                                    event.preventDefault();
                                    visit({});
                                }}
                                className="min-w-[200px] flex-1"
                            >
                                <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by order #, name, phone or email" />
                            </form>
                            <NativeSelect className="w-48" value={filters.status} onChange={(e) => visit({ status: e.target.value })} aria-label="Status">
                                <option value="">All statuses</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>

                        <div className="rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Order</TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Delivery / payment</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {table.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                                No orders found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {table.data.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell>
                                                <Link href={`/admin/orders/${order.id}`} className="font-medium hover:underline">
                                                    #{order.id}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                {order.customer_name}
                                                <p className="text-xs text-muted-foreground">{order.customer_phone}</p>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {order.delivery_method}
                                                <p className="text-xs text-muted-foreground">{order.payment_method}</p>
                                            </TableCell>
                                            <TableCell>
                                                <OrderStatusBadge status={order.status} label={order.status_label} />
                                            </TableCell>
                                            <TableCell>{formatDate(order.created_at)}</TableCell>
                                            <TableCell className="text-right font-semibold">{money(order.total)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <Pagination links={table.links} />
                    </>
                )}
            </AdminContent>
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Orders', href: '/admin/orders' },
    ],
};
