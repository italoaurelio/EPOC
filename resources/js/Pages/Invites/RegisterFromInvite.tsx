import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

type GhostCandidate = { id: number; name: string };

export default function RegisterFromInvite({ invite, ghostCandidates }: { invite: { token: string; group_name: string; role: string; requires_approval: boolean }; ghostCandidates: GhostCandidate[] }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '', claim_ghost_user_id: '' });

    return (
        <GuestLayout>
            <Head title="Cadastro por convite" />
            <div className="mb-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-700">
                <p>Grupo: <strong>{invite.group_name}</strong></p>
                <p>Perfil: <strong>{invite.role}</strong></p>
                <p>{invite.requires_approval ? 'Entrada com aprovacao do coordenador.' : 'Entrada imediata no grupo.'}</p>
            </div>
            <form onSubmit={(e) => { e.preventDefault(); form.post(route('invites.register', invite.token)); }}>
                <div>
                    <InputLabel htmlFor="name" value="Nome" />
                    <TextInput id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} className="mt-1 block w-full" required />
                    <InputError message={form.errors.name} className="mt-2" />
                </div>
                <div className="mt-4">
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} className="mt-1 block w-full" required />
                    <InputError message={form.errors.email} className="mt-2" />
                </div>
                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Senha" />
                    <TextInput id="password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} className="mt-1 block w-full" required />
                    <InputError message={form.errors.password} className="mt-2" />
                </div>
                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="Confirmar senha" />
                    <TextInput id="password_confirmation" type="password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} className="mt-1 block w-full" required />
                </div>
                {ghostCandidates.length > 0 && (
                    <div className="mt-4">
                        <InputLabel htmlFor="claim_ghost_user_id" value="Você já serviu neste grupo com outro cadastro (conta fantasma)?" />
                        <select id="claim_ghost_user_id" className="mt-1 block w-full rounded-md border-slate-300" value={form.data.claim_ghost_user_id} onChange={(e) => form.setData('claim_ghost_user_id', e.target.value)}>
                            <option value="">Não, sou novo(a)</option>
                            {ghostCandidates.map((candidate) => (
                                <option key={candidate.id} value={candidate.id}>{candidate.name}</option>
                            ))}
                        </select>
                    </div>
                )}
                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton disabled={form.processing}>Criar conta e entrar</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
