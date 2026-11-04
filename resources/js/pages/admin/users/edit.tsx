import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField, NativeSelect } from '@/components/admin/form-field';
import { OrderStatusBadge } from '@/components/storefront/order-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatPrice } from '@/lib/format';

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        role_id: number | null;
        is_active: boolean;
        last_login_at: string | null;
        created_at: string;
        email_verified: boolean;
        bonus: number;
        marketing_email: boolean;
    };
    roles: { id: number; name: string }[];
    orders: { id: number; status: string; status_label: string; total: number; created_at: string }[];
    cart: { id: number; name: string | null; quantity: number }[];
    activity: { id: number; event_type: string; description: string; created_at: string }[];
    audits: { id: number; action_type: string; reason: string; old_value: string | null; new_value: string | null; admin: string | null; created_at: string }[];
    logins: { id: number; ip_address: string | null; user_agent: string | null; login_time: string }[];
};

export default function UserEdit({ user, roles, orders, cart, activity, audits, logins }: Props) {
    const { shop } = usePage().props;
    const base = `/admin/users/${user.id}`;

    const profile = useForm({ name: user.name, email: user.email, phone: user.phone ?? '', role_id: user.role_id ?? '' });
    const bonus = useForm({ balance: user.bonus, reason: '' });
    const block = useForm({ reason: '' });

    const saveProfile = (event: FormEvent) => {
        event.preventDefault();
        profile.put(base, { preserveScroll: true });
    };

    const saveBonus = (event: FormEvent) => {
        event.preventDefault();
        bonus.post(`${base}/bonus`, { preserveScroll: true, onSuccess: () => bonus.setData('reason', '') });
    };

    const saveBlock = (event: FormEvent) => {
        event.preventDefault();
        // The action toggles the account: blocking an active user, unblocking a blocked one
        block.transform((data) => ({ ...data, is_active: !user.is_active }));
        block.post(`${base}/block`, { preserveScroll: true, onSuccess: () => block.reset('reason') });
    };

    const destroy = () => {
        if (confirm(`Delete the account of ${user.name}? Their orders will be kept without an account.`)) {
            router.delete(base);
        }
    };

    return (
        <>
            <Head title={user.name} />
            <AdminContent>
                <PageHeader
                    title={user.name}
                    description={`Customer #${user.id} · registered ${formatDate(user.created_at)}`}
                    actions={
                        <>
                            <Badge variant={user.is_active ? 'default' : 'destructive'}>{user.is_active ? 'Active' : 'Blocked'}</Badge>
                            <Button variant="outline" className="text-destructive" onClick={destroy}>
                                Delete account
                            </Button>
                        </>
                    }
                />

                <div className="grid gap-6 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={saveProfile} className="grid gap-4 md:grid-cols-2">
                                <FormField label="Name" htmlFor="name" error={profile.errors.name}>
                                    <Input id="name" value={profile.data.name} onChange={(e) => profile.setData('name', e.target.value)} />
                                </FormField>
                                <FormField label="Email" htmlFor="email" error={profile.errors.email} hint={user.email_verified ? 'Verified' : 'Not verified'}>
                                    <Input id="email" type="email" value={profile.data.email} onChange={(e) => profile.setData('email', e.target.value)} />
                                </FormField>
                                <FormField label="Phone" htmlFor="phone" error={profile.errors.phone}>
                                    <Input id="phone" value={profile.data.phone} onChange={(e) => profile.setData('phone', e.target.value)} />
                                </FormField>
                                <FormField label="Role" htmlFor="role_id" error={profile.errors.role_id}>
                                    <NativeSelect id="role_id" value={profile.data.role_id} onChange={(e) => profile.setData('role_id', Number(e.target.value))}>
                                        {roles.map((role) => (
                                            <option key={role.id} value={role.id}>
                                                {role.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                                <div className="md:col-span-2">
                                    <Button type="submit" disabled={profile.processing}>
                                        Save profile
                                    </Button>
                                </div>
                            </form>
                            <div className="mt-4 border-t pt-4">
                                <CheckboxField
                                    label="Subscribed to marketing emails"
                                    checked={user.marketing_email}
                                    onChange={(checked) => router.post(`${base}/subscription`, { marketing_email: checked }, { preserveScroll: true })}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid content-start gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Bonus points</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={saveBonus} className="grid gap-4">
                                    <FormField label="Balance" htmlFor="balance" error={bonus.errors.balance}>
                                        <Input id="balance" type="number" min="0" value={bonus.data.balance} onChange={(e) => bonus.setData('balance', Number(e.target.value))} />
                                    </FormField>
                                    <FormField label="Reason" htmlFor="bonus_reason" error={bonus.errors.reason} hint="Recorded in the audit log.">
                                        <Input id="bonus_reason" value={bonus.data.reason} onChange={(e) => bonus.setData('reason', e.target.value)} />
                                    </FormField>
                                    <div>
                                        <Button type="submit" disabled={bonus.processing}>
                                            Update balance
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{user.is_active ? 'Block account' : 'Unblock account'}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={saveBlock} className="grid gap-4">
                                    <FormField label="Reason" htmlFor="block_reason" error={block.errors.reason}>
                                        <Textarea id="block_reason" rows={2} value={block.data.reason} onChange={(e) => block.setData('reason', e.target.value)} />
                                    </FormField>
                                    <div>
                                        <Button type="submit" variant={user.is_active ? 'destructive' : 'default'} disabled={block.processing}>
                                            {user.is_active ? 'Block' : 'Unblock'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent orders</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {orders.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No orders yet.</p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {orders.map((order) => (
                                        <li key={order.id} className="flex items-center justify-between gap-3 py-2">
                                            <Link href={`/admin/orders/${order.id}`} className="font-medium hover:underline">
                                                #{order.id}
                                            </Link>
                                            <span className="text-muted-foreground">{formatDate(order.created_at)}</span>
                                            <OrderStatusBadge status={order.status} label={order.status_label} />
                                            <strong>{formatPrice(order.total, shop.currency.symbol)}</strong>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Current cart</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {cart.length === 0 ? (
                                <p className="text-sm text-muted-foreground">The cart is empty.</p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {cart.map((item) => (
                                        <li key={item.id} className="flex justify-between py-2">
                                            <span>{item.name}</span>
                                            <span>× {item.quantity}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle>Activity</CardTitle>
                            {activity.length > 0 && (
                                <Button size="sm" variant="outline" onClick={() => confirm('Clear the activity log?') && router.post(`${base}/activity-log/clear`, {}, { preserveScroll: true })}>
                                    Clear
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent>
                            {activity.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No recorded activity.</p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {activity.map((entry) => (
                                        <li key={entry.id} className="py-2">
                                            {entry.description}
                                            <p className="text-xs text-muted-foreground">{formatDate(entry.created_at)}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Audit log</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {audits.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No administrator actions yet.</p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {audits.map((audit) => (
                                        <li key={audit.id} className="py-2">
                                            <p>
                                                <strong className="capitalize">{audit.action_type}</strong>
                                                {audit.old_value !== null && ` · ${audit.old_value} → ${audit.new_value}`}
                                            </p>
                                            <p className="text-muted-foreground">{audit.reason}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {audit.admin} · {formatDate(audit.created_at)}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Login history</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {logins.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No logins recorded.</p>
                            ) : (
                                <ul className="divide-y text-sm">
                                    {logins.map((login) => (
                                        <li key={login.id} className="py-2">
                                            {formatDate(login.login_time)} · {login.ip_address}
                                            <p className="truncate text-xs text-muted-foreground">{login.user_agent}</p>
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

UserEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Customers', href: '/admin/users' },
        { title: 'Customer', href: '#' },
    ],
};
