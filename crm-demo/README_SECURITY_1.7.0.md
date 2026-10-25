# Seguridad del CRM

La versión 1.7.0 aplica defensa en profundidad dentro de la aplicación. No convierte por sí sola el servidor en una infraestructura certificada ni sustituye una auditoría de seguridad independiente.

## Nivel objetivo

El diseño se orienta a controles equivalentes a una aplicación empresarial de riesgo moderado: autenticación reforzada, mínimo privilegio, auditoría, protección de sesiones, cifrado, integridad y respuesta ante eventos.

## Archivos sensibles

- `includes/config.php`: debe permanecer con permisos restrictivos y nunca publicarse.
- `storage/`: contiene logs, línea base, caché y respaldos cifrados. Incluye reglas para bloquear acceso web.
- `uploads/quotation-attachments/` y otras carpetas documentales: quedan bloqueadas para acceso directo.

## Respaldos

El respaldo interno cubre MySQL y se cifra con la clave privada de la aplicación. Debe descargarse y almacenarse fuera del servidor. Los archivos de `uploads/` deben respaldarse por separado desde el hosting.

## Auditoría

Los registros de auditoría se conservan inicialmente por 365 días. La tarea de mantenimiento elimina registros técnicos antiguos y sesiones vencidas.

## TOTP

La configuración se hace manualmente con la clave secreta para evitar enviar el secreto a servicios externos de generación de códigos QR.

## Infraestructura pendiente

Requiere configuración manual fuera del ZIP:

- Cloudflare WAF y rate limiting.
- Firewall y Fail2ban.
- 2FA en panel de hosting y correo.
- SFTP o SSH con llaves.
- Actualizaciones de PHP, MySQL y sistema operativo.
- Backups externos automatizados.
- Escaneo antivirus y monitorización del servidor.
- Revisión de SPF, DKIM y DMARC para alertas por email.
