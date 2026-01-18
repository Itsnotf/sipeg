import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, router } from '@inertiajs/react';
import { BreadcrumbItem, KontrakDokumen } from '@/types';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useState } from 'react';
import kontraks from '@/routes/kontraks';
import { Input } from '@/components/ui/input';



interface Props {
    dokumen: KontrakDokumen;
    kontrak_id: string;
}


export default function DokumenEditPage({ dokumen, kontrak_id }: Props) {
    const [namaDokumen, setNamaDokumen] = useState(dokumen.nama_dokumen);
    const [file, setFile] = useState<File | null>(null);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Kontraks',
            href: kontraks.index().url,
        },
        {
            title: 'Dokumen',
            href: `/kontraks/${kontrak_id}/dokumens`,
        },
        {
            title: 'Edit',
            href: '#',
        },
    ];


    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const formData = new FormData();
        formData.append('nama_dokumen', namaDokumen);
        if (file) {
            formData.append('file', file);
        }
        formData.append('_method', 'PUT');

        router.post(
            `/kontraks/${kontrak_id}/dokumens/${dokumen.id}`,
            formData as any,
            {
                onFinish: () => setProcessing(false),
                onError: (error) => {
                    setErrors(error);
                    setProcessing(false);
                },
            }
        );
    };
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Dokumen" />
            <form
                onSubmit={handleSubmit}
                encType="multipart/form-data"
                className="flex flex-col gap-6 p-4"
            >
                <>
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="nama_dokumen">Nama Dokumen</Label>
                            <Input
                                id="nama_dokumen"
                                type="text"
                                required
                                autoFocus
                                value={namaDokumen}
                                onChange={(e) => setNamaDokumen(e.target.value)}
                                tabIndex={1}
                                autoComplete="nama_dokumen"
                                placeholder="Nama Dokumen"
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                            />
                            <InputError
                                message={errors.nama_dokumen}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="file">File (Opsional)</Label>
                            <Input
                                id="file"
                                type="file"
                                tabIndex={2}
                                onChange={(e) => setFile(e.target.files?.[0] || null)}
                                accept=".pdf,.doc,.docx,.jpg,.png"

                            />
                            <InputError
                                message={errors.file}
                                className="mt-2"
                            />
                            <p className="text-sm text-gray-500">
                                Format: PDF, DOC, DOCX, JPG, PNG. Jika tidak diubah, upload file baru tidak diperlukan.
                            </p>
                            {dokumen.file && (
                                <p className="text-sm text-gray-600 mt-2">
                                    File saat ini: <span className="font-semibold">{dokumen.file.split('/').pop()}</span>
                                </p>
                            )}
                        </div>

                        <div className='space-x-2'>
                            <Button type="submit" className="mt-2 w-fit" disabled={processing}>
                                {processing ? (
                                    <>
                                        <span className="mr-2">⏳</span>
                                        Saving...
                                    </>
                                ) : (
                                    'Simpan Perubahan'
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
            </form>
        </AppLayout>
    );
}
