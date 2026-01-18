import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { TrendingUp, Users, FileText, DollarSign, AlertCircle, CheckCircle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface Props {
    statistics: {
        total_kontraks: number;
        active_kontraks: number;
        total_karyawans: number;
        total_penggajians: number;
    };
    financial: {
        total_biaya_kontraks: number;
        total_penggajian_dikeluarkan: number;
        keuntungan_bersih: number;
    };
    penggajian_status: {
        pending_count: number;
        pending_total_gaji: number;
    };
    cashbon_status: {
        total: number;
        pending_count: number;
    };
    recent_kontraks: any[];
    recent_penggajians: any[];
}

export default function Dashboard({
    statistics,
    financial,
    penggajian_status,
    cashbon_status,
    recent_kontraks,
    recent_penggajians,
}: Props) {
    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'aktif':
                return 'bg-blue-100 text-blue-800';
            case 'selesai':
                return 'bg-green-100 text-green-800';
            case 'dibayar':
                return 'bg-green-100 text-green-800';
            case 'belum_dibayar':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="p-4 space-y-6">
                {/* Main KPI Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 mb-2">Total Kontraks</p>
                                    <p className="text-3xl font-bold">{statistics.total_kontraks}</p>
                                    <p className="text-xs text-green-600 mt-2">{statistics.active_kontraks} aktif</p>
                                </div>
                                <FileText className="w-8 h-8 text-blue-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 mb-2">Total Karyawan</p>
                                    <p className="text-3xl font-bold">{statistics.total_karyawans}</p>
                                    <p className="text-xs text-gray-500 mt-2">Aktif di semua kontrak</p>
                                </div>
                                <Users className="w-8 h-8 text-purple-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 mb-2">Total Penggajian</p>
                                    <p className="text-3xl font-bold">{statistics.total_penggajians}</p>
                                    <p className="text-xs text-yellow-600 mt-2">{penggajian_status.pending_count} pending</p>
                                </div>
                                <DollarSign className="w-8 h-8 text-orange-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 mb-2">Cashbon</p>
                                    <p className="text-3xl font-bold">{formatCurrency(cashbon_status.total)}</p>
                                    <p className="text-xs text-red-600 mt-2">{cashbon_status.pending_count} belum lunas</p>
                                </div>
                                <AlertCircle className="w-8 h-8 text-red-500" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Financial Summary */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card className="border-l-4 border-l-blue-600">
                        <CardContent className="pt-6">
                            <div className="text-center">
                                <p className="text-sm text-gray-600 mb-2">Total Biaya Kontraks</p>
                                <p className="text-2xl font-bold text-blue-600">{formatCurrency(financial.total_biaya_kontraks)}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-red-600">
                        <CardContent className="pt-6">
                            <div className="text-center">
                                <p className="text-sm text-gray-600 mb-2">Total Penggajian Dikeluarkan</p>
                                <p className="text-2xl font-bold text-red-600">{formatCurrency(financial.total_penggajian_dikeluarkan)}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className={`border-l-4 ${financial.keuntungan_bersih >= 0 ? 'border-l-green-600' : 'border-l-orange-600'}`}>
                        <CardContent className="pt-6">
                            <div className="text-center">
                                <p className="text-sm text-gray-600 mb-2">Keuntungan Bersih</p>
                                <p className={`text-2xl font-bold ${financial.keuntungan_bersih >= 0 ? 'text-green-600' : 'text-orange-600'}`}>
                                    {formatCurrency(financial.keuntungan_bersih)}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Alerts Section */}
                {(penggajian_status.pending_count > 0 || cashbon_status.pending_count > 0) && (
                    <div className="grid grid-cols-1 md:grid-cols-1 gap-4">
                        {penggajian_status.pending_count > 0 && (
                            <Card className="border-yellow-300 bg-yellow-50">
                                <CardContent className="pt-6">
                                    <div className="flex items-start gap-4">
                                        <AlertCircle className="w-6 h-6 text-yellow-600 mt-1 flex-shrink-0" />
                                        <div className="flex-1">
                                            <p className="font-semibold text-yellow-900">Penggajian Pending</p>
                                            <p className="text-sm text-yellow-700 mt-1">
                                                Ada {penggajian_status.pending_count} penggajian menunggu pembayaran
                                            </p>
                                            <p className="text-lg font-bold text-yellow-900 mt-2">
                                                {formatCurrency(penggajian_status.pending_total_gaji)}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {cashbon_status.pending_count > 0 && (
                            <Card className="border-red-300 bg-red-50">
                                <CardContent className="pt-6">
                                    <div className="flex items-start gap-4">
                                        <AlertCircle className="w-6 h-6 text-red-600 mt-1 flex-shrink-0" />
                                        <div className="flex-1">
                                            <p className="font-semibold text-red-900">Cashbon Belum Lunas</p>
                                            <p className="text-sm text-red-700 mt-1">
                                                Ada {cashbon_status.pending_count} cashbon yang masih belum dibayar
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}

                {/* Recent Data Section */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Kontraks */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Kontraks Terbaru</CardTitle>
                            <Link href="/kontraks">
                                <Button variant="outline" size="sm">Lihat Semua</Button>
                            </Link>
                        </CardHeader>
                        <CardContent>
                            {recent_kontraks.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">Belum ada kontrak</p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Judul</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recent_kontraks.map((kontrak) => (
                                            <TableRow key={kontrak.id}>
                                                <TableCell>
                                                    <Link href={`/kontraks/${kontrak.id}`} className="hover:underline">
                                                        <div className="font-medium">{kontrak.judul}</div>
                                                        <div className="text-sm text-gray-500">{kontrak.client_name}</div>
                                                    </Link>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge className={getStatusColor(kontrak.status)}>
                                                        {kontrak.status}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Penggajians */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Penggajian Terbaru</CardTitle>
                            <Link href="/penggajians">
                                <Button variant="outline" size="sm">Lihat Semua</Button>
                            </Link>
                        </CardHeader>
                        <CardContent>
                            {recent_penggajians.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">Belum ada penggajian</p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Periode</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recent_penggajians.map((penggajian) => {
                                            const periode = new Date(penggajian.periode).toLocaleDateString('id-ID', {
                                                year: 'numeric',
                                                month: 'long',
                                            });
                                            return (
                                                <TableRow key={penggajian.id}>
                                                    <TableCell>
                                                        <Link href={`/kontraks/${penggajian.kontrak_id}/penggajians/${penggajian.id}`} className="hover:underline">
                                                            <div className="font-medium text-sm">{periode}</div>
                                                            <div className="text-xs text-gray-500">{penggajian.kontrak_judul}</div>
                                                        </Link>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge className={getStatusColor(penggajian.status)}>
                                                            {penggajian.status === 'dibayar' ? 'Dibayar' : 'Belum Dibayar'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right font-medium">
                                                        {formatCurrency(parseFloat(penggajian.total_gaji))}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
