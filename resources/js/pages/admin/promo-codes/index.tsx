import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate, formatPrice } from '@/lib/format';

type Props = {
    promoCodes: {
        id: number;
        code: string;
        type: 'percent' | 'fixed';
        value: number;
        used_count: number;
        usage_limit: number | null;
        starts_at: string | null;
        expires_at: string | null;
        is_active: boolean;
        assigned_user: string | null;
    }[];
};

export default function PromoCodesIndex({ promoCodes }: Props) {
    const { shop } = usePage().props;

    return (
        <>
            <Head title="Promo codes" />
            <AdminContent>
                <PageHeader
                    title="Promo codes"
                    description="Discount codes customers can apply in the cart"
                    actions={
                        <Button asChild>
                            <Link href="/admin/promo-codes/create">
                                <Plus /> New promo code
                            </Link>
                        </Button>
                    }
                />
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Discount</TableHead>
                                <TableHead>Used</TableHead>
                                <TableHead>Valid</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {promoCodes.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                        No promo codes yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {promoCodes.map((promo) => (
                                <TableRow key={promo.id}>
                                    <TableCell>
                                        <span className="font-mono font-semibold">{promo.code}</span>
                                        {promo.assigned_user && <p className="text-xs text-muted-foreground">Personal: {promo.assigned_user}</p>}
                                    </TableCell>
                                    <TableCell>{promo.type === 'percent' ? `${promo.value}%` : formatPrice(promo.value, shop.currency.symbol)}</TableCell>
                                    <TableCell>
                                        {promo.used_count}
                                        {promo.usage_limit !== null && ` / ${promo.usage_limit}`}
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {promo.starts_at || promo.expires_at ? `${promo.starts_at ? formatDate(promo.starts_at) : 'Now'} – ${promo.expires_at ? formatDate(promo.expires_at) : 'No end'}` : 'Always'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={promo.is_active ? 'default' : 'secondary'}>{promo.is_active ? 'Active' : 'Disabled'}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right whitespace-nowrap">
                                        <Button asChild size="sm" variant="ghost">
                                            <Link href={`/admin/promo-codes/${promo.id}/edit`}>
                                                <Pencil /> Edit
                                            </Link>
                                        </Button>
                                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => confirm(`Delete promo code ${promo.code}?`) && router.delete(`/admin/promo-codes/${promo.id}`)}>
                                            <Trash2 /> Delete
                                        </Button>
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

PromoCodesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Promo codes', href: '/admin/promo-codes' },
    ],
};
