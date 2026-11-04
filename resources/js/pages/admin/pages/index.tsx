import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Pencil, Plus, Trash2 } from 'lucide-react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/format';

type Props = {
    pages: { id: number; title: string; slug: string; is_active: boolean; sort_order: number; updated_at: string }[];
};

export default function PagesIndex({ pages }: Props) {
    const destroy = (id: number, title: string) => {
        if (confirm(`Delete the page "${title}"?`)) {
            router.delete(`/admin/pages/${id}`);
        }
    };

    return (
        <>
            <Head title="Pages" />
            <AdminContent>
                <PageHeader
                    title="Pages"
                    description="Static content such as About us or Delivery"
                    actions={
                        <Button asChild>
                            <Link href="/admin/pages/create">
                                <Plus /> New page
                            </Link>
                        </Button>
                    }
                />
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>URL</TableHead>
                                <TableHead>Order</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Updated</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pages.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                        No pages yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {pages.map((page) => (
                                <TableRow key={page.id}>
                                    <TableCell className="font-medium">{page.title}</TableCell>
                                    <TableCell className="text-muted-foreground">/{page.slug}</TableCell>
                                    <TableCell>{page.sort_order}</TableCell>
                                    <TableCell>
                                        <Badge variant={page.is_active ? 'default' : 'secondary'}>{page.is_active ? 'Published' : 'Draft'}</Badge>
                                    </TableCell>
                                    <TableCell>{formatDate(page.updated_at)}</TableCell>
                                    <TableCell className="text-right whitespace-nowrap">
                                        <Button asChild size="sm" variant="ghost">
                                            <a href={`/${page.slug}`} target="_blank" rel="noreferrer">
                                                <ExternalLink /> View
                                            </a>
                                        </Button>
                                        <Button asChild size="sm" variant="ghost">
                                            <Link href={`/admin/pages/${page.id}/edit`}>
                                                <Pencil /> Edit
                                            </Link>
                                        </Button>
                                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => destroy(page.id, page.title)}>
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

PagesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Pages', href: '/admin/pages' },
    ],
};
