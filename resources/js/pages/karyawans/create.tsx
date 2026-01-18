import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, Form } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { store } from '@/routes/karyawans';
import { BreadcrumbItem, Jabatan } from '@/types';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';

import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import karyawans from '@/routes/karyawans';


interface Props {
   jabatans : Jabatan[]
}


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Karyawans',
        href: karyawans.index().url,
    },
    {
        title: 'Create',
        href: karyawans.create().url,
    },
];

export default function KaryawanCreatePage({ jabatans }: Props) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Karyawans Create" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6 p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="nama">Nama</Label>
                                <Input
                                    id="nama"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="nama"
                                    name="nama"
                                    placeholder="Nama Pegawai"
                                />
                                <InputError
                                    message={errors.nama}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="nik">Nik</Label>
                                <Input
                                    id="nik"
                                    type="number"
                                    required
                                    minLength={16}
                                    tabIndex={2}
                                    autoComplete="nik"
                                    name="nik"
                                    placeholder="nik"
                                />
                                <InputError message={errors.nik} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="alamat">Alamat</Label>
                                <Input
                                    id="alamat"
                                    type="text"
                                    required
                                    tabIndex={2}
                                    autoComplete="alamat"
                                    name="alamat"
                                    placeholder="alamat"
                                />
                                <InputError message={errors.alamat} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="no_hp">Kontak</Label>
                                <Input
                                    id="no_hp"
                                    type="number"
                                    required
                                    tabIndex={2}
                                    autoComplete="no_hp"
                                    name="no_hp"
                                    placeholder="Kontak"
                                />
                                <InputError message={errors.no_hp} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tanggal_lahir">Tanggal Lahir</Label>
                                <Input
                                    id="tanggal_lahir"
                                    type="date"
                                    required
                                    tabIndex={3}
                                    name="tanggal_lahir"
                                />
                                <InputError message={errors.tanggal_lahir} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="jenis_kelamin">Jenis Kelamin</Label>
                                <Select name="jenis_kelamin" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih jenis kelamin" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="L">Laki-laki</SelectItem>
                                        <SelectItem value="P">Perempuan</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.jenis_kelamin} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select name="status" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Aktif">Aktif</SelectItem>
                                        <SelectItem value="Non Aktif">Non Aktif</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="id_jabatan">Jabatan</Label>
                                <Select name="id_jabatan" required>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a jabatan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {jabatans.map((jabatan) => (
                                            <SelectItem key={jabatan.id} value={jabatan.id.toString()}>
                                                {jabatan.nama_jabatan}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.id_jabatan} />
                            </div>


                            <div className='space-x-2'>
                                <Button type="submit" className="mt-2 w-fit">
                                    {processing ? (
                                        <>
                                            <Spinner className="mr-2" />    
                                            Creating...
                                        </>
                                    ) : (
                                        'Create account'
                                    )}
                                </Button>
                                <Link href={'/jabatans'}>
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
