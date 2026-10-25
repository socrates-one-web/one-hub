<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('settings.manage');
$pageTitle='ONE HUB Community';
require __DIR__.'/includes/header.php';
?><div class="panel"><div class="panel-header"><h2>ONE HUB · COMMUNITY</h2></div><div class="panel-body">
<p>Esta edición no requiere activar licencias ni conectarse a un servidor de licencias.</p>
<p>Puede utilizar y personalizar la plataforma después de completar la instalación.</p>
<p><strong>Licencia del código propio:</strong> GNU AGPL-3.0-only. Consulte el archivo <code>LICENSE</code> incluido en la raíz del paquete y los avisos de terceros en <code>THIRD_PARTY_NOTICES.md</code>. Esta distribución candidata sigue en revisión previa al lanzamiento.</p>
<p>Los aportes al desarrollador son voluntarios y no desbloquean funciones.</p>
</div></div><?php require __DIR__.'/includes/footer.php'; ?>
