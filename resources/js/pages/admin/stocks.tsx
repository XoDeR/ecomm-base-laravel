import { Head, router, useForm } from '@inertiajs/react';
import { Fragment, useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, NativeSelect } from '@/components/admin/form-field';
import { Pagination } from '@/components/storefront/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import type { PaginationLink } from '@/types';

type Variant = { id: number; label: string; quantity: number; reserved: number };

type Props = {
    products: {
        data: { id: number; name: string; sku: string | null; quantity: number; reserved: number; variants: Variant[] }[];
        links: PaginationLink[];
        total: number;
    };
    filters: { search: string; low: boolean };
    log: { id: number; product: string | null; event_type: string; qty: number; comment: string | null; created_at: string }[];
};

function AdjustForm({ productId, variantId }: { productId: number; variantId?: number }) {
    const form = useForm({ product_id: productId, variant_id: variantId ?? null, action: 'receipt', quantity: 1, comment: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/stocks/adjust', { preserveScroll: true, onSuccess: () => form.reset('comment') });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-center gap-2">
            <NativeSelect className="w-36" aria-label="Action" value={form.data.action} onChange={(e) => form.setData('action', e.target.value)}>
                <option value="receipt">Receive</option>
                <option value="write_off">Write off</option>
                <option value="set">Set to</option>
            </NativeSelect>
            <Input className="w-24" type="number" min="0" aria-label="Quantity" value={form.data.quantity} onChange={(e) => form.setData('quantity', Number(e.target.value))} />
            <Input className="w-44" aria-label="Comment" placeholder="Comment (optional)" value={form.data.comment} onChange={(e) => form.setData('comment', e.target.value)} />
            <Button type="submit" size="sm" disabled={form.processing}>
                Apply
            </Button>
            {Object.values(form.errors)[0] && <span className="text-sm text-destructive">{Object.values(form.errors)[0]}</span>}
        </form>
    );
}

export default function Stocks({ products, filters, log }: Props) {
    const [search, setSearch] = useState(filters.search);

    const apply = (patch: Partial<Props['filters']>) => {
        const next = { ...filters, search, ...patch };

        router.get('/admin/stocks', { ...(next.search ? { search: next.search } : {}), ...(next.low ? { low: 1 } : {}) }, { preserveState: true, replace: true });
    };

    return (
        <>
            <Head title="Stock" />
            <AdminContent>
                <PageHeader title="Stock" description="Receive, write off and count products. Reserved items belong to open orders." />

                <div className="mb-4 flex flex-wrap items-center gap-4 rounded-xl border p-3">
                    <form
                        onSubmit={(event: FormEvent) => {
                            event.preventDefault();
                            apply({});
                        }}
                        className="min-w-[200px] flex-1"
                    >
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by name or SKU" />
                    </form>
                    <CheckboxField label="Low stock only" checked={filters.low} onChange={(low) => apply({ low })} />
                </div>

                <div className="mb-10 rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead>On hand</TableHead>
                                <TableHead>Reserved</TableHead>
                                <TableHead>Available</TableHead>
                                <TableHead>Adjust</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                        No products found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {products.data.map((product) => (
                                <Fragment key={product.id}>
                                    <TableRow>
                                        <TableCell>
                                            <span className="font-medium">{product.name}</span>
                                            {product.sku && <p className="text-xs text-muted-foreground">{product.sku}</p>}
                                        </TableCell>
                                        <TableCell>{product.quantity}</TableCell>
                                        <TableCell>{product.reserved}</TableCell>
                                        <TableCell className={product.quantity - product.reserved <= 0 ? 'font-semibold text-red-600' : product.quantity - product.reserved <= 5 ? 'text-amber-600' : ''}>
                                            {product.quantity - product.reserved}
                                        </TableCell>
                                        <TableCell>
                                            <AdjustForm productId={product.id} />
                                        </TableCell>
                                    </TableRow>
                                    {product.variants.map((variant) => (
                                        <TableRow key={`${product.id}-${variant.id}`} className="bg-muted/30">
                                            <TableCell className="pl-8 text-sm text-muted-foreground">↳ {variant.label}</TableCell>
                                            <TableCell>{variant.quantity}</TableCell>
                                            <TableCell>{variant.reserved}</TableCell>
                                            <TableCell>{variant.quantity - variant.reserved}</TableCell>
                                            <TableCell>
                                                <AdjustForm productId={product.id} variantId={variant.id} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </Fragment>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                <Pagination links={products.links} />

                <h2 className="mb-3 text-lg font-semibold">Recent stock movements</h2>
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Quantity</TableHead>
                                <TableHead>Comment</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {log.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                        No stock movements yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {log.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell>{formatDate(entry.created_at)}</TableCell>
                                    <TableCell>{entry.product}</TableCell>
                                    <TableCell className="capitalize">{entry.event_type}</TableCell>
                                    <TableCell>{entry.qty}</TableCell>
                                    <TableCell className="text-muted-foreground">{entry.comment}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </AdminContent>
        </>
    );
}

Stocks.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Stock', href: '/admin/stocks' },
    ],
};
