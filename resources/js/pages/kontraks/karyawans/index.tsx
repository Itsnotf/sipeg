import { Head, Link, router } from '@inertiajs/react';
import { PlusCircle, Search, Users } from 'lucide-react';
import { useState } from 'react';

import DataList, { type DataColumn } from '@/components/data-list';
import DeleteButtonChild from '@/components/delete-button-child';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pagination, { type Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import hasAnyPermission, { tanggal } from '@/lib/utils';
import kontraks from '@/routes/kontraks';
import kontrakKaryawans from '@/routes/kontraks/karyawans';
import type { BreadcrumbItem } from '@/types';

interface Penempatan {
    id: number;
    kontrak_id: number;
    nama: string | null;
    nik: number | string | null;
    jabatan: string | null;
    tanggal_mulai: string | null;
    tanggal_selesai: string | null;
}

interface Props {
    kontrak_id: string;
    kontrak: { id: number; judul: string };
    karyawans: Paginated<Penempatan>;
    filters: { search?: string };
}

export default function PenempatanIndex({ kontrak_id, kontrak, karyawans: list, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontrak', href: kontraks.index().url },
        { title: kontrak.judul, href: kontraks.show(kontrak.id).url },
        { title: 'Penempatan', href: kontrakKaryawans.index(kontrak_id).url },
    ];

    const columns: DataColumn<Penempatan>[] = [
        {
            key: 'karyawan',
            header: 'Pekerja',
            primary: true,
            cell: (row) => (
                <div className="flex flex-col gap-0.5">
                    <span className="font-semibold">{row.nama ?? '—'}</span>
                    <span className="num text-muted-foreground text-xs">{row.nik ?? '—'}</span>
                </div>
            ),
        },
        {
            key: 'jabatan',
            header: 'Jabatan',
            width: 'w-48',
            cell: (row) => <span className="text-muted-foreground text-[13px]">{row.jabatan ?? '—'}</span>,
        },
        {
            key: 'mulai',
            header: 'Mulai ditempatkan',
            width: 'w-44',
            cell: (row) => <span className="num text-muted-foreground text-[13px]">{tanggal(row.tanggal_mulai)}</span>,
        },
        {
            key: 'selesai',
            header: 'Berakhir',
            width: 'w-44',
            cell: (row) => (
                <span className="num text-muted-foreground text-[13px]">
                    {row.tanggal_selesai ? tanggal(row.tanggal_selesai) : 'Masih berjalan'}
                </span>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Penempatan · ${kontrak.judul}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow={kontrak.judul}
                    title="Penempatan pekerja"
                    description="Pekerja yang tercatat di sini ikut terhitung pada penggajian kontrak ini selama penempatannya berjalan"
                    actions={
                        hasAnyPermission(['kontraks karyawans create']) ? (
                            <Button asChild className="h-11 w-full sm:h-9 sm:w-auto">
                                <Link href={kontrakKaryawans.create(kontrak_id)}>
                                    <PlusCircle />
                                    Tempatkan pekerja
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            kontrakKaryawans.index(kontrak_id).url,
                            { search },
                            { preserveState: true, replace: true },
                        );
                    }}
                    className="flex gap-2 sm:max-w-md"
                >
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama atau NIK"
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
                    actions={(row) =>
                        hasAnyPermission(['kontraks karyawans delete']) ? (
                            <div className="flex w-full items-center gap-2 sm:w-auto sm:justify-end">
                                <DeleteButtonChild
                                    id={row.kontrak_id}
                                    featured="kontraks"
                                    child="karyawans"
                                    child_id={row.id}
                                    jenis="penempatan"
                                    nama={row.nama ?? undefined}
                                />
                            </div>
                        ) : null
                    }
                    empty={
                        <EmptyState
                            icon={Users}
                            title={filters.search ? 'Tidak ada yang cocok' : 'Belum ada pekerja ditempatkan'}
                            description={
                                filters.search
                                    ? 'Coba kata kunci lain, atau kosongkan pencarian untuk melihat semuanya.'
                                    : 'Penggajian kontrak ini baru bisa disusun setelah ada pekerja yang ditempatkan.'
                            }
                            action={
                                !filters.search && hasAnyPermission(['kontraks karyawans create']) ? (
                                    <Button asChild>
                                        <Link href={kontrakKaryawans.create(kontrak_id)}>Tempatkan pekerja</Link>
                                    </Button>
                                ) : null
                            }
                        />
                    }
                />

                <Pagination meta={list} noun="penempatan" />
            </div>
        </AppLayout>
    );
}
