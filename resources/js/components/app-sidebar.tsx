import { Link } from '@inertiajs/react';
import {
    BadgePercent,
    Boxes,
    Coins,
    FileText,
    LayoutGrid,
    ListChecks,
    MessageSquare,
    Package,
    Settings,
    ShoppingBag,
    Store,
    Tags,
    Truck,
    Users,
    Warehouse,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

type NavGroup = { label: string; items: NavItem[] };

const groups: NavGroup[] = [
    {
        label: 'Overview',
        items: [{ title: 'Dashboard', href: '/admin', icon: LayoutGrid }],
    },
    {
        label: 'Sales',
        items: [
            { title: 'Orders', href: '/admin/orders', icon: ShoppingBag },
            { title: 'Customers', href: '/admin/users', icon: Users },
            { title: 'Promo codes', href: '/admin/promo-codes', icon: BadgePercent },
            { title: 'Reviews', href: '/admin/reviews', icon: MessageSquare },
        ],
    },
    {
        label: 'Catalog',
        items: [
            { title: 'Products', href: '/admin/products', icon: Package },
            { title: 'Categories', href: '/admin/categories', icon: Boxes },
            { title: 'Attributes', href: '/admin/attributes', icon: Tags },
            { title: 'Stock', href: '/admin/stocks', icon: Warehouse },
            { title: 'Price changes', href: '/admin/price-batches', icon: ListChecks },
        ],
    },
    {
        label: 'Store',
        items: [
            { title: 'Pages', href: '/admin/pages', icon: FileText },
            { title: 'Delivery & payment', href: '/admin/methods', icon: Truck },
            { title: 'Currencies', href: '/admin/currencies', icon: Coins },
            { title: 'Settings', href: '/admin/settings', icon: Settings },
        ],
    },
];

export function AppSidebar() {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {groups.map((group) => (
                    <SidebarGroup key={group.label} className="px-2 py-0">
                        <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                        <SidebarMenu>
                            {group.items.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={
                                            item.href === '/admin'
                                                ? isCurrentUrl(item.href)
                                                : isCurrentUrl(item.href, undefined, true)
                                        }
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild tooltip={{ children: 'View store' }}>
                            <Link href="/">
                                <Store />
                                <span>View store</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
