import { useMemo, useState } from 'react';

import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

export interface IzinRingkas {
    id: number;
    name: string;
}

interface PermissionPickerProps {
    permissions: IzinRingkas[];
    /** Nama izin yang sudah dimiliki peran ini. */
    terpilihAwal?: string[];
}

/** "kontraks dokumens create" → modul "kontraks dokumens", aksi "create". */
function pecah(nama: string): { modul: string; aksi: string } {
    const bagian = nama.split(' ');
    const aksi = bagian.pop() ?? nama;

    return { modul: bagian.join(' ') || nama, aksi };
}

const LABEL_AKSI: Record<string, string> = {
    index: 'Lihat daftar',
    show: 'Lihat rincian',
    create: 'Tambah',
    edit: 'Ubah',
    update: 'Ubah',
    delete: 'Hapus',
    generate: 'Proses penggajian',
};

/**
 * Empat puluh lebih izin sebagai satu grid kotak centang tidak terbaca: nama
 * mentahnya berulang dan tidak ada tanda mana yang satu modul. Di sini izin
 * dikelompokkan per modul, tiap kelompok punya satu sakelar "semua", dan
 * jumlah terpilih terlihat di kepala kelompok.
 */
export default function PermissionPicker({ permissions, terpilihAwal = [] }: PermissionPickerProps) {
    const [terpilih, setTerpilih] = useState<string[]>(terpilihAwal);

    const kelompok = useMemo(() => {
        const peta = new Map<string, IzinRingkas[]>();

        for (const izin of permissions) {
            const { modul } = pecah(izin.name);
            peta.set(modul, [...(peta.get(modul) ?? []), izin]);
        }

        return [...peta.entries()];
    }, [permissions]);

    const ubah = (nama: string, aktif: boolean) => {
        setTerpilih((sebelumnya) =>
            aktif ? [...new Set([...sebelumnya, nama])] : sebelumnya.filter((n) => n !== nama),
        );
    };

    const ubahKelompok = (daftar: IzinRingkas[], aktif: boolean) => {
        const nama = daftar.map((i) => i.name);

        setTerpilih((sebelumnya) =>
            aktif ? [...new Set([...sebelumnya, ...nama])] : sebelumnya.filter((n) => !nama.includes(n)),
        );
    };

    return (
        <div className="flex flex-col gap-3">
            {/*
                Kotak centang di bawah tidak bernama; nilai yang benar-benar
                terkirim adalah input tersembunyi ini, satu per izin terpilih.
            */}
            {terpilih.map((nama) => (
                <input key={nama} type="hidden" name="permissions[]" value={nama} />
            ))}

            {kelompok.map(([modul, daftar]) => {
                const jumlah = daftar.filter((i) => terpilih.includes(i.name)).length;
                const semua = jumlah === daftar.length;

                return (
                    <section key={modul} className="border-border rounded-sm border">
                        <header className="border-border bg-muted/40 flex items-center justify-between gap-3 border-b px-3 py-2">
                            <label className="flex items-center gap-2.5">
                                <Checkbox
                                    checked={semua}
                                    onCheckedChange={(nilai) => ubahKelompok(daftar, nilai === true)}
                                    aria-label={`Pilih semua izin ${modul}`}
                                />
                                <span className="text-sm font-medium capitalize">{modul}</span>
                            </label>
                            <span className="num text-muted-foreground text-xs">
                                {jumlah}/{daftar.length}
                            </span>
                        </header>

                        <div className="grid gap-x-4 gap-y-2.5 p-3 sm:grid-cols-2 lg:grid-cols-4">
                            {daftar.map((izin) => {
                                const { aksi } = pecah(izin.name);
                                const id = `izin-${izin.id}`;

                                return (
                                    <div key={izin.id} className="flex items-center gap-2.5">
                                        <Checkbox
                                            id={id}
                                            checked={terpilih.includes(izin.name)}
                                            onCheckedChange={(nilai) => ubah(izin.name, nilai === true)}
                                        />
                                        <Label htmlFor={id} className="text-sm font-normal">
                                            {LABEL_AKSI[aksi] ?? aksi}
                                        </Label>
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
