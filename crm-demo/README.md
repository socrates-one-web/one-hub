# SÓCRATES ONE CRM 1.6.0

CRM propio en PHP y MySQL para administrar clientes, contactos, actividades, servicios, cotizaciones, contratos, oportunidades comerciales, campañas, reuniones, vencimientos e ingresos.

## Novedades de la versión 1.6.0

- KPI del dashboard convertidos en tarjetas independientes con drag and drop.
- Tamaño S, M, L o XL para cada KPI, gráfico, tabla y bloque del dashboard.
- Menú lateral más compacto, alineado y con iconos de color sin fondos.
- Submenús laterales estables al mover el cursor desde el menú.
- Selector de idioma con banderas completas.
- Buscador global del CRM con acceso rápido mediante Ctrl/Cmd + K.
- Asistente interno convertido en chat flotante en la esquina inferior derecha.
- Creador de formularios con plantillas predefinidas y más tipos de campos.
- Formularios públicos con listas, radio, casillas, archivos, URL, fecha y hora.
- Manual visual con navegación interna e impresión / guardado como PDF.
- Tipo de cambio USD/BRL y USD/CLP en el footer con caché diaria.
- Permisos individuales de usuarios organizados en tres columnas.
- Alineación superior corregida en los servicios e ítems de las cotizaciones.

## Funciones existentes que se conservan

- Clientes organizados por país.
- Catálogo de servicios en español, portugués e inglés.
- Mantenedor independiente de categorías.
- Servicios asignados a clientes con fechas, modalidades y renovaciones.
- Cotizaciones en CLP, USD y BRL.
- Impresión y PDF A4.
- Envío de cotizaciones con resumen, PDF y documentos adicionales.
- Forma de pago opcional en cada cotización.
- Formas de pago reutilizables y envío por email o WhatsApp.
- Contratos editables, envío para aprobación y PDF para firma.
- Usuarios, roles, permisos, fotografía y autenticación de dos factores por email.
- Alertas, ingresos, enlaces de facturación, tickets de soporte y manual integrado.

## Requisitos

- PHP 8.1 o superior.
- MySQL 5.7+ o MariaDB equivalente.
- Extensiones PDO MySQL, OpenSSL, JSON y Fileinfo.
- cURL cuando se utilice WhatsApp Cloud API.
- SMTP autenticado recomendado.
- Tarea CRON diaria para alertas y vencimientos.
- HTTPS activo.

## Instalación nueva

1. Crea una base de datos MySQL vacía.
2. Sube el contenido de la carpeta `socrates-one-crm` a la raíz del subdominio.
3. Abre `https://crm.socrates.la/install.php`.
4. Completa los datos de MySQL y crea el administrador.
5. Configura SMTP y realiza una prueba de correo.
6. Configura el CRON mostrado en Configuración.
7. Elimina o bloquea `install.php` después de comprobar el acceso.

## Actualización desde una versión anterior

Lee `ACTUALIZACION_RAPIDA_1.6.0.txt`. Esta actualización no modifica ni elimina datos de la base de datos.

## Datos que deben respaldarse

- Base de datos MySQL completa.
- `includes/config.php`.
- Carpeta `uploads/`.

## Contratos

Las plantillas admiten variables como:

- `{{EMPRESA_PRESTADORA}}`
- `{{ID_PRESTADOR}}`
- `{{DIRECCION_PRESTADOR}}`
- `{{CLIENTE}}`
- `{{ID_CLIENTE}}`
- `{{PAIS}}`
- `{{DIRECCION_CLIENTE}}`
- `{{CONTACTO}}`
- `{{EMAIL_CLIENTE}}`
- `{{SERVICIOS}}`
- `{{VALOR}}`
- `{{FECHA_INICIO}}`
- `{{FECHA}}`

Las plantillas son modelos operativos editables. Deben ser revisadas por un abogado del país aplicable antes de utilizarlas como documento legal definitivo.

## Asistente interno

El asistente incluido es una ayuda local basada en la estructura y el manual del CRM. Responde preguntas sobre navegación y uso del sistema sin enviar datos de clientes a un proveedor externo. No es un asesor legal ni financiero y no genera decisiones comerciales automáticamente.

## Seguridad

- Usa HTTPS.
- Activa 2FA solo después de verificar SMTP.
- Mantén respaldos periódicos.
- No guardes contraseñas de clientes en notas o contratos.
- Configura SPF, DKIM y DMARC para el dominio remitente.
- Limita permisos según el rol de cada usuario.
