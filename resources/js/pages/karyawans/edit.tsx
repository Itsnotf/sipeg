import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import karyawans, { update } from '@/routes/karyawans';
import type { BreadcrumbItem, Jabatan, Karyawan } from '@/types';

interface Opsi {
    value: string;
    label: string;
}

interface Props {
    karyawan: Karyawan;
    jabatans: Jabatan[];
    opsi: { jenis_kelamin: Opsi[] };
}

export default function KaryawanEdit({ karyawan, jabatans, opsi }: Props) {
    useFlashToast();

    const [jabatan, setJabatan] = useState(String(karyawan.id_jabatan));
    const [kelamin, setKelamin] = useState(String(karyawan.jenis_kelamin));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Karyawan', href: karyawans.index().url },
        { title: karyawan.nama, href: karyawans.edit(karyawan.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${karyawan.nama}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Ubah karyawan"
                    description="Mengubah jabatan akan berlaku pada periode penggajian berikutnya, bukan pada slip yang sudah tersusun"
                />

                <Form {...update.form(karyawan.id)} disableWhileProcessing className="flex max-w-xl flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="id_jabatan" value={jabatan} />
                            <input type="hidden" name="jenis_kelamin" value={kelamin} />

                            <Field id="nama" label="Nama lengkap" error={errors.nama}>
                                <Input
                                    id="nama"
                                    name="nama"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    defaultValue={karyawan.nama}
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="nik" label="NIK" error={errors.nik} hint="16 digit sesuai KTP">
                                <Input
                                    id="nik"
                                    name="nik"
                                    type="text"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    maxLength={16}
                                    required
                                    defaultValue={karyawan.nik}
                                    className="num h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="id_jabatan" label="Jabatan" error={errors.id_jabatan}>
                                <Select value={jabatan} onValueChange={setJabatan}>
                                    <SelectTrigger id="id_jabatan" className="h-12 sm:h-9">
                                        <SelectValue placeholder="Pilih jabatan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {jabatans.map((j) => (
                                            <SelectItem key={j.id} value={String(j.id)}>
                                                {j.nama_jabatan}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field id="jenis_kelamin" label="Jenis kelamin" error={errors.jenis_kelamin}>
                                    <Select value={kelamin} onValueChange={setKelamin}>
                                        <SelectTrigger id="jenis_kelamin" className="h-12 sm:h-9">
                                            <SelectValue placeholder="Pilih" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opsi.jenis_kelamin.map((o) => (
                                                <SelectItem key={o.value} value={o.value}>
                                                    {o.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>

                                <Field id="tanggal_lahir" label="Tanggal lahir" error={errors.tanggal_lahir}>
                                    <Input
                                        id="tanggal_lahir"
                                        name="tanggal_lahir"
                                        type="date"
                                        required
                                        autoComplete="bday"
                                        defaultValue={karyawan.tanggal_lahir}
                                        className="num h-12 sm:h-9"
                                    />
                                </Field>
                            </div>

                            <Field id="no_hp" label="Nomor HP" error={errors.no_hp}>
                                <Input
                                    id="no_hp"
                                    name="no_hp"
                                    type="tel"
                                    inputMode="tel"
                                    maxLength={15}
                                    required
                                    autoComplete="tel"
                                    defaultValue={karyawan.no_hp}
                                    className="num h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="alamat" label="Alamat" error={errors.alamat}>
                                <Input
                                    id="alamat"
                                    name="alamat"
                                    required
                                    autoComplete="street-address"
                                    defaultValue={karyawan.alamat}
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            {/*
                                Status bukan bidang isian: nilainya diturunkan
                                dari penempatan. Menyetelnya "Aktif" dengan
                                tangan dahulu membuat pekerja hilang dari daftar
                                yang tersedia untuk ditempatkan.
                            */}
                            <div className="border-border bg-muted/40 flex items-baseline justify-between gap-3 rounded-sm border px-3 py-2.5">
                                <span className="text-sm font-medium">Status</span>
                                <span className="text-muted-foreground text-sm">
                                    {karyawan.status} · mengikuti penempatan kontrak
                                </span>
                            </div>

                            <FormActions
                                processing={processing}
                                simpan="Simpan perubahan"
                                batalKe={karyawans.index().url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
