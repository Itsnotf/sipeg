import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

import type { SharedData } from '@/types';

/**
 * Menampilkan pesan kilat dari server sebagai toast, sekali per pesan.
 *
 * Sebelumnya blok useEffect yang sama disalin di belasan halaman, dan sebagian
 * salinan hanya menangani `success` — sehingga kegagalan di sisi server pada
 * halaman-halaman itu hilang tanpa jejak di layar.
 */
export function useFlashToast(): void {
    const { flash } = usePage<SharedData>().props;
    const terlihat = useRef(new Set<string>());

    useEffect(() => {
        const tampilkan = (pesan: string | undefined, jenis: 'success' | 'error' | 'info') => {
            if (!pesan || terlihat.current.has(jenis + pesan)) return;

            terlihat.current.add(jenis + pesan);
            toast[jenis](pesan);
        };

        tampilkan(flash?.success, 'success');
        tampilkan(flash?.error, 'error');
        tampilkan(flash?.info, 'info');
    }, [flash?.success, flash?.error, flash?.info]);
}
