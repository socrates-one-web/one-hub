<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('catalog.manage');
$id=max(0,(int)($_GET['id']??$_POST['id']??0));
$service=null;
if($id){$stmt=db()->prepare('SELECT * FROM service_catalog WHERE id=?');$stmt->execute([$id]);$service=$stmt->fetch();if(!$service){http_response_code(404);exit('Servicio no encontrado.');}}
$currentCategoryId=(int)($service['category_id']??0);
$stmt=db()->prepare('SELECT * FROM service_categories WHERE is_active=1 OR id=? ORDER BY sort_order,name');$stmt->execute([$currentCategoryId]);$categories=$stmt->fetchAll();
$pageTitle=$service?'Editar producto / servicio':'Nuevo producto / servicio';$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $categoryId=max(0,(int)($_POST['category_id']??0))?:null;
    $categoryName=null;
    if($categoryId){$stmt=db()->prepare('SELECT name FROM service_categories WHERE id=?');$stmt->execute([$categoryId]);$categoryName=$stmt->fetchColumn()?:null;if(!$categoryName)$categoryId=null;}
    $data=[
        'category'=>$categoryName,
        'category_id'=>$categoryId,
        'item_type'=>in_array((string)($_POST['item_type']??'service'),['service','product'],true)?(string)$_POST['item_type']:'service',
        'name_es'=>trim((string)($_POST['name_es']??'')),
        'name_pt'=>trim((string)($_POST['name_pt']??'')),
        'name_en'=>trim((string)($_POST['name_en']??'')),
        'description_es'=>trim((string)($_POST['description_es']??'')),
        'description_pt'=>trim((string)($_POST['description_pt']??'')),
        'description_en'=>trim((string)($_POST['description_en']??'')),
        'default_contract_type'=>(string)($_POST['default_contract_type']??'one_time'),
        'custom_period_months'=>($_POST['custom_period_months']??'')!==''?max(1,(int)$_POST['custom_period_months']):null,
        'price_clp'=>($_POST['price_clp']??'')!==''?(float)$_POST['price_clp']:null,
        'price_usd'=>($_POST['price_usd']??'')!==''?(float)$_POST['price_usd']:null,
        'price_brl'=>($_POST['price_brl']??'')!==''?(float)$_POST['price_brl']:null,
        'is_active'=>isset($_POST['is_active'])?1:0,
    ];
    if($data['name_es']==='')$errors[]='El nombre del producto o servicio en español es obligatorio.';
    if(!$categoryId)$errors[]='Selecciona una categoría. Puedes crearla desde el mantenedor de categorías.';
    if(!in_array($data['default_contract_type'],['monthly','annual','one_time','custom'],true))$data['default_contract_type']='one_time';
    if(!$errors){
        if($id){
            db()->prepare('UPDATE service_catalog SET category=?,category_id=?,item_type=?,name_es=?,name_pt=?,name_en=?,description_es=?,description_pt=?,description_en=?,default_contract_type=?,custom_period_months=?,price_clp=?,price_usd=?,price_brl=?,is_active=? WHERE id=?')->execute([...array_values($data),$id]);
            log_activity('Producto/servicio del catálogo actualizado',null,null,$data['name_es']);flash('success','Producto/servicio actualizado correctamente.');
        }else{
            db()->prepare('INSERT INTO service_catalog (category,category_id,item_type,name_es,name_pt,name_en,description_es,description_pt,description_en,default_contract_type,custom_period_months,price_clp,price_usd,price_brl,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute(array_values($data));
            log_activity('Producto/servicio del catálogo creado',null,null,$data['name_es']);flash('success','Producto/servicio creado. Ya puede utilizarse en cotizaciones.');
        }
        redirect('service_catalog.php');
    }
}
$v=fn(string$key,mixed$default='')=>$_POST[$key]??($service[$key]??$default);
require __DIR__.'/includes/header.php';
?>
<?php foreach($errors as$error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?>
<form method="post" class="panel"><div class="panel-header"><div><h2><?=$service?'Actualizar producto / servicio':'Crear producto / servicio reutilizable'?></h2><span class="table-sub">Registra productos o servicios con nombres, descripciones y precios. Las categorías se administran por separado.</span></div><div class="actions"><a class="btn btn-light btn-small" href="service_categories.php">Categorías</a><a class="btn btn-light btn-small" href="service_catalog.php">Cancelar</a></div></div><div class="panel-body form-grid"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
<div class="form-section full form-grid"><h3>Información general</h3>
<label>Tipo *<select name="item_type" required><option value="service" <?=$v('item_type','service')==='service'?'selected':''?>>Servicio</option><option value="product" <?=$v('item_type')==='product'?'selected':''?>>Producto</option></select></label><label>Categoría *<select name="category_id" required><option value="">Seleccionar...</option><?php foreach($categories as$category):?><option value="<?=(int)$category['id']?>" <?=((int)$v('category_id')===(int)$category['id'])?'selected':''?>><?=e($category['name'])?><?=$category['is_active']?'':' · Inactiva'?></option><?php endforeach;?></select><span class="table-sub"><a href="service_category_form.php" target="_blank">Crear nueva categoría</a></span></label>
<label>Modalidad sugerida<select name="default_contract_type"><option value="one_time" <?=$v('default_contract_type','one_time')==='one_time'?'selected':''?>>Única vez</option><option value="monthly" <?=$v('default_contract_type')==='monthly'?'selected':''?>>Mensual</option><option value="annual" <?=$v('default_contract_type')==='annual'?'selected':''?>>Anual</option><option value="custom" <?=$v('default_contract_type')==='custom'?'selected':''?>>Personalizada</option></select></label>
<label data-custom-period>Meses del período personalizado<input type="number" min="1" name="custom_period_months" value="<?=e($v('custom_period_months'))?>"></label>
<label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?=($v('is_active',1))?'checked':''?>> Ítem activo</label></div>
<div class="form-section full form-grid"><h3>Nombres por idioma</h3><label>Nombre en español *<input name="name_es" required value="<?=e($v('name_es'))?>"></label><label>Nome em português<input name="name_pt" value="<?=e($v('name_pt'))?>"></label><label>Name in English<input name="name_en" value="<?=e($v('name_en'))?>"></label><label>Descripción en español<textarea name="description_es"><?=e($v('description_es'))?></textarea></label><label>Descrição em português<textarea name="description_pt"><?=e($v('description_pt'))?></textarea></label><label>Description in English<textarea name="description_en"><?=e($v('description_en'))?></textarea></label></div>
<div class="form-section full form-grid"><h3>Precios de referencia</h3><label>Precio en pesos chilenos (CLP)<input type="number" min="0" step="1" name="price_clp" value="<?=e($v('price_clp'))?>"></label><label>Precio en dólares (USD)<input type="number" min="0" step="0.01" name="price_usd" value="<?=e($v('price_usd'))?>"></label><label>Preço em reais (BRL)<input type="number" min="0" step="0.01" name="price_brl" value="<?=e($v('price_brl'))?>"></label></div>
<div class="full form-actions"><a class="btn btn-light" href="service_catalog.php">Cancelar</a><button class="btn btn-primary">GUARDAR PRODUCTO / SERVICIO</button></div></div></form>
<?php require __DIR__.'/includes/footer.php';?>
