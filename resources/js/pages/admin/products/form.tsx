import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField, NativeSelect } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type AttributeRow = {
    id: number | null;
    attribute_id: number;
    attribute_option_id: number | '';
    value: string;
    is_selectable: boolean;
    price_operation: '+' | '-';
    price_modifier: number;
    stock_quantity: number;
};

type CategoryAttribute = {
    id: number;
    name: string;
    type: string;
    is_required: boolean;
    options: { id: number; name: string }[];
};

type Props = {
    product: {
        id: number;
        name: string;
        sku: string | null;
        slug: string;
        vendor: string | null;
        category_id: number | null;
        price: number;
        description: string | null;
        is_visible: boolean;
        meta_title: string | null;
        meta_description: string | null;
        meta_keywords: string | null;
        stock_quantity: number;
        reserved: number;
        images: { id: number; image_path: string }[];
        main_image_id: number | null;
        discount: { price: number; start_date: string | null; end_date: string | null; is_active: boolean } | null;
        attributes: (Omit<AttributeRow, 'attribute_option_id'> & { attribute_option_id: number | null })[];
    } | null;
    categories: { id: number; name: string }[];
    categoryAttributes: Record<string, CategoryAttribute[]>;
};

export default function ProductForm({ product, categories, categoryAttributes }: Props) {
    const form = useForm({
        name: product?.name ?? '',
        sku: product?.sku ?? '',
        slug: product?.slug ?? '',
        vendor: product?.vendor ?? '',
        category_id: product?.category_id?.toString() ?? '',
        price: product?.price ?? 0,
        description: product?.description ?? '',
        is_visible: product?.is_visible ?? true,
        stock_quantity: product?.stock_quantity ?? 0,
        discount: {
            price: product?.discount?.price?.toString() ?? '',
            start_date: product?.discount?.start_date ?? '',
            end_date: product?.discount?.end_date ?? '',
            is_active: product?.discount?.is_active ?? true,
        },
        attributes: (product?.attributes ?? []).map<AttributeRow>((row) => ({ ...row, attribute_option_id: row.attribute_option_id ?? '' })),
        images: [] as File[],
        remove_image_ids: [] as number[],
        main_image_id: product?.main_image_id ?? null,
        meta_title: product?.meta_title ?? '',
        meta_description: product?.meta_description ?? '',
        meta_keywords: product?.meta_keywords ?? '',
    });

    const errors = form.errors as Record<string, string | undefined>;
    const allowed = useMemo(() => categoryAttributes[form.data.category_id] ?? [], [categoryAttributes, form.data.category_id]);

    const rowsOf = (attributeId: number) =>
        form.data.attributes.map((row, index) => ({ row, index })).filter(({ row }) => row.attribute_id === attributeId);

    const updateRow = (index: number, patch: Partial<AttributeRow>) =>
        form.setData(
            'attributes',
            form.data.attributes.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );

    const addRow = (attribute: CategoryAttribute) =>
        form.setData('attributes', [
            ...form.data.attributes,
            {
                id: null,
                attribute_id: attribute.id,
                attribute_option_id: '',
                value: '',
                is_selectable: false,
                price_operation: '+',
                price_modifier: 0,
                stock_quantity: 0,
            },
        ]);

    const removeRow = (index: number) =>
        form.setData(
            'attributes',
            form.data.attributes.filter((_, i) => i !== index),
        );

    const toggleRemoveImage = (id: number) =>
        form.setData(
            'remove_image_ids',
            form.data.remove_image_ids.includes(id) ? form.data.remove_image_ids.filter((x) => x !== id) : [...form.data.remove_image_ids, id],
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const allowedIds = new Set(allowed.map((attribute) => attribute.id));

        form.transform((data) => ({
            ...data,
            // Only attributes of the selected category are saved
            attributes: data.attributes.filter((row) => allowedIds.has(row.attribute_id)),
            ...(product ? { _method: 'put' } : {}),
        }));

        form.post(product ? `/admin/products/${product.id}` : '/admin/products', { forceFormData: true, preserveScroll: true });
    };

    return (
        <>
            <Head title={product ? `Edit ${product.name}` : 'New product'} />
            <AdminContent>
                <PageHeader
                    title={product ? 'Edit product' : 'New product'}
                    actions={
                        product && (
                            <Button asChild variant="outline">
                                <Link href={`/product/${product.slug}`}>View in store</Link>
                            </Button>
                        )
                    }
                />

                <form onSubmit={submit} className="grid max-w-5xl gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>General</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <FormField label="Name" htmlFor="name" error={form.errors.name} className="md:col-span-2">
                                <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                            </FormField>
                            <FormField label="SKU" htmlFor="sku" error={form.errors.sku}>
                                <Input id="sku" value={form.data.sku} onChange={(e) => form.setData('sku', e.target.value)} />
                            </FormField>
                            <FormField label="Brand" htmlFor="vendor" error={form.errors.vendor}>
                                <Input id="vendor" value={form.data.vendor} onChange={(e) => form.setData('vendor', e.target.value)} />
                            </FormField>
                            <FormField label="Category" htmlFor="category_id" error={form.errors.category_id}>
                                <NativeSelect id="category_id" value={form.data.category_id} onChange={(e) => form.setData('category_id', e.target.value)}>
                                    <option value="">— No category —</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <FormField label="Slug" htmlFor="slug" error={form.errors.slug} hint="Leave empty to generate it from the name.">
                                <Input id="slug" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} />
                            </FormField>
                            <FormField label="Description" htmlFor="description" error={form.errors.description} className="md:col-span-2">
                                <Textarea id="description" rows={6} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                            </FormField>
                            <div className="md:col-span-2">
                                <CheckboxField label="Visible in the store" checked={form.data.is_visible} onChange={(value) => form.setData('is_visible', value)} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Price and stock</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <FormField label="Price" htmlFor="price" error={form.errors.price}>
                                <Input id="price" type="number" step="0.01" min="0" value={form.data.price} onChange={(e) => form.setData('price', Number(e.target.value))} required />
                            </FormField>
                            <FormField
                                label="Quantity in stock"
                                htmlFor="stock_quantity"
                                error={form.errors.stock_quantity}
                                hint={product && product.reserved > 0 ? `${product.reserved} reserved by open orders` : undefined}
                            >
                                <Input id="stock_quantity" type="number" min="0" value={form.data.stock_quantity} onChange={(e) => form.setData('stock_quantity', Number(e.target.value))} />
                            </FormField>

                            <div className="md:col-span-2">
                                <h3 className="mb-2 text-sm font-semibold">Promotional price</h3>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <FormField label="Discount price" htmlFor="discount_price" error={errors['discount.price']} hint="Leave empty for no discount. Must be lower than the price.">
                                        <Input
                                            id="discount_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={form.data.discount.price}
                                            onChange={(e) => form.setData('discount', { ...form.data.discount, price: e.target.value })}
                                        />
                                    </FormField>
                                    <FormField label="Starts" htmlFor="discount_start" error={errors['discount.start_date']}>
                                        <Input
                                            id="discount_start"
                                            type="datetime-local"
                                            value={form.data.discount.start_date}
                                            onChange={(e) => form.setData('discount', { ...form.data.discount, start_date: e.target.value })}
                                        />
                                    </FormField>
                                    <FormField label="Ends" htmlFor="discount_end" error={errors['discount.end_date']}>
                                        <Input
                                            id="discount_end"
                                            type="datetime-local"
                                            value={form.data.discount.end_date}
                                            onChange={(e) => form.setData('discount', { ...form.data.discount, end_date: e.target.value })}
                                        />
                                    </FormField>
                                </div>
                                <div className="mt-3">
                                    <CheckboxField
                                        label="Discount is active"
                                        checked={form.data.discount.is_active}
                                        onChange={(value) => form.setData('discount', { ...form.data.discount, is_active: value })}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Images</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            {product && product.images.length > 0 && (
                                <ul className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {product.images.map((image) => {
                                        const removed = form.data.remove_image_ids.includes(image.id);

                                        return (
                                            <li key={image.id} className={`rounded-lg border p-2 ${removed ? 'opacity-40' : ''}`}>
                                                <img src={image.image_path.replace('/original/', '/medium/')} alt="" className="mb-2 aspect-square w-full rounded object-cover" />
                                                <label className="flex items-center gap-2 text-xs">
                                                    <input
                                                        type="radio"
                                                        name="main_image"
                                                        disabled={removed}
                                                        checked={form.data.main_image_id === image.id}
                                                        onChange={() => form.setData('main_image_id', image.id)}
                                                    />
                                                    Main image
                                                </label>
                                                <label className="mt-1 flex items-center gap-2 text-xs text-destructive">
                                                    <input type="checkbox" checked={removed} onChange={() => toggleRemoveImage(image.id)} />
                                                    Remove
                                                </label>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                            <FormField label="Add images" htmlFor="images" error={errors.images ?? errors['images.0']} hint="JPEG, PNG, WebP or GIF up to 6 MB each. The first image becomes the main one unless you choose another.">
                                <Input id="images" type="file" accept="image/*" multiple onChange={(e) => form.setData('images', Array.from(e.target.files ?? []))} />
                            </FormField>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Attributes</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5">
                            {!form.data.category_id && <p className="text-sm text-muted-foreground">Select a category to set product attributes.</p>}
                            {form.data.category_id && allowed.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    This category has no attributes. Assign some in the <Link href="/admin/categories" className="underline">category settings</Link>.
                                </p>
                            )}
                            {allowed.map((attribute) => {
                                const hasOptions = ['select', 'multiselect', 'color'].includes(attribute.type);

                                return (
                                    <div key={attribute.id} className="rounded-lg border p-3">
                                        <div className="mb-2 flex items-center justify-between">
                                            <h3 className="font-medium">
                                                {attribute.name}
                                                {attribute.is_required && <span className="text-destructive"> *</span>}
                                            </h3>
                                            <Button type="button" size="sm" variant="outline" onClick={() => addRow(attribute)}>
                                                <Plus /> Add value
                                            </Button>
                                        </div>
                                        {rowsOf(attribute.id).length === 0 && <p className="text-sm text-muted-foreground">No values.</p>}
                                        <div className="grid gap-2">
                                            {rowsOf(attribute.id).map(({ row, index }) => (
                                                <div key={index} className="flex flex-wrap items-center gap-2 rounded-md bg-muted/40 p-2">
                                                    {hasOptions ? (
                                                        <NativeSelect
                                                            className="w-44"
                                                            aria-label={`${attribute.name} option`}
                                                            value={row.attribute_option_id}
                                                            onChange={(e) =>
                                                                updateRow(index, {
                                                                    attribute_option_id: e.target.value ? Number(e.target.value) : '',
                                                                    value: attribute.options.find((o) => o.id === Number(e.target.value))?.name ?? '',
                                                                })
                                                            }
                                                        >
                                                            <option value="">— Select —</option>
                                                            {attribute.options.map((option) => (
                                                                <option key={option.id} value={option.id}>
                                                                    {option.name}
                                                                </option>
                                                            ))}
                                                        </NativeSelect>
                                                    ) : (
                                                        <Input className="w-44" aria-label={`${attribute.name} value`} placeholder="Value" value={row.value} onChange={(e) => updateRow(index, { value: e.target.value })} />
                                                    )}

                                                    {hasOptions && (
                                                        <CheckboxField label="Customer can choose" checked={row.is_selectable} onChange={(checked) => updateRow(index, { is_selectable: checked })} />
                                                    )}

                                                    {hasOptions && row.is_selectable && (
                                                        <>
                                                            <NativeSelect
                                                                className="w-16"
                                                                aria-label="Price operation"
                                                                value={row.price_operation}
                                                                onChange={(e) => updateRow(index, { price_operation: e.target.value as '+' | '-' })}
                                                            >
                                                                <option value="+">+</option>
                                                                <option value="-">−</option>
                                                            </NativeSelect>
                                                            <Input
                                                                className="w-24"
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                aria-label="Price modifier"
                                                                placeholder="Price"
                                                                value={row.price_modifier}
                                                                onChange={(e) => updateRow(index, { price_modifier: Number(e.target.value) })}
                                                            />
                                                            <Input
                                                                className="w-24"
                                                                type="number"
                                                                min="0"
                                                                aria-label="Variant stock"
                                                                placeholder="Stock"
                                                                value={row.stock_quantity}
                                                                onChange={(e) => updateRow(index, { stock_quantity: Number(e.target.value) })}
                                                            />
                                                        </>
                                                    )}

                                                    <Button type="button" size="icon" variant="ghost" className="ml-auto text-destructive" aria-label="Remove value" onClick={() => removeRow(index)}>
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
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
                            {product ? 'Save changes' : 'Create product'}
                        </Button>
                        <Button asChild variant="outline">
                            <Link href="/admin/products">Back to products</Link>
                        </Button>
                    </div>
                </form>
            </AdminContent>
        </>
    );
}

ProductForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Products', href: '/admin/products' },
        { title: 'Edit', href: '#' },
    ],
};
