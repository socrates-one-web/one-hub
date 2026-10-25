# Módulo de Facturación — SÓCRATES ONE CRM 1.8.0

## Conceptos

**Negocio**: oportunidad que todavía está en el ciclo de ventas.

**Servicio facturable**: trabajo o contrato ya cerrado que está activo para un cliente y puede generar uno o más cobros.

**Cobro**: obligación concreta con fecha, moneda y valor.

**Ingreso**: dinero efectivamente recibido. Puede cubrir total o parcialmente un cobro.

**Renovación**: extensión del período de un servicio recurrente una vez que corresponde continuar el contrato.

## Flujo

`Negocio ganado → Servicio facturable → Cobro pendiente → Pago → Próximo cobro / renovación`

## Estados independientes

El servicio puede estar activo, suspendido, finalizado o cancelado. El cobro puede estar pendiente, parcial, pagado, vencido o cancelado. Esto evita confundir la prestación del servicio con su situación de pago.

## Recurrencia

La frecuencia se guarda por servicio. Cuando un cobro queda totalmente pagado y la generación automática está activa, el CRM calcula la próxima fecha y crea el siguiente cobro sin reemplazar el historial anterior.

## Alertas

Los días se escriben separados por comas. Los positivos significan días antes y los negativos días después. Ejemplo:

`30,15,7,3,1,0,-3`

Cada servicio decide si envía la alerta por CRM, email o WhatsApp.

## Seguridad

Todas las acciones respetan los permisos del usuario y los formularios usan protección CSRF. Los administradores conservan acceso global; otros perfiles dependen de sus permisos de facturación.
