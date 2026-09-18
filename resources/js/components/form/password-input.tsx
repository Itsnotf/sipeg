import { Eye, EyeOff } from 'lucide-react';
import { useId, useState, type ComponentProps } from 'react';

import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type PasswordInputProps = Omit<ComponentProps<typeof Input>, 'type'>;

/**
 * Kolom kata sandi dengan sakelar tampil/sembunyi milik aplikasi sendiri.
 *
 * Sebelumnya tidak ada sakelar sama sekali di sini — yang terlihat pengguna
 * adalah kendali bawaan peramban (::-ms-reveal pada Edge). Kendali itu hanya
 * muncul selama kolomnya berisi DAN sedang difokuskan, lalu lenyap begitu
 * fokus berpindah; itulah "tombolnya hilang sendiri". Kendali bawaan tersebut
 * disembunyikan lewat app.css, dan digantikan tombol ini yang tetap di
 * tempatnya selama masih ada yang bisa ditampilkan.
 */
export default function PasswordInput({
    className,
    ...props
}: PasswordInputProps) {
    const [terlihat, setTerlihat] = useState(false);
    const petunjukId = useId();

    return (
        <div className="relative">
            <Input
                {...props}
                type={terlihat ? 'text' : 'password'}
                aria-describedby={petunjukId}
                // Ruang di kanan agar teks tidak berjalan di bawah tombolnya.
                className={cn('pr-11', className)}
            />

            <button
                type="button"
                onClick={() => setTerlihat((sebelumnya) => !sebelumnya)}
                aria-pressed={terlihat}
                aria-label={
                    terlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
                }
                className={cn(
                    'absolute top-1/2 right-1 text-muted-foreground hover:text-foreground focus-visible:ring-ring/50',
                    'flex size-9 -translate-y-1/2 items-center justify-center rounded-md',
                    'transition-colors focus-visible:ring-[3px] focus-visible:outline-none',
                )}
            >
                {terlihat ? (
                    <EyeOff className="size-4" />
                ) : (
                    <Eye className="size-4" />
                )}
            </button>

            <span id={petunjukId} className="sr-only">
                {terlihat
                    ? 'Kata sandi sedang ditampilkan.'
                    : 'Kata sandi sedang disembunyikan.'}
            </span>
        </div>
    );
}
