# Fase Dental 08B: cierre operativo y validación de integración

**Fecha:** 2026-10-12  
**Estado:** validación automatizada aprobada; revisión visual y concurrencia en PostgreSQL aislado pendientes.

## 1. Migraciones y estado real

Se trabajó en `C:\laragon\www\sistema-dental`. La conexión predeterminada es `pgsql` y apunta a `neondb` en un host de Neon. `php artisan migrate:status --no-interaction` muestra las 16 migraciones aplicadas; `2026_10_12_040309_create_appointment_reminder_tracking_tables.php` figura en el lote 5. En la auditoría 08 figuraba pendiente: su estado cambió antes de esta revisión. Aquí no se ejecutó ninguna migración ni se modificó SIGEC.

La inspección de solo lectura confirmó las siete columnas de preferencias en `patients`, la clave foránea de `notification_preferences_updated_by` hacia `users` con `ON DELETE SET NULL`, y las tablas `appointment_reminders` y `appointment_reminder_attempts` con sus columnas, claves foráneas e índices esperados. Los recordatorios tienen una clave única por cita, paciente, canal y hora de inicio; los intentos, por recordatorio y número de intento. Las claves foráneas de recordatorios e intentos usan `ON DELETE CASCADE`. Ambas tablas están vacías y no se detectaron objetos parciales respecto de la migración inspeccionada. La integridad referencial está protegida por las claves foráneas; no se hizo una escritura de prueba en Neon.

El riesgo de despliegue por tablas ausentes, señalado en la auditoría 08, ya no aparece en el esquema actual. No se puede atribuir cuándo ni quién aplicó la migración a partir de esta inspección.

## 2. Validaciones completadas

- **Citas y recordatorios:** las pruebas de creación, reprogramación y cancelación de citas pasan. Las pruebas de recordatorios cubren elegibilidad por estado, consentimiento previo y revocado, límite de 24 horas, invalidación al reprogramar o cancelar, idempotencia y ausencia de duplicados.
- **Intentos y entrega:** se comprobaron registro de intento, correo sin notas clínicas, reintentos SMS con límite de tres y desactivación SMS sin proveedor. En las pruebas se usa cola simulada, correo `array` y proveedor SMS simulado; no se enviaron correos ni SMS reales.
- **Comando:** se agregó una prueba que ejecuta `appointments:dispatch-reminders`, verifica la salida, un único trabajo encolado y ningún intento de entrega. Pasó junto con las otras siete pruebas de recordatorios.
- **Dashboard:** las pruebas de administrador, recepcionista y odontólogo pasan sobre SQLite en memoria. Se añadieron aserciones para el contador de recordatorios pendientes, el de fallos recientes y la ausencia de estos contadores para odontólogos. Por separado se confirmó que Neon ya tiene las tablas de recordatorios; esto no equivale a una prueba funcional del dashboard en Neon.
- **Facturación:** la prueba de concurrencia preparada en `tests/Integration/Actions/RegisterDentalPaymentTest.php` se revisó. Usa dos procesos que disputan el bloqueo de una factura de 100.00, cada uno intenta registrar 60.00 y espera exactamente un pago aceptado, otro rechazado y saldo 40.00. La acción usa `lockForUpdate`, validación de saldo en centavos y clave de idempotencia.

## 3. Resultados de pruebas

| Comprobación | Resultado |
| --- | --- |
| `php artisan test --compact tests/Feature/AppointmentReminderTest.php` | 8 aprobadas, 41 aserciones |
| `php artisan test --compact tests/Feature/DentalDashboardTest.php` | 4 aprobadas, 39 aserciones |
| `php artisan test --compact` | 274 aprobadas, 1,422 aserciones |
| `vendor/bin/pint --dirty --format agent` | Aprobado |
| `vendor/bin/pint --test` | Aprobado |
| `git diff --check` | Aprobado |
| `npm run build` | Aprobado fuera del sandbox; aviso informativo de `fontaine` opcional |
| `vendor/bin/phpunit tests/Integration/Actions/RegisterDentalPaymentTest.php` | 1 omitida por su guarda; 0 aserciones |

La compilación falló inicialmente dentro del sandbox por `EPERM` al cargar un módulo nativo y pasó al repetirla fuera de él. No se cambió ninguna dependencia.

## 4. Problemas corregidos

Faltaba una comprobación automatizada del comando Artisan que inicia el despacho de recordatorios y una aserción específica de sus contadores en el dashboard. Se añadieron a `tests/Feature/AppointmentReminderTest.php` y `tests/Feature/DentalDashboardTest.php`; no se cambió la lógica de producción. La migración que faltaba en la auditoría 08 ya estaba aplicada al comenzar esta tarea.

## 5. Pendientes de configuración y revisión

**Concurrencia PostgreSQL.** `phpunit.xml` configura SQLite en memoria. El puerto local `127.0.0.1:5432` no respondió y la conexión PostgreSQL predeterminada apunta a Neon; no hay una base PostgreSQL local aislada disponible. Por eso no se validaron en PostgreSQL real la espera por bloqueo, el rechazo del segundo pago ni el saldo concurrente. La prueba está fuera de la suite predeterminada y exige `APP_ENV=testing`, `DENTAL_CONCURRENCY_TESTS=1`, `DB_URL` vacío, host local y nombre de base terminado en `_test`.

Para ejecutarla más adelante, crear primero una base PostgreSQL **local, vacía y exclusiva para pruebas**, asignar sus credenciales a `DB_USERNAME` y `DB_PASSWORD` en la sesión de PowerShell y verificar host y nombre antes de migrarla:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'pgsql'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '5432'
$env:DB_DATABASE = 'sistema_dental_concurrency_test'
$env:DB_URL = ''
$env:DENTAL_CONCURRENCY_TESTS = '1'

php artisan config:show database.connections.pgsql.host --no-interaction
php artisan config:show database.connections.pgsql.database --no-interaction
php artisan migrate:status --no-interaction
php artisan migrate --force --no-interaction
vendor/bin/phpunit tests/Integration/Actions/RegisterDentalPaymentTest.php
```

**Revisión visual.** No se realizó una revisión con navegador. Aunque Microsoft Edge está instalado, no había servidor HTTP escuchando en la URL local configurada (`localhost:8082`) ni una sesión autenticada de prueba. Quedan por revisar en navegador login, dashboard, pacientes, citas, tratamientos, historial clínico, facturación, pagos, navegación adaptable, mensajes de validación y permisos por rol.

**Operación de recordatorios.** Falta confirmar en el entorno de despliegue la ejecución periódica del scheduler, el worker de cola, el transporte de correo y sus credenciales. La suite solo valida el flujo aislado y no prueba estos procesos externos.

## 6. Regla de tratamientos repetidos

`patient_treatments` tiene dos restricciones únicas: `(appointment_id, treatment_id)` y `(patient_id, dentist_id, treatment_id, performed_at)`. La primera impide registrar el mismo tipo de tratamiento dos veces en una cita, aunque se aplique a piezas distintas o a dos partidas de un plan con cantidad mayor que uno. La segunda impide dos registros del mismo tratamiento, paciente y odontólogo en el mismo instante incluso sin cita. Al ser restricciones de tabla, también cuentan filas eliminadas lógicamente. La prueba actual confirma el rechazo del duplicado en una cita.

Si la clínica necesita distinguir procedimientos repetidos válidos, debe definir primero su unidad de registro: pieza o zona tratada, ocurrencia individual o cantidad de unidades. Una alternativa es guardar cada ocurrencia con su pieza/zona y reemplazar la unicidad de cita por una clave que refleje esa identidad; otra es admitir varias ocurrencias por cita y conservar una restricción de idempotencia específica para evitar envíos duplicados. Registrar una hora artificial o quitar la cita para sortear la restricción deformaría el historial.

El historial y el progreso del plan cuentan filas de procedimientos. La creación del borrador de factura usa cada `patient_treatment_id` como una línea de cantidad 1 y conserva su precio histórico. Si se cambia a cantidades agregadas, habrá que adaptar el conteo del plan, la selección y los detalles de factura para mantener trazabilidad por procedimiento. No se cambió la regla clínica ni el esquema.

## 7. Riesgos residuales y preparación académica

El código y el esquema inspeccionado están en condiciones de documentarse académicamente, indicando de forma expresa los límites de esta validación. Quedan pendientes la prueba de concurrencia en PostgreSQL aislado, la revisión visual con navegador y la verificación operativa del scheduler, la cola y el correo. La regla clínica de tratamientos repetidos necesita una decisión antes de cualquier cambio. No se iniciaron otras fases, no se hicieron commits y no se modificó `Tarea1.md`.
