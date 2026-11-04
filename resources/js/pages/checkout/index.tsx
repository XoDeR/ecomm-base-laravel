import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/components/input-error';
import { formatPrice } from '@/lib/format';

type Method = { id: number; code: string; name: string; cost: number; address: string | null };

type Props = {
    items: {
        id: number;
        name: string;
        quantity: number;
        selected_options: { name: string; value: string }[];
        total_price: number;
    }[];
    subtotal: number;
    discount: number;
    deliveryMethods: Method[];
    paymentMethods: Method[];
    defaults: { full_name: string; phone: string; email: string };
    pickupCode: string;
};

function applyMask(value: string, mask: string): string {
    const digits = value.replace(/\D/g, '');
    // Digits fixed in the mask before the first placeholder (the country code)
    const prefix = mask.slice(0, mask.indexOf('#')).replace(/\D/g, '');
    let rest = digits.startsWith(prefix) ? digits.slice(prefix.length) : digits;
    let result = '';

    for (const char of mask) {
        if (char !== '#') {
            result += char;
            continue;
        }

        if (!rest.length) {
            break;
        }

        result += rest[0];
        rest = rest.slice(1);
    }

    return result.replace(/\D+$/, '');
}

function Card({ title, children, className = '' }: { title: string; children: ReactNode; className?: string }) {
    return (
        <section className={`rounded-lg border border-slate-200 bg-white p-4 ${className}`}>
            <h2 className="mb-2 text-lg font-semibold">{title}</h2>
            {children}
        </section>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <div className="mt-3">
            <label className="mb-1 block font-semibold">{label}</label>
            {children}
            <InputError message={error} className="mt-1" />
        </div>
    );
}

const inputClass = 'w-full rounded-md border border-slate-300 p-2.5';

export default function Checkout({ items, subtotal, discount, deliveryMethods, paymentMethods, defaults, pickupCode }: Props) {
    const { shop } = usePage().props;
    const money = (value: number) => formatPrice(value, shop.currency.symbol);

    const form = useForm({
        full_name: defaults.full_name,
        phone: defaults.phone,
        email: defaults.email,
        delivery_id: deliveryMethods[0]?.id ?? 0,
        payment_id: paymentMethods[0]?.id ?? 0,
        delivery_city: '',
        delivery_address: '',
        comment: '',
    });

    const delivery = deliveryMethods.find((method) => method.id === form.data.delivery_id);
    const shipping = delivery?.cost ?? 0;
    const total = subtotal - discount + shipping;
    const isPickup = delivery?.code === pickupCode;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/place-order');
    };

    const errors = form.errors as Record<string, string | undefined>;

    return (
        <>
            <Head title="Checkout" />
            <h1 className="mb-4 text-3xl font-bold">Checkout</h1>

            {errors.cart && (
                <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-red-800">{errors.cart}</div>
            )}

            <form onSubmit={submit} className="grid gap-4 md:grid-cols-2" noValidate>
                <Card title="Contact information">
                    <Field label="Full name" error={errors.full_name}>
                        <input
                            className={inputClass}
                            value={form.data.full_name}
                            onChange={(e) => form.setData('full_name', e.target.value)}
                            required
                        />
                    </Field>
                    <Field label="Phone" error={errors.phone}>
                        <input
                            className={inputClass}
                            type="tel"
                            value={form.data.phone}
                            placeholder={shop.phoneMask}
                            onChange={(e) => form.setData('phone', applyMask(e.target.value, shop.phoneMask))}
                            required
                        />
                    </Field>
                    <Field label="Email" error={errors.email}>
                        <input
                            className={inputClass}
                            type="email"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            required
                        />
                    </Field>
                </Card>

                <Card title="Delivery">
                    <div className="my-1 flex flex-wrap gap-4">
                        {deliveryMethods.map((method) => (
                            <label key={method.id} className="flex cursor-pointer items-start gap-2">
                                <input
                                    type="radio"
                                    name="delivery_id"
                                    checked={form.data.delivery_id === method.id}
                                    onChange={() => form.setData('delivery_id', method.id)}
                                    className="mt-1"
                                />
                                <span>
                                    {method.name}
                                    {method.cost > 0 && (
                                        <small className="block font-normal text-slate-400">+ {money(method.cost)} delivery</small>
                                    )}
                                </span>
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.delivery_id} />

                    {isPickup ? (
                        <div className="mt-3">
                            <p className="mb-1 font-semibold">Pickup address</p>
                            <p className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">{delivery?.address}</p>
                        </div>
                    ) : (
                        <>
                            <Field label="City" error={errors.delivery_city}>
                                <input
                                    className={inputClass}
                                    value={form.data.delivery_city}
                                    onChange={(e) => form.setData('delivery_city', e.target.value)}
                                    placeholder="Start typing your city"
                                />
                            </Field>
                            <Field label="Delivery address" error={errors.delivery_address}>
                                <input
                                    className={inputClass}
                                    value={form.data.delivery_address}
                                    onChange={(e) => form.setData('delivery_address', e.target.value)}
                                    placeholder="Street, house, apartment"
                                />
                            </Field>
                        </>
                    )}
                </Card>

                <Card title="Payment">
                    <div className="my-1 flex flex-wrap gap-4">
                        {paymentMethods.map((method) => (
                            <label key={method.id} className="flex cursor-pointer items-center gap-2">
                                <input
                                    type="radio"
                                    name="payment_id"
                                    checked={form.data.payment_id === method.id}
                                    onChange={() => form.setData('payment_id', method.id)}
                                />
                                {method.name}
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.payment_id} />

                    <Field label="Order comment" error={errors.comment}>
                        <textarea
                            className={inputClass}
                            rows={4}
                            value={form.data.comment}
                            onChange={(e) => form.setData('comment', e.target.value)}
                            placeholder="Anything we should know about your order?"
                        />
                    </Field>
                </Card>

                <Card title="Your order" className="md:col-span-2">
                    <ul>
                        {items.map((item) => (
                            <li key={item.id} className="flex justify-between border-b border-dashed border-slate-200 py-2">
                                <span>
                                    {item.name} × {item.quantity}
                                    {item.selected_options.length > 0 && (
                                        <small className="block text-slate-500">
                                            {item.selected_options.map((option) => `${option.name}: ${option.value}`).join('; ')}
                                        </small>
                                    )}
                                </span>
                                <strong>{money(item.total_price)}</strong>
                            </li>
                        ))}
                    </ul>

                    {discount > 0 && (
                        <div className="flex justify-between border-b border-dashed border-slate-200 py-2 text-green-600">
                            <span>Discount</span>
                            <strong>-{money(discount)}</strong>
                        </div>
                    )}

                    <div className="flex justify-between border-b border-dashed border-slate-200 py-2">
                        <span>Shipping</span>
                        <strong>{shipping > 0 ? money(shipping) : 'Free'}</strong>
                    </div>

                    <div className="flex justify-between pt-3 text-xl">
                        <span>Total</span>
                        <strong>{money(total)}</strong>
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="mt-4 w-full rounded-md bg-blue-600 p-3 font-bold text-white hover:bg-blue-700 disabled:opacity-60"
                    >
                        {form.processing ? 'Sending...' : 'Place order'}
                    </button>
                </Card>
            </form>
        </>
    );
}
