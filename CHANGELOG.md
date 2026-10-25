## v1.0.0 — Stable Release pública (2026-10-25)

- **Primera versión estable** publicada en GitHub: https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0.
- CLEAN, DEMO y COMPLETO disponibles como ZIP, junto con archivo de checksums SHA-256.
- Los tres ZIP coinciden por tamaño y huella SHA-256 con los paquetes estables preparados anteriormente.
- El titular confirmó completados los controles críticos de seguridad, licencias y validación de instalación; sin afirmar auditoría de terceros.
- **Sin alteraciones funcionales respecto de RC2**; se actualizaron las instrucciones de distribución y paquetes ZIP internos del COMPLETO.
- La pre-release histórica `v1.0.0-rc.2` permanece disponible.

## v1.0.0-rc.2 — Pre-release pública en GitHub (2026-10-25)

- Código fuente RC2 completo publicado en `main` con CLEAN, DEMO, EMAILSEND, landing y jsPDF offline.
- GitHub Release **pública, marcada como pre-release**, con CLEAN, DEMO, COMPLETO y `SHA256SUMS.txt`: https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0-rc.2.
- El titular confirmó haber probado instalación y autorización en Ferozo.
- GitHub confirma que los cuatro adjuntos están en estado `uploaded` y publica su huella SHA-256. Sigue pendiente comprobar una descarga externa completa y activar los canales de donación reales.

# CHANGELOG — ONE HUB Community

Este registro conserva las notas históricas de la preparación RC1/RC2 y documenta la primera versión estable publicada.

## [Unreleased] — v1.0.0-RC1 (revisión privada, 2026-10-25)

### Preparado para revisión

- Ediciones CLEAN y DEMO compuestas a partir de ONE HUB Open Source V1.2.
- Landing multilingüe ES/PT-BR/EN actualizada con 10 capturas y módulo EMAILSEND.
- Bloqueos de comunicaciones salientes para DEMO (V1.7).
- jsPDF actualizado a 4.2.1 e incluido localmente en CLEAN y DEMO (PDF offline V2.1).
- Archivo legal AGPL-3.0-only para el código original y avisos preliminares de terceros.
- Pantalla de licencia del CRM ajustada para no indicar “licencia pendiente”.
- Tres ZIP candidatos privados (COMPLETO, CLEAN, DEMO) generados y comprobados por integridad.
- Pruebas de sintaxis locales sin errores: 407 PHP y 25 JavaScript propios.
- Prueba HTTP local positiva de landing, instaladores y recursos PDF (sin conectar MySQL).

### Por validar antes de una Release pública

- Instalación desde cero de **estos ZIP exactos** con bases MySQL nuevas y aisladas.
- Pruebas dinámicas de seguridad y autorizaciones; revisión de cargas de archivos SVG.
- Revisión completa de derechos/avisos de dependencias, imágenes y demás materiales.
- Revisión de datos de demostración, protecciones contra envíos y cuentas de acceso público.
- Auditoría de secretos con herramientas especializadas y pruebas de integración.
- Configuración verificada de patrocinios internacionales antes de publicar enlaces.

Consulta [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md) y [la tarea de publicación](https://github.com/socrates-one-web/one-hub/issues/3).
