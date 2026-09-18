import { angka, rupiah } from '@/lib/utils';

export interface KaryawanPlafon {
    id: number;
    nama: string;
    nik: string;
    jabatan: { nama_jabatan: string; gaji: number };
    gaji_bersih: number;
    potongan_maksimal: number;
    plafon: number;
    hutang_berjalan: number;
    sisa_plafon: number;
}

interface PlafonMeterProps {
    karyawan: KaryawanPlafon;
    /** Nominal yang sedang diketik, untuk memperkirakan jumlah cicilan. */
    jumlah?: number;
    maksHutangBulan: number;
}

/**
 * Menampilkan sisa plafon SEBELUM pengguna mengetik nominal.
 *
 * Sebelumnya batas pinjaman hanya diperiksa di server, sehingga pengguna baru
 * tahu angkanya terlalu besar setelah menekan simpan dan kehilangan isian.
 */
export default function PlafonMeter({ karyawan, jumlah = 0, maksHutangBulan }: PlafonMeterProps) {
    const terpakai = karyawan.plafon > 0 ? (karyawan.hutang_berjalan / karyawan.plafon) * 100 : 0;
    const diminta = karyawan.plafon > 0 ? (jumlah / karyawan.plafon) * 100 : 0;
    const melebihi = jumlah > karyawan.sisa_plafon;
    const periode = karyawan.potongan_maksimal > 0 ? Math.ceil(jumlah / karyawan.potongan_maksimal) : 0;

    return (
        <div className="bg-card flex flex-col gap-2.5 rounded-lg border p-4">
            <div className="flex items-baseline justify-between gap-3">
                <span className="eyebrow">Sisa plafon pinjaman</span>
                <span className="num text-primary text-lg font-medium">{rupiah(karyawan.sisa_plafon)}</span>
            </div>

            <div className="flex h-3 overflow-hidden rounded-xs bg-[color-mix(in_oklch,var(--primary)_18%,transparent)]">
                <div className="bg-warn" style={{ width: `${Math.min(terpakai, 100)}%` }} />
                <div
                    className={melebihi ? 'bg-crit' : 'bg-[var(--primary)]'}
                    style={{ width: `${Math.min(diminta, 100 - Math.min(terpakai, 100))}%` }}
                />
            </div>

            <div className="text-muted-foreground flex items-baseline justify-between gap-3 text-xs">
                <span>
                    Terpakai <span className="num">{rupiah(karyawan.hutang_berjalan)}</span>
                </span>
                <span>
                    Plafon <span className="num">{rupiah(karyawan.plafon)}</span>
                </span>
            </div>

            <div className="border-border/70 border-t pt-2.5">
                <p className="text-muted-foreground text-xs leading-relaxed">
                    Plafon <span className="num">{angka(maksHutangBulan)}×</span> gaji bersih bulanan{' '}
                    <span className="num">{rupiah(karyawan.gaji_bersih)}</span>. Potongan maksimal{' '}
                    <span className="num">{rupiah(karyawan.potongan_maksimal)}</span> untuk periode yang dikerjakan
                    penuh; periode yang tidak penuh ikut mengecil sesuai hari kerjanya. Sisanya dicicil ke periode
                    berikutnya.
                </p>
            </div>

            {jumlah > 0 ? (
                melebihi ? (
                    <p className="text-[13px] font-medium text-crit">
                        Melebihi sisa plafon sebesar <span className="num">{rupiah(jumlah - karyawan.sisa_plafon)}</span>
                    </p>
                ) : (
                    <p className="text-[13px] text-ok">
                        Masih di bawah plafon · perkiraan lunas dalam <span className="num">{angka(periode)}</span>{' '}
                        periode
                    </p>
                )
            ) : null}
        </div>
    );
}
