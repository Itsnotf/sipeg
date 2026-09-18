import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import kontraks from '@/routes/kontraks';
import dokumens, { update } from '@/routes/kontraks/dokumens';
import type { BreadcrumbItem, KontrakDokumen } from '@/types';

interface Props {
    dokumen: KontrakDokumen;
    kontrak_id: string;
}

const FORMAT = 'PDF, DOC, DOCX, JPG, atau PNG';

export default function DokumenEdit({ dokumen, kontrak_id }: Props) {
    useFlashToast();

    const [namaBerkas, setNamaBerkas] = useState<string | null>(null);

    const berkasSaatIni = dokumen.file?.split('/').pop();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontrak', href: kontraks.index().url },
        { title: 'Dokumen', href: dokumens.index(kontrak_id).url },
        { title: dokumen.nama_dokumen, href: dokumens.edit([kontrak_id, dokumen.id]).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${dokumen.nama_dokumen}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Ubah dokumen"
                    description="Berkas lama baru dihapus setelah berkas pengganti berhasil tersimpan"
                />

                {/*
                    Formulir bawaan Inertia, bukan FormData rakitan tangan: versi
                    sebelumnya menyusun sendiri _method dan menanganinya lewat
                    `formData as any`, sehingga galat validasi tidak pernah
                    sampai ke layar dengan benar.
                */}
                <Form
                    {...update.form([kontrak_id, dokumen.id])}
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
                                    defaultValue={dokumen.nama_dokumen}
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="file"
                                label="Ganti berkas"
                                error={errors.file}
                                opsional
                                hint={
                                    namaBerkas
                                        ? `Akan menggantikan berkas lama dengan ${namaBerkas}`
                                        : `Biarkan kosong untuk mempertahankan berkas yang ada. Format ${FORMAT}, maksimal 2 MB.`
                                }
                            >
                                <Input
                                    id="file"
                                    name="file"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                    onChange={(e) => setNamaBerkas(e.target.files?.[0]?.name ?? null)}
                                    className="h-12 file:mr-3 file:text-sm sm:h-9"
                                />
                            </Field>

                            {berkasSaatIni ? (
                                <p className="text-muted-foreground text-sm">
                                    Berkas saat ini:{' '}
                                    <a
                                        href={`/storage/${dokumen.file}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-foreground underline underline-offset-4"
                                    >
                                        {berkasSaatIni}
                                    </a>
                                </p>
                            ) : null}

                            <FormActions
                                processing={processing}
                                simpan="Simpan perubahan"
                                batalKe={dokumens.index(kontrak_id).url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
