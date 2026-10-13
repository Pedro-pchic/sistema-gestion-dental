# Fase Dental 14 — Optimización financiera

## Diagnóstico y reutilización

Las fases 11–13 ya estaban presentes como cambios locales y se conservaron. La facturación usa `dental_invoices`, `dental_invoice_details`, `dental_payments` y `patient_treatments`; sus importes son `DECIMAL(12,2)`. Los borradores toman únicamente procedimientos realizados y guardan precio histórico. El registro y la anulación de pagos ya usan transacciones, bloqueo de factura, enteros en centavos, clave idempotente, responsable y trazabilidad. Las Policies restringen el módulo a administración y recepción; solo administración anula documentos. Los comprobantes existentes son internos e imprimibles.

## Mejoras implementadas

- Listado de facturas con búsqueda por número o paciente, estado, fechas de emisión, orden, paginación y abonos vigentes calculados en la base de datos. Los pagos anulados no cuentan como abonos. La acción de cada fila refleja el estado.
- Vista de cuentas pendientes mediante el filtro «Solo saldos pendientes», accesible desde Facturas y el dashboard. Incluye paciente, factura, fecha, total, abonos, saldo y estado; comparte filtros y paginación con el listado.
- Dashboard: cantidad y saldo de facturas pendientes ahora respetan el período seleccionado. El enlace a cuentas pendientes conserva ese período.
- Reporte: muestra facturas anuladas durante el período y explica qué fechas y estados se usan para facturado, cobrado y pendiente. Los totales efectivos excluyen facturas y pagos anulados.
- Recibo: muestra explícitamente el estado del pago. Se conservó la identificación de comprobante administrativo interno y la impresión desde el navegador.

## Archivos modificados en esta fase

`app/Http/Controllers/DentalInvoiceController.php`, `app/Http/Controllers/DentalFinancialReportController.php`, `app/Services/DentalDashboardService.php`, `resources/views/dental-invoices/index.blade.php`, `resources/views/dashboard/dental.blade.php`, `resources/views/dental-reports/index.blade.php`, `resources/views/documents/dental-receipt.blade.php`, `tests/Feature/DentalBillingManagementTest.php` y `tests/Feature/DentalDashboardTest.php`.

## Validación financiera y seguridad

Se conservaron las reglas de emisión, pagos parciales y completos, prevención de sobrepagos y duplicados, anulación con auditoría, CSRF y autorización existentes. Los importes cobrados se suman como `DECIMAL` en SQL; no se introdujeron cálculos de saldo en punto flotante ni importes confiados al navegador. Los nuevos filtros tienen valores y fechas validados antes de construir consultas. No se alteraron tablas, migraciones, dependencias ni datos de Neon.

## Pruebas y resultados

- `php artisan test --compact`: 284 pruebas correctas y 1512 aserciones; la prueba concurrente se omitió.
- `vendor/bin/pint --test` y `git diff --check`: correctos.
- `npm run build`: correcto fuera del sandbox. Dentro falló por bloqueo `EPERM` del proceso nativo de Tailwind en Windows.
- La prueba de concurrencia PostgreSQL quedó omitida: requiere activación explícita y una base local aislada terminada en `_test`. No se ejecutó contra Neon principal.

## Límites y pendientes

El saldo del reporte y del dashboard es el saldo **actual** de las facturas emitidas en el período, no una reconstrucción histórica al cierre de ese período. El reporte agrupa como máximo 30 pacientes por saldo; la vista de cuentas pendientes permite consultar todas las facturas con paginación. Queda pendiente ejecutar la prueba concurrente cuando exista PostgreSQL local aislado. No se implementaron vencimientos, intereses, contabilidad general ni facturación fiscal.
