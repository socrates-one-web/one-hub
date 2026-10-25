# ONE HUB Community

**Open-source business management platform · Plataforma empresarial de código abierto**

[Official website / Sitio oficial](https://os.socrates.la/) · [Installation guide / Manual](https://os.socrates.la/manual.php) · [Issues](https://github.com/socrates-one-web/one-hub/issues) · [**Download v1.0.0-rc.2**](https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0-rc.2)

**Source code published · Código fuente publicado · Código-fonte publicado**

ONE HUB Community is developed by **Sócrates Creative Studio / Socrates One Web Design LTDA**. Original project code is distributed under **GNU AGPL-3.0-only**; third-party materials retain their respective terms as stated in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## ES — Español

ONE HUB Community integra CRM, clientes, contactos, oportunidades, ventas, cotizaciones multimoneda y PDF, contratos, proyectos, tareas, calendario, documentos, finanzas, reportes, dashboards y **EMAILSEND** (contactos, listas, campañas, SMTP y métricas).

**Estado 25/10/2026:** el código fuente RC2 completo de CLEAN, DEMO y landing ya está disponible en la rama `main`. La versión **`v1.0.0-rc.2` fue publicada el 25/10/2026 como pre-release pública**, con tres instaladores y sus checksums. Todavía **no** existe la versión estable `v1.0.0`.

**Ediciones:**
- **CLEAN:** instalación sin datos comerciales.
- **DEMO:** datos ficticios y protecciones para impedir envíos reales; no habilitar SMTP ni usarla como entorno productivo.
- **COMPLETO:** landing y ambas ediciones.

**Requisitos:** PHP 8.1+, MySQL, PDO MySQL, OpenSSL, Fileinfo, mbstring y Apache compatible con las reglas de protección incluidas. Utiliza bases independientes y vacías, credenciales propias, configura una `cron_key` segura y elimina o bloquea el instalador al finalizar. Revisa [la documentación de instalación](docs/INSTALACION_PLANIFICADA.md) antes de desplegar.

El titular confirmó las pruebas de instalación y autorización en Ferozo. Esto no reemplaza la revisión de seguridad de cada despliegue ni garantiza ausencia de vulnerabilidades. La versión RC se publica para evaluación; la etiqueta estable requiere cerrar los controles de [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md).

## PT-BR — Português

ONE HUB Community reúne CRM, clientes, vendas, propostas multimoeda com PDF, contratos, projetos, tarefas, calendário, documentos, finanças, relatórios, dashboards e **EMAILSEND**.

**Situação em 25/10/2026:** o código-fonte RC2 está público em `main`. Os instaladores `v1.0.0-rc.2` estão em um **rascunho de Release**, ainda sem downloads públicos. A versão estável `v1.0.0` ainda não foi publicada.

**Edições:** CLEAN (sem dados comerciais), DEMO (dados fictícios e envios externos restringidos) e COMPLETO (site + as duas edições). Requisitos: PHP 8.1+, MySQL/PDO, OpenSSL, Fileinfo, mbstring e regras de proteção Apache. Use bancos de dados novos e independentes, senhas próprias e uma `cron_key` segura; remova ou bloqueie o instalador após a configuração. Confira [a documentação](docs/INSTALACION_PLANIFICADA.md) e [a segurança](SECURITY.md).

## EN — English

ONE HUB Community combines CRM, contacts, sales, quotations and PDF export, contracts, project management, tasks, calendars, document management, finance, reporting, dashboards and **EMAILSEND**.

**Status as of 25 October 2026:** audited-for-packaging RC2 source is public on `main`. The **`v1.0.0-rc.2` distribution was published on 25 October 2026 as a public pre-release** with three downloadable installers and checksums. No stable `v1.0.0` release has been published.

**Editions:** CLEAN (no business records), DEMO (fictional seed data and outbound messaging protections), and COMPLETO (website and both editions). Requires PHP 8.1+, MySQL/PDO, OpenSSL, Fileinfo, mbstring, and correctly enforced Apache access controls. Use separate new databases, strong credentials, a secure `cron_key`, and remove or block the installer after setup. See the [installation notes](docs/INSTALACION_PLANIFICADA.md) and [security policy](SECURITY.md).

## Descargar / Baixar / Download — v1.0.0-rc.2

**Versión candidata / Versão candidata / Release candidate — not certified for production.**

| Paquete / Package | Descarga / Download | SHA-256 |
|---|---|---|
| **CLEAN** | [ZIP CLEAN](https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0-rc.2/ONE_HUB_COMMUNITY_CLEAN_v1.0.0-rc.2.zip) | `b9a0929b8d7449a7c3ee4b37a689b7acf4aba4c16d92d8eb2ed41d66742e16b0` |
| **DEMO** | [ZIP DEMO](https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0-rc.2/ONE_HUB_COMMUNITY_DEMO_v1.0.0-rc.2.zip) | `19584f3eaf2217cff0928b8dcd6e7889b94c42305fdb03fe771b0e1a14f83674` |
| **COMPLETO** | [ZIP COMPLETO](https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0-rc.2/ONE_HUB_COMMUNITY_COMPLETO_v1.0.0-rc.2.zip) | `b0e0598d9d0c1df56618b79ca5a534e42987f6361faf6102328f8a879b3db3c9` |
| **Checksum file** | [SHA256SUMS.txt](https://github.com/socrates-one-web/one-hub/releases/download/v1.0.0-rc.2/SHA256SUMS.txt) | `215954cd09c11fd573a3f2a3b092caad7e67c8ae47dab7251e8a6ded229f4208` |

See the [release notes](https://github.com/socrates-one-web/one-hub/releases/tag/v1.0.0-rc.2) for installation details. The SHA-256 values above come from the published GitHub Release metadata. Confirm checksums locally after downloading.

## Source, contributions and funding

- Source: [GitHub main](https://github.com/socrates-one-web/one-hub/tree/main)
- License: [GNU AGPL-3.0-only](LICENSE)
- Third-party components: [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md)
- Security reporting: [SECURITY.md](SECURITY.md)
- Contributions: [CONTRIBUTING.md](CONTRIBUTING.md)
- Release checklist: [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md)
- Donations are **voluntary**. An official sponsorship URL will be published only after the payment account is verified.

**Maintainer:** Sócrates Creative Studio / Socrates One Web Design LTDA · https://os.socrates.la/
