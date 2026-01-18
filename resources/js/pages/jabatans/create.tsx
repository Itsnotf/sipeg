import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, Form } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { store } from '@/routes/jabatans';
import { BreadcrumbItem } from '@/types';

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
import jabatans from '@/routes/jabatans';


interface Props {
    roles: {
        id: number;
        name: string;
    }[];
}


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Jabatan',
        href: jabatans.index.url(),
    },
    {
        title: 'Create',
        href: jabatans.create.url(),
    },
];

export default function JabatanCreatePage({ roles }: Props) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Jabatan Create" />
            <Form
                {...store.form()}
                disableWhileProcessing
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
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="nama_jabatan"
                                    name="nama_jabatan"
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
                                            Creating...
                                        </>
                                    ) : (
                                        'Create Jabatan'
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
