import { Head, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AdminLayout from '../../Components/AdminLayout';
import { Button, ConfirmModal, Field, FormSection, TextareaInput, TextInput } from '../../Components/ui';

const days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

export default function Edit({ settings, bookingHours, updateUrl, publicUrl }) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const heroInput = useRef(null);
    const logoInput = useRef(null);
    const form = useForm({
        _method: 'put',
        clinic_name: settings.clinic_name ?? '',
        description: settings.description ?? '',
        phone: settings.phone ?? '',
        email: settings.email ?? '',
        address: settings.address ?? '',
        opening_hours: settings.opening_hours ?? '',
        facebook_url: settings.facebook_url ?? '',
        instagram_url: settings.instagram_url ?? '',
        booking_hours: Object.fromEntries(days.map((_, index) => [index + 1, {
            start: bookingHours[index + 1]?.start ?? '',
            end: bookingHours[index + 1]?.end ?? '',
        }])),
        hero_image: null,
        logo: null,
    });

    const setHour = (day, field, value) => form.setData('booking_hours', {
        ...form.data.booking_hours,
        [day]: { ...form.data.booking_hours[day], [field]: value },
    });

    const save = () => form.post(updateUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            setConfirmOpen(false);
            form.reset('hero_image', 'logo');
            if (heroInput.current) heroInput.current.value = '';
            if (logoInput.current) logoInput.current.value = '';
        },
        onError: () => setConfirmOpen(false),
    });

    return <AdminLayout title="Sitio público" subtitle="Información institucional y horarios para solicitudes en línea" actions={<Button as="a" variant="secondary" href={publicUrl} target="_blank" rel="noopener noreferrer">Ver sitio público</Button>}>
        <Head title="Sitio público | Chimuelo" />
        <form className="grid max-w-5xl gap-5" onSubmit={(event) => { event.preventDefault(); setConfirmOpen(true); }}>
            <FormSection title="Identidad y contacto" description="Estos datos se muestran a los visitantes del consultorio.">
                <div className="grid gap-5 sm:grid-cols-2">
                    <Field id="clinic_name" label="Nombre comercial" error={form.errors.clinic_name} className="sm:col-span-2"><TextInput id="clinic_name" value={form.data.clinic_name} maxLength="255" onChange={(event) => form.setData('clinic_name', event.target.value)} error={form.errors.clinic_name} /></Field>
                    <Field id="description" label="Descripción" error={form.errors.description} className="sm:col-span-2"><TextareaInput id="description" rows="3" maxLength="2000" value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} error={form.errors.description} /></Field>
                    <Field id="phone" label="Teléfono" error={form.errors.phone}><TextInput id="phone" value={form.data.phone} maxLength="40" onChange={(event) => form.setData('phone', event.target.value)} error={form.errors.phone} /></Field>
                    <Field id="email" label="Correo público" error={form.errors.email}><TextInput id="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} /></Field>
                    <Field id="address" label="Dirección" error={form.errors.address}><TextareaInput id="address" rows="3" maxLength="1000" value={form.data.address} onChange={(event) => form.setData('address', event.target.value)} error={form.errors.address} /></Field>
                    <Field id="opening_hours" label="Horario de atención visible" error={form.errors.opening_hours}><TextareaInput id="opening_hours" rows="3" maxLength="1000" value={form.data.opening_hours} onChange={(event) => form.setData('opening_hours', event.target.value)} error={form.errors.opening_hours} /></Field>
                </div>
            </FormSection>

            <FormSection title="Horario para solicitudes" description="La última cita debe terminar a la hora de cierre. Deja ambas horas vacías para cerrar un día.">
                <div className="grid gap-3">
                    {days.map((dayName, index) => {
                        const day = index + 1;
                        return <div key={day} className="grid items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[8rem_1fr_1fr]">
                            <span className="font-semibold text-coffee">{dayName}</span>
                            {['start', 'end'].map((field) => {
                                const id = `hours-${day}-${field}`;
                                const error = form.errors[`booking_hours.${day}.${field}`];
                                return <Field key={id} id={id} label={field === 'start' ? 'Apertura' : 'Cierre'} error={error}>
                                    <TextInput id={id} type="time" value={form.data.booking_hours[day][field]} onChange={(event) => setHour(day, field, event.target.value)} error={error} />
                                </Field>;
                            })}
                        </div>;
                    })}
                </div>
            </FormSection>

            <FormSection title="Redes e imágenes" description="Las imágenes deben ser JPG, PNG o WebP.">
                <div className="grid gap-5 sm:grid-cols-2">
                    <Field id="facebook_url" label="Facebook" error={form.errors.facebook_url}><TextInput id="facebook_url" type="url" value={form.data.facebook_url} onChange={(event) => form.setData('facebook_url', event.target.value)} error={form.errors.facebook_url} /></Field>
                    <Field id="instagram_url" label="Instagram" error={form.errors.instagram_url}><TextInput id="instagram_url" type="url" value={form.data.instagram_url} onChange={(event) => form.setData('instagram_url', event.target.value)} error={form.errors.instagram_url} /></Field>
                    <Field id="hero_image" label="Imagen principal" error={form.errors.hero_image}><TextInput ref={heroInput} id="hero_image" type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => form.setData('hero_image', event.target.files[0] ?? null)} error={form.errors.hero_image} /></Field>
                    <Field id="logo" label="Logotipo" error={form.errors.logo}><TextInput ref={logoInput} id="logo" type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => form.setData('logo', event.target.files[0] ?? null)} error={form.errors.logo} /></Field>
                </div>
            </FormSection>

            <div className="flex flex-wrap items-center gap-3"><Button type="submit" disabled={form.processing}>Guardar configuración</Button>{form.processing && <span role="status" className="text-sm text-slate-600">Guardando cambios…</span>}{form.progress && <progress value={form.progress.percentage} max="100" aria-label="Progreso de carga" />}</div>
        </form>
        <ConfirmModal open={confirmOpen} title="Guardar configuración" onClose={() => setConfirmOpen(false)} onConfirm={save} processing={form.processing}>Los cambios se mostrarán en el sitio público después de guardar.</ConfirmModal>
    </AdminLayout>;
}
