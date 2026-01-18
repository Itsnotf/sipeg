import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import DeleteButton from '@/components/delete-button';
import { Edit2Icon, PlusCircle } from 'lucide-react';
import { BreadcrumbItem, KontrakDokumen, KontrakKaryawan, SharedData } from '@/types';
import { toast } from 'sonner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import kontraks from '@/routes/kontraks';
import DeleteButtonChild from '@/components/delete-button-child';


interface Props {
    kontrak_id: number | string
    karyawans: {
        data: KontrakKaryawan[];
        links: any[];
    };
    filters: {
        search?: string;
    };
    flash?: {
        success?: string;
    };
}


export default function KontrakKaryawanPage({ kontrak_id, karyawans, filters, flash }: Props) {

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Kontraks',
            href: kontraks.index.url(),
        },
        {
            title: 'Karyawans',
            href: `/kontraks/${kontrak_id}/karyawans`,
        },
    ];

    const user = usePage<SharedData>().props.auth.user;

    const [search, setSearch] = useState(filters.search || '');
    const [shownMessages] = useState(new Set());

    useEffect(() => {
        if (flash?.success && !shownMessages.has(flash.success)) {
            toast.success(flash.success);
            shownMessages.add(flash.success);
        }
    }, [flash?.success]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(`/kontraks/${kontrak_id}/karyawans`, { search }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Karyawans" />

            <div className="p-4 space-y-4">

                {/* Search Bar */}
                <div className='flex space-x-1'>
                    <form onSubmit={handleSearch} className="flex gap-2 w-full md:w-1/3">
                        <Input
                            placeholder="Search karyawans..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button variant='outline' type="submit">Search</Button>
                    </form>
                    {hasAnyPermission(["kontraks karyawans create"]) && (
                        <Link href={`/kontraks/${kontrak_id}/karyawans/create`}>
                            <Button variant='default' className='group flex items-center'>
                                <PlusCircle className='group-hover:rotate-90 transition-all' />
                                Add Karyawans
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Karyawan Table */}
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama Kontrak</TableHead>
                            <TableHead>Nama Karyawan</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {karyawans.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={8} className="h-[65vh]  text-center">
                                    Belum Ada Data Karyawans.
                                </TableCell>
                            </TableRow>
                        ) : (
                            karyawans.data.map((karyawan) => (
                                <TableRow key={karyawan.id}>
                                    <TableCell>{karyawan.kontrak.judul}</TableCell>
                                    <TableCell>{karyawan.karyawan.nama}</TableCell>

                                    <TableCell className="space-x-2">
                                       

                                        {hasAnyPermission(["kontraks karyawans delete"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <DeleteButtonChild id={karyawan.kontrak_id} featured='kontraks' child='karyawans' child_id={karyawan.id} />
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Delete
                                                </TooltipContent>
                                            </Tooltip>
                                        )}
                                    </TableCell>
                                </TableRow>
                            )))}
                    </TableBody>
                </Table>

                <div className="flex gap-1">
                    {karyawans.links.map((link, i) => (
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
