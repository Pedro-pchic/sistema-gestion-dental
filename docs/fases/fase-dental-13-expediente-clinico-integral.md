# Fase Dental 13: expediente clínico integral

**Fecha:** 2026-10-12  
**Estado:** implementación y verificaciones automatizadas completas; revisión visual pendiente.

## Diagnóstico y funciones reutilizadas

La ficha del paciente ya contenía datos generales y enlaces a citas, historial clínico, procedimientos y planes. El historial clínico ya exigía `viewClinicalHistory` y paginaba citas, procedimientos y pendientes con filtros de fecha, odontólogo y tratamiento. Las notas de procedimientos se modificaban con auditoría en `patient_treatment_events`. Los planes tenían estados y partidas existentes. La Fase 12 de agenda estaba presente como cambios sin commit y se conservó.

Faltaban un resumen clínico en la ficha, una cronología conjunta y una vista de avance de los planes dentro del expediente. La pantalla clínica también mostraba importes, que se retiraron de esa vista. Las rutas anidadas de planes y procedimientos aceptaban una ficha de paciente distinta de la del registro solicitado; ahora comprueban la pertenencia antes de mostrar o modificar datos.

## Mejoras implementadas

- La ficha muestra fecha de registro y, solo a usuarios con acceso clínico, totales de citas, procedimientos y planes, última cita completada y próxima cita. Para odontólogos, las citas del resumen se limitan a las propias.
- El historial protegido incorpora navegación por secciones, una cronología paginada de citas, procedimientos y actualizaciones auditadas, y filtros por fecha, odontólogo, tratamiento y tipo de registro. El tratamiento filtra procedimientos; las citas conservan su descripción libre y no tienen una relación estructurada con el catálogo de tratamientos.
- Se muestran planes paginados con estado y avance por partidas completas. Cada tarjeta carga como máximo cinco partidas y enlaza al plan para consultar el resto. Los listados previos de citas, procedimientos y pendientes conservan su paginación y filtros.
- La cronología muestra las observaciones actuales a los usuarios autorizados, y registra fecha y autor de las actualizaciones sin repetir en pantalla los valores antiguos de las notas. Los procedimientos se toman de `patient_treatments`; una cita cancelada no se convierte en procedimiento realizado.
- Se eliminó de la vista clínica la presentación de precios estimados y aplicados. No se alteró la información almacenada, la facturación ni los permisos existentes.

## Archivos modificados

`app/Http/Controllers/PatientController.php`, `PatientClinicalHistoryController.php`, `PatientTreatmentController.php`, `TreatmentPlanController.php`; `resources/views/patients/show.blade.php`, `clinical-history.blade.php`; `tests/Feature/PatientClinicalHistoryTest.php`, `PatientTreatmentManagementTest.php`, `TreatmentPlanManagementTest.php`.

## Seguridad y permisos

La ruta del historial sigue autorizándose en el servidor con `PatientPolicy::viewClinicalHistory`: solo odontólogos activos asignados y administradores con concesión clínica explícita. Recepcionistas, administradores sin concesión y odontólogos no asignados reciben 403. Las citas del odontólogo se acotan a su perfil; los enlaces a citas del expediente corresponden a esas citas. Las rutas anidadas devuelven 404 si el plan o procedimiento no pertenece al paciente de la URL. Las notas se imprimen con escape HTML de Blade. La cronología carga sus relaciones por página y evita consultas por cada fila.

## Pruebas y resultados

| Comprobación | Resultado |
| --- | --- |
| `php artisan test --compact` | 282 pruebas y 1495 aserciones aprobadas |
| `vendor/bin/pint --dirty --format agent` | Aprobado |
| `vendor/bin/pint --test` | Aprobado |
| `git diff --check` | Aprobado |
| `npm run build` | Aprobado fuera del sandbox |

Las pruebas añadidas cubren permisos por rol, ficha sin historial, cronología ordenada, planes y paginación, filtros, citas propias, URL anidadas, escape de texto clínico y ausencia de importes en la vista clínica. Las pruebas previas siguen cubriendo auditoría, cambios de notas, estados y asociaciones con citas y planes. No se enviaron notificaciones reales.

## Limitaciones y pendientes

No se hizo una revisión visual en navegador. Queda comprobar navegación por teclado, lectura en móvil y contraste de la cronología y los planes. La cronología ordena las citas por la fecha programada y las actualizaciones por su fecha de registro; una cita futura puede aparecer antes que un procedimiento pasado. El avance de cada plan se expresa en partidas completas, no en unidades de tratamientos repetidos.

La compilación dentro del sandbox falló con `EPERM` al cargar el módulo nativo de Tailwind; el mismo comando pasó fuera de él. Vite mostró un aviso sobre `fontaine`, dependencia opcional que no se instaló. No se ejecutaron migraciones, no se modificó Neon ni SIGEC y no se hizo commit.
