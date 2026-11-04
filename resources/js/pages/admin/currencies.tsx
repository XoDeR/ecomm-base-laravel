import { Head, router, useForm } from '@inertiajs/react';
import { Check, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { FormField } from '@/components/admin/form-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Currency = { id: number; code: string; symbol: string; rate: string; is_active: boolean };

function CurrencyRow({ currency }: { currency: Currency }) {
    const [symbol, setSymbol] = useState(currency.symbol);
    const [rate, setRate] = useState(currency.rate);
    const dirty = symbol !== currency.symbol || Number(rate) !== Number(currency.rate);

    return (
        <TableRow>
            <TableCell className="font-medium">
                {currency.code}{' '}
                {currency.is_active && <Badge className="ml-1">Store currency</Badge>}
            </TableCell>
            <TableCell>
                <Input className="w-20" aria-label={`${currency.code} symbol`} value={symbol} onChange={(e) => setSymbol(e.target.value)} />
            </TableCell>
            <TableCell>
                <Input className="w-32" type="number" step="0.0001" min="0" aria-label={`${currency.code} rate`} value={rate} onChange={(e) => setRate(e.target.value)} />
            </TableCell>
            <TableCell className="text-right whitespace-nowrap">
                {dirty && (
                    <Button size="sm" onClick={() => router.put(`/admin/currencies/${currency.id}`, { symbol, rate }, { preserveScroll: true })}>
                        Save
                    </Button>
                )}
                {!currency.is_active && (
                    <>
                        <Button size="sm" variant="ghost" onClick={() => router.post(`/admin/currencies/${currency.id}/activate`, {}, { preserveScroll: true })}>
                            <Check /> Use in store
                        </Button>
                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => confirm(`Delete ${currency.code}?`) && router.delete(`/admin/currencies/${currency.id}`, { preserveScroll: true })}>
                            <Trash2 /> Delete
                        </Button>
                    </>
                )}
            </TableCell>
        </TableRow>
    );
}

export default function Currencies({ currencies }: { currencies: Currency[] }) {
    const form = useForm({ code: '', symbol: '', rate: '1' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/currencies', { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <>
            <Head title="Currencies" />
            <AdminContent>
                <PageHeader title="Currencies" description="Prices are entered in the store currency. Rates are relative to it (the store currency has a rate of 1)." />

                <div className="mb-8 max-w-3xl rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Currency</TableHead>
                                <TableHead>Symbol</TableHead>
                                <TableHead>Rate</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {currencies.map((currency) => (
                                <CurrencyRow key={`${currency.id}-${currency.symbol}-${currency.rate}`} currency={currency} />
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <Card className="max-w-3xl">
                    <CardHeader>
                        <CardTitle>Add currency</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid items-start gap-4 md:grid-cols-4">
                            <FormField label="Code" htmlFor="code" error={form.errors.code} hint="Three letters, e.g. EUR">
                                <Input id="code" maxLength={3} value={form.data.code} onChange={(e) => form.setData('code', e.target.value.toUpperCase())} required />
                            </FormField>
                            <FormField label="Symbol" htmlFor="symbol" error={form.errors.symbol}>
                                <Input id="symbol" value={form.data.symbol} onChange={(e) => form.setData('symbol', e.target.value)} required />
                            </FormField>
                            <FormField label="Rate" htmlFor="rate" error={form.errors.rate}>
                                <Input id="rate" type="number" step="0.0001" min="0" value={form.data.rate} onChange={(e) => form.setData('rate', e.target.value)} required />
                            </FormField>
                            <div className="flex items-end md:pt-6">
                                <Button type="submit" disabled={form.processing}>
                                    Add
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </AdminContent>
        </>
    );
}

Currencies.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Currencies', href: '/admin/currencies' },
    ],
};
