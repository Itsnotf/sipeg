import { Head, Link, router } from '@inertiajs/react';
import { Eye, RefreshCw, ScrollText } from 'lucide-react';
import { useState } from 'react';

import DataList, { type DataColumn } from '@/components/data-list';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pagination, { type Paginated } from '@/components/pagination';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import hasAnyPermission, { angka, periodeLabel, rupiah, tanggal, tanggalRingkas } from '@/lib/utils';
import kontraks from '@/routes/kontraks';
import type { BreadcrumbItem, Kontrak } from '@/types';

interface Penggajian {
    id: number;
    kontrak_id: number;
    periode: string;
    periode_mulai: string | null;
    periode_selesai: string | null;
    tanggal_bayar: string | null;
    final: boolean;
    status: string;
    total_gaji: string | number;
    penggajian_details_count?: number;
}

interface JadwalBaris {
    periode: string;
    label: string;
    mulai: string;
    selesai: string;
    tanggal_bayar: string;
    jatuh_tempo: boolean;
}

interface Props {
    kontrak_id: number | string;
    kontrak: Kontrak;
    penggajians: Paginated<Penggajian>;
    jadwal: JadwalBaris[];
    filters: { status?: string };
}

export default function PenggajianIndex({ kontrak_id, kontrak, penggajians, jadwal, filters }: Props) {
    useFlashToast();
    const [processing, setProcessing] = useState(false);
    const [showAllJadwal, setShowAllJadwal] = useState(false);


    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontrak', href: kontraks.index().url },
        { title: kontrak.judul, href: kontraks.show(kontrak_id).url },
        { title: 'Penggajian', href: kontraks.penggajians.index(kontrak_id).url },
    ];

    const proses = () => {
        setProcessing(true);
        router.post(
            kontraks.penggajians.generate(kontrak_id).url,
            {},
            { onFinish: () => setProcessing(false), preserveScroll: true },
        );
    };

    const jadwalTampil = showAllJadwal ? jadwal : jadwal.slice(0, 4);

    const columns: DataColumn<Penggajian>[] = [
        {
            key: 'periode',
            header: 'Periode',
            primary: true,
            width: 'w-44',
            cell: (row) => <span className="font-semibold">{periodeLabel(row.periode)}</span>,
        },
        {
            key: 'cakupan',
            header: 'Cakupan kerja',
            width: 'w-52',
            cell: (row) => (
                <span className="num text-muted-foreground text-[13px]">
                    {tanggalRingkas(row.periode_mulai)} – {tanggal(row.periode_selesai)}
                </span>
            ),
        },
        {
            key: 'bayar',
            header: 'Tanggal bayar',
            width: 'w-40',
            cell: (row) => <span className="num text-muted-foreground text-[13px]">{tanggal(row.tanggal_bayar)}</span>,
        },
        {
            key: 'karyawan',
            header: 'Karyawan',
            width: 'w-28',
            cell: (row) => (
                <span className="num text-muted-foreground text-[13px]">
                    {angka(row.penggajian_details_count ?? 0)} orang
                </span>
            ),
        },
        {
            key: 'total',
            header: 'Total gaji',
            className: 'text-right',
            cell: (row) => <span className="num text-sm">{rupiah(row.total_gaji)}</span>,
        },
        {
            key: 'status',
            header: 'Status',
            badge: true,
            width: 'w-36',
            cell: (row) =>
                row.status === 'dibayar' ? (
                    <StatusBadge tone="positive">Dibayar</StatusBadge>
                ) : (
                    <StatusBadge tone="warning">Belum dibayar</StatusBadge>
                ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Penggajian — ${kontrak.judul}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Penggajian"
                    description={
                        <>
                            {kontrak.judul} · gajian tiap tanggal <span className="num">{kontrak.tanggal_gajian}</span>
                        </>
                    }
                    actions={
                        <>
                            <Select
                                value={filters.status ?? 'all'}
                                onValueChange={(status) =>
                                    router.get(
                                        kontraks.penggajians.index(kontrak_id).url,
                                        { status: status === 'all' ? undefined : status },
                                        { preserveState: true, replace: true },
                                    )
                                }
                            >
                                <SelectTrigger className="h-11 w-full sm:h-9 sm:w-44">
                                    <SelectValue placeholder="Semua status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua status</SelectItem>
                                    <SelectItem value="belum_dibayar">Belum dibayar</SelectItem>
                                    <SelectItem value="dibayar">Dibayar</SelectItem>
                                </SelectContent>
                            </Select>

                            {hasAnyPermission(['penggajians generate']) && (
                                <Button onClick={proses} disabled={processing} className="h-11 w-full sm:h-9 sm:w-auto">
                                    <RefreshCw className={processing ? 'animate-spin' : undefined} />
                                    {processing ? 'Memproses…' : 'Proses periode jatuh tempo'}
                                </Button>
                            )}
                        </>
                    }
                />

                <section className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold">Periode yang sudah diproses</h2>

                    <DataList
                        columns={columns}
                        rows={penggajians.data}
                        rowKey={(row) => row.id}
                        actions={(row) => (
                            <Button asChild variant="outline" size="sm" className="h-11 w-full sm:h-8 sm:w-auto">
                                <Link href={kontraks.penggajians.show([kontrak_id, row.id])}>
                                    <Eye />
                                    Lihat
                                </Link>
                            </Button>
                        )}
                        empty={
                            <EmptyState
                                icon={ScrollText}
                                title="Belum ada periode yang diproses"
                                description="Periode dibuat saat tanggal bayarnya tiba. Tekan “Proses periode jatuh tempo” untuk membuat yang sudah waktunya."
                            />
                        }
                    />

                    <Pagination meta={penggajians} noun="periode" />
                </section>

                {/*
                    Periode mendatang sengaja tidak dibuat di muka — record masa depan
                    harus terus disinkronkan setiap roster, gaji, atau cashbon berubah.
                    Jadwal ini dihitung dari rentang kontrak saat halaman dibuka.
                */}
                {jadwal.length > 0 && (
                    <section className="flex flex-col gap-3">
                        <div className="flex flex-col gap-1">
                            <h2 className="text-lg font-semibold">Jadwal periode berikutnya</h2>
                            <p className="text-muted-foreground text-xs">
                                Dihitung dari rentang kontrak — belum dibuat, dan tidak akan dibuat sebelum tanggal
                                bayarnya tiba
                            </p>
                        </div>

                        <div className="border-border/80 flex flex-col rounded-lg border border-dashed">
                            {jadwalTampil.map((baris, index) => (
                                <div
                                    key={baris.periode}
                                    className={`flex items-center justify-between gap-3 border-b border-dashed p-3.5 last:border-b-0 ${
                                        index % 2 === 1 ? 'bg-muted/40' : ''
                                    }`}
                                >
                                    <div className="flex min-w-0 flex-col gap-0.5">
                                        <span className="text-muted-foreground text-sm">{baris.label}</span>
                                        <span className="num text-muted-foreground/80 text-xs">
                                            {tanggalRingkas(baris.mulai)}–{tanggalRingkas(baris.selesai)} · bayar{' '}
                                            {tanggal(baris.tanggal_bayar)}
                                        </span>
                                    </div>
                                    <StatusBadge tone={baris.jatuh_tempo ? 'warning' : 'outline'}>
                                        {baris.jatuh_tempo ? 'Jatuh tempo' : 'Terjadwal'}
                                    </StatusBadge>
                                </div>
                            ))}

                            {jadwal.length > 4 && (
                                <button
                                    type="button"
                                    onClick={() => setShowAllJadwal((value) => !value)}
                                    className="text-primary border-border/80 hover:bg-accent/50 h-12 border-t border-dashed text-sm font-semibold transition-colors"
                                >
                                    {showAllJadwal
                                        ? 'Sembunyikan'
                                        : `Tampilkan ${angka(jadwal.length - 4)} periode lainnya`}
                                </button>
                            )}
                        </div>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
