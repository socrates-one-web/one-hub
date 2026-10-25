<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
require_permission('users.manage');

$id=(int)($_GET['id']??$_POST['id']??0);
$currentId=(int)($_SESSION['user_id']??0);
if($id<1){flash('error','Usuario no válido.');redirect('users.php');}
if($id===$currentId){flash('error','No puedes eliminar el usuario con el que estás conectado.');redirect('users.php');}

$stmt=db()->prepare('SELECT id,name,email,role,is_active,last_login_at,created_at FROM users WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$user=$stmt->fetch();
if(!$user){flash('error','Usuario no encontrado.');redirect('users.php');}
$current=current_user();
if(($user['role']??'')==='admin' && ($current['role']??'')!=='admin'){
    flash('error','Solo otro administrador puede eliminar una cuenta de administrador.');
    redirect('users.php');
}

$otherAdmins=(int)db()->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1 AND id<>".$id)->fetchColumn();
$blockedLastAdmin=(($user['role']??'')==='admin' && (int)$user['is_active']===1 && $otherAdmins<1);

// Resumen para que el administrador sepa qué ocurrirá con la trazabilidad.
$impact=['tickets'=>0,'hours'=>0,'projects'=>0,'tasks'=>0,'activities'=>0];
$queries=[
    'tickets'=>'SELECT COUNT(*) FROM support_tickets WHERE user_id=?',
    'hours'=>'SELECT COUNT(*) FROM project_time_entries WHERE user_id=?',
    'projects'=>'SELECT COUNT(*) FROM projects WHERE owner_user_id=?',
    'tasks'=>'SELECT COUNT(*) FROM project_tasks WHERE assigned_to=?',
    'activities'=>'SELECT COUNT(*) FROM activities WHERE assigned_to=?',
];
foreach($queries as $k=>$sql){try{$q=db()->prepare($sql);$q->execute([$id]);$impact[$k]=(int)$q->fetchColumn();}catch(Throwable){$impact[$k]=0;}}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    if($blockedLastAdmin){flash('error','No se puede eliminar el último administrador activo.');redirect('users.php');}
    if((string)($_POST['confirm_delete']??'')!=='1'){flash('error','Debes confirmar la eliminación definitiva.');redirect('user_delete.php?id='.$id);}
    $pdo=db();
    try{
        $pdo->beginTransaction();
        // Conservamos registros operativos que usan user_id obligatorio y los reasignamos al administrador actual.
        foreach([
            'UPDATE support_tickets SET user_id=? WHERE user_id=?',
            'UPDATE project_time_entries SET user_id=? WHERE user_id=?',
        ] as $sql){try{$pdo->prepare($sql)->execute([$currentId,$id]);}catch(Throwable){}}
        // Cerramos cualquier sesión antes de eliminar.
        try{crm_revoke_user_sessions($id);}catch(Throwable){}
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        $pdo->commit();
        crm_security_audit('user_deleted','critical','Usuario eliminado definitivamente: '.(string)$user['email'],'user',$id);
        log_activity('Usuario eliminado',null,null,(string)$user['email'].' · historial obligatorio reasignado a '.(string)($current['email']??'administrador'));
        flash('success','Usuario eliminado. El historial operativo obligatorio fue conservado y reasignado al administrador actual.');
        redirect('users.php');
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        flash('error','No fue posible eliminar el usuario: '.$e->getMessage());
        redirect('user_delete.php?id='.$id);
    }
}

$pageTitle='Eliminar usuario';
require __DIR__.'/includes/header.php';
?>
<section class="panel danger-zone-panel" style="max-width:860px;margin-inline:auto">
 <div class="panel-header"><div><h2>Eliminar usuario</h2><span class="table-sub">Esta acción elimina la cuenta de acceso, pero protege la trazabilidad operativa.</span></div><a class="btn btn-light btn-small" href="users.php">Volver</a></div>
 <div class="panel-body">
  <div class="user-delete-identity">
   <div class="manual-card-icon"><?=crm_icon('users')?></div>
   <div><strong><?=e($user['name'])?></strong><span><?=e($user['email'])?> · <?=e(role_label((string)$user['role']))?></span></div>
   <span class="badge badge-<?=$user['is_active']?'green':'gray'?>"><?=$user['is_active']?'Activo':'Inactivo'?></span>
  </div>
  <?php if($blockedLastAdmin):?>
   <div class="alert alert-error" style="margin-top:18px"><strong>No se puede eliminar.</strong> Esta cuenta es el último administrador activo del sistema. Crea o activa otro administrador primero.</div>
  <?php else:?>
   <div class="alert alert-warning" style="margin-top:18px"><strong>Eliminación definitiva.</strong> Ya no podrá iniciar sesión y su perfil desaparecerá de Usuarios. Los registros históricos no se borrarán cuando sea posible conservarlos.</div>
   <div class="delete-impact-grid">
    <div><b><?=number_format($impact['projects'])?></b><span>Proyectos responsables</span><small>Quedarán sin responsable.</small></div>
    <div><b><?=number_format($impact['tasks'])?></b><span>Tareas asignadas</span><small>Quedarán sin asignar.</small></div>
    <div><b><?=number_format($impact['activities'])?></b><span>Actividades</span><small>Quedarán sin asignar.</small></div>
    <div><b><?=number_format($impact['hours'])?></b><span>Registros de horas</span><small>Se conservan y reasignan.</small></div>
    <div><b><?=number_format($impact['tickets'])?></b><span>Tickets de soporte</span><small>Se conservan y reasignan.</small></div>
   </div>
   <form method="post" class="delete-confirm-box">
    <?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
    <label class="check-line"><input type="checkbox" name="confirm_delete" value="1" required><span>Entiendo que la cuenta de <strong><?=e($user['name'])?></strong> se eliminará definitivamente.</span></label>
    <div class="form-actions"><a class="btn btn-light" href="users.php">Cancelar</a><button class="btn btn-danger">ELIMINAR USUARIO DEFINITIVAMENTE</button></div>
   </form>
  <?php endif;?>
 </div>
</section>
<?php require __DIR__.'/includes/footer.php';?>
