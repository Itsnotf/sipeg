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
import users, { update } from '@/routes/users';
import type { BreadcrumbItem, Role, User } from '@/types';

interface Props {
    roles: Role[];
    user: User;
}

export default function UserEdit({ roles, user }: Props) {
    useFlashToast();

    const roleSaatIni = (user.roles as Role[] | undefined)?.[0]?.name ?? '';
    const [role, setRole] = useState(roleSaatIni);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pengguna', href: users.index().url },
        { title: user.name, href: users.edit(user.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${user.name}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Ubah pengguna"
                    description="Kosongkan kata sandi bila tidak ingin menggantinya"
                />

                <Form
                    {...update.form(user.id)}
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
                                    defaultValue={user.name}
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
                                    defaultValue={user.email}
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="password"
                                label="Kata sandi baru"
                                error={errors.password}
                                hint="Biarkan kosong untuk mempertahankan kata sandi lama"
                                opsional
                            >
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    minLength={8}
                                    autoComplete="new-password"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="password_confirmation"
                                label="Ulangi kata sandi baru"
                                error={errors.password_confirmation}
                                opsional
                            >
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
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
                                simpan="Simpan perubahan"
                                batalKe={users.index().url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
