import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField, NativeSelect } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Props = {
    promoCode: {
        id: number;
        code: string;
        type: 'percent' | 'fixed';
        value: number;
        min_order_amount: number | null;
        max_discount_amount: number | null;
        usage_limit: number | null;
        usage_limit_per_user: number | null;
        used_count: number;
        starts_at: string | null;
        expires_at: string | null;
        is_active: boolean;
        assigned_user_email: string | null;
    } | null;
};

export default function PromoCodeForm({ promoCode }: Props) {
    const form = useForm({
        code: promoCode?.code ?? '',
        type: promoCode?.type ?? 'percent',
        value: promoCode?.value?.toString() ?? '',
        min_order_amount: promoCode?.min_order_amount?.toString() ?? '',
        max_discount_amount: promoCode?.max_discount_amount?.toString() ?? '',
        usage_limit: promoCode?.usage_limit?.toString() ?? '',
        usage_limit_per_user: promoCode?.usage_limit_per_user?.toString() ?? '',
        starts_at: promoCode?.starts_at ?? '',
        expires_at: promoCode?.expires_at ?? '',
        is_active: promoCode?.is_active ?? true,
        assigned_user_email: promoCode?.assigned_user_email ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (promoCode) {
            form.put(`/admin/promo-codes/${promoCode.id}`);
        } else {
            form.post('/admin/promo-codes');
        }
    };

    const field = (key: keyof typeof form.data) => ({
        id: key,
        value: form.data[key] as string,
        onChange: (event: { target: { value: string } }) => form.setData(key, event.target.value as never),
    });

    return (
        <>
            <Head title={promoCode ? `Edit ${promoCode.code}` : 'New promo code'} />
            <AdminContent>
                <PageHeader title={promoCode ? 'Edit promo code' : 'New promo code'} description={promoCode ? `Used ${promoCode.used_count} times` : undefined} />

                <form onSubmit={submit} className="grid max-w-3xl gap-6">
                    <Card>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <FormField label="Code" htmlFor="code" error={form.errors.code} hint="Letters, numbers, dashes and underscores. Stored in upper case.">
                                <Input {...field('code')} className="font-mono uppercase" required />
                            </FormField>
                            <FormField label="Discount type" htmlFor="type" error={form.errors.type}>
                                <NativeSelect id="type" value={form.data.type} onChange={(e) => form.setData('type', e.target.value as 'percent' | 'fixed')}>
                                    <option value="percent">Percentage of the cart</option>
                                    <option value="fixed">Fixed amount</option>
                                </NativeSelect>
                            </FormField>
                            <FormField label={form.data.type === 'percent' ? 'Percent (1–100)' : 'Amount'} htmlFor="value" error={form.errors.value}>
                                <Input type="number" step="0.01" min="0" {...field('value')} required />
                            </FormField>
                            {form.data.type === 'percent' && (
                                <FormField label="Maximum discount" htmlFor="max_discount_amount" error={form.errors.max_discount_amount} hint="Optional ceiling for percentage discounts.">
                                    <Input type="number" step="0.01" min="0" {...field('max_discount_amount')} />
                                </FormField>
                            )}
                            <FormField label="Minimum order amount" htmlFor="min_order_amount" error={form.errors.min_order_amount}>
                                <Input type="number" step="0.01" min="0" {...field('min_order_amount')} />
                            </FormField>
                            <FormField label="Total usage limit" htmlFor="usage_limit" error={form.errors.usage_limit} hint="Empty means unlimited.">
                                <Input type="number" min="1" {...field('usage_limit')} />
                            </FormField>
                            <FormField label="Limit per customer" htmlFor="usage_limit_per_user" error={form.errors.usage_limit_per_user}>
                                <Input type="number" min="1" {...field('usage_limit_per_user')} />
                            </FormField>
                            <FormField label="Personal for customer (email)" htmlFor="assigned_user_email" error={form.errors.assigned_user_email} hint="Only this customer can use the code.">
                                <Input type="email" {...field('assigned_user_email')} />
                            </FormField>
                            <FormField label="Starts" htmlFor="starts_at" error={form.errors.starts_at}>
                                <Input type="datetime-local" {...field('starts_at')} />
                            </FormField>
                            <FormField label="Expires" htmlFor="expires_at" error={form.errors.expires_at}>
                                <Input type="datetime-local" {...field('expires_at')} />
                            </FormField>
                            <div className="md:col-span-2">
                                <CheckboxField label="Active" checked={form.data.is_active} onChange={(value) => form.setData('is_active', value)} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {promoCode ? 'Save changes' : 'Create promo code'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/admin/promo-codes">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </AdminContent>
        </>
    );
}

PromoCodeForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Promo codes', href: '/admin/promo-codes' },
        { title: 'Edit', href: '#' },
    ],
};
