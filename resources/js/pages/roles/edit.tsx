import { Form, Head } from '@inertiajs/react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PermissionPicker, { type IzinRingkas } from '@/components/form/permission-picker';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import roles, { update } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';

interface Props {
    role: { id: number; name: string; permissions: string[] };
    permissions: IzinRingkas[];
}

export default function RoleEdit({ role, permissions }: Props) {
    useFlashToast();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Role', href: roles.index().url },
        { title: role.name, href: roles.edit(role.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${role.name}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Ubah role"
                    description="Perubahan izin berlaku pada kunjungan halaman berikutnya bagi pengguna yang memakai role ini"
                />

                <Form {...update.form(role.id)} disableWhileProcessing className="flex flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <Field id="name" label="Nama role" error={errors.name} className="max-w-xl">
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="off"
                                    defaultValue={role.name}
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="permissions" label="Izin" error={errors.permissions} opsional>
                                <PermissionPicker permissions={permissions} terpilihAwal={role.permissions} />
                            </Field>

                            <FormActions
                                processing={processing}
                                simpan="Simpan perubahan"
                                batalKe={roles.index().url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
