import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Eye } from 'lucide-react';
import { BreadcrumbItem, Kontrak, SharedData } from '@/types';
import { toast } from 'sonner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"



interface Penggajian {
    id: number;
    kontrak_id: number;
    periode: string;
    status: string;
    created_at: string;
    kontrak: Kontrak;
}

interface Props {
    penggajians: {
        data: Penggajian[];
        links: any[];
    };
    filters: {
        status?: string;
    };
    flash?: {
        success?: string;
        error?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Penggajians',
        href: '/penggajians',
    },
];

export default function PenggajianIndexPage({ penggajians, filters, flash }: Props) {
    const user = usePage<SharedData>().props.auth.user;
    const [status, setStatus] = useState(filters.status || '');
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

    const handleStatusFilter = (value: string) => {
        setStatus(value);
        router.get('/penggajians', 
            value !== 'all' ? { status: value } : {}, 
            { preserveState: true }
        );
    };

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const getStatusBadge = (status: string) => {
        return status === 'dibayar' ? (
            <Badge className='bg-green-500'>Dibayar</Badge>
        ) : (
            <Badge className='bg-yellow-500'>Belum Dibayar</Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penggajians" />

            <div className="p-4 space-y-4">

                {/* Filter Bar */}
                <div className='flex items-center gap-4'>
                    <div className='w-48'>
                        <Select value={status} onValueChange={handleStatusFilter}>
                            <SelectTrigger>
                                <SelectValue placeholder="Filter by status..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="belum_dibayar">Belum Dibayar</SelectItem>
                                <SelectItem value="dibayar">Dibayar</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Penggajian Table */}
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Periode</TableHead>
                            <TableHead>Kontrak</TableHead>
                            <TableHead>Client</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Dibuat</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {penggajians.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={6} className="h-[65vh] text-center">
                                    Belum Ada Data Penggajians.
                                </TableCell>
                            </TableRow>
                        ) : (
                            penggajians.data.map((penggajian) => (
                                <TableRow key={penggajian.id}>
                                    <TableCell className='font-medium'>
                                        {formatDate(penggajian.periode)}
                                    </TableCell>
                                    <TableCell>
                                        {penggajian.kontrak.judul}
                                    </TableCell>
                                    <TableCell>
                                        {penggajian.kontrak.client?.nama_client || '-'}
                                    </TableCell>
                                    <TableCell>
                                        {getStatusBadge(penggajian.status)}
                                    </TableCell>
                                    <TableCell className='text-sm'>
                                        {formatDate(penggajian.created_at)}
                                    </TableCell>
                                    <TableCell className="space-x-2 flex">
                                        {hasAnyPermission(["penggajians show"]) && (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Link href={`/kontraks/${penggajian.kontrak_id}/penggajians/${penggajian.id}`}>
                                                        <Button variant='ghost' size='sm' className='p-0 w-10 h-10'>
                                                            <Eye className='w-4 h-4' />
                                                        </Button>
                                                    </Link>
                                                </TooltipTrigger>
                                                <TooltipContent>View</TooltipContent>
                                            </Tooltip>
                                        )}
                                    </TableCell>
                                </TableRow>
                            )))}
                    </TableBody>
                </Table>

                {/* Pagination */}
                <div className="flex gap-1">
                    {penggajians.links.map((link, i) => (
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
