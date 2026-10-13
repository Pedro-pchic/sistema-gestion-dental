# Fase Dental 08: auditoría final

**Fecha:** 2026-10-12

**Estado:** revisión automatizada aprobada; despliegue pendiente de una migración.

## Resumen ejecutivo

La suite actual pasa completa y el build de Vite termina correctamente. La conexión PostgreSQL revisada tiene 15 migraciones aplicadas y una pendiente: las tablas de seguimiento de recordatorios no existen todavía. Esa diferencia de esquema puede romper el dashboard administrativo y las operaciones de reprogramación o cambio de estado de citas. La migración se identificó, pero no se ejecutó.

Se corrigió una etiqueta accesible en la selección de procedimientos al crear una factura y se preparó una prueba de pagos concurrentes para una base PostgreSQL local aislada. La prueba concurrente no se validó contra PostgreSQL porque el proyecto no tiene una base de pruebas PostgreSQL configurada; la configuración PHPUnit disponible usa SQLite en memoria.

## Entorno y alcance

- Boost reporta PHP 8.4.25, Laravel 13.33.0 y PostgreSQL.
- Se revisaron autenticación y roles, pacientes y odontólogos, citas, tratamientos y planes, historial clínico, facturación, pagos, recordatorios, dashboard, migraciones y vistas dentales principales.
- Se usaron consultas de solo lectura para inspeccionar esquema e historial de migraciones. No se ejecutaron migraciones, seeders ni escrituras de negocio en PostgreSQL.
- No se modificaron `.env`, dependencias ni el proyecto SIGEC. El cambio previo en `Tarea1.md` se conservó intacto.

## Hallazgos

### Alto: falta la migración de seguimiento de recordatorios

`php artisan migrate:status` informa como pendiente `2026_10_12_040309_create_appointment_reminder_tracking_tables.php`. La inspección del esquema PostgreSQL confirmó que no existen `appointment_reminders` ni `appointment_reminder_attempts`.

El dashboard consulta `appointment_reminders` para mostrar los contadores a administradores y recepcionistas. Además, el listener de `Appointment` consulta esa tabla al cambiar paciente, fecha o estado. Esos flujos no son compatibles con el esquema actual y pueden terminar en una excepción SQL.

**Acción:** no se aplicó la migración, de acuerdo con la instrucción de no ejecutarla sin autorización expresa. La versión que depende de esas tablas no está lista para desplegarse sobre esa base hasta aplicar y verificar la migración.

### Medio: el esquema no permite repetir un tratamiento en la misma cita

La base tiene el índice único `patient_treatments_appointment_id_treatment_id_unique`. También mantiene una restricción única por paciente, odontólogo, tratamiento y hora. La prueba existente `test_duplicate_procedure_for_the_same_appointment_is_rejected` confirma que la aplicación rechaza la segunda entrada del mismo tratamiento en una cita.

Esto impide guardar, por ejemplo, dos procedimientos del mismo tipo durante una visita si la regla clínica distingue piezas dentales. La regla de negocio no especifica si ese caso debe permitirse; se conservaron las restricciones y la prueba hasta recibir esa definición.

### Medio: concurrencia financiera preparada, sin validación PostgreSQL

El registro de pagos bloquea la factura con `lockForUpdate`, calcula los importes en centavos, comprueba el saldo y usa una clave de idempotencia única en la base. La suite cubre pagos parciales, duplicados y sobrepagos secuenciales.

Se agregó `tests/Integration/Actions/RegisterDentalPaymentTest.php`. Dos procesos intentan cobrar 60.00 a una factura con saldo 100.00; la prueba espera a que ambos compitan por el bloqueo y comprueba que solo uno registre el pago y que el saldo termine en 40.00. La prueba exige opt-in, host local, PostgreSQL, una base cuyo nombre termine en `_test` y `DB_URL` vacío.

No se ejecutó en PostgreSQL: PHPUnit está configurado con SQLite en memoria y la conexión `pgsql` del proyecto apunta a la base del proyecto, no a una base aislada. La ejecución directa con la configuración actual se omitió de forma segura.

Para validarla en una base PostgreSQL local vacía, configurar sus credenciales únicamente en la sesión de PowerShell y ejecutar:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'pgsql'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '5432'
$env:DB_DATABASE = 'sistema_dental_concurrency_test'
$env:DB_URL = ''
$env:DENTAL_CONCURRENCY_TESTS = '1'

# Definir DB_USERNAME y DB_PASSWORD con credenciales de esa base local.
php artisan migrate --force
vendor\bin\phpunit tests\Integration\Actions\RegisterDentalPaymentTest.php
```

Este procedimiento es solo para la base local de pruebas. No usar la conexión de Neon.

### Medio: fecha futura aceptada como procedimiento realizado — corregido

El registro clínico aceptaba un `performed_at` futuro. Como las facturas se crean desde esos registros, se podía guardar y facturar un procedimiento con fecha futura. Ahora el formulario rechaza fechas posteriores a la hora actual de la clínica y una prueba comprueba que no se inserte el registro.

### Bajo: etiqueta accesible para seleccionar procedimientos

Los checkboxes de la pantalla de borrador de factura no tenían nombre accesible. Se asociaron a una etiqueta oculta visualmente que describe el tratamiento y su fecha.

### Bajo: resumen financiero sin paginación — corregido

El resumen financiero del paciente cargaba todos los detalles de factura en una sola consulta. Ahora muestra 20 por página y conserva los parámetros de URL. Una prueba con 21 detalles comprueba el enlace y el contenido de ambas páginas.

## Revisión por módulo

- **Autenticación y permisos:** las rutas dentales están detrás de `auth` y `active`; las rutas financieras usan autorización por rol y las pantallas clínicas aplican policies por paciente, odontólogo asignado y permiso administrativo. La suite incluye pruebas de autorización de pacientes, historial clínico, roles y facturación. No se encontró un bypass evidente en las rutas revisadas.
- **Pacientes y odontólogos:** los listados de pacientes están paginados; las fichas usan eliminación lógica y las asignaciones se actualizan en transacción. `UserController` sincroniza el estado del perfil odontológico al cambiar el rol o activar/desactivar usuarios.
- **Citas:** fechas locales se convierten a UTC; se validan intervalos y disponibilidad. PostgreSQL tiene restricciones de intervalo y estado, además de una exclusión GiST que evita traslapes de citas reservables para el mismo odontólogo.
- **Tratamientos e historial:** los planes se distinguen de los procedimientos registrados; la factura se construye desde `patient_treatments`, usa el precio histórico y no incluye notas clínicas. El registro clínico rechaza fechas de realización futuras. Las ediciones clínicas crean eventos de auditoría.
- **Facturación y pagos:** borradores, emisión, pagos y anulaciones trabajan dentro de transacciones. Los comprobantes se mantienen como documentos internos. La suite valida saldos, correcciones, permisos y exclusión de notas clínicas.
- **Recordatorios:** el código verifica consentimiento, estado de cita, elegibilidad del paciente, reintentos e idempotencia; el proveedor SMS instalado en la aplicación está deshabilitado. El scheduler está registrado cada minuto con protección contra solapamiento. Estos flujos no pueden verificarse en la base actual mientras falten sus tablas.
- **Dashboard y consultas:** las consultas revisadas usan agregados, límites, carga anticipada o paginación en las vistas principales. El resumen financiero por paciente quedó paginado. No se hizo una prueba de carga ni una inspección visual en navegador.
- **Interfaz:** las vistas dentales revisadas usan formularios etiquetados, mensajes de validación y contenedores con desplazamiento horizontal para tablas. La revisión fue estática; no equivale a una prueba manual en distintos navegadores y tamaños.

## Calidad y resultados

| Verificación | Resultado |
| --- | --- |
| `php artisan test --compact` | 273 aprobadas, 1,414 aserciones |
| `vendor/bin/pint --dirty --format agent` | Aprobado |
| `vendor/bin/pint --test` | Aprobado |
| `git diff --check` y `git diff --cached --check` | Aprobados |
| `npm run build` | Aprobado |
| Prueba PostgreSQL de concurrencia | Preparada; omitida sin base local aislada |

Vite mostró una advertencia informativa: `fontaine` es opcional para optimizar fallbacks de fuentes. No impidió el build y no se agregó esa dependencia.

La inspección de logs no mostró errores recientes de navegador; las entradas disponibles eran mensajes DEBUG antiguos de Vite. Los errores del log de aplicación corresponden a escenarios de fallo intencionales de pruebas, no a una verificación de producción.

## Migraciones

- **Aplicadas:** 15.
- **Pendiente:** `2026_10_12_040309_create_appointment_reminder_tracking_tables.php`.
- **Acción en esta auditoría:** ninguna migración aplicada ni revertida.

## Riesgos y pendientes

1. Autorizar y aplicar la migración de recordatorios mediante el procedimiento de despliegue antes de usar dashboard y cambios de citas con esa versión; después, volver a verificar los flujos.
2. Ejecutar la prueba de concurrencia en una base PostgreSQL local aislada.
3. Confirmar la regla clínica sobre procedimientos repetidos del mismo tipo en una cita antes de cambiar la restricción única.
4. Confirmar en el entorno de despliegue la ejecución del scheduler, el queue worker y la configuración de correo. No se verificaron procesos ni credenciales de producción.
5. La compilación y suite automatizadas no sustituyen una prueba funcional en producción ni una revisión visual manual.

## Archivos modificados por esta auditoría

- `resources/views/dental-invoices/create.blade.php`
- `resources/views/dental-invoices/patient-summary.blade.php`
- `app/Http/Controllers/DentalInvoiceController.php`
- `app/Http/Requests/StorePatientTreatmentRequest.php`
- `tests/Feature/DentalBillingManagementTest.php`
- `tests/Feature/PatientTreatmentManagementTest.php`
- `tests/Integration/Actions/RegisterDentalPaymentTest.php`
- `docs/fases/fase-dental-08-auditoria-final.md`

## Recomendación final

El proyecto está en condiciones para presentación académica con las pruebas automatizadas actuales, pero el despliegue queda pendiente de resolver la migración de recordatorios y verificar la prueba de concurrencia en PostgreSQL aislado. No se cambió ninguna regla clínica ambigua ni se afectaron los datos existentes.
