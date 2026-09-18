import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import karyawans, { store } from '@/routes/karyawans';
import type { BreadcrumbItem, Jabatan } from '@/types';

interface Opsi {
    value: string;
    label: string;
}

interface Props {
    jabatans: Jabatan[];
    opsi: { jenis_kelamin: Opsi[] };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Karyawan', href: karyawans.index().url },
    { title: 'Tambah', href: karyawans.create().url },
];

export default function KaryawanCreate({ jabatans, opsi }: Props) {
    useFlashToast();

    const [jabatan, setJabatan] = useState('');
    const [kelamin, setKelamin] = useState('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah karyawan" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Tambah karyawan"
                    description="Pekerja baru dimulai sebagai Non Aktif sampai ditempatkan pada sebuah kontrak"
                />

                <Form {...store.form()} disableWhileProcessing className="flex max-w-xl flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="id_jabatan" value={jabatan} />
                            <input type="hidden" name="jenis_kelamin" value={kelamin} />

                            <Field id="nama" label="Nama lengkap" error={errors.nama}>
                                <Input id="nama" name="nama" required autoFocus autoComplete="name" className="h-12 sm:h-9" />
                            </Field>

                            <Field
                                id="nik"
                                label="NIK"
                                error={errors.nik}
                                hint="16 digit sesuai KTP"
                            >
                                {/*
                                    Bertipe teks, bukan angka: NIK 16 digit melampaui batas aman
                                    bilangan JavaScript dan nol di depannya akan hilang.
                                */}
                                <Input
                                    id="nik"
                                    name="nik"
                                    type="text"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    maxLength={16}
                                    required
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
                                    placeholder="08…"
                                    className="num h-12 sm:h-9"
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

                            <FormActions
                                processing={processing}
                                simpan="Simpan karyawan"
                                batalKe={karyawans.index().url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
