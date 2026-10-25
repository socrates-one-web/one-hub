# ONE HUB Community — Cierre técnico RC2

**Fecha:** 25/10/2026 · **Estado:** Candidato privado; todavía NO es una Release pública.

## Lo que el usuario ha validado

- ONE HUB CLEAN y DEMO funcionan después de instalar el RC1 y aplicar RC1.1 en Ferozo.
- Las cotizaciones PDF se descargan en ambas ediciones mediante jsPDF 4.2.1 local.
- El titular confirma derechos de publicación del código propio de CRM, Project Manager, Document Manager y EMAILSEND.
- La licencia elegida para el código propio es **GNU AGPL-3.0-only**, cuyo texto está en `LICENSE`.

## Mejoras RC2 (solo en los ZIP candidatos privados)

1. `crm/uploads/.htaccess` y `crm-demo/uploads/.htaccess`: acceso web a SVG, SVGZ, archivos HTML y scripts bloqueado. En una prueba Apache 2.4 local: SVG **403**, PHP **403**, PNG **200**.
2. `uploads/web.config` de ambas ediciones: restricciones de extensiones para IIS.
3. `cron/document_alerts.php` de ambas ediciones: exige `cron_key` no vacía si la llamada es HTTP; CLI conserva acceso.
4. `cron/security_maintenance.php` de ambas ediciones: impide acceso HTTP con clave vacía o de ejemplo.
5. Eliminación de `diagnostico.php` (ambas ediciones) y `verificar-servidor.php` en los paquetes candidatos de distribución.

## Comprobaciones automatizadas

- Sintaxis: **404 archivos PHP y 25 JavaScript** sin errores.
- ZIP: integridad comprobada para COMPLETO, CLEAN y DEMO RC2.
- Demo seed: correos con dominios ficticios `demo.local` y `example.invalid`.
- Pruebas HTTP con servidor PHP de ambos crons: sin clave **403**; incorrecta **403**; correcta **200**.
- No hay `config.php` real ni respaldos productivos `admwebla_*.sql` en los paquetes RC2.
- Dependencia jsPDF 4.2.1 incluida localmente.

## Archivos RC2 privados, pendientes de aprobación

- **COMPLETO:** `ONE_HUB_COMMUNITY_COMPLETO_RC2_REVISION_PRIVADA.zip` SHA-256 `7d8d9aa68765d72c83ffda9b90be393ee2d50a3a9769e8cc4a27cae103588edb`
- **CLEAN:** `ONE_HUB_COMMUNITY_CLEAN_RC2_REVISION_PRIVADA.zip` SHA-256 `f9821704b5ea875c7964cf5e22bae0db392575864581a39343b67e0f7b6d83b9`
- **DEMO:** `ONE_HUB_COMMUNITY_DEMO_RC2_REVISION_PRIVADA.zip` SHA-256 `33f6de2d9666809e5a722181b213f5efcae1e2f2fab1b8a569382bccb079b575`

Estos archivos **no** están publicados en GitHub y esta documentación **no** garantiza seguridad integral.

## Bloqueantes de v1.0.0 estable

- [ ] Instalar los ZIP RC2 exactos en bases desechables y probar autenticación, roles, permisos, CSRF, subida/descarga de archivos y gestión documental.
- [ ] Verificar en Ferozo que SVG responde 403 y PNG/JPG/WEBP responde normalmente; probar los crons configurados.
- [ ] Auditar dinámicamente el código y componentes externos, y completar los avisos de terceros.
- [ ] Comprobar que la DEMO pública no envía comunicaciones a destinatarios reales, que aplica límites de abuso y que admite reinicio de datos.
- [ ] Subir el código público revisado, publicar Release y checksums.
- [ ] Activar GitHub Sponsors para la organización, verificar información bancaria/fiscal y URLs de donación.

**La existencia de una licencia en GitHub no equivale a que el código ya esté publicado.**
