import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField, NativeSelect } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type AttributeRow = { attribute_id: number; is_required: boolean };

type Props = {
    category: {
        id: number;
        name: string;
        slug: string;
        parent_id: number | null;
        description: string | null;
        sort_order: number;
        image: string | null;
        is_active: boolean;
        meta_title: string | null;
        meta_description: string | null;
        meta_keywords: string | null;
        attributes: AttributeRow[];
    } | null;
    parents: { id: number; name: string }[];
    allAttributes: { id: number; name: string; type: string }[];
};

export default function CategoryForm({ category, parents, allAttributes }: Props) {
    const form = useForm<{
        name: string;
        slug: string;
        parent_id: string;
        description: string;
        sort_order: number;
        is_active: boolean;
        meta_title: string;
        meta_description: string;
        meta_keywords: string;
        image: File | null;
        remove_image: boolean;
        attributes: AttributeRow[];
    }>({
        name: category?.name ?? '',
        slug: category?.slug ?? '',
        parent_id: category?.parent_id?.toString() ?? '',
        description: category?.description ?? '',
        sort_order: category?.sort_order ?? 0,
        is_active: category?.is_active ?? true,
        meta_title: category?.meta_title ?? '',
        meta_description: category?.meta_description ?? '',
        meta_keywords: category?.meta_keywords ?? '',
        image: null,
        remove_image: false,
        attributes: category?.attributes ?? [],
    });

    const selected = (id: number) => form.data.attributes.find((row) => row.attribute_id === id);

    const toggleAttribute = (id: number, checked: boolean) =>
        form.setData(
            'attributes',
            checked
                ? [...form.data.attributes, { attribute_id: id, is_required: false }]
                : form.data.attributes.filter((row) => row.attribute_id !== id),
        );

    const setRequired = (id: number, required: boolean) =>
        form.setData(
            'attributes',
            form.data.attributes.map((row) => (row.attribute_id === id ? { ...row, is_required: required } : row)),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (category) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/admin/categories/${category.id}`, { forceFormData: true });
        } else {
            form.post('/admin/categories', { forceFormData: true });
        }
    };

    return (
        <>
            <Head title={category ? `Edit ${category.name}` : 'New category'} />
            <AdminContent>
                <PageHeader title={category ? 'Edit category' : 'New category'} />

                <form onSubmit={submit} className="grid max-w-4xl gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>General</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <FormField label="Name" htmlFor="name" error={form.errors.name}>
                                <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                            </FormField>
                            <FormField label="Slug" htmlFor="slug" error={form.errors.slug} hint="Leave empty to generate it from the name.">
                                <Input id="slug" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} />
                            </FormField>
                            <FormField label="Parent category" htmlFor="parent_id" error={form.errors.parent_id}>
                                <NativeSelect id="parent_id" value={form.data.parent_id} onChange={(e) => form.setData('parent_id', e.target.value)}>
                                    <option value="">— None (top level) —</option>
                                    {parents.map((parent) => (
                                        <option key={parent.id} value={parent.id}>
                                            {parent.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <FormField label="Sort order" htmlFor="sort_order" error={form.errors.sort_order}>
                                <Input id="sort_order" type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} />
                            </FormField>
                            <FormField label="Description" htmlFor="description" error={form.errors.description} className="md:col-span-2">
                                <Textarea id="description" rows={4} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                            </FormField>
                            <FormField label="Image" htmlFor="image" error={form.errors.image} className="md:col-span-2">
                                {category?.image && !form.data.remove_image && (
                                    <img src={category.image} alt="" className="mb-2 size-24 rounded-lg object-cover" />
                                )}
                                <Input id="image" type="file" accept="image/*" onChange={(e) => form.setData('image', e.target.files?.[0] ?? null)} />
                                {category?.image && (
                                    <CheckboxField label="Remove the current image" checked={form.data.remove_image} onChange={(value) => form.setData('remove_image', value)} />
                                )}
                            </FormField>
                            <div className="md:col-span-2">
                                <CheckboxField label="Active (visible in the store)" checked={form.data.is_active} onChange={(value) => form.setData('is_active', value)} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Attributes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-3 text-sm text-muted-foreground">
                                Choose which attributes products of this category can have. Filterable attributes appear as filters on the category page.
                            </p>
                            {allAttributes.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No attributes yet. <Link href="/admin/attributes/create" className="underline">Create one</Link>.
                                </p>
                            ) : (
                                <ul className="grid gap-2 md:grid-cols-2">
                                    {allAttributes.map((attribute) => {
                                        const row = selected(attribute.id);

                                        return (
                                            <li key={attribute.id} className="flex items-center justify-between rounded-md border p-2">
                                                <CheckboxField
                                                    label={`${attribute.name} (${attribute.type})`}
                                                    checked={row !== undefined}
                                                    onChange={(checked) => toggleAttribute(attribute.id, checked)}
                                                />
                                                {row && (
                                                    <CheckboxField label="Required" checked={row.is_required} onChange={(checked) => setRequired(attribute.id, checked)} />
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
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
                            <FormField label="Meta keywords" htmlFor="meta_keywords" error={form.errors.meta_keywords}>
                                <Input id="meta_keywords" value={form.data.meta_keywords} onChange={(e) => form.setData('meta_keywords', e.target.value)} />
                            </FormField>
                        </CardContent>
                    </Card>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {category ? 'Save changes' : 'Create category'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/admin/categories">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </AdminContent>
        </>
    );
}

CategoryForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Categories', href: '/admin/categories' },
        { title: 'Edit', href: '#' },
    ],
};
