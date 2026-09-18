import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import kontraks from '@/routes/kontraks';
import dokumens, { store } from '@/routes/kontraks/dokumens';
import type { BreadcrumbItem } from '@/types';

interface Props {
    kontrak_id: string;
}

const FORMAT = 'PDF, DOC, DOCX, JPG, atau PNG';

export default function DokumenCreate({ kontrak_id }: Props) {
    useFlashToast();

    const [namaBerkas, setNamaBerkas] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontrak', href: kontraks.index().url },
        { title: 'Dokumen', href: dokumens.index(kontrak_id).url },
        { title: 'Unggah', href: dokumens.create(kontrak_id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Unggah dokumen" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Unggah dokumen"
                    description="Berkas tersimpan melekat pada kontrak ini dan dapat diunduh kembali dari daftar dokumen"
                />

                <Form
                    {...store.form(kontrak_id)}
                    encType="multipart/form-data"
                    disableWhileProcessing
                    className="flex max-w-xl flex-col gap-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <Field id="nama_dokumen" label="Nama dokumen" error={errors.nama_dokumen}>
                                <Input
                                    id="nama_dokumen"
                                    name="nama_dokumen"
                                    required
                                    autoFocus
                                    placeholder="Surat perjanjian kerja sama"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="file"
                                label="Berkas"
                                error={errors.file}
                                hint={namaBerkas ? `Dipilih: ${namaBerkas}` : `Format ${FORMAT}, maksimal 2 MB.`}
                            >
                                <Input
                                    id="file"
                                    name="file"
                                    type="file"
                                    required
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                    onChange={(e) => setNamaBerkas(e.target.files?.[0]?.name ?? null)}
                                    className="h-12 file:mr-3 file:text-sm sm:h-9"
                                />
                            </Field>

                            <FormActions
                                processing={processing}
                                simpan="Unggah dokumen"
                                batalKe={dokumens.index(kontrak_id).url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
