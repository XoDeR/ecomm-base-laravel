import { router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { formatPrice } from '@/lib/format';

type Suggestion = {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    price: number;
};

export function SearchBox() {
    const { shop } = usePage().props;
    const initial =
        typeof window !== 'undefined'
            ? (new URLSearchParams(window.location.search).get('q') ?? '')
            : '';
    const [query, setQuery] = useState(initial);
    const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
    const [open, setOpen] = useState(false);
    const container = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const term = query.trim();

        if (term.length < 2) {
            setSuggestions([]);

            return;
        }

        const controller = new AbortController();
        const timer = setTimeout(() => {
            fetch(`/search/autocomplete?q=${encodeURIComponent(term)}`, {
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then((data: { suggestions: Suggestion[] }) => {
                    setSuggestions(data.suggestions);
                    setOpen(true);
                })
                .catch(() => undefined);
        }, 250);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [query]);

    useEffect(() => {
        const close = (event: MouseEvent) => {
            if (!container.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', close);

        return () => document.removeEventListener('mousedown', close);
    }, []);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setOpen(false);
        router.get('/search', { q: query });
    };

    return (
        <div ref={container} className="relative w-full max-w-sm">
            <form
                onSubmit={submit}
                role="search"
                className="flex items-center overflow-hidden rounded-lg bg-white"
            >
                <input
                    type="search"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    onFocus={() => suggestions.length > 0 && setOpen(true)}
                    placeholder="Search products..."
                    autoComplete="off"
                    className="min-w-0 flex-1 px-3 py-2 text-sm text-slate-800 outline-none"
                />
                <button
                    type="submit"
                    aria-label="Search"
                    className="px-3 text-slate-500 hover:text-blue-600"
                >
                    <Search className="size-4" />
                </button>
            </form>

            {open && suggestions.length > 0 && (
                <ul className="absolute top-full right-0 left-0 z-50 mt-1 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                    {suggestions.map((item) => (
                        <li key={item.id}>
                            <a
                                href={`/product/${item.slug}`}
                                onClick={(event) => {
                                    event.preventDefault();
                                    setOpen(false);
                                    router.visit(`/product/${item.slug}`);
                                }}
                                className="flex items-center gap-3 px-3 py-2 text-sm text-slate-800 hover:bg-blue-50"
                            >
                                {item.image ? (
                                    <img
                                        src={item.image}
                                        alt=""
                                        className="size-9 rounded object-cover"
                                    />
                                ) : (
                                    <span className="size-9 rounded bg-slate-100" />
                                )}
                                <span className="flex-1 truncate">
                                    {item.name}
                                </span>
                                <span className="font-semibold">
                                    {formatPrice(
                                        item.price,
                                        shop.currency.symbol,
                                    )}
                                </span>
                            </a>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
