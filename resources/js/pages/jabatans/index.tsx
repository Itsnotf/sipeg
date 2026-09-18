import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, PlusCircle, Search, SquarePen } from 'lucide-react';
import { useState } from 'react';

import DataList, { type DataColumn } from '@/components/data-list';
import DeleteButton from '@/components/delete-button';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pagination, { type Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import hasAnyPermission, { angka, rupiah } from '@/lib/utils';
import jabatans from '@/routes/jabatans';
import type { BreadcrumbItem, Jabatan } from '@/types';

interface Props {
    jabatans: Paginated<Jabatan>;
    filters: { search?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Jabatan', href: jabatans.index().url }];

export default function JabatanIndex({ jabatans: list, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');


    const columns: DataColumn<Jabatan>[] = [
        {
            key: 'nama',
            header: 'Jabatan',
            primary: true,
            cell: (row) => <span className="font-semibold">{row.nama_jabatan}</span>,
        },
        {
            key: 'deskripsi',
            header: 'Deskripsi',
            hideOnCard: true,
            cell: (row) => (
                <span className="text-muted-foreground line-clamp-1 text-[13px]" title={row.deskripsi}>
                    {row.deskripsi}
                </span>
            ),
        },
        {
            key: 'gaji',
            header: 'Gaji pokok',
            className: 'text-right',
            width: 'w-44',
            cell: (row) => <span className="num text-sm">{rupiah(row.gaji)}</span>,
        },
        {
            key: 'bpjs',
            header: 'BPJS',
            className: 'text-right',
            width: 'w-48',
            cell: (row) => (
                <div className="flex flex-col gap-0.5 sm:items-end">
                    <span className="num text-sm">{angka(row.bpjs_persen)}%</span>
                    <span className="num text-muted-foreground/80 text-[11px]">
                        {rupiah((Number(row.gaji) * Number(row.bpjs_persen)) / 100)}
                    </span>
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Jabatan" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Jabatan"
                    description="Gaji pokok dan tarif BPJS yang menjadi dasar perhitungan penggajian"
                    actions={
                        hasAnyPermission(['jabatans create']) ? (
                            <Button asChild className="h-11 w-full sm:h-9 sm:w-auto">
                                <Link href={jabatans.create()}>
                                    <PlusCircle />
                                    Tambah jabatan
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(jabatans.index().url, { search }, { preserveState: true, replace: true });
                    }}
                    className="flex gap-2 sm:max-w-md"
                >
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama jabatan"
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
                            {hasAnyPermission(['jabatans edit']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 flex-1 sm:h-8 sm:flex-none">
                                    <Link href={jabatans.edit(row.id)}>
                                        <SquarePen />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['jabatans delete']) && <DeleteButton id={row.id} featured="jabatans" />}
                        </div>
                    )}
                    empty={
                        <EmptyState
                            icon={BookOpen}
                            title={filters.search ? 'Tidak ada jabatan yang cocok' : 'Belum ada jabatan'}
                            description={
                                filters.search
                                    ? 'Coba kata kunci lain, atau kosongkan pencarian untuk melihat semuanya.'
                                    : 'Jabatan menentukan gaji pokok dan tarif BPJS setiap pekerja.'
                            }
                            action={
                                !filters.search && hasAnyPermission(['jabatans create']) ? (
                                    <Button asChild>
                                        <Link href={jabatans.create()}>Tambah jabatan pertama</Link>
                                    </Button>
                                ) : null
                            }
                        />
                    }
                />

                <Pagination meta={list} noun="jabatan" />
            </div>
        </AppLayout>
    );
}
