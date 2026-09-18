import ConfirmDelete from '@/components/confirm-delete';
import { LABEL_SUMBER_DAYA } from '@/components/delete-button';

interface DeleteButtonChildProps {
    /** Id induk, mis. id kontrak. */
    id: number;
    /** Segmen rute induk, mis. "kontraks". */
    featured: string;
    /** Segmen rute anak, mis. "dokumens". */
    child: string;
    /** Id baris anak yang dihapus. */
    child_id: number;
    /** Nama baris, ditampilkan di dialog konfirmasi. */
    nama?: string;
    /** Menimpa kata benda di dialog, mis. "penempatan" untuk kontraks/karyawans. */
    jenis?: string;
}

/** Tombol hapus untuk sumber daya bersarang: /{featured}/{id}/{child}/{child_id}. */
export default function DeleteButtonChild({ id, featured, child, child_id, nama, jenis }: DeleteButtonChildProps) {
    return (
        <ConfirmDelete
            url={`/${featured}/${id}/${child}/${child_id}`}
            jenis={jenis ?? LABEL_SUMBER_DAYA[child] ?? child}
            nama={nama}
        />
    );
}
