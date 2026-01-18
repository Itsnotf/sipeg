import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import DeleteButton from '@/components/delete-button';
import { Edit2Icon, PlusCircle } from 'lucide-react';
import { BreadcrumbItem, Karyawan, SharedData, User } from '@/types';
import { toast } from 'sonner';
import users from '@/routes/users';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import karyawans from '@/routes/karyawans';


interface Props {
    karyawans: {
        data: Karyawan[];
        links: any[];
    };
    filters: {
        search?: string;
    };
    flash?: {
        success?: string;
    };
}


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Karyawans',
        href: karyawans.index().url,
    },
];

export default function KaryawanPage({ karyawans, filters, flash }: Props) {
    const user = usePage<SharedData>().props.auth.user;
    console.log(karyawans);
    

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
        router.get('/karyawans', { search }, { preserveState: true });
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
                    {hasAnyPermission(["karyawans create"]) && (
                        <Link href="/karyawans/create">
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
                            <TableHead>Nama</TableHead>
                            <TableHead>Jabatan</TableHead>
                            <TableHead>Nik</TableHead>
                            <TableHead>Alamat</TableHead>
                            <TableHead>Jenis Kelamin</TableHead>
                            <TableHead>Tanggal Lahir</TableHead>
                            <TableHead>Kontak</TableHead>
                            <TableHead>Status</TableHead>
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
                                    <TableCell>{karyawan.nama}</TableCell>
                                    <TableCell>{karyawan.jabatan.nama_jabatan}</TableCell>
                                    <TableCell>{karyawan.nik}</TableCell>
                                    <TableCell>{karyawan.alamat}</TableCell>
                                    <TableCell>{karyawan.jenis_kelamin}</TableCell>
                                    <TableCell>{karyawan.tanggal_lahir}</TableCell>
                                    <TableCell>{karyawan.no_hp}</TableCell>
                                    <TableCell><Badge>{karyawan.status}</Badge></TableCell>
                                    <TableCell className="space-x-2">
                                        {hasAnyPermission(["karyawans edit"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Link href={`/karyawans/${karyawan.id}/edit`}>
                                                        <Button variant="outline" size="sm" className='hover:bg-blue-200 hover:text-blue-600'> <Edit2Icon /></Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Edit
                                                </TooltipContent>
                                            </Tooltip>
                                        )}

                                        {hasAnyPermission(["karyawans delete"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <DeleteButton id={karyawan.id} featured='karyawans' />
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
