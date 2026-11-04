import { Link } from '@inertiajs/react';
import type { PaginationLink } from '@/types';

const decode = (label: string) =>
    label
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace(/<[^>]+>/g, '')
        .replace('Previous', 'Prev');

export function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="mt-8 flex flex-wrap items-center justify-center gap-1">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        className={`rounded-md border px-3 py-1.5 text-sm ${
                            link.active
                                ? 'border-blue-600 bg-blue-600 text-white'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-blue-50'
                        }`}
                    >
                        {decode(link.label)}
                    </Link>
                ) : (
                    <span
                        key={index}
                        className="rounded-md border border-slate-100 px-3 py-1.5 text-sm text-slate-300"
                    >
                        {decode(link.label)}
                    </span>
                ),
            )}
        </nav>
    );
}
