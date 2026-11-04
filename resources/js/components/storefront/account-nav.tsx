import { Link, usePage } from '@inertiajs/react';

const items = [
    { title: 'Overview', href: '/profile' },
    { title: 'My orders', href: '/profile/orders' },
    { title: 'Favorites', href: '/profile/favorites' },
    { title: 'Edit profile', href: '/settings/profile' },
];

export function AccountNav() {
    const { url } = usePage();
    const path = url.split('?')[0];

    return (
        <nav className="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3" aria-label="Account">
            {items.map((item) => (
                <Link
                    key={item.href}
                    href={item.href}
                    className={`rounded-md px-3 py-1.5 text-sm font-medium ${
                        path === item.href ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-blue-50'
                    }`}
                >
                    {item.title}
                </Link>
            ))}
        </nav>
    );
}
