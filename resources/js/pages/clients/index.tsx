import { Head, Link, router } from '@inertiajs/react';
import { Contact, PlusCircle, Search, SquarePen } from 'lucide-react';
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
import hasAnyPermission from '@/lib/utils';
import clients from '@/routes/clients';
import type { BreadcrumbItem, Client } from '@/types';

interface Props {
    clients: Paginated<Client>;
    filters: { search?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Client', href: clients.index().url }];

export default function ClientIndex({ clients: list, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');


    const columns: DataColumn<Client>[] = [
        {
            key: 'nama',
            header: 'Client',
            primary: true,
            cell: (row) => (
                <div className="flex flex-col gap-0.5">
                    <span className="font-semibold">{row.nama_client}</span>
                    <span className="text-muted-foreground line-clamp-1 text-xs">{row.deskripsi}</span>
                </div>
            ),
        },
        {
            key: 'email',
            header: 'Email',
            width: 'w-64',
            cell: (row) => <span className="text-muted-foreground text-[13px]">{row.email}</span>,
        },
        {
            key: 'hp',
            header: 'No HP',
            width: 'w-40',
            cell: (row) => <span className="num text-muted-foreground text-[13px]">{row.no_hp}</span>,
        },
        {
            key: 'alamat',
            header: 'Alamat',
            cell: (row) => (
                <span className="text-muted-foreground line-clamp-1 text-[13px]" title={row.alamat}>
                    {row.alamat}
                </span>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Client" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Client"
                    description="Perusahaan yang menandatangani kontrak penyediaan tenaga kerja"
                    actions={
                        hasAnyPermission(['clients create']) ? (
                            <Button asChild className="h-11 w-full sm:h-9 sm:w-auto">
                                <Link href={clients.create()}>
                                    <PlusCircle />
                                    Tambah client
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(clients.index().url, { search }, { preserveState: true, replace: true });
                    }}
                    className="flex gap-2 sm:max-w-md"
                >
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama client"
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
                            {hasAnyPermission(['clients edit']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 flex-1 sm:h-8 sm:flex-none">
                                    <Link href={clients.edit(row.id)}>
                                        <SquarePen />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['clients delete']) && <DeleteButton id={row.id} featured="clients" />}
                        </div>
                    )}
                    empty={
                        <EmptyState
                            icon={Contact}
                            title={filters.search ? 'Tidak ada client yang cocok' : 'Belum ada client'}
                            description={
                                filters.search
                                    ? 'Coba kata kunci lain, atau kosongkan pencarian untuk melihat semuanya.'
                                    : 'Client adalah perusahaan yang akan dikontrak — tambahkan satu untuk mulai.'
                            }
                            action={
                                !filters.search && hasAnyPermission(['clients create']) ? (
                                    <Button asChild>
                                        <Link href={clients.create()}>Tambah client pertama</Link>
                                    </Button>
                                ) : null
                            }
                        />
                    }
                />

                <Pagination meta={list} noun="client" />
            </div>
        </AppLayout>
    );
}
