import { Head, router, useForm } from '@inertiajs/react';
import { Undo2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField, NativeSelect } from '@/components/admin/form-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/format';

type Props = {
    categories: { id: number; name: string }[];
    vendors: string[];
    currencies: { code: string; symbol: string; rate: string; is_active: boolean }[];
    history: {
        id: number;
        mode: 'percent' | 'fixed' | 'currency';
        params: { value?: number; direction?: string; from_currency?: string | null; round_price?: boolean } | null;
        category: string | null;
        include_subcategories: boolean;
        vendor: string | null;
        affected_count: number;
        skipped_count: number;
        admin: string | null;
        created_at: string;
        reverted_at: string | null;
    }[];
};

function describe(batch: Props['history'][number]): string {
    const params = batch.params ?? {};

    if (batch.mode === 'currency') {
        return `Convert from ${params.from_currency}`;
    }

    const sign = params.direction === 'decrease' ? '−' : '+';

    return batch.mode === 'percent' ? `${sign}${params.value}%` : `${sign}${params.value} (flat)`;
}

export default function PriceBatches({ categories, vendors, currencies, history }: Props) {
    const form = useForm({
        mode: 'percent' as 'percent' | 'fixed' | 'currency',
        value: '',
        direction: 'increase',
        from_currency: currencies.find((currency) => !currency.is_active)?.code ?? '',
        round_price: false,
        category_id: '',
        include_subcategories: true,
        vendor: '',
    });
    const [count, setCount] = useState<number | null>(null);

    useEffect(() => {
        const params = new URLSearchParams();

        if (form.data.category_id) params.set('category_id', form.data.category_id);
        if (form.data.include_subcategories) params.set('include_subcategories', '1');
        if (form.data.vendor) params.set('vendor', form.data.vendor);

        const controller = new AbortController();

        fetch(`/admin/price-batches/preview?${params}`, { signal: controller.signal, headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data: { count: number }) => setCount(data.count))
            .catch(() => undefined);

        return () => controller.abort();
    }, [form.data.category_id, form.data.include_subcategories, form.data.vendor]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!confirm(`Change prices of ${count ?? 'the selected'} products? You can undo this later.`)) {
            return;
        }

        form.post('/admin/price-batches', { preserveScroll: true, onSuccess: () => form.setData('value', '') });
    };

    const undo = (id: number) => {
        if (confirm('Restore the previous prices for this batch?')) {
            router.post(`/admin/price-batches/${id}/undo`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Price changes" />
            <AdminContent>
                <PageHeader title="Bulk price changes" description="Change many prices at once and undo it if needed" />

                <Card className="mb-8 max-w-4xl">
                    <CardHeader>
                        <CardTitle>New price change</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
                            <FormField label="Mode" htmlFor="mode" error={form.errors.mode}>
                                <NativeSelect id="mode" value={form.data.mode} onChange={(e) => form.setData('mode', e.target.value as typeof form.data.mode)}>
                                    <option value="percent">Change by a percentage</option>
                                    <option value="fixed">Change by a fixed amount</option>
                                    <option value="currency">Convert from another currency</option>
                                </NativeSelect>
                            </FormField>

                            {form.data.mode === 'currency' ? (
                                <FormField label="Prices are currently in" htmlFor="from_currency" error={form.errors.from_currency}>
                                    <NativeSelect id="from_currency" value={form.data.from_currency} onChange={(e) => form.setData('from_currency', e.target.value)}>
                                        {currencies.map((currency) => (
                                            <option key={currency.code} value={currency.code}>
                                                {currency.code} ({currency.symbol}) — rate {currency.rate}
                                                {currency.is_active ? ' · store currency' : ''}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            ) : (
                                <div className="grid grid-cols-2 gap-3">
                                    <FormField label={form.data.mode === 'percent' ? 'Percent' : 'Amount'} htmlFor="value" error={form.errors.value}>
                                        <Input id="value" type="number" step="0.01" min="0" value={form.data.value} onChange={(e) => form.setData('value', e.target.value)} />
                                    </FormField>
                                    <FormField label="Direction" htmlFor="direction">
                                        <NativeSelect id="direction" value={form.data.direction} onChange={(e) => form.setData('direction', e.target.value)}>
                                            <option value="increase">Increase</option>
                                            <option value="decrease">Decrease</option>
                                        </NativeSelect>
                                    </FormField>
                                </div>
                            )}

                            <FormField label="Category" htmlFor="category_id" error={form.errors.category_id}>
                                <NativeSelect id="category_id" value={form.data.category_id} onChange={(e) => form.setData('category_id', e.target.value)}>
                                    <option value="">All categories</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <FormField label="Brand" htmlFor="vendor">
                                <NativeSelect id="vendor" value={form.data.vendor} onChange={(e) => form.setData('vendor', e.target.value)}>
                                    <option value="">All brands</option>
                                    {vendors.map((vendor) => (
                                        <option key={vendor} value={vendor}>
                                            {vendor}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>

                            <div className="grid gap-2 md:col-span-2">
                                <CheckboxField label="Include subcategories" checked={form.data.include_subcategories} onChange={(value) => form.setData('include_subcategories', value)} />
                                <CheckboxField label="Round prices to whole numbers" checked={form.data.round_price} onChange={(value) => form.setData('round_price', value)} />
                            </div>

                            <div className="flex items-center gap-4 md:col-span-2">
                                <Button type="submit" disabled={form.processing || count === 0}>
                                    Apply
                                </Button>
                                <span className="text-sm text-muted-foreground">{count === null ? '' : `${count} products match this selection`}</span>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <h2 className="mb-3 text-lg font-semibold">History</h2>
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Change</TableHead>
                                <TableHead>Scope</TableHead>
                                <TableHead>Products</TableHead>
                                <TableHead>By</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {history.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                        No price changes yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {history.map((batch) => (
                                <TableRow key={batch.id}>
                                    <TableCell>{formatDate(batch.created_at)}</TableCell>
                                    <TableCell>{describe(batch)}</TableCell>
                                    <TableCell className="text-sm">
                                        {batch.category ?? 'All categories'}
                                        {batch.category && batch.include_subcategories && ' + subcategories'}
                                        {batch.vendor && ` · ${batch.vendor}`}
                                    </TableCell>
                                    <TableCell>
                                        {batch.affected_count}
                                        {batch.skipped_count > 0 && <span className="text-muted-foreground"> ({batch.skipped_count} skipped)</span>}
                                    </TableCell>
                                    <TableCell>{batch.admin ?? '—'}</TableCell>
                                    <TableCell className="text-right">
                                        {batch.reverted_at ? (
                                            <Badge variant="secondary">Reverted {formatDate(batch.reverted_at)}</Badge>
                                        ) : (
                                            <Button size="sm" variant="outline" onClick={() => undo(batch.id)}>
                                                <Undo2 /> Undo
                                            </Button>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </AdminContent>
        </>
    );
}

PriceBatches.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Price changes', href: '/admin/price-batches' },
    ],
};
