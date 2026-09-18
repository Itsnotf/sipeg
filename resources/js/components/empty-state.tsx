import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description?: ReactNode;
    action?: ReactNode;
    className?: string;
}

/**
 * Menggantikan sel tabel kosong setinggi 65vh yang sebelumnya memenuhi seluruh
 * layar ponsel tanpa menjelaskan apa pun.
 */
export default function EmptyState({ icon: Icon, title, description, action, className }: EmptyStateProps) {
    return (
        <div className={cn('flex flex-col items-center gap-3 px-6 py-12 text-center', className)}>
            <Icon className="text-muted-foreground/50 size-8" strokeWidth={1.4} />
            <div className="text-base font-semibold">{title}</div>
            {description ? (
                <p className="text-muted-foreground max-w-[34ch] text-sm leading-relaxed">{description}</p>
            ) : null}
            {action ? <div className="pt-1">{action}</div> : null}
        </div>
    );
}
