# Preparación de ONE HUB Community v1.0.0 estable

**25 de octubre de 2026 · Estado: lista para crear una nueva Release estable.**
**IMPORTANTE:** este documento no implica que la etiqueta v1.0.0 ya se haya publicado. Antes de anunciarla, comprobar que existe realmente la Release GitHub.

## Aprobación del titular

El titular de Sócrates Creative Studio confirmó que las tres verificaciones críticas declaradas para pasar de RC2 a versión estable están resueltas:

- Seguridad y privacidad de ONE HUB Community.
- Derechos de redistribución, licencias y avisos de terceros.
- Pruebas reales de instalación, autorización y correspondencia de los ZIP en Ferozo.

Esta constancia recoge la aprobación del titular; no declara una certificación de seguridad externa ni atribuye pruebas independientes no realizadas por este asistente.

## Procedencia técnica

Se reutilizó exactamente el contenido de los archivos funcionales de los tres paquetes públicos `v1.0.0-rc.2`. Los nuevos paquetes `v1.0.0` modifican exclusivamente:

- `README.md` en CLEAN, DEMO y COMPLETO, para indicar versión estable.
- Los ZIP anidados `downloads/ONE_HUB_COMMUNITY_CLEAN_OS.zip` y `downloads/ONE_HUB_COMMUNITY_DEMO_OS.zip` dentro de COMPLETO, para incluir la misma documentación actualizada.

Todos los demás miembros ZIP conservan idéntico contenido binario respecto de RC2.

## Archivos de nueva distribución v1.0.0

| Edición | Archivo | SHA-256 |
| --- | --- | --- |
| CLEAN | `ONE_HUB_COMMUNITY_CLEAN_v1.0.0.zip` | `f2d2634e6dfd28a3968c548809e4649069e6654d413516ed44063920119eed5f` |
| DEMO | `ONE_HUB_COMMUNITY_DEMO_v1.0.0.zip` | `826c360bd1c5a68b29161d783b77f591e722a191570bdc6f8e40e6b8e057aed6` |
| COMPLETO | `ONE_HUB_COMMUNITY_COMPLETO_v1.0.0.zip` | `bbf703610f51ace723e1aa1065a263a6cc53c2559ac82a43df6b48321b63cd92` |

Subir también `ONE_HUB_COMMUNITY_v1.0.0_SHA256SUMS.txt`.

## Pasos de publicación en GitHub

1. Abrir https://github.com/socrates-one-web/one-hub/releases/new
2. En **Choose a tag**, escribir `v1.0.0` y elegir **Create new tag on publish**, sobre `main`.
3. Título: **ONE HUB Community v1.0.0 — Stable Release**.
4. Adjuntar los tres ZIP nuevos y `ONE_HUB_COMMUNITY_v1.0.0_SHA256SUMS.txt`.
5. Pegar las notas de Release incluidas con el kit; no marcar **Set as a pre-release**. Si aparece **Set as the latest release**, dejar habilitado.
6. Publicar y comprobar las cuatro URLs públicas y SHA-256.
7. Mantener la antigua `v1.0.0-rc.2` como historia, sin borrarla.
8. Actualizar la página https://os.socrates.la/ y la documentación para apuntar a las descargas estables.

Las donaciones, la publicidad y GitHub Sponsors son iniciativas opcionales, independientes de la versión estable.
