import Badge from '@/Components/Escalada/Badge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { CalendarDays, CheckCircle2, Clock3, ImagePlus, ShieldCheck, Users2 } from 'lucide-react';
import { useMemo, useState } from 'react';

type Membership = { id: number; group_id: number; role: 'membro' | 'coordenador'; group: { id: number; name: string } };
type EventItem = { id: number; type: 'missa' | 'reuniao'; name: string; event_date: string; event_time: string; liturgical_color: string | null; group: { name: string } };
type Attendance = { id: number; event: { name: string; event_date: string; event_time: string } };
type PendingPhoto = { event_id: number; event_name: string; event_date: string };
type CoordinatorStats = {
    pending_memberships: number;
    pending_attendance: number;
    open_slots: number;
    upcoming_masses: number;
    upcoming_meetings: number;
    unconfirmed_people: number;
    pending_environment_photos: number;
    last_mass_summary?: { id: number; name: string; event_date: string; event_time: string } | null;
};

function liturgicalBadgeClass(color: string | null) {
    const key = (color ?? '').toLowerCase();
    if (key === 'branco') return 'bg-stone-100 text-stone-800';
    if (key === 'vermelho') return 'bg-rose-100 text-rose-800';
    if (key === 'verde') return 'bg-emerald-100 text-emerald-800';
    if (key === 'roxo') return 'bg-violet-100 text-violet-800';
    if (key === 'rosa') return 'bg-pink-100 text-pink-800';
    if (key === 'preto') return 'bg-slate-200 text-slate-900';
    return 'bg-purple-100 text-purple-800';
}

export default function Dashboard({ memberships, upcomingEvents, pendingAttendance, myPendingPhotos, coordinatorStats }: { memberships: Membership[]; upcomingEvents: EventItem[]; pendingAttendance: Attendance[]; myPendingPhotos: PendingPhoto[]; coordinatorStats: CoordinatorStats | null; }) {
    const attendanceForm = useForm<{ status: 'compareceu' | 'nao_compareceu' }>({ status: 'compareceu' });
    const substitutionForm = useForm<{ status: 'nao_compareceu' | 'nao_computado'; replacement_name?: string }>({ status: 'nao_compareceu' });
    const [pendingIndex, setPendingIndex] = useState(0);
    const [replacementName, setReplacementName] = useState('');
    const pendingItem = pendingAttendance[pendingIndex] ?? null;

    const nextEvent = useMemo(() => upcomingEvents[0] ?? null, [upcomingEvents]);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800">Escalada para o Céu</h2>}>
            <Head title="Painel" />
            <div className="mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6">
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <motion.div whileHover={{ y: -2 }}>
                        <Link href={route('groups.index')} className="block rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100 transition hover:shadow">
                            <p className="text-xs text-slate-500">Grupos</p><p className="mt-2 text-2xl font-bold text-slate-800">{memberships.length}</p>
                        </Link>
                    </motion.div>
                    <motion.div whileHover={{ y: -2 }}>
                        <Link href={route('events.index')} className="block rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100 transition hover:shadow">
                            <p className="text-xs text-slate-500">Eventos futuros</p><p className="mt-2 text-2xl font-bold text-slate-800">{upcomingEvents.length}</p>
                        </Link>
                    </motion.div>
                    <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100"><p className="text-xs text-slate-500">Pendências de presença</p><p className="mt-2 text-2xl font-bold text-slate-800">{pendingAttendance.length}</p></div>
                    <div className="rounded-2xl bg-gradient-to-br from-amber-50 to-rose-50 p-4 shadow-sm ring-1 ring-amber-100"><p className="text-xs text-slate-500">Perfil</p><p className="mt-2 text-base font-bold text-slate-800">{coordinatorStats ? 'Coordenação' : 'Membro'}</p></div>
                </div>

                {nextEvent && (
                    <section className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p className="text-xs font-semibold uppercase tracking-wide text-amber-700">Próximo evento</p>
                        <div className="mt-2 flex flex-wrap items-center gap-3 text-sm text-amber-900">
                            <span className="font-semibold">{nextEvent.name}</span>
                            <span className="inline-flex items-center gap-1"><Clock3 className="h-3.5 w-3.5" />{nextEvent.event_date} {nextEvent.event_time}</span>
                            {nextEvent.type === 'missa'
                                ? <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${liturgicalBadgeClass(nextEvent.liturgical_color)}`}>{nextEvent.liturgical_color ?? 'missa'}</span>
                                : <Badge tone="blue">reunião</Badge>}
                        </div>
                    </section>
                )}

                {coordinatorStats && (
                    <section className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                        <h3 className="flex items-center gap-2 text-sm font-semibold text-slate-700"><ShieldCheck className="h-4 w-4 text-amber-600" />Dashboard do coordenador</h3>
                        <div className="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div>Solicitações pendentes: <strong>{coordinatorStats.pending_memberships}</strong></div>
                            <div>Presenças pendentes: <strong>{coordinatorStats.pending_attendance}</strong></div>
                            <div>Vagas abertas: <strong>{coordinatorStats.open_slots}</strong></div>
                            <div>Pessoas sem confirmar: <strong>{coordinatorStats.unconfirmed_people}</strong></div>
                            <div>Fotos pendentes: <strong>{coordinatorStats.pending_environment_photos}</strong></div>
                            <div>Próximas missas: <strong>{coordinatorStats.upcoming_masses}</strong></div>
                            <div>Próximas reuniões: <strong>{coordinatorStats.upcoming_meetings}</strong></div>
                        </div>
                        {coordinatorStats.last_mass_summary && <div className="mt-3 rounded-xl border border-slate-100 p-3 text-xs text-slate-600">Última missa: <strong>{coordinatorStats.last_mass_summary.name}</strong></div>}
                    </section>
                )}

                <section className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                    <h3 className="flex items-center gap-2 text-sm font-semibold text-slate-700"><CalendarDays className="h-4 w-4 text-amber-600" />Calendário (lista mobile)</h3>
                    {upcomingEvents.length === 0 && <div className="mt-3 rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">Sem eventos próximos.</div>}
                    <div className="mt-3 space-y-3">
                        {upcomingEvents.map((event) => (
                            <motion.article key={event.id} whileHover={{ y: -2 }} className="rounded-xl border border-slate-100 p-3 transition">
                                <div className="flex items-center justify-between gap-2">
                                    <p className="font-medium text-slate-800">{event.name}</p>
                                    {event.type === 'missa'
                                        ? <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${liturgicalBadgeClass(event.liturgical_color)}`}>{event.liturgical_color ?? 'missa'}</span>
                                        : <Badge tone="blue">reunião</Badge>}
                                </div>
                                <p className="mt-1 text-xs text-slate-500">{event.group.name} • {event.event_date} {event.event_time}</p>
                            </motion.article>
                        ))}
                    </div>
                </section>

                <section className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                    <h3 className="flex items-center gap-2 text-sm font-semibold text-slate-700"><ImagePlus className="h-4 w-4 text-amber-600" />Pendências de foto/observação</h3>
                    <div className="mt-3 space-y-2">
                        {myPendingPhotos.length === 0 && <p className="rounded-xl border border-dashed border-slate-200 p-4 text-xs text-slate-500">Nenhuma pendência.</p>}
                        {myPendingPhotos.map((item) => (
                            <div key={item.event_id} className="rounded-xl border border-slate-100 p-3 text-xs text-slate-600">
                                {item.event_name} • {item.event_date}
                            </div>
                        ))}
                    </div>
                </section>
            </div>

            <AnimatePresence>
                {pendingItem && (
                    <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
                        <motion.div initial={{ y: 14, scale: 0.98 }} animate={{ y: 0, scale: 1 }} className="w-full max-w-md rounded-2xl bg-white p-4 shadow-xl">
                            <p className="text-sm font-semibold text-slate-800">Você compareceu?</p>
                            <p className="mt-1 text-xs text-slate-600">{pendingItem.event.name} • {pendingItem.event.event_date} {pendingItem.event.event_time}</p>

                            <div className="mt-3 space-y-2">
                                <button
                                    className="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white"
                                    onClick={() => {
                                        attendanceForm.setData('status', 'compareceu');
                                        attendanceForm.post(route('attendance.confirm', pendingItem.id), {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                setPendingIndex((prev) => prev + 1);
                                                setReplacementName('');
                                            },
                                        });
                                    }}
                                >
                                    <CheckCircle2 className="h-3.5 w-3.5" />Sim, compareci
                                </button>

                                <input className="w-full rounded-lg border-slate-200 text-xs" placeholder="Se faltou, quem foi no seu lugar? (opcional)" value={replacementName} onChange={(e) => setReplacementName(e.target.value)} />
                                <button
                                    className="w-full rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white"
                                    onClick={() => {
                                        substitutionForm.setData({ status: 'nao_compareceu', replacement_name: replacementName || undefined });
                                        substitutionForm.post(route('attendance.confirm', pendingItem.id), {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                setPendingIndex((prev) => prev + 1);
                                                setReplacementName('');
                                            },
                                        });
                                    }}
                                >
                                    Não compareci
                                </button>

                                <button
                                    className="w-full rounded-lg bg-slate-500 px-3 py-2 text-xs font-semibold text-white"
                                    onClick={() => {
                                        substitutionForm.setData({ status: 'nao_computado' });
                                        substitutionForm.post(route('attendance.confirm', pendingItem.id), {
                                            preserveScroll: true,
                                            onSuccess: () => setPendingIndex((prev) => prev + 1),
                                        });
                                    }}
                                >
                                    Não computar
                                </button>
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </AuthenticatedLayout>
    );
}
