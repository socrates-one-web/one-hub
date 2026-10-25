<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/demo_seed.php';
require_permission('settings.manage');
$pageTitle='Contenido DEMO';
$pdo=db();
$result=null;$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $result=crm_seed_demo($pdo,(int)$_SESSION['user_id']);
        flash('success',!empty($result['already'])?'La DEMO ya estaba instalada.':'Contenido DEMO instalado correctamente.');
        redirect('demo_data.php?done=1');
    }catch(Throwable $e){$error=$e->getMessage();}
}
$seedVersion=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='demo_seed_version' LIMIT 1")->fetchColumn()?:'');
require __DIR__.'/includes/header.php';
?>
<div class="project-hero"><div><span class="eyebrow">SÓCRATES ONE · PRESENTACIÓN</span><h2>Contenido DEMO completo</h2><p>Puebla el CRM + Project Manager con información ficticia para mostrar todas las funciones del sistema.</p></div></div>
<?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
<section class="panel" style="max-width:900px">
  <div class="panel-header"><div><h2><?= $seedVersion ? 'Demo instalada' : 'Preparar demostración' ?></h2><span class="table-sub"><?= $seedVersion ? 'Versión: '.e($seedVersion) : 'Ideal para una instalación destinada a presentaciones comerciales.' ?></span></div></div>
  <div style="padding:8px 0 18px;line-height:1.65">
    <p><strong>Incluye automáticamente:</strong> clientes y contactos, servicios y renovaciones, facturación e ingresos, pipeline de ventas, actividades, cotizaciones, contratos, campañas, formularios, leads, reuniones y un portafolio completo de proyectos.</p>
    <p>El Project Manager queda con <strong>Kanban, Gantt, calendario, hitos, tareas y subtareas, dependencias, checklist, comentarios, equipo, horas, riesgos, costos y archivos demo</strong>.</p>
    <p style="background:#fff5ee;border:1px solid #ffd7c2;border-radius:12px;padding:12px 14px"><strong>Importante:</strong> usa esta función únicamente en una instalación de demostración. Los registros son ficticios y se identifican como DEMO.</p>
  </div>
  <?php if(!$seedVersion):?>
  <form method="post" onsubmit="return confirm('¿Instalar ahora todo el contenido DEMO?');">
    <?=csrf_field()?>
    <button class="btn btn-primary" type="submit">INSTALAR DEMO COMPLETA</button>
  </form>
  <?php else:?><div class="actions"><a class="btn btn-primary" href="project_dashboard.php">VER DASHBOARD DE PROYECTOS</a><a class="btn btn-secondary" href="dashboard.php">VER DASHBOARD CRM</a></div><?php endif;?>
</section>
<?php require __DIR__.'/includes/footer.php';?>
