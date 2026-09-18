import { cn } from '@/lib/utils';

type Tone = 'positive' | 'warning' | 'critical' | 'neutral' | 'outline';

interface StatusBadgeProps {
    children: React.ReactNode;
    tone?: Tone;
    className?: string;
}

const toneClass: Record<Tone, string> = {
    positive: 'bg-[color-mix(in_oklch,var(--ok)_16%,transparent)] text-ok',
    warning: 'bg-[color-mix(in_oklch,var(--warn)_18%,transparent)] text-warn',
    critical: 'bg-[color-mix(in_oklch,var(--crit)_16%,transparent)] text-crit',
    neutral: 'bg-muted text-muted-foreground',
    outline: 'text-muted-foreground border border-border',
};

/** Lencana status bergaya buku besar: huruf besar, kecil, dan tenang. */
export default function StatusBadge({ children, tone = 'neutral', className }: StatusBadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex w-fit shrink-0 items-center rounded-sm px-2 py-1 text-[10.5px] font-semibold tracking-[0.05em] whitespace-nowrap uppercase',
                toneClass[tone],
                className,
            )}
        >
            {children}
        </span>
    );
}
