# SÓCRATES CRM v2.1.0 — Document Manager 360°

## Nuevo módulo: Gestión documental

Se incorpora una biblioteca documental integrada al CRM, inspirada en la experiencia de SharePoint y adaptada al ecosistema SÓCRATES CRM.

### Biblioteca y organización
- Dashboard documental con indicadores, actividad reciente, aprobaciones y salud documental.
- Biblioteca en vista lista o tarjetas.
- Carpetas y subcarpetas.
- Buscador y filtros por carpeta, categoría, estado, responsable, vencimiento y favoritos.
- Carga individual o múltiple mediante selector o arrastrar y soltar.
- Metadatos: título, descripción, categoría, estado, visibilidad y fecha de vencimiento.
- Papelera con restauración.

### Versionado
- Historial completo de versiones.
- Nueva versión sin perder las anteriores.
- Restauración de una versión anterior como una nueva versión.
- Hash SHA-256 por archivo para trazabilidad.
- Registro de usuario, fecha, notas, tamaño, MIME y nombre original.

### Permisos y colaboración
- Permisos por usuario, rol y departamento.
- Capacidades separadas para ver, editar y compartir.
- Documentos privados o visibles para toda la empresa.
- Comentarios por documento.
- Favoritos.
- Documentos compartidos conmigo.

### Aprobaciones
- Rutas de aprobación secuenciales configurables.
- Uno o varios aprobadores por ruta.
- Aprobación o rechazo con observaciones.
- Estado del documento sincronizado con el flujo de aprobación.
- Bandeja de aprobaciones pendientes.

### Compartición externa
- Enlaces públicos criptográficamente aleatorios.
- Contraseña opcional.
- Fecha/hora de expiración.
- Límite de descargas.
- Posibilidad de deshabilitar descarga.
- Desactivación inmediata del enlace.
- Registro en auditoría de descargas externas.

### Integración con el CRM
Un documento puede relacionarse con:
- Clientes.
- Contactos.
- Proyectos.
- Cotizaciones.
- Contratos.
- Tickets de soporte.
- Servicios de clientes.

Las fichas de Cliente, Proyecto, Cotización, Contrato y Ticket muestran sus documentos relacionados y permiten cargar nuevos documentos ya vinculados al registro.

### Seguridad documental
- Archivos almacenados fuera del acceso web directo en `storage/documents/`.
- Entrega de archivos únicamente mediante control de permisos PHP.
- Lista blanca de tipos MIME.
- Tamaño máximo configurable.
- Nombres físicos aleatorios.
- Protección frente a traversal de rutas.
- Cabecera `nosniff` en descargas.
- Auditoría de acciones con usuario, IP y agente de navegador.

### Vencimientos
- Fecha de vencimiento opcional por documento.
- Alertas configurables (por defecto 30, 15, 7, 3 y 1 día).
- CRON documental para crear notificaciones internas.

### Administración
- Nuevos permisos `documents.*`.
- Departamento configurable por usuario.
- Tarjeta Gestión documental en Configuración.
- Tamaño máximo, días de alerta y visibilidad predeterminada configurables.

### Navegación, búsqueda, manual y tour
- Nueva área DOCUMENTOS en el menú principal.
- Documentos incluidos en la búsqueda global.
- Manual 360° ampliado con Gestión documental.
- Tour Guiado 360° ampliado a 6 capítulos e incluye Document Manager.

## Compatibilidad
La migración es aditiva. No elimina clientes, proyectos, cotizaciones, contratos, tickets, usuarios, archivos existentes ni configuración SMTP.
