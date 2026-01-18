import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, Form } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import kontraks, { update } from '@/routes/kontraks';
import { BreadcrumbItem, Client, Kontrak } from '@/types';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";


interface Props {
    clients: Client[];
    kontrak: Kontrak;
}



export default function KontrakEditPage({ clients, kontrak }: Props) {
    const [status, setStatus] = useState(kontrak.status);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Kontraks',
            href: kontraks.index().url,
        },
        {
            title: 'Edit',
            href: kontraks.edit(kontrak.id).url,
        },
    ];


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kontrak" />
            <Form
                {...update.form(kontrak.id)}
                className="flex flex-col gap-6 p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="client_id">Client</Label>
                                <Select name="client_id" defaultValue={kontrak.client_id.toString()} required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih client" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {clients.map((client) => (
                                            <SelectItem key={client.id} value={client.id.toString()}>
                                                {client.nama_client}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.client_id}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="judul">Judul</Label>
                                <Input
                                    id="judul"
                                    type="text"
                                    required
                                    tabIndex={1}
                                    autoComplete="judul"
                                    name="judul"
                                    defaultValue={kontrak.judul}
                                    placeholder="Judul Kontrak"
                                />
                                <InputError message={errors.judul} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="deskripsi">Deskripsi</Label>
                                <Input
                                    id="deskripsi"
                                    type="text"
                                    required
                                    defaultValue={kontrak.deskripsi}
                                    tabIndex={2}
                                    autoComplete="deskripsi"
                                    name="deskripsi"
                                    placeholder="Deskripsi Kontrak"
                                />
                                <InputError message={errors.deskripsi} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tanggal_mulai">Tanggal Mulai</Label>
                                <Input
                                    id="tanggal_mulai"
                                    type="date"
                                    required
                                    defaultValue={kontrak.tanggal_mulai}
                                    tabIndex={3}
                                    name="tanggal_mulai"
                                />
                                <InputError message={errors.tanggal_mulai} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tanggal_selesai">Tanggal Selesai</Label>
                                <Input
                                    id="tanggal_selesai"
                                    type="date"
                                    required
                                    defaultValue={kontrak.tanggal_selesai}
                                    tabIndex={4}
                                    name="tanggal_selesai"
                                />
                                <InputError message={errors.tanggal_selesai} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tanggal_gajian">Tanggal Gajian (Hari dalam Sebulan)</Label>
                                <Input
                                    id="tanggal_gajian"
                                    type="number"
                                    required
                                    min="1"
                                    max="31"
                                    defaultValue={kontrak.tanggal_gajian}
                                    tabIndex={4}
                                    name="tanggal_gajian"
                                    placeholder="Masukkan hari (1-31)"
                                />
                                <InputError message={errors.tanggal_gajian} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select name="status" value={status} onValueChange={setStatus} required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Pending">Pending</SelectItem>
                                        <SelectItem value="Progres">Progres</SelectItem>
                                        <SelectItem value="Selesai">Selesai</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="total_biaya">Total Biaya</Label>
                                <Input
                                    id="total_biaya"
                                    type="number"
                                    required
                                    defaultValue={kontrak.total_biaya}
                                    tabIndex={5}
                                    autoComplete="total_biaya"
                                    name="total_biaya"
                                    placeholder="Total Biaya"
                                    step="0.01"
                                />
                                <InputError message={errors.total_biaya} />
                            </div>

                            <div className='space-x-2'>
                                <Button type="submit" className="mt-2 w-fit">
                                    {processing ? (
                                        <>
                                            <Spinner className="mr-2" />    
                                            Saving...
                                        </>
                                    ) : (
                                        'Save changes'
                                    )}
                                </Button>
                                <Link href={'/kontraks'}>
                                    <Button variant='outline' type="button" className="mt-2 w-fit">
                                        Back
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </>
                )}
            </Form>
        </AppLayout>
    );
}
