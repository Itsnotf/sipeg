import { Head, Link, router } from '@inertiajs/react';
import { Eye, ScrollText } from 'lucide-react';
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
import { angka, periodeLabel, rupiah, tanggal } from '@/lib/utils';
import kontraks from '@/routes/kontraks';
import penggajians from '@/routes/penggajians';
import type { BreadcrumbItem } from '@/types';

interface Penggajian {
    id: number;
    kontrak_id: number;
    periode: string;
    periode_mulai: string | null;
    periode_selesai: string | null;
    tanggal_bayar: string | null;
    status: string;
    total_gaji: string | number;
    penggajian_details_count?: number;
    kontrak?: { judul: string; client?: { nama_client: string } };
}

interface Props {
    penggajians: Paginated<Penggajian>;
    filters: { status?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Penggajian', href: penggajians.index().url }];

export default function PenggajianGlobalIndex({ penggajians: list, filters }: Props) {
    useFlashToast();

    const columns: DataColumn<Penggajian>[] = [
        {
            key: 'periode',
            header: 'Periode',
            primary: true,
            width: 'w-44',
            cell: (row) => (
                <div className="flex flex-col gap-0.5">
                    <span className="font-semibold">{periodeLabel(row.periode)}</span>
                    <span className="text-muted-foreground text-xs">{row.kontrak?.judul ?? '—'}</span>
                </div>
            ),
        },
        {
            key: 'client',
            header: 'Client',
            width: 'w-52',
            cell: (row) => (
                <span className="text-muted-foreground text-[13px]">{row.kontrak?.client?.nama_client ?? '—'}</span>
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
            <Head title="Penggajian" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Penggajian"
                    description="Seluruh periode penggajian lintas kontrak"
                    actions={
                        <Select
                            value={filters.status ?? 'all'}
                            onValueChange={(status) =>
                                router.get(
                                    penggajians.index().url,
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
                    }
                />

                <DataList
                    columns={columns}
                    rows={list.data}
                    rowKey={(row) => row.id}
                    actions={(row) => (
                        <Button asChild variant="outline" size="sm" className="h-11 w-full sm:h-8 sm:w-auto">
                            <Link href={kontraks.penggajians.show([row.kontrak_id, row.id])}>
                                <Eye />
                                Lihat
                            </Link>
                        </Button>
                    )}
                    empty={
                        <EmptyState
                            icon={ScrollText}
                            title={filters.status ? 'Tidak ada periode dengan status itu' : 'Belum ada penggajian'}
                            description="Periode dibuat saat tanggal bayarnya tiba. Buka sebuah kontrak untuk memprosesnya."
                        />
                    }
                />

                <Pagination meta={list} noun="periode" />
            </div>
        </AppLayout>
    );
}
