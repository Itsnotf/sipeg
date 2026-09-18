import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import PlafonMeter, { type KaryawanPlafon } from '@/components/plafon-meter';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import { rupiah } from '@/lib/utils';
import cashbons, { store } from '@/routes/cashbons';
import type { BreadcrumbItem } from '@/types';

interface Props {
    karyawans: KaryawanPlafon[];
    kebijakan: { maks_hutang_bulan: number; batas_potongan: number };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cashbon', href: cashbons.index().url },
    { title: 'Buat', href: cashbons.create().url },
];

export default function CashbonCreate({ karyawans, kebijakan }: Props) {
    useFlashToast();
    const [karyawanId, setKaryawanId] = useState('');
    const [jumlah, setJumlah] = useState('');


    const terpilih = karyawans.find((karyawan) => String(karyawan.id) === karyawanId);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat cashbon" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Buat cashbon"
                    description="Pinjaman akan terpotong otomatis dari gaji secara mencicil"
                />

                <Form {...store.form()} disableWhileProcessing className="flex max-w-xl flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="karyawan_id" value={karyawanId} />

                            <Field id="karyawan" label="Karyawan" error={errors.karyawan_id}>
                                <Select value={karyawanId} onValueChange={setKaryawanId}>
                                    <SelectTrigger id="karyawan" className="h-12 sm:h-9">
                                        <SelectValue placeholder="Pilih karyawan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {karyawans.map((karyawan) => (
                                            <SelectItem key={karyawan.id} value={String(karyawan.id)}>
                                                {karyawan.nama} — {karyawan.jabatan.nama_jabatan}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>

                            {terpilih ? (
                                <PlafonMeter
                                    karyawan={terpilih}
                                    jumlah={Number(jumlah) || 0}
                                    maksHutangBulan={kebijakan.maks_hutang_bulan}
                                />
                            ) : (
                                <p className="text-muted-foreground bg-muted/50 rounded-lg border border-dashed p-4 text-sm">
                                    Pilih karyawan untuk melihat sisa plafon pinjamannya.
                                </p>
                            )}

                            <Field
                                id="jumlah"
                                label="Jumlah pinjaman"
                                error={errors.jumlah}
                                hint={jumlah ? rupiah(jumlah) : undefined}
                            >
                                <Input
                                    id="jumlah"
                                    name="jumlah"
                                    type="number"
                                    min={1}
                                    required
                                    value={jumlah}
                                    onChange={(event) => setJumlah(event.target.value)}
                                    placeholder="0"
                                    className="num h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="keterangan" label="Keterangan" error={errors.keterangan}>
                                <Input
                                    id="keterangan"
                                    name="keterangan"
                                    type="text"
                                    required
                                    placeholder="Mis. pinjaman biaya sekolah"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <FormActions
                                processing={processing}
                                simpan="Simpan cashbon"
                                batalKe={cashbons.index().url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
