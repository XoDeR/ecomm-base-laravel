import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Props = {
    categories: {
        id: number;
        name: string;
        path: string;
        depth: number;
        is_active: boolean;
        products_count: number;
        image: string | null;
    }[];
};

export default function CategoriesIndex({ categories }: Props) {
    const destroy = (id: number, name: string) => {
        if (confirm(`Delete the category "${name}"? Its subcategories will move to the top level.`)) {
            router.delete(`/admin/categories/${id}`);
        }
    };

    return (
        <>
            <Head title="Categories" />
            <AdminContent>
                <PageHeader
                    title="Categories"
                    description="Organize products into a category tree"
                    actions={
                        <Button asChild>
                            <Link href="/admin/categories/create">
                                <Plus /> New category
                            </Link>
                        </Button>
                    }
                />

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>URL</TableHead>
                                <TableHead>Products</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                        No categories yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {categories.map((category) => (
                                <TableRow key={category.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-3" style={{ paddingLeft: category.depth * 20 }}>
                                            {category.image ? (
                                                <img src={category.image} alt="" className="size-8 rounded object-cover" />
                                            ) : (
                                                <span className="size-8 rounded bg-muted" />
                                            )}
                                            <span className="font-medium">{category.name}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">/category{category.path}</TableCell>
                                    <TableCell>{category.products_count}</TableCell>
                                    <TableCell>
                                        <Badge variant={category.is_active ? 'default' : 'secondary'}>
                                            {category.is_active ? 'Active' : 'Hidden'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button asChild size="sm" variant="ghost">
                                            <Link href={`/admin/categories/${category.id}/edit`}>
                                                <Pencil /> Edit
                                            </Link>
                                        </Button>
                                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => destroy(category.id, category.name)}>
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

CategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Categories', href: '/admin/categories' },
    ],
};
