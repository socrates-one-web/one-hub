<?php
declare(strict_types=1);

require __DIR__.'/includes/bootstrap.php';
require_login();

/*
 * EMAILSEND ONE v0.13.4
 * Builder 3 Safe Save Hotfix
 *
 * Objetivos:
 * - Nunca dejar al usuario frente a un HTTP 500 silencioso al guardar.
 * - Registrar el punto exacto del fallo en storage/builder3-save.log.
 * - Guardar el JSON del diseño incluso si un bloque concreto rompe el compilador.
 * - Compilar de forma degradada aislando el bloque conflictivo cuando sea posible.
 */

function cb3_save_log(Throwable $e, string $stage, int $campaignId=0, array $extra=[]): string {
    $id=date('Ymd-His').'-'.substr(bin2hex(random_bytes(5)),0,10);
    try {
        $dir=APP_ROOT.'/storage';
        if(!is_dir($dir)) @mkdir($dir,0755,true);
        $row=[
            'time'=>date('c'),
            'error_id'=>$id,
            'stage'=>$stage,
            'campaign_id'=>$campaignId,
            'tenant_id'=>(int)(current_tenant_id()??0),
            'user_id'=>(int)(current_user()['id']??0),
            'type'=>get_class($e),
            'message'=>$e->getMessage(),
            'file'=>basename($e->getFile()),
            'line'=>$e->getLine(),
        ];
        foreach($extra as $k=>$v){
            if(is_scalar($v) || $v===null) $row[$k]=$v;
        }
        @file_put_contents(
            $dir.'/builder3-save.log',
            json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND|LOCK_EX
        );
    } catch(Throwable $ignore) {}
    return $id;
}

function cb3_fix_urls(&$value): void {
    if(is_array($value)){
        foreach($value as &$v) cb3_fix_urls($v);
        unset($v);
        return;
    }
    if(is_string($value)){
        $value=str_ireplace(
            [
                app_url(),
                app_url(),
                app_url()
            ],
            [
                app_url(),
                app_url(),
                app_url()
            ],
            $value
        );
    }
}

/**
 * Intenta compilar normalmente. Si un bloque top-level provoca una excepción,
 * conserva el JSON completo pero genera el HTML con los bloques sanos para
 * impedir que un único bloque deje inutilizable el guardado.
 */
function cb3_compile_resilient(array $blocks, int $campaignId, array &$skipped=[]): string {
    try {
        return compile_campaign_html($blocks);
    } catch(Throwable $first) {
        cb3_save_log($first,'compile-full',$campaignId);

        $doc=[];
        $content=[];
        foreach($blocks as $b){
            if(!is_array($b)) continue;
            if(($b['type']??'')==='__document') $doc=[$b];
            else $content[]=$b;
        }

        $safe=$doc;
        foreach($content as $index=>$block){
            try {
                // Probar cada bloque usando el mismo compilador final.
                compile_campaign_html(array_merge($doc,[$block]));
                $safe[]=$block;
            } catch(Throwable $blockError) {
                $type=(string)($block['type']??'desconocido');
                $bid=(string)($block['id']??'');
                $skipped[]=[
                    'index'=>$index,
                    'type'=>$type,
                    'id'=>$bid,
                    'message'=>$blockError->getMessage()
                ];
                cb3_save_log($blockError,'compile-block',$campaignId,[
                    'block_index'=>$index,
                    'block_type'=>$type,
                    'block_id'=>$bid
                ]);
            }
        }

        if(count($safe)>count($doc)){
            try {
                return compile_campaign_html($safe);
            } catch(Throwable $second) {
                cb3_save_log($second,'compile-safe-set',$campaignId);
            }
        }

        // Última protección: conservar el HTML previamente guardado.
        if($campaignId>0){
            try {
                $st=db()->prepare('SELECT html FROM campaigns WHERE id=? AND tenant_id=? LIMIT 1');
                $st->execute([$campaignId,(int)current_tenant_id()]);
                $old=(string)($st->fetchColumn()?:'');
                if(trim($old)!=='') return $old;
            } catch(Throwable $lookupError) {
                cb3_save_log($lookupError,'load-previous-html',$campaignId);
            }
        }

        // Para una campaña nueva, producir al menos un documento válido.
        try {
            return compile_campaign_html($doc);
        } catch(Throwable $last) {
            cb3_save_log($last,'compile-document-only',$campaignId);
            return '<!doctype html><html><body></body></html>';
        }
    }
}

$id=0;

try {
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){
        http_response_code(405);
        exit('Método no permitido');
    }

    verify_csrf();

    $id=(int)($_POST['id']??0);
    require_permission('campaigns',$id?'edit':'create');

    $tid=(int)(current_tenant_id()??0);
    if($tid<=0) throw new RuntimeException('No se pudo determinar la empresa activa.');

    if($id && !campaign_lists_are_authorized($id,$tid)){
        flash('error','No tienes autorización para modificar esta campaña.');
        redirect('index.php?route=campaigns');
    }

    $name=trim((string)($_POST['name']??''));
    $subject=trim((string)($_POST['subject']??''));
    $preheader=trim((string)($_POST['preheader']??''));
    $fromName=trim((string)($_POST['from_name']??''));
    $fromEmail=strtolower(trim((string)($_POST['from_email']??'')));

    if($name==='') throw new RuntimeException('Indica un nombre interno para la campaña.');
    if($subject==='') throw new RuntimeException('Indica el asunto de la campaña.');
    if($fromEmail!=='' && !filter_var($fromEmail,FILTER_VALIDATE_EMAIL)){
        throw new RuntimeException('El email remitente no es válido.');
    }

    $blocksRaw=(string)($_POST['blocks_json']??'[]');
    $blocks=json_decode($blocksRaw,true);
    if(!is_array($blocks)){
        throw new RuntimeException(
            'El diseño recibido no contiene un JSON válido: '.json_last_error_msg()
        );
    }

    cb3_fix_urls($blocks);

    $encoded=json_encode(
        $blocks,
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE
    );
    if($encoded===false){
        throw new RuntimeException('No se pudo serializar el diseño: '.json_last_error_msg());
    }
    $blocksRaw=$encoded;

    $listIds=array_values(array_unique(array_filter(array_map(
        'intval',
        (array)($_POST['list_ids']??[])
    ))));

    if($listIds){
        $ph=implode(',',array_fill(0,count($listIds),'?'));
        $q=db()->prepare('SELECT id FROM contact_lists WHERE tenant_id=? AND id IN ('.$ph.')');
        $q->execute([$tid,...$listIds]);
        $valid=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));

        if(count($valid)!==count($listIds)){
            throw new RuntimeException('Una de las listas seleccionadas ya no existe.');
        }
        $listIds=$valid;
    }

    $allowed=user_allowed_list_ids($tid);
    if($allowed!==null){
        foreach($listIds as $lid){
            if(!in_array($lid,$allowed,true)){
                throw new RuntimeException('No tienes autorización para una de las listas seleccionadas.');
            }
        }
        if(!$listIds){
            throw new RuntimeException('Selecciona al menos una lista autorizada.');
        }
    }

    $skipped=[];
    $html=cb3_compile_resilient($blocks,$id,$skipped);
    $primary=$listIds[0]??null;

    db()->beginTransaction();
    try {
        if($id){
            $st=db()->prepare(
                'UPDATE campaigns
                 SET name=?,subject=?,preheader=?,from_name=?,from_email=?,list_id=?,
                     blocks_json=?,html=?,updated_at=NOW()
                 WHERE id=? AND tenant_id=?'
            );
            $st->execute([
                $name,$subject,$preheader,$fromName,$fromEmail,$primary,
                $blocksRaw,$html,$id,$tid
            ]);
            if($st->rowCount()===0){
                $check=db()->prepare('SELECT id FROM campaigns WHERE id=? AND tenant_id=?');
                $check->execute([$id,$tid]);
                if(!$check->fetchColumn()) throw new RuntimeException('La campaña ya no existe o pertenece a otra empresa.');
            }
        } else {
            $st=db()->prepare(
                'INSERT INTO campaigns
                 (tenant_id,name,subject,preheader,from_name,from_email,list_id,
                  blocks_json,html,status,created_at,updated_at)
                 VALUES(?,?,?,?,?,?,?,?,?,"draft",NOW(),NOW())'
            );
            $st->execute([
                $tid,$name,$subject,$preheader,$fromName,$fromEmail,$primary,
                $blocksRaw,$html
            ]);
            $id=(int)db()->lastInsertId();
        }

        db()->prepare('DELETE FROM campaign_lists WHERE campaign_id=?')->execute([$id]);

        if($listIds){
            $ins=db()->prepare('INSERT INTO campaign_lists(campaign_id,list_id) VALUES(?,?)');
            foreach($listIds as $lid) $ins->execute([$id,$lid]);
        }

        db()->commit();
    } catch(Throwable $dbError) {
        if(db()->inTransaction()) db()->rollBack();
        throw $dbError;
    }

    if($skipped){
        $types=[];
        foreach($skipped as $item) $types[]=$item['type'].($item['id']!==''?' #'.$item['id']:'');
        flash(
            'error',
            'El diseño fue guardado, pero el compilador aisló '.count($skipped).
            ' bloque(s) problemático(s): '.implode(', ',$types).
            '. Revisa storage/builder3-save.log antes de enviar la campaña.'
        );
    } else {
        flash('ok','Campaña guardada correctamente.');
    }

    redirect('campaign-builder3.php?id='.$id.'&saved=1');

} catch(Throwable $e) {
    try {
        if(function_exists('db') && db()->inTransaction()) db()->rollBack();
    } catch(Throwable $ignore) {}

    $errorId=cb3_save_log($e,'save-request',$id);

    flash(
        'error',
        'No se pudo guardar la campaña. Código de diagnóstico: '.$errorId.
        ' · '.$e->getMessage()
    );

    redirect('campaign-builder3.php'.($id?'?id='.$id:''));
}
