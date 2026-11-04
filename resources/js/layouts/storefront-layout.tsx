import type { ReactNode } from 'react';
import { StorefrontFooter } from '@/components/storefront/footer';
import { StorefrontHeader } from '@/components/storefront/header';

export default function StorefrontLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col bg-slate-100 text-slate-800">
            <StorefrontHeader />
            <main className="mx-auto w-full max-w-[1300px] flex-1 px-4 py-6 md:px-8">
                <div className="rounded-lg bg-white p-4 shadow-sm md:p-6">
                    {children}
                </div>
            </main>
            <StorefrontFooter />
        </div>
    );
}
