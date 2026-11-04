import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdminContent, PageHeader } from '@/components/admin/page-header';
import { CheckboxField, FormField, NativeSelect } from '@/components/admin/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type SocialLink = { id: number | null; name: string; url: string; is_active: boolean };

type Props = {
    settings: {
        site_name: string;
        site_description: string;
        site_url: string;
        footer_about_text: string;
        store_status: 'open' | 'closed';
        maintenance_message: string;
        contact_phone: string;
        contact_email: string;
        contact_address: string;
        phone_mask: string;
        compare_enabled: boolean;
        reviews_enabled: boolean;
        reviews_moderation: boolean;
        seo_home_title: string;
        seo_home_description: string;
        seo_home_keywords: string;
        seo_title_template: string;
        seo_desc_template: string;
        seo_category_title_template: string;
        seo_category_desc_template: string;
    };
    logo: string | null;
    socialLinks: { id: number; name: string; url: string; is_active: boolean }[];
};

const tabs = ['General', 'Contact', 'Catalog', 'SEO', 'Social links'] as const;

export default function Settings({ settings, logo, socialLinks }: Props) {
    const [tab, setTab] = useState<(typeof tabs)[number]>('General');
    const form = useForm({
        ...settings,
        logo: null as File | null,
        remove_logo: false,
        social_links: socialLinks.map<SocialLink>((link) => ({ ...link })),
    });
    const errors = form.errors as Record<string, string | undefined>;

    const text = (key: keyof Props['settings']) => ({
        id: key,
        value: form.data[key] as string,
        onChange: (event: { target: { value: string } }) => form.setData(key, event.target.value as never),
    });

    const updateLink = (index: number, patch: Partial<SocialLink>) =>
        form.setData(
            'social_links',
            form.data.social_links.map((link, i) => (i === index ? { ...link, ...patch } : link)),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/settings', { forceFormData: true, preserveScroll: true, onSuccess: () => form.setData('logo', null) });
    };

    return (
        <>
            <Head title="Settings" />
            <AdminContent>
                <PageHeader title="Settings" description="Store information and behavior" />

                <div className="mb-4 flex flex-wrap gap-1 border-b">
                    {tabs.map((name) => (
                        <button
                            key={name}
                            type="button"
                            onClick={() => setTab(name)}
                            className={`-mb-px border-b-2 px-4 py-2 text-sm font-medium ${tab === name ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
                        >
                            {name}
                        </button>
                    ))}
                </div>

                <form onSubmit={submit} className="grid max-w-3xl gap-6">
                    <Card>
                        <CardContent className="grid gap-4">
                            {tab === 'General' && (
                                <>
                                    <FormField label="Store name" htmlFor="site_name" error={errors.site_name}>
                                        <Input {...text('site_name')} required />
                                    </FormField>
                                    <FormField label="Description" htmlFor="site_description" error={errors.site_description}>
                                        <Textarea rows={2} {...text('site_description')} />
                                    </FormField>
                                    <FormField label="Store URL" htmlFor="site_url" error={errors.site_url}>
                                        <Input type="url" placeholder="https://example.com" {...text('site_url')} />
                                    </FormField>
                                    <FormField label="Footer text" htmlFor="footer_about_text" error={errors.footer_about_text}>
                                        <Textarea rows={3} {...text('footer_about_text')} />
                                    </FormField>
                                    <FormField label="Logo" htmlFor="logo" error={errors.logo}>
                                        {logo && !form.data.remove_logo && <img src={logo} alt="Current logo" className="mb-2 max-h-16 rounded border bg-muted p-1" />}
                                        <Input id="logo" type="file" accept="image/*" onChange={(e) => form.setData('logo', e.target.files?.[0] ?? null)} />
                                        {logo && <CheckboxField label="Remove the current logo" checked={form.data.remove_logo} onChange={(value) => form.setData('remove_logo', value)} />}
                                    </FormField>
                                    <FormField label="Store status" htmlFor="store_status" error={errors.store_status} hint="When closed, visitors see the maintenance message. Administrators can still browse.">
                                        <NativeSelect id="store_status" value={form.data.store_status} onChange={(e) => form.setData('store_status', e.target.value as 'open' | 'closed')}>
                                            <option value="open">Open</option>
                                            <option value="closed">Closed for maintenance</option>
                                        </NativeSelect>
                                    </FormField>
                                    <FormField label="Maintenance message" htmlFor="maintenance_message" error={errors.maintenance_message}>
                                        <Textarea rows={2} {...text('maintenance_message')} />
                                    </FormField>
                                </>
                            )}

                            {tab === 'Contact' && (
                                <>
                                    <FormField label="Phone" htmlFor="contact_phone" error={errors.contact_phone}>
                                        <Input {...text('contact_phone')} />
                                    </FormField>
                                    <FormField label="Email" htmlFor="contact_email" error={errors.contact_email}>
                                        <Input type="email" {...text('contact_email')} />
                                    </FormField>
                                    <FormField label="Address" htmlFor="contact_address" error={errors.contact_address}>
                                        <Input {...text('contact_address')} />
                                    </FormField>
                                    <FormField label="Checkout phone mask" htmlFor="phone_mask" error={errors.phone_mask} hint="Use # for each digit, for example +1 (###) ###-####.">
                                        <Input {...text('phone_mask')} />
                                    </FormField>
                                </>
                            )}

                            {tab === 'Catalog' && (
                                <>
                                    <CheckboxField label="Enable product comparison" checked={form.data.compare_enabled} onChange={(value) => form.setData('compare_enabled', value)} />
                                    <CheckboxField label="Enable product reviews" checked={form.data.reviews_enabled} onChange={(value) => form.setData('reviews_enabled', value)} />
                                    <CheckboxField
                                        label="Moderate new reviews"
                                        hint="New reviews stay hidden until an administrator publishes them."
                                        checked={form.data.reviews_moderation}
                                        onChange={(value) => form.setData('reviews_moderation', value)}
                                    />
                                </>
                            )}

                            {tab === 'SEO' && (
                                <>
                                    <p className="text-sm text-muted-foreground">
                                        Templates support <code>{'{name}'}</code>, <code>{'{price}'}</code>, <code>{'{category}'}</code> and <code>{'{shop}'}</code> placeholders. A product&apos;s own meta fields take priority.
                                    </p>
                                    <FormField label="Home page title" htmlFor="seo_home_title" error={errors.seo_home_title}>
                                        <Input {...text('seo_home_title')} />
                                    </FormField>
                                    <FormField label="Home page description" htmlFor="seo_home_description" error={errors.seo_home_description}>
                                        <Textarea rows={2} {...text('seo_home_description')} />
                                    </FormField>
                                    <FormField label="Home page keywords" htmlFor="seo_home_keywords" error={errors.seo_home_keywords}>
                                        <Input {...text('seo_home_keywords')} />
                                    </FormField>
                                    <FormField label="Product title template" htmlFor="seo_title_template" error={errors.seo_title_template}>
                                        <Input placeholder="Buy {name} for {price} at {shop}" {...text('seo_title_template')} />
                                    </FormField>
                                    <FormField label="Product description template" htmlFor="seo_desc_template" error={errors.seo_desc_template}>
                                        <Textarea rows={2} {...text('seo_desc_template')} />
                                    </FormField>
                                    <FormField label="Category title template" htmlFor="seo_category_title_template" error={errors.seo_category_title_template}>
                                        <Input {...text('seo_category_title_template')} />
                                    </FormField>
                                    <FormField label="Category description template" htmlFor="seo_category_desc_template" error={errors.seo_category_desc_template}>
                                        <Textarea rows={2} {...text('seo_category_desc_template')} />
                                    </FormField>
                                </>
                            )}

                            {tab === 'Social links' && (
                                <>
                                    {form.data.social_links.length === 0 && <p className="text-sm text-muted-foreground">No social links yet.</p>}
                                    {form.data.social_links.map((link, index) => (
                                        <div key={index} className="flex flex-wrap items-center gap-2">
                                            <Input className="w-36" aria-label="Network name" placeholder="Name" value={link.name} onChange={(e) => updateLink(index, { name: e.target.value })} />
                                            <Input className="min-w-[200px] flex-1" aria-label="Link URL" type="url" placeholder="https://" value={link.url} onChange={(e) => updateLink(index, { url: e.target.value })} />
                                            <CheckboxField label="Show" checked={link.is_active} onChange={(checked) => updateLink(index, { is_active: checked })} />
                                            <Button type="button" size="icon" variant="ghost" className="text-destructive" aria-label="Remove link" onClick={() => form.setData('social_links', form.data.social_links.filter((_, i) => i !== index))}>
                                                <Trash2 />
                                            </Button>
                                            {errors[`social_links.${index}.url`] && <p className="w-full text-sm text-destructive">{errors[`social_links.${index}.url`]}</p>}
                                        </div>
                                    ))}
                                    <div>
                                        <Button type="button" variant="outline" size="sm" onClick={() => form.setData('social_links', [...form.data.social_links, { id: null, name: '', url: '', is_active: true }])}>
                                            <Plus /> Add link
                                        </Button>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <div>
                        <Button type="submit" disabled={form.processing}>
                            Save settings
                        </Button>
                    </div>
                </form>
            </AdminContent>
        </>
    );
}

Settings.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Settings', href: '/admin/settings' },
    ],
};
