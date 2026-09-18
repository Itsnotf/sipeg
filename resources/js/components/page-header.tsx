import { type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface PageHeaderProps {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    eyebrow?: ReactNode;
    className?: string;
}

/**
 * Kepala halaman yang sama di seluruh aplikasi: judul serif, keterangan, dan
 * tombol aksi yang turun ke bawah judul saat layar menyempit.
 */
export default function PageHeader({ title, description, actions, eyebrow, className }: PageHeaderProps) {
    return (
        <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between', className)}>
            <div className="flex min-w-0 flex-col gap-1.5">
                {eyebrow ? <div className="eyebrow text-primary">{eyebrow}</div> : null}
                <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{title}</h1>
                {description ? <div className="text-muted-foreground text-sm">{description}</div> : null}
            </div>

            {actions ? <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div> : null}
        </div>
    );
}
