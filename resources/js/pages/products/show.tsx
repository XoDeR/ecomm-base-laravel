import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Heart, Star } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { StorefrontBreadcrumbs } from '@/components/storefront/breadcrumbs';
import { CompareButton, ProductGrid, ProductPrice } from '@/components/storefront/product-card';
import { SeoHead } from '@/components/storefront/seo-head';
import { formatDate, formatPrice } from '@/lib/format';
import type { Breadcrumb, ProductCard, SeoMeta, User } from '@/types';

type SelectableGroup = {
    attribute_id: number;
    name: string;
    options: {
        id: number;
        label: string;
        price_delta: number;
        color: string | null;
        available: number;
    }[];
};

type Review = {
    id: number;
    author_name: string;
    rating: number | null;
    body: string;
    created_at: string;
    replies: { id: number; author_name: string; body: string; created_at: string }[];
};

type Props = {
    product: ProductCard & {
        description: string | null;
        category: { name: string; url: string } | null;
        available_quantity: number;
        images: string[];
        selectable: SelectableGroup[];
        specs: { name: string; value: string }[];
    };
    breadcrumbs: Breadcrumb[];
    reviews: Review[];
    reviewsEnabled: boolean;
    rating: { average: number; count: number };
    isFavorite: boolean;
    related: ProductCard[];
    seo: SeoMeta;
};

function Stars({ value }: { value: number }) {
    return (
        <span className="inline-flex" aria-label={`${value} out of 5`}>
            {[1, 2, 3, 4, 5].map((star) => (
                <Star
                    key={star}
                    className={`size-4 ${star <= Math.round(value) ? 'fill-amber-400 text-amber-400' : 'text-slate-300'}`}
                />
            ))}
        </span>
    );
}

function ReviewForm({ slug, parentId, onDone }: { slug: string; parentId?: number; onDone?: () => void }) {
    const form = useForm({ body: '', rating: 0, parent_id: parentId ?? null });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, rating: data.rating || null }));
        form.post(`/product/${slug}/reviews`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onDone?.();
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            {!parentId && (
                <div className="flex items-center gap-1">
                    <span className="mr-2 text-sm text-slate-600">Your rating:</span>
                    {[1, 2, 3, 4, 5].map((star) => (
                        <button type="button" key={star} onClick={() => form.setData('rating', star)} aria-label={`${star} stars`}>
                            <Star className={`size-5 ${star <= form.data.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300'}`} />
                        </button>
                    ))}
                </div>
            )}
            <textarea
                value={form.data.body}
                onChange={(event) => form.setData('body', event.target.value)}
                rows={parentId ? 2 : 4}
                placeholder={parentId ? 'Write a reply...' : 'Share your experience with this product...'}
                className="w-full rounded-md border border-slate-300 p-3 text-sm"
            />
            <InputError message={form.errors.body} />
            <button
                type="submit"
                disabled={form.processing}
                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
            >
                {parentId ? 'Reply' : 'Submit review'}
            </button>
        </form>
    );
}

function ReviewItem({ review, slug, canReply }: { review: Review; slug: string; canReply: boolean }) {
    const [replying, setReplying] = useState(false);

    return (
        <li className="rounded-lg border border-slate-200 p-4">
            <div className="mb-1 flex flex-wrap items-center gap-3">
                <strong>{review.author_name}</strong>
                {review.rating && <Stars value={review.rating} />}
                <span className="text-xs text-slate-400">{formatDate(review.created_at)}</span>
            </div>
            <p className="whitespace-pre-line text-slate-700">{review.body}</p>

            {review.replies.length > 0 && (
                <ul className="mt-3 space-y-2 border-l-2 border-blue-100 pl-4">
                    {review.replies.map((reply) => (
                        <li key={reply.id} className="text-sm">
                            <strong>{reply.author_name}</strong>{' '}
                            <span className="text-xs text-slate-400">{formatDate(reply.created_at)}</span>
                            <p className="whitespace-pre-line text-slate-700">{reply.body}</p>
                        </li>
                    ))}
                </ul>
            )}

            {canReply &&
                (replying ? (
                    <div className="mt-3">
                        <ReviewForm slug={slug} parentId={review.id} onDone={() => setReplying(false)} />
                    </div>
                ) : (
                    <button type="button" onClick={() => setReplying(true)} className="mt-2 text-sm text-blue-600 hover:underline">
                        Reply
                    </button>
                ))}
        </li>
    );
}

export default function ProductShow({ product, breadcrumbs, reviews, reviewsEnabled, rating, isFavorite, related, seo }: Props) {
    const { shop, auth } = usePage().props;
    const user = auth.user as User | null;
    const [activeImage, setActiveImage] = useState(0);
    const [quantity, setQuantity] = useState(1);
    const [chosen, setChosen] = useState<Record<number, number>>({});

    const delta = useMemo(
        () =>
            product.selectable.reduce((sum, group) => {
                const option = group.options.find((o) => o.id === chosen[group.attribute_id]);

                return sum + (option?.price_delta ?? 0);
            }, 0),
        [chosen, product.selectable],
    );

    const unitPrice = product.final_price + delta;
    const missingOption = product.selectable.some((group) => !chosen[group.attribute_id]);
    const maxQuantity = Math.max(
        1,
        Math.min(
            product.available_quantity,
            ...product.selectable
                .map((group) => group.options.find((o) => o.id === chosen[group.attribute_id])?.available)
                .filter((v): v is number => v !== undefined),
        ),
    );

    const addToCart = () =>
        router.post(
            `/cart/add/${product.id}`,
            { quantity, selected_option_ids: Object.values(chosen) },
            { preserveScroll: true },
        );

    const toggleFavorite = () => router.post('/favorites/toggle', { product_id: product.id }, { preserveScroll: true });

    return (
        <>
            <SeoHead seo={seo} fallbackTitle={product.name} />

            <StorefrontBreadcrumbs items={breadcrumbs} />

            <div className="grid gap-8 lg:grid-cols-2">
                <div>
                    <div className="mb-3 flex aspect-square items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white">
                        {product.images[activeImage] ? (
                            <img src={product.images[activeImage]} alt={product.name} className="size-full object-contain" />
                        ) : (
                            <span className="text-slate-400">No image</span>
                        )}
                    </div>
                    {product.images.length > 1 && (
                        <div className="flex flex-wrap gap-2">
                            {product.images.map((image, index) => (
                                <button
                                    key={image}
                                    type="button"
                                    onClick={() => setActiveImage(index)}
                                    className={`size-16 overflow-hidden rounded-lg border-2 ${index === activeImage ? 'border-blue-600' : 'border-transparent'}`}
                                >
                                    <img src={image.replace('/original/', '/thumb/')} alt="" className="size-full object-cover" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <h1 className="mb-2 text-3xl font-bold">{product.name}</h1>
                    <div className="mb-3 flex flex-wrap items-center gap-4 text-sm text-slate-500">
                        {product.sku && <span>SKU: {product.sku}</span>}
                        {product.vendor && <span>Brand: {product.vendor}</span>}
                        {rating.count > 0 && (
                            <span className="flex items-center gap-1">
                                <Stars value={rating.average} /> {rating.average} ({rating.count})
                            </span>
                        )}
                    </div>

                    <div className="mb-4">
                        {delta === 0 ? (
                            <div className="text-2xl">
                                <ProductPrice product={product} />
                            </div>
                        ) : (
                            <p className="text-2xl font-bold">{formatPrice(unitPrice, shop.currency.symbol)}</p>
                        )}
                    </div>

                    <p className={`mb-4 text-sm font-medium ${product.in_stock ? 'text-green-600' : 'text-red-600'}`}>
                        {product.in_stock ? `In stock: ${product.available_quantity}` : 'Out of stock'}
                    </p>

                    {product.selectable.map((group) => (
                        <div key={group.attribute_id} className="mb-4">
                            <h3 className="mb-2 text-sm font-semibold">{group.name}</h3>
                            <div className="flex flex-wrap gap-2">
                                {group.options.map((option) => (
                                    <button
                                        key={option.id}
                                        type="button"
                                        disabled={option.available < 1}
                                        onClick={() => setChosen((c) => ({ ...c, [group.attribute_id]: option.id }))}
                                        className={`flex items-center gap-2 rounded-md border px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40 ${
                                            chosen[group.attribute_id] === option.id
                                                ? 'border-blue-600 bg-blue-50 text-blue-700'
                                                : 'border-slate-300 hover:bg-slate-50'
                                        }`}
                                    >
                                        {option.color && (
                                            <span className="size-4 rounded-full border" style={{ backgroundColor: option.color }} />
                                        )}
                                        {option.label}
                                        {option.price_delta !== 0 && (
                                            <span className="text-xs text-slate-500">
                                                ({option.price_delta > 0 ? '+' : '-'}
                                                {formatPrice(Math.abs(option.price_delta), shop.currency.symbol)})
                                            </span>
                                        )}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}

                    <div className="mb-6 flex flex-wrap items-center gap-3">
                        <input
                            type="number"
                            min={1}
                            max={maxQuantity}
                            value={quantity}
                            onChange={(event) => setQuantity(Math.max(1, Math.min(maxQuantity, Number(event.target.value) || 1)))}
                            className="w-20 rounded-md border border-slate-300 px-3 py-2 text-center"
                            aria-label="Quantity"
                        />
                        <button
                            type="button"
                            disabled={!product.in_stock || missingOption}
                            onClick={addToCart}
                            className="rounded-md bg-blue-600 px-6 py-2.5 font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                        >
                            {product.in_stock ? (missingOption ? 'Select options' : 'Add to cart') : 'Out of stock'}
                        </button>
                        <CompareButton product={product} />
                        {user ? (
                            <button
                                type="button"
                                onClick={toggleFavorite}
                                className={`inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm ${
                                    isFavorite ? 'border-red-400 bg-red-50 text-red-600' : 'border-slate-300 hover:bg-slate-50'
                                }`}
                            >
                                <Heart className={`size-4 ${isFavorite ? 'fill-red-500' : ''}`} />
                                {isFavorite ? 'In favorites' : 'Add to favorites'}
                            </button>
                        ) : (
                            <Link href="/login" className="text-sm text-blue-600 hover:underline">
                                Log in to add to favorites
                            </Link>
                        )}
                    </div>

                    {product.specs.length > 0 && (
                        <table className="mb-6 w-full text-sm">
                            <tbody>
                                {product.specs.map((spec) => (
                                    <tr key={spec.name} className="border-b border-slate-100">
                                        <th className="w-1/3 py-2 text-left font-medium text-slate-500">{spec.name}</th>
                                        <td className="py-2">{spec.value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>

            {product.description && (
                <section className="mt-10">
                    <h2 className="mb-3 text-xl font-bold">Description</h2>
                    <p className="whitespace-pre-line text-slate-700">{product.description}</p>
                </section>
            )}

            {reviewsEnabled && (
                <section className="mt-10" id="reviews">
                    <h2 className="mb-4 text-xl font-bold">Reviews ({reviews.length})</h2>
                    {reviews.length === 0 ? (
                        <p className="mb-4 text-slate-500">There are no reviews yet. Be the first to write one!</p>
                    ) : (
                        <ul className="mb-6 space-y-3">
                            {reviews.map((review) => (
                                <ReviewItem key={review.id} review={review} slug={product.slug} canReply={user !== null} />
                            ))}
                        </ul>
                    )}
                    {user ? (
                        <ReviewForm slug={product.slug} />
                    ) : (
                        <p className="text-sm text-slate-600">
                            <Link href="/login" className="text-blue-600 hover:underline">
                                Log in
                            </Link>{' '}
                            to write a review.
                        </p>
                    )}
                </section>
            )}

            {related.length > 0 && (
                <section className="mt-10">
                    <h2 className="mb-4 text-xl font-bold">Related products</h2>
                    <ProductGrid products={related} />
                </section>
            )}
        </>
    );
}
