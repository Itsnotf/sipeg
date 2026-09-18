import { Head, Link, router } from '@inertiajs/react';
import { PlusCircle, Search, SquarePen, Users } from 'lucide-react';
import { useState } from 'react';

import DataList, { type DataColumn } from '@/components/data-list';
import DeleteButton from '@/components/delete-button';
import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import Pagination, { type Paginated } from '@/components/pagination';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import hasAnyPermission, { tanggal } from '@/lib/utils';
import karyawans from '@/routes/karyawans';
import type { BreadcrumbItem, Karyawan } from '@/types';

interface Props {
    karyawans: Paginated<Karyawan>;
    filters: { search?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Karyawan', href: karyawans.index().url }];

const kelamin: Record<string, string> = { L: 'Laki-laki', P: 'Perempuan' };

export default function KaryawanIndex({ karyawans: list, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');


    const columns: DataColumn<Karyawan>[] = [
        {
            key: 'nama',
            header: 'Nama',
            primary: true,
            cell: (row) => (
                <div className="flex flex-col gap-0.5">
                    <span className="font-semibold">{row.nama}</span>
                    <span className="text-muted-foreground text-xs">{row.jabatan?.nama_jabatan ?? '—'}</span>
                </div>
            ),
        },
        {
            key: 'nik',
            header: 'NIK',
            width: 'w-48',
            cell: (row) => <span className="num text-muted-foreground text-[13px]">{row.nik}</span>,
        },
        {
            key: 'kontak',
            header: 'Kontak',
            width: 'w-40',
            cell: (row) => <span className="num text-muted-foreground text-[13px]">{row.no_hp}</span>,
        },
        {
            key: 'kelamin',
            header: 'Jenis kelamin',
            width: 'w-36',
            cell: (row) => (
                <span className="text-muted-foreground text-[13px]">
                    {kelamin[row.jenis_kelamin as string] ?? row.jenis_kelamin}
                </span>
            ),
        },
        {
            key: 'lahir',
            header: 'Tanggal lahir',
            width: 'w-40',
            cell: (row) => <span className="num text-muted-foreground text-[13px]">{tanggal(row.tanggal_lahir)}</span>,
        },
        {
            key: 'alamat',
            header: 'Alamat',
            hideOnCard: true,
            cell: (row) => (
                <span className="text-muted-foreground line-clamp-1 text-[13px]" title={row.alamat}>
                    {row.alamat}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            badge: true,
            width: 'w-32',
            cell: (row) => (
                <StatusBadge tone={row.status === 'Aktif' ? 'positive' : 'outline'}>{row.status}</StatusBadge>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Karyawan" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Karyawan"
                    description="Daftar pekerja beserta jabatan dan status penempatannya"
                    actions={
                        hasAnyPermission(['karyawans create']) ? (
                            <Button asChild className="h-11 w-full sm:h-9 sm:w-auto">
                                <Link href={karyawans.create()}>
                                    <PlusCircle />
                                    Tambah karyawan
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(karyawans.index().url, { search }, { preserveState: true, replace: true });
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
                    actions={(row) => (
                        <div className="flex w-full items-center gap-2 sm:w-auto sm:justify-end">
                            {hasAnyPermission(['karyawans edit']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 flex-1 sm:h-8 sm:flex-none">
                                    <Link href={karyawans.edit(row.id)}>
                                        <SquarePen />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['karyawans delete']) && (
                                <DeleteButton id={row.id} featured="karyawans" />
                            )}
                        </div>
                    )}
                    empty={
                        <EmptyState
                            icon={Users}
                            title={filters.search ? 'Tidak ada karyawan yang cocok' : 'Belum ada karyawan'}
                            description={
                                filters.search
                                    ? 'Coba kata kunci lain, atau kosongkan pencarian untuk melihat semuanya.'
                                    : 'Tambahkan pekerja terlebih dahulu sebelum menempatkannya pada kontrak.'
                            }
                            action={
                                !filters.search && hasAnyPermission(['karyawans create']) ? (
                                    <Button asChild>
                                        <Link href={karyawans.create()}>Tambah karyawan pertama</Link>
                                    </Button>
                                ) : null
                            }
                        />
                    }
                />

                <Pagination meta={list} noun="karyawan" />
            </div>
        </AppLayout>
    );
}
