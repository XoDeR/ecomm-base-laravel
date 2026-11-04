import { Link } from '@inertiajs/react';
import { Home } from 'lucide-react';
import type { Breadcrumb } from '@/types';

export function StorefrontBreadcrumbs({ items }: { items: Breadcrumb[] }) {
    return (
        <nav aria-label="Breadcrumb" className="mb-5">
            <ol className="flex flex-wrap items-center text-sm text-slate-500">
                <li className="inline-flex items-center">
                    <Link
                        href="/"
                        className="inline-flex items-center gap-1 hover:text-blue-600"
                    >
                        <Home className="size-3.5" />
                        Home
                    </Link>
                </li>
                {items.map((item, index) => (
                    <li
                        key={`${item.title}-${index}`}
                        className="inline-flex items-center before:mx-2 before:text-slate-300 before:content-['›']"
                    >
                        {item.href ? (
                            <Link href={item.href} className="hover:text-blue-600">
                                {item.title}
                            </Link>
                        ) : (
                            <span className="max-w-[260px] truncate font-medium text-slate-800">
                                {item.title}
                            </span>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}
