# Dependencias y licencias de terceros — auditoría preliminar

**Estado:** revisión parcial, NO constituye autorización legal general ni inventario final. Fecha: 2026-10-25.

La revisión está basada en el paquete distribuidor ONE HUB OS V1.2 y parches V1.6/V1.7 de prepublicación; la instalación del servidor puede diferir.

| Elemento | Ubicación / uso | Licencia / acción |
| --- | --- | --- |
| jsPDF 2.5.1 | `crm/assets/js/quote-pdf.js`, `crm-demo/assets/js/quote-pdf.js` (cargador UMD por CDN para PDF) | **MIT**. Tiene vulnerabilidades conocidas. Preparado un parche que referencia **4.2.1**, pendiente de validación de exportación real. |
| Google Fonts — Exo 2 | Estilos CSS de landing y CRM; fuente cargada por Google Fonts | **SIL Open Font License 1.1**. Mantener atribuciones/avisos cuando corresponda; no modificar ni redistribuir archivos sin revisar sus términos. |
| Google Fonts — Ubuntu Sans | Estilos de landing y CRM | **Ubuntu Font Licence 1.0** (Canonical). |
| Google Fonts — Ubuntu Condensed | Estilos de landing | Revisar la licencia exacta y sus avisos. |
| Logo ONE HUB y Sócrates Creative Studio | Recursos gráficos de la aplicación y landing | El titular confirma derechos de redistribución del proyecto; registrar avisos de copyright y tratamiento de marcas. |

## Fuentes de referencia

- AGPL-3.0: https://www.gnu.org/licenses/agpl-3.0.en.html
- jsPDF: https://github.com/parallax/jsPDF
- jsPDF 4.2.1: https://github.com/parallax/jsPDF/releases/tag/v4.2.1
- Advisory jsPDF: https://github.com/advisories/GHSA-wfv2-pwc8-crg5
- Exo 2 (OFL-1.1): https://fontsource.org/fonts/exo-2/about
- Ubuntu Sans (UFL-1.0): https://github.com/google/fonts/blob/main/ufl/ubuntusans/METADATA.pb

## Pendientes bloqueantes

- [ ] Auditar y enumerar **todas** las bibliotecas, fragmentos de código externo, iconos y contenidos incluidos.
- [ ] Confirmar las atribuciones y licencias para materiales redistribuidos efectivamente (no confundir fuente remota con fuente empaquetada).
- [ ] Completar `THIRD_PARTY_NOTICES.md` y el archivo `LICENSE` del proyecto.
- [ ] Validar la compatibilidad entre AGPL-3.0 y licencias de cualquier tercero incluido.
- [ ] Aplicar y **probar funcionalmente** el parche jsPDF 4.2.1 antes de una Release pública.

El código fuente aún no se ha publicado porque el proceso de cumplimiento y seguridad continúa abierto.
