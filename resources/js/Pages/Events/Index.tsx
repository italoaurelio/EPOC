import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { CalendarDays, CheckCircle2, Circle, Clock3, MapPin, Plus, Sparkles, UserCircle2, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

type Candidate = { id: number; user_id: number; status: string; user: { name: string } | null };
type Slot = { id: number; event_function_id: number; event_function: { id: number; name: string } | null; assignment: { user: { name: string } | null } | null; candidates: Candidate[] };
type Attendance = { id: number; status: string; user: { name: string } | null };
type Invitee = { id: number; user: { id: number; name: string } | null };
type EventItem = {
    id: number;
    group_id: number;
    name: string;
    type: 'missa' | 'reuniao';
    event_date: string;
    event_time: string;
    notes: string | null;
    liturgical_color: string | null;
    audience: 'all' | 'specific';
    group: { id: number; name: string };
    location?: { name?: string | null } | null;
    slots: Slot[];
    invitees: Invitee[];
    attendance_records: Attendance[];
};
type GroupMember = { group_id: number; user_id: number; name: string | null };
type GroupFunction = { id: number; group_id: number; name: string; is_initially_active: boolean };

type AssignmentMode = 'vacancy' | 'member' | 'ghost';
type SlotAssignmentDraft = { mode: AssignmentMode; user_id: number | null; ghost_name: string };

function liturgicalColorClass(color: string | null) {
    const key = (color ?? '').toLowerCase();
    if (key === 'branco') return 'bg-stone-100 text-stone-800 border-stone-200';
    if (key === 'vermelho') return 'bg-rose-100 text-rose-800 border-rose-200';
    if (key === 'verde') return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    if (key === 'roxo') return 'bg-violet-100 text-violet-800 border-violet-200';
    if (key === 'rosa') return 'bg-pink-100 text-pink-800 border-pink-200';
    if (key === 'preto') return 'bg-slate-200 text-slate-900 border-slate-300';
    return 'bg-amber-50 text-amber-800 border-amber-200';
}

function monthlyGrid(events: EventItem[]) {
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth();
    const days = new Date(y, m + 1, 0).getDate();

    return Array.from({ length: days }, (_, i) => {
        const day = `${y}-${String(m + 1).padStart(2, '0')}-${String(i + 1).padStart(2, '0')}`;
        return { day, events: events.filter((e) => e.event_date === day) };
    });
}

export default function EventsIndex({ events, groups, groupMembers, groupFunctions }: { events: EventItem[]; groups: Array<{ id: number; name: string }>; groupMembers: GroupMember[]; groupFunctions: GroupFunction[] }) {
    const initialGroupId = groups[0]?.id ?? 0;
    const activeFunctionIds = groupFunctions.filter((f) => f.group_id === initialGroupId && f.is_initially_active).map((f) => f.id);

    const form = useForm({
        group_id: initialGroupId,
        type: 'missa',
        audience: 'all',
        invitee_user_ids: [] as number[],
        name: '',
        event_date: '',
        event_time: '',
        notes: '',
        liturgical_color: 'verde',
        function_ids: activeFunctionIds,
        slot_assignments: {} as Record<number, SlotAssignmentDraft>,
        location: {
            name: '',
            street: '',
            number: '',
            district: '',
            city: '',
            state: '',
            complement: '',
        },
    });

    const updateForm = useForm({
        name: '',
        event_date: '',
        event_time: '',
        notes: '',
        liturgical_color: 'verde',
        audience: 'all',
        invitee_user_ids: [] as number[],
        function_ids: [] as number[],
        slot_assignments: {} as Record<number, SlotAssignmentDraft>,
        location: {
            name: '',
            street: '',
            number: '',
            district: '',
            city: '',
            state: '',
            complement: '',
        },
    });

    const functionForm = useForm({ name: '', is_initially_active: false });
    const candidateForm = useForm({ event_function_slot_id: 0 });
    const decideForm = useForm({ decision: 'aprovar', candidate_id: 0 });
    const manualAttendanceForm = useForm({ status: 'compareceu' });
    const envForm = useForm({ environment: 'altar', photo_path: '', observation: '' });
    const [selectedEventId, setSelectedEventId] = useState<number | null>(null);
    const [editingEventId, setEditingEventId] = useState<number | null>(null);

    const month = monthlyGrid(events);
    const today = new Date().toISOString().slice(0, 10);
    const selectableMembers = groupMembers.filter((m) => m.group_id === form.data.group_id);
    const selectableFunctions = groupFunctions.filter((f) => f.group_id === form.data.group_id);
    const selectedEvent = useMemo(() => events.find((event) => event.id === selectedEventId) ?? null, [events, selectedEventId]);

    const setMode = (functionId: number, mode: AssignmentMode) => {
        const existing = form.data.slot_assignments[functionId] ?? { mode: 'vacancy' as AssignmentMode, user_id: null, ghost_name: '' };
        form.setData('slot_assignments', {
            ...form.data.slot_assignments,
            [functionId]: { ...existing, mode },
        });
    };

    const startEdit = (event: EventItem) => {
        setEditingEventId(event.id);
        updateForm.setData({
            name: event.name,
            event_date: event.event_date,
            event_time: event.event_time,
            notes: event.notes ?? '',
            liturgical_color: event.liturgical_color ?? 'verde',
            audience: event.audience,
            invitee_user_ids: event.invitees.map((i) => i.user?.id).filter(Boolean) as number[],
            function_ids: event.slots.map((s) => s.event_function_id),
            slot_assignments: {},
            location: {
                name: '',
                street: '',
                number: '',
                district: '',
                city: '',
                state: '',
                complement: '',
            },
        });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800">Eventos</h2>}>
            <Head title="Eventos" />
            <div className="mx-auto max-w-6xl space-y-5 px-4 py-6">
                <motion.form
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(route('groups.events.store', form.data.group_id), { preserveScroll: true });
                    }}
                >
                    <div className="flex items-center justify-between gap-2">
                        <h3 className="flex items-center gap-2 text-sm font-semibold text-slate-800"><Sparkles className="h-4 w-4 text-amber-600" />Novo evento</h3>
                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">mobile first</span>
                    </div>

                    <div className="mt-4 grid gap-2 sm:grid-cols-2">
                        <select className="rounded-xl border-slate-200" value={form.data.group_id} onChange={(e) => {
                            const groupId = Number(e.target.value);
                            form.setData('group_id', groupId);
                            form.setData('function_ids', groupFunctions.filter((f) => f.group_id === groupId && f.is_initially_active).map((f) => f.id));
                            form.setData('slot_assignments', {});
                        }}>{groups.map((group) => <option key={group.id} value={group.id}>{group.name}</option>)}</select>
                        <select className="rounded-xl border-slate-200" value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}><option value="missa">Missa</option><option value="reuniao">Reunião</option></select>
                        <input className="rounded-xl border-slate-200" placeholder="Nome do evento" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                        <input type="date" className="rounded-xl border-slate-200" value={form.data.event_date} onChange={(e) => form.setData('event_date', e.target.value)} />
                        <input type="time" className="rounded-xl border-slate-200" value={form.data.event_time} onChange={(e) => form.setData('event_time', e.target.value)} />
                        <select className="rounded-xl border-slate-200" value={form.data.liturgical_color} onChange={(e) => form.setData('liturgical_color', e.target.value)}>
                            <option value="branco">branco</option><option value="vermelho">vermelho</option><option value="verde">verde</option><option value="roxo">roxo</option><option value="rosa">rosa</option><option value="preto">preto</option>
                        </select>
                        <input className="rounded-xl border-slate-200 sm:col-span-2" placeholder="Observações" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                    </div>

                    {form.data.type === 'missa' && (
                        <div className="mt-4 rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Funções e escala</p>
                            <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                {selectableFunctions.map((func) => {
                                    const checked = form.data.function_ids.includes(func.id);
                                    const draft = form.data.slot_assignments[func.id] ?? { mode: 'vacancy' as AssignmentMode, user_id: null, ghost_name: '' };

                                    return (
                                        <div key={func.id} className={`rounded-xl border p-2 transition ${checked ? 'border-amber-200 bg-white' : 'border-slate-200 bg-slate-50'}`}>
                                            <label className="flex items-center gap-2 text-sm text-slate-700">
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={(e) => {
                                                        if (e.target.checked) {
                                                            form.setData('function_ids', [...form.data.function_ids, func.id]);
                                                        } else {
                                                            form.setData('function_ids', form.data.function_ids.filter((id) => id !== func.id));
                                                            const next = { ...form.data.slot_assignments };
                                                            delete next[func.id];
                                                            form.setData('slot_assignments', next);
                                                        }
                                                    }}
                                                />
                                                <span className="font-medium">{func.name}</span>
                                            </label>

                                            {checked && (
                                                <div className="mt-2 space-y-2 rounded-lg bg-slate-50 p-2">
                                                    <select className="w-full rounded-lg border-slate-200 text-xs" value={draft.mode} onChange={(e) => setMode(func.id, e.target.value as AssignmentMode)}>
                                                        <option value="vacancy">Deixar vaga sem pessoa</option>
                                                        <option value="member">Escolher usuário do grupo</option>
                                                        <option value="ghost">Digitar nome (conta fantasma)</option>
                                                    </select>

                                                    {draft.mode === 'member' && (
                                                        <select
                                                            className="w-full rounded-lg border-slate-200 text-xs"
                                                            value={draft.user_id ?? ''}
                                                            onChange={(e) => {
                                                                form.setData('slot_assignments', {
                                                                    ...form.data.slot_assignments,
                                                                    [func.id]: { ...draft, user_id: e.target.value ? Number(e.target.value) : null },
                                                                });
                                                            }}
                                                        >
                                                            <option value="">Selecione membro</option>
                                                            {selectableMembers.map((member) => (
                                                                <option key={member.user_id} value={member.user_id}>{member.name ?? `Usuário ${member.user_id}`}</option>
                                                            ))}
                                                        </select>
                                                    )}

                                                    {draft.mode === 'ghost' && (
                                                        <input
                                                            className="w-full rounded-lg border-slate-200 text-xs"
                                                            placeholder="Nome da pessoa"
                                                            value={draft.ghost_name}
                                                            onChange={(e) => {
                                                                form.setData('slot_assignments', {
                                                                    ...form.data.slot_assignments,
                                                                    [func.id]: { ...draft, ghost_name: e.target.value },
                                                                });
                                                            }}
                                                        />
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="mt-3 flex flex-wrap gap-2">
                                <input className="rounded-lg border-slate-200 text-xs" placeholder="Nova função personalizada" value={functionForm.data.name} onChange={(e) => functionForm.setData('name', e.target.value)} />
                                <button type="button" className="rounded-lg bg-fuchsia-700 px-2.5 py-1 text-xs font-semibold text-white" onClick={() => functionForm.post(route('groups.functions.store', form.data.group_id), { preserveScroll: true })}>Adicionar função</button>
                            </div>
                        </div>
                    )}

                    {form.data.type === 'reuniao' && (
                        <div className="mt-4 rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Público da reunião</p>
                            <select className="mt-2 rounded-xl border-slate-200 text-sm" value={form.data.audience} onChange={(e) => form.setData('audience', e.target.value)}>
                                <option value="all">Todos do grupo</option>
                                <option value="specific">Convidados específicos</option>
                            </select>
                            {form.data.audience === 'specific' && (
                                <div className="mt-2 grid gap-1 sm:grid-cols-2">
                                    {selectableMembers.map((member) => (
                                        <label key={member.user_id} className="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600">
                                            <input
                                                type="checkbox"
                                                className="mr-1"
                                                checked={form.data.invitee_user_ids.includes(member.user_id)}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        form.setData('invitee_user_ids', [...form.data.invitee_user_ids, member.user_id]);
                                                    } else {
                                                        form.setData('invitee_user_ids', form.data.invitee_user_ids.filter((id) => id !== member.user_id));
                                                    }
                                                }}
                                            />
                                            {member.name ?? `Usuário ${member.user_id}`}
                                        </label>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {Object.keys(form.errors).length > 0 && <p className="mt-3 text-xs text-rose-600">Revise os campos obrigatórios e tente novamente.</p>}

                    <button className="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:scale-[1.01] hover:bg-slate-800"><Plus className="h-4 w-4" />Salvar evento</button>
                </motion.form>

                <section className="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                    <h3 className="flex items-center gap-2 text-sm font-semibold text-slate-700"><CalendarDays className="h-4 w-4 text-amber-600" />Calendário mensal</h3>
                    <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                        {month.map((cell) => {
                            const isToday = cell.day === today;
                            return (
                                <motion.div key={cell.day} whileHover={{ y: -2 }} className={`min-h-24 rounded-xl border p-2 ${isToday ? 'border-amber-300 bg-amber-50' : 'border-slate-100 bg-white'}`}>
                                    <p className={`text-xs font-semibold ${isToday ? 'text-amber-700' : 'text-slate-500'}`}>{cell.day.slice(-2)} {isToday && '• Hoje'}</p>
                                    <div className="mt-1 space-y-1">
                                        {cell.events.slice(0, 3).map((event) => (
                                            <button key={event.id} className={`block w-full truncate rounded-md border px-1.5 py-0.5 text-left text-[10px] transition hover:opacity-90 ${event.type === 'missa' ? liturgicalColorClass(event.liturgical_color) : 'border-slate-200 bg-slate-100 text-slate-700'}`} onClick={() => setSelectedEventId(event.id)}>{event.name}</button>
                                        ))}
                                        {cell.events.length === 0 && <p className="text-[10px] text-slate-300">sem eventos</p>}
                                    </div>
                                </motion.div>
                            );
                        })}
                    </div>
                    {events.length === 0 && <div className="mt-4 rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">Nenhum evento ainda. Crie uma missa ou reunião para começar.</div>}
                </section>

                <AnimatePresence>
                    {selectedEvent && (
                        <motion.section initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: 8 }} className="rounded-3xl border border-amber-200 bg-amber-50 p-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-sm font-semibold text-amber-800">Detalhes do evento</h3>
                                <div className="flex gap-2">
                                    <button className="rounded-md bg-white px-2 py-1 text-xs font-semibold text-amber-800" onClick={() => startEdit(selectedEvent)}>Editar</button>
                                    <button className="rounded-md bg-amber-700 px-2 py-1 text-xs font-semibold text-white" onClick={() => setSelectedEventId(null)}>Fechar</button>
                                </div>
                            </div>
                            <div className="mt-2 grid gap-2 text-sm text-amber-900 sm:grid-cols-2">
                                <p className="flex items-center gap-1"><Circle className="h-3.5 w-3.5" />{selectedEvent.name}</p>
                                <p className="flex items-center gap-1"><Clock3 className="h-3.5 w-3.5" />{selectedEvent.event_date} {selectedEvent.event_time}</p>
                                <p className="flex items-center gap-1"><Users className="h-3.5 w-3.5" />{selectedEvent.group.name}</p>
                                <p className="flex items-center gap-1"><MapPin className="h-3.5 w-3.5" />{selectedEvent.location?.name ?? 'Local a definir'}</p>
                                {selectedEvent.type === 'missa' && <p className={`inline-flex w-max rounded-full border px-2 py-0.5 text-xs font-semibold ${liturgicalColorClass(selectedEvent.liturgical_color)}`}>{selectedEvent.liturgical_color ?? 'missa'}</p>}
                                <p className="sm:col-span-2">{selectedEvent.notes ?? 'Sem observações'}</p>
                            </div>
                        </motion.section>
                    )}
                </AnimatePresence>

                <section className="space-y-3">
                    {events.map((event) => (
                        <motion.article key={event.id} whileHover={{ y: -2 }} className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100 transition">
                            <div className="flex items-center justify-between">
                                <h3 className="font-semibold text-slate-800">{event.name}</h3>
                                <span className={`rounded-full border px-2 py-0.5 text-xs font-semibold ${event.type === 'missa' ? liturgicalColorClass(event.liturgical_color) : 'border-slate-200 bg-slate-100 text-slate-700'}`}>{event.type}</span>
                            </div>
                            <p className="mt-1 text-xs text-slate-500">{event.group.name} • {event.event_date} {event.event_time}</p>
                            {event.type === 'reuniao' && (
                                <div className="mt-2 space-y-1 rounded-xl border border-slate-100 bg-slate-50 p-2">
                                    {event.attendance_records.map((record) => (
                                        <div key={record.id} className="flex items-center justify-between rounded bg-white px-2 py-1 text-xs">
                                            <span>{record.user?.name ?? 'Usuário'} • {record.status}</span>
                                            <div className="flex gap-1">
                                                <button className="rounded bg-emerald-600 px-1.5 py-0.5 text-white" onClick={() => { manualAttendanceForm.setData('status', 'compareceu'); manualAttendanceForm.patch(route('attendance.manual', record.id), { preserveScroll: true }); }}>Compareceu</button>
                                                <button className="rounded bg-rose-600 px-1.5 py-0.5 text-white" onClick={() => { manualAttendanceForm.setData('status', 'nao_compareceu'); manualAttendanceForm.patch(route('attendance.manual', record.id), { preserveScroll: true }); }}>Faltou</button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                            <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                {event.slots.map((slot) => (
                                    <div key={slot.id} className="rounded-xl border border-slate-100 bg-slate-50 p-2 text-xs">
                                        <p className="font-semibold text-slate-700">{slot.event_function?.name ?? 'Função'}</p>
                                        <p className="mt-1 flex items-center gap-1 text-slate-600"><UserCircle2 className="h-3.5 w-3.5" />{slot.assignment?.user?.name ?? 'Vaga aberta'}</p>
                                        {!slot.assignment && (
                                            <div className="mt-2 flex flex-wrap gap-1">
                                                <button className="rounded bg-emerald-700 px-1.5 py-0.5 text-[10px] font-semibold text-white" onClick={() => { candidateForm.setData('event_function_slot_id', slot.id); candidateForm.post(route('event-candidates.store'), { preserveScroll: true }); }}>Candidatar</button>
                                                {slot.candidates.filter((c) => c.status === 'pendente').map((candidate) => (
                                                    <div key={candidate.id} className="flex items-center gap-1 rounded bg-slate-200 px-1 py-0.5 text-[10px]">
                                                        <span>{candidate.user?.name ?? `#${candidate.user_id}`}</span>
                                                        <button className="rounded bg-emerald-600 px-1 py-0.5 text-white" onClick={() => { decideForm.setData({ decision: 'aprovar', candidate_id: candidate.id }); decideForm.post(route('event-slots.decide-candidate', slot.id), { preserveScroll: true }); }}>Aprovar</button>
                                                        <button className="rounded bg-rose-600 px-1 py-0.5 text-white" onClick={() => { decideForm.setData({ decision: 'rejeitar', candidate_id: candidate.id }); decideForm.post(route('event-slots.decide-candidate', slot.id), { preserveScroll: true }); }}>Rejeitar</button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                            {event.type === 'missa' && (
                                <div className="mt-2 rounded-xl border border-slate-100 bg-slate-50 p-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <button className="rounded bg-amber-600 px-2 py-1 text-[11px] font-semibold text-white" onClick={() => { envForm.setData('environment', 'turibulo'); envForm.post(route('events.environments.store', event.id), { preserveScroll: true }); }}>Enviar Turíbulo</button>
                                        <button className="rounded bg-sky-700 px-2 py-1 text-[11px] font-semibold text-white" onClick={() => { envForm.setData('environment', 'altar'); envForm.post(route('events.environments.store', event.id), { preserveScroll: true }); }}>Enviar Altar</button>
                                        <input className="rounded border-slate-200 text-[11px]" placeholder="foto.jpg" value={envForm.data.photo_path} onChange={(e) => envForm.setData('photo_path', e.target.value)} />
                                        <input className="rounded border-slate-200 text-[11px]" placeholder="Observação" value={envForm.data.observation} onChange={(e) => envForm.setData('observation', e.target.value)} />
                                    </div>
                                </div>
                            )}
                        </motion.article>
                    ))}
                </section>
            </div>

            {editingEventId && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
                    <form
                        className="w-full max-w-lg rounded-2xl bg-white p-4 shadow-xl"
                        onSubmit={(e) => {
                            e.preventDefault();
                            updateForm.patch(route('events.update', editingEventId), {
                                preserveScroll: true,
                                onSuccess: () => setEditingEventId(null),
                            });
                        }}
                    >
                        <h3 className="text-sm font-semibold text-slate-800">Editar evento</h3>
                        <div className="mt-3 grid gap-2 sm:grid-cols-2">
                            <input className="rounded-xl border-slate-200" value={updateForm.data.name} onChange={(e) => updateForm.setData('name', e.target.value)} />
                            <input type="date" className="rounded-xl border-slate-200" value={updateForm.data.event_date} onChange={(e) => updateForm.setData('event_date', e.target.value)} />
                            <input type="time" className="rounded-xl border-slate-200" value={updateForm.data.event_time} onChange={(e) => updateForm.setData('event_time', e.target.value)} />
                            <select className="rounded-xl border-slate-200" value={updateForm.data.liturgical_color} onChange={(e) => updateForm.setData('liturgical_color', e.target.value)}>
                                <option value="branco">branco</option><option value="vermelho">vermelho</option><option value="verde">verde</option><option value="roxo">roxo</option><option value="rosa">rosa</option><option value="preto">preto</option>
                            </select>
                            <input className="rounded-xl border-slate-200 sm:col-span-2" value={updateForm.data.notes} onChange={(e) => updateForm.setData('notes', e.target.value)} placeholder="Observações" />
                        </div>
                        <div className="mt-3 flex justify-end gap-2">
                            <button type="button" className="rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700" onClick={() => setEditingEventId(null)}>Cancelar</button>
                            <button className="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">Salvar</button>
                        </div>
                    </form>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
