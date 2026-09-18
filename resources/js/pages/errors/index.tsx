import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Ban, FileQuestion, ServerCrash, TimerReset } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

interface Props {
    status: number;
}

const keterangan: Record<number, { judul: string; pesan: string; icon: typeof Ban }> = {
    403: {
        judul: 'Tidak punya akses',
        pesan: 'Akun Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator bila Anda merasa seharusnya bisa.',
        icon: Ban,
    },
    404: {
        judul: 'Halaman tidak ditemukan',
        pesan: 'Alamat yang Anda buka tidak ada, atau datanya sudah dihapus.',
        icon: FileQuestion,
    },
    419: {
        judul: 'Sesi berakhir',
        pesan: 'Halaman ini terbuka terlalu lama sehingga sesinya kedaluwarsa. Muat ulang, lalu coba lagi.',
        icon: TimerReset,
    },
    500: {
        judul: 'Terjadi kesalahan di server',
        pesan: 'Permintaan Anda tidak dapat diselesaikan. Kesalahannya sudah dicatat untuk ditelusuri.',
        icon: ServerCrash,
    },
    503: {
        judul: 'Sedang dalam perawatan',
        pesan: 'Aplikasi sementara tidak tersedia. Silakan coba beberapa saat lagi.',
        icon: ServerCrash,
    },
};

export default function ErrorPage({ status }: Props) {
    const { judul, pesan, icon: Icon } = keterangan[status] ?? keterangan[500];

    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 px-6 py-16">
            <Head title={`${status} — ${judul}`} />

            <div className="flex flex-col items-center gap-5 text-center">
                <div className="border-border flex size-14 items-center justify-center rounded-lg border">
                    <Icon className="text-muted-foreground size-6" strokeWidth={1.5} />
                </div>

                <div className="flex flex-col items-center gap-2">
                    <span className="num text-muted-foreground text-sm tracking-[0.2em]">{status}</span>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{judul}</h1>
                    <p className="text-muted-foreground max-w-[46ch] text-sm leading-relaxed">{pesan}</p>
                </div>

                <div className="flex flex-col gap-2 sm:flex-row">
                    <Button asChild className="h-11 sm:h-9">
                        <Link href={dashboard()}>
                            <ArrowLeft />
                            Kembali ke dashboard
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        type="button"
                        className="h-11 sm:h-9"
                        onClick={() => window.history.back()}
                    >
                        Halaman sebelumnya
                    </Button>
                </div>
            </div>
        </div>
    );
}
