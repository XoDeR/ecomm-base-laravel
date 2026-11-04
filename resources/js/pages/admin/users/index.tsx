import { Head, Link, router } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { NativeSelect } from '@/components/admin/form-field';
import { Pagination } from '@/components/storefront/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import type { PaginationLink } from '@/types';

type Props = {
    users: {
        data: {
            id: number;
            name: string;
            email: string;
            phone: string | null;
            role: string | null;
            is_active: boolean;
            orders_count: number;
            last_login_at: string | null;
            created_at: string;
        }[];
        links: PaginationLink[];
        total: number;
    };
    roles: { slug: string; name: string }[];
    filters: { search: string; role: string };
};

export default function UsersIndex({ users, roles, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    const apply = (patch: Partial<Props['filters']>) => {
        const next = { ...filters, search, ...patch };

        router.get('/admin/users', { ...(next.search ? { search: next.search } : {}), ...(next.role ? { role: next.role } : {}) }, { preserveState: true, replace: true });
    };

    return (
        <>
            <Head title="Customers" />
            <AdminContent>
                <PageHeader title="Customers" description={`${users.total} accounts`} />

                <div className="mb-4 flex flex-wrap items-center gap-3 rounded-xl border p-3">
                    <form
                        onSubmit={(event: FormEvent) => {
                            event.preventDefault();
                            apply({});
                        }}
                        className="min-w-[200px] flex-1"
                    >
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by name, email or phone" />
                    </form>
                    <NativeSelect className="w-44" value={filters.role} onChange={(e) => apply({ role: e.target.value })} aria-label="Role">
                        <option value="">All roles</option>
                        {roles.map((role) => (
                            <option key={role.slug} value={role.slug}>
                                {role.name}
                            </option>
                        ))}
                    </NativeSelect>
                </div>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Customer</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Orders</TableHead>
                                <TableHead>Last login</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                        No customers found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <Link href={`/admin/users/${user.id}/edit`} className="font-medium hover:underline">
                                            {user.name}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {user.email}
                                            {user.phone && ` · ${user.phone}`}
                                        </p>
                                    </TableCell>
                                    <TableCell>{user.role ?? '—'}</TableCell>
                                    <TableCell>{user.orders_count}</TableCell>
                                    <TableCell>{formatDate(user.last_login_at)}</TableCell>
                                    <TableCell>
                                        <Badge variant={user.is_active ? 'default' : 'destructive'}>{user.is_active ? 'Active' : 'Blocked'}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button asChild size="sm" variant="ghost">
                                            <Link href={`/admin/users/${user.id}/edit`}>
                                                <Pencil /> Manage
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                <Pagination links={users.links} />
            </AdminContent>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Customers', href: '/admin/users' },
    ],
};
