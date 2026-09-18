import { Form, Head } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useMemo, useState } from 'react';

import EmptyState from '@/components/empty-state';
import FormActions from '@/components/form/form-actions';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import { rupiah } from '@/lib/utils';
import kontraks from '@/routes/kontraks';
import kontrakKaryawans, { store } from '@/routes/kontraks/karyawans';
import type { BreadcrumbItem } from '@/types';

interface KaryawanTersedia {
    id: number;
    nama: string;
    nik: number | string | null;
    jabatan: string | null;
    gaji: number;
}

interface Props {
    kontrak_id: string;
    karyawans: KaryawanTersedia[];
}

export default function PenempatanCreate({ kontrak_id, karyawans }: Props) {
    useFlashToast();

    const [terpilih, setTerpilih] = useState<number[]>([]);
    const [cari, setCari] = useState('');

    const hasil = useMemo(() => {
        const kunci = cari.trim().toLowerCase();
        if (!kunci) return karyawans;

        return karyawans.filter((k) =>
            [k.nama, String(k.nik ?? ''), k.jabatan ?? ''].some((nilai) => nilai.toLowerCase().includes(kunci)),
        );
    }, [cari, karyawans]);

    const semuaHasilTerpilih = hasil.length > 0 && hasil.every((k) => terpilih.includes(k.id));

    const bebanBulanan = useMemo(
        () => karyawans.filter((k) => terpilih.includes(k.id)).reduce((jumlah, k) => jumlah + k.gaji, 0),
        [karyawans, terpilih],
    );

    const ubah = (id: number) => {
        setTerpilih((sebelumnya) =>
            sebelumnya.includes(id) ? sebelumnya.filter((n) => n !== id) : [...sebelumnya, id],
        );
    };

    const ubahSemuaHasil = () => {
        const ids = hasil.map((k) => k.id);

        setTerpilih((sebelumnya) =>
            semuaHasilTerpilih
                ? sebelumnya.filter((id) => !ids.includes(id))
                : [...new Set([...sebelumnya, ...ids])],
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontrak', href: kontraks.index().url },
        { title: 'Penempatan', href: kontrakKaryawans.index(kontrak_id).url },
        { title: 'Tambah', href: kontrakKaryawans.create(kontrak_id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah penempatan" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Tambah penempatan"
                    description="Hanya pekerja tanpa penempatan aktif yang tampil di sini. Penempatan dimulai hari ini, atau saat kontrak mulai bila kontraknya belum berjalan"
                />

                <Form {...store.form(kontrak_id)} disableWhileProcessing className="flex flex-col gap-5">
                    {({ processing, errors }) => (
                        <>
                            {terpilih.map((id) => (
                                <input key={id} type="hidden" name="karyawan_id[]" value={id} />
                            ))}

                            <div className="flex flex-col gap-2 sm:flex-row">
                                <div className="relative flex-1">
                                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                    <Input
                                        value={cari}
                                        onChange={(e) => setCari(e.target.value)}
                                        placeholder="Cari nama, NIK, atau jabatan"
                                        aria-label="Cari karyawan"
                                        className="h-12 pl-9 sm:h-9"
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={ubahSemuaHasil}
                                    disabled={hasil.length === 0}
                                    className="h-12 sm:h-9"
                                >
                                    {semuaHasilTerpilih ? 'Batalkan semua' : 'Pilih semua'}
                                </Button>
                            </div>

                            {hasil.length === 0 ? (
                                <EmptyState
                                    icon={Search}
                                    title={cari ? 'Tidak ada yang cocok' : 'Tidak ada pekerja tersedia'}
                                    description={
                                        cari
                                            ? 'Coba kata kunci lain, atau kosongkan pencarian.'
                                            : 'Semua pekerja sedang ditempatkan pada kontrak lain.'
                                    }
                                />
                            ) : (
                                <ul className="border-border divide-border divide-y rounded-sm border">
                                    {hasil.map((karyawan) => {
                                        const id = `karyawan-${karyawan.id}`;
                                        const dipilih = terpilih.includes(karyawan.id);

                                        return (
                                            <li key={karyawan.id}>
                                                <label
                                                    htmlFor={id}
                                                    className="hover:bg-muted/50 flex cursor-pointer items-center gap-3 px-3 py-3"
                                                >
                                                    <Checkbox
                                                        id={id}
                                                        checked={dipilih}
                                                        onCheckedChange={() => ubah(karyawan.id)}
                                                    />
                                                    <span className="flex min-w-0 flex-1 flex-col">
                                                        <span className="truncate text-sm font-medium">
                                                            {karyawan.nama}
                                                        </span>
                                                        <span className="text-muted-foreground truncate text-xs">
                                                            <span className="num">{karyawan.nik ?? '—'}</span>
                                                            {karyawan.jabatan ? ` · ${karyawan.jabatan}` : null}
                                                        </span>
                                                    </span>
                                                    <span className="num text-muted-foreground shrink-0 text-xs">
                                                        {rupiah(karyawan.gaji)}
                                                    </span>
                                                </label>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}

                            <InputError message={errors.karyawan_id ?? errors['karyawan_id.0']} />

                            <p className="text-muted-foreground text-sm">
                                <span className="num text-foreground font-medium">{terpilih.length}</span> pekerja
                                dipilih
                                {terpilih.length > 0 ? (
                                    <>
                                        {' · tambahan beban gaji pokok '}
                                        <span className="num text-foreground font-medium">{rupiah(bebanBulanan)}</span>
                                        {' per bulan'}
                                    </>
                                ) : null}
                            </p>

                            <FormActions
                                processing={processing}
                                nonaktif={terpilih.length === 0}
                                simpan={terpilih.length > 0 ? `Tempatkan ${terpilih.length} pekerja` : 'Tempatkan'}
                                batalKe={kontrakKaryawans.index(kontrak_id).url}
                            />
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
