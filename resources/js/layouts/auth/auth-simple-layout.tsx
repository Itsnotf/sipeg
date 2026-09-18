import { type PropsWithChildren } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

/**
 * Layar masuk.
 *
 * Judulnya serif dan keterangannya di bawah, mengikuti PageHeader di dalam
 * aplikasi, supaya halaman pertama yang dilihat pengguna sudah terasa bagian
 * dari sistem yang sama. Lambangnya tidak lagi menjadi tautan: satu-satunya
 * tujuan yang dulu dimilikinya adalah "/", dan "/" sekarang mengarah balik ke
 * halaman ini.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
            <div className="flex w-full max-w-sm flex-col gap-8">
                <div className="flex flex-col gap-3">
                    <div className="flex items-center gap-2.5">
                        <AppLogoIcon className="size-8 shrink-0" />
                        <span className="eyebrow text-primary">SIPEG</span>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <h1 className="font-serif text-3xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                </div>

                {children}

                <p className="border-t border-border/70 pt-5 text-xs leading-relaxed text-muted-foreground">
                    Sistem Informasi Penggajian — pengelolaan kontrak,
                    penempatan pekerja, dan penggajian outsourcing.
                </p>
            </div>
        </div>
    );
}
