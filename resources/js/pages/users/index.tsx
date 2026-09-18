import { Head, Link, router } from '@inertiajs/react';
import { PlusCircle, Search, SquarePen, UserRound } from 'lucide-react';
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
import hasAnyPermission from '@/lib/utils';
import users from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

interface UserRow {
    id: number;
    name: string;
    email: string;
    roles?: { id: number; name: string }[];
}

interface Props {
    users: Paginated<UserRow>;
    filters: { search?: string };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: users.index().url },
];

export default function UserIndex({ users: list, filters }: Props) {
    useFlashToast();
    const [search, setSearch] = useState(filters.search ?? '');

    const columns: DataColumn<UserRow>[] = [
        {
            key: 'nama',
            header: 'Nama',
            primary: true,
            cell: (row) => (
                <div className="flex flex-col gap-0.5">
                    <span className="font-semibold">{row.name}</span>
                    <span className="text-xs text-muted-foreground">
                        {row.email}
                    </span>
                </div>
            ),
        },
        {
            key: 'peran',
            header: 'Peran',
            cell: (row) => (
                <div className="flex flex-wrap justify-end gap-1.5 sm:justify-start">
                    {(row.roles ?? []).length === 0 ? (
                        <span className="text-[13px] text-muted-foreground">
                            —
                        </span>
                    ) : (
                        (row.roles ?? []).map((role) => (
                            <StatusBadge key={role.id} tone="neutral">
                                {role.name}
                            </StatusBadge>
                        ))
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Users"
                    description="Akun yang dapat masuk ke sistem beserta perannya"
                    actions={
                        hasAnyPermission(['users create']) ? (
                            <Button
                                asChild
                                className="h-11 w-full sm:h-9 sm:w-auto"
                            >
                                <Link href={users.create()}>
                                    <PlusCircle />
                                    Tambah user
                                </Link>
                            </Button>
                        ) : null
                    }
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            users.index().url,
                            { search },
                            { preserveState: true, replace: true },
                        );
                    }}
                    className="flex gap-2 sm:max-w-md"
                >
                    <div className="relative flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Cari nama atau email"
                            className="h-11 pl-9 sm:h-9"
                        />
                    </div>
                    <Button
                        type="submit"
                        variant="outline"
                        className="h-11 sm:h-9"
                    >
                        Cari
                    </Button>
                </form>

                <DataList
                    columns={columns}
                    rows={list.data}
                    rowKey={(row) => row.id}
                    actions={(row) => (
                        <div className="flex w-full items-center gap-2 sm:w-auto sm:justify-end">
                            {hasAnyPermission(['users edit']) && (
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="h-11 flex-1 sm:h-8 sm:flex-none"
                                >
                                    <Link href={users.edit(row.id)}>
                                        <SquarePen />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {hasAnyPermission(['users delete']) && (
                                <DeleteButton id={row.id} featured="users" />
                            )}
                        </div>
                    )}
                    empty={
                        <EmptyState
                            icon={UserRound}
                            title={
                                filters.search
                                    ? 'Tidak ada user yang cocok'
                                    : 'Belum ada user'
                            }
                            description="User adalah akun yang dapat masuk ke sistem. Perannya menentukan apa yang boleh diaksesnya."
                        />
                    }
                />

                <Pagination meta={list} noun="user" />
            </div>
        </AppLayout>
    );
}
