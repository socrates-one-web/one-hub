> **ACTUALIZACIÓN (25/10/2026):** el código fuente RC2 ya fue publicado en `main` (commit `151e528`). Los tres paquetes ZIP y `SHA256SUMS.txt` ya están adjuntos al **borrador** de `v1.0.0-rc.2`. **No** vuelva a cargar el código ni cree una segunda Release. Abrir [Releases](https://github.com/socrates-one-web/one-hub/releases), editar el borrador existente, marcar **This is a pre-release** y elegir **Publish release**. Verificar luego los cuatro enlaces públicos y sus SHA-256. El patrocinio continúa sujeto a aprobación de la cuenta real. La versión estable aún no se autoriza.

# Publicación de ONE HUB Community y donaciones — pasos para el titular

El repositorio oficial es [socrates-one-web/one-hub](https://github.com/socrates-one-web/one-hub).

> **No publiques los ZIP RC2 como versión estable todavía:** antes hay que superar los controles de [CIERRE_TECNICO_RC2.md](CIERRE_TECNICO_RC2.md).

## 1. Subir el código auditado
Una vez aprobados los paquetes:
1. Abrir GitHub Desktop o Git para Windows y clonar el repositorio oficial.
2. Descomprimir el paquete auditado en el repositorio, respetando la estructura `crm/`, `crm-demo/`, `assets/`, `index.php`, `LICENSE`, `THIRD_PARTY_NOTICES.md`.
3. Conservar `.gitignore` del repositorio; ignora credenciales, backups y datos de ejecución, **pero no** la biblioteca local `crm/assets/js/vendor/jspdf.umd.min.js` y su equivalente DEMO.
4. Inspeccionar el listado de archivos a subir; **nunca** añadir `config.php`, `.env`, volcados privados `admwebla_*.sql`, logs ni archivos cargados por usuarios.
5. Hacer el commit y sincronizar `main` después de revisar los cambios. Una revisión por Pull Request es preferible.

## 2. Publicar GitHub Release
1. Abrir [Releases](https://github.com/socrates-one-web/one-hub/releases) y elegir **Draft a new release**.
2. Crear etiqueta `v1.0.0` **solo si la versión estable está validada**; antes usar `v1.0.0-rc.2` y marcar **pre-release**.
3. Adjuntar los archivos CLEAN, DEMO y COMPLETO aprobados, más `SHA256SUMS.txt`.
4. Incluir los requisitos mínimos, pasos de instalación, licencia AGPL-3.0 y advertencia de que DEMO no está preparada para envíos reales.
5. Guardar como **draft** hasta confirmar las comprobaciones finales; no activar “Publish release” prematuramente.

Referencia: https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository

## 3. Activar donaciones
Como el repositorio está bajo una organización, el propietario debe entrar a https://github.com/sponsors y seleccionar la **organización** correspondiente en el alta de patrocinio.

GitHub solicita perfil, datos fiscales, método de pago y 2FA. Tras la aprobación, crear niveles opcionales de patrocinio y verificar la URL real.

**No publicar** `.github/FUNDING.yml` con direcciones supuestas. Después de la aprobación se podrá añadir:
```yaml
github: [NOMBRE_REAL_VERIFICADO]
```
Con nombre de usuario/organización cuyo perfil de patrocinio esté activo. Para otra plataforma, utilizar `custom` solo con URL válida y verificada.

Referencia: https://docs.github.com/en/sponsors/receiving-sponsorships-through-github-sponsors/setting-up-github-sponsors-for-your-organization

## 4. Dar difusión
Solo cuando las descargas públicas sean reales y los enlaces de patrocinio funcionen, actualizar la landing https://os.socrates.la/ y comunicar por ES/PT/EN:
- Software gratuito y código fuente disponible con GNU AGPL-3.0.
- Dos ediciones CLEAN y DEMO, características y requisitos de servidor.
- Documentación e incidencias en GitHub.
- Patrocinio voluntario, sin bloquear funciones gratuitas.

## Verificaciones finales
- [ ] Código auditado y subido al repositorio
- [ ] Release con paquetes y hashes, publicada y descargable
- [ ] Guía de instalación ensayada con ZIP descargado de la Release
- [ ] Landing enlaza a Release y repositorio correctos
- [ ] Patrocinio activo con pagos comprobados
- [ ] Anuncio internacional publicado
