# ONE HUB Community

**Open-source business management platform · Plataforma empresarial de código abierto**

[Official website / Sitio oficial](https://os.socrates.la/) · [Installation guide / Manual](https://os.socrates.la/manual.php) · [Issues](https://github.com/socrates-one-web/one-hub/issues) · [**Download STABLE v1.0.0**](https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0)

**Source code published · Código fuente publicado · Código-fonte publicado**

ONE HUB Community is developed by **Sócrates Creative Studio / Socrates One Web Design LTDA**. Original project code is distributed under **GNU AGPL-3.0-only**; third-party materials retain their respective terms as stated in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## ES — Español

ONE HUB Community integra CRM, clientes, contactos, oportunidades, ventas, cotizaciones multimoneda y PDF, contratos, proyectos, tareas, calendario, documentos, finanzas, reportes, dashboards y **EMAILSEND** (contactos, listas, campañas, SMTP y métricas).

**Estado 25/10/2026:** El código fuente completo se encuentra publicado en `main` y **ONE HUB Community `v1.0.0` ya está disponible como Release estable pública** desde el 25/10/2026. Los paquetes CLEAN, DEMO y COMPLETO están adjuntos con SHA-256 comprobables. RC2 permanece disponible como pre-release histórica.

**Ediciones:**
- **CLEAN:** instalación sin datos comerciales.
- **DEMO:** datos ficticios y protecciones para impedir envíos reales; no habilitar SMTP ni usarla como entorno productivo.
- **COMPLETO:** landing y ambas ediciones.

**Requisitos:** PHP 8.1+, MySQL, PDO MySQL, OpenSSL, Fileinfo, mbstring y Apache compatible con las reglas de protección incluidas. Utiliza bases independientes y vacías, credenciales propias, configura una `cron_key` segura y elimina o bloquea el instalador al finalizar. Revisa [la documentación de instalación](docs/INSTALACION_PLANIFICADA.md) antes de desplegar.

El titular confirmó las pruebas de instalación y autorización en Ferozo. Esto no reemplaza la revisión de seguridad de cada despliegue ni garantiza ausencia de vulnerabilidades. La publicación `v1.0.0` estable cuenta con la aprobación de las verificaciones críticas declaradas por el titular y registradas en [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md). Dicha aprobación no equivale a una certificación de seguridad por terceros.

## PT-BR — Português

ONE HUB Community reúne CRM, clientes, vendas, propostas multimoeda com PDF, contratos, projetos, tarefas, calendário, documentos, finanças, relatórios, dashboards e **EMAILSEND**.

**Situação em 25/10/2026:** O código-fonte completo está público em `main` e **ONE HUB Community `v1.0.0` foi publicado como versão estável**, com downloads CLEAN, DEMO, COMPLETO e checksums SHA-256. A versão RC2 continua disponível no histórico.

**Edições:** CLEAN (sem dados comerciais), DEMO (dados fictícios e envios externos restringidos) e COMPLETO (site + as duas edições). Requisitos: PHP 8.1+, MySQL/PDO, OpenSSL, Fileinfo, mbstring e regras de proteção Apache. Use bancos de dados novos e independentes, senhas próprias e uma `cron_key` segura; remova ou bloqueie o instalador após a configuração. Confira [a documentação](docs/INSTALACION_PLANIFICADA.md) e [a segurança](SECURITY.md).

## EN — English

ONE HUB Community combines CRM, contacts, sales, quotations and PDF export, contracts, project management, tasks, calendars, document management, finance, reporting, dashboards and **EMAILSEND**.

**Status as of 25 October 2026:** **ONE HUB Community `v1.0.0` is now published as a public stable GitHub Release**, with CLEAN, DEMO and COMPLETO downloads and SHA-256 checksums. The full source code is on `main`; RC2 remains available as a historic release candidate.

**Editions:** CLEAN (no business records), DEMO (fictional seed data and outbound messaging protections), and COMPLETO (website and both editions). Requires PHP 8.1+, MySQL/PDO, OpenSSL, Fileinfo, mbstring, and correctly enforced Apache access controls. Use separate new databases, strong credentials, a secure `cron_key`, and remove or block the installer after setup. See the [installation notes](docs/INSTALACION_PLANIFICADA.md) and [security policy](SECURITY.md).

## Descargar / Baixar / Download — v1.0.0 STABLE

**Publicada / Publicada / Published: 25 October 2026.** [Official Release](https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0).

| Edición / Edition | Descarga / Download | SHA-256 |
| --- | --- | --- |
| **CLEAN** | [ONE_HUB_COMMUNITY_CLEAN_v1.0.0.zip](https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0/ONE_HUB_COMMUNITY_CLEAN_v1.0.0.zip) | `f2d2634e6dfd28a3968c548809e4649069e6654d413516ed44063920119eed5f` |
| **DEMO** | [ONE_HUB_COMMUNITY_DEMO_v1.0.0.zip](https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0/ONE_HUB_COMMUNITY_DEMO_v1.0.0.zip) | `826c360bd1c5a68b29161d783b77f591e722a191570bdc6f8e40e6b8e057aed6` |
| **COMPLETO** | [ONE_HUB_COMMUNITY_COMPLETO_v1.0.0.zip](https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0/ONE_HUB_COMMUNITY_COMPLETO_v1.0.0.zip) | `bbf703610f51ace723e1aa1065a263a6cc53c2559ac82a43df6b48321b63cd92` |
| **SHA256SUMS** | [Checksum](https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0/ONE_HUB_COMMUNITY_v1.0.0_SHA256SUMS.txt) | `dc9e211c2c38af283c2523d002d055928665285cb95767a7ff6e6d303d66dbab` |

Los SHA-256 de esta tabla provienen de los adjuntos oficiales de GitHub. Verificar el checksum tras descargar. / Confira a soma SHA-256 após baixar. / Verify the SHA-256 checksum after downloading.

[RC2 — versión candidata anterior / previous release candidate](https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0-rc.2)

## Source, contributions and funding

- Source: [GitHub main](https://github.com/socrates-one-web/one-hub/tree/main)
- License: [GNU AGPL-3.0-only](LICENSE)
- Third-party components: [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md)
- Security reporting: [SECURITY.md](SECURITY.md)
- Contributions: [CONTRIBUTING.md](CONTRIBUTING.md)
- Release checklist: [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md)
- **GitHub Sponsors está activo / is live:** [Apoyar ONE HUB / Sponsor ONE HUB](https://github.com/sponsors/socrates-one-web). Aportes voluntarios; el software y todas sus funciones continúan gratuitos.
- **Stripe:** [Contribuir por Stripe](https://donate.stripe.com/4gMfZj4Z1aFPaZQa4Cdwc00).
- **Mercado Pago (Brasil):** [Contribuir por Mercado Pago](https://link.mercadopago.com.br/socratesone).
- Todos los aportes son voluntarios. Los enlaces de cobro de Stripe y Mercado Pago fueron facilitados por el titular y su funcionamiento de pago debe validarse en las plataformas.

**Maintainer:** Sócrates Creative Studio / Socrates One Web Design LTDA · https://os.socrates.la/
