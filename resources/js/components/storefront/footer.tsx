import { Link, usePage } from '@inertiajs/react';

export function StorefrontFooter() {
    const { shop } = usePage().props;

    return (
        <footer className="mt-12 bg-slate-800 text-slate-300">
            <div className="mx-auto grid max-w-[1300px] gap-8 px-4 py-10 md:grid-cols-3 md:px-8">
                <div>
                    <h3 className="mb-3 text-lg font-bold text-white">
                        {shop.name}
                    </h3>
                    <p className="text-sm leading-relaxed">{shop.footerAbout}</p>
                </div>

                <div>
                    <h3 className="mb-3 text-lg font-bold text-white">Information</h3>
                    <ul className="space-y-2 text-sm">
                        {shop.footerPages.map((page) => (
                            <li key={page.slug}>
                                <Link
                                    href={`/${page.slug}`}
                                    className="hover:text-white"
                                >
                                    {page.title}
                                </Link>
                            </li>
                        ))}
                        <li>
                            <Link href="/products" className="hover:text-white">
                                All products
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 className="mb-3 text-lg font-bold text-white">Contact us</h3>
                    <ul className="space-y-2 text-sm">
                        {shop.phone && <li>{shop.phone}</li>}
                        {shop.email && <li>{shop.email}</li>}
                        {shop.address && <li>{shop.address}</li>}
                    </ul>
                    {shop.socialLinks.length > 0 && (
                        <div className="mt-4 flex flex-wrap gap-3 text-sm">
                            {shop.socialLinks.map((link) => (
                                <a
                                    key={link.slug}
                                    href={link.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="hover:text-white"
                                >
                                    {link.name}
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            </div>
            <div className="border-t border-white/10 py-4 text-center text-xs text-slate-400">
                © {new Date().getFullYear()} {shop.name}. All rights reserved.
            </div>
        </footer>
    );
}
