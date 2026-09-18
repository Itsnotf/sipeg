import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    permissions: Permission[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    permissions?: string[];
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    flash?: {
        success?: string;
        error?: string;
        info?: string;
    };
    errors?: Record<string, string>;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    /** Sudah diformat Y-m-d di server; null bila belum terverifikasi. */
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    roles?: Role;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Permission {
    id: number;
    name: string;
    /* Hanya dikirim oleh endpoint role; prop bersama hanya membawa id dan nama. */
    guard_name?: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name?: string;
    permissions?: Permission[];
}

export interface Jabatan {
    id: number;
    nama_jabatan: string;
    deskripsi: string;
    gaji: string;
    /** Nominal rupiah historis, dipertahankan sebagai rujukan sebelum BPJS menjadi persentase. */
    bpjs: string | null;
    /** Persentase gaji pokok; nominalnya dihitung saat penggajian diproses. */
    bpjs_persen: string;
}

export interface Karyawan {
    id: number;
    id_jabatan: number;
    nama: string;
    nik: number;
    alamat: string;
    no_hp: number;
    jenis_kelamin: string;
    tanggal_lahir: string;
    status: string;
    jabatan: Jabatan
}

export interface Client {
    id: number;
    nama_client: string;
    deskripsi: string;
    alamat: string;
    email: string;
    no_hp: string;
}

export interface Kontrak {
    id: number;
    client_id: number;
    judul: string;
    deskripsi: string;
    tanggal_mulai: string;
    tanggal_selesai: string;
    tanggal_gajian: string;
    status: string;
    total_biaya: string;
    client : Client
}

export interface KontrakDokumen {
    id: number;
    kontrak_id: number;
    nama_dokumen: string;
    file: string;
}

export interface KontrakKaryawan {
    id: number;
    kontrak_id : number;
    karyawan_id : number;
    kontrak: Kontrak;
    karyawan: Karyawan;
}

export interface Cashbon {
    id : number;
    karyawan_id : number;
    jumlah : string;
    keterangan: string;
    status : string;
    karyawan: Karyawan;
}