import { type ReactNode } from 'react';

import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface FieldProps {
    /** Harus sama dengan id kontrol di dalamnya, agar label benar-benar terhubung. */
    id: string;
    label: string;
    error?: string;
    /** Keterangan singkat di bawah label — aturan atau format yang diharapkan. */
    hint?: ReactNode;
    opsional?: boolean;
    children: ReactNode;
    className?: string;
}

/**
 * Satu bidang formulir: label, kontrol, keterangan, dan pesan galat.
 *
 * Ruang galat tidak pernah dicadangkan di muka, tetapi label dan kontrol selalu
 * terhubung lewat id yang sama — sebelumnya beberapa formulir memakai label
 * tanpa htmlFor sehingga pembaca layar tidak tahu label itu milik kontrol mana.
 */
export default function Field({ id, label, error, hint, opsional, children, className }: FieldProps) {
    return (
        <div className={cn('flex flex-col gap-2', className)}>
            <div className="flex items-baseline justify-between gap-3">
                <Label htmlFor={id}>{label}</Label>
                {opsional ? <span className="text-muted-foreground text-xs">opsional</span> : null}
            </div>

            {children}

            {hint && !error ? <p className="text-muted-foreground text-xs leading-relaxed">{hint}</p> : null}
            <InputError message={error} />
        </div>
    );
}
