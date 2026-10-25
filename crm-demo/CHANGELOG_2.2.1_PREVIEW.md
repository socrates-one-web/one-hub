# SÓCRATES CRM v2.2.1 — Document Preview Fix

- Nuevo endpoint seguro `document_preview.php`.
- Se elimina la dependencia del `iframe` sobre `document_download.php` para generar vistas previas.
- PDF e imágenes usan renderizado nativo del navegador.
- TXT, Markdown, logs y otros textos se muestran con escape seguro y límite de tamaño.
- CSV se representa como tabla.
- DOCX, XLSX y PPTX se previsualizan localmente usando `ZipArchive` cuando está disponible.
- ZIP muestra su contenido sin extraerlo.
- Los formatos binarios antiguos muestran un estado útil en vez de un visor roto.
- `storage/documents/` continúa sin acceso web directo.
- No requiere migración de base de datos.
