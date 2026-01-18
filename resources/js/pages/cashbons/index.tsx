import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import DeleteButton from '@/components/delete-button';
import { Edit2Icon, PlusCircle } from 'lucide-react';
import { BreadcrumbItem, Cashbon, SharedData } from '@/types';
import { toast } from 'sonner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import cashbons from '@/routes/cashbons';
import { Badge } from '@/components/ui/badge';


interface Props {
    cashbons: {
        data: Cashbon[];
        links: any[];
    };
    filters: {
        search?: string;
    };
    flash?: {
        success?: string;
        error?: string;
    };
}


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cashbon',
        href: cashbons.index.url(),
    },
];

export default function CashbonPage({ cashbons: cashbonPagination, filters, flash }: Props) {
    usePage<SharedData>().props.auth.user;

    const [search, setSearch] = useState(filters.search || '');
    const [shownMessages] = useState(new Set());

    useEffect(() => {
        if (flash?.success && !shownMessages.has(flash.success)) {
            toast.success(flash.success);
            shownMessages.add(flash.success);
        }

        if (flash?.error && !shownMessages.has(flash.error)) {
            toast.error(flash.error);
            shownMessages.add(flash.error);
        }
    }, [flash?.success, flash?.error]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/cashbons', { search }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cashbon" />

            <div className="p-4 space-y-4">

                {/* Search Bar */}
                <div className='flex space-x-1'>
                    <form onSubmit={handleSearch} className="flex gap-2 w-full md:w-1/3">
                        <Input
                            placeholder="Search cashbons..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button variant='outline' type="submit">Search</Button>
                    </form>
                    {hasAnyPermission(["cashbons create"]) && (
                        <Link href="/cashbons/create">
                            <Button variant='default' className='group flex items-center'>
                                <PlusCircle className='group-hover:rotate-90 transition-all' />
                                Add Cashbon
                            </Button>
                        </Link>
                    )}
                </div>

                {/* User Table */}
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Karyawan</TableHead>
                            <TableHead>Jabatan</TableHead>
                            <TableHead>Jumlah</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Keterangan</TableHead>
                            <TableHead>Dibuat</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {cashbonPagination.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={8} className="h-[65vh]  text-center">
                                    Belum Ada Data Cashbon.
                                </TableCell>
                            </TableRow>
                        ) : (
                            cashbonPagination.data.map((cashbon) => (
                                <TableRow key={cashbon.id}>
                                    <TableCell>{cashbon.karyawan?.nama ?? '-'}</TableCell>
                                    <TableCell>{cashbon.karyawan?.jabatan?.nama_jabatan ?? '-'}</TableCell>
                                    <TableCell>
                                        {new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR',
                                            minimumFractionDigits: 0,
                                        }).format(Number(cashbon.jumlah ?? 0))}
                                    </TableCell>
                                    <TableCell>
                                        <Badge>
                                            {cashbon.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{cashbon.keterangan}</TableCell>
                                    <TableCell>{new Date(cashbon.created_at).toLocaleDateString('id-ID')}</TableCell>
                                    <TableCell className="space-x-2">
                                        {(() => {
                                            const createdAt = new Date(cashbon.created_at).getTime();
                                            const now = Date.now();
                                            const isLocked = Number.isFinite(createdAt) && now - createdAt > 24 * 60 * 60 * 1000;

                                            if (isLocked) return null;

                                            return (
                                                <>
                                                    {hasAnyPermission(["cashbons edit"]) && (
                                                        <Tooltip>
                                                            <TooltipTrigger>
                                                                <Link href={`/cashbons/${cashbon.id}/edit`}>
                                                                    <Button variant="outline" size="sm" className='hover:bg-blue-200 hover:text-blue-600'> <Edit2Icon /></Button>
                                                                </Link>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Edit
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}

                                                    {hasAnyPermission(["cashbons delete"]) && (
                                                        <Tooltip>
                                                            <TooltipTrigger>
                                                                <DeleteButton id={cashbon.id} featured='cashbons' />
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Delete
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </>
                                            );
                                        })()}
                                    </TableCell>
                                </TableRow>
                            )))}
                    </TableBody>
                </Table>

                <div className="flex gap-1">
                    {cashbonPagination.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`px-3 py-1 flex justify-center items-center border rounded-md ${link.active ? 'bg-black text-white text-sm' : 'text-sm'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>

            </div>
        </AppLayout>
    );
}
