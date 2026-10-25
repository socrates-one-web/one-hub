# Avisos de terceros — ONE HUB Community

**Estado:** inventario preliminar verificado sobre el paquete de instalación V1.2 y los parches V1.6–V1.8. Este archivo no reemplaza la revisión integral de dependencias pendiente antes de la Release pública.

## Código original

Copyright © 2026 Sócrates Creative Studio / Socrates One Web Design LTDA.

Licencia elegida por el titular: **GNU Affero General Public License, Version 3 (AGPL-3.0-only)**. Texto oficial en [LICENSE](LICENSE).

El código de terceros y los materiales identificados con una licencia propia se rigen por sus respectivos términos y no se relicencian por incluirse en ONE HUB.

## Bibliotecas externas identificadas

### jsPDF

- Proyecto: https://github.com/parallax/jsPDF
- Uso: generación de cotizaciones PDF a través de un cargador JavaScript remoto (CDN)
- Licencia: **MIT**
- Aviso de autoría del proyecto: James Hall y yWorks GmbH, entre otros contribuyentes según el archivo oficial
- Licencia completa: https://github.com/parallax/jsPDF/blob/master/LICENSE
- Versión detectada en la base V1.2: 2.5.1; actualización referenciada a 4.2.1 en parche V1.8, **pendiente de prueba funcional**
- **Atención:** el cargador obtiene la biblioteca desde un CDN, no equivale a incorporarla físicamente al ZIP.

### Fuentes tipográficas

| Familia | Fuente | Condiciones |
|---|---|---|
| Exo 2 | https://github.com/google/fonts/tree/main/ofl/exo2 | **SIL Open Font License 1.1** — https://github.com/google/fonts/blob/main/ofl/exo2/OFL.txt |
| Ubuntu Sans | https://github.com/google/fonts/tree/main/ufl/ubuntusans | **Ubuntu Font Licence 1.0** — https://github.com/google/fonts/blob/main/ufl/ubuntusans/LICENCE.txt |
| Ubuntu Condensed | https://github.com/google/fonts/tree/main/ufl/ubuntucondensed | **Ubuntu Font Licence 1.0** — https://github.com/google/fonts/blob/main/ufl/ubuntucondensed/UFL.txt |

Estas fuentes se cargan mediante Google Fonts en las páginas revisadas. Si en la versión final se incluyen archivos de fuentes, deberán acompañarse de sus licencias y atribuciones correspondientes.

## Servicios externos (no incluidos como software propio)

- Google Fonts
- Servicio externo de cotización de divisas `open.er-api.com`
- API para generar QR `api.qrserver.com`
- Otros servicios externos indicados por el administrador (por ejemplo SMTP)

El uso de servicios externos puede tener consecuencias de privacidad, conectividad y términos de uso. Revisar antes de ofrecer una DEMO abierta al público.

## Pendientes antes de publicar código

- [ ] Revisar exhaustivamente iconos, imágenes, marcas, fragmentos de terceros y otros paquetes.
- [ ] Conservar todos los avisos exigidos por terceros en el paquete distribuido.
- [ ] Verificar compatible uso de licencias junto a AGPL-3.0-only.
- [ ] Probar cotizaciones PDF de CLEAN y DEMO después del parche V1.8.
- [ ] Realizar auditoría final de seguridad, no solo revisión de sintaxis.

**Última revisión documental:** 2026-10-25.
