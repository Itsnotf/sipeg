import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import { rupiah } from '@/lib/utils';
import jabatans, { update } from '@/routes/jabatans';
import type { BreadcrumbItem, Jabatan } from '@/types';

interface Props {
    jabatan: Jabatan;
}

export default function JabatanEdit({ jabatan }: Props) {
    useFlashToast();

    const [gaji, setGaji] = useState(String(jabatan.gaji ?? ''));
    const [persen, setPersen] = useState(String(jabatan.bpjs_persen ?? ''));

    const bpjsPerBulan = Number(gaji || 0) * (Number(persen || 0) / 100);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Jabatan', href: jabatans.index().url },
        { title: jabatan.nama_jabatan, href: jabatans.edit(jabatan.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${jabatan.nama_jabatan}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Ubah jabatan"
                    description="Perubahan gaji berlaku pada periode penggajian berikutnya; slip yang sudah tersusun memakai nominal saat itu"
                />

                <Form {...update.form(jabatan.id)} disableWhileProcessing className="flex max-w-xl flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <Field id="nama_jabatan" label="Nama jabatan" error={errors.nama_jabatan}>
                                <Input
                                    id="nama_jabatan"
                                    name="nama_jabatan"
                                    required
                                    autoFocus
                                    autoComplete="organization-title"
                                    defaultValue={jabatan.nama_jabatan}
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="deskripsi" label="Deskripsi" error={errors.deskripsi}>
                                <Input
                                    id="deskripsi"
                                    name="deskripsi"
                                    required
                                    defaultValue={jabatan.deskripsi}
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="gaji"
                                label="Gaji pokok per bulan"
                                error={errors.gaji}
                                hint={gaji ? rupiah(gaji) : 'Nominal sebelum BPJS dan potongan'}
                            >
                                <Input
                                    id="gaji"
                                    name="gaji"
                                    type="number"
                                    inputMode="numeric"
                                    min="0"
                                    step="1000"
                                    required
                                    value={gaji}
                                    onChange={(e) => setGaji(e.target.value)}
                                    className="num h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="bpjs_persen"
                                label="BPJS (%)"
                                error={errors.bpjs_persen}
                                hint={
                                    gaji
                                        ? `${rupiah(bpjsPerBulan)} per bulan, dihitung ulang otomatis bila gaji berubah`
                                        : 'Persentase dari gaji pokok, bukan nominal tetap'
                                }
                            >
                                <Input
                                    id="bpjs_persen"
                                    name="bpjs_persen"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    required
                                    value={persen}
                                    onChange={(e) => setPersen(e.target.value)}
                                    className="num h-12 sm:h-9"
                                />
                            </Field>

                            <FormActions
                                processing={processing}
                                simpan="Simpan perubahan"
                                batalKe={jabatans.index().url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
