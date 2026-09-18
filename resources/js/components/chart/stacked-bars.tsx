import { useLayoutEffect, useRef, useState } from 'react';

import { useIsDark } from '@/hooks/use-is-dark';
import { rupiah, rupiahRingkas } from '@/lib/utils';

import { seriWarna, warnaBantu } from './palette';

export interface SeriGrafik {
    key: string;
    label: string;
}

export interface TitikGrafik {
    label: string;
    /** Nilai per seri, dijumlahkan menjadi tinggi batang. */
    nilai: Record<string, number>;
}

interface StackedBarsProps {
    seri: SeriGrafik[];
    titik: TitikGrafik[];
    tinggi?: number;
    /** Keterangan pengganti saat tidak ada data. */
    kosong?: string;
}

const PAD = { atas: 10, kanan: 8, bawah: 30, kiri: 62 };
const JARAK_SEGMEN = 2;

/**
 * Batang bertumpuk: besaran per periode sekaligus komposisinya.
 *
 * Satu sumbu nilai saja. Segmen dipisah jarak 2px agar batasnya terbaca tanpa
 * bergantung pada perbedaan warna, dan legenda selalu hadir untuk ≥2 seri —
 * identitas seri tidak pernah bersandar pada warna semata.
 */
export default function StackedBars({ seri, titik, tinggi = 260, kosong }: StackedBarsProps) {
    const gelap = useIsDark();
    const warna = seriWarna(gelap);
    const bantu = warnaBantu(gelap);

    const wadah = useRef<HTMLDivElement>(null);
    const [lebar, setLebar] = useState(0);
    const [aktif, setAktif] = useState<number | null>(null);

    // Diukur dalam piksel sebenarnya, bukan diskalakan lewat viewBox — supaya
    // ukuran teks tetap terbaca di layar sempit.
    useLayoutEffect(() => {
        const el = wadah.current;
        if (!el) return;

        const ukur = () => setLebar(el.clientWidth);
        ukur();

        const pengamat = new ResizeObserver(ukur);
        pengamat.observe(el);

        return () => pengamat.disconnect();
    }, []);

    if (titik.length === 0) {
        return (
            <div ref={wadah} className="text-muted-foreground flex h-40 items-center justify-center text-sm">
                {kosong ?? 'Belum ada data untuk ditampilkan.'}
            </div>
        );
    }

    const plotW = Math.max(0, lebar - PAD.kiri - PAD.kanan);
    const plotH = tinggi - PAD.atas - PAD.bawah;

    const total = (t: TitikGrafik) => seri.reduce((s, x) => s + (t.nilai[x.key] ?? 0), 0);
    const puncak = Math.max(...titik.map(total), 1);

    // Skala dibulatkan ke atas agar tick berupa angka bulat yang enak dibaca
    const magnitudo = 10 ** Math.floor(Math.log10(puncak));
    const atasSkala = Math.ceil(puncak / magnitudo) * magnitudo;

    const ticks = [0, 0.25, 0.5, 0.75, 1].map((f) => atasSkala * f);
    const y = (v: number) => PAD.atas + plotH - (v / atasSkala) * plotH;

    const band = plotW / titik.length;
    const lebarBatang = Math.max(8, Math.min(34, band * 0.56));

    return (
        <div ref={wadah} className="relative w-full">
            {lebar > 0 && (
                <svg width={lebar} height={tinggi} role="img" aria-label="Komposisi penggajian per periode">
                    {/* Garis bantu — surut, di belakang marka */}
                    {ticks.map((t) => (
                        <g key={t}>
                            <line
                                x1={PAD.kiri}
                                x2={lebar - PAD.kanan}
                                y1={y(t)}
                                y2={y(t)}
                                stroke={t === 0 ? bantu.sumbu : bantu.grid}
                                strokeWidth={1}
                            />
                            <text
                                x={PAD.kiri - 10}
                                y={y(t) + 4}
                                textAnchor="end"
                                className="fill-muted-foreground"
                                style={{ fontSize: 11, fontFamily: 'var(--font-mono)' }}
                            >
                                {t === 0 ? '0' : rupiahRingkas(t)}
                            </text>
                        </g>
                    ))}

                    {titik.map((t, i) => {
                        const cx = PAD.kiri + band * i + band / 2;
                        const x = cx - lebarBatang / 2;
                        let kursor = PAD.atas + plotH;

                        return (
                            <g
                                key={t.label}
                                onMouseEnter={() => setAktif(i)}
                                onMouseLeave={() => setAktif(null)}
                                onFocus={() => setAktif(i)}
                                onBlur={() => setAktif(null)}
                                tabIndex={0}
                                style={{ outline: 'none' }}
                            >
                                {/* Sasaran arahkan lebih lebar dari batangnya */}
                                <rect
                                    x={PAD.kiri + band * i}
                                    y={PAD.atas}
                                    width={band}
                                    height={plotH}
                                    fill={aktif === i ? bantu.grid : 'transparent'}
                                />

                                {seri.map((s, si) => {
                                    const nilai = t.nilai[s.key] ?? 0;
                                    if (nilai <= 0) return null;

                                    const h = (nilai / atasSkala) * plotH;
                                    kursor -= h;

                                    return (
                                        <rect
                                            key={s.key}
                                            x={x}
                                            y={kursor}
                                            width={lebarBatang}
                                            height={Math.max(0, h - JARAK_SEGMEN)}
                                            fill={warna[si % warna.length]}
                                            rx={2}
                                        />
                                    );
                                })}

                                <text
                                    x={cx}
                                    y={tinggi - 10}
                                    textAnchor="middle"
                                    className="fill-muted-foreground"
                                    style={{ fontSize: 11 }}
                                >
                                    {t.label}
                                </text>
                            </g>
                        );
                    })}
                </svg>
            )}

            {/* Tooltip */}
            {aktif !== null && lebar > 0 && (
                <div
                    className="bg-popover pointer-events-none absolute z-10 flex min-w-44 flex-col gap-1.5 rounded-md border p-3 shadow-md"
                    style={{
                        left: Math.min(Math.max(PAD.kiri + band * aktif + band / 2 - 88, 4), Math.max(4, lebar - 180)),
                        top: 4,
                    }}
                >
                    <span className="text-xs font-semibold">{titik[aktif].label}</span>
                    {seri.map((s, si) => (
                        <span key={s.key} className="flex items-center justify-between gap-4 text-xs">
                            <span className="text-muted-foreground flex items-center gap-1.5">
                                <span
                                    className="size-2 shrink-0 rounded-xs"
                                    style={{ background: warna[si % warna.length] }}
                                />
                                {s.label}
                            </span>
                            <span className="num">{rupiah(titik[aktif].nilai[s.key] ?? 0)}</span>
                        </span>
                    ))}
                    <span className="mt-0.5 flex items-center justify-between gap-4 border-t pt-1.5 text-xs font-semibold">
                        <span>Total</span>
                        <span className="num">{rupiah(total(titik[aktif]))}</span>
                    </span>
                </div>
            )}

            {/* Legenda — selalu ada untuk dua seri atau lebih */}
            <div className="text-muted-foreground mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs">
                {seri.map((s, si) => (
                    <span key={s.key} className="flex items-center gap-1.5">
                        <span className="size-2.5 rounded-xs" style={{ background: warna[si % warna.length] }} />
                        {s.label}
                    </span>
                ))}
            </div>
        </div>
    );
}
