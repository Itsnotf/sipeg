import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PasswordInput from '@/components/form/password-input';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import users, { store } from '@/routes/users';
import type { BreadcrumbItem, Role } from '@/types';

interface Props {
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengguna', href: users.index().url },
    { title: 'Tambah', href: users.create().url },
];

export default function UserCreate({ roles }: Props) {
    useFlashToast();

    const [role, setRole] = useState('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah pengguna" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Tambah pengguna"
                    description="Role menentukan menu dan tindakan yang dapat diakses pengguna ini"
                />

                <Form
                    {...store.form()}
                    resetOnSuccess={['password', 'password_confirmation']}
                    disableWhileProcessing
                    className="flex max-w-xl flex-col gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="role" value={role} />

                            <Field id="name" label="Nama" error={errors.name}>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="email"
                                label="Email"
                                error={errors.email}
                                hint="Dipakai untuk masuk ke sistem"
                            >
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    placeholder="nama@perusahaan.co.id"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="password"
                                label="Kata sandi"
                                error={errors.password}
                                hint="Minimal 8 karakter"
                            >
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    minLength={8}
                                    autoComplete="new-password"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="password_confirmation"
                                label="Ulangi kata sandi"
                                error={errors.password_confirmation}
                            >
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    minLength={8}
                                    autoComplete="new-password"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="role" label="Role" error={errors.role}>
                                <Select value={role} onValueChange={setRole}>
                                    <SelectTrigger
                                        id="role"
                                        className="h-12 sm:h-9"
                                    >
                                        <SelectValue placeholder="Pilih role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((r) => (
                                            <SelectItem
                                                key={r.id}
                                                value={r.name}
                                            >
                                                {r.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>

                            <FormActions
                                processing={processing}
                                simpan="Simpan pengguna"
                                batalKe={users.index().url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
