import { Head, Link, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { formatPrice } from '@/lib/format';

type Props = {
    order: { id: number; total: number; customer_email: string };
};

export default function CheckoutSuccess({ order }: Props) {
    const { shop } = usePage().props;

    return (
        <>
            <Head title="Order placed" />
            <div className="mx-auto max-w-lg py-10 text-center">
                <CheckCircle2 className="mx-auto mb-4 size-16 text-green-500" />
                <h1 className="mb-2 text-3xl font-bold">Thank you for your order!</h1>
                <p className="mb-1 text-slate-600">
                    Your order <strong>#{order.id}</strong> for <strong>{formatPrice(order.total, shop.currency.symbol)}</strong> has been placed.
                </p>
                <p className="mb-6 text-sm text-slate-500">We will contact you at {order.customer_email} to confirm the details.</p>
                <Link href="/products" className="rounded-md bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700">
                    Continue shopping
                </Link>
            </div>
        </>
    );
}
