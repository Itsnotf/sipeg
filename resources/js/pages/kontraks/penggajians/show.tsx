import { Head, Link, router } from '@inertiajs/react';
import { Lock, Users } from 'lucide-react';
import { useState } from 'react';

import EmptyState from '@/components/empty-state';
import PageHeader from '@/components/page-header';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import hasAnyPermission, { angka, periodeLabel, rupiah, tanggal, tanggalRingkas } from '@/lib/utils';
import kontraks from '@/routes/kontraks';
import type { BreadcrumbItem, Kontrak } from '@/types';

interface Potongan {
    id: number;
    jumlah: number;
    keterangan: string;
    pinjaman: number;
    sisa: number;
}

interface Detail {
    id: number;
    karyawan_id: number;
    gaji_pokok_penuh: number;
    gaji_pokok: number;
    bpjs_persen: number;
    bpjs: number;
    potongan_cashbon: number;
    total_gaji: number;
    hari_aktif: number;
    hari_periode: number;
    karyawan: { id: number; nama: string; jabatan: { nama_jabatan: string } };
    potongans: Potongan[];
}

interface Props {
    kontrak_id: number | string;
    kontrak: Kontrak;
    penggajian: {
        id: number;
        periode: string;
        periode_mulai: string | null;
        periode_selesai: string | null;
        tanggal_bayar: string | null;
        final: boolean;
        status: string;
        penggajianDetails: Detail[];
    };
    summary: { total_gaji: number; total_bpjs: number; total_cashbon: number; karyawan_count: number };
}

/** Satu pasangan label–nilai di kartu slip ponsel. */
function Baris({
    label,
    value,
    strong,
    negative,
}: {
    label: React.ReactNode;
    value: React.ReactNode;
    strong?: boolean;
    negative?: boolean;
}) {
    return (
        <div className="flex items-baseline justify-between gap-4">
            <span className={strong ? 'text-sm font-semibold' : 'text-muted-foreground text-[13px]'}>{label}</span>
            <span className={`num text-sm ${negative ? 'text-crit' : ''}`}>{value}</span>
        </div>
    );
}

/** Rincian buku besar: cashbon mana yang menyumbang berapa pada slip ini. */
function BukuBesar({ potongans }: { potongans: Potongan[] }) {
    if (potongans.length === 0) return null;

    return (
        <div className="border-primary/45 ml-0.5 flex flex-col gap-2 border-l-2 py-1.5 pl-3">
            <span className="eyebrow text-primary">Buku besar potongan</span>
            {potongans.map((potongan) => (
                <div key={potongan.id} className="flex flex-col gap-0.5">
                    <div className="flex items-baseline justify-between gap-3">
                        <span className="text-muted-foreground text-[13px]">{potongan.keterangan}</span>
                        <span className="num shrink-0 text-[13px]">{rupiah(potongan.jumlah)}</span>
                    </div>
                    <span className="num text-muted-foreground/80 text-[11px]">
                        pinjaman {rupiah(potongan.pinjaman)} · sisa {rupiah(potongan.sisa)}
                    </span>
                </div>
            ))}
        </div>
    );
}

export default function PenggajianShow({ kontrak_id, kontrak, penggajian, summary }: Props) {
    useFlashToast();
    const [paying, setPaying] = useState(false);


    const dibayar = penggajian.status === 'dibayar';
    const details = penggajian.penggajianDetails;
    const totalGajiPokok = details.reduce((sum, d) => sum + Number(d.gaji_pokok), 0);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kontrak', href: kontraks.index().url },
        { title: kontrak.judul, href: kontraks.show(kontrak_id).url },
        { title: 'Penggajian', href: kontraks.penggajians.index(kontrak_id).url },
        { title: periodeLabel(penggajian.periode), href: kontraks.penggajians.show([kontrak_id, penggajian.id]).url },
    ];

    const tandaiDibayar = () => {
        setPaying(true);
        router.put(
            kontraks.penggajians.update([kontrak_id, penggajian.id]).url,
            { status: 'dibayar' },
            { onFinish: () => setPaying(false), preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Penggajian ${periodeLabel(penggajian.periode)}`} />

            <div className="flex flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title={`Penggajian ${periodeLabel(penggajian.periode)}`}
                    description={`${kontrak.judul} · ${kontrak.client?.nama_client ?? ''}`}
                    eyebrow={
                        <span className="flex items-center gap-2">
                            {dibayar ? (
                                <StatusBadge tone="positive">Sudah dibayar</StatusBadge>
                            ) : (
                                <StatusBadge tone="warning">Belum dibayar</StatusBadge>
                            )}
                            {penggajian.final ? <StatusBadge tone="outline">Periode terakhir</StatusBadge> : null}
                        </span>
                    }
                    actions={
                        !dibayar && hasAnyPermission(['penggajians update']) ? (
                            <Button onClick={tandaiDibayar} disabled={paying} className="h-11 w-full sm:h-9 sm:w-auto">
                                {paying ? 'Menyimpan…' : 'Tandai sudah dibayar'}
                            </Button>
                        ) : null
                    }
                />

                {/* Keterangan periode */}
                <div className="bg-card grid grid-cols-2 gap-px overflow-hidden rounded-lg border lg:grid-cols-4">
                    <div className="bg-background flex flex-col gap-1 p-3.5">
                        <span className="eyebrow">Cakupan kerja</span>
                        <span className="num text-[13px]">
                            {tanggalRingkas(penggajian.periode_mulai)} – {tanggal(penggajian.periode_selesai)}
                        </span>
                    </div>
                    <div className="bg-background flex flex-col gap-1 p-3.5">
                        <span className="eyebrow">Tanggal bayar</span>
                        <span className="num text-[13px]">{tanggal(penggajian.tanggal_bayar)}</span>
                    </div>
                    <div className="bg-background flex flex-col gap-1 p-3.5">
                        <span className="eyebrow">Karyawan</span>
                        <span className="num text-[13px]">{angka(summary.karyawan_count)} orang</span>
                    </div>
                    <div className="bg-background flex flex-col gap-1 p-3.5">
                        <span className="eyebrow">Total diterima</span>
                        <span className="num text-[13px] font-medium">{rupiah(summary.total_gaji)}</span>
                    </div>
                </div>

                <section className="flex flex-col gap-3">
                    <div className="flex flex-col gap-1">
                        <h2 className="text-lg font-semibold">Rincian per karyawan</h2>
                        <p className="text-muted-foreground text-xs">
                            Setiap angka menunjukkan asalnya — gaji penuh, hari aktif, tarif BPJS, dan cashbon yang
                            memotongnya
                        </p>
                    </div>

                    {details.length === 0 ? (
                        <div className="bg-card rounded-lg border">
                            <EmptyState
                                icon={Users}
                                title="Tidak ada karyawan pada periode ini"
                                description="Belum ada pekerja yang penempatannya menyentuh rentang tanggal periode ini."
                            />
                        </div>
                    ) : (
                        <>
                            {/* Tabel — 1024px ke atas */}
                            <div className="bg-card hidden overflow-hidden rounded-lg border lg:block">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-muted text-muted-foreground border-b text-[10.5px] font-semibold tracking-[0.09em] uppercase">
                                            <th className="h-9 px-4 text-left">Karyawan</th>
                                            <th className="h-9 w-28 px-4 text-left">Hari aktif</th>
                                            <th className="h-9 px-4 text-right">Gaji pokok</th>
                                            <th className="h-9 px-4 text-right">BPJS</th>
                                            <th className="h-9 px-4 text-right">Potongan cashbon</th>
                                            <th className="h-9 px-4 text-right">Diterima</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {details.map((detail, index) => (
                                            <tr
                                                key={detail.id}
                                                className={`border-b align-top ${index % 2 === 1 ? 'bg-muted/45' : ''}`}
                                            >
                                                <td className="px-4 py-3.5">
                                                    <div className="flex flex-col gap-0.5">
                                                        <span className="font-semibold">{detail.karyawan.nama}</span>
                                                        <span className="text-muted-foreground text-xs">
                                                            {detail.karyawan.jabatan?.nama_jabatan ?? '—'}
                                                        </span>
                                                    </div>
                                                    <div className="mt-2.5">
                                                        <BukuBesar potongans={detail.potongans} />
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3.5">
                                                    <div className="flex flex-col gap-0.5">
                                                        <span
                                                            className={`num ${
                                                                detail.hari_aktif < detail.hari_periode
                                                                    ? 'text-warn'
                                                                    : ''
                                                            }`}
                                                        >
                                                            {detail.hari_aktif} / {detail.hari_periode}
                                                        </span>
                                                        <span className="num text-muted-foreground/80 text-[11px]">
                                                            {detail.hari_aktif < detail.hari_periode
                                                                ? 'sebagian bulan'
                                                                : 'bulan penuh'}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3.5 text-right">
                                                    <div className="flex flex-col gap-0.5">
                                                        <span className="num">{rupiah(detail.gaji_pokok)}</span>
                                                        <span className="num text-muted-foreground/80 text-[11px]">
                                                            {rupiah(detail.gaji_pokok_penuh)} × {detail.hari_aktif}/
                                                            {detail.hari_periode}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3.5 text-right">
                                                    <div className="flex flex-col gap-0.5">
                                                        <span className="num text-crit">
                                                            − {rupiah(detail.bpjs)}
                                                        </span>
                                                        <span className="num text-muted-foreground/80 text-[11px]">
                                                            {angka(detail.bpjs_persen)}% dari gaji pokok
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3.5 text-right">
                                                    <div className="flex flex-col gap-0.5">
                                                        <span
                                                            className={`num ${detail.potongan_cashbon > 0 ? 'text-crit' : 'text-muted-foreground'}`}
                                                        >
                                                            {detail.potongan_cashbon > 0
                                                                ? `− ${rupiah(detail.potongan_cashbon)}`
                                                                : '—'}
                                                        </span>
                                                        <span className="num text-muted-foreground/80 text-[11px]">
                                                            {detail.potongans.length > 0
                                                                ? `${angka(detail.potongans.length)} cashbon`
                                                                : 'tanpa cashbon'}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="num px-4 py-3.5 text-right font-medium">
                                                    {rupiah(detail.total_gaji)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="border-foreground border-t-2">
                                        <tr>
                                            <td className="px-4 py-3.5 font-semibold">
                                                Total {angka(summary.karyawan_count)} karyawan
                                            </td>
                                            <td />
                                            <td className="num px-4 py-3.5 text-right font-medium">
                                                {rupiah(totalGajiPokok)}
                                            </td>
                                            <td className="num px-4 py-3.5 text-right font-medium text-crit">
                                                − {rupiah(summary.total_bpjs)}
                                            </td>
                                            <td className="num px-4 py-3.5 text-right font-medium text-crit">
                                                − {rupiah(summary.total_cashbon)}
                                            </td>
                                            <td className="num px-4 py-3.5 text-right text-base font-medium">
                                                {rupiah(summary.total_gaji)}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {/* Kartu slip — di bawah 1024px */}
                            <div className="flex flex-col gap-3 lg:hidden">
                                {details.map((detail) => (
                                    <div key={detail.id} className="bg-card overflow-hidden rounded-lg border">
                                        <div className="bg-muted flex flex-col gap-0.5 border-b p-3.5">
                                            <span className="text-[15px] font-semibold">{detail.karyawan.nama}</span>
                                            <span className="text-muted-foreground text-xs">
                                                {detail.karyawan.jabatan?.nama_jabatan ?? '—'}
                                            </span>
                                        </div>

                                        <div className="flex flex-col gap-2.5 p-3.5">
                                            <Baris
                                                label="Gaji penuh jabatan"
                                                value={rupiah(detail.gaji_pokok_penuh)}
                                            />
                                            <Baris
                                                label="Hari aktif"
                                                value={`${detail.hari_aktif} / ${detail.hari_periode}`}
                                            />
                                        </div>

                                        <div className="flex flex-col gap-2.5 border-t p-3.5">
                                            <Baris label="Gaji pokok" value={rupiah(detail.gaji_pokok)} strong />
                                            <Baris
                                                label={`BPJS · ${angka(detail.bpjs_persen)}%`}
                                                value={`− ${rupiah(detail.bpjs)}`}
                                                negative
                                            />
                                            {detail.potongan_cashbon > 0 ? (
                                                <>
                                                    <Baris
                                                        label="Potongan cashbon"
                                                        value={`− ${rupiah(detail.potongan_cashbon)}`}
                                                        negative
                                                    />
                                                    <BukuBesar potongans={detail.potongans} />
                                                </>
                                            ) : null}
                                        </div>

                                        <div className="border-foreground flex items-baseline justify-between border-t-2 p-3.5">
                                            <span className="text-sm font-semibold">Diterima</span>
                                            <span className="num text-lg font-medium">{rupiah(detail.total_gaji)}</span>
                                        </div>
                                    </div>
                                ))}

                                <div className="bg-foreground text-background flex flex-col gap-2.5 rounded-lg p-4">
                                    <div className="flex items-baseline justify-between gap-3">
                                        <span className="text-background/70 text-[13px]">
                                            Gaji pokok {angka(summary.karyawan_count)} karyawan
                                        </span>
                                        <span className="num text-[13px]">{rupiah(totalGajiPokok)}</span>
                                    </div>
                                    <div className="flex items-baseline justify-between gap-3">
                                        <span className="text-background/70 text-[13px]">BPJS</span>
                                        <span className="num text-[13px]">− {rupiah(summary.total_bpjs)}</span>
                                    </div>
                                    <div className="flex items-baseline justify-between gap-3">
                                        <span className="text-background/70 text-[13px]">Potongan cashbon</span>
                                        <span className="num text-[13px]">− {rupiah(summary.total_cashbon)}</span>
                                    </div>
                                    <div className="bg-background/25 h-px" />
                                    <div className="flex items-baseline justify-between gap-3">
                                        <span className="text-sm font-semibold">Total dibayarkan</span>
                                        <span className="num text-xl font-medium">{rupiah(summary.total_gaji)}</span>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}

                    {dibayar ? (
                        <p className="text-muted-foreground flex items-start gap-2 text-xs leading-relaxed">
                            <Lock className="text-primary mt-0.5 size-4 shrink-0" strokeWidth={1.8} />
                            Penggajian yang sudah dibayar bersifat terkunci — nominal tidak dapat dihitung ulang dan
                            alokasi cashbonnya tidak dapat dilepas.
                        </p>
                    ) : null}
                </section>
            </div>
        </AppLayout>
    );
}
