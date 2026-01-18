import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import DeleteButton from '@/components/delete-button';
import { Edit2Icon, PlusCircle } from 'lucide-react';
import { BreadcrumbItem, Jabatan, SharedData, User } from '@/types';
import { toast } from 'sonner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import jabatans from '@/routes/jabatans';


interface Props {
    jabatans: {
        data: Jabatan[];
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
        title: 'Jabatan',
        href: jabatans.index.url(),
    },
];

export default function JabatanPage({ jabatans, filters, flash }: Props) {
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
        router.get('/jabatans', { search }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Jabatan" />

            <div className="p-4 space-y-4">

                {/* Search Bar */}
                <div className='flex space-x-1'>
                    <form onSubmit={handleSearch} className="flex gap-2 w-full md:w-1/3">
                        <Input
                            placeholder="Search jabatans..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button variant='outline' type="submit">Search</Button>
                    </form>
                    {hasAnyPermission(["jabatans create"]) && (
                        <Link href="/jabatans/create">
                            <Button variant='default' className='group flex items-center'>
                                <PlusCircle className='group-hover:rotate-90 transition-all' />
                                Add Jabatans
                            </Button>
                        </Link>
                    )}
                </div>

                {/* User Table */}
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama Jabatan</TableHead>
                            <TableHead>Deskripsi</TableHead>
                            <TableHead>Gaji</TableHead>
                            <TableHead>BPJS</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {jabatans.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={8} className="h-[65vh]  text-center">
                                    Belum Ada Data Jabatan.
                                </TableCell>
                            </TableRow>
                        ) : (
                            jabatans.data.map((jabatan) => (
                                <TableRow key={jabatan.id}>
                                    <TableCell>{jabatan.nama_jabatan}</TableCell>
                                    <TableCell>{jabatan.deskripsi}</TableCell>
                                    <TableCell>
                                        {new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR',
                                            minimumFractionDigits: 0,
                                        }).format(Number(jabatan.gaji ?? 0))}
                                    </TableCell>
                                    <TableCell>
                                        {new Intl.NumberFormat('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR',
                                            minimumFractionDigits: 0,
                                        }).format(Number(jabatan.bpjs ?? 0))}
                                    </TableCell>
                                    <TableCell className="space-x-2">
                                        {hasAnyPermission(["jabatans edit"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Link href={`/jabatans/${jabatan.id}/edit`}>
                                                        <Button variant="outline" size="sm" className='hover:bg-blue-200 hover:text-blue-600'> <Edit2Icon /></Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Edit
                                                </TooltipContent>
                                            </Tooltip>
                                        )}

                                        {hasAnyPermission(["jabatans delete"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <DeleteButton id={jabatan.id} featured='jabatans' />
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
                    {jabatans.links.map((link, i) => (
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
