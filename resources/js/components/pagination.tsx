import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { angka, cn } from '@/lib/utils';

export interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginatorLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface PaginationProps {
    /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
    meta: Paginated<any>;
    /** Kata benda yang dihitung, mis. "kontrak" atau "cashbon". */
    noun?: string;
    className?: string;
}

const box =
    'inline-flex h-11 min-w-11 items-center justify-center rounded-md border px-3 text-sm transition-colors sm:h-9 sm:min-w-9';

/**
 * Paginasi tunggal untuk seluruh aplikasi.
 *
 * Sebelumnya markup yang sama disalin di tujuh halaman, tautan nonaktif tetap
 * mengarah ke "#" sehingga bisa diklik, dan target sentuhnya hanya px-3 py-1.
 */
export default function Pagination({ meta, noun = 'baris', className }: PaginationProps) {
    if (meta.last_page <= 1) {
        return null;
    }

    // Laravel menaruh tautan sebelumnya/berikutnya di ujung larik; keduanya
    // digambar terpisah sebagai tombol ikon.
    const pages = meta.links.slice(1, -1);

    return (
        <div className={cn('flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between', className)}>
            <p className="text-muted-foreground text-xs">
                Menampilkan <span className="num">{angka(meta.from ?? 0)}</span>&ndash;
                <span className="num">{angka(meta.to ?? 0)}</span> dari <span className="num">{angka(meta.total)}</span>{' '}
                {noun}
            </p>

            <div className="flex flex-wrap items-center gap-1.5">
                {meta.prev_page_url ? (
                    <Link href={meta.prev_page_url} className={cn(box, 'bg-card hover:bg-accent')} aria-label="Halaman sebelumnya">
                        <ChevronLeft className="size-4" />
                    </Link>
                ) : (
                    <span className={cn(box, 'text-muted-foreground/40 border-border/60')} aria-disabled="true">
                        <ChevronLeft className="size-4" />
                    </span>
                )}

                {pages.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            aria-current={link.active ? 'page' : undefined}
                            className={cn(
                                box,
                                'num',
                                link.active
                                    ? 'bg-foreground text-background border-foreground font-semibold'
                                    : 'bg-card hover:bg-accent',
                            )}
                        >
                            {link.label}
                        </Link>
                    ) : (
                        <span key={index} className={cn(box, 'num text-muted-foreground/60 border-transparent')}>
                            {link.label}
                        </span>
                    ),
                )}

                {meta.next_page_url ? (
                    <Link href={meta.next_page_url} className={cn(box, 'bg-card hover:bg-accent')} aria-label="Halaman berikutnya">
                        <ChevronRight className="size-4" />
                    </Link>
                ) : (
                    <span className={cn(box, 'text-muted-foreground/40 border-border/60')} aria-disabled="true">
                        <ChevronRight className="size-4" />
                    </span>
                )}
            </div>
        </div>
    );
}
