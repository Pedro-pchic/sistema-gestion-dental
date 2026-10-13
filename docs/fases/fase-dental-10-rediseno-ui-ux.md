# Fase Dental 10: rediseño UI/UX e identidad odontológica

**Fecha:** 2026-10-12  
**Estado:** implementación y pruebas automatizadas completas; revisión visual en navegador pendiente.

## Componentes rediseñados

- **Identidad:** se reemplazó la marca anterior por un símbolo dental ligero en sidebar, login y favicon. El panel usa fondo claro, navegación oscura y acentos turquesa.
- **Navegación:** sidebar agrupado por los permisos existentes, iconos SVG, estado activo de alto contraste, apertura móvil con control de foco y modo compacto en escritorio. El estado compacto se recuerda en el navegador.
- **Encabezado:** título de página único, ruta simple a Inicio cuando corresponde, nombre y rol del usuario, cierre de sesión y enlace para saltar al contenido.
- **Dashboard:** cuatro indicadores prioritarios según rol; resumen de estados, próximas citas, recordatorios, saldos pendientes y accesos rápidos. La nueva actividad reciente usa los últimos cinco eventos reales de citas, con carga anticipada de paciente y filtro por odontólogo para ese rol. No se muestran notas clínicas ni métricas simuladas.
- **Vistas dentales:** tarjetas, botones, filtros, formularios, tablas y estados comparten clases `ui-*`. Se quitaron bloques vacíos de plantilla que aparecían antes del layout y mensajes de error duplicados en los detalles de factura y pago. Los formularios de anulación ahora tienen etiquetas accesibles.

No había un modal dental existente que requiriera rediseño; no se añadió uno sin una función que lo necesite.

## Paleta y tipografía

| Uso | Valor |
| --- | --- |
| Turquesa principal | `#0F766E` |
| Azul de apoyo | `#2563EB` |
| Menta suave | `#EAF7F5` |
| Fondo | `#F4F7FB` |
| Texto principal | `#172B4D` |
| Sidebar | `#12343E` |
| Tipografía | Instrument Sans existente, con respaldo de fuentes del sistema |

Las combinaciones principales calculadas superan 4.5:1: turquesa sobre blanco 5.47:1, texto principal sobre fondo 13.12:1, menta del sidebar sobre fondo oscuro 6.83:1 y blanco sobre turquesa 5.47:1.

## Responsive y accesibilidad

El sidebar funciona como panel móvil bajo `lg` y se reduce a iconos en escritorio. La cabecera adapta título y acciones al ancho disponible. Los formularios conservan columnas únicas en móvil y las tablas densas de pacientes, tratamientos, facturas, pagos y usuarios tienen un ancho mínimo dentro de contenedores con desplazamiento horizontal, manteniendo sus acciones accesibles. El dashboard cambia de una a dos y cuatro columnas según el ancho.

Los controles de navegación tienen nombre accesible, `aria-expanded`, foco visible y operación por teclado; el panel móvil conserva cierre con Escape y contención del foco. El enlace «Saltar al contenido» permite omitir el menú. Los errores siguen en un aviso con `role="alert"` y se mantienen las validaciones existentes. Los iconos decorativos se ocultan a tecnologías asistivas.

Estas características se revisaron en código y mediante compilación; falta comprobarlas visualmente con teclado y lector de pantalla en un navegador real.

## Archivos modificados

- Identidad y estructura: `resources/css/app.css`, `resources/js/app.js`, `public/favicon.svg`, `resources/views/layouts/admin.blade.php`, `resources/views/components/admin/nav-link.blade.php` y `resources/views/auth/login.blade.php`.
- Dashboard y datos: `resources/views/dashboard/dental.blade.php`, `app/Services/DentalDashboardService.php` y `tests/Feature/DentalDashboardTest.php`.
- Vistas dentales: archivos de `resources/views/patients`, `appointments`, `treatments`, `patient-treatments`, `treatment-plans`, `dental-invoices`, `dental-payments`, `dental-reports` y `users`.
- Este reporte: `docs/fases/fase-dental-10-rediseno-ui-ux.md`.

Se conservaron las rutas, campos, reglas, permisos y lógica clínica y financiera. No se modificó SIGEC, Neon, el esquema ni las dependencias; no se hicieron commits.

## Pruebas ejecutadas

| Comprobación | Resultado |
| --- | --- |
| `php artisan test --compact` | 274 aprobadas, 1,426 aserciones |
| `vendor/bin/pint --dirty --format agent` | Aprobado |
| `vendor/bin/pint --test` | Aprobado |
| `git diff --check` | Aprobado |
| `npm run build` | Aprobado fuera del sandbox |
| `php artisan view:cache --no-interaction` | Plantillas Blade compiladas |
| `node --check resources/js/app.js` | Aprobado |

Vite mostró únicamente el aviso informativo sobre `fontaine`, una dependencia opcional de optimización de fuentes. La compilación dentro del sandbox falló por `EPERM` al cargar el módulo nativo de Tailwind y pasó al repetirla fuera de él.

## Pendientes

No se realizó validación visual en navegador. El puerto local `localhost:8082` no tenía un servidor HTTP activo ni había una sesión autenticada de prueba. Queda revisar login y los módulos dentales a tamaños de escritorio, tablet y móvil, además de foco, menú, tablas y mensajes de error con navegador y teclado. La revisión de código no sustituye una auditoría completa de accesibilidad con lector de pantalla.
