import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

export type FilterOption = {
    value: string;
    label: string;
    color: string | null;
    count: number;
};

export type FilterAttribute = {
    id: number;
    name: string;
    slug: string;
    type: string;
    options: FilterOption[];
    range: { min: number; max: number } | null;
};

export type AppliedFilters = {
    min_price: number | null;
    max_price: number | null;
    attr: Record<string, string[]>;
    range: Record<string, { min: number | null; max: number | null }>;
    sort: string;
};

type Props = {
    url: string;
    options: FilterAttribute[];
    priceRange: { min: number; max: number };
    applied: AppliedFilters;
};

function Group({ title, children }: { title: string; children: ReactNode }) {
    return (
        <div className="border-t border-slate-100 py-3 first:border-t-0 first:pt-0">
            <h4 className="mb-2 text-sm font-semibold text-slate-900">{title}</h4>
            {children}
        </div>
    );
}

function NumberPair({
    min,
    max,
    onChange,
    placeholders,
}: {
    min: number | null;
    max: number | null;
    onChange: (min: string, max: string) => void;
    placeholders: [string, string];
}) {
    const [values, setValues] = useState<[string, string]>([min?.toString() ?? '', max?.toString() ?? '']);
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);

    useEffect(() => {
        setValues([min?.toString() ?? '', max?.toString() ?? '']);
    }, [min, max]);

    const update = (index: 0 | 1, value: string) => {
        const next: [string, string] = [...values] as [string, string];
        next[index] = value;
        setValues(next);
        clearTimeout(timer.current);
        timer.current = setTimeout(() => onChange(next[0], next[1]), 500);
    };

    const input = 'w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm';

    return (
        <div className="flex items-center gap-2">
            <input type="number" step="0.01" value={values[0]} placeholder={placeholders[0]} onChange={(e) => update(0, e.target.value)} className={input} />
            <span className="text-slate-400">-</span>
            <input type="number" step="0.01" value={values[1]} placeholder={placeholders[1]} onChange={(e) => update(1, e.target.value)} className={input} />
        </div>
    );
}

export function ProductFilters({ url, options, priceRange, applied }: Props) {
    const visit = (next: AppliedFilters) => {
        const query: Record<string, unknown> = { sort: next.sort };

        if (next.min_price !== null) query.min_price = next.min_price;
        if (next.max_price !== null) query.max_price = next.max_price;
        if (Object.keys(next.attr).length) query.attr = next.attr;
        if (Object.keys(next.range).length) query.range = next.range;

        router.get(url, query as Record<string, never>, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const toggle = (attributeId: number, value: string) => {
        const current = applied.attr[attributeId] ?? [];
        const selected = current.includes(value)
            ? current.filter((v) => v !== value)
            : [...current, value];
        const attr = { ...applied.attr };

        if (selected.length) attr[attributeId] = selected;
        else delete attr[attributeId];

        visit({ ...applied, attr });
    };

    const hasFilters =
        applied.min_price !== null ||
        applied.max_price !== null ||
        Object.keys(applied.attr).length > 0 ||
        Object.keys(applied.range).length > 0;

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="text-lg font-semibold">Filters</h3>
                {hasFilters && (
                    <button
                        type="button"
                        onClick={() => visit({ min_price: null, max_price: null, attr: {}, range: {}, sort: applied.sort })}
                        className="text-sm text-blue-600 hover:underline"
                    >
                        Clear all
                    </button>
                )}
            </div>

            <Group title="Price">
                <NumberPair
                    min={applied.min_price}
                    max={applied.max_price}
                    placeholders={[`Min (${priceRange.min})`, `Max (${priceRange.max})`]}
                    onChange={(min, max) =>
                        visit({
                            ...applied,
                            min_price: min === '' ? null : Number(min),
                            max_price: max === '' ? null : Number(max),
                        })
                    }
                />
            </Group>

            {options.map((attribute) => (
                <Group key={attribute.id} title={attribute.name}>
                    {attribute.type === 'range' && attribute.range ? (
                        <NumberPair
                            min={applied.range[attribute.id]?.min ?? null}
                            max={applied.range[attribute.id]?.max ?? null}
                            placeholders={[`Min (${attribute.range.min})`, `Max (${attribute.range.max})`]}
                            onChange={(min, max) => {
                                const range = { ...applied.range };

                                if (min === '' && max === '') delete range[attribute.id];
                                else
                                    range[attribute.id] = {
                                        min: min === '' ? null : Number(min),
                                        max: max === '' ? null : Number(max),
                                    };

                                visit({ ...applied, range });
                            }}
                        />
                    ) : (
                        <ul className="space-y-1.5">
                            {attribute.options.map((option) => (
                                <li key={option.value}>
                                    <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            checked={(applied.attr[attribute.id] ?? []).includes(option.value)}
                                            onChange={() => toggle(attribute.id, option.value)}
                                            className="size-4 rounded border-slate-300"
                                        />
                                        {option.color && (
                                            <span
                                                className="size-4 rounded-full border border-slate-300"
                                                style={{ backgroundColor: option.color }}
                                            />
                                        )}
                                        <span className="flex-1">{option.label}</span>
                                        <span className="text-xs text-slate-400">({option.count})</span>
                                    </label>
                                </li>
                            ))}
                        </ul>
                    )}
                </Group>
            ))}
        </div>
    );
}
