import ConfirmDelete from '@/components/confirm-delete';

/** Segmen rute jamak → kata benda tunggal yang dibaca pengguna. */
export const LABEL_SUMBER_DAYA: Record<string, string> = {
    cashbons: 'cashbon',
    clients: 'client',
    dokumens: 'dokumen',
    jabatans: 'jabatan',
    karyawans: 'karyawan',
    kontraks: 'kontrak',
    roles: 'role',
    users: 'pengguna',
};

interface DeleteButtonProps {
    id: number;
    /** Segmen rute sumber daya, mis. "clients". */
    featured: string;
    /** Nama baris, ditampilkan di dialog konfirmasi. */
    nama?: string;
}

/** Tombol hapus untuk sumber daya tingkat atas: /{featured}/{id}. */
export default function DeleteButton({ id, featured, nama }: DeleteButtonProps) {
    return (
        <ConfirmDelete url={`/${featured}/${id}`} jenis={LABEL_SUMBER_DAYA[featured] ?? featured} nama={nama} />
    );
}
