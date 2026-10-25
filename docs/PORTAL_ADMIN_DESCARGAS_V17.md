# ONE HUB Community — Visibilidad de patrocinios y administrador privado v1.7

**Código fuente del sitio** (no reemplaza los ZIP de la Release v1.0.0 estable).

## Nuevas secciones públicas

- Botón **APOYAR/APOIAR/SUPPORT** en el menú de escritorio y móvil.
- Destacado con icono en la portada, sello de colaboración comunitaria en la sección `#contribuir`, recordatorio de apoyo junto a las descargas y enlaces destacados en el footer.
- Tres pasarelas proporcionadas por el titular: GitHub Sponsors, Stripe y Mercado Pago.
- Se conservan los idiomas ES/PT-BR/EN, el sitio PHP y los botones gratuitos.

## Sistema de registros y clics

`descargar.php` continúa solicitando nombre, correo y aceptación de privacidad y guardando solicitudes en `_private_downloads/lead_requests.csv`. La nueva versión registra el **clic en el botón hacia GitHub** en `_private_downloads/download_events.csv` antes de redirigir al ZIP externo. Un clic **no confirma que GitHub terminó de transferir el ZIP**.

El panel **`admin-descargas.php`** permite ver ambas tablas, cantidades por edición, buscar, filtrar, exportar CSV y borrar datos por dirección de email, con autenticación privada, sesiones, tokens CSRF y bloqueo temporal por fallos de acceso.

## Activación segura

El panel **no tiene contraseñas predeterminadas** y no funciona hasta configurarse.

- El paquete privado de despliegue contiene `_private_downloads/admin_setup.key`, generado aleatoriamente y **excluido del repositorio público**.
- El administrador abre `https://os.socrates.la/admin-descargas.php`, introduce la clave de un solo uso (consultada mediante Ferozo/SFTP) y establece su propia contraseña segura (mínimo 12 caracteres).
- El proceso genera `_private_downloads/admin_config.php` con el hash de la contraseña y destruye `admin_setup.key`.
- La carpeta `_private_downloads` está protegida por `.htaccess`; debe comprobarse que **Apache deniega el acceso web con 403** al directorio, los CSV y la clave. No activar la captura de contactos si no se garantiza la protección.

Si se despliega desde el repositorio, el administrador debe generar **por su cuenta** la clave privada antes de la primera visita, usando un generador criptográfico local: `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;' > _private_downloads/admin_setup.key` (no ejecutar en la web, no subir el archivo a Git). Nunca usar claves públicas ni contraseñas compartidas.

## Publicación

Subir los cambios a la raíz de `os.socrates.la` en Ferozo, sin alterar `/crm/`, `/crm-demo/`, EMAILSEND ni MySQL. El código publicado en GitHub **no se despliega automáticamente** en Ferozo.

## Protección de datos

No recolectar emails para fines comerciales sin consentimiento independiente. Cumplir LGPD, limitar el acceso al administrador, eliminar registros al recibir una solicitud legítima y evitar backups expuestos. En el fichero CSV se registra la solicitud y el clic; no se guardan IPs en las listas ni datos de pago.
