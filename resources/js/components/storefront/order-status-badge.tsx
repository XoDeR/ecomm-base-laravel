const styles: Record<string, string> = {
    new: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-indigo-100 text-indigo-800',
    processing: 'bg-amber-100 text-amber-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-teal-100 text-teal-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    returned: 'bg-slate-200 text-slate-700',
};

export function OrderStatusBadge({ status, label }: { status: string; label: string }) {
    return (
        <span className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ${styles[status] ?? 'bg-slate-100 text-slate-700'}`}>
            {label}
        </span>
    );
}
