import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Contact, Folder, HandCoins, KeyIcon, LayoutGrid, ScrollText, User, User2, Users, DollarSign } from 'lucide-react';
import AppLogo from './app-logo';
import users from '@/routes/users';
import roles from '@/routes/roles';
import jabatans from '@/routes/jabatans';
import karyawans from '@/routes/karyawans';
import clients from '@/routes/clients';
import kontraks from '@/routes/kontraks';
import cashbons from '@/routes/cashbons';
import penggajians from '@/routes/penggajians';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const userManagement: NavItem[] = [
    {
        title: 'Users',
        href: users.index(),
        icon: User,
        permissions: ['users index'],
    },
    {
        title: 'Roles',
        href: roles.index(),
        icon: KeyIcon,
        permissions: ['roles index'],
    },
];

const employeeManagement: NavItem[] = [
    {
        title: 'Jabatan',
        href: jabatans.index(),
        icon: BookOpen,
        permissions: ['jabatans index'],
    },
    {
        title: 'Karyawan',
        href: karyawans.index(),
        icon: Users,
        permissions: ['karyawans index'],
    },
    {
        title: 'Cashbon',
        href: cashbons.index(),
        icon: HandCoins,
        permissions: ['cashbons index'],
    },
    
];

const clientManagement: NavItem[] = [
    {
        title: 'Clients',
        href: clients.index(),
        icon: Contact,
        permissions: ['clients index'],
    },
    {
        title: 'Kontrak',
        href: kontraks.index(),
        icon: ScrollText,
        permissions: ['kontraks index'],
    },
    {
        title: 'Penggajian',
        href: penggajians.index(),
        icon: DollarSign,
        permissions: ['penggajians index'],
    },
    
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/react-starter-kit',
    //     icon: Folder,
    // },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain section='Platform' items={mainNavItems} />
                <NavMain section='User Management' items={userManagement} />
                <NavMain section='Employee Management' items={employeeManagement} />
                <NavMain section='Client Management' items={clientManagement} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
