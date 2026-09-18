import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/form/field';
import FormActions from '@/components/form/form-actions';
import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import { rupiah } from '@/lib/utils';
import kontraks, { store } from '@/routes/kontraks';
import type { BreadcrumbItem, Client } from '@/types';

interface Opsi {
    value: string;
    label: string;
}

interface Props {
    clients: Client[];
    opsi: { status: Opsi[] };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kontrak', href: kontraks.index().url },
    { title: 'Tambah', href: kontraks.create().url },
];

export default function KontrakCreate({ clients, opsi }: Props) {
    useFlashToast();

    const [client, setClient] = useState('');
    const [status, setStatus] = useState('Pending');
    const [mulai, setMulai] = useState('');
    const [selesai, setSelesai] = useState('');
    const [biaya, setBiaya] = useState('');
    const [hariGajian, setHariGajian] = useState('25');

    // Aturan yang sama ada di server (`after:tanggal_mulai`); di sini hanya agar
    // keliru urutan terlihat sebelum formulir dikirim, bukan sesudahnya.
    const urutanSalah = Boolean(mulai && selesai && selesai <= mulai);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah kontrak" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Tambah kontrak"
                    description="Periode dan tanggal gajian di sini menentukan kapan penggajian boleh dibuat dan untuk bulan apa saja"
                />

                <Form {...store.form()} disableWhileProcessing className="flex max-w-xl flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            <input type="hidden" name="client_id" value={client} />
                            <input type="hidden" name="status" value={status} />

                            <Field id="client_id" label="Client" error={errors.client_id}>
                                <Select value={client} onValueChange={setClient}>
                                    <SelectTrigger id="client_id" className="h-12 sm:h-9">
                                        <SelectValue placeholder="Pilih client" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {clients.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.nama_client}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>

                            <Field id="judul" label="Judul kontrak" error={errors.judul}>
                                <Input
                                    id="judul"
                                    name="judul"
                                    required
                                    autoFocus
                                    placeholder="Penyediaan tenaga kebersihan 2026"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <Field id="deskripsi" label="Deskripsi" error={errors.deskripsi}>
                                <Input
                                    id="deskripsi"
                                    name="deskripsi"
                                    required
                                    placeholder="Ruang lingkup dan lokasi kerja"
                                    className="h-12 sm:h-9"
                                />
                            </Field>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field id="tanggal_mulai" label="Tanggal mulai" error={errors.tanggal_mulai}>
                                    <Input
                                        id="tanggal_mulai"
                                        name="tanggal_mulai"
                                        type="date"
                                        required
                                        value={mulai}
                                        onChange={(e) => setMulai(e.target.value)}
                                        className="num h-12 sm:h-9"
                                    />
                                </Field>

                                <Field
                                    id="tanggal_selesai"
                                    label="Tanggal selesai"
                                    error={
                                        errors.tanggal_selesai ??
                                        (urutanSalah ? 'Tanggal selesai harus setelah tanggal mulai.' : undefined)
                                    }
                                >
                                    <Input
                                        id="tanggal_selesai"
                                        name="tanggal_selesai"
                                        type="date"
                                        required
                                        min={mulai || undefined}
                                        value={selesai}
                                        onChange={(e) => setSelesai(e.target.value)}
                                        className="num h-12 sm:h-9"
                                    />
                                </Field>
                            </div>

                            <Field
                                id="tanggal_gajian"
                                label="Tanggal gajian"
                                error={errors.tanggal_gajian}
                                hint={`Tanggal ${hariGajian || '—'} tiap bulan. Bila bulannya lebih pendek, gajian jatuh pada hari terakhir bulan itu.`}
                            >
                                <Input
                                    id="tanggal_gajian"
                                    name="tanggal_gajian"
                                    type="number"
                                    inputMode="numeric"
                                    min="1"
                                    max="31"
                                    required
                                    value={hariGajian}
                                    onChange={(e) => setHariGajian(e.target.value)}
                                    className="num h-12 sm:h-9"
                                />
                            </Field>

                            <Field
                                id="status"
                                label="Status"
                                error={errors.status}
                                hint="Hanya kontrak berstatus Progres yang penggajiannya dapat diproses."
                            >
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger id="status" className="h-12 sm:h-9">
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {opsi.status.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>
                                                {o.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>

                            <Field
                                id="total_biaya"
                                label="Nilai kontrak"
                                error={errors.total_biaya}
                                hint={biaya ? rupiah(biaya) : 'Nilai yang ditagihkan ke client selama kontrak berjalan'}
                            >
                                <Input
                                    id="total_biaya"
                                    name="total_biaya"
                                    type="number"
                                    inputMode="numeric"
                                    min="0"
                                    step="1000"
                                    required
                                    value={biaya}
                                    onChange={(e) => setBiaya(e.target.value)}
                                    className="num h-12 sm:h-9"
                                />
                            </Field>

                            <FormActions
                                processing={processing}
                                simpan="Simpan kontrak"
                                batalKe={kontraks.index().url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
