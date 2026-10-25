<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('catalog.view');

$pageTitle='Catálogo de cotizaciones';
$pdo=db();
$stats=[
    'products'=>(int)$pdo->query("SELECT COUNT(*) FROM service_catalog WHERE item_type='product'")->fetchColumn(),
    'services'=>(int)$pdo->query("SELECT COUNT(*) FROM service_catalog WHERE item_type='service'")->fetchColumn(),
    'active'=>(int)$pdo->query("SELECT COUNT(*) FROM service_catalog WHERE is_active=1")->fetchColumn(),
    'categories'=>(int)$pdo->query("SELECT COUNT(*) FROM service_categories WHERE is_active=1")->fetchColumn(),
];
$recent=$pdo->query("SELECT sc.id,sc.item_type,sc.name_es,COALESCE(c.name,sc.category,'Sin categoría') category_name,sc.price_usd,sc.price_brl,sc.price_clp,sc.is_active FROM service_catalog sc LEFT JOIN service_categories c ON c.id=sc.category_id ORDER BY sc.updated_at DESC LIMIT 8")->fetchAll();
require __DIR__.'/includes/header.php';
?>
<div class="catalog-hub-hero panel"><div class="panel-body"><div><span class="eyebrow">CATÁLOGO COMERCIAL</span><h2>Todo lo que puedes agregar a una cotización</h2><p>Administra productos, servicios, categorías y formas de pago desde un solo lugar. Los cambios quedan disponibles inmediatamente al crear una cotización.</p></div><div class="actions"><?php if(has_permission('catalog.manage')):?><a class="btn btn-primary" href="service_catalog_form.php">+ PRODUCTO O SERVICIO</a><?php endif;?><?php if(has_permission('catalog_categories.manage')):?><a class="btn btn-light" href="service_category_form.php">+ CATEGORÍA</a><?php endif;?></div></div></div>

<div class="stats-grid catalog-hub-stats">
<a class="stat-card" href="service_catalog.php?type=product" style="--accent:var(--yellow)"><small>PRODUCTOS</small><strong><?=$stats['products']?></strong><span>Ítems físicos o digitales</span></a>
<a class="stat-card" href="service_catalog.php?type=service" style="--accent:var(--blue)"><small>SERVICIOS</small><strong><?=$stats['services']?></strong><span>Servicios reutilizables</span></a>
<a class="stat-card" href="service_categories.php" style="--accent:var(--green)"><small>CATEGORÍAS ACTIVAS</small><strong><?=$stats['categories']?></strong><span>Organización del catálogo</span></a>
<a class="stat-card" href="service_catalog.php?state=active" style="--accent:var(--orange)"><small>ÍTEMS ACTIVOS</small><strong><?=$stats['active']?></strong><span>Disponibles al cotizar</span></a>
</div>

<div class="catalog-admin-grid">
<a class="catalog-admin-card" href="service_catalog.php"><span class="catalog-admin-icon"><?=crm_icon('catalog')?></span><div><strong>Productos y servicios</strong><p>Crear, editar, activar, desactivar o eliminar ítems; definir textos en ES/PT/EN y precios CLP/USD/BRL.</p></div><b>→</b></a>
<a class="catalog-admin-card" href="service_categories.php"><span class="catalog-admin-icon"><?=crm_icon('catalog')?></span><div><strong>Categorías</strong><p>Organiza el catálogo para encontrar rápidamente lo que necesitas al preparar una cotización.</p></div><b>→</b></a>
<?php if(has_permission('payment_methods.view')):?><a class="catalog-admin-card" href="payment_methods.php"><span class="catalog-admin-icon"><?=crm_icon('payment')?></span><div><strong>Formas de pago</strong><p>Configura bancos, PIX, links de pago e instrucciones por país, moneda e idioma.</p></div><b>→</b></a><?php endif;?>
<a class="catalog-admin-card" href="quotation_form.php"><span class="catalog-admin-icon"><?=crm_icon('quote')?></span><div><strong>Probar en una cotización</strong><p>Abre una cotización nueva para comprobar cómo aparecen los productos y servicios del catálogo.</p></div><b>→</b></a>
</div>

<section class="panel"><div class="panel-header"><div><h2>Modificados recientemente</h2><span class="table-sub">Los últimos ítems editados del catálogo comercial.</span></div><a class="btn btn-light btn-small" href="service_catalog.php">VER TODO</a></div><div class="table-wrap"><?php if(!$recent):?><div class="empty">Todavía no hay productos ni servicios.</div><?php else:?><table class="data-table"><thead><tr><th>Tipo</th><th>Ítem</th><th>Categoría</th><th>Precio referencial</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($recent as$r):?><tr><td><span class="badge <?=$r['item_type']==='product'?'badge-yellow':'badge-blue'?>"><?=$r['item_type']==='product'?'Producto':'Servicio'?></span></td><td><strong><?=e($r['name_es'])?></strong></td><td><?=e($r['category_name'])?></td><td><?php if($r['price_usd']!==null):?><?=e(format_money($r['price_usd'],'USD'))?><?php elseif($r['price_brl']!==null):?><?=e(format_money($r['price_brl'],'BRL'))?><?php elseif($r['price_clp']!==null):?><?=e(format_money($r['price_clp'],'CLP'))?><?php else:?>—<?php endif;?></td><td><span class="badge <?=$r['is_active']?'badge-green':'badge-gray'?>"><?=$r['is_active']?'Activo':'Inactivo'?></span></td><td><a class="btn btn-light btn-small" href="service_catalog_form.php?id=<?=(int)$r['id']?>">Editar</a></td></tr><?php endforeach;?></tbody></table><?php endif;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
