import { Head, Link, router } from '@inertiajs/react';
import { HandCoins, PlusCircle, Search, SquarePen } from 'lucide-react';
import { useState } from 'react';

import DataList, { type DataColumn } from '@/components/data-list';
import DeleteButton from '@/components/delete-button';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pagination, { type Paginated } from '@/components/pagination';
import StatTile from '@/components/stat-tile';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import hasAnyPermission, { angka, rupiah, rupiahRingkas, tanggal } from '@/lib/utils';
import cashbons from '@/routes/cashbons';
import type { BreadcrumbItem } from '@/types';

interface Cashbon {
    id: number;
    karyawan_id: number;
    jumlah: string | number;
    keterangan: string;
    status: string;
    tanggal: string | null;
    terpotong: number;
    terbayar: number;
    sisa: number;
    karyawan?: { nama: string; jabatan?: { nama_jabatan: string } };
}

interface Props {
    cashbons: Paginated<Cashbon>;
    ringkasan: {
        total: number;
        terbayar: number;
        sisa: number;
        jumlah_pinjaman: number;
        berjalan: number;
    };
    filters: { search?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Cashbon', href: cashbons.index().url }];

/** Batang pelunasan: terbayar · dipesan (sudah dialokasikan, slip belum dibayar) · sisa. */
function ProgresPelunasan({ cashbon }: { cashbon: Cashbon }) {
    const jumlah = Number(cashbon.jumlah) || 1;
    const terbayar = (cashbon.terbayar / jumlah) * 100;
    const dipesan = ((cashbon.terpotong - cashbon.terbayar) / jumlah) * 100;

    return (
        <div className="flex flex-col gap-1.5">
            <div className="bg-muted-foreground/20 flex h-2.5 overflow-hidden rounded-xs">
                <div className="bg-ok" style={{ width: `${terbayar}%` }} />
                <div
                    className="bg-[color-mix(in_oklch,var(--ok)_35%,transparent)]"
                    style={{ width: `${dipesan}%` }}
                />
            </div>
            <span className="num text-muted-foreground/90 text-[11px]">
                {cashbon.sisa < 1
                    ? `lunas · ${rupiah(cashbon.terbayar)} terpotong`
                    : `${rupiah(cashbon.terbayar)} terbayar · ${rupiah(cashbon.terpotong - cashbon.terbayar)} menunggu pembayaran`}
            </span>
        </div>
    );
}

export default function CashbonIndex({ cashbons: list, ringkasan, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');


    const columns: DataColumn<Cashbon>[] = [
        {
            key: 'karyawan',
            header: 'Karyawan',
            primary: true,
            cell: (row) => (
                <div className="flex flex-col gap-0.5">
                    <span className="font-semibold">{row.karyawan?.nama ?? '—'}</span>
                    <span className="text-muted-foreground text-xs">
                        {row.karyawan?.jabatan?.nama_jabatan ?? '—'}
                    </span>
                </div>
            ),
        },
        {
            key: 'keterangan',
            header: 'Keterangan',
            cell: (row) => <span className="text-muted-foreground text-[13px]">{row.keterangan}</span>,
        },
        {
            key: 'jumlah',
            header: 'Pinjaman',
            className: 'text-right',
            width: 'w-36',
            cell: (row) => <span className="num text-sm">{rupiah(row.jumlah)}</span>,
        },
        {
            key: 'progres',
            header: 'Progres pelunasan',
            width: 'w-60',
            cell: (row) => <ProgresPelunasan cashbon={row} />,
        },
        {
            key: 'sisa',
            header: 'Sisa',
            className: 'text-right',
            width: 'w-36',
            cell: (row) => (
                <span className={`num text-sm ${row.sisa >= 1 ? 'text-warn' : 'text-muted-foreground'}`}>
                    {rupiah(row.sisa)}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            badge: true,
            width: 'w-28',
            cell: (row) =>
                row.sisa < 1 ? (
                    <StatusBadge tone="positive">Lunas</StatusBadge>
                ) : (
                    <StatusBadge tone="warning">Berjalan</StatusBadge>
                ),
        },
        {
            key: 'dibuat',
            header: 'Dibuat',
            hideOnCard: true,
            width: 'w-36',
            cell: (row) => <span className="num text-muted-foreground text-[13px]">{tanggal(row.tanggal)}</span>,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cashbon" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Cashbon"
                    description="Pinjaman dipotong mencicil dari gaji, maksimal 50% gaji bersih tiap periode"
                    actions={
                        hasAnyPermission(['cashbons create']) ? (
                            <Button asChild className="h-11 w-full sm:h-9 sm:w-auto">
                                <Link href={cashbons.create()}>
                                    <PlusCircle />
                                    Buat cashbon
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <StatTile
                        label="Total dipinjamkan"
                        value={rupiahRingkas(ringkasan.total)}
                        hint={`${angka(ringkasan.jumlah_pinjaman)} pinjaman`}
                    />
                    <StatTile
                        label="Sudah terbayar"
                        value={rupiahRingkas(ringkasan.terbayar)}
                        tone="positive"
                        hint="dipotong dari gaji yang sudah dibayar"
                    />
                    <StatTile
                        label="Sisa hutang berjalan"
                        value={rupiahRingkas(ringkasan.sisa)}
                        tone={ringkasan.sisa > 0 ? 'warning' : 'default'}
                        hint={`${angka(ringkasan.berjalan)} pinjaman belum lunas`}
                    />
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(cashbons.index().url, { search }, { preserveState: true, replace: true });
                    }}
                    className="flex gap-2 sm:max-w-md"
                >
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama atau keterangan"
                            className="h-11 pl-9 sm:h-9"
                        />
                    </div>
                    <Button type="submit" variant="outline" className="h-11 sm:h-9">
                        Cari
                    </Button>
                </form>

                <DataList
                    columns={columns}
                    rows={list.data}
                    rowKey={(row) => row.id}
                    actions={(row) => (
                        <div className="flex w-full items-center gap-2 sm:w-auto sm:justify-end">
                            {hasAnyPermission(['cashbons edit']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 flex-1 sm:h-8 sm:flex-none">
                                    <Link href={cashbons.edit(row.id)}>
                                        <SquarePen />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['cashbons delete']) && (
                                <DeleteButton id={row.id} featured="cashbons" />
                            )}
                        </div>
                    )}
                    empty={
                        <EmptyState
                            icon={HandCoins}
                            title={filters.search ? 'Tidak ada cashbon yang cocok' : 'Belum ada cashbon'}
                            description={
                                filters.search
                                    ? 'Coba kata kunci lain, atau kosongkan pencarian untuk melihat semuanya.'
                                    : 'Pinjaman yang dibuat di sini akan otomatis terpotong dari gaji secara mencicil.'
                            }
                            action={
                                !filters.search && hasAnyPermission(['cashbons create']) ? (
                                    <Button asChild>
                                        <Link href={cashbons.create()}>Buat cashbon pertama</Link>
                                    </Button>
                                ) : null
                            }
                        />
                    }
                />

                <Pagination meta={list} noun="cashbon" />
            </div>
        </AppLayout>
    );
}
