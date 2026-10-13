# Fase Dental 12: agenda odontológica y gestión avanzada de citas

**Fecha:** 2026-10-12  
**Estado:** implementación y verificaciones automatizadas completas; revisión visual en navegador pendiente.

## Funcionalidades implementadas

- La agenda conserva las vistas diaria, semanal y mensual, con navegación por fechas. En las tarjetas semanales y mensuales se ven el horario completo, paciente, odontólogo, tratamiento previsto y estado; la vista diaria ya mostraba estos datos.
- Se añadió un filtro por texto de tratamiento previsto. Convive con los filtros existentes de fecha, paciente o ficha, odontólogo y estado. La consulta de la agenda usa carga anticipada de paciente y odontólogo.
- La vista diaria muestra los intervalos ocupados del odontólogo seleccionado y los huecos entre reservas. Para un odontólogo autenticado solo consulta sus propios intervalos. La consulta de ocupación es independiente de los filtros de texto y estado de la agenda.
- La leyenda utiliza los seis estados reales del sistema. Se ajustaron los colores de «En atención», «Cancelada» y «No asistió» para distinguirlos con texto y color.
- Los formularios explican que la duración se define con inicio y finalización; el detalle muestra los minutos. La cancelación pide confirmación antes de enviar y asocia el error de motivo a su campo.
- Se conservan las validaciones, permisos, transiciones, auditoría y exclusión de horarios del servicio existente. La reprogramación sigue invalidando los recordatorios pendientes obsoletos mediante el observador del modelo; los recordatorios se vuelven a calcular desde los datos actuales.

## Archivos modificados

`app/Http/Controllers/AppointmentController.php`, `app/Enums/AppointmentStatus.php`, `resources/views/appointments/index.blade.php`, `_form.blade.php`, `reschedule.blade.php`, `show.blade.php` y `tests/Feature/AppointmentManagementTest.php`.

Se conservaron los cambios sin commit de la Fase 11 y de `Tarea5.md`. No se cambiaron rutas, esquema, dependencias, facturación, historial clínico ni SIGEC. No se modificó Neon y no se ejecutaron migraciones.

## Reglas de disponibilidad

Solo ocupan horario las citas `scheduled`, `confirmed` e `in_treatment`. Se incluyen reservas que cruzan el límite del día y se excluyen las canceladas, completadas y marcadas como inasistencia. Los huecos se calculan únicamente **entre** reservas; no se presentan horas antes de la primera cita ni después de la última como disponibles. El horario laboral no está configurado en el sistema. Cada nueva cita o reprogramación se vuelve a validar en el backend contra traslapes y permisos: un hueco mostrado no garantiza que se pueda reservar.

## Pruebas ejecutadas

| Comprobación | Resultado |
| --- | --- |
| `php artisan test --compact tests/Feature/AppointmentManagementTest.php` | 17 pruebas, 110 aserciones aprobadas |
| `php artisan test --compact` | 276 pruebas, 1443 aserciones aprobadas |
| `vendor/bin/pint --dirty --format agent` y `vendor/bin/pint --test` | Aprobados |
| `git diff --check` | Aprobado |
| `npm run build` | Aprobado fuera del sandbox |

Las pruebas existentes cubren permisos, conflictos, estados, reprogramación, auditoría, consentimiento, cancelación, actualización y ausencia de duplicados en recordatorios. Las nuevas pruebas cubren el filtro de tratamiento, los intervalos ocupados, huecos, citas canceladas, límites de día y aislamiento por odontólogo. No se enviaron correos ni SMS reales.

## Problemas encontrados y pendientes

La primera compilación dentro del sandbox falló con `EPERM` al cargar el módulo nativo de Tailwind; el mismo comando pasó fuera del sandbox. Vite mostró solo un aviso sobre `fontaine`, dependencia opcional para optimizar fuentes; no se instaló.

No se hizo revisión visual en navegador. Queda comprobar la agenda y los formularios en móvil, tablet y escritorio, así como navegación por teclado y contraste. Si se necesita mostrar disponibilidad antes o después de las citas, hará falta definir jornadas, ausencias y excepciones laborales en un módulo configurable posterior.
