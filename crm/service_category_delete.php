<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('catalog_categories.manage');
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('service_categories.php');
verify_csrf();
$id=max(0,(int)($_POST['id']??0));
$pdo=db();$stmt=$pdo->prepare('SELECT id,name FROM service_categories WHERE id=?');$stmt->execute([$id]);$cat=$stmt->fetch();
if(!$cat){flash('error','La categoría ya no existe.');redirect('service_categories.php');}
try{
    $pdo->beginTransaction();
    $countStmt=$pdo->prepare('SELECT COUNT(*) FROM service_catalog WHERE category_id=?');$countStmt->execute([$id]);$count=(int)$countStmt->fetchColumn();
    $pdo->prepare('UPDATE service_catalog SET category_id=NULL,category=NULL WHERE category_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM service_categories WHERE id=?')->execute([$id]);
    $pdo->commit();
    log_activity('Categoría eliminada',null,null,(string)$cat['name']);
    flash('success','Categoría eliminada'.($count?' y '.$count.' ítem(s) quedaron como “Sin categoría”.':'.'));
}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    flash('error','No fue posible eliminar la categoría.');
}
redirect('service_categories.php');
