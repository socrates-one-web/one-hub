<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('catalog.manage');
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('service_catalog.php');
verify_csrf();
$id=max(0,(int)($_POST['id']??0));
$stmt=db()->prepare('SELECT id,name_es,item_type FROM service_catalog WHERE id=?');$stmt->execute([$id]);$item=$stmt->fetch();
if(!$item){flash('error','El ítem ya no existe.');redirect('service_catalog.php');}
try{
    db()->prepare('DELETE FROM service_catalog WHERE id=?')->execute([$id]);
    log_activity(($item['item_type']==='product'?'Producto':'Servicio').' eliminado del catálogo',null,null,(string)$item['name_es']);
    flash('success','Ítem eliminado del catálogo. Las cotizaciones históricas conservan su descripción, cantidad y precio.');
}catch(Throwable $e){
    flash('error','No fue posible eliminar el ítem. Puedes desactivarlo si existe alguna relación que impida borrarlo.');
}
redirect('service_catalog.php');
