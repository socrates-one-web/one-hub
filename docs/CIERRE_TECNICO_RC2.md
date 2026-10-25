# ONE HUB Community — Cierre técnico RC2

> **Actualización 25/10/2026:** este informe conserva el historial técnico de RC2. La primera versión estable **ONE HUB Community v1.0.0** ya está publicada: https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0. La aprobación de las verificaciones críticas fue confirmada por el titular; no implica una certificación de seguridad independiente.


> **ESTADO ACTUAL (25/10/2026):** la versión candidata **v1.0.0-rc.2 está publicada en GitHub como pre-release**, con CLEAN, DEMO, COMPLETO y SHA256SUMS.txt. Enlace oficial: https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0-rc.2. El resto de esta página conserva documentación histórica de la preparación privada previa al lanzamiento. No es certificación de producción.

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

6. Protección adicional de acceso web a los directorios `includes/`, `sql/`, `data/` y `emailsend/includes/` de CLEAN y DEMO, con `.htaccess` propios aunque no exista mod_rewrite.

## Comprobaciones automatizadas

- Sintaxis: **404 archivos PHP y 25 JavaScript** sin errores.
- ZIP: integridad comprobada para COMPLETO, CLEAN y DEMO RC2.
- Demo seed: correos con dominios ficticios `demo.local` y `example.invalid`.
- Pruebas HTTP con servidor PHP de ambos crons: sin clave **403**; incorrecta **403**; correcta **200**.
- No hay `config.php` real ni respaldos productivos `admwebla_*.sql` en los paquetes RC2.
- Dependencia jsPDF 4.2.1 incluida localmente.

## Archivos RC2 privados, pendientes de aprobación

- **COMPLETO:** `ONE_HUB_COMMUNITY_COMPLETO_RC2_REVISION_PRIVADA.zip` SHA-256 `88c4860681f57b5314307e402d6e0a3ff0d2e42932e1a599ac3cd9d31e5396be`
- **CLEAN:** `ONE_HUB_COMMUNITY_CLEAN_RC2_REVISION_PRIVADA.zip` SHA-256 `755f1fa30ddc0fde504f53e29bcf3fb4b5c4587f2346da381e5e5cabd5bfa019`
- **DEMO:** `ONE_HUB_COMMUNITY_DEMO_RC2_REVISION_PRIVADA.zip` SHA-256 `ba436ab2c764a3a620c5130b93a6a98ee2b5baa6f06ac25d8efcfc85c7aff55d`

Estos archivos **no** están publicados en GitHub y esta documentación **no** garantiza seguridad integral.

## Bloqueantes de v1.0.0 estable

- [ ] Instalar los ZIP RC2 exactos en bases desechables y probar autenticación, roles, permisos, CSRF, subida/descarga de archivos y gestión documental.
- [ ] Verificar en Ferozo que SVG responde 403 y PNG/JPG/WEBP responde normalmente; probar los crons configurados.
- [ ] Auditar dinámicamente el código y componentes externos, y completar los avisos de terceros.
- [ ] Comprobar que la DEMO pública no envía comunicaciones a destinatarios reales, que aplica límites de abuso y que admite reinicio de datos.
- [ ] Subir el código público revisado, publicar Release y checksums.
- [ ] Activar GitHub Sponsors para la organización, verificar información bancaria/fiscal y URLs de donación.

**La existencia de una licencia en GitHub no equivale a que el código ya esté publicado.**


## Actualización de publicación de código — 25/10/2026
El texto precedente describe el estado **histórico anterior** a la publicación en GitHub. Posteriormente se publicó el código fuente RC2 en `main`, con 640 archivos y commit `151e528873a261da1a9c652017c4ffb630a62950`. Se confirmó por el titular que la instalación y autorización en Ferozo ya fueron probadas. La Release `v1.0.0-rc.2` existe como **borrador con cuatro adjuntos**, sin fecha de publicación y sin descargas públicas. No equivale a `v1.0.0` estable. Son tareas separadas la revisión legal final y la activación verificada de donaciones.
