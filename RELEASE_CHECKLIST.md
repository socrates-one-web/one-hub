# ONE HUB Community — Checklist de publicación

**Estado:** PRE-LANZAMIENTO. Este checklist no certifica que el código esté listo para producción.

## Software y derechos
- [ ] Confirmar titularidad y autorización para redistribuir el código de CRM, Project Manager, Document Manager y EMAILSEND
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
