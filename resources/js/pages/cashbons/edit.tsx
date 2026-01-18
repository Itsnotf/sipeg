import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, Form } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { update } from '@/routes/cashbons';
import { BreadcrumbItem, Cashbon, Karyawan } from '@/types';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import cashbons from '@/routes/cashbons';
import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import { toast } from 'sonner';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";


interface Props {
    cashbon: Cashbon;
    karyawans: Karyawan[];
}


export default function CashbonEditPage({ cashbon, karyawans }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Cashbon',
            href: cashbons.index().url,
        },
        {
            title: 'Edit',
            href: cashbons.edit(cashbon.id).url,
        },
    ];

    const [karyawanId, setKaryawanId] = useState<string>(String(cashbon.karyawan_id));
    const [status, setStatus] = useState<string>(cashbon.status ?? 'belum dibayar');
    const flash = usePage<SharedData>().props.flash;
    const [shownMessages] = useState(new Set());

    useEffect(() => {
        if (flash?.success && !shownMessages.has(flash.success)) {
            toast.success(flash.success);
            shownMessages.add(flash.success);
        }

        if (flash?.error && !shownMessages.has(flash.error)) {
            toast.error(flash.error);
            shownMessages.add(flash.error);
        }
    }, [flash?.success, flash?.error]);


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cashbon" />
            <Form
                {...update.form(cashbon.id)}
                className="flex flex-col gap-6 p-4"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <input type="hidden" name="karyawan_id" value={karyawanId} />
                            <input type="hidden" name="status" value={status} />

                            <div className="grid gap-2">
                                <Label>Karyawan</Label>
                                <Select value={karyawanId} onValueChange={setKaryawanId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih karyawan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {karyawans.map((karyawan) => (
                                            <SelectItem key={karyawan.id} value={String(karyawan.id)}>
                                                {karyawan.nama} - {karyawan.jabatan?.nama_jabatan}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.karyawan_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="jumlah">Jumlah</Label>
                                <Input
                                    id="jumlah"
                                    type="number"
                                    required
                                    tabIndex={1}
                                    autoComplete="jumlah"
                                    name="jumlah"
                                    defaultValue={cashbon.jumlah}
                                    placeholder="Jumlah"
                                />
                                <InputError
                                    message={errors.jumlah}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <Input
                                    id="keterangan"
                                    type="text"
                                    required
                                    defaultValue={cashbon.keterangan}
                                    tabIndex={2}
                                    autoComplete="keterangan"
                                    name="keterangan"
                                    placeholder="Keterangan"
                                />
                                <InputError message={errors.keterangan} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="belum dibayar">Belum dibayar</SelectItem>
                                        <SelectItem value="dibayar">Dibayar</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
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
                                <Link href={'/cashbons'}>
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
