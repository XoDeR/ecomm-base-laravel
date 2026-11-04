import { Head, Link, router } from '@inertiajs/react';
import { Eye, EyeOff, Reply, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/storefront/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';
import type { PaginationLink } from '@/types';

type Review = {
    id: number;
    author_name: string;
    user_email: string | null;
    body: string;
    rating: number | null;
    is_visible: boolean;
    reply_to: string | null;
    product: { name: string; slug: string } | null;
    created_at: string;
};

type Props = {
    reviews: { data: Review[]; links: PaginationLink[]; total: number };
    filters: { visibility: 'all' | 'visible' | 'hidden'; search: string };
    pending: number;
};

const tabs = [
    { value: 'all', label: 'All' },
    { value: 'visible', label: 'Published' },
    { value: 'hidden', label: 'Hidden' },
] as const;

function ReviewRow({ review, selected, onSelect }: { review: Review; selected: boolean; onSelect: () => void }) {
    const [mode, setMode] = useState<'view' | 'edit' | 'reply'>('view');
    const [text, setText] = useState(review.body);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: () => setMode('view') };

        if (mode === 'edit') {
            router.put(`/admin/reviews/${review.id}`, { body: text, rating: review.rating }, options);
        } else {
            router.post(`/admin/reviews/${review.id}/reply`, { body: text }, { ...options, onSuccess: () => { setMode('view'); setText(review.body); } });
        }
    };

    return (
        <li className={`rounded-xl border p-4 ${review.is_visible ? '' : 'bg-muted/40'}`}>
            <div className="flex flex-wrap items-start gap-3">
                <input type="checkbox" checked={selected} onChange={onSelect} aria-label={`Select review ${review.id}`} className="mt-1.5 size-4" />
                <div className="min-w-0 flex-1">
                    <div className="mb-1 flex flex-wrap items-center gap-2 text-sm">
                        <strong>{review.author_name}</strong>
                        {review.user_email && <span className="text-xs text-muted-foreground">{review.user_email}</span>}
                        {review.rating && (
                            <span className="inline-flex">
                                {[1, 2, 3, 4, 5].map((star) => (
                                    <Star key={star} className={`size-3.5 ${star <= review.rating! ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/40'}`} />
                                ))}
                            </span>
                        )}
                        {review.reply_to && <Badge variant="outline">Reply to {review.reply_to}</Badge>}
                        <Badge variant={review.is_visible ? 'default' : 'secondary'}>{review.is_visible ? 'Published' : 'Hidden'}</Badge>
                    </div>
                    <p className="mb-1 text-xs text-muted-foreground">
                        {review.product ? (
                            <Link href={`/product/${review.product.slug}`} className="hover:underline">
                                {review.product.name}
                            </Link>
                        ) : (
                            'Deleted product'
                        )}{' '}
                        · {formatDate(review.created_at)}
                    </p>

                    {mode === 'view' ? (
                        <p className="whitespace-pre-line text-sm">{review.body}</p>
                    ) : (
                        <form onSubmit={submit} className="grid gap-2">
                            <Textarea value={text} onChange={(e) => setText(e.target.value)} rows={3} />
                            <div className="flex gap-2">
                                <Button type="submit" size="sm">
                                    {mode === 'edit' ? 'Save' : 'Publish reply'}
                                </Button>
                                <Button type="button" size="sm" variant="outline" onClick={() => setMode('view')}>
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    )}
                </div>
                <div className="flex gap-1">
                    <Button size="icon" variant="ghost" title={review.is_visible ? 'Hide' : 'Publish'} onClick={() => router.post(`/admin/reviews/${review.id}/toggle`, {}, { preserveScroll: true })}>
                        {review.is_visible ? <EyeOff /> : <Eye />}
                    </Button>
                    <Button size="icon" variant="ghost" title="Edit" onClick={() => { setText(review.body); setMode('edit'); }}>
                        <span className="text-xs">Edit</span>
                    </Button>
                    <Button size="icon" variant="ghost" title="Reply" onClick={() => { setText(''); setMode('reply'); }}>
                        <Reply />
                    </Button>
                    <Button size="icon" variant="ghost" className="text-destructive" title="Delete" onClick={() => confirm('Delete this review?') && router.delete(`/admin/reviews/${review.id}`, { preserveScroll: true })}>
                        <Trash2 />
                    </Button>
                </div>
            </div>
        </li>
    );
}

export default function ReviewsIndex({ reviews, filters, pending }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [selected, setSelected] = useState<number[]>([]);

    const apply = (patch: Partial<Props['filters']>) => {
        const next = { ...filters, search, ...patch };

        router.get(
            '/admin/reviews',
            { ...(next.search ? { search: next.search } : {}), ...(next.visibility !== 'all' ? { visibility: next.visibility } : {}) },
            { preserveState: true, replace: true },
        );
        setSelected([]);
    };

    const bulk = (action: 'show' | 'hide' | 'delete') => {
        if (action === 'delete' && !confirm(`Delete ${selected.length} reviews?`)) {
            return;
        }

        router.post('/admin/reviews/bulk', { action, ids: selected }, { preserveScroll: true, onSuccess: () => setSelected([]) });
    };

    return (
        <>
            <Head title="Reviews" />
            <AdminContent>
                <PageHeader title="Reviews" description={pending > 0 ? `${pending} hidden reviews` : 'Moderate customer reviews'} />

                <div className="mb-4 flex flex-wrap items-center gap-3 rounded-xl border p-3">
                    <form
                        onSubmit={(event: FormEvent) => {
                            event.preventDefault();
                            apply({});
                        }}
                        className="min-w-[200px] flex-1"
                    >
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by author, text or product" />
                    </form>
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

                {selected.length > 0 && (
                    <div className="mb-3 flex flex-wrap items-center gap-2 rounded-lg bg-muted p-2 text-sm">
                        <span>{selected.length} selected</span>
                        <Button size="sm" variant="outline" onClick={() => bulk('show')}>
                            Publish
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => bulk('hide')}>
                            Hide
                        </Button>
                        <Button size="sm" variant="outline" className="text-destructive" onClick={() => bulk('delete')}>
                            Delete
                        </Button>
                    </div>
                )}

                {reviews.data.length === 0 ? (
                    <p className="rounded-xl border p-8 text-center text-muted-foreground">No reviews found.</p>
                ) : (
                    <ul className="grid gap-3">
                        {reviews.data.map((review) => (
                            <ReviewRow
                                key={review.id}
                                review={review}
                                selected={selected.includes(review.id)}
                                onSelect={() => setSelected((current) => (current.includes(review.id) ? current.filter((id) => id !== review.id) : [...current, review.id]))}
                            />
                        ))}
                    </ul>
                )}
                <Pagination links={reviews.links} />
            </AdminContent>
        </>
    );
}

ReviewsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Reviews', href: '/admin/reviews' },
    ],
};
