export type CategoryNode = {
    id: number;
    name: string;
    url: string;
    children: CategoryNode[];
};

export type Shop = {
    name: string;
    logo: string | null;
    phone: string;
    email: string;
    address: string;
    footerAbout: string;
    phoneMask: string;
    currency: { symbol: string; code: string };
    compareEnabled: boolean;
    socialLinks: { name: string; slug: string; url: string }[];
    footerPages: { title: string; slug: string }[];
    categories: CategoryNode[];
    cartCount: number;
    compareCount: number;
};

export type ProductCard = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    vendor: string | null;
    image: string | null;
    price: number;
    final_price: number;
    has_discount: boolean;
    in_stock: boolean;
    in_compare: boolean;
};

export type Breadcrumb = { title: string; href?: string };

export type PaginationLink = { url: string | null; label: string; active: boolean };

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type SeoMeta = {
    title: string;
    description?: string | null;
    keywords?: string | null;
};
