import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField } from '@/components/admin/form-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type Method = {
    id: number;
    type: 'shipping' | 'payment';
    code: string;
    name: string;
    description: string | null;
    is_active: boolean;
    is_test_mode: boolean;
    sort_order: number;
    cost: number;
    address: string;
    public_key: string;
    private_key: string;
};

function MethodCard({ method }: { method: Method }) {
    const form = useForm({
        name: method.name,
        description: method.description ?? '',
        is_active: method.is_active,
        is_test_mode: method.is_test_mode,
        sort_order: method.sort_order,
        cost: method.cost,
        address: method.address,
        public_key: method.public_key,
        private_key: method.private_key,
    });
    const isCash = method.type === 'payment' && method.code === 'cash';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/admin/methods/${method.id}`, { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader className="flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                    {method.name}
                    <Badge variant={method.is_active ? 'default' : 'secondary'}>{method.is_active ? 'Active' : 'Inactive'}</Badge>
                </CardTitle>
                <Button size="icon" variant="ghost" className="text-destructive" aria-label={`Delete ${method.name}`} onClick={() => confirm(`Delete "${method.name}"?`) && router.delete(`/admin/methods/${method.id}`, { preserveScroll: true })}>
                    <Trash2 />
                </Button>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
                    <FormField label="Name" htmlFor={`name-${method.id}`} error={form.errors.name}>
                        <Input id={`name-${method.id}`} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                    </FormField>
                    <FormField label="Sort order" htmlFor={`sort-${method.id}`} error={form.errors.sort_order}>
                        <Input id={`sort-${method.id}`} type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} />
                    </FormField>

                    {method.type === 'shipping' && (
                        <FormField label="Delivery cost" htmlFor={`cost-${method.id}`} error={form.errors.cost} hint="0 means free delivery.">
                            <Input id={`cost-${method.id}`} type="number" step="0.01" min="0" value={form.data.cost} onChange={(e) => form.setData('cost', Number(e.target.value))} />
                        </FormField>
                    )}
                    {method.code === 'self_pickup' && (
                        <FormField label="Pickup address" htmlFor={`address-${method.id}`} error={form.errors.address}>
                            <Input id={`address-${method.id}`} value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
                        </FormField>
                    )}
                    {method.type === 'payment' && !isCash && (
                        <>
                            <FormField label="Public key" htmlFor={`public-${method.id}`} error={form.errors.public_key}>
                                <Input id={`public-${method.id}`} value={form.data.public_key} onChange={(e) => form.setData('public_key', e.target.value)} autoComplete="off" />
                            </FormField>
                            <FormField label="Private key" htmlFor={`private-${method.id}`} error={form.errors.private_key}>
                                <Input id={`private-${method.id}`} type="password" value={form.data.private_key} onChange={(e) => form.setData('private_key', e.target.value)} autoComplete="new-password" />
                            </FormField>
                        </>
                    )}

                    <FormField label="Description" htmlFor={`description-${method.id}`} error={form.errors.description} className="md:col-span-2">
                        <Textarea id={`description-${method.id}`} rows={2} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                    </FormField>

                    <div className="flex flex-wrap items-center gap-6 md:col-span-2">
                        <CheckboxField label="Available at checkout" checked={form.data.is_active} onChange={(value) => form.setData('is_active', value)} />
                        {method.type === 'payment' && !isCash && <CheckboxField label="Test mode" checked={form.data.is_test_mode} onChange={(value) => form.setData('is_test_mode', value)} />}
                        <Button type="submit" size="sm" className="ml-auto" disabled={form.processing}>
                            Save
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function AddMethod({ type }: { type: 'shipping' | 'payment' }) {
    const form = useForm({ type, name: '' });

    return (
        <form
            onSubmit={(event: FormEvent) => {
                event.preventDefault();
                form.post('/admin/methods', { preserveScroll: true, onSuccess: () => form.reset('name') });
            }}
            className="flex flex-wrap items-start gap-2"
        >
            <div className="grid gap-1">
                <Input aria-label={`New ${type} method name`} className="w-64" placeholder="New method name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                {form.errors.name && <p className="text-sm text-destructive">{form.errors.name}</p>}
            </div>
            <Button type="submit" variant="outline" disabled={form.processing || form.data.name.trim() === ''}>
                Add {type === 'shipping' ? 'delivery' : 'payment'} method
            </Button>
        </form>
    );
}

export default function Methods({ shipping, payment }: { shipping: Method[]; payment: Method[] }) {
    return (
        <>
            <Head title="Delivery & payment" />
            <AdminContent>
                <PageHeader title="Delivery and payment" description="Methods customers can choose at checkout" />

                <section className="mb-10 grid max-w-4xl gap-4">
                    <h2 className="text-lg font-semibold">Delivery methods</h2>
                    {shipping.map((method) => (
                        <MethodCard key={method.id} method={method} />
                    ))}
                    <AddMethod type="shipping" />
                </section>

                <section className="grid max-w-4xl gap-4">
                    <h2 className="text-lg font-semibold">Payment methods</h2>
                    {payment.map((method) => (
                        <MethodCard key={method.id} method={method} />
                    ))}
                    <AddMethod type="payment" />
                </section>
            </AdminContent>
        </>
    );
}

Methods.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Delivery & payment', href: '/admin/methods' },
    ],
};
