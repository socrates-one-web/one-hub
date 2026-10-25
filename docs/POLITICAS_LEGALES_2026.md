# ONE HUB Community — Políticas legales ampliadas (25/10/2026)

Versión estable v1.0.0. Los avisos se presentan en español, portugués brasileño e inglés.

## Archivos
- [`privacidad.php`](../privacidad.php) — finalidad del registro, campos, bases legales LGPD, proveedores, derechos y plazos de conservación.
- [`terminos.php`](../terminos.php) — condiciones del sitio y resumen de los derechos/obligaciones de la licencia AGPL-3.0-only.
- [`cookies.php`](../cookies.php) — cookies de sesión, idioma y conexiones externas.
- [`legal_template.php`](../legal_template.php) — diseño, índice y selector de idioma.

## Comprobación y configuración
1. Copiar los cuatro PHP a la raíz de `os.socrates.la` tras crear un respaldo; GitHub **no** realiza un despliegue automático en Ferozo.
2. Verificar `/privacidad.php?lang=es|pt|en`, `/terminos.php?lang=es|pt|en` y `/cookies.php?lang=es|pt|en` por separado.
3. Confirmar la efectividad del bloqueo HTTP sobre `/_private_downloads/` y limitar el acceso a CSV y administradores.
4. Llevar a cabo la revisión/eliminación periódica de registros: el plazo objetivo de 12 meses de la política **no se automatiza con estos avisos**.
5. Revisar jurídicamente con un profesional habilitado en Brasil las bases legales del tratamiento, los plazos reales de logs/backup, obligaciones de encarregado y contratos con proveedores.

## Principios
- El formulario de descarga exige nombre y correo solo para ese canal; **el repositorio público permanece accesible sin registro**.
- El panel registra clics a GitHub, sin verificar descargas completas.
- Aportaciones de GitHub Sponsors, Stripe y Mercado Pago son opcionales y las pasarelas externas procesan pagos.
- La AGPL-3.0-only gobierna los derechos del código original, incluidos uso comercial, modificación y redistribución, y la obligación pertinente de ofrecer código de versiones modificadas usadas por red.
- Los activos y bibliotecas de terceros conservan sus respectivas condiciones y avisos.

Fuentes de referencia: [LGPD, Ley 13.709/2018](https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709compilado.htm), [Marco Civil da Internet](https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2014/lei/l12965.htm), [AGPL v3](https://www.gnu.org/licenses/agpl-3.0.en.html).
