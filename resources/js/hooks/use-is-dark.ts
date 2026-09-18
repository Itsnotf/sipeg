import { useEffect, useState } from 'react';

/**
 * Apakah tema gelap sedang aktif.
 *
 * Mengamati kelas `dark` pada elemen root, bukan preferensi yang tersimpan —
 * sehingga tetap benar baik ketika pengguna memilih terang/gelap secara eksplisit
 * maupun ketika mengikuti setelan sistem.
 *
 * Grafik membutuhkan ini karena warnanya ditulis sebagai hex literal, bukan
 * variabel CSS: nilai hex itulah yang sudah divalidasi keterbedaannya bagi
 * penglihatan warna terbatas, dan keduanya berbeda antar tema.
 */
export function useIsDark(): boolean {
    const [gelap, setGelap] = useState(
        () => typeof document !== 'undefined' && document.documentElement.classList.contains('dark'),
    );

    useEffect(() => {
        const root = document.documentElement;
        const perbarui = () => setGelap(root.classList.contains('dark'));

        perbarui();

        const pengamat = new MutationObserver(perbarui);
        pengamat.observe(root, { attributes: true, attributeFilter: ['class'] });

        return () => pengamat.disconnect();
    }, []);

    return gelap;
}
