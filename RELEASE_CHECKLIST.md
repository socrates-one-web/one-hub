# ONE HUB Community — Checklist de publicación

**Estado:** PRE-LANZAMIENTO. Este checklist no certifica que el código esté listo para producción.

## Software y derechos
- [x] Titular confirmó autorización de redistribución de CRM, Project Manager, Document Manager y EMAILSEND (25/10/2026)
- [ ] Inventariar bibliotecas, iconos, logos, imágenes, tipografías y licencias de terceros
- [x] Seleccionar AGPL-3.0-only para el código propio y añadir el texto oficial en `LICENSE`
- [ ] Verificar compatibilidad legal de las dependencias y registrar todos los avisos de terceros
- [ ] Confirmar que branding propio y marcas de terceros cumplen las condiciones de distribución

## Seguridad y privacidad
- [ ] Revisar todos los archivos con un escáner de secretos, incluidos los históricos de Git
- [ ] Excluir `config.php`, respaldos SQL de producción, tokens, sesiones, documentos privados y datos personales
- [ ] Probar CLEAN en una base MySQL nueva e independiente
- [ ] Probar DEMO con datos 100% ficticios y bases independientes
- [ ] Confirmar que la DEMO pública no envía email ni WhatsApp reales desde ninguno de los módulos
- [ ] Revisar autenticación, permisos, formularios, CSRF, carga y descarga de archivos
- [ ] Revisar instaladores y mecanismos de recuperación; impedir que se ejecuten sobre bases ya configuradas
- [ ] Proteger endpoints de cron, scripts de instalación y almacenamiento contra acceso web
- [ ] Definir rate limits, cuentas de acceso restringido y reinicio automatizado de la DEMO pública
- [ ] Revisión manual y pruebas de seguridad en entorno aislado

## PDF de cotizaciones — verificación del servidor
- [x] jsPDF 4.2.1 alojado localmente e íntegro en CLEAN y DEMO (420165 bytes cada uno; informado por servidor)
- [x] PDF DEMO descargado correctamente (`DEMO-COT-001.pdf`; comprobación visual de descarga, 25/10/2026)
- [x] Descargar un PDF real desde CLEAN (confirmado por el usuario el 25/10/2026)
- [ ] Revisar visualmente totales, tildes, logos y paginación en ambas versiones con casos representativos

## Candidato de distribución RC1 privado
- [x] Generados ZIP COMPLETO, CLEAN y DEMO para revisión privada con AGPL-3.0, DEMO V1.7 y PDF V2.1
- [x] Validación estática: 407 PHP y 25 JavaScript sin errores de sintaxis; archivos ZIP íntegros
- [x] No se encontraron configuraciones activas ni SQL de producción en estos ZIP
- [x] Instalación del ZIP COMPLETO RC1: CLEAN y DEMO arrancan, confirmado con capturas del usuario (25/10/2026)
- [ ] Probar los ZIP independientes CLEAN y DEMO si se publicarán separados
- [ ] Completar pruebas de módulos, permisos y archivos del paquete instalado antes de publicar una Release
- [x] Detectado SVG sin sanitizar; preparado parche RC1.1 para impedir nuevas cargas SVG por instalador y ajustes
- [x] Instalar parche RC1.1 (confirmación del usuario el 25/10/2026)
- [x] Funcionamiento general de CLEAN y DEMO confirmado por el usuario tras RC1.1 (25/10/2026)
- [ ] Ejecutar prueba específica de rechazo SVG y aceptación PNG/JPG/WEBP; examinar SVG existentes y otros archivos públicos

## Limpieza posterior a la instalación (instancias públicas)
- [ ] **Archivos de diagnóstico y de instalación:** comprobar que ya no son accesibles `verificar-servidor.php`, `crm/diagnostico.php`, `crm-demo/diagnostico.php`, `crm/install.php` y `crm-demo/install.php` una vez finalizada la instalación.
- [ ] Rechazo de nuevas cargas SVG con RC1.1 y aceptación de PNG/JPG/WEBP comprobados en el servidor.
- [ ] Comprobar que no quedan archivos SVG previamente subidos ni archivos personales accesibles directamente.

## Entregables
- [ ] Código fuente auditable y directorios documentados
- [ ] CLEAN: paquete de instalación sin registros comerciales
- [ ] DEMO: paquete con datos ficticios y transporte saliente desactivado
- [ ] `README.md`, `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md`, `CHANGELOG.md`
- [ ] Guías de instalación y uso en español, portugués brasileño e inglés
- [ ] Publicar los archivos en GitHub Releases y registrar checksum SHA-256
- [ ] Hacer instalación de prueba desde el mismo ZIP que recibirá la comunidad

## Difusión y financiación
- [ ] Perfil de GitHub Sponsors aprobado para el titular autorizado
- [ ] Método alternativo de aportes internacionales verificado y activo
- [ ] Añadir enlaces reales a `.github/FUNDING.yml` solo después de comprobarlos
- [ ] Publicar campaña de lanzamiento ES/PT/EN y video de demostración
- [ ] Definir proceso público para Issues, cambios y seguimiento de nuevas versiones

**No publicar como release estable ni activar la DEMO pública hasta completar los controles críticos.**
