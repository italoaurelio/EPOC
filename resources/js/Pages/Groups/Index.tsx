import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { CheckCircle2, Link2, ShieldCheck, UserMinus2, UserPlus2, Users2 } from 'lucide-react';

type Membership = { id: number; user_name: string | null; user_email: string | null; role: 'membro' | 'coordenador'; status: 'pendente' | 'aprovado' | 'rejeitado' };
type GroupItem = { id: number; name: string; description: string | null; role: 'membro' | 'coordenador' | 'admin_sistema'; memberships: Membership[] };

export default function GroupsIndex({ groups }: { groups: GroupItem[] }) {
    const createForm = useForm({ name: '', description: '' });
    const inviteForm = useForm({ role: 'membro', requires_approval: true });
    const membershipForm = useForm({ action: 'approve', role: 'membro' });

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800">Grupos</h2>}>
            <Head title="Grupos" />
            <div className="mx-auto max-w-5xl space-y-5 px-4 py-6">
                <motion.form
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100"
                    onSubmit={(e) => { e.preventDefault(); createForm.post(route('groups.store'), { preserveScroll: true, onSuccess: () => createForm.reset('name', 'description') }); }}
                >
                    <h3 className="flex items-center gap-2 text-sm font-semibold text-slate-800"><Users2 className="h-4 w-4 text-amber-600" />Criar grupo</h3>
                    <div className="mt-3 grid gap-2 sm:grid-cols-2">
                        <input className="rounded-xl border-slate-200" placeholder="Nome" value={createForm.data.name} onChange={(e) => createForm.setData('name', e.target.value)} />
                        <input className="rounded-xl border-slate-200" placeholder="Descrição" value={createForm.data.description} onChange={(e) => createForm.setData('description', e.target.value)} />
                    </div>
                    {Object.keys(createForm.errors).length > 0 && <p className="mt-2 text-xs text-rose-600">Verifique os campos do grupo.</p>}
                    <button className="mt-3 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Salvar</button>
                </motion.form>

                {groups.length === 0 && <div className="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Nenhum grupo ainda. Crie o primeiro para começar.</div>}

                <div className="space-y-3">
                    {groups.map((group) => (
                        <motion.article key={group.id} whileHover={{ y: -2 }} className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h3 className="font-semibold text-slate-800">{group.name}</h3>
                                    <p className="mt-0.5 text-xs text-slate-500">{group.description ?? 'Sem descrição'}</p>
                                </div>
                                <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{group.role}</span>
                            </div>

                            {(group.role === 'coordenador' || group.role === 'admin_sistema') && (
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    <form className="rounded-xl border border-slate-100 bg-slate-50 p-3" onSubmit={(e) => { e.preventDefault(); inviteForm.post(route('groups.invites.store', group.id), { preserveScroll: true }); }}>
                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Convites</p>
                                        <div className="mt-2 flex flex-wrap items-center gap-2">
                                            <select className="rounded-lg border-slate-200 text-xs" value={inviteForm.data.role} onChange={(e) => inviteForm.setData('role', e.target.value)}>
                                                <option value="membro">Convite membro</option>
                                                <option value="coordenador">Convite coordenador</option>
                                            </select>
                                            <label className="text-xs"><input type="checkbox" className="mr-1" checked={inviteForm.data.requires_approval} onChange={(e) => inviteForm.setData('requires_approval', e.target.checked)} />Exigir aprovação</label>
                                        </div>
                                        <button className="mt-2 inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white"><Link2 className="h-3.5 w-3.5" />Gerar convite</button>
                                    </form>

                                    <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Membros</p>
                                        <div className="mt-2 space-y-2">
                                            {group.memberships.map((membership) => (
                                                <div key={membership.id} className="rounded-lg border border-slate-200 bg-white p-2">
                                                    <div className="flex flex-wrap items-center justify-between gap-1">
                                                        <p className="text-xs font-medium text-slate-800">{membership.user_name ?? 'Sem nome'} <span className="text-slate-500">({membership.user_email ?? 'sem email'})</span></p>
                                                        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{membership.role} • {membership.status}</span>
                                                    </div>
                                                    <div className="mt-2 flex flex-wrap gap-1">
                                                        {membership.status === 'pendente' && (
                                                            <>
                                                                <button className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2 py-1 text-[11px] font-semibold text-white" onClick={() => { membershipForm.setData({ action: 'approve', role: membership.role }); membershipForm.patch(route('groups.memberships.update', [group.id, membership.id]), { preserveScroll: true }); }}><CheckCircle2 className="h-3 w-3" />Aprovar</button>
                                                                <button className="inline-flex items-center gap-1 rounded-md bg-rose-600 px-2 py-1 text-[11px] font-semibold text-white" onClick={() => { membershipForm.setData({ action: 'reject', role: membership.role }); membershipForm.patch(route('groups.memberships.update', [group.id, membership.id]), { preserveScroll: true }); }}><UserMinus2 className="h-3 w-3" />Rejeitar</button>
                                                            </>
                                                        )}
                                                        <button className="inline-flex items-center gap-1 rounded-md bg-sky-700 px-2 py-1 text-[11px] font-semibold text-white" onClick={() => { membershipForm.setData({ action: 'role', role: membership.role === 'membro' ? 'coordenador' : 'membro' }); membershipForm.patch(route('groups.memberships.update', [group.id, membership.id]), { preserveScroll: true }); }}><ShieldCheck className="h-3 w-3" />Trocar role</button>
                                                        <button className="inline-flex items-center gap-1 rounded-md bg-slate-700 px-2 py-1 text-[11px] font-semibold text-white" onClick={() => { membershipForm.setData({ action: 'remove', role: membership.role }); membershipForm.patch(route('groups.memberships.update', [group.id, membership.id]), { preserveScroll: true }); }}><UserPlus2 className="h-3 w-3" />Remover</button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </motion.article>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
