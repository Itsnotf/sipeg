import { Head, Link, router } from '@inertiajs/react';
import { KeyRound, PlusCircle, Search, SquarePen } from 'lucide-react';
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
import hasAnyPermission, { angka } from '@/lib/utils';
import roles from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';

interface RoleRow {
    id: number;
    name: string;
    permissions?: { id: number; name: string }[];
}

interface Props {
    roles: Paginated<RoleRow>;
    filters: { search?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: roles.index().url }];

export default function RoleIndex({ roles: list, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');


    const columns: DataColumn<RoleRow>[] = [
        {
            key: 'nama',
            header: 'Peran',
            primary: true,
            cell: (row) => <span className="font-semibold">{row.name}</span>,
        },
        {
            key: 'izin',
            header: 'Izin',
            cell: (row) => (
                <span className="num text-muted-foreground text-[13px]">
                    {angka((row.permissions ?? []).length)} izin
                </span>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Roles"
                    description="Peran menentukan halaman dan aksi mana yang boleh diakses seorang user"
                    actions={
                        hasAnyPermission(['roles create']) ? (
                            <Button asChild className="h-11 w-full sm:h-9 sm:w-auto">
                                <Link href={roles.create()}>
                                    <PlusCircle />
                                    Tambah peran
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(roles.index().url, { search }, { preserveState: true, replace: true });
                    }}
                    className="flex gap-2 sm:max-w-md"
                >
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama peran"
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
                            {hasAnyPermission(['roles edit']) && (
                                <Button asChild variant="outline" size="sm" className="h-11 flex-1 sm:h-8 sm:flex-none">
                                    <Link href={roles.edit(row.id)}>
                                        <SquarePen />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['roles delete']) && <DeleteButton id={row.id} featured="roles" />}
                        </div>
                    )}
                    empty={
                        <EmptyState
                            icon={KeyRound}
                            title={filters.search ? 'Tidak ada peran yang cocok' : 'Belum ada peran'}
                            description="Peran mengelompokkan izin, lalu diberikan kepada user."
                        />
                    }
                />

                <Pagination meta={list} noun="peran" />
            </div>
        </AppLayout>
    );
}
