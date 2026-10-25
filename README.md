# ONE HUB Community

**Open-source business management platform · Plataforma empresarial de código abierto**

[Official website / Sitio oficial](https://os.socrates.la/) · [Installation guide / Manual](https://os.socrates.la/manual.php) · [Issues](https://github.com/socrates-one-web/one-hub/issues) · [Release status](https://github.com/socrates-one-web/one-hub/releases)

**Source code published · Código fuente publicado · Código-fonte publicado**

ONE HUB Community is developed by **Sócrates Creative Studio / Socrates One Web Design LTDA**. Original project code is distributed under **GNU AGPL-3.0-only**; third-party materials retain their respective terms as stated in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## ES — Español

ONE HUB Community integra CRM, clientes, contactos, oportunidades, ventas, cotizaciones multimoneda y PDF, contratos, proyectos, tareas, calendario, documentos, finanzas, reportes, dashboards y **EMAILSEND** (contactos, listas, campañas, SMTP y métricas).

**Estado 25/10/2026:** el código fuente RC2 completo de CLEAN, DEMO y landing ya está disponible en la rama `main`. La versión `v1.0.0-rc.2` está preparada como **borrador de GitHub Release**: las descargas adjuntas no son públicas hasta pulsar “Publish release”. Todavía **no** existe la versión estable `v1.0.0`.

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

**Status as of 25 October 2026:** audited-for-packaging RC2 source is public on `main`. The `v1.0.0-rc.2` distribution files are attached to a **draft Release**, not yet publicly downloadable. No stable `v1.0.0` release has been published.

**Editions:** CLEAN (no business records), DEMO (fictional seed data and outbound messaging protections), and COMPLETO (website and both editions). Requires PHP 8.1+, MySQL/PDO, OpenSSL, Fileinfo, mbstring, and correctly enforced Apache access controls. Use separate new databases, strong credentials, a secure `cron_key`, and remove or block the installer after setup. See the [installation notes](docs/INSTALACION_PLANIFICADA.md) and [security policy](SECURITY.md).

## Source, contributions and funding

- Source: [GitHub main](https://github.com/socrates-one-web/one-hub/tree/main)
- License: [GNU AGPL-3.0-only](LICENSE)
- Third-party components: [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md)
- Security reporting: [SECURITY.md](SECURITY.md)
- Contributions: [CONTRIBUTING.md](CONTRIBUTING.md)
- Release checklist: [RELEASE_CHECKLIST.md](RELEASE_CHECKLIST.md)
- Donations are **voluntary**. An official sponsorship URL will be published only after the payment account is verified.

**Maintainer:** Sócrates Creative Studio / Socrates One Web Design LTDA · https://os.socrates.la/
