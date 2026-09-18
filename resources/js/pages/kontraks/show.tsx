import { Head, Link } from '@inertiajs/react';
import { DollarSign, Eye, FileText, PlusCircle, ScrollText, SquarePen, Users } from 'lucide-react';
import { useState } from 'react';

import DeleteButtonChild from '@/components/delete-button-child';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import StatTile from '@/components/stat-tile';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import hasAnyPermission, { angka, periodeLabel, rupiah, tanggal, tanggalRingkas } from '@/lib/utils';
import kontraks from '@/routes/kontraks';
import type { BreadcrumbItem } from '@/types';

interface PenggajianBaris {
    id: number;
    periode: string;
    periode_mulai: string | null;
    periode_selesai: string | null;
    tanggal_bayar: string | null;
    status: string;
    total_gaji: number;
    karyawan_count: number;
}

interface DokumenBaris {
    id: number;
    nama_dokumen: string;
    file: string;
    created_at: string | null;
}

interface PenempatanBaris {
    id: number;
    karyawan_id: number;
    tanggal_mulai: string | null;
    tanggal_selesai: string | null;
    karyawan: { id: number; nama: string; nik: string; status: string; jabatan: string };
}

interface Props {
    kontrak: {
        id: number;
        judul: string;
        deskripsi: string;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
        total_biaya: number;
        tanggal_gajian: number;
        status: string;
        client: { id: number; nama_client: string; email?: string; no_hp?: string };
        kontrak_dokumens: DokumenBaris[];
        kontrak_karyawans: PenempatanBaris[];
        penggajians: PenggajianBaris[];
    };
    penggajian_summary: { total_penggajian: number; total_biaya: number; keuntungan: number };
}

const nada: Record<string, 'positive' | 'warning' | 'outline'> = {
    Progres: 'positive',
    Pending: 'warning',
    Selesai: 'outline',
};

export default function KontrakShow({ kontrak, penggajian_summary }: Props) {
    useFlashToast();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontrak', href: kontraks.index().url },
        { title: kontrak.judul, href: kontraks.show(kontrak.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={kontrak.judul} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title={kontrak.judul}
                    description={kontrak.client.nama_client}
                    eyebrow={<StatusBadge tone={nada[kontrak.status] ?? 'neutral'}>{kontrak.status}</StatusBadge>}
                    actions={
                        <>
                            {hasAnyPermission(['penggajians index']) && (
                                <Button asChild variant="outline" className="h-11 sm:h-9">
                                    <Link href={kontraks.penggajians.index(kontrak.id)}>
                                        <DollarSign />
                                        Penggajian
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['kontraks edit']) && (
                                <Button asChild className="h-11 sm:h-9">
                                    <Link href={kontraks.edit(kontrak.id)}>
                                        <SquarePen />
                                        Ubah kontrak
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                {/* Keterangan kontrak */}
                <div className="bg-card grid grid-cols-2 gap-px overflow-hidden rounded-lg border lg:grid-cols-4">
                    <div className="bg-background flex flex-col gap-1 p-3.5">
                        <span className="eyebrow">Mulai</span>
                        <span className="num text-[13px]">{tanggal(kontrak.tanggal_mulai)}</span>
                    </div>
                    <div className="bg-background flex flex-col gap-1 p-3.5">
                        <span className="eyebrow">Selesai</span>
                        <span className="num text-[13px]">{tanggal(kontrak.tanggal_selesai)}</span>
                    </div>
                    <div className="bg-background flex flex-col gap-1 p-3.5">
                        <span className="eyebrow">Tanggal gajian</span>
                        <span className="num text-[13px]">tiap tanggal {kontrak.tanggal_gajian}</span>
                    </div>
                    <div className="bg-background flex flex-col gap-1 p-3.5">
                        <span className="eyebrow">Pekerja ditempatkan</span>
                        <span className="num text-[13px]">{angka(kontrak.kontrak_karyawans.length)} orang</span>
                    </div>
                </div>

                {kontrak.deskripsi ? (
                    <p className="text-muted-foreground max-w-[70ch] text-sm leading-relaxed">{kontrak.deskripsi}</p>
                ) : null}

                {/* Ringkasan keuangan */}
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <StatTile label="Nilai kontrak" value={rupiah(penggajian_summary.total_biaya)} />
                    <StatTile
                        label="Gaji tersusun"
                        value={rupiah(penggajian_summary.total_penggajian)}
                        hint="dari periode yang sudah diproses"
                    />
                    <StatTile
                        label="Selisih berjalan"
                        value={rupiah(penggajian_summary.keuntungan)}
                        tone={penggajian_summary.keuntungan >= 0 ? 'positive' : 'critical'}
                        hint="bukan proyeksi seluruh kontrak"
                    />
                </div>

                <Tabs defaultValue="penggajian" className="gap-4">
                    <TabsList className="h-11 w-full sm:h-9 sm:w-auto">
                        <TabsTrigger value="penggajian" className="flex-1 sm:flex-none">
                            Penggajian
                        </TabsTrigger>
                        <TabsTrigger value="pekerja" className="flex-1 sm:flex-none">
                            Pekerja
                        </TabsTrigger>
                        <TabsTrigger value="dokumen" className="flex-1 sm:flex-none">
                            Dokumen
                        </TabsTrigger>
                    </TabsList>

                    {/* Penggajian */}
                    <TabsContent value="penggajian" className="flex flex-col gap-3">
                        {kontrak.penggajians.length === 0 ? (
                            <div className="bg-card rounded-lg border">
                                <EmptyState
                                    icon={ScrollText}
                                    title="Belum ada periode yang diproses"
                                    description="Periode dibuat saat tanggal bayarnya tiba."
                                    action={
                                        hasAnyPermission(['penggajians index']) ? (
                                            <Button asChild>
                                                <Link href={kontraks.penggajians.index(kontrak.id)}>
                                                    Buka penggajian
                                                </Link>
                                            </Button>
                                        ) : null
                                    }
                                />
                            </div>
                        ) : (
                            <div className="bg-card flex flex-col overflow-hidden rounded-lg border">
                                {kontrak.penggajians.map((baris, index) => (
                                    <div
                                        key={baris.id}
                                        className={`flex flex-col gap-2 border-b p-3.5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between ${
                                            index % 2 === 1 ? 'bg-muted/45' : ''
                                        }`}
                                    >
                                        <div className="flex min-w-0 flex-col gap-0.5">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-semibold">
                                                    {periodeLabel(baris.periode)}
                                                </span>
                                                <StatusBadge tone={baris.status === 'dibayar' ? 'positive' : 'warning'}>
                                                    {baris.status === 'dibayar' ? 'Dibayar' : 'Belum dibayar'}
                                                </StatusBadge>
                                            </div>
                                            <span className="num text-muted-foreground text-xs">
                                                {tanggalRingkas(baris.periode_mulai)}–{tanggal(baris.periode_selesai)} ·
                                                bayar {tanggal(baris.tanggal_bayar)} · {angka(baris.karyawan_count)}{' '}
                                                orang
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between gap-4 sm:justify-end">
                                            <span className="num text-sm font-medium">{rupiah(baris.total_gaji)}</span>
                                            <Button asChild variant="outline" size="sm" className="h-9 sm:h-8">
                                                <Link href={kontraks.penggajians.show([kontrak.id, baris.id])}>
                                                    <Eye />
                                                    Lihat
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </TabsContent>

                    {/* Pekerja */}
                    <TabsContent value="pekerja" className="flex flex-col gap-3">
                        {hasAnyPermission(['kontraks karyawans create']) && (
                            <Button asChild variant="outline" className="h-11 w-full sm:h-9 sm:w-fit">
                                <Link href={kontraks.karyawans.create(kontrak.id)}>
                                    <PlusCircle />
                                    Tempatkan pekerja
                                </Link>
                            </Button>
                        )}

                        {kontrak.kontrak_karyawans.length === 0 ? (
                            <div className="bg-card rounded-lg border">
                                <EmptyState
                                    icon={Users}
                                    title="Belum ada pekerja ditempatkan"
                                    description="Penggajian hanya menghitung pekerja yang penempatannya menyentuh periode berjalan."
                                />
                            </div>
                        ) : (
                            <div className="bg-card flex flex-col overflow-hidden rounded-lg border">
                                {kontrak.kontrak_karyawans.map((baris, index) => (
                                    <div
                                        key={baris.id}
                                        className={`flex flex-col gap-2 border-b p-3.5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between ${
                                            index % 2 === 1 ? 'bg-muted/45' : ''
                                        }`}
                                    >
                                        <div className="flex min-w-0 flex-col gap-0.5">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-semibold">{baris.karyawan.nama}</span>
                                                {baris.tanggal_selesai ? (
                                                    <StatusBadge tone="outline">Penempatan berakhir</StatusBadge>
                                                ) : null}
                                            </div>
                                            <span className="num text-muted-foreground text-xs">
                                                {baris.karyawan.jabatan} · NIK {baris.karyawan.nik} · sejak{' '}
                                                {tanggal(baris.tanggal_mulai)}
                                                {baris.tanggal_selesai
                                                    ? ` s/d ${tanggal(baris.tanggal_selesai)}`
                                                    : ''}
                                            </span>
                                        </div>
                                        {hasAnyPermission(['kontraks karyawans delete']) && !baris.tanggal_selesai ? (
                                            <DeleteButtonChild
                                                id={kontrak.id}
                                                featured="kontraks"
                                                child="karyawans"
                                                child_id={baris.id}
                                            />
                                        ) : null}
                                    </div>
                                ))}
                            </div>
                        )}
                    </TabsContent>

                    {/* Dokumen */}
                    <TabsContent value="dokumen" className="flex flex-col gap-3">
                        {hasAnyPermission(['kontraks dokumens create']) && (
                            <Button asChild variant="outline" className="h-11 w-full sm:h-9 sm:w-fit">
                                <Link href={kontraks.dokumens.create(kontrak.id)}>
                                    <PlusCircle />
                                    Unggah dokumen
                                </Link>
                            </Button>
                        )}

                        {kontrak.kontrak_dokumens.length === 0 ? (
                            <div className="bg-card rounded-lg border">
                                <EmptyState
                                    icon={FileText}
                                    title="Belum ada dokumen"
                                    description="Lampirkan MOU, PKS, atau surat persetujuan sebagai arsip kontrak ini."
                                />
                            </div>
                        ) : (
                            <div className="bg-card flex flex-col overflow-hidden rounded-lg border">
                                {kontrak.kontrak_dokumens.map((dokumen, index) => (
                                    <div
                                        key={dokumen.id}
                                        className={`flex flex-col gap-2 border-b p-3.5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between ${
                                            index % 2 === 1 ? 'bg-muted/45' : ''
                                        }`}
                                    >
                                        <div className="flex min-w-0 flex-col gap-0.5">
                                            <span className="text-sm font-semibold">{dokumen.nama_dokumen}</span>
                                            <span className="num text-muted-foreground text-xs">
                                                diunggah {tanggal(dokumen.created_at)}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button asChild variant="outline" size="sm" className="h-9 sm:h-8">
                                                <a href={`/storage/${dokumen.file}`} target="_blank" rel="noreferrer">
                                                    <Eye />
                                                    Buka
                                                </a>
                                            </Button>
                                            {hasAnyPermission(['kontraks dokumens delete']) && (
                                                <DeleteButtonChild
                                                    id={kontrak.id}
                                                    featured="kontraks"
                                                    child="dokumens"
                                                    child_id={dokumen.id}
                                                />
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
