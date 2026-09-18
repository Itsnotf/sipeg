import { Form, Head } from '@inertiajs/react';

import Field from '@/components/form/field';
import PasswordInput from '@/components/form/password-input';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    return (
        <AuthLayout
            title="Masuk"
            description="Gunakan akun yang diberikan administrator untuk masuk ke SIPEG"
        >
            <Head title="Masuk" />

            {status && (
                <div className="rounded-sm border border-ok/30 bg-ok/10 px-3 py-2.5 text-sm text-ok">
                    {status}
                </div>
            )}

            {/*
                Tanpa tabIndex manual: urutan DOM sudah benar, dan angka yang
                ditulis tangan justru membuat sakelar kata sandi terlewat.
            */}
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                disableWhileProcessing
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <Field id="email" label="Email" error={errors.email}>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                autoComplete="email"
                                placeholder="nama@perusahaan.co.id"
                                className="h-12 sm:h-10"
                            />
                        </Field>

                        <div className="flex flex-col gap-2">
                            <div className="flex items-baseline justify-between gap-3">
                                <Label htmlFor="password">Kata sandi</Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="text-xs"
                                    >
                                        Lupa kata sandi?
                                    </TextLink>
                                )}
                            </div>

                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoComplete="current-password"
                                placeholder="Kata sandi"
                                className="h-12 sm:h-10"
                            />

                            <InputError message={errors.password} />
                        </div>

                        <label
                            htmlFor="remember"
                            className="flex w-fit cursor-pointer items-center gap-2.5 text-sm"
                        >
                            <Checkbox id="remember" name="remember" />
                            Biarkan saya tetap masuk
                        </label>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-12 w-full sm:h-10"
                            data-test="login-button"
                        >
                            {processing ? (
                                <>
                                    <Spinner />
                                    Memeriksa…
                                </>
                            ) : (
                                'Masuk'
                            )}
                        </Button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
