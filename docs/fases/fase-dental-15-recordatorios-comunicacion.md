# Fase Dental 15 — Recordatorios y comunicación

## Diagnóstico

La migración 2026_10_12_040309_create_appointment_reminder_tracking_tables consta como aplicada en la base configurada (lote 5; consulta de solo lectura). Ya existían AppointmentReminderScheduler, el comando appointments:dispatch-reminders, la tarea cada minuto con protección contra solapamiento, un trabajo de cola, intentos, estados, consentimiento, reintentos y un resumen en el dashboard. Las citas canceladas o reprogramadas quedan obsoletas y el trabajo vuelve a comprobar la cita y el consentimiento antes de enviar.

La configuración actual usa correo log, cola sync, caché file, recordatorios de correo desactivados y SMS desactivado sin proveedor. No hay entrega real habilitada. No se inspeccionaron ni revelaron credenciales.

## Mejoras implementadas

- Se exige consentimiento vigente, preferencia activa, contacto válido y paciente activo tanto al programar como al procesar. Una preferencia activa sin fecha de consentimiento ya no autoriza el envío; no se modificaron preferencias existentes.
- Se mantienen los reintentos transitorios, con máximo tres intentos y espera de 15/60 minutos. Los errores de lógica y las respuestas SMTP permanentes (5xx) terminan sin otro reintento. El registro conserva un tipo genérico, sin respuesta del proveedor.
- Nuevo centro administrativo de solo lectura en Recordatorios para administración y recepción. Muestra cita, paciente, canal, fecha programada, estado, intentos, error resumido y fecha de envío; filtra por fechas locales, estado y canal, y pagina resultados. Se enlaza desde navegación y dashboard.
- Los estados tienen etiquetas y colores legibles. Con transporte log o array, la interfaz avisa que «Enviado» significa aceptación local, no entrega al paciente.

## Archivos modificados

app/Models/Patient.php, app/Services/AppointmentReminderScheduler.php, app/Jobs/SendAppointmentReminder.php, app/Enums/AppointmentReminderStatus.php, app/Http/Controllers/AppointmentReminderController.php, app/Providers/AppServiceProvider.php, routes/web.php, resources/views/appointments/reminders.blade.php, resources/views/layouts/admin.blade.php, resources/views/dashboard/dental.blade.php, resources/css/app.css, tests/Feature/AppointmentReminderTest.php y tests/Feature/DentalDashboardTest.php.

## Configuración operativa

**Desarrollo:** mantener MAIL_MAILER=log o un transporte de prueba, QUEUE_CONNECTION=sync y SMS desactivado. Activar APPOINTMENT_EMAIL_REMINDERS_ENABLED=true solo en un entorno aislado para comprobar la programación. El comando existente se ejecuta mediante php artisan schedule:run cada minuto.

**Despliegue:** preparar primero un SMTP autorizado con MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_SCHEME (TLS/SMTPS según proveedor), MAIL_FROM_ADDRESS y MAIL_FROM_NAME. Guardar secretos fuera del repositorio. Configurar una cola persistente, por ejemplo QUEUE_CONNECTION=database, y mantener un worker supervisado con php artisan queue:work database --sleep=3 --tries=1 --timeout=30; retry_after debe superar el timeout (la configuración actual es 90 segundos). Ejecutar el scheduler una sola vez por minuto y usar caché compartida para los bloqueos si hay varios servidores. Verificar recepción con un buzón de prueba antes de activar APPOINTMENT_EMAIL_REMINDERS_ENABLED=true para pacientes. No se iniciaron procesos permanentes.

**SMS:** permanece desactivado. Un proveedor futuro necesitaría implementar SmsSender, proteger sus credenciales, comprobar disponibilidad, clasificar fallos permanentes/transitorios y confirmar entregas reales. No se configuró ni simuló un proveedor.

## Seguridad y resultados

La ruta exige sesión, cuenta activa y permiso exclusivo de administración o recepción. La vista omite datos clínicos y respuestas crudas del proveedor. El envío conserva estados controlados y una restricción única por cita, paciente, canal y horario. Las pruebas usan transporte array y dobles; no enviaron comunicaciones reales. No se modificaron SIGEC, facturación, historial clínico, Neon, migraciones, dependencias ni credenciales.

Verificaciones: php artisan test --compact pasó con 289 pruebas y 1544 aserciones; vendor/bin/pint --test y git diff --check pasaron. npm run build pasó fuera del sandbox; dentro falló por el bloqueo EPERM de Windows al cargar el componente nativo de Tailwind. La migración se comprobó mediante una consulta de solo lectura y la ruta administrativa aparece en route:list.

## Límites y pendientes

La entrega externa es de tipo «al menos una vez»: si el proveedor acepta un mensaje y el proceso cae antes de registrar el estado enviado, un reintento podría duplicarlo. Garantizar exactamente una entrega requiere idempotencia del proveedor. Los pacientes con preferencia activa pero sin marca de consentimiento vigente deberán otorgarlo de nuevo mediante el flujo existente. Queda pendiente validar SMTP real y poner en marcha el scheduler y worker supervisados en el entorno autorizado.
