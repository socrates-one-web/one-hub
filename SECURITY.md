# Security policy / Política de seguridad

## Estado

ONE HUB se encuentra en revisión antes de su publicación pública. **Todavía no se ha publicado una versión de código abierto para producción.**

## Reportar una vulnerabilidad

No publiques contraseñas, archivos de configuración, datos personales ni pruebas de explotación en Issues públicos.

Comunica primero el problema al responsable del proyecto por un canal privado acordado a través de [Sócrates Creative Studio](https://os.socrates.la/). Se habilitará un canal formal de reporte privado antes del lanzamiento.

Incluye una descripción del problema, versión afectada, impacto, pasos de reproducción seguros y una propuesta de mitigación, si existe.

## Antes de instalar

- Usa una base de datos nueva e independiente para CRM y EMAILSEND.
- Mantén fuera del repositorio claves SMTP, de API, credenciales y respaldos con datos reales.
- Impide el acceso web a directorios privados y archivos SQL.
- Desactiva los envíos reales en entornos de demostración.
- Actualiza PHP y aplica permisos mínimos a archivos y usuarios.
