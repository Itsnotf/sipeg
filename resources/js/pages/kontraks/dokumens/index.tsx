import { Head, Link, router } from '@inertiajs/react';
import { FileText, PlusCircle, Search, SquarePen } from 'lucide-react';
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
import dokumens from '@/routes/kontraks/dokumens';
import type { BreadcrumbItem } from '@/types';

interface Dokumen {
    id: number;
    kontrak_id: number;
    nama_dokumen: string;
    file: string | null;
    diunggah: string | null;
}

interface Props {
    kontrak_id: string;
    kontrak: { id: number; judul: string };
    dokumens: Paginated<Dokumen>;
    filters: { search?: string };
}

export default function DokumenIndex({ kontrak_id, kontrak, dokumens: list, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontrak', href: kontraks.index().url },
        { title: kontrak.judul, href: kontraks.show(kontrak.id).url },
        { title: 'Dokumen', href: dokumens.index(kontrak_id).url },
    ];

    const columns: DataColumn<Dokumen>[] = [
        {
            key: 'nama',
            header: 'Dokumen',
            primary: true,
            cell: (row) => <span className="font-semibold">{row.nama_dokumen}</span>,
        },
        {
            key: 'berkas',
            header: 'Berkas',
            cell: (row) =>
                row.file ? (
                    <a
                        href={`/storage/${row.file}`}
                        target="_blank"
                        rel="noreferrer"
                        className="text-muted-foreground hover:text-foreground line-clamp-1 text-[13px] underline underline-offset-4"
                    >
                        {row.file.split('/').pop()}
                    </a>
                ) : (
                    <span className="text-muted-foreground text-[13px]">—</span>
                ),
        },
        {
            key: 'diunggah',
            header: 'Diunggah',
            width: 'w-44',
            cell: (row) => <span className="num text-muted-foreground text-[13px]">{tanggal(row.diunggah)}</span>,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Dokumen · ${kontrak.judul}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    eyebrow={kontrak.judul}
                    title="Dokumen kontrak"
                    description="Berkas perjanjian, adendum, dan lampiran lain yang melekat pada kontrak ini"
                    actions={
                        hasAnyPermission(['kontraks dokumens create']) ? (
                            <Button asChild className="h-11 w-full sm:h-9 sm:w-auto">
                                <Link href={dokumens.create(kontrak_id)}>
                                    <PlusCircle />
                                    Unggah dokumen
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(dokumens.index(kontrak_id).url, { search }, { preserveState: true, replace: true });
                    }}
                    className="flex gap-2 sm:max-w-md"
                >
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama dokumen"
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
                            {hasAnyPermission(['kontraks dokumens edit']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 flex-1 sm:h-8 sm:flex-none">
                                    <Link href={dokumens.edit([kontrak_id, row.id])}>
                                        <SquarePen />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {/* Sebelumnya tombol ini diuji dengan izin "karyawans delete". */}
                            {hasAnyPermission(['kontraks dokumens delete']) && (
                                <DeleteButtonChild
                                    id={row.kontrak_id}
                                    featured="kontraks"
                                    child="dokumens"
                                    child_id={row.id}
                                    nama={row.nama_dokumen}
                                />
                            )}
                        </div>
                    )}
                    empty={
                        <EmptyState
                            icon={FileText}
                            title={filters.search ? 'Tidak ada dokumen yang cocok' : 'Belum ada dokumen'}
                            description={
                                filters.search
                                    ? 'Coba kata kunci lain, atau kosongkan pencarian untuk melihat semuanya.'
                                    : 'Unggah surat perjanjian atau lampiran lain agar tersimpan bersama kontrak ini.'
                            }
                            action={
                                !filters.search && hasAnyPermission(['kontraks dokumens create']) ? (
                                    <Button asChild>
                                        <Link href={dokumens.create(kontrak_id)}>Unggah dokumen pertama</Link>
                                    </Button>
                                ) : null
                            }
                        />
                    }
                />

                <Pagination meta={list} noun="dokumen" />
            </div>
        </AppLayout>
    );
}
