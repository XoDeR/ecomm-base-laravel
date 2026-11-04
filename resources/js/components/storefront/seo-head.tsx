import { Head } from '@inertiajs/react';
import type { SeoMeta } from '@/types';

export function SeoHead({ seo, fallbackTitle }: { seo?: SeoMeta; fallbackTitle: string }) {
    return (
        <Head title={seo?.title || fallbackTitle}>
            {seo?.description && <meta name="description" content={seo.description} />}
            {seo?.keywords && <meta name="keywords" content={seo.keywords} />}
        </Head>
    );
}
