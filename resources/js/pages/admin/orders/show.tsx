import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { FormField, NativeSelect } from '@/components/admin/form-field';
import { OrderStatusBadge } from '@/components/storefront/order-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatPrice } from '@/lib/format';

type Props = {
    order: {
        id: number;
        customer_name: string;
        customer_phone: string;
        customer_email: string;
        user: { id: number; name: string } | null;
        total: number;
        shipping_cost: number;
        discount_amount: number;
        status: string;
        status_label: string;
        delivery_method: string | null;
        payment_method: string | null;
        delivery_city: string | null;
        delivery_warehouse: string | null;
        delivery_address: string | null;
        tracking_number: string | null;
        comment: string | null;
        created_at: string;
        items: {
            id: number;
            name: string;
            sku: string | null;
            product_slug: string | null;
            options: { name: string; value: string }[];
            qty: number;
            price: number;
        }[];
        history: {
            id: number;
            old_status: string | null;
            new_status: string;
            tracking_number: string | null;
            changed_by: string | null;
            changed_at: string;
        }[];
    };
    statuses: { value: string; label: string }[];
};

export default function OrderShow({ order, statuses }: Props) {
    const { shop } = usePage().props;
    const money = (value: number) => formatPrice(value, shop.currency.symbol);
    const labelOf = (value: string | null) => statuses.find((status) => status.value === value)?.label ?? value ?? '—';
    const subtotal = order.items.reduce((sum, item) => sum + item.price * item.qty, 0);

    const statusForm = useForm({ status: order.status, tracking_number: order.tracking_number ?? '' });
    const detailsForm = useForm({
        customer_name: order.customer_name,
        customer_phone: order.customer_phone,
        customer_email: order.customer_email,
        delivery_city: order.delivery_city ?? '',
        delivery_address: order.delivery_address ?? '',
        comment: order.comment ?? '',
    });

    const saveStatus = (event: FormEvent) => {
        event.preventDefault();
        statusForm.post(`/admin/orders/${order.id}/status`, { preserveScroll: true });
    };

    const saveDetails = (event: FormEvent) => {
        event.preventDefault();
        detailsForm.put(`/admin/orders/${order.id}`, { preserveScroll: true });
    };

    const destroy = () => {
        if (confirm(`Delete order #${order.id}? This cannot be undone.`)) {
            router.delete(`/admin/orders/${order.id}`);
        }
    };

    return (
        <>
            <Head title={`Order #${order.id}`} />
            <AdminContent>
                <PageHeader
                    title={`Order #${order.id}`}
                    description={`Placed ${formatDate(order.created_at)}`}
                    actions={
                        <>
                            <OrderStatusBadge status={order.status} label={order.status_label} />
                            <Button variant="outline" className="text-destructive" onClick={destroy}>
                                <Trash2 /> Delete
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <div className="grid content-start gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Items</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-xs text-muted-foreground uppercase">
                                            <th className="py-2">Product</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th className="text-right">Sum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {order.items.map((item) => (
                                            <tr key={item.id} className="border-b">
                                                <td className="py-2">
                                                    {item.product_slug ? (
                                                        <Link href={`/product/${item.product_slug}`} className="font-medium hover:underline">
                                                            {item.name}
                                                        </Link>
                                                    ) : (
                                                        <span className="font-medium">{item.name}</span>
                                                    )}
                                                    {item.sku && <span className="ml-2 text-xs text-muted-foreground">{item.sku}</span>}
                                                    {item.options.length > 0 && (
                                                        <p className="text-xs text-muted-foreground">{item.options.map((o) => `${o.name}: ${o.value}`).join('; ')}</p>
                                                    )}
                                                </td>
                                                <td>{money(item.price)}</td>
                                                <td>{item.qty}</td>
                                                <td className="text-right">{money(item.price * item.qty)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                <dl className="mt-4 ml-auto grid max-w-xs grid-cols-2 gap-1 text-sm">
                                    <dt className="text-muted-foreground">Subtotal</dt>
                                    <dd className="text-right">{money(subtotal)}</dd>
                                    {order.discount_amount > 0 && (
                                        <>
                                            <dt className="text-muted-foreground">Discount</dt>
                                            <dd className="text-right text-green-600">-{money(order.discount_amount)}</dd>
                                        </>
                                    )}
                                    <dt className="text-muted-foreground">Shipping</dt>
                                    <dd className="text-right">{order.shipping_cost > 0 ? money(order.shipping_cost) : 'Free'}</dd>
                                    <dt className="font-semibold">Total</dt>
                                    <dd className="text-right font-bold">{money(order.total)}</dd>
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Customer and delivery</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={saveDetails} className="grid gap-4 md:grid-cols-2">
                                    <FormField label="Name" htmlFor="customer_name" error={detailsForm.errors.customer_name}>
                                        <Input id="customer_name" value={detailsForm.data.customer_name} onChange={(e) => detailsForm.setData('customer_name', e.target.value)} />
                                    </FormField>
                                    <FormField label="Phone" htmlFor="customer_phone" error={detailsForm.errors.customer_phone}>
                                        <Input id="customer_phone" value={detailsForm.data.customer_phone} onChange={(e) => detailsForm.setData('customer_phone', e.target.value)} />
                                    </FormField>
                                    <FormField label="Email" htmlFor="customer_email" error={detailsForm.errors.customer_email}>
                                        <Input id="customer_email" type="email" value={detailsForm.data.customer_email} onChange={(e) => detailsForm.setData('customer_email', e.target.value)} />
                                    </FormField>
                                    <div className="text-sm">
                                        <p className="mb-2 font-medium">Account</p>
                                        {order.user ? (
                                            <Link href={`/admin/users/${order.user.id}/edit`} className="text-blue-600 hover:underline">
                                                {order.user.name}
                                            </Link>
                                        ) : (
                                            <span className="text-muted-foreground">Guest checkout</span>
                                        )}
                                    </div>
                                    <p className="text-sm md:col-span-2">
                                        <span className="font-medium">Delivery:</span> {order.delivery_method ?? '—'} · <span className="font-medium">Payment:</span> {order.payment_method ?? '—'}
                                    </p>
                                    <FormField label="City" htmlFor="delivery_city" error={detailsForm.errors.delivery_city}>
                                        <Input id="delivery_city" value={detailsForm.data.delivery_city} onChange={(e) => detailsForm.setData('delivery_city', e.target.value)} />
                                    </FormField>
                                    <FormField label="Address" htmlFor="delivery_address" error={detailsForm.errors.delivery_address}>
                                        <Input id="delivery_address" value={detailsForm.data.delivery_address} onChange={(e) => detailsForm.setData('delivery_address', e.target.value)} />
                                    </FormField>
                                    <FormField label="Customer comment" htmlFor="comment" error={detailsForm.errors.comment} className="md:col-span-2">
                                        <Textarea id="comment" rows={3} value={detailsForm.data.comment} onChange={(e) => detailsForm.setData('comment', e.target.value)} />
                                    </FormField>
                                    <div className="md:col-span-2">
                                        <Button type="submit" disabled={detailsForm.processing}>
                                            Save details
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="grid content-start gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Status</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={saveStatus} className="grid gap-4">
                                    <FormField label="Order status" htmlFor="status" error={statusForm.errors.status}>
                                        <NativeSelect id="status" value={statusForm.data.status} onChange={(e) => statusForm.setData('status', e.target.value)}>
                                            {statuses.map((status) => (
                                                <option key={status.value} value={status.value}>
                                                    {status.label}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                    </FormField>
                                    <FormField label="Tracking number" htmlFor="tracking_number" error={statusForm.errors.tracking_number} hint="Required when the order is shipped.">
                                        <Input id="tracking_number" value={statusForm.data.tracking_number} onChange={(e) => statusForm.setData('tracking_number', e.target.value)} />
                                    </FormField>
                                    <Button type="submit" disabled={statusForm.processing}>
                                        Update status
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Status history</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ol className="grid gap-3 text-sm">
                                    {order.history.map((row) => (
                                        <li key={row.id} className="border-l-2 border-blue-200 pl-3">
                                            <p className="font-medium">
                                                {row.old_status ? `${labelOf(row.old_status)} → ` : ''}
                                                {labelOf(row.new_status)}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDate(row.changed_at)}
                                                {row.changed_by && ` · ${row.changed_by}`}
                                            </p>
                                            {row.tracking_number && <p className="text-xs">Tracking: {row.tracking_number}</p>}
                                        </li>
                                    ))}
                                </ol>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AdminContent>
        </>
    );
}

OrderShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Orders', href: '/admin/orders' },
        { title: 'Order', href: '#' },
    ],
};
