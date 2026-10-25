# ONE HUB Community · Descargas GitHub y formulario de registro

**Versión:** landing 1.6.2 · GitHub Release estable v1.0.0. Implementación de código (25/10/2026).

## Flujo del usuario

1. En `index.php` los botones CLEAN y DEMO dirigen a `descargar.php`, no a ZIP locales.
2. El visitante introduce nombre, correo y edición, y confirma que ha leído `privacidad.php`.
3. El servidor valida CSRF, campo antispam y formato de datos; registra la solicitud en el fichero **privado** `_private_downloads/lead_requests.csv`.
4. Tras registrarse, el usuario ve CLEAN, DEMO y COMPLETO con enlaces directos a los archivos publicados en https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0.
5. El código fuente del proyecto continúa libremente disponible en GitHub, sin obligación de registro.

## Despliegue

El cambio está en el repositorio de GitHub, pero **no se refleja por sí solo en Ferozo**. El titular deberá hacer copia de seguridad y publicar los siguientes archivos en el Document Root de https://os.socrates.la/:

- `index.php`
- `assets/js/landing.js`
- `assets/css/landing.css`
- `descargar.php`
- `manual.php`
- `privacidad.php`
- `_private_downloads/.htaccess`
- `_private_downloads/index.php`

No reemplazar `crm/`, `crm-demo/`, EMAILSEND ni bases MySQL.

## Protección y privacidad

**CRÍTICO:** el directorio `_private_downloads/` depende de las reglas Apache `.htaccess`. Antes de habilitar el formulario, comprobar en navegador anónimo que `https://os.socrates.la/_private_downloads/` y la URL del archivo CSV devuelven **403 Forbidden**. Si son accesibles, no activar registro hasta corregir Apache. La carpeta debe ser escribible por PHP; el código de la aplicación no permite descargar ese CSV por HTTP.

El CSV almacena fecha UTC, nombre, email, edición e idioma. Consultar por el panel de archivos del hosting/SFTP. No usar los emails para campañas publicitarias sin consentimiento independiente. Revisar la retención de datos y atender solicitudes de eliminación según la política del sitio.

Los enlaces privados de contribuciones no se almacenan aquí. Las tres plataformas públicas visibles en `#contribuir` son GitHub Sponsors, Stripe y Mercado Pago.

## Pruebas

Las pruebas de sintaxis PHP y JS y el flujo básico de registro/redirección a GitHub se realizaron localmente. El despliegue en Ferozo y la comprobación Apache de denegación de acceso al CSV están pendientes.
