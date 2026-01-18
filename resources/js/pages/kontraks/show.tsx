import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, usePage } from '@inertiajs/react';
import { BreadcrumbItem, SharedData, Kontrak, KontrakDokumen, KontrakKaryawan } from '@/types';
import { Edit2Icon, FileTextIcon, UserCheck, Trash2, PlusCircle, DollarSign } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip"
import hasAnyPermission from '@/lib/utils';
import kontraks from '@/routes/kontraks';
import DeleteButton from '@/components/delete-button';


interface Props {
    kontrak: Kontrak & {
        client: any;
        kontrak_dokumens?: KontrakDokumen[];
        kontrak_karyawans?: (KontrakKaryawan & { karyawan: any })[];
        penggajians?: any[];
    };
    penggajian_summary?: {
        total_penggajian: number;
        total_biaya: number;
        keuntungan: number;
    };
}

export default function KontrakShowPage({ kontrak, penggajian_summary }: Props) {
    const user = usePage<SharedData>().props.auth.user;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontraks', href: kontraks.index().url },
        { title: kontrak.judul, href: '#' },
    ];

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Berjalan':
                return 'bg-blue-100 text-blue-800';
            case 'Selesai':
                return 'bg-green-100 text-green-800';
            case 'Pending':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${kontrak.judul} - Detail`} />

            <div className="p-4 space-y-6">
                {/* Header Section */}
                <div className="flex justify-between items-start">
                    <div>
                        <h1 className="text-3xl font-bold mb-2">{kontrak.judul}</h1>
                        <p className="text-gray-600">
                            <span className="font-medium">{kontrak.client.nama_client}</span>
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {hasAnyPermission(["kontraks edit"]) && (
                            <Tooltip>
                                <TooltipTrigger>
                                    <Link href={`/kontraks/${kontrak.id}/edit`}>
                                        <Button variant="outline" size="sm">
                                            <Edit2Icon className="w-4 h-4" />
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent>Edit</TooltipContent>
                            </Tooltip>
                        )}
                        {hasAnyPermission(["kontraks delete"]) && (
                            <Tooltip>
                                <TooltipTrigger>
                                    <DeleteButton id={kontrak.id} featured='kontraks' />
                                </TooltipTrigger>
                                <TooltipContent>Delete</TooltipContent>
                            </Tooltip>
                        )}
                    </div>
                </div>

                {/* Quick Info Grid */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-600 mb-2">Status</p>
                            <Badge className={getStatusColor(kontrak.status)}>
                                {kontrak.status}
                            </Badge>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-600 mb-2">Tanggal Mulai</p>
                            <p className="font-medium">{kontrak.tanggal_mulai}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-600 mb-2">Tanggal Selesai</p>
                            <p className="font-medium">{kontrak.tanggal_selesai}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-600 mb-2">Gajian Tgl</p>
                            <p className="font-medium text-lg">{kontrak.tanggal_gajian}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Deskripsi */}
                {kontrak.deskripsi && (
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-sm text-gray-600 mb-2">Deskripsi</p>
                            <p className="text-gray-800">{kontrak.deskripsi}</p>
                        </CardContent>
                    </Card>
                )}

                {/* Financial Summary Cards */}
                {penggajian_summary && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Card className="border-l-4 border-l-blue-600">
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <p className="text-sm text-gray-600 mb-2">Total Biaya Kontrak</p>
                                    <p className="text-3xl font-bold text-blue-600">{formatCurrency(penggajian_summary.total_biaya)}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="border-l-4 border-l-red-600">
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <p className="text-sm text-gray-600 mb-2">Total Penggajian</p>
                                    <p className="text-3xl font-bold text-red-600">{formatCurrency(penggajian_summary.total_penggajian)}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card className={`border-l-4 ${penggajian_summary.keuntungan >= 0 ? 'border-l-green-600' : 'border-l-orange-600'}`}>
                            <CardContent className="pt-6">
                                <div className="text-center">
                                    <p className="text-sm text-gray-600 mb-2">Keuntungan/Rugi</p>
                                    <p className={`text-3xl font-bold ${penggajian_summary.keuntungan >= 0 ? 'text-green-600' : 'text-orange-600'}`}>
                                        {formatCurrency(penggajian_summary.keuntungan)}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Content Tabs - Simplified to 3 tabs */}
                <Tabs defaultValue="penggajian" className="w-full">
                    <TabsList className="grid w-full grid-cols-3">
                        <TabsTrigger value="penggajian">
                            Penggajian ({kontrak.penggajians?.length || 0})
                        </TabsTrigger>
                        <TabsTrigger value="dokumen">
                            Dokumen ({kontrak.kontrak_dokumens?.length || 0})
                        </TabsTrigger>
                        <TabsTrigger value="karyawan">
                            Karyawan ({kontrak.kontrak_karyawans?.length || 0})
                        </TabsTrigger>
                    </TabsList>

                    {/* Tab 1: Penggajian */}
                    <TabsContent value="penggajian" className="space-y-4">
                        <div className="flex justify-between items-center mb-4">
                            {hasAnyPermission(["penggajians index"]) && (
                                <Link href={`/kontraks/${kontrak.id}/penggajians`}>
                                    <Button size="sm" className='flex items-center gap-2'>
                                        <DollarSign className='w-4 h-4' />
                                        Lihat Semua Penggajian
                                    </Button>
                                </Link>
                            )}
                        </div>

                        {kontrak.penggajians && kontrak.penggajians.length === 0 ? (
                            <Card>
                                <CardContent className="py-8 text-center text-gray-500">
                                    Belum ada penggajian
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Periode</TableHead>
                                            <TableHead className="text-right">Total Gaji</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Action</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {kontrak.penggajians?.map((penggajian: any) => {
                                            // Calculate total gaji from penggajianDetails array
                                            let totalGaji = 0;
                                            
                                            if (Array.isArray(penggajian.penggajianDetails)) {
                                                totalGaji = penggajian.penggajianDetails.reduce((sum: number, detail: any) => {
                                                    const gajiValue = parseFloat(detail.total_gaji || '0');
                                                    return sum + gajiValue;
                                                }, 0);
                                            }

                                            return (
                                                <TableRow key={penggajian.id}>
                                                    <TableCell>{new Date(penggajian.periode).toLocaleDateString('id-ID', { year: 'numeric', month: 'long' })}</TableCell>
                                                    <TableCell className="text-right font-medium">{formatCurrency(totalGaji)}</TableCell>
                                                    <TableCell>
                                                        <Badge className={penggajian.status === 'dibayar' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}>
                                                            {penggajian.status === 'dibayar' ? 'Dibayar' : 'Belum Dibayar'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        {hasAnyPermission(["penggajians show"]) && (
                                                            <Tooltip>
                                                                <TooltipTrigger>
                                                                    <Link href={`/kontraks/${kontrak.id}/penggajians/${penggajian.id}`}>
                                                                        <Button variant="outline" size="sm">
                                                                            Lihat Detail
                                                                        </Button>
                                                                    </Link>
                                                                </TooltipTrigger>
                                                                <TooltipContent>Lihat Detail Penggajian</TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </Card>
                        )}
                    </TabsContent>

                    {/* Tab 2: Dokumen */}
                    <TabsContent value="dokumen" className="space-y-4">
                        <div className="flex justify-between items-center">
                            <h3 className="text-lg font-semibold">Dokumen Kontrak</h3>
                            {hasAnyPermission(["kontraks dokumens create"]) && (
                                <Link href={`/kontraks/${kontrak.id}/dokumens/create`}>
                                    <Button size="sm" className='flex items-center gap-2'>
                                        <PlusCircle className='w-4 h-4' />
                                        Tambah Dokumen
                                    </Button>
                                </Link>
                            )}
                        </div>

                        {kontrak.kontrak_dokumens && kontrak.kontrak_dokumens.length === 0 ? (
                            <Card>
                                <CardContent className="py-8 text-center text-gray-500">
                                    Belum ada dokumen
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nama Dokumen</TableHead>
                                            <TableHead>File</TableHead>
                                            <TableHead>Tanggal Upload</TableHead>
                                            <TableHead>Action</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {kontrak.kontrak_dokumens?.map((dokumen) => (
                                            <TableRow key={dokumen.id}>
                                                <TableCell>{dokumen.nama_dokumen}</TableCell>
                                                <TableCell>
                                                    <a
                                                        href={`/storage/${dokumen.file}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-blue-600 hover:underline"
                                                    >
                                                        {dokumen.file.split('/').pop()}
                                                    </a>
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(dokumen.created_at).toLocaleDateString('id-ID')}
                                                </TableCell>
                                                <TableCell className="space-x-2">
                                                    {hasAnyPermission(["kontraks dokumens edit"]) && (
                                                        <Tooltip>
                                                            <TooltipTrigger>
                                                                <Link href={`/kontraks/${kontrak.id}/dokumens/${dokumen.id}/edit`}>
                                                                    <Button variant="outline" size="sm">
                                                                        <Edit2Icon className='w-4 h-4' />
                                                                    </Button>
                                                                </Link>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Edit</TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </Card>
                        )}
                    </TabsContent>

                    {/* Tab 3: Karyawan */}
                    <TabsContent value="karyawan" className="space-y-4">
                        <div className="flex justify-between items-center">
                            <h3 className="text-lg font-semibold">Karyawan</h3>
                            {hasAnyPermission(["kontraks karyawans create"]) && (
                                <Link href={`/kontraks/${kontrak.id}/karyawans/create`}>
                                    <Button size="sm" className='flex items-center gap-2'>
                                        <PlusCircle className='w-4 h-4' />
                                        Tambah Karyawan
                                    </Button>
                                </Link>
                            )}
                        </div>

                        {kontrak.kontrak_karyawans && kontrak.kontrak_karyawans.length === 0 ? (
                            <Card>
                                <CardContent className="py-8 text-center text-gray-500">
                                    Belum ada karyawan
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nama Karyawan</TableHead>
                                            <TableHead>NIK</TableHead>
                                            <TableHead>Jabatan</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Action</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {kontrak.kontrak_karyawans?.map((kk) => (
                                            <TableRow key={kk.id}>
                                                <TableCell>{kk.karyawan.nama}</TableCell>
                                                <TableCell>{kk.karyawan.nik}</TableCell>
                                                <TableCell>{kk.karyawan.jabatan?.nama_jabatan}</TableCell>
                                                <TableCell>
                                                    <Badge className={kk.karyawan.status === 'Aktif' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}>
                                                        {kk.karyawan.status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {hasAnyPermission(["kontraks karyawans delete"]) && (
                                                        <Tooltip>
                                                            <TooltipTrigger>
                                                                <Link href={`/kontraks/${kontrak.id}/karyawans/${kk.id}?_method=DELETE`} method="delete" as="button">
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        className='hover:bg-red-200 hover:text-red-600'
                                                                    >
                                                                        <Trash2 className='w-4 h-4' />
                                                                    </Button>
                                                                </Link>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Delete</TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </Card>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
