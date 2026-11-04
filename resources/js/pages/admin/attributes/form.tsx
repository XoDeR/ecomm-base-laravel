import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField, NativeSelect } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type OptionRow = { id: number | null; name: string; value: string; color_code: string };

type Props = {
    attribute: {
        id: number;
        name: string;
        slug: string;
        type: string;
        description: string | null;
        is_filterable: boolean;
        is_visible: boolean;
        sort_order: number;
        options: { id: number; name: string; value: string; color_code: string | null }[];
    } | null;
    types: string[];
};

export default function AttributeForm({ attribute, types }: Props) {
    const form = useForm({
        name: attribute?.name ?? '',
        slug: attribute?.slug ?? '',
        type: attribute?.type ?? 'text',
        description: attribute?.description ?? '',
        is_filterable: attribute?.is_filterable ?? true,
        is_visible: attribute?.is_visible ?? true,
        sort_order: attribute?.sort_order ?? 0,
        options: (attribute?.options ?? []).map<OptionRow>((option) => ({
            id: option.id,
            name: option.name,
            value: option.value,
            color_code: option.color_code ?? '',
        })),
    });

    const hasOptions = ['select', 'multiselect', 'color'].includes(form.data.type);
    const errors = form.errors as Record<string, string | undefined>;

    const updateOption = (index: number, patch: Partial<OptionRow>) =>
        form.setData(
            'options',
            form.data.options.map((option, i) => (i === index ? { ...option, ...patch } : option)),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (attribute) {
            form.put(`/admin/attributes/${attribute.id}`);
        } else {
            form.post('/admin/attributes');
        }
    };

    return (
        <>
            <Head title={attribute ? `Edit ${attribute.name}` : 'New attribute'} />
            <AdminContent>
                <PageHeader title={attribute ? 'Edit attribute' : 'New attribute'} />

                <form onSubmit={submit} className="grid max-w-3xl gap-6">
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
                            <FormField label="Type" htmlFor="type" error={form.errors.type}>
                                <NativeSelect id="type" value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                    {types.map((type) => (
                                        <option key={type} value={type}>
                                            {type.charAt(0).toUpperCase() + type.slice(1)}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <FormField label="Sort order" htmlFor="sort_order" error={form.errors.sort_order}>
                                <Input id="sort_order" type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} />
                            </FormField>
                            <FormField label="Description" htmlFor="description" error={form.errors.description} className="md:col-span-2">
                                <Textarea id="description" rows={3} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                            </FormField>
                            <CheckboxField label="Use as a filter on category pages" checked={form.data.is_filterable} onChange={(value) => form.setData('is_filterable', value)} />
                            <CheckboxField label="Show on the product page" checked={form.data.is_visible} onChange={(value) => form.setData('is_visible', value)} />
                        </CardContent>
                    </Card>

                    {hasOptions && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Options</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3">
                                {form.data.options.length === 0 && <p className="text-sm text-muted-foreground">No options yet.</p>}
                                {form.data.options.map((option, index) => (
                                    <div key={index} className="grid gap-2">
                                        <div className="flex items-center gap-2">
                                            <Input
                                                aria-label="Option name"
                                                placeholder="Name"
                                                value={option.name}
                                                onChange={(e) => updateOption(index, { name: e.target.value })}
                                            />
                                            <Input
                                                aria-label="Option value"
                                                placeholder="Value (defaults to name)"
                                                value={option.value}
                                                onChange={(e) => updateOption(index, { value: e.target.value })}
                                            />
                                            {form.data.type === 'color' && (
                                                <input
                                                    type="color"
                                                    aria-label="Option color"
                                                    value={option.color_code || '#000000'}
                                                    onChange={(e) => updateOption(index, { color_code: e.target.value })}
                                                    className="h-9 w-12 shrink-0 rounded border"
                                                />
                                            )}
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                className="text-destructive"
                                                aria-label="Remove option"
                                                onClick={() => form.setData('options', form.data.options.filter((_, i) => i !== index))}
                                            >
                                                <Trash2 />
                                            </Button>
                                        </div>
                                        {errors[`options.${index}.name`] && (
                                            <p className="text-sm text-destructive">{errors[`options.${index}.name`]}</p>
                                        )}
                                    </div>
                                ))}
                                <div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => form.setData('options', [...form.data.options, { id: null, name: '', value: '', color_code: '' }])}
                                    >
                                        <Plus /> Add option
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {attribute ? 'Save changes' : 'Create attribute'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/admin/attributes">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </AdminContent>
        </>
    );
}

AttributeForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Attributes', href: '/admin/attributes' },
        { title: 'Edit', href: '#' },
    ],
};
