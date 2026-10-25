<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
$token=(string)($_GET['token']??$_POST['token']??'');
$s=db()->prepare('SELECT * FROM lead_forms WHERE public_token=? AND is_active=1');$s->execute([$token]);$form=$s->fetch();
if(!$form){http_response_code(404);exit('Formulario no disponible.');}
$fields=json_decode((string)$form['fields_json'],true)?:[];$style=json_decode((string)($form['style_json']??''),true)?:[];$style=array_merge(['primary_color'=>'#0074c7','accent_color'=>'#ff5300','background_color'=>'#eef4f7','card_color'=>'#ffffff','text_color'=>'#153149','border_radius'=>16,'columns'=>1,'button_label'=>'ENVIAR'],$style);$hex=static fn($v,$d)=>preg_match('/^#[0-9a-f]{6}$/i',(string)$v)?(string)$v:$d;foreach(['primary_color'=>'#0074c7','accent_color'=>'#ff5300','background_color'=>'#eef4f7','card_color'=>'#ffffff','text_color'=>'#153149'] as $k=>$d)$style[$k]=$hex($style[$k],$d);$style['border_radius']=max(0,min(32,(int)$style['border_radius']));$style['columns']=in_array((int)$style['columns'],[1,2],true)?(int)$style['columns']:1;$errors=[];$done=false;
function lead_field_options(array $field):array{
    $raw=trim((string)($field['options']??''));$raw=str_replace('\\n', "\n", $raw);if($raw==='')return[];
    $items=preg_split('/\r\n|\r|\n|,/', $raw)?:[];
    return array_values(array_filter(array_map('trim',$items),fn($v)=>$v!==''));
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$data=[];
    foreach($fields as$f){
        $name=(string)($f['name']??'');if($name==='')continue;$type=(string)($f['type']??'text');
        if($type==='file'){
            $fileData=null;
            if(isset($_FILES['field']['error'][$name])&&$_FILES['field']['error'][$name]!==UPLOAD_ERR_NO_FILE){
                $file=['name'=>$_FILES['field']['name'][$name]??'archivo','type'=>$_FILES['field']['type'][$name]??'','tmp_name'=>$_FILES['field']['tmp_name'][$name]??'','error'=>$_FILES['field']['error'][$name]??UPLOAD_ERR_NO_FILE,'size'=>$_FILES['field']['size'][$name]??0];
                try{$fileData=crm_upload_document($file,'lead-submissions',15);}catch(Throwable$e){$errors[]=(string)$f['label'].': '.$e->getMessage();}
            }
            if(!empty($f['required'])&&!$fileData)$errors[]='Completa: '.$f['label'];
            $data[$name]=$fileData['file_path']??'';continue;
        }
        if($type==='checkbox')$value=isset($_POST['field'][$name])?'Sí':'';
        else$value=trim((string)($_POST['field'][$name]??''));
        if(!empty($f['required'])&&$value==='')$errors[]='Completa: '.$f['label'];
        if($type==='email'&&$value!==''&&!filter_var($value,FILTER_VALIDATE_EMAIL))$errors[]='El email no es válido.';
        if($type==='url'&&$value!==''&&!filter_var($value,FILTER_VALIDATE_URL))$errors[]='La URL no es válida.';
        $data[$name]=$value;
    }
    if(!$errors){
        db()->prepare('INSERT INTO lead_submissions(form_id,data_json,name,email,phone,country) VALUES (?,?,?,?,?,?)')->execute([(int)$form['id'],json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$data['name']??$data['nombre']??null,$data['email']??null,$data['phone']??$data['telefono']??null,$data['country']??$data['pais']??null]);
        $done=true;
    }
}
?><!doctype html><html lang="<?=e($form['language'])?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($form['title'])?></title><link rel="stylesheet" href="assets/css/app.css?v=1.8.0"><style>:root{--form-primary:<?=e($style['primary_color'])?>;--form-accent:<?=e($style['accent_color'])?>;--form-bg:<?=e($style['background_color'])?>;--form-card:<?=e($style['card_color'])?>;--form-text:<?=e($style['text_color'])?>;--form-radius:<?=(int)$style['border_radius']?>px}body{background:var(--form-bg);color:var(--form-text)}.public-lead-wrap{max-width:920px;margin:40px auto;padding:20px}.public-lead-card{background:var(--form-card);border-radius:var(--form-radius);border:1px solid #dbe6ed;box-shadow:0 18px 50px rgba(0,47,87,.12);overflow:hidden}.public-lead-head{padding:30px;border-top:6px solid var(--form-accent)}.public-lead-head h1{color:var(--form-text)}.public-lead-body{padding:0 30px 30px}.public-lead-brand{font-weight:900;color:var(--form-primary);margin-bottom:18px}.public-fields-grid{display:grid;grid-template-columns:repeat(<?=(int)$style['columns']?>,minmax(0,1fr));gap:0 18px}.public-lead-card label{display:block;margin:15px 0}.public-lead-card input,.public-lead-card textarea,.public-lead-card select{width:100%;border-radius:calc(var(--form-radius)*.45)}.public-lead-card textarea,.public-field-wide{grid-column:1/-1}.public-options{display:grid;gap:8px;margin-top:8px}.public-option{display:flex!important;gap:9px;align-items:center;margin:0!important;padding:10px;border:1px solid #d8e3ea;border-radius:7px}.public-option input{width:auto!important;margin:0}.public-checkbox{display:flex!important;gap:9px;align-items:flex-start;grid-column:1/-1}.public-checkbox input{width:auto!important;margin-top:2px!important}.public-submit{width:100%;background:var(--form-primary)!important;border-color:var(--form-primary)!important;border-radius:calc(var(--form-radius)*.45)!important}.public-submit:hover{filter:brightness(.92)}@media(max-width:700px){.public-lead-wrap{margin:12px auto;padding:12px}.public-lead-head,.public-lead-body{padding:22px}.public-fields-grid{grid-template-columns:1fr}}</style></head><body><main class="public-lead-wrap"><div class="public-lead-card"><div class="public-lead-head"><div class="public-lead-brand">SÓCRATES · AI Creative Studio</div><h1><?=e($form['title'])?></h1><?php if($form['description']):?><p><?=nl2br(e($form['description']))?></p><?php endif;?></div><div class="public-lead-body"><?php if($done):?><div class="alert alert-success"><?=nl2br(e($form['success_message']?:'Gracias. Recibimos tu información.'))?></div><?php else:?><?php foreach($errors as$er):?><div class="alert alert-error"><?=e($er)?></div><?php endforeach;?><form method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="token" value="<?=e($token)?>"><div class="public-fields-grid">
<?php foreach($fields as$f):$name=(string)($f['name']??'');$type=(string)($f['type']??'text');$required=!empty($f['required']);$placeholder=(string)($f['placeholder']??'');$options=lead_field_options($f);if($type==='hidden'):?><input type="hidden" name="field[<?=e($name)?>]" value="<?=e($placeholder)?>"><?php continue;endif;?>
<?php if($type==='checkbox'):?><label class="public-checkbox"><input type="checkbox" name="field[<?=e($name)?>]" value="1" <?=$required?'required':''?>><span><?=e($f['label'])?><?=$required?' *':''?></span></label>
<?php elseif($type==='radio'):?><fieldset class="public-field-wide" style="border:0;padding:0;margin:15px 0"><legend style="font-weight:700;font-size:13px"><?=e($f['label'])?><?=$required?' *':''?></legend><div class="public-options"><?php foreach($options as$option):?><label class="public-option"><input type="radio" name="field[<?=e($name)?>]" value="<?=e($option)?>" <?=$required?'required':''?>> <?=e($option)?></label><?php endforeach;?></div></fieldset>
<?php else:?><label class="<?=$type==='textarea'?'public-field-wide':''?>"><?=e($f['label'])?><?=$required?' *':''?>
<?php if($type==='textarea'):?><textarea name="field[<?=e($name)?>]" placeholder="<?=e($placeholder)?>" <?=$required?'required':''?>><?=e($_POST['field'][$name]??'')?></textarea>
<?php elseif($type==='select'):?><select name="field[<?=e($name)?>]" <?=$required?'required':''?>><option value="">Seleccionar…</option><?php foreach($options as$option):?><option value="<?=e($option)?>" <?=($_POST['field'][$name]??'')===$option?'selected':''?>><?=e($option)?></option><?php endforeach;?></select>
<?php elseif($type==='file'):?><input type="file" name="field[<?=e($name)?>]" <?=$required?'required':''?> accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip">
<?php else:$inputType=in_array($type,['text','email','tel','number','date','time','datetime-local','url'],true)?$type:'text';?><input type="<?=e($inputType)?>" name="field[<?=e($name)?>]" value="<?=e($_POST['field'][$name]??'')?>" placeholder="<?=e($placeholder)?>" <?=$required?'required':''?>><?php endif;?></label><?php endif;?>
<?php endforeach;?></div><button class="btn btn-primary public-submit"><?=e((string)$style['button_label'])?></button></form><?php endif;?></div></div></main></body></html>