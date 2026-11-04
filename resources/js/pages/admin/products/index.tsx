import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { NativeSelect } from '@/components/admin/form-field';
import { Pagination } from '@/components/storefront/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatPrice } from '@/lib/format';
import type { PaginationLink } from '@/types';

type Props = {
    products: {
        data: {
            id: number;
            name: string;
            sku: string | null;
            slug: string;
            image: string | null;
            category: string | null;
            price: number;
            final_price: number;
            available: number;
            is_visible: boolean;
        }[];
        links: PaginationLink[];
        total: number;
    };
    filters: { q: string; category_id: number | null; visibility: 'all' | 'visible' | 'hidden' };
    categories: { id: number; name: string }[];
};

const tabs = [
    { value: 'all', label: 'All' },
    { value: 'visible', label: 'Visible' },
    { value: 'hidden', label: 'Hidden' },
] as const;

export default function ProductsIndex({ products, filters, categories }: Props) {
    const { shop } = usePage().props;
    const [query, setQuery] = useState(filters.q);

    const apply = (patch: Partial<Props['filters']>) => {
        const next = { ...filters, q: query, ...patch };

        router.get(
            '/admin/products',
            {
                ...(next.q ? { q: next.q } : {}),
                ...(next.category_id ? { category_id: next.category_id } : {}),
                ...(next.visibility !== 'all' ? { visibility: next.visibility } : {}),
            },
            { preserveState: true, replace: true },
        );
    };

    const destroy = (id: number, name: string) => {
        if (confirm(`Delete the product "${name}"? This cannot be undone.`)) {
            router.delete(`/admin/products/${id}`, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Products" />
            <AdminContent>
                <PageHeader
                    title="Products"
                    description={`${products.total} products`}
                    actions={
                        <Button asChild>
                            <Link href="/admin/products/create">
                                <Plus /> New product
                            </Link>
                        </Button>
                    }
                />

                <div className="mb-4 flex flex-wrap items-center gap-3 rounded-xl border p-3">
                    <form
                        onSubmit={(event: FormEvent) => {
                            event.preventDefault();
                            apply({});
                        }}
                        className="relative min-w-[200px] flex-1"
                    >
                        <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                        <Input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search by name, SKU or brand" className="pl-9" />
                    </form>
                    <NativeSelect
                        className="w-52"
                        value={filters.category_id ?? ''}
                        onChange={(e) => apply({ category_id: e.target.value ? Number(e.target.value) : null })}
                        aria-label="Category"
                    >
                        <option value="">All categories</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </NativeSelect>
                    <div className="flex gap-1">
                        {tabs.map((tab) => (
                            <button
                                key={tab.value}
                                type="button"
                                onClick={() => apply({ visibility: tab.value })}
                                className={`rounded-full px-3 py-1 text-sm font-medium ${filters.visibility === tab.value ? 'bg-primary text-primary-foreground' : 'bg-muted hover:bg-muted/70'}`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-14" />
                                <TableHead>Product</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Price</TableHead>
                                <TableHead>In stock</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                                        No products found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {products.data.map((product) => (
                                <TableRow key={product.id}>
                                    <TableCell>
                                        {product.image ? (
                                            <img src={product.image} alt="" className="size-10 rounded object-cover" />
                                        ) : (
                                            <span className="block size-10 rounded bg-muted" />
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Link href={`/admin/products/${product.id}/edit`} className="font-medium hover:underline">
                                            {product.name}
                                        </Link>
                                        {product.sku && <p className="text-xs text-muted-foreground">{product.sku}</p>}
                                    </TableCell>
                                    <TableCell>{product.category ?? '—'}</TableCell>
                                    <TableCell>
                                        {product.final_price < product.price ? (
                                            <>
                                                <span className="font-medium text-red-600">{formatPrice(product.final_price, shop.currency.symbol)}</span>{' '}
                                                <s className="text-xs text-muted-foreground">{formatPrice(product.price, shop.currency.symbol)}</s>
                                            </>
                                        ) : (
                                            formatPrice(product.price, shop.currency.symbol)
                                        )}
                                    </TableCell>
                                    <TableCell className={product.available <= 0 ? 'font-medium text-red-600' : ''}>{product.available}</TableCell>
                                    <TableCell>
                                        <Badge variant={product.is_visible ? 'default' : 'secondary'}>{product.is_visible ? 'Visible' : 'Hidden'}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right whitespace-nowrap">
                                        <Button asChild size="sm" variant="ghost">
                                            <Link href={`/admin/products/${product.id}/edit`}>
                                                <Pencil /> Edit
                                            </Link>
                                        </Button>
                                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => destroy(product.id, product.name)}>
                                            <Trash2 /> Delete
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <Pagination links={products.links} />
            </AdminContent>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Products', href: '/admin/products' },
    ],
};
