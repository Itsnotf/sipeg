import { Form, Head } from '@inertiajs/react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import clients, { store } from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Client', href: clients.index().url },
    { title: 'Tambah', href: clients.create().url },
];

export default function ClientCreate() {
    useFlashToast();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah client" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Tambah client"
                    description="Perusahaan pemberi kerja. Kontrak dan penempatan pekerja dicatat di bawah client ini"
                />

                <Form {...store.form()} disableWhileProcessing className="flex max-w-xl flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <Field id="nama_client" label="Nama client" error={errors.nama_client}>
                                <Input
                                    id="nama_client"
                                    name="nama_client"
                                    required
                                    autoFocus
                                    autoComplete="organization"
                                    placeholder="PT Sumber Makmur"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="alamat" label="Alamat" error={errors.alamat}>
                                <Input
                                    id="alamat"
                                    name="alamat"
                                    required
                                    autoComplete="street-address"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field id="email" label="Email" error={errors.email}>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        required
                                        autoComplete="email"
                                        placeholder="kontak@perusahaan.co.id"
                                        className="h-12 sm:h-9"
                                    />
                                </Field>

                                <Field id="no_hp" label="Nomor HP" error={errors.no_hp}>
                                    <Input
                                        id="no_hp"
                                        name="no_hp"
                                        type="tel"
                                        inputMode="tel"
                                        maxLength={15}
                                        required
                                        autoComplete="tel"
                                        className="num h-12 sm:h-9"
                                    />
                                </Field>
                            </div>

                            <Field id="deskripsi" label="Deskripsi" error={errors.deskripsi} opsional>
                                <Input
                                    id="deskripsi"
                                    name="deskripsi"
                                    placeholder="Bidang usaha, catatan kerja sama"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <FormActions processing={processing} simpan="Simpan client" batalKe={clients.index().url} />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
