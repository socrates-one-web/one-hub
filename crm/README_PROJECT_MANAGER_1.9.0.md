# SÓCRATES ONE CRM 1.9.0 · PROJECT MANAGER

Módulo integrado de administración y gestión de proyectos para SÓCRATES ONE CRM.

## Módulos incluidos

- Dashboard ejecutivo de proyectos con KPI y gráficos.
- Portafolio de proyectos por cliente, responsable, estado, salud y prioridad.
- Conversión directa de negocios ganados a proyectos.
- Ficha 360° del proyecto con presupuesto, avance, equipo, hitos, tareas, riesgos y archivos.
- Kanban drag & drop: Backlog, Por hacer, En progreso, Revisión, Bloqueado y Completado.
- Gantt / Project con fechas, duración y avance.
- Calendario mensual de entregas.
- Hitos y tareas/subtareas.
- Dependencias entre tareas.
- Checklist y comentarios por tarea.
- Horas planificadas y reales.
- Timesheet y horas facturables.
- Equipo, rol, porcentaje de asignación y costo por hora.
- Registro de riesgos: probabilidad × impacto, responsable y mitigación.
- Costos y gastos por proyecto, con opción refacturable.
- Archivos de proyecto protegidos y descargables desde el CRM.
- Permisos individuales integrados al sistema de usuarios.
- Integración con clientes y búsqueda global.
- Responsive y modo claro/oscuro siguiendo el diseño actual del CRM.

## Instalación sobre CRM 1.8.0

1. Exporta la base de datos desde phpMyAdmin.
2. Conserva `includes/config.php` y la carpeta `uploads/`.
3. Sube el contenido del ZIP de actualización sobre `crm.socrates.la`.
4. Acepta reemplazar archivos.
5. **No ejecutes `install.php`.**
6. Abre `https://crm.socrates.la/repair.php`.
7. Pulsa **REPARAR Y ACTUALIZAR AHORA**.
8. Recarga el CRM con `Ctrl + F5`.

La reparación crea las nuevas tablas y conserva clientes, contactos, negocios, servicios, facturación, cotizaciones, contratos, usuarios, permisos y configuración existentes.

## Flujo recomendado

**Negocio → Cerrado ganado → Convertir en proyecto → Planificación → Hitos → Tareas → Kanban/Gantt → Horas → Riesgos/Costos → Proyecto completado.**
