import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, Form } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { store } from '@/routes/clients';
import { BreadcrumbItem } from '@/types';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import clients from '@/routes/clients';


interface Props {
    roles: {
        id: number;
        name: string;
    }[];
}


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: clients.index().url,
    },
    {
        title: 'Create',
        href: clients.create().url,
    },
];

export default function ClientCreatePage({ roles }: Props) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Client Create" />
            <Form
                {...store.form()}
                disableWhileProcessing
                className="flex flex-col gap-6 p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="nama_client">Nama Client</Label>
                                <Input
                                    id="nama_client"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="nama_client"
                                    name="nama_client"
                                    placeholder="Nama Client"
                                />
                                <InputError
                                    message={errors.nama_client}
                                    className="mt-2"
                                />
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
                                    placeholder="Alamat"
                                />
                                <InputError message={errors.alamat} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={3}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="Email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="no_hp">No. HP</Label>
                                <Input
                                    id="no_hp"
                                    type="text"
                                    required
                                    tabIndex={4}
                                    autoComplete="no_hp"
                                    name="no_hp"
                                    placeholder="No. HP"
                                />
                                <InputError message={errors.no_hp} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="deskripsi">Deskripsi</Label>
                                <Input
                                    id="deskripsi"
                                    type="text"
                                    tabIndex={5}
                                    autoComplete="deskripsi"
                                    name="deskripsi"
                                    placeholder="Deskripsi"
                                />
                                <InputError message={errors.deskripsi} />
                            </div>

                            <div className='space-x-2'>
                                <Button type="submit" className="mt-2 w-fit">
                                    {processing ? (
                                        <>
                                            <Spinner className="mr-2" />    
                                            Creating...
                                        </>
                                    ) : (
                                        'Create Client'
                                    )}
                                </Button>
                                <Link href={'/clients'}>
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
