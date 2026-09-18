import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

interface FormActionsProps {
    processing: boolean;
    /** Label tombol simpan saat diam. */
    simpan: string;
    /** Tujuan tombol batal. */
    batalKe: string;
    /** Menonaktifkan simpan tanpa menampilkan pemintal, mis. belum ada yang dipilih. */
    nonaktif?: boolean;
}

/**
 * Baris tombol formulir.
 *
 * Tombol batal memakai `Button asChild` membungkus `Link`, bukan sebaliknya —
 * pola lama menaruh `<button>` di dalam `<a>`, yang tidak sah dan menghasilkan
 * dua perhentian tab bersarang untuk satu kendali.
 */
export default function FormActions({ processing, simpan, batalKe, nonaktif }: FormActionsProps) {
    return (
        <div className="flex flex-col gap-2 pt-1 sm:flex-row">
            <Button type="submit" disabled={processing || nonaktif} className="h-12 sm:h-9">
                {processing ? (
                    <>
                        <Spinner />
                        Menyimpan…
                    </>
                ) : (
                    simpan
                )}
            </Button>
            <Button asChild variant="outline" type="button" className="h-12 sm:h-9">
                <Link href={batalKe}>Batal</Link>
            </Button>
        </div>
    );
}
