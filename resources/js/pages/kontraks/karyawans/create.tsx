import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Link, Head, Form } from '@inertiajs/react';
import kontraks from '@/routes/kontraks';
import { BreadcrumbItem, Karyawan } from '@/types';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Checkbox } from '@/components/ui/checkbox';
import { useMemo, useState } from 'react';


interface Props {
    kontrak_id: string;
    karyawans: Karyawan[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kontraks', href: kontraks.index().url },
    { title: 'Karyawan', href: '#' },
    { title: 'Create', href: '#' },
];

export default function KaryawanCreatePage({ kontrak_id, karyawans }: Props) {
    const [selectedKaryawans, setSelectedKaryawans] = useState<(string | number)[]>([]);
    const [search, setSearch] = useState('');

    const filteredKaryawans = useMemo(() => {
        return karyawans.filter((k) =>
            k.nama.toLowerCase().includes(search.toLowerCase()) ||
            k.nik?.toString().toLowerCase().includes(search.toLowerCase()) ||
            k.jabatan.nama_jabatan?.toLowerCase().includes(search.toLowerCase())
        );
    }, [search, karyawans]);

    const toggleKaryawan = (id: string | number) => {
        setSelectedKaryawans((prev) =>
            prev.includes(id)
                ? prev.filter((k) => k !== id)
                : [...prev, id]
        );
    };

    const toggleAllFiltered = () => {
        const ids: (string | number)[] = filteredKaryawans.map((k) => k.id);
        const allSelected = ids.every((id) => selectedKaryawans.includes(id));

        setSelectedKaryawans((prev) =>
            allSelected
                ? prev.filter((id) => !ids.includes(id))
                : [...new Set([...prev, ...ids])]
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Karyawan Kontrak" />

            <Form
                method="post"
                action={`/kontraks/${kontrak_id}/karyawans`}
                className="w-full p-4"
            >
                {({ processing, errors }) => (
                    <Card className=''>
                        <CardHeader className="space-y-3">
                            <Label>Pilih Karyawan</Label>

                            <div className="flex gap-2">
                                <Input
                                    placeholder="Cari nama, NIK, atau jabatan..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={toggleAllFiltered}
                                >
                                    {filteredKaryawans.length > 0 &&
                                        filteredKaryawans.every((k) => selectedKaryawans.includes(k.id))
                                        ? 'Uncheck All'
                                        : 'Check All'}
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-4">
                            {/* Hidden inputs untuk mengirim selected karyawans */}
                            {selectedKaryawans.map((id) => (
                                <input
                                    key={`hidden-${id}`}
                                    type="hidden"
                                    name="karyawan_id[]"
                                    value={id}
                                />
                            ))}

                            {filteredKaryawans.length === 0 ? (
                                <div className="text-center text-sm text-muted-foreground py-10">
                                    Karyawan tidak ditemukan
                                </div>
                            ) : (
                                <ScrollArea className="h-[360px] pr-4">
                                    <div className="space-y-2">
                                        {filteredKaryawans.map((karyawan) => (
                                            <label
                                                key={karyawan.id}
                                                className="flex items-start gap-3 rounded-lg border p-3 cursor-pointer hover:bg-muted"
                                            >
                                                <Checkbox
                                                    checked={selectedKaryawans.includes(karyawan.id)}
                                                    onCheckedChange={() => toggleKaryawan(karyawan.id)}
                                                />

                                                <div className="flex-1">
                                                    <p className="font-medium text-sm">
                                                        {karyawan.nama}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {karyawan.nik && `nik: ${karyawan.nik}`}
                                                        {karyawan.nik && karyawan.jabatan && ' • Jabatan: '}
                                                        {karyawan.jabatan?.nama_jabatan}
                                                    </p>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                </ScrollArea>
                            )}

                            <InputError message={errors['karyawan_id']} />
                            <InputError message={errors['karyawan_id.*']} />

                            <div className="flex justify-between items-center">
                                <p className="text-sm text-muted-foreground">
                                    Dipilih: <span className="font-medium">{selectedKaryawans.length}</span> karyawan
                                </p>

                                <div className="flex gap-2">
                                    <Button
                                        type="submit"
                                        disabled={selectedKaryawans.length === 0 || processing}
                                    >
                                        {processing ? (
                                            <>
                                                <Spinner className="mr-2" />
                                                Menambahkan...
                                            </>
                                        ) : (
                                            `Tambah (${selectedKaryawans.length})`
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
                        </CardContent>
                    </Card>
                )}
            </Form>
        </AppLayout>
    );
}
