import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router, Form } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import kontraks from '@/routes/kontraks';
import { BreadcrumbItem } from '@/types';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';


interface Props {
   kontrak_id: string;
}


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kontraks',
        href: kontraks.index().url,
    },
    {
        title: 'Dokumen',
        href: '#',
    },
    {
        title: 'Create',
        href: '#',
    },
];

export default function DokumenCreatePage({ kontrak_id }: Props) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dokumen Create" />
            <Form
                method="post"
                action={`/kontraks/${kontrak_id}/dokumens`}
                encType="multipart/form-data"
                className="flex flex-col gap-6 p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="nama_dokumen">Nama Dokumen</Label>
                                <Input
                                    id="nama_dokumen"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="nama_dokumen"
                                    name="nama_dokumen"
                                    placeholder="Nama Dokumen"
                                />
                                <InputError
                                    message={errors.nama_dokumen}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="file">File</Label>
                                <Input
                                    id="file"
                                    type="file"
                                    required
                                    tabIndex={2}
                                    name="file"
                                    accept=".pdf,.doc,.docx,.jpg,.png"
                                />
                                <InputError
                                    message={errors.file}
                                    className="mt-2"
                                />
                                <p className="text-sm text-gray-500">Format: PDF, DOC, DOCX, JPG, PNG</p>
                            </div>

                            <div className='space-x-2'>
                                <Button type="submit" className="mt-2 w-fit">
                                    {processing ? (
                                        <>
                                            <Spinner className="mr-2" />    
                                            Uploading...
                                        </>
                                    ) : (
                                        'Upload Dokumen'
                                    )}
                                </Button>
                                <Button 
                                    variant='outline' 
                                    type="button" 
                                    className="mt-2 w-fit"
                                    onClick={() => window.history.back()}
                                >
                                    Back
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </Form>
        </AppLayout>
    );
}
