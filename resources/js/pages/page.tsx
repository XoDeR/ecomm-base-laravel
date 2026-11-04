import { StorefrontBreadcrumbs } from '@/components/storefront/breadcrumbs';
import { SeoHead } from '@/components/storefront/seo-head';
import type { SeoMeta } from '@/types';

type Props = {
    page: { title: string; content: string };
    seo: SeoMeta;
};

export default function StaticPage({ page, seo }: Props) {
    return (
        <>
            <SeoHead seo={seo} fallbackTitle={page.title} />
            <StorefrontBreadcrumbs items={[{ title: page.title }]} />
            <h1 className="mb-4 text-3xl font-bold">{page.title}</h1>
            <div
                className="prose max-w-none text-slate-700 [&_img]:h-auto [&_img]:max-w-full [&_ul]:list-disc [&_ul]:pl-6"
                // Content is authored by store administrators in the admin panel.
                dangerouslySetInnerHTML={{ __html: page.content }}
            />
        </>
    );
}
