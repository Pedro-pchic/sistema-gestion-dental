# Fase Dental 09B: cumplimiento del proyecto de curso

**Fecha:** 2026-10-12  
**Estado:** cotejo académico pendiente del documento original.

## Alcance y fuente pendiente

`Tarea5.md` solicita comparar la aplicación con `PROYECTO DE CURSO.pdf`. Una búsqueda de archivos del repositorio, incluidos los ocultos y los no seguidos por Git, no encontró ese PDF ni diagramas `.drawio`. Se solicitó el PDF. Por tanto, **no se conoce el texto de ningún requisito original ni la imagen organizacional**. Las filas siguientes corresponden únicamente a los temas nombrados en `Tarea5.md`; no deben presentarse como transcripción ni como matriz final del PDF.

Se revisaron rutas, controladores, servicios, acciones, configuración, vistas, pruebas y los tres informes existentes en `docs/fases`. La presencia de código o de pruebas demuestra implementación y cobertura automatizada, pero no confirma por sí sola cumplimiento del enunciado académico ni operación en producción. Esta revisión no ejecutó pruebas, migraciones ni escrituras de base de datos.

## Matriz de cumplimiento provisional

| Requisito original del PDF | Tema indicado en `Tarea5.md` y módulo | Archivos de implementación representativos | Pruebas asociadas | Estado frente al PDF |
| --- | --- | --- | --- | --- |
| Texto no disponible | Gestión de pacientes: altas, consultas, cambios, desactivación, asignación de odontólogos y acceso al historial | `routes/web.php`, `app/Http/Controllers/PatientController.php`, `app/Http/Controllers/PatientClinicalHistoryController.php`, `app/Policies/PatientPolicy.php`, `resources/views/patients/` | `tests/Feature/PatientAdministrationTest.php`, `tests/Feature/PatientPolicyTest.php`, `tests/Feature/ClinicalRecordAuthorizationTest.php` | **No verificable**: funciones implementadas, criterios originales desconocidos |
| Texto no disponible | Gestión de citas: agenda, creación, reprogramación, transiciones y auditoría | `routes/web.php`, `app/Http/Controllers/AppointmentController.php`, `app/Http/Controllers/AppointmentTransitionController.php`, `app/Services/AppointmentScheduler.php`, `database/migrations/2026_10_11_010518_create_appointments_table.php`, `resources/views/appointments/` | `tests/Feature/AppointmentManagementTest.php`, `tests/Feature/AppointmentPolicyTest.php` | **No verificable**: funciones implementadas, criterios originales desconocidos |
| Texto no disponible | Recordatorios: consentimiento, planificación, correo, intentos y canal SMS | `app/Services/AppointmentReminderScheduler.php`, `app/Console/Commands/DispatchAppointmentReminders.php`, `app/Jobs/SendAppointmentReminder.php`, `app/Notifications/AppointmentReminderNotification.php`, `routes/console.php`, `config/appointment_reminders.php` | `tests/Feature/AppointmentReminderTest.php`, `tests/Feature/PatientReminderPreferencesTest.php` | **No verificable** frente al PDF; operación externa **parcial** |
| Texto no disponible | Catálogo, planes y registro de tratamientos realizados | `app/Http/Controllers/TreatmentController.php`, `app/Http/Controllers/TreatmentPlanController.php`, `app/Http/Controllers/PatientTreatmentController.php`, `app/Models/PatientTreatment.php`, `resources/views/treatments/`, `resources/views/patient-treatments/` | `tests/Feature/TreatmentAdministrationTest.php`, `tests/Feature/TreatmentPlanManagementTest.php`, `tests/Feature/PatientTreatmentManagementTest.php` | **No verificable**: funciones implementadas, criterios originales desconocidos |
| Texto no disponible | Facturación dental, pagos parciales, saldos y comprobantes internos | `app/Http/Controllers/DentalInvoiceController.php`, `app/Http/Controllers/DentalPaymentController.php`, `app/Actions/CreateDentalInvoiceDraft.php`, `app/Actions/IssueDentalInvoice.php`, `app/Actions/RegisterDentalPayment.php`, `resources/views/dental-invoices/`, `resources/views/dental-payments/` | `tests/Feature/DentalBillingManagementTest.php`, `tests/Integration/Actions/RegisterDentalPaymentTest.php` | **No verificable** frente al PDF; concurrencia PostgreSQL real pendiente |
| Texto no disponible | Página funcional: navegación autenticada y pantallas dentales | `routes/web.php`, `resources/views/layouts/admin.blade.php`, `resources/views/dashboard/dental.blade.php`, vistas de los cinco módulos anteriores | Pruebas funcionales anteriores y `tests/Feature/DentalDashboardTest.php` | **No verificable** frente al PDF; validación en navegador pendiente |
| Texto no disponible | Requerimientos funcionales y no funcionales como entregables escritos | `Tarea5.md` solo enumera estos entregables; los informes de `docs/fases/` documentan auditorías y rediseño | No hay una prueba que sustituya la especificación académica | **No verificable**: falta el listado original y no se halló una especificación propia completa |
| Texto no disponible | Siete fases del SDLC como entregable | Informes `docs/fases/fase-dental-08-auditoria-final.md`, `fase-dental-08b-cierre-operativo.md` y `fase-dental-10-rediseno-ui-ux.md`; código y pruebas evidencian construcción y verificación | Suite de pruebas existente; resultados históricos en los informes | **No verificable**: faltan nombres, criterios y evidencias documentales de las siete fases originales |
| Texto no disponible | Diagramas UML | No se encontraron archivos `.drawio`, `.puml`, `.plantuml`, `.mmd`, `.mermaid` ni otros diagramas UML en `docs/` o `public/` | No aplica | **No verificable**: no hay diagramas para comparar con el código |
| Texto no disponible | «Áreas de la empresa» | Propuesta dental de la sección siguiente; imagen original no disponible | No aplica | **No verificable**: falta cotejar la imagen y los nombres exigidos |

**Criterio de estado:** «cumple» exige cotejar el requisito literal y su evidencia; «parcial» identifica una función o entrega incompleta; «pendiente» indica una entrega aún no hecha; «no verificable» indica falta de fuente o de prueba suficiente. Ninguna fila puede declararse «cumple» con respecto al PDF ausente.

## Áreas de la empresa: borrador para clínica dental

La siguiente organización es una **propuesta de adaptación**, no una reproducción de la imagen del PDF. Debe revisarse cuando esté disponible esa imagen.

| Área organizativa propuesta | Responsabilidad en la clínica | Automatización visible en la aplicación dental |
| --- | --- | --- |
| Dirección y administración | Coordinar la clínica, usuarios y seguimiento | Administración de usuarios y dashboard por rol |
| Atención clínica odontológica | Evaluar pacientes, planificar y realizar procedimientos | Historial clínico, catálogo, planes y tratamientos realizados |
| Recepción y atención al paciente | Registrar pacientes, gestionar agenda y contacto | Pacientes, citas y preferencias de recordatorio |
| Administración financiera | Emitir documentos internos y registrar cobros | Facturas dentales, pagos parciales, saldos y reportes |
| Servicios generales y apoyo externo | Instalaciones, suministros, soporte y comunicación | Sin automatización dental acreditada por esta revisión |

Un área del organigrama describe **quién trabaja y qué responsabilidad tiene**; un módulo describe **qué proceso automatiza el software**. La coincidencia de nombres no implica que exista un requisito de software. El repositorio conserva código comercial heredado de compras, inventario, marketing y recursos humanos; `routes/web.php` carga `routes/legacy.php` únicamente en el entorno de pruebas. Ese código no se toma como evidencia de módulos dentales en producción ni justifica ampliarlos por aparecer en un organigrama.

## Inventario de diagramas UML

No se encontraron archivos `.drawio` en el repositorio. La búsqueda de otros formatos comunes de diagramas tampoco encontró material UML en `docs/` o `public/`; `public/favicon.svg` es un recurso visual de identidad. Por ello no es posible comprobar correspondencia de actores, entidades, clases, estados o flujos de diagramas con el código. Si los diagramas están fuera del repositorio, deben incorporarse o proporcionarse para revisar al menos pacientes, citas, recordatorios, tratamientos y facturación.

## Documentación SDLC y entregables

| Entregable | Evidencia encontrada | Falta para validar el curso |
| --- | --- | --- |
| Siete fases SDLC | Tres informes de fase (08, 08B, 10), código, migraciones y pruebas | PDF con definición de cada fase y documentos trazables para las siete; no se infiere que 08, 08B y 10 equivalgan a fases SDLC |
| Requerimientos funcionales | Funciones observables en rutas, controladores y pruebas | Lista original con identificadores y criterios de aceptación; trazabilidad requisito → código → prueba |
| Requerimientos no funcionales | Policies y pruebas de autorización; validaciones y controles de concurrencia; informe de accesibilidad y compilación en fase 10 | Lista original y mediciones exigidas; no hay evidencia de rendimiento, disponibilidad ni revisión visual completa |
| Diagramas UML | Ninguno localizado | Diagramas originales y comparación con la implementación actual |
| Página funcional | Rutas dentales de producción, vistas Blade y resultados automatizados históricos | Demostración en navegador con servidor, usuarios de prueba y flujos por rol |
| Áreas de la empresa | Borrador propuesto arriba | Imagen original, correspondencia de áreas y texto académico definitivo |

Los informes anteriores registran resultados de pruebas y compilación en sus fechas; **no se repitieron en esta fase**. El informe 08 reportó la migración de seguimiento de recordatorios pendiente; el 08B registró después las 16 migraciones aplicadas y las tablas presentes mediante lectura. Este documento no vuelve a consultar Neon, así que el estado actual del despliegue queda por confirmar.

## Evidencia y pendientes priorizados

1. **Bloqueante académico:** obtener `PROYECTO DE CURSO.pdf`, transcribir fielmente cada requisito y la estructura de la imagen, y completar la matriz por requisito individual con estado y evidencia. Sin ello no existe una conclusión válida de cumplimiento global.
2. **Entregables ausentes o no localizados:** localizar o preparar, según el PDF, la documentación de las siete fases SDLC, los requerimientos funcionales y no funcionales y los diagramas UML. Comprobar cada diagrama contra rutas, modelos y estados actuales.
3. **Operación de recordatorios:** confirmar el estado actual de la migración en el entorno objetivo y activar/verificar SMTP, worker de cola y scheduler. `config/appointment_reminders.php` deshabilita ambos canales por defecto; `AppServiceProvider` vincula SMS a `DisabledSmsSender`. Las pruebas usan dobles y no acreditan entregas reales.
4. **Validación funcional:** recorrer en navegador los cinco módulos y permisos por rol, con especial atención a interfaz adaptable y accesibilidad. El informe 10 deja explícitamente pendiente esta comprobación.
5. **Riesgos clínicos y financieros:** ejecutar la prueba de concurrencia en una base PostgreSQL local aislada; confirmar la regla clínica para tratamientos repetidos antes de modificar restricciones o procesos. El informe 08B documenta ambos límites.

## Conclusión

El repositorio aporta evidencia concreta de una aplicación dental con los cinco módulos principales pedidos para revisión, rutas de producción, vistas y pruebas automatizadas. **No puede afirmarse el cumplimiento del proyecto académico original**: falta el PDF, no se localizaron diagramas UML ni una documentación completa de las siete fases, y algunas verificaciones operativas y visuales siguen pendientes. Cuando se proporcione el PDF, esta matriz debe sustituir los renglones temáticos por **cada requisito literal** y cerrar el estado de cada uno con evidencia verificable.
