import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { ArrowLeft, CheckCircle2, Clock } from 'lucide-react';
import { BreadcrumbItem, SharedData, Kontrak } from '@/types';
import { toast } from 'sonner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import kontraks from '@/routes/kontraks';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"

interface PenggajianDetail {
    id: number;
    karyawan_id: number;
    gaji_pokok: string;
    bpjs: string;
    potongan_cashbon: string;
    total_gaji: string;
    karyawan?: {
        id: number;
        nama: string;
        jabatan?: {
            nama_jabatan: string;
        }
    }
}

interface Penggajian {
    id: number;
    kontrak_id: number;
    periode: string;
    status: string;
    created_at: string;
    updated_at: string;
    penggajianDetails: PenggajianDetail[];
}

interface Props {
    kontrak_id: number | string;
    kontrak: Kontrak;
    penggajian: Penggajian;
    summary: {
        total_gaji: number;
        total_bpjs: number;
        total_cashbon: number;
        karyawan_count: number;
    };
}

export default function PenggajianShowPage({ kontrak_id, kontrak, penggajian, summary }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Kontraks',
            href: kontraks.index.url(),
        },
        {
            title: kontrak.judul,
            href: `/kontraks/${kontrak_id}`,
        },
        {
            title: 'Penggajians',
            href: `/kontraks/${kontrak_id}/penggajians`,
        },
        {
            title: formatDate(penggajian.periode),
            href: `/kontraks/${kontrak_id}/penggajians/${penggajian.id}`,
        },
    ];

    const user = usePage<SharedData>().props.auth.user;
    const [isUpdating, setIsUpdating] = useState(false);
    const [showStatusDialog, setShowStatusDialog] = useState(false);
    const [newStatus, setNewStatus] = useState(penggajian.status);

    const handleStatusUpdate = () => {
        setIsUpdating(true);
        router.put(`/kontraks/${kontrak_id}/penggajians/${penggajian.id}`, 
            { status: newStatus },
            {
                onFinish: () => {
                    setIsUpdating(false);
                    setShowStatusDialog(false);
                },
            }
        );
    };

    function formatDate(date: string) {
        return new Date(date).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatCurrency(value: string | number) {
        const num = typeof value === 'string' ? parseFloat(value) : value;
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(num);
    }

    const getStatusBadge = (status: string) => {
        return status === 'dibayar' ? (
            <div className='flex items-center gap-2'>
                <CheckCircle2 className='w-5 h-5 text-green-500' />
                <Badge className='bg-green-500'>Dibayar</Badge>
            </div>
        ) : (
            <div className='flex items-center gap-2'>
                <Clock className='w-5 h-5 text-yellow-500' />
                <Badge className='bg-yellow-500'>Belum Dibayar</Badge>
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Penggajian - ${formatDate(penggajian.periode)}`} />

            <div className="p-4 space-y-6">

                {/* Header Section */}
                <div className='flex items-center justify-between'>
                    <div className='flex items-center gap-4'>
                        <Link href={`/kontraks/${kontrak_id}/penggajians`}>
                            <Button variant='outline' size='icon'>
                                <ArrowLeft className='w-4 h-4' />
                            </Button>
                        </Link>
                        <div>
                            <h1 className='text-2xl font-bold'>{formatDate(penggajian.periode)}</h1>
                            <p className='text-gray-500'>{kontrak.judul}</p>
                        </div>
                    </div>
                    <div className='flex items-center gap-4'>
                        {getStatusBadge(penggajian.status)}
                        {hasAnyPermission(["penggajians update"]) && (
                            <Button 
                                onClick={() => setShowStatusDialog(true)}
                                variant='outline'
                            >
                                Ubah Status
                            </Button>
                        )}
                    </div>
                </div>

                {/* Summary Cards */}
                <div className='grid grid-cols-4 gap-4'>
                    <div className='border rounded-lg p-4'>
                        <p className='text-sm text-gray-600'>Total Gaji Bersih</p>
                        <p className='text-2xl font-bold mt-2'>{formatCurrency(summary.total_gaji)}</p>
                    </div>
                    <div className='border rounded-lg p-4'>
                        <p className='text-sm text-gray-600'>Total BPJS</p>
                        <p className='text-2xl font-bold mt-2'>{formatCurrency(summary.total_bpjs)}</p>
                    </div>
                    <div className='border rounded-lg p-4'>
                        <p className='text-sm text-gray-600'>Total Cashbon</p>
                        <p className='text-2xl font-bold mt-2'>{formatCurrency(summary.total_cashbon)}</p>
                    </div>
                    <div className='border rounded-lg p-4'>
                        <p className='text-sm text-gray-600'>Jumlah Karyawan</p>
                        <p className='text-2xl font-bold mt-2'>{summary.karyawan_count}</p>
                    </div>
                </div>

                {/* Detail Table */}
                <div className='border rounded-lg p-4'>
                    <h2 className='text-xl font-bold mb-4'>Detail Penggajian</h2>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>No</TableHead>
                                <TableHead>Nama Karyawan</TableHead>
                                <TableHead>Jabatan</TableHead>
                                <TableHead className='text-right'>Gaji Pokok</TableHead>
                                <TableHead className='text-right'>BPJS</TableHead>
                                <TableHead className='text-right'>Cashbon</TableHead>
                                <TableHead className='text-right'>Total Gaji</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {penggajian.penggajianDetails && penggajian.penggajianDetails.length > 0 ? (
                                <>
                                    {penggajian.penggajianDetails.map((detail, index) => (
                                        <TableRow key={detail.id}>
                                            <TableCell>{index + 1}</TableCell>
                                            <TableCell className='font-medium'>{detail.karyawan?.nama}</TableCell>
                                            <TableCell>{detail.karyawan?.jabatan?.nama_jabatan || '-'}</TableCell>
                                            <TableCell className='text-right'>{formatCurrency(detail.gaji_pokok)}</TableCell>
                                            <TableCell className='text-right'>({formatCurrency(detail.bpjs)})</TableCell>
                                            <TableCell className='text-right'>({formatCurrency(detail.potongan_cashbon)})</TableCell>
                                            <TableCell className='text-right font-bold'>{formatCurrency(detail.total_gaji)}</TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow className='bg-gray-50 font-bold'>
                                        <TableCell colSpan={3}>TOTAL</TableCell>
                                        <TableCell className='text-right'>{formatCurrency(summary.total_gaji + summary.total_bpjs + summary.total_cashbon)}</TableCell>
                                        <TableCell className='text-right'>({formatCurrency(summary.total_bpjs)})</TableCell>
                                        <TableCell className='text-right'>({formatCurrency(summary.total_cashbon)})</TableCell>
                                        <TableCell className='text-right'>{formatCurrency(summary.total_gaji)}</TableCell>
                                    </TableRow>
                                </>
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-8">
                                        Belum ada data detail penggajian
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

            </div>

            {/* Status Update Dialog */}
            <Dialog open={showStatusDialog} onOpenChange={setShowStatusDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ubah Status Penggajian</DialogTitle>
                        <DialogDescription>
                            Pilih status baru untuk penggajian periode {formatDate(penggajian.periode)}
                        </DialogDescription>
                    </DialogHeader>
                    <div className='space-y-4 py-4'>
                        <Select value={newStatus} onValueChange={setNewStatus}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="belum_dibayar">Belum Dibayar</SelectItem>
                                <SelectItem value="dibayar">Dibayar</SelectItem>
                            </SelectContent>
                        </Select>
                        <div className='flex justify-end gap-2 pt-4'>
                            <Button 
                                variant='outline' 
                                onClick={() => setShowStatusDialog(false)}
                                disabled={isUpdating}
                            >
                                Batal
                            </Button>
                            <Button 
                                onClick={handleStatusUpdate}
                                disabled={isUpdating || newStatus === penggajian.status}
                            >
                                {isUpdating ? 'Updating...' : 'Simpan'}
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

        </AppLayout>
    );
}
