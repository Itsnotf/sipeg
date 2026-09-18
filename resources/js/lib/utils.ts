import { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function isSameUrl(
    url1: NonNullable<InertiaLinkProps['href']>,
    url2: NonNullable<InertiaLinkProps['href']>,
) {
    return resolveUrl(url1) === resolveUrl(url2);
}

export function resolveUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Format rupiah tunggal untuk seluruh aplikasi.
 *
 * Sebelumnya cuplikan Intl.NumberFormat yang sama disalin di lima halaman dan
 * satu halaman memakai bentuk berbeda, sehingga kolom uang tidak konsisten.
 */
export function rupiah(value: number | string | null | undefined): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
}

/** Nominal tanpa simbol mata uang — untuk kolom yang sudah berjudul "Rp". */
export function angka(value: number | string | null | undefined): string {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value ?? 0));
}

/** Bentuk ringkas untuk kartu statistik di layar sempit: 1,21 M · 184,7 jt. */
export function rupiahRingkas(value: number | string | null | undefined): string {
    const n = Number(value ?? 0);
    const format = (v: number, suffix: string) =>
        `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(v)} ${suffix}`;

    if (Math.abs(n) >= 1_000_000_000) return format(n / 1_000_000_000, 'M');
    if (Math.abs(n) >= 1_000_000) return format(n / 1_000_000, 'jt');
    if (Math.abs(n) >= 1_000) return format(n / 1_000, 'rb');

    return angka(n);
}

/** Tanggal panjang Indonesia: 25 Mei 2026. */
export function tanggal(value: string | null | undefined): string {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

/** Tanggal ringkas: 25 Mei 2026 → 25 Mei. */
export function tanggalRingkas(value: string | null | undefined): string {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

/** Nama bulan sebuah periode: 2026-04-01 → April 2026. */
export function periodeLabel(value: string | null | undefined): string {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
}

/** Label sumbu grafik: 2026-04-01 → Apr '26. */
export function bulanSingkat(value: string | null | undefined): string {
    if (!value) return '—';

    const d = new Date(value);
    const bulan = d.toLocaleDateString('id-ID', { month: 'short' });

    return `${bulan} '${String(d.getFullYear()).slice(2)}`;
}


import { usePage } from "@inertiajs/react";
import { Permission } from '@/types';

type AuthProps = {
    permissions?: Permission[];
};

export default function hasAnyPermission(permissions: string[]) {
    const { auth } = usePage().props as { auth?: AuthProps };
    
    // Handle case when user is not logged in
    if (!auth || !auth.permissions) {
        return false;
    }

    const allPermissions: string[] = auth.permissions.map((p: Permission) => p.name);

    return permissions.some(p => allPermissions.includes(p));
}