import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Alert, Notification } from './ui';

function NavigationLink({ item, active, onClick }) {
    const className = `flex min-h-11 items-center rounded-xl px-4 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-brand-300 ${active ? 'bg-brand-600 text-white' : 'text-slate-200 hover:bg-white/10 hover:text-white'}`;

    if (item.inertia) {
        return <Link href={item.href} className={className} aria-current={active ? 'page' : undefined} onClick={onClick}>{item.label}</Link>;
    }

    return <a href={item.href} className={className} aria-current={active ? 'page' : undefined} onClick={onClick}>{item.label}</a>;
}

export function Sidebar({ open, onClose, desktop, navigation, app, routes }) {
    const currentPath = usePage().url.split('?')[0];
    const sections = [...new Set(navigation.map((item) => item.section))];

    return (
        <>
            {open && !desktop && <button type="button" className="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" onClick={onClose} aria-label="Cerrar menú" />}
            <aside id="inertia-sidebar" inert={!desktop && !open} aria-hidden={!desktop && !open} className={`fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-espresso text-white shadow-xl transition-transform lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`} aria-label="Navegación principal">
                <div className="flex h-20 items-center justify-between border-b border-white/10 px-5">
                    <Link href={routes.dashboard} className="flex items-center gap-3 font-bold" onClick={onClose}>
                        <img src={app.logo} alt="" className="size-11 rounded-xl object-contain" />
                        <span><span className="block text-base">{app.name}</span><span className="block text-xs font-semibold text-brand-200">Gestión odontológica</span></span>
                    </Link>
                    <button type="button" className="rounded-lg px-3 py-2 text-sm hover:bg-white/10 lg:hidden" onClick={onClose} aria-label="Cerrar navegación">Cerrar</button>
                </div>
                <nav className="flex-1 overflow-y-auto px-4 py-5" aria-label="Secciones administrativas">
                    {sections.map((section) => <div className="mb-6" key={section}>
                        <h2 className="px-3 text-xs font-bold uppercase tracking-widest text-brand-200">{section}</h2>
                        <div className="mt-2 grid gap-1">{navigation.filter((item) => item.section === section).map((item) => <NavigationLink key={item.href} item={item} active={new URL(item.href).pathname === currentPath} onClick={onClose} />)}</div>
                    </div>)}
                </nav>
                <p className="border-t border-white/10 px-5 py-4 text-xs text-brand-200">Sistema de gestión dental</p>
            </aside>
        </>
    );
}

export function Header({ title, onMenuOpen, auth, csrfToken, app, routes }) {
    return <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div className="flex min-h-20 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <div className="flex min-w-0 items-center gap-3">
                <button type="button" onClick={onMenuOpen} aria-controls="inertia-sidebar" aria-label="Abrir navegación" className="grid size-11 shrink-0 place-items-center rounded-xl border border-brand-200 text-brand-900 lg:hidden">☰</button>
                <div className="min-w-0"><p className="text-xs font-bold uppercase tracking-widest text-brand-700">Espacio clínico</p><h1 className="truncate text-xl font-bold text-coffee">{title}</h1></div>
            </div>
            <div className="flex items-center gap-3">
                <div className="hidden text-right sm:block"><p className="max-w-48 truncate text-sm font-semibold">{auth?.name}</p><p className="text-xs text-brand-700">{auth?.role}</p></div>
                <img src={app.logo} alt="" className="size-9 rounded-full object-contain ring-1 ring-brand-200" />
                <form method="post" action={routes.logout}><input type="hidden" name="_token" value={csrfToken} /><button type="submit" className="ui-button ui-button-secondary"><span className="hidden sm:inline">Cerrar sesión</span><span className="sm:hidden">Salir</span></button></form>
            </div>
        </div>
    </header>;
}

export function Breadcrumbs({ title, routes }) {
    if (title === 'Dashboard') {
        return null;
    }

    return <nav className="text-xs font-semibold text-brand-700" aria-label="Ruta de navegación"><Link href={routes.dashboard}>Inicio</Link><span className="px-2 text-slate-400" aria-hidden="true">/</span><span aria-current="page">{title}</span></nav>;
}

export default function AdminLayout({ title, subtitle, actions, children }) {
    const { app, auth, navigation, routes, csrfToken, flash, errors } = usePage().props;
    const [open, setOpen] = useState(false);
    const [desktop, setDesktop] = useState(() => window.matchMedia('(min-width: 64rem)').matches);

    useEffect(() => {
        const media = window.matchMedia('(min-width: 64rem)');
        const update = () => setDesktop(media.matches);
        media.addEventListener('change', update);
        return () => media.removeEventListener('change', update);
    }, []);

    useEffect(() => {
        const closeOnEscape = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };
        document.addEventListener('keydown', closeOnEscape);
        return () => document.removeEventListener('keydown', closeOnEscape);
    }, []);

    return <div className="min-h-screen">
        <a href="#main-content" className="sr-only fixed left-4 top-4 z-[60] rounded-xl bg-white px-4 py-3 font-semibold text-brand-900 shadow-lg focus:not-sr-only">Saltar al contenido</a>
        <Sidebar open={open} onClose={() => setOpen(false)} desktop={desktop} navigation={navigation} app={app} routes={routes} />
        <div className="min-h-screen lg:pl-72">
            <Header title={title} onMenuOpen={() => setOpen(true)} auth={auth} csrfToken={csrfToken} app={app} routes={routes} />
            <main id="main-content" tabIndex="-1" className="mx-auto flex w-full max-w-[100rem] flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                <div className="flex flex-wrap items-end justify-between gap-3"><div><Breadcrumbs title={title} routes={routes} />{subtitle && <p className="mt-2 text-sm text-slate-600">{subtitle}</p>}</div>{actions}</div>
                {flash?.status && <Alert>{flash.status}</Alert>}
                {flash?.warning && <Notification>{flash.warning}</Notification>}
                {errors && Object.keys(errors).length > 0 && <Alert variant="danger">Revisa los datos ingresados.</Alert>}
                {children}
            </main>
        </div>
    </div>;
}
