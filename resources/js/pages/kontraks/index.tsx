import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import DeleteButton from '@/components/delete-button';
import { Edit2Icon, FileTextIcon, PlusCircle, UserCheck, Eye, DollarSign } from 'lucide-react';
import { BreadcrumbItem, Kontrak, SharedData, User } from '@/types';
import { toast } from 'sonner';
import users from '@/routes/users';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import kontraks from '@/routes/kontraks';


interface Props {
    kontraks: {
        data: Kontrak[];
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
        title: 'Kontraks',
        href: kontraks.index().url,
    },
];

export default function KontrakPage({ kontraks, filters, flash }: Props) {
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
        router.get('/kontraks', { search }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kontraks" />

            <div className="p-4 space-y-4">

                {/* Search Bar */}
                <div className='flex space-x-1'>
                    <form onSubmit={handleSearch} className="flex gap-2 w-full md:w-1/3">
                        <Input
                            placeholder="Search kontraks..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button variant='outline' type="submit">Search</Button>
                    </form>
                    {hasAnyPermission(["kontraks create"]) && (
                        <Link href="/kontraks/create">
                            <Button variant='default' className='group flex items-center'>
                                <PlusCircle className='group-hover:rotate-90 transition-all' />
                                Add Kontraks
                            </Button>
                        </Link>
                    )}
                </div>

                {/* User Table */}
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Client</TableHead>
                            <TableHead>Judul</TableHead>
                            {/* <TableHead>Deskripsi</TableHead> */}
                            <TableHead>Tanggal Mulai</TableHead>
                            <TableHead>Tanggal Selesai</TableHead>
                            <TableHead>Tanggal Gajian</TableHead>
                            <TableHead>Total Biaya</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {kontraks.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={8} className="h-[65vh]  text-center">
                                    Belum Ada Data Kontraks.
                                </TableCell>
                            </TableRow>
                        ) : (
                            kontraks.data.map((kontrak) => (
                                <TableRow key={kontrak.id}>
                                    <TableCell>{kontrak.client.nama_client}</TableCell>
                                    <TableCell>{kontrak.judul}</TableCell>
                                    {/* <TableCell>{kontrak.deskripsi}</TableCell> */}
                                    <TableCell>{kontrak.tanggal_mulai}</TableCell>
                                    <TableCell>{kontrak.tanggal_selesai}</TableCell>
                                    <TableCell>{kontrak.tanggal_gajian}</TableCell>
                                    <TableCell>Rp {parseInt(kontrak.total_biaya.toString()).toLocaleString('id-ID')}</TableCell>
                                    <TableCell><Badge>{kontrak.status}</Badge></TableCell>

                                    <TableCell className="space-x-2">
                                        {hasAnyPermission(["kontraks index"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Link href={`/kontraks/${kontrak.id}`}>
                                                        <Button variant="outline" size="sm" className='hover:bg-green-200 hover:text-green-600'> <Eye /></Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Detail
                                                </TooltipContent>
                                            </Tooltip>
                                        )}
                                        {hasAnyPermission(["kontraks edit"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Link href={`/kontraks/${kontrak.id}/edit`}>
                                                        <Button variant="outline" size="sm" className='hover:bg-blue-200 hover:text-blue-600'> <Edit2Icon /></Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Edit
                                                </TooltipContent>
                                            </Tooltip>
                                        )}
                                        {hasAnyPermission(["kontraks dokumens index"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Link href={`/kontraks/${kontrak.id}/dokumens`}>
                                                        <Button variant="outline" size="sm" className='hover:bg-blue-200 hover:text-blue-600'> <FileTextIcon /></Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Dokumen
                                                </TooltipContent>
                                            </Tooltip>
                                        )}

                                        {hasAnyPermission(["kontraks karyawans index"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Link href={`/kontraks/${kontrak.id}/karyawans`}>
                                                        <Button variant="outline" size="sm" className='hover:bg-blue-200 hover:text-blue-600'> <UserCheck /></Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Karyawan
                                                </TooltipContent>
                                            </Tooltip>
                                        )}

                                        {hasAnyPermission(["penggajians index"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <Link href={`/kontraks/${kontrak.id}/penggajians`}>
                                                        <Button variant="outline" size="sm" className='hover:bg-purple-200 hover:text-purple-600'> <DollarSign /></Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Penggajian
                                                </TooltipContent>
                                            </Tooltip>
                                        )}

                                        {hasAnyPermission(["kontraks delete"]) && (
                                            <Tooltip>
                                                <TooltipTrigger>
                                                    <DeleteButton id={kontrak.id} featured='kontraks' />
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
                    {kontraks.links.map((link, i) => (
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
