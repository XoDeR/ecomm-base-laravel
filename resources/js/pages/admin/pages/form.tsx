import { Head, Link, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type Props = {
    page: {
        id: number;
        title: string;
        slug: string;
        content: string;
        meta_title: string | null;
        meta_description: string | null;
        is_active: boolean;
        sort_order: number;
    } | null;
};

export default function PageForm({ page }: Props) {
    const form = useForm({
        title: page?.title ?? '',
        slug: page?.slug ?? '',
        content: page?.content ?? '',
        meta_title: page?.meta_title ?? '',
        meta_description: page?.meta_description ?? '',
        is_active: page?.is_active ?? true,
        sort_order: page?.sort_order ?? 0,
    });
    const editor = useRef<HTMLTextAreaElement>(null);
    const [uploading, setUploading] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (page) {
            form.put(`/admin/pages/${page.id}`);
        } else {
            form.post('/admin/pages');
        }
    };

    /** Upload an image and insert an <img> tag at the cursor position. */
    const uploadImage = async (file: File) => {
        setUploading(true);
        setUploadError(null);

        const body = new FormData();
        body.append('image', file);

        try {
            const token = decodeURIComponent(document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');
            const response = await fetch('/admin/pages/upload', {
                method: 'POST',
                body,
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': token },
            });

            if (!response.ok) {
                throw new Error('Upload failed. Use an image smaller than 4 MB.');
            }

            const { url } = (await response.json()) as { url: string };
            const tag = `<img src="${url}" alt="" style="max-width: 100%; height: auto;">`;
            const area = editor.current;
            const position = area?.selectionStart ?? form.data.content.length;

            form.setData('content', form.data.content.slice(0, position) + tag + form.data.content.slice(position));
        } catch (error) {
            setUploadError(error instanceof Error ? error.message : 'Upload failed.');
        } finally {
            setUploading(false);
        }
    };

    return (
        <>
            <Head title={page ? `Edit ${page.title}` : 'New page'} />
            <AdminContent>
                <PageHeader title={page ? 'Edit page' : 'New page'} />

                <form onSubmit={submit} className="grid max-w-4xl gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Content</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <FormField label="Title" htmlFor="title" error={form.errors.title}>
                                <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
                            </FormField>
                            <FormField label="Slug" htmlFor="slug" error={form.errors.slug} hint="The page is available at /slug. Leave empty to generate it from the title.">
                                <Input id="slug" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} />
                            </FormField>
                            <FormField label="Content (HTML)" htmlFor="content" error={form.errors.content} className="md:col-span-2">
                                <Textarea
                                    id="content"
                                    ref={editor}
                                    rows={14}
                                    className="font-mono text-sm"
                                    value={form.data.content}
                                    onChange={(e) => form.setData('content', e.target.value)}
                                    required
                                />
                                <div className="flex flex-wrap items-center gap-3">
                                    <Input
                                        type="file"
                                        accept="image/*"
                                        disabled={uploading}
                                        className="max-w-xs"
                                        aria-label="Insert an image"
                                        onChange={(e) => {
                                            const file = e.target.files?.[0];

                                            if (file) void uploadImage(file);
                                            e.target.value = '';
                                        }}
                                    />
                                    <span className="text-xs text-muted-foreground">{uploading ? 'Uploading...' : 'Insert an image at the cursor position.'}</span>
                                </div>
                                {uploadError && <p className="text-sm text-destructive">{uploadError}</p>}
                            </FormField>
                            <FormField label="Sort order" htmlFor="sort_order" error={form.errors.sort_order}>
                                <Input id="sort_order" type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} />
                            </FormField>
                            <div className="flex items-end">
                                <CheckboxField label="Published" checked={form.data.is_active} onChange={(value) => form.setData('is_active', value)} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>SEO</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <FormField label="Meta title" htmlFor="meta_title" error={form.errors.meta_title}>
                                <Input id="meta_title" value={form.data.meta_title} onChange={(e) => form.setData('meta_title', e.target.value)} />
                            </FormField>
                            <FormField label="Meta description" htmlFor="meta_description" error={form.errors.meta_description}>
                                <Textarea id="meta_description" rows={3} value={form.data.meta_description} onChange={(e) => form.setData('meta_description', e.target.value)} />
                            </FormField>
                        </CardContent>
                    </Card>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {page ? 'Save changes' : 'Create page'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/admin/pages">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </AdminContent>
        </>
    );
}

PageForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Pages', href: '/admin/pages' },
        { title: 'Edit', href: '#' },
    ],
};
