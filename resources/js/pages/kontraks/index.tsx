import { Head, Link, router } from '@inertiajs/react';
import { DollarSign, Eye, FileText, PlusCircle, ScrollText, Search, SquarePen, Users } from 'lucide-react';
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
import hasAnyPermission, { rupiah, tanggal, tanggalRingkas } from '@/lib/utils';
import kontraks from '@/routes/kontraks';
import type { BreadcrumbItem, Kontrak } from '@/types';

interface Props {
    kontraks: Paginated<Kontrak>;
    filters: { search?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Kontrak', href: kontraks.index().url }];

const nada: Record<string, 'positive' | 'warning' | 'outline'> = {
    Progres: 'positive',
    Pending: 'warning',
    Selesai: 'outline',
};

export default function KontrakIndex({ kontraks: list, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');


    const columns: DataColumn<Kontrak>[] = [
        {
            key: 'judul',
            header: 'Kontrak',
            primary: true,
            cell: (row) => (
                <div className="flex flex-col gap-0.5">
                    <span className="font-semibold">{row.judul}</span>
                    <span className="text-muted-foreground text-xs">{row.client?.nama_client ?? '—'}</span>
                </div>
            ),
        },
        {
            key: 'rentang',
            header: 'Rentang kontrak',
            width: 'w-56',
            cell: (row) => (
                <span className="num text-muted-foreground text-[13px]">
                    {tanggalRingkas(row.tanggal_mulai)} – {tanggal(row.tanggal_selesai)}
                </span>
            ),
        },
        {
            key: 'gajian',
            header: 'Tanggal gajian',
            width: 'w-36',
            cell: (row) => (
                <span className="num text-muted-foreground text-[13px]">tiap tanggal {row.tanggal_gajian}</span>
            ),
        },
        {
            key: 'biaya',
            header: 'Nilai kontrak',
            className: 'text-right',
            width: 'w-44',
            cell: (row) => <span className="num text-sm">{rupiah(row.total_biaya)}</span>,
        },
        {
            key: 'status',
            header: 'Status',
            badge: true,
            width: 'w-32',
            cell: (row) => (
                <StatusBadge tone={nada[row.status as string] ?? 'neutral'}>{row.status}</StatusBadge>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kontrak" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Kontrak"
                    description="Perjanjian dengan klien beserta nilai dan jadwal penggajiannya"
                    actions={
                        hasAnyPermission(['kontraks create']) ? (
                            <Button asChild className="h-11 w-full sm:h-9 sm:w-auto">
                                <Link href={kontraks.create()}>
                                    <PlusCircle />
                                    Buat kontrak
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(kontraks.index().url, { search }, { preserveState: true, replace: true });
                    }}
                    className="flex gap-2 sm:max-w-md"
                >
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari judul atau deskripsi"
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
                        <div className="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:justify-end">
                            <Button asChild variant="outline" size="sm" className="h-11 sm:h-8">
                                <Link href={kontraks.show(row.id)}>
                                    <Eye />
                                    Detail
                                </Link>
                            </Button>
                            {hasAnyPermission(['penggajians index']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 sm:h-8">
                                    <Link href={kontraks.penggajians.index(row.id)}>
                                        <DollarSign />
                                        Gaji
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['kontraks karyawans index']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 sm:h-8">
                                    <Link href={kontraks.karyawans.index(row.id)}>
                                        <Users />
                                        Pekerja
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['kontraks dokumens index']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 sm:h-8">
                                    <Link href={kontraks.dokumens.index(row.id)}>
                                        <FileText />
                                        Dokumen
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['kontraks edit']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 sm:h-8">
                                    <Link href={kontraks.edit(row.id)}>
                                        <SquarePen />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['kontraks delete']) && <DeleteButton id={row.id} featured="kontraks" />}
                        </div>
                    )}
                    empty={
                        <EmptyState
                            icon={ScrollText}
                            title={filters.search ? 'Tidak ada kontrak yang cocok' : 'Belum ada kontrak'}
                            description={
                                filters.search
                                    ? 'Coba kata kunci lain, atau kosongkan pencarian untuk melihat semuanya.'
                                    : 'Kontrak menentukan jadwal penggajian — buat satu untuk mulai memproses gaji.'
                            }
                            action={
                                !filters.search && hasAnyPermission(['kontraks create']) ? (
                                    <Button asChild>
                                        <Link href={kontraks.create()}>Buat kontrak pertama</Link>
                                    </Button>
                                ) : null
                            }
                        />
                    }
                />

                <Pagination meta={list} noun="kontrak" />
            </div>
        </AppLayout>
    );
}
