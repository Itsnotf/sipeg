import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import { rupiah } from '@/lib/utils';
import jabatans, { store } from '@/routes/jabatans';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Jabatan', href: jabatans.index().url },
    { title: 'Tambah', href: jabatans.create().url },
];

export default function JabatanCreate() {
    useFlashToast();

    const [gaji, setGaji] = useState('');
    const [persen, setPersen] = useState('5');

    const bpjsPerBulan = Number(gaji || 0) * (Number(persen || 0) / 100);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah jabatan" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Tambah jabatan"
                    description="Gaji pokok dan persentase BPJS di sini menjadi dasar perhitungan slip setiap karyawan pada jabatan ini"
                />

                <Form {...store.form()} disableWhileProcessing className="flex max-w-xl flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <Field id="nama_jabatan" label="Nama jabatan" error={errors.nama_jabatan}>
                                <Input
                                    id="nama_jabatan"
                                    name="nama_jabatan"
                                    required
                                    autoFocus
                                    autoComplete="organization-title"
                                    placeholder="Cleaning Service"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="deskripsi" label="Deskripsi" error={errors.deskripsi}>
                                <Input
                                    id="deskripsi"
                                    name="deskripsi"
                                    required
                                    placeholder="Ruang lingkup pekerjaan"
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
                                    placeholder="3500000"
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

                            <FormActions processing={processing} simpan="Simpan jabatan" batalKe={jabatans.index().url} />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
