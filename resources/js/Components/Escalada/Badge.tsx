import { PropsWithChildren } from 'react';

type Tone = 'green' | 'yellow' | 'red' | 'gray' | 'blue' | 'purple';

const tones: Record<Tone, string> = {
    green: 'bg-emerald-100 text-emerald-700',
    yellow: 'bg-amber-100 text-amber-700',
    red: 'bg-rose-100 text-rose-700',
    gray: 'bg-slate-100 text-slate-700',
    blue: 'bg-sky-100 text-sky-700',
    purple: 'bg-fuchsia-100 text-fuchsia-700',
};

export default function Badge({ tone, children }: PropsWithChildren<{ tone: Tone }>) {
    return <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${tones[tone]}`}>{children}</span>;
}
