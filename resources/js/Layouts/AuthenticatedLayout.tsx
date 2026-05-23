import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { BarChart3, CalendarDays, CheckCircle2, Home, LogOut, UserCircle2, Users2, XCircle } from 'lucide-react';
import { PropsWithChildren, ReactNode } from 'react';

type NavItem = {
    key: string;
    label: string;
    href: string;
    icon: ReactNode;
    active: boolean;
};

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const page = usePage();
    const user = page.props.auth.user as { name: string; email: string | null };
    const flash = (page.props as { flash?: { success?: string; error?: string } }).flash ?? {};

    const navItems: NavItem[] = [
        { key: 'dashboard', label: 'Painel', href: route('dashboard'), icon: <Home className="h-5 w-5" />, active: route().current('dashboard') },
        { key: 'groups', label: 'Grupos', href: route('groups.index'), icon: <Users2 className="h-5 w-5" />, active: route().current('groups.index') },
        { key: 'events', label: 'Eventos', href: route('events.index'), icon: <CalendarDays className="h-5 w-5" />, active: route().current('events.index') },
        { key: 'insights', label: 'Insights', href: route('insights.index'), icon: <BarChart3 className="h-5 w-5" />, active: route().current('insights.index') },
    ];

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <aside className="group fixed left-4 top-4 z-40 hidden h-[calc(100vh-2rem)] w-20 overflow-hidden rounded-[28px] border border-slate-200 bg-white/95 shadow-xl backdrop-blur transition-[width] duration-500 ease-out hover:w-64 lg:flex lg:flex-col">
                <div className="flex h-16 items-center justify-center px-4 py-5">
                    <ApplicationLogo className="h-8 w-8 shrink-0 fill-current text-amber-600" />
                    <span className="ml-0 max-w-0 overflow-hidden whitespace-nowrap text-sm font-semibold text-slate-700 opacity-0 transition-[max-width,opacity,margin] duration-200 ease-out group-hover:ml-3 group-hover:max-w-[180px] group-hover:opacity-100">Escalada para o Céu</span>
                </div>

                <div className="mt-2 flex flex-1 flex-col gap-2 px-3">
                    {navItems.map((item) => (
                        <Link
                            key={item.key}
                            href={item.href}
                            className={`flex items-center justify-center rounded-full px-3 py-2.5 text-sm font-medium ${item.active ? 'bg-amber-100 text-amber-800' : 'text-slate-600 hover:bg-slate-100'}`}
                        >
                            <span className="shrink-0">{item.icon}</span>
                            <span className="ml-0 max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-[max-width,opacity,margin] duration-200 ease-out group-hover:ml-3 group-hover:max-w-[120px] group-hover:opacity-100">{item.label}</span>
                        </Link>
                    ))}
                </div>

                <div className="border-t border-slate-100 p-3">
                    <Link href={route('profile.edit')} className="flex items-center justify-center rounded-full px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">
                        <UserCircle2 className="h-5 w-5 shrink-0" />
                        <div className="ml-0 max-w-0 min-w-0 overflow-hidden opacity-0 transition-[max-width,opacity,margin] duration-200 ease-out group-hover:ml-3 group-hover:max-w-[150px] group-hover:opacity-100">
                            <p className="truncate text-xs font-semibold text-slate-800">{user.name}</p>
                            <p className="truncate text-[11px] text-slate-500">{user.email ?? 'sem email'}</p>
                        </div>
                    </Link>
                    <Link href={route('logout')} method="post" as="button" className="mt-2 flex w-full items-center justify-center rounded-full px-3 py-2 text-sm text-rose-700 hover:bg-rose-50">
                        <LogOut className="h-5 w-5 shrink-0" />
                        <span className="ml-0 max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-[max-width,opacity,margin] duration-200 ease-out group-hover:ml-3 group-hover:max-w-[80px] group-hover:opacity-100">Sair</span>
                    </Link>
                </div>
            </aside>

            <nav className="fixed inset-x-0 bottom-3 z-40 px-3 lg:hidden">
                <div className="mx-auto flex max-w-md items-center justify-around rounded-full border border-slate-200 bg-white/95 px-2 py-2 shadow-xl backdrop-blur">
                    {navItems.map((item) => (
                        <Link
                            key={item.key}
                            href={item.href}
                            className={`flex min-w-[64px] flex-col items-center rounded-full px-3 py-1.5 text-[11px] font-medium transition ${item.active ? 'bg-amber-100 text-amber-800' : 'text-slate-600'}`}
                        >
                            {item.icon}
                            <span className="mt-0.5">{item.label}</span>
                        </Link>
                    ))}
                    <Link href={route('profile.edit')} className="flex min-w-[64px] flex-col items-center rounded-full px-3 py-1.5 text-[11px] font-medium text-slate-600 transition">
                        <UserCircle2 className="h-5 w-5" />
                        <span className="mt-0.5">Perfil</span>
                    </Link>
                </div>
            </nav>

            <div className="pb-24 lg:pb-6 lg:pl-28 lg:pr-4">
                {header && (
                    <header className="px-4 pt-5 lg:px-6">
                        <div className="rounded-2xl bg-white px-4 py-4 shadow-sm ring-1 ring-slate-100">{header}</div>
                    </header>
                )}
                <main>{children}</main>
            </div>

            <div className="pointer-events-none fixed right-4 top-4 z-[60] w-full max-w-sm space-y-2">
                <AnimatePresence>
                    {flash.success && (
                        <motion.div initial={{ opacity: 0, y: -12 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }} className="pointer-events-auto rounded-xl border border-emerald-200 bg-emerald-50 p-3 shadow-lg">
                            <div className="flex items-start gap-2">
                                <CheckCircle2 className="mt-0.5 h-4 w-4 text-emerald-700" />
                                <p className="text-sm font-medium text-emerald-900">{flash.success}</p>
                            </div>
                        </motion.div>
                    )}
                    {flash.error && (
                        <motion.div initial={{ opacity: 0, y: -12 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }} className="pointer-events-auto rounded-xl border border-rose-200 bg-rose-50 p-3 shadow-lg">
                            <div className="flex items-start gap-2">
                                <XCircle className="mt-0.5 h-4 w-4 text-rose-700" />
                                <p className="text-sm font-medium text-rose-900">{flash.error}</p>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>
        </div>
    );
}
