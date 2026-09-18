/**
 * Palet seri grafik — nilai hex literal, bukan variabel CSS.
 *
 * Nilai-nilai inilah yang dijalankan lewat pemeriksa palet dan lolos kelima cek:
 * pita kelaranan, ambang chroma, keterbedaan bagi penglihatan warna terbatas,
 * ambang penglihatan normal, dan kontras terhadap permukaan.
 *
 * Urutan rona sengaja tidak berurut semantik. Menukarnya akan mendekatkan ungu
 * dan biru, yang runtuh menjadi satu warna bagi deuteranopia (ΔE 2,3).
 *
 * Pita kelaranan tema gelap lebih GELAP daripada tema terang (L 0.48–0.67):
 * warna pekat pertengahan terbaca di permukaan gelap, pastel terang menyilaukan.
 */
export const SERI_TERANG = ['#007938', '#006cba', '#c67700', '#7c30a2', '#be2132'] as const;
export const SERI_GELAP = ['#29a259', '#1e85d4', '#cd7d00', '#9a50c2', '#cf4047'] as const;

export function seriWarna(gelap: boolean): readonly string[] {
    return gelap ? SERI_GELAP : SERI_TERANG;
}

/** Warna garis bantu dan sumbu — surut, tidak pernah bersaing dengan marka data. */
export function warnaBantu(gelap: boolean) {
    return {
        grid: gelap ? 'rgba(233,231,223,0.10)' : 'rgba(31,30,25,0.09)',
        sumbu: gelap ? 'rgba(233,231,223,0.28)' : 'rgba(31,30,25,0.22)',
        permukaan: gelap ? '#161916' : '#ffffff',
    };
}
