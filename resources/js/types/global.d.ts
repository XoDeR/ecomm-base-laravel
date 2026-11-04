import type { Auth } from '@/types/auth';
import type { Shop } from '@/types/shop';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            shop: Shop;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
