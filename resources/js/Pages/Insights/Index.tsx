import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function InsightsIndex({ byUser, substitutionsCount, ranking }: { byUser: Array<{ user_id: number; name: string; presence: number; absence: number; not_computed: number; total: number; presence_rate: number; color: 'green' | 'yellow' | 'red' | 'gray' }>; substitutionsCount: number; ranking: Array<{ name: string; presence_rate: number }>; }) {
    const colorMap: Record<string, string> = {
        green: 'text-emerald-700 bg-emerald-50',
        yellow: 'text-amber-700 bg-amber-50',
        red: 'text-rose-700 bg-rose-50',
        gray: 'text-slate-700 bg-slate-100',
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800">Insights</h2>}>
            <Head title="Insights" />
            <div className="mx-auto max-w-6xl space-y-4 px-4 py-6">
                <div className="grid gap-3 sm:grid-cols-3">
                    <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                        <p className="text-xs text-slate-500">Substituicoes</p>
                        <p className="mt-2 text-2xl font-bold">{substitutionsCount}</p>
                    </div>
                    <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100 sm:col-span-2">
                        <p className="text-xs text-slate-500">Ranking de assiduidade</p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {ranking.slice(0, 5).map((item) => (
                                <span key={item.name} className="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{item.name}: {item.presence_rate}%</span>
                            ))}
                        </div>
                    </div>
                </div>

                <section className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                    <h3 className="text-sm font-semibold text-slate-700">Indicadores por pessoa</h3>
                    <div className="mt-3 space-y-2">
                        {byUser.map((item) => (
                            <div key={item.user_id} className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 p-3">
                                <p className="text-sm font-medium text-slate-800">{item.name}</p>
                                <div className="flex flex-wrap items-center gap-2 text-xs">
                                    <span className={`rounded-full px-2.5 py-1 font-semibold ${colorMap[item.color]}`}>{item.presence_rate}%</span>
                                    <span>Presenças: {item.presence}</span>
                                    <span>Faltas: {item.absence}</span>
                                    <span>Não computado: {item.not_computed}</span>
                                    <span>Total: {item.total}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
