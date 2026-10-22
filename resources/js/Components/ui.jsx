import { useEffect, useRef } from 'react';

const buttonStyles = {
    primary: 'ui-button-primary',
    secondary: 'ui-button-secondary',
    danger: 'ui-button-danger',
};

export function Button({ as: Component = 'button', variant = 'primary', className = '', children, ...props }) {
    return <Component className={`ui-button ${buttonStyles[variant]} ${className}`} {...props}>{children}</Component>;
}

export function Field({ id, label, error, hint, children, className = '' }) {
    return (
        <div className={`ui-field ${className}`}>
            <label htmlFor={id} className="font-semibold">{label}</label>
            {children}
            {hint && <p className="text-xs font-normal text-slate-600">{hint}</p>}
            {error && <p id={`${id}-error`} className="ui-field-error" role="alert">{error}</p>}
        </div>
    );
}

export function TextInput({ error, id, ...props }) {
    return <input id={id} aria-invalid={Boolean(error)} aria-describedby={error ? `${id}-error` : undefined} {...props} />;
}

export function SelectInput({ error, id, children, ...props }) {
    return <select id={id} aria-invalid={Boolean(error)} aria-describedby={error ? `${id}-error` : undefined} {...props}>{children}</select>;
}

export function TextareaInput({ error, id, ...props }) {
    return <textarea id={id} aria-invalid={Boolean(error)} aria-describedby={error ? `${id}-error` : undefined} {...props} />;
}

export function Card({ as: Component = 'section', className = '', children, ...props }) {
    return <Component className={`ui-card ${className}`} {...props}>{children}</Component>;
}

export function FormSection({ title, description, children, className = '' }) {
    return (
        <section className={`ui-form-section ${className}`}>
            <div>
                <h2 className="text-lg font-bold text-coffee">{title}</h2>
                {description && <p className="mt-1 text-sm text-slate-600">{description}</p>}
            </div>
            {children}
        </section>
    );
}

export function FilterPanel({ children }) {
    return <section className="ui-filter" aria-label="Filtros">{children}</section>;
}

export function DataTable({ children, label }) {
    return <div className="ui-table-wrap"><div className="ui-table-scroll"><table className="w-full min-w-[36rem] text-left text-sm" aria-label={label}>{children}</table></div></div>;
}

export function Alert({ variant = 'success', children }) {
    return <div className={variant === 'danger' ? 'ui-alert-danger' : 'ui-alert-success'} role={variant === 'danger' ? 'alert' : 'status'}>{children}</div>;
}

export function Notification({ children }) {
    return <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="status">{children}</div>;
}

export function EmptyState({ children }) {
    return <div className="ui-empty-state text-sm text-slate-600">{children}</div>;
}

export function Loading({ label = 'Cargando' }) {
    return <span className="inline-flex items-center gap-2 text-sm" role="status"><span className="size-4 animate-spin rounded-full border-2 border-current border-r-transparent motion-reduce:animate-none" aria-hidden="true" />{label}</span>;
}

export function Pagination({ links = [] }) {
    if (links.length <= 3) {
        return null;
    }

    return <nav className="flex flex-wrap gap-2" aria-label="Paginación">{links.map((link, index) => link.url ? <a key={index} href={link.url} aria-current={link.active ? 'page' : undefined} className="ui-button ui-button-compact ui-button-secondary">{link.label}</a> : <span key={index} className="px-3 py-2 text-sm text-slate-500">{link.label}</span>)}</nav>;
}

export function ConfirmModal({ open, title, children, onClose, onConfirm, processing = false }) {
    const dialog = useRef(null);

    useEffect(() => {
        if (open && !dialog.current?.open) {
            dialog.current?.showModal();
        } else if (!open && dialog.current?.open) {
            dialog.current.close();
        }
    }, [open]);

    return (
        <dialog ref={dialog} className="ui-dialog" onClose={onClose} onCancel={onClose} aria-labelledby="confirm-title">
            <h2 id="confirm-title" className="text-lg font-bold text-coffee">{title}</h2>
            <p className="mt-3 text-sm leading-6 text-slate-700">{children}</p>
            <div className="mt-6 flex justify-end gap-2">
                <Button variant="secondary" onClick={onClose} autoFocus>Volver</Button>
                <Button variant="primary" onClick={onConfirm} disabled={processing}>{processing ? 'Guardando…' : 'Confirmar'}</Button>
            </div>
        </dialog>
    );
}
