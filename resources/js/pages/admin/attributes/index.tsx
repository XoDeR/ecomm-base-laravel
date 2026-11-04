import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Props = {
    attributes: {
        id: number;
        name: string;
        slug: string;
        type: string;
        is_filterable: boolean;
        is_visible: boolean;
        options_count: number;
        products_count: number;
    }[];
};

export default function AttributesIndex({ attributes }: Props) {
    const destroy = (id: number, name: string) => {
        if (confirm(`Delete the attribute "${name}"? Its values will be removed from all products.`)) {
            router.delete(`/admin/attributes/${id}`);
        }
    };

    return (
        <>
            <Head title="Attributes" />
            <AdminContent>
                <PageHeader
                    title="Attributes"
                    description="Product characteristics such as color, size or brand"
                    actions={
                        <Button asChild>
                            <Link href="/admin/attributes/create">
                                <Plus /> New attribute
                            </Link>
                        </Button>
                    }
                />

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Options</TableHead>
                                <TableHead>Used by</TableHead>
                                <TableHead>Flags</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {attributes.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                        No attributes yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {attributes.map((attribute) => (
                                <TableRow key={attribute.id}>
                                    <TableCell>
                                        <span className="font-medium">{attribute.name}</span>
                                        <span className="ml-2 text-xs text-muted-foreground">{attribute.slug}</span>
                                    </TableCell>
                                    <TableCell className="capitalize">{attribute.type}</TableCell>
                                    <TableCell>{attribute.options_count}</TableCell>
                                    <TableCell>{attribute.products_count} products</TableCell>
                                    <TableCell className="space-x-1">
                                        {attribute.is_filterable && <Badge variant="secondary">Filter</Badge>}
                                        {attribute.is_visible && <Badge variant="outline">Visible</Badge>}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button asChild size="sm" variant="ghost">
                                            <Link href={`/admin/attributes/${attribute.id}/edit`}>
                                                <Pencil /> Edit
                                            </Link>
                                        </Button>
                                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => destroy(attribute.id, attribute.name)}>
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

AttributesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Attributes', href: '/admin/attributes' },
    ],
};
