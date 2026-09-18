import { router } from '@inertiajs/react';
import { Trash } from 'lucide-react';
import { useState } from 'react';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

interface ConfirmDeleteProps {
    /** Alamat penghapusan, biasanya dari helper rute. */
    url: string;
    /** Kata benda yang dihapus, mis. "client" atau "dokumen". */
    jenis: string;
    /** Nama baris yang dihapus, ditampilkan di dalam dialog bila ada. */
    nama?: string;
}

/**
 * Dialog konfirmasi hapus tunggal untuk seluruh aplikasi.
 *
 * Penting: komponen ini TIDAK memunculkan toast "berhasil dihapus" sendiri.
 * Penolakan aturan bisnis — mis. karyawan yang punya riwayat penggajian
 * terbayar — kembali sebagai redirect biasa, yang di mata Inertia adalah
 * keberhasilan. Versi sebelumnya karena itu mengumumkan "deleted successfully"
 * tepat di samping pesan galat dari server. Sekarang pesannya hanya satu, dan
 * datang dari server lewat pesan kilat.
 */
export default function ConfirmDelete({ url, jenis, nama }: ConfirmDeleteProps) {
    const [terbuka, setTerbuka] = useState(false);
    const [memproses, setMemproses] = useState(false);

    const hapus = () => {
        setMemproses(true);

        router.delete(url, {
            preserveScroll: true,
            onFinish: () => {
                setMemproses(false);
                setTerbuka(false);
            },
        });
    };

    return (
        <AlertDialog open={terbuka} onOpenChange={setTerbuka}>
            <AlertDialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    aria-label={`Hapus ${jenis}`}
                    className="text-crit hover:bg-crit/10 hover:text-crit h-11 sm:h-8"
                >
                    <Trash />
                    <span className="sm:hidden">Hapus</span>
                </Button>
            </AlertDialogTrigger>

            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus {jenis} ini?</AlertDialogTitle>
                    <AlertDialogDescription>
                        {nama ? (
                            <>
                                <span className="text-foreground font-medium">{nama}</span> akan dihapus permanen.{' '}
                            </>
                        ) : null}
                        Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel disabled={memproses}>Batal</AlertDialogCancel>
                    <AlertDialogAction asChild>
                        <Button
                            variant="destructive"
                            disabled={memproses}
                            onClick={(event) => {
                                event.preventDefault();
                                hapus();
                            }}
                        >
                            {memproses ? (
                                <>
                                    <Spinner />
                                    Menghapus…
                                </>
                            ) : (
                                'Hapus'
                            )}
                        </Button>
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
