import { type ImgHTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

type AppLogoIconProps = Omit<
    ImgHTMLAttributes<HTMLImageElement>,
    'src' | 'alt'
>;

/**
 * Lambang perusahaan.
 *
 * Props-nya diteruskan ke <img>. Sebelumnya tidak: kelas ukuran yang dikirim
 * setiap pemanggil — size-7, size-9, h-6 w-6 — dibuang begitu saja, sehingga
 * berkas logo digambar pada ukuran aslinya dan merusak tata letak di layar
 * masuk. Tipenya juga masih SVGAttributes, peninggalan saat lambangnya berupa
 * SVG sebaris, sehingga TypeScript ikut menerima kelas yang tidak pernah
 * sampai ke mana pun.
 */
export default function AppLogoIcon({ className, ...props }: AppLogoIconProps) {
    return (
        <img
            {...props}
            src="/logo.png"
            alt="Logo PDBS"
            className={cn('h-auto w-auto object-contain', className)}
        />
    );
}
