import { type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface StatTileProps {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    tone?: 'default' | 'positive' | 'warning' | 'critical';
    className?: string;
}

const toneClass: Record<NonNullable<StatTileProps['tone']>, string> = {
    default: 'text-foreground',
    positive: 'text-ok',
    warning: 'text-warn',
    critical: 'text-crit',
};

/** Satu angka besar dengan label di atas dan keterangan di bawahnya. */
export default function StatTile({ label, value, hint, tone = 'default', className }: StatTileProps) {
    return (
        <div className={cn('bg-card flex flex-col gap-1.5 rounded-lg border p-4', className)}>
            <span className="eyebrow">{label}</span>
            <span className={cn('num text-xl font-medium tracking-tight sm:text-2xl', toneClass[tone])}>{value}</span>
            {hint ? <span className="text-muted-foreground text-xs">{hint}</span> : null}
        </div>
    );
}
