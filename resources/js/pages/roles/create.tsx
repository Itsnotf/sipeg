import { Form, Head } from '@inertiajs/react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PermissionPicker, { type IzinRingkas } from '@/components/form/permission-picker';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import roles, { store } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';

interface Props {
    permissions: IzinRingkas[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Role', href: roles.index().url },
    { title: 'Tambah', href: roles.create().url },
];

export default function RoleCreate({ permissions }: Props) {
    useFlashToast();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah role" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Tambah role"
                    description="Izin yang dicentang menentukan menu apa yang terlihat dan tindakan apa yang boleh dilakukan"
                />

                <Form {...store.form()} disableWhileProcessing className="flex flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <Field
                                id="name"
                                label="Nama role"
                                error={errors.name}
                                hint="Misalnya: admin, staf keuangan, supervisor"
                                className="max-w-xl"
                            >
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="off"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="permissions" label="Izin" error={errors.permissions} opsional>
                                <PermissionPicker permissions={permissions} />
                            </Field>

                            <FormActions processing={processing} simpan="Simpan role" batalKe={roles.index().url} />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
