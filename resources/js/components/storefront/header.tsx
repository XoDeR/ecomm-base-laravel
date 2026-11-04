import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, Phone, Scale, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import { SearchBox } from '@/components/storefront/search-box';
import type { CategoryNode, User } from '@/types';

function CategoryList({ nodes, depth = 0 }: { nodes: CategoryNode[]; depth?: number }) {
    return (
        <ul className={depth === 0 ? 'py-1' : 'pl-3'}>
            {nodes.map((node) => (
                <li key={node.id}>
                    <Link
                        href={node.url}
                        className="block px-4 py-1.5 text-sm text-slate-900 hover:bg-blue-50"
                    >
                        {node.name}
                    </Link>
                    {node.children.length > 0 && depth < 3 && (
                        <CategoryList nodes={node.children} depth={depth + 1} />
                    )}
                </li>
            ))}
        </ul>
    );
}

function Counter({ value }: { value: number }) {
    return (
        <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-white/90 px-1.5 text-[11px] font-bold text-blue-800">
            {value}
        </span>
    );
}

export function StorefrontHeader() {
    const { shop, auth } = usePage().props;
    const user = auth.user as User | null;
    const [menuOpen, setMenuOpen] = useState(false);

    const linkClass =
        'rounded-lg px-3 py-2 text-sm font-medium text-white transition hover:bg-white/20';

    return (
        <header className="sticky top-0 z-40 bg-gradient-to-br from-blue-600 to-blue-800 text-white shadow-lg">
            <nav className="mx-auto flex max-w-[1300px] flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:gap-8 md:px-8">
                <Link
                    href="/"
                    className="flex items-center gap-3 text-2xl font-extrabold text-white"
                >
                    {shop.logo && (
                        <img
                            src={shop.logo}
                            alt={shop.name}
                            className="max-h-[50px] max-w-[200px] rounded-md bg-white/10 object-contain p-1"
                        />
                    )}
                    <span className="truncate">{shop.name}</span>
                </Link>

                <div className="flex flex-1 flex-col gap-2">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <div className="flex flex-wrap items-center gap-1">
                            <Link href="/" className={linkClass}>
                                Home
                            </Link>
                            <Link href="/products" className={linkClass}>
                                Products
                            </Link>

                            <div
                                className="relative"
                                onMouseEnter={() => setMenuOpen(true)}
                                onMouseLeave={() => setMenuOpen(false)}
                            >
                                <button
                                    type="button"
                                    className={`${linkClass} flex items-center gap-1`}
                                    aria-expanded={menuOpen}
                                    onClick={() => setMenuOpen((open) => !open)}
                                >
                                    Categories
                                    <ChevronDown className="size-4" />
                                </button>
                                {menuOpen && shop.categories.length > 0 && (
                                    <div className="absolute top-full left-0 z-50 max-h-[360px] min-w-[250px] overflow-y-auto rounded-lg bg-white shadow-xl">
                                        <CategoryList nodes={shop.categories} />
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-1">
                            {user ? (
                                <>
                                    <Link href="/profile" className={linkClass}>
                                        {user.name}
                                    </Link>
                                    {user.can_access_admin && (
                                        <Link href="/admin" className={linkClass}>
                                            Admin
                                        </Link>
                                    )}
                                    <button
                                        type="button"
                                        className={linkClass}
                                        onClick={() => router.post('/logout')}
                                    >
                                        Log out
                                    </button>
                                </>
                            ) : (
                                <>
                                    <Link href="/login" className={linkClass}>
                                        Log in
                                    </Link>
                                    <Link href="/register" className={linkClass}>
                                        Register
                                    </Link>
                                </>
                            )}
                            {shop.compareEnabled && (
                                <Link
                                    href="/compare"
                                    className={`${linkClass} flex items-center gap-2`}
                                >
                                    <Scale className="size-4" />
                                    Compare
                                    <Counter value={shop.compareCount} />
                                </Link>
                            )}
                            <Link
                                href="/cart"
                                className={`${linkClass} flex items-center gap-2`}
                            >
                                <ShoppingCart className="size-4" />
                                Cart
                                <Counter value={shop.cartCount} />
                            </Link>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-2">
                        <SearchBox />
                        {shop.phone && (
                            <a
                                href={`tel:${shop.phone.replace(/[^+\d]/g, '')}`}
                                className="flex items-center gap-2 text-lg font-semibold text-white hover:text-white/80"
                            >
                                <Phone className="size-4" />
                                {shop.phone}
                            </a>
                        )}
                    </div>
                </div>
            </nav>
        </header>
    );
}
