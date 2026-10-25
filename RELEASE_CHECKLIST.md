# ONE HUB Community — Checklist de publicación

**Estado:** RC2 pública publicada el 25/10/2026 (https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0-rc.2). No certifica que el código esté listo para producción.

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

## Candidato RC1.1 (distribución privada)
- [x] Generados y verificados candidatos privados RC1.1 COMPLETO, CLEAN y DEMO, con parche de logos incorporado
- [x] Sintaxis 407 PHP y 25 JS propios; integridad ZIP e inspección preliminar de patrones de credenciales
- [ ] Auditoría dinámica e instalación de los ZIP RC1.1 exactos antes de publicación pública

## RC2 privado y refuerzo de seguridad
- [x] RC2 privado completo, CLEAN y DEMO generados; nueva protección Apache/IIS de archivos subidos y carpetas privadas
- [x] Pruebas locales: SVG y PHP subidos 403, PNG 200; crons con clave vacía/incorrecta 403 y válida 200
- [x] Biblioteca jsPDF incluida y `.gitignore` corregido para conservar los recursos dentro de `assets/js/vendor/`
- [x] Prueba real de instalación y autorización en Ferozo completada (confirmación del titular, 25/10/2026); las verificaciones no especificadas permanecen independientes

## Entregables
- [x] Código fuente RC2 público en `main` (CLEAN, DEMO, landing y EMAILSEND), verificado 25/10/2026
- [x] CLEAN RC2 empaquetado y adjuntado a una Release en borrador (aún sin descarga pública)
- [x] DEMO RC2 empaquetado y adjuntado a una Release en borrador (aún sin descarga pública)
- [x] `README.md`, `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md`, `CHANGELOG.md`
- [ ] Guías de instalación y uso en español, portugués brasileño e inglés
- [x] Publicar CLEAN, DEMO, COMPLETO y SHA256SUMS.txt en GitHub Release `v1.0.0-rc.2` como pre-release (25/10/2026)
- [ ] Hacer instalación de prueba desde el mismo ZIP que recibirá la comunidad

## Difusión y financiación
- [ ] Perfil de GitHub Sponsors aprobado para el titular autorizado
- [ ] Método alternativo de aportes internacionales verificado y activo
- [ ] Añadir enlaces reales a `.github/FUNDING.yml` solo después de comprobarlos
- [ ] Publicar campaña de lanzamiento ES/PT/EN y video de demostración
- [ ] Definir proceso público para Issues, cambios y seguimiento de nuevas versiones

**No publicar como release estable ni activar la DEMO pública hasta completar los controles críticos.**

## Registro de publicación GitHub (25/10/2026)
- [x] Código fuente incluido en 640 archivos del repositorio, 300 por edición; commit `151e528873a261da1a9c652017c4ffb630a62950`.
- [x] jsPDF 4.2.1 local visible en los dos módulos `assets/js/vendor/`.
- [x] Cuatro adjuntos (COMPLETO, CLEAN, DEMO y SHA256SUMS.txt) están en el borrador de `v1.0.0-rc.2`.
- [x] Publicar el borrador como **pre-release**; GitHub confirma URL pública, cuatro adjuntos y SHA-256 asociados.
- [ ] Comprobar descargas externas integrales, contrastar hash SHA-256 del archivo recibido y actualizar los enlaces desde os.socrates.la.
- [ ] Verificar el perfil de GitHub Sponsors y agregar enlace real (sin URL inventada).
