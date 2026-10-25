# SÓCRATES CRM v2.2.0 — COPILOT PROFESIONAL

## SÓCRATES Copilot
- El antiguo asistente se transforma en un copiloto operativo conectado a los módulos instalados del CRM.
- Lee únicamente información que el usuario autenticado tiene permiso para consultar.
- No envía datos a servicios externos de IA.
- Usa el contexto de la pantalla actual para explicar funciones y resumir registros abiertos.
- Búsqueda transversal en clientes, contactos, servicios, catálogo, negocios, proyectos, documentos, cotizaciones, contratos y tickets.
- Resúmenes en tiempo real de ventas, proyectos, gestión documental, servicios, marketing, facturación, soporte, agenda, seguridad, horas, riesgos y costos.
- Vista “Mi día” con actividades vencidas, tareas de proyecto, aprobaciones documentales, tickets, reuniones y riesgos altos.
- Resumen ejecutivo del negocio con indicadores de pipeline, proyectos, cobros, documentos y soporte.
- Acciones guiadas: el Copilot abre el formulario o módulo correcto para crear o registrar información, pero no ejecuta cambios críticos sin confirmación del usuario.
- Detección dinámica de módulos disponibles según instalación y permisos.

## Interfaz
- Nueva página profesional `assistant.php` con panel de atención, chat, accesos inteligentes y módulos conectados.
- Asistente flotante rediseñado con identidad visual SÓCRATES CRM.
- Historial reciente de conversación guardado localmente por usuario en el navegador.
- Indicador de escritura, acciones contextuales y sugerencias clicables.
- Acceso a Copilot desde el menú AYUDA y desde el perfil del usuario.

## Tour y Manual
- El Tour 360° incorpora SÓCRATES Copilot y pasa a 42 pasos.
- El capítulo Orientación pasa a 7 pasos.
- Se corrige el mapa de los 6 capítulos del tour.
- Manual 360° incorpora una sección específica de Copilot y guía de ejemplos.

## Seguridad
- `assistant_answer.php` valida CSRF.
- Las consultas respetan permisos antes de acceder a cada módulo.
- El Copilot es de consulta y navegación; las altas/ediciones se terminan en las pantallas normales del CRM.
- Los errores internos se registran con código de diagnóstico sin exponer trazas al usuario.

## Compatibilidad
- Base: SÓCRATES CRM v2.1.0 Document Manager 360°.
- No modifica tablas ni registros MySQL.
- No requiere ejecutar `repair.php`.
