import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, Form } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { update } from '@/routes/jabatans';
import { BreadcrumbItem, Jabatan } from '@/types';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import jabatans from '@/routes/jabatans';


interface Props {
    jabatan: Jabatan;
}


export default function JabatanEditPage({ jabatan }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Jabatan',
            href: jabatans.index().url,
        },
        {
            title: 'Edit',
            href: jabatans.edit(jabatan.id).url,
        },
    ];


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Jabatan" />
            <Form
                {...update.form(jabatan.id)}
                className="flex flex-col gap-6 p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="nama_jabatan">Nama Jabatan</Label>
                                <Input
                                    id="nama_jabatan"
                                    type="text"
                                    required
                                    tabIndex={1}
                                    autoComplete="nama_jabatan"
                                    name="nama_jabatan"
                                    defaultValue={jabatan.nama_jabatan}
                                    placeholder="Nama Jabatan"
                                />
                                <InputError
                                    message={errors.nama_jabatan}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="deskripsi">Deskripsi</Label>
                                <Input
                                    id="deskripsi"
                                    type="text"
                                    required
                                    defaultValue={jabatan.deskripsi}
                                    tabIndex={2}
                                    autoComplete="deskripsi"
                                    name="deskripsi"
                                    placeholder="Deskripsi"
                                />
                                <InputError message={errors.deskripsi} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="gaji">Gaji</Label>
                                <Input
                                    id="gaji"
                                    type="number"
                                    required
                                    defaultValue={jabatan.gaji}
                                    tabIndex={2}
                                    autoComplete="gaji"
                                    name="gaji"
                                    placeholder="Gaji"
                                />
                                <InputError message={errors.gaji} />
                            </div>
                            
                            <div className="grid gap-2">
                                <Label htmlFor="bpjs">BPJS</Label>
                                <Input
                                    id="bpjs"
                                    type="number"
                                    required
                                    defaultValue={jabatan.bpjs}
                                    tabIndex={2}
                                    autoComplete="bpjs"
                                    name="bpjs"
                                    placeholder="BPJS"
                                />
                                <InputError message={errors.bpjs} />
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
