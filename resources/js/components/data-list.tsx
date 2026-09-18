import { type ReactNode } from 'react';

import { cn } from '@/lib/utils';

export interface DataColumn<T> {
    /** Kunci unik kolom. */
    key: string;
    /** Judul kolom pada tampilan tabel. */
    header: string;
    /** Isi sel. */
    cell: (row: T) => ReactNode;
    /** Kelas tambahan untuk sel tabel, mis. "text-right". */
    className?: string;
    /** Lebar kolom pada tampilan tabel, mis. "w-40". */
    width?: string;
    /**
     * Kolom identitas — naik menjadi judul kartu pada layar sempit.
     * Tepat satu kolom sebaiknya ditandai demikian.
     */
    primary?: boolean;
    /** Ditempatkan di sudut kanan atas kartu, biasanya lencana status. */
    badge?: boolean;
    /** Disembunyikan dari kartu (sudah terwakili judul, atau tidak penting di ponsel). */
    hideOnCard?: boolean;
}

interface DataListProps<T> {
    columns: DataColumn<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    /** Aksi per baris — kolom terakhir di tabel, deretan tombol di kartu. */
    actions?: (row: T) => ReactNode;
    /** Digambar saat tidak ada baris sama sekali. */
    empty?: ReactNode;
    /** Baris ringkasan di kaki tabel. */
    footer?: ReactNode;
    className?: string;
}

/**
 * Pola inti responsif aplikasi ini.
 *
 * Di atas 640px baris digambar sebagai tabel berbelang. Di bawahnya setiap
 * baris menjadi satu kartu: kolom identitas naik menjadi judul, lencana pindah
 * ke sudut kanan, sisanya menjadi pasangan label-nilai, dan aksi turun menjadi
 * tombol setinggi 44px.
 *
 * Sebelumnya tabel sembilan kolom hanya bisa digeser ke samping tanpa petunjuk
 * apa pun bahwa masih ada kolom di sebelah kanan.
 */
export default function DataList<T>({
    columns,
    rows,
    rowKey,
    actions,
    empty,
    footer,
    className,
}: DataListProps<T>) {
    if (rows.length === 0 && empty) {
        return <div className={cn('bg-card rounded-lg border', className)}>{empty}</div>;
    }

    const primary = columns.find((column) => column.primary);
    const badges = columns.filter((column) => column.badge);
    const details = columns.filter((column) => !column.primary && !column.badge && !column.hideOnCard);

    return (
        <div className={cn('flex flex-col', className)}>
            {/* Tabel — 640px ke atas */}
            <div className="bg-card hidden overflow-hidden rounded-lg border sm:block">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="bg-muted border-b">
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className={cn(
                                        'text-muted-foreground h-9 px-4 text-left text-[10.5px] font-semibold tracking-[0.09em] whitespace-nowrap uppercase',
                                        column.width,
                                        column.className,
                                    )}
                                >
                                    {column.header}
                                </th>
                            ))}
                            {actions ? (
                                <th className="text-muted-foreground h-9 px-4 text-right text-[10.5px] font-semibold tracking-[0.09em] whitespace-nowrap uppercase">
                                    Aksi
                                </th>
                            ) : null}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr
                                key={rowKey(row)}
                                className={cn('border-b last:border-b-0', index % 2 === 1 && 'bg-muted/45')}
                            >
                                {columns.map((column) => (
                                    <td key={column.key} className={cn('px-4 py-3 align-middle', column.className)}>
                                        {column.cell(row)}
                                    </td>
                                ))}
                                {actions ? (
                                    <td className="px-4 py-3 text-right align-middle whitespace-nowrap">{actions(row)}</td>
                                ) : null}
                            </tr>
                        ))}
                    </tbody>
                    {footer ? <tfoot className="border-foreground border-t-2">{footer}</tfoot> : null}
                </table>
            </div>

            {/* Kartu — di bawah 640px */}
            <div className="flex flex-col gap-2.5 sm:hidden">
                {rows.map((row) => (
                    <div key={rowKey(row)} className="bg-card flex flex-col gap-3 rounded-lg border p-3.5">
                        <div className="flex items-start justify-between gap-3">
                            {primary ? <div className="min-w-0 text-[15px] font-semibold">{primary.cell(row)}</div> : null}
                            {badges.length > 0 ? (
                                <div className="flex shrink-0 items-center gap-1.5">
                                    {badges.map((column) => (
                                        <span key={column.key}>{column.cell(row)}</span>
                                    ))}
                                </div>
                            ) : null}
                        </div>

                        {details.length > 0 ? (
                            <div className="border-border/70 flex flex-col gap-2 border-t pt-3">
                                {details.map((column) => (
                                    <div key={column.key} className="flex items-baseline justify-between gap-4">
                                        <span className="text-muted-foreground shrink-0 text-xs">{column.header}</span>
                                        <span className="min-w-0 text-right text-[13px]">{column.cell(row)}</span>
                                    </div>
                                ))}
                            </div>
                        ) : null}

                        {actions ? (
                            <div className="border-border/70 flex items-center gap-2 border-t pt-3">{actions(row)}</div>
                        ) : null}
                    </div>
                ))}
            </div>
        </div>
    );
}
