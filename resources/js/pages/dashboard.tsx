import { Head, Link } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';

import StackedBars from '@/components/chart/stacked-bars';
import PageHeader from '@/components/page-header';
import StatTile from '@/components/stat-tile';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import { angka, bulanSingkat, periodeLabel, rupiah, rupiahRingkas, tanggal, tanggalRingkas } from '@/lib/utils';
import { dashboard } from '@/routes';
import cashbons from '@/routes/cashbons';
import kontraks from '@/routes/kontraks';
import penggajians from '@/routes/penggajians';
import type { BreadcrumbItem } from '@/types';

interface MarginKontrak {
    id: number;
    judul: string;
    client: string;
    status: string;
    total_biaya: number;
    gaji_tersusun: number;
    tanggal_mulai: string | null;
}

interface JadwalBaris {
    kontrak_id: number;
    kontrak: string;
    client: string;
    periode: string;
    mulai: string;
    selesai: string;
    tanggal_bayar: string;
    dibuat: boolean;
    jatuh_tempo: boolean;
    final: boolean;
}

interface CashbonBerjalan {
    id: number;
    karyawan: string;
    keterangan: string;
    jumlah: number;
    terbayar: number;
    terpotong: number;
    sisa: number;
}

interface KomposisiPeriode {
    periode: string;
    diterima: number;
    bpjs: number;
    cashbon: number;
}

interface Props {
    statistics: {
        total_kontraks: number;
        active_kontraks: number;
        total_karyawans: number;
        total_penggajians: number;
    };
    financial: {
        total_biaya_kontraks: number;
        total_penggajian_dikeluarkan: number;
        keuntungan_bersih: number;
    };
    penggajian_status: { pending_count: number; pending_total_gaji: number };
    cashbon_status: {
        total: number;
        terbayar: number;
        sisa: number;
        pending_count: number;
        berjalan: CashbonBerjalan[];
    };
    margin_kontraks: MarginKontrak[];
    jadwal_terdekat: JadwalBaris[];
    komposisi_periode: KomposisiPeriode[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

export default function Dashboard({
    statistics,
    financial,
    penggajian_status,
    cashbon_status,
    margin_kontraks,
    jadwal_terdekat,
    komposisi_periode,
}: Props) {
    useFlashToast();

    const biayaTerbesar = Math.max(...margin_kontraks.map((k) => k.total_biaya), 1);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title="Dashboard"
                    description="Ringkasan penggajian dan margin kontrak berjalan"
                    actions={
                        <>
                            <span className="num text-muted-foreground hidden text-xs sm:inline">
                                {tanggal(new Date().toISOString())}
                            </span>
                            <Button asChild>
                                <Link href={kontraks.index()}>
                                    <RefreshCw />
                                    Proses penggajian
                                </Link>
                            </Button>
                        </>
                    }
                />

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <StatTile
                        label="Nilai kontrak"
                        value={rupiahRingkas(financial.total_biaya_kontraks)}
                        hint={`${angka(statistics.total_kontraks)} kontrak · ${angka(statistics.active_kontraks)} berjalan`}
                    />
                    <StatTile
                        label="Gaji tersusun"
                        value={rupiahRingkas(financial.total_penggajian_dikeluarkan)}
                        hint={`${angka(statistics.total_penggajians)} periode · ${angka(statistics.total_karyawans)} karyawan`}
                    />
                    <StatTile
                        label="Belum dibayar"
                        value={angka(penggajian_status.pending_count)}
                        tone="warning"
                        hint="periode menunggu pembayaran"
                    />
                    <StatTile
                        label="Cashbon berjalan"
                        value={rupiahRingkas(cashbon_status.sisa)}
                        tone={cashbon_status.sisa > 0 ? 'warning' : 'default'}
                        hint={`sisa hutang · ${angka(cashbon_status.pending_count)} pinjaman`}
                    />
                </div>

                {/* Komposisi penggajian per periode */}
                <section className="bg-card flex flex-col rounded-lg border">
                    <header className="flex flex-col gap-1 border-b p-4">
                        <h2 className="text-lg font-semibold">Komposisi penggajian per periode</h2>
                        <p className="text-muted-foreground text-xs">
                            Ke mana gaji pokok tiap bulan pergi — diterima pekerja, disetor BPJS, atau kembali sebagai
                            potongan cashbon
                        </p>
                    </header>
                    <div className="p-4">
                        <StackedBars
                            seri={[
                                { key: 'diterima', label: 'Diterima pekerja' },
                                { key: 'bpjs', label: 'BPJS' },
                                { key: 'cashbon', label: 'Potongan cashbon' },
                            ]}
                            titik={komposisi_periode.map((baris) => ({
                                label: bulanSingkat(baris.periode),
                                nilai: {
                                    diterima: baris.diterima,
                                    bpjs: baris.bpjs,
                                    cashbon: baris.cashbon,
                                },
                            }))}
                            kosong="Belum ada periode penggajian yang diproses."
                        />
                    </div>
                </section>

                {/* Margin per kontrak */}
                <section className="bg-card flex flex-col rounded-lg border">
                    <header className="flex flex-col gap-2 border-b p-4 sm:flex-row sm:items-baseline sm:justify-between">
                        <div className="flex flex-col gap-1">
                            <h2 className="text-lg font-semibold">Margin per kontrak</h2>
                            <p className="text-muted-foreground text-xs">
                                Gaji tersusun dihitung dari periode yang sudah diproses, bukan proyeksi seluruh kontrak
                            </p>
                        </div>
                        <div className="text-muted-foreground flex shrink-0 items-center gap-4 text-xs">
                            <span className="flex items-center gap-1.5">
                                <span className="size-2.5 rounded-xs bg-ok" />
                                Gaji tersusun
                            </span>
                            <span className="flex items-center gap-1.5">
                                <span className="bg-muted-foreground/25 size-2.5 rounded-xs" />
                                Sisa nilai kontrak
                            </span>
                        </div>
                    </header>

                    <div className="flex flex-col">
                        {margin_kontraks.map((kontrak, index) => (
                            <Link
                                key={kontrak.id}
                                href={kontraks.show(kontrak.id)}
                                className={`hover:bg-accent/60 flex flex-col gap-2.5 border-b p-4 transition-colors last:border-b-0 lg:grid lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.6fr)_140px_150px] lg:items-center lg:gap-5 ${
                                    index % 2 === 1 ? 'bg-muted/45' : ''
                                }`}
                            >
                                <div className="flex min-w-0 flex-col gap-0.5">
                                    <div className="flex items-center gap-2">
                                        <span className="truncate text-sm font-semibold">{kontrak.judul}</span>
                                        {kontrak.status !== 'Progres' ? (
                                            <StatusBadge tone={kontrak.status === 'Selesai' ? 'outline' : 'warning'}>
                                                {kontrak.status}
                                            </StatusBadge>
                                        ) : null}
                                    </div>
                                    <span className="text-muted-foreground truncate text-xs">{kontrak.client}</span>
                                </div>

                                {/*
                                    Batang diskalakan terhadap kontrak TERBESAR, bukan terhadap
                                    dirinya sendiri — sehingga panjang batang langsung menunjukkan
                                    besar kontraknya, dan bagian hijau menunjukkan porsi gajinya.
                                    Latar abu-abu ada pada segmen kedua, bukan pada induknya:
                                    kalau di induk, semua kontrak akan tampak selebar penuh.
                                */}
                                <div className="flex h-3 overflow-hidden rounded-xs">
                                    <div
                                        className="bg-ok"
                                        style={{ width: `${(kontrak.gaji_tersusun / biayaTerbesar) * 100}%` }}
                                    />
                                    <div
                                        className="bg-muted-foreground/20"
                                        style={{
                                            width: `${((kontrak.total_biaya - kontrak.gaji_tersusun) / biayaTerbesar) * 100}%`,
                                        }}
                                    />
                                </div>

                                <div className="flex items-baseline justify-between gap-3 lg:flex-col lg:items-end lg:gap-0.5">
                                    <span className="eyebrow lg:hidden">Gaji tersusun</span>
                                    <span className="num text-sm">
                                        {kontrak.gaji_tersusun > 0 ? rupiah(kontrak.gaji_tersusun) : '—'}
                                    </span>
                                </div>

                                <div className="flex items-baseline justify-between gap-3 lg:flex-col lg:items-end lg:gap-0.5">
                                    <span className="eyebrow lg:hidden">Nilai kontrak</span>
                                    <span className="num text-muted-foreground text-sm">{rupiah(kontrak.total_biaya)}</span>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Jadwal terdekat */}
                    <section className="bg-card flex flex-col rounded-lg border">
                        <header className="flex items-center justify-between gap-3 border-b p-4">
                            <h2 className="text-base font-semibold">Jadwal gajian terdekat</h2>
                            <Link href={penggajians.index()} className="text-primary text-sm font-semibold">
                                Semua periode
                            </Link>
                        </header>

                        {jadwal_terdekat.length === 0 ? (
                            <p className="text-muted-foreground p-6 text-center text-sm">Tidak ada periode menunggu.</p>
                        ) : (
                            <div className="flex flex-col">
                                {jadwal_terdekat.map((baris, index) => (
                                    <div
                                        key={`${baris.kontrak_id}-${baris.periode}`}
                                        className={`flex items-center justify-between gap-3 border-b p-3.5 last:border-b-0 ${
                                            index % 2 === 1 ? 'bg-muted/45' : ''
                                        }`}
                                    >
                                        <div className="flex min-w-0 flex-col gap-0.5">
                                            <span
                                                className={`truncate text-sm ${baris.dibuat ? 'font-semibold' : 'text-muted-foreground'}`}
                                            >
                                                {periodeLabel(baris.periode)} · {baris.kontrak}
                                            </span>
                                            <span className="num text-muted-foreground text-xs">
                                                {tanggalRingkas(baris.mulai)}–{tanggalRingkas(baris.selesai)} · bayar{' '}
                                                {tanggal(baris.tanggal_bayar)}
                                            </span>
                                        </div>
                                        <StatusBadge tone={baris.jatuh_tempo ? 'warning' : 'outline'}>
                                            {baris.jatuh_tempo ? 'Jatuh tempo' : 'Terjadwal'}
                                        </StatusBadge>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    {/* Cashbon berjalan */}
                    <section className="bg-card flex flex-col rounded-lg border">
                        <header className="flex items-center justify-between gap-3 border-b p-4">
                            <h2 className="text-base font-semibold">Cashbon berjalan</h2>
                            <Link href={cashbons.index()} className="text-primary text-sm font-semibold">
                                Semua cashbon
                            </Link>
                        </header>

                        {cashbon_status.berjalan.length === 0 ? (
                            <p className="text-muted-foreground p-6 text-center text-sm">
                                Semua cashbon sudah lunas.
                            </p>
                        ) : (
                            <div className="flex flex-col">
                                {cashbon_status.berjalan.map((cashbon) => (
                                    <div key={cashbon.id} className="flex flex-col gap-2.5 border-b p-4 last:border-b-0">
                                        <div className="flex items-baseline justify-between gap-3">
                                            <span className="truncate text-sm font-semibold">{cashbon.karyawan}</span>
                                            <span className="num shrink-0 text-sm">sisa {rupiah(cashbon.sisa)}</span>
                                        </div>
                                        <div className="bg-muted-foreground/20 flex h-2.5 overflow-hidden rounded-xs">
                                            <div
                                                className="bg-ok"
                                                style={{ width: `${(cashbon.terbayar / cashbon.jumlah) * 100}%` }}
                                            />
                                            <div
                                                className="bg-[color-mix(in_oklch,var(--ok)_35%,transparent)]"
                                                style={{
                                                    width: `${((cashbon.terpotong - cashbon.terbayar) / cashbon.jumlah) * 100}%`,
                                                }}
                                            />
                                        </div>
                                        <p className="text-muted-foreground text-xs">
                                            Terbayar <span className="num">{rupiah(cashbon.terbayar)}</span> dari{' '}
                                            <span className="num">{rupiah(cashbon.jumlah)}</span> · {cashbon.keterangan}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
