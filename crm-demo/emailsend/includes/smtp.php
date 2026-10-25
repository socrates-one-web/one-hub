<?php
final class SimpleSMTP {
    private $fp; private array $cfg=[];

    private function read(): string {
        $r='';
        while(($line=fgets($this->fp,515))!==false){$r.=$line;if(strlen($line)<4||$line[3]!=='-')break;}
        return $r;
    }

    private function cmd(string $cmd,array $ok=[250]): string {
        fwrite($this->fp,$cmd."\r\n");$r=$this->read();$code=(int)substr($r,0,3);
        if(!in_array($code,$ok,true)) throw new RuntimeException("SMTP $code: ".trim($r));
        return $r;
    }

    private function plainText(string $html): string {
        $s=preg_replace('/<\s*br\s*\/?>/i',"\n",$html);
        $s=preg_replace('/<\/(p|div|h1|h2|h3|h4|li|tr)>/i',"\n",(string)$s);
        $s=html_entity_decode(strip_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $s=preg_replace("/[ \t]+/u",' ',$s);
        $s=preg_replace("/\n{3,}/u","\n\n",(string)$s);
        return trim((string)$s);
    }

    private function safeHeader(string $value): string { return str_replace(["\r","\n"],'',$value); }

    private function queueId(string $response): string {
        foreach([
            '/queued\s+as\s+<?([A-Za-z0-9._-]+)>?/i',
            '/queue(?:\s+id)?\s*[=:]\s*<?([A-Za-z0-9._-]+)>?/i',
            '/\bid[=: ]+<?([A-Za-z0-9._-]{5,})>?/i'
        ] as $rx){ if(preg_match($rx,$response,$m)) return (string)$m[1]; }
        return '';
    }

    private function writeDiagnostic(array $data): void {
        try{
            $dir=dirname(__DIR__).'/storage';
            if(!is_dir($dir)) @mkdir($dir,0755,true);
            unset($data['html']);
            @file_put_contents($dir.'/smtp-delivery.log',json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND|LOCK_EX);
        }catch(Throwable $ignore){}
    }

    private function mimeForFile(string $file): string {
        $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
        return [
            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'
        ][$ext]??'application/octet-stream';
    }

    /**
     * Resolve a public EMAILSEND image URL back to a local file so the image can
     * travel inside the email as a CID attachment. Only known image locations
     * below this installation are accepted.
     */
    private function localImageFromUrl(string $url): ?array {
        $decoded=html_entity_decode(trim($url),ENT_QUOTES|ENT_HTML5,'UTF-8');
        if($decoded==='') return null;

        $parts=@parse_url($decoded);
        if(!is_array($parts)) return null;

        $host=strtolower((string)($parts['host']??''));
        $allowed=[];
        if(defined('APP_URL')){
            $h=strtolower((string)parse_url((string)APP_URL,PHP_URL_HOST));
            if($h!=='')$allowed[]=$h;
        }
        $requestHost=strtolower(trim((string)($_SERVER['HTTP_HOST']??'')));
        if($requestHost!=='')$allowed[]=preg_replace('/:\d+$/','',$requestHost)??$requestHost;
        $allowed=array_values(array_unique(array_filter($allowed)));

        // Never download/attach arbitrary external URLs.
        if($host!=='' && !in_array($host,$allowed,true)) return null;

        $path=(string)($parts['path']??'');
        $query=[];
        parse_str((string)($parts['query']??''),$query);
        $root=dirname(__DIR__);
        $relative='';

        if(preg_match('~/(?:media|image)\.php$~i',$path) && !empty($query['f'])){
            $f=ltrim(str_replace('\\','/',rawurldecode((string)$query['f'])),'/');
            if(preg_match('~^\d{4}/\d{2}/[a-f0-9]{16,128}\.(?:jpg|jpeg|png|gif|webp)$~i',$f)){
                $relative='uploads/'.$f;
            }
        } elseif(preg_match('~/builder-media\.php$~i',$path) && !empty($query['show'])){
            $name=basename(rawurldecode((string)$query['show']));
            if(preg_match('~^[a-f0-9]{24}\.(?:jpg|jpeg|png|gif|webp)$~i',$name)){
                $relative='uploads/campaigns/'.$name;
            }
        } elseif(preg_match('~/builder3-media\.php$~i',$path) && !empty($query['show'])){
            $name=basename(rawurldecode((string)$query['show']));
            if(preg_match('~^[a-f0-9]{32}\.(?:jpg|jpeg|png|gif|webp)$~i',$name)){
                $relative='uploads/campaigns3/'.$name;
            }
        } elseif(preg_match('~/(uploads/(?:\d{4}/\d{2}/[a-f0-9]{16,128}|campaigns/[a-f0-9]{24}|campaigns3/[a-f0-9]{32})\.(?:jpg|jpeg|png|gif|webp))$~i',$path,$m)){
            $relative=$m[1];
        } elseif(preg_match('~/(uploads/[A-Za-z0-9._/-]+\.(?:jpg|jpeg|png|gif|webp))$~i',$path,$m)){
            $relative=$m[1];
        } elseif(preg_match('~/(img/[A-Za-z0-9._/-]+\.(?:jpg|jpeg|png|gif|webp))$~i',$path,$m)){
            // Legacy builders (including /img/builder5/...) stored public paths here.
            $relative=$m[1];
        } elseif(preg_match('~/(email-assets/[a-f0-9]{40,64}\.(?:jpg|jpeg|png|gif|webp))$~i',$path,$m)){
            $relative=$m[1];
        } elseif(preg_match('~/(assets/[A-Za-z0-9._/-]+\.(?:jpg|jpeg|png|gif|webp))$~i',$path,$m)){
            $relative=$m[1];
        }

        if($relative==='') return null;
        $relative=ltrim(str_replace('\\','/',$relative),'/');
        if(str_contains($relative,'../')) return null;

        $rootReal=realpath($root);
        $fileReal=realpath($root.'/'.$relative);
        if(!$rootReal||!$fileReal||!is_file($fileReal)||!is_readable($fileReal)) return null;
        $prefix=rtrim(str_replace('\\','/',$rootReal),'/').'/';
        if(!str_starts_with(str_replace('\\','/',$fileReal),$prefix)) return null;

        return [
            'file'=>$fileReal,
            'name'=>basename($fileReal),
            'mime'=>$this->mimeForFile($fileReal),
            'url'=>$decoded,
        ];
    }


    /**
     * v0.14.6 — Convert EMAILSEND image endpoints to public static HTTPS files.
     * Outlook/Gmail image proxies are more reliable with immutable image URLs
     * than with PHP endpoints or CID resources rewritten in transit.
     */
    private function publicBaseUrl(): string {
        $base=defined('APP_URL')?trim((string)APP_URL):'';
        
        if(!empty($_SERVER['HTTP_HOST'])){
            $host=preg_replace('/:\d+$/','',strtolower(trim((string)$_SERVER['HTTP_HOST'])))??'';
            if($host!==''){
                $script=(string)($_SERVER['SCRIPT_NAME']??'');
                $dir=rtrim(str_replace('\\','/',dirname($script)),'/');
                if($dir==='.'||$dir==='/')$dir='';
                $base='https://'.$host.$dir;
            }
        }
        if($base==='')$base=app_url();
        $base=preg_replace('~^http://~i','https://',$base)??$base;
        return rtrim($base,'/');
    }

    /**
     * Build an Outlook-safe static rendition for campaign images.
     * JPEG/PNG uploads are normalized with GD when available; WEBP is converted
     * to PNG so Outlook Classic never has to render WEBP. Existing hashed assets
     * that are already JPEG/PNG/GIF are reused without generational re-encoding.
     */
    private function safeStaticAssetForFile(string $file): ?array {
        $root=realpath(dirname(__DIR__));
        $real=realpath($file);
        if(!$root||!$real||!is_file($real)||!is_readable($real))return null;
        $rootN=rtrim(str_replace('\\','/',$root),'/').'/';
        $realN=str_replace('\\','/',$real);
        if(!str_starts_with($realN,$rootN))return null;

        $probe=@getimagesize($real);
        if(!is_array($probe))return null;
        $mime=strtolower((string)($probe['mime']??''));
        $width=max(1,(int)($probe[0]??0));
        $height=max(1,(int)($probe[1]??0));
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        if(!isset($allowed[$mime]))return null;

        $dir=dirname(__DIR__).'/email-assets';
        if(!is_dir($dir) && !@mkdir($dir,0755,true) && !is_dir($dir))return null;
        @chmod($dir,0755);

        // Already-normalized content-addressed assets can be reused directly.
        $rel=ltrim(substr($realN,strlen($rootN)),'/');
        if(preg_match('~^email-assets/[a-f0-9]{40}\.(?:jpg|jpeg|png|gif)$~i',$rel) && $mime!=='image/webp'){
            return ['file'=>$real,'mime'=>$mime,'width'=>$width,'height'=>$height,'name'=>basename($real)];
        }

        $tmp=$dir.'/.emailsafe-'.bin2hex(random_bytes(8)).'.tmp';
        $outMime=$mime;
        $ok=false;

        try{
            if($mime==='image/jpeg' && function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')){
                $im=@imagecreatefromjpeg($real);
                if($im!==false){
                    if(function_exists('imageinterlace'))@imageinterlace($im,false); // baseline JPEG
                    $ok=@imagejpeg($im,$tmp,90);
                    @imagedestroy($im);
                    $outMime='image/jpeg';
                }
            }elseif($mime==='image/png' && function_exists('imagecreatefrompng') && function_exists('imagepng')){
                $im=@imagecreatefrompng($real);
                if($im!==false){
                    @imagealphablending($im,false);@imagesavealpha($im,true);
                    $ok=@imagepng($im,$tmp,6);
                    @imagedestroy($im);
                    $outMime='image/png';
                }
            }elseif($mime==='image/webp' && function_exists('imagecreatefromwebp') && function_exists('imagepng')){
                $im=@imagecreatefromwebp($real);
                if($im!==false){
                    @imagealphablending($im,false);@imagesavealpha($im,true);
                    $ok=@imagepng($im,$tmp,6);
                    @imagedestroy($im);
                    $outMime='image/png';
                }
            }elseif($mime==='image/gif'){
                // Keep GIF bytes intact so animation is not destroyed.
                $ok=@copy($real,$tmp);
                $outMime='image/gif';
            }
        }catch(Throwable $ignore){$ok=false;}

        // Conservative fallback for already-supported JPEG/PNG/GIF when GD is unavailable.
        if(!$ok && in_array($mime,['image/jpeg','image/png','image/gif'],true)){
            @unlink($tmp);$ok=@copy($real,$tmp);$outMime=$mime;
        }
        if(!$ok || !is_file($tmp) || (int)@filesize($tmp)<1){@unlink($tmp);return null;}

        $probe2=@getimagesize($tmp);
        if(!is_array($probe2)){@unlink($tmp);return null;}
        $outMime=strtolower((string)($probe2['mime']??$outMime));
        $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'][$outMime]??'';
        if($ext===''){@unlink($tmp);return null;}
        $hash=@sha1_file($tmp);
        if(!is_string($hash)||!preg_match('/^[a-f0-9]{40}$/',$hash)){@unlink($tmp);return null;}
        $name=$hash.'.'.$ext;
        $dest=$dir.'/'.$name;
        if(!is_file($dest) || (int)@filesize($dest)!==(int)@filesize($tmp)){
            if(!@rename($tmp,$dest)){
                if(!@copy($tmp,$dest)){@unlink($tmp);return null;}
                @unlink($tmp);
            }
        }else @unlink($tmp);
        @chmod($dest,0644);
        $finalProbe=@getimagesize($dest);
        return [
            'file'=>$dest,
            'mime'=>$outMime,
            'width'=>max(1,(int)($finalProbe[0]??$width)),
            'height'=>max(1,(int)($finalProbe[1]??$height)),
            'name'=>$name,
        ];
    }

    private function publicStaticUrlForFile(string $file): string {
        $asset=$this->safeStaticAssetForFile($file);
        if(!$asset)return '';
        return $this->publicBaseUrl().'/email-assets/'.rawurlencode((string)$asset['name']);
    }

    private function rewriteLocalImagesToStatic(string $html): array {
        $items=[];$byFile=[];$bytes=0;
        $rewrite=function(string $raw) use (&$items,&$byFile,&$bytes): string {
            $decoded=html_entity_decode(trim($raw),ENT_QUOTES|ENT_HTML5,'UTF-8');
            if($decoded==='')return $raw;
            $info=$this->localImageFromUrl($decoded);
            if(!$info)return $raw;
            $file=(string)$info['file'];
            if(isset($byFile[$file]))return $byFile[$file];
            $static=$this->publicStaticUrlForFile($file);
            if($static==='')return $raw;
            $size=(int)@filesize($file);
            $byFile[$file]=$static;
            $staticInfo=$this->localImageFromUrl($static);
            if($staticInfo)$byFile[(string)$staticInfo['file']]=$static;
            $bytes+=max(0,$size);
            $items[]=$info+['static_url'=>$static,'size'=>$size];
            return $static;
        };

        // 1) IMG/background attributes, including root-relative legacy URLs.
        $rewritten=preg_replace_callback('~(\\b(?:src|background)\\s*=\\s*)(["\\\'])(.*?)\\2~is',function(array $m) use ($rewrite){
            $url=$rewrite((string)$m[3]);
            return (string)$m[1].(string)$m[2].htmlspecialchars($url,ENT_QUOTES|ENT_HTML5,'UTF-8').(string)$m[2];
        },$html);
        if(!is_string($rewritten))$rewritten=$html;

        // 2) CSS background-image:url(...), also used by hero/banner blocks.
        $rewritten2=preg_replace_callback('~url\\(\\s*(["\\\']?)([^)"\\\']+)\\1\\s*\\)~i',function(array $m) use ($rewrite){
            $url=$rewrite(trim((string)$m[2]));
            $q=(string)$m[1];if($q==='')$q="'";
            return 'url('.$q.$url.$q.')';
        },$rewritten);
        if(is_string($rewritten2))$rewritten=$rewritten2;

        // 3) Last pass for absolute local URLs that may live inside custom HTML.
        $rewritten3=preg_replace_callback('~https?://[^"\\\'\\s<>()]+~i',function(array $m) use ($rewrite){return $rewrite((string)$m[0]);},$rewritten);
        if(is_string($rewritten3))$rewritten=$rewritten3;
        return [$rewritten,$items,$bytes];
    }


    /**
     * v0.15.7 — normalize escaped line breaks emitted by some Builder text blocks.
     * Campaign text must never expose literal "\\r\\n" sequences in Outlook.
     */
    private function normalizeEscapedBuilderBreaks(string $html): string {
        // Decode the common HTML-entity forms first.
        $html=str_ireplace([
            '&#92;r&#92;n','&#92;n','&#92;r',
            '&#x5c;r&#x5c;n','&#x5c;n','&#x5c;r',
            '&#x5C;r&#x5C;n','&#x5C;n','&#x5C;r',
            '&bsol;r&bsol;n','&bsol;n','&bsol;r'
        ],'<br>',$html);
        // Reduce double-escaped JSON strings to the normal escaped representation.
        for($i=0;$i<3;$i++){
            $html=str_replace(['\\\\r\\\\n','\\\\n','\\\\r'],['\\r\\n','\\n','\\r'],$html);
        }
        return str_replace(['\\r\\n','\\n','\\r'],'<br>',$html);
    }

    private function outlookContainerWidth(string $tag,int $parentWidth): int {
        $style=$this->htmlImgAttr($tag,'style');
        $raw=$this->htmlImgAttr($tag,'width');
        $w=0;
        if(preg_match('/^(\\d+(?:\\.\\d+)?)$/',$raw,$m))$w=(int)round((float)$m[1]);
        elseif(preg_match('/^(\\d+(?:\\.\\d+)?)%$/',$raw,$m) && $parentWidth>0)$w=(int)round($parentWidth*((float)$m[1]/100));
        if(!$w && preg_match('~(?:^|;)\\s*width\\s*:\\s*(\\d+(?:\\.\\d+)?)px\\b~i',$style,$m))$w=(int)round((float)$m[1]);
        if(!$w && preg_match('~(?:^|;)\\s*width\\s*:\\s*(\\d+(?:\\.\\d+)?)%\\b~i',$style,$m) && $parentWidth>0)$w=(int)round($parentWidth*((float)$m[1]/100));
        $max=0;
        if(preg_match('~(?:^|;)\\s*max-width\\s*:\\s*(\\d+(?:\\.\\d+)?)px\\b~i',$style,$m))$max=(int)round((float)$m[1]);
        if(!$w && $max>0)$w=$max;
        if(!$w && $parentWidth>0)$w=$parentWidth;
        if($max>0 && $w>0)$w=min($w,$max);
        return max(0,$w);
    }

    /**
     * Returns the nearest authored container width for each IMG without
     * serializing or changing the campaign HTML.
     */
    private function outlookImageContexts(string $html): array {
        $out=[];$stack=[];
        if(!preg_match_all('~<\\s*(/?)\\s*(table|td|th|div|section|main)\\b[^>]*>|<\\s*img\\b[^>]*>~is',$html,$tokens,PREG_SET_ORDER))return [];
        foreach($tokens as $tok){
            $full=(string)$tok[0];
            if(preg_match('~^<\\s*img\\b~i',$full)){
                $out[]=$stack?(int)$stack[count($stack)-1]['width']:0;
                continue;
            }
            if(!preg_match('~^<\\s*(/?)\\s*(table|td|th|div|section|main)\\b~i',$full,$m))continue;
            $closing=((string)$m[1])==='/';$name=strtolower((string)$m[2]);
            if($closing){
                for($i=count($stack)-1;$i>=0;$i--){
                    $entry=array_pop($stack);
                    if(($entry['name']??'')===$name)break;
                }
                continue;
            }
            $parent=$stack?(int)$stack[count($stack)-1]['width']:0;
            $w=$this->outlookContainerWidth($full,$parent);
            $stack[]=['name'=>$name,'width'=>$w];
        }
        return $out;
    }

    private function outlookDisplaySize(string $tag,int $contextW,int $intrinsicW,int $intrinsicH): array {
        $style=$this->htmlImgAttr($tag,'style');
        $rawW=$this->htmlImgAttr($tag,'width');
        $rawH=$this->htmlImgAttr($tag,'height');
        $w=0;$h=0;
        if(preg_match('/^(\\d+(?:\\.\\d+)?)$/',$rawW,$m))$w=(int)round((float)$m[1]);
        elseif(preg_match('/^(\\d+(?:\\.\\d+)?)%$/',$rawW,$m) && $contextW>0)$w=(int)round($contextW*((float)$m[1]/100));
        if(!$w && preg_match('~(?:^|;)\\s*width\\s*:\\s*(\\d+(?:\\.\\d+)?)px\\b~i',$style,$m))$w=(int)round((float)$m[1]);
        if(!$w && preg_match('~(?:^|;)\\s*width\\s*:\\s*(\\d+(?:\\.\\d+)?)%\\b~i',$style,$m) && $contextW>0)$w=(int)round($contextW*((float)$m[1]/100));
        $max=0;
        if(preg_match('~(?:^|;)\\s*max-width\\s*:\\s*(\\d+(?:\\.\\d+)?)px\\b~i',$style,$m))$max=(int)round((float)$m[1]);
        if(!$w && $contextW>0)$w=$contextW;
        if(!$w && $intrinsicW>0)$w=min($intrinsicW,680);
        if(!$w)$w=600;
        if($contextW>0)$w=min($w,$contextW);
        if($max>0)$w=min($w,$max);
        if($intrinsicW>0)$w=min($w,$intrinsicW);
        $w=max(1,min(1200,(int)$w));

        if(preg_match('/^(\\d+(?:\\.\\d+)?)$/',$rawH,$m))$h=(int)round((float)$m[1]);
        if(!$h && preg_match('~(?:^|;)\\s*height\\s*:\\s*(\\d+(?:\\.\\d+)?)px\\b~i',$style,$m))$h=(int)round((float)$m[1]);
        if(!$h && $intrinsicW>0 && $intrinsicH>0)$h=(int)round($intrinsicH*($w/$intrinsicW));
        if(!$h)$h=$w;
        $h=max(1,min(1600,(int)$h));
        return [$w,$h];
    }

    /**
     * Outlook Classic fallback: Word renders local campaign images through VML,
     * while every non-MSO client receives the original IMG unchanged.
     * This bypasses the broken remote-IMG path seen in Outlook without changing
     * the Builder layout for Gmail/webmail/mobile clients.
     */
    private function outlookVmlImages(string $html): array {
        $contexts=$this->outlookImageContexts($html);$idx=0;$converted=0;
        $out=preg_replace_callback('~<img\\b[^>]*>~i',function(array $m) use (&$idx,$contexts,&$converted){
            $tag=(string)$m[0];$context=(int)($contexts[$idx]??0);$idx++;
            $src=$this->htmlImgAttr($tag,'src');
            if($src==='')return $tag;
            $info=$this->localImageFromUrl($src);
            if(!$info)return $tag; // External images stay untouched.
            $probe=@getimagesize((string)$info['file']);
            if(!is_array($probe))return $tag;
            $iw=max(1,(int)($probe[0]??1));$ih=max(1,(int)($probe[1]??1));
            [$w,$h]=$this->outlookDisplaySize($tag,$context,$iw,$ih);
            $safeSrc=htmlspecialchars($src,ENT_QUOTES|ENT_HTML5,'UTF-8');
            $converted++;
            $mso='<!--[if mso]>'
                .'<v:image xmlns:v="urn:schemas-microsoft-com:vml" src="'.$safeSrc.'" '
                .'style="behavior:url(#default#VML);display:inline-block;width:'.$w.'px;height:'.$h.'px;border:0;" />'
                .'<![endif]-->';
            $nonMso='<!--[if !mso]><!-->'.$tag.'<!--<![endif]-->';
            return $mso.$nonMso;
        },$html);
        return [is_string($out)?$out:$html,$converted];
    }

    private function htmlImgAttr(string $tag,string $name): string {
        if(preg_match('~\\b'.preg_quote($name,'~').'\\s*=\\s*(["\\\'])(.*?)\\1~is',$tag,$m))return html_entity_decode((string)$m[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
        if(preg_match('~\\b'.preg_quote($name,'~').'\\s*=\\s*([^\\s>]+)~is',$tag,$m))return trim((string)$m[1]," \\t\\n\\r\\0\\x0B\\\"'");
        return '';
    }

    private function setHtmlImgAttr(string $tag,string $name,string $value): string {
        $encoded=htmlspecialchars($value,ENT_QUOTES|ENT_HTML5,'UTF-8');
        $rx='~\\s+'.preg_quote($name,'~').'\\s*=\\s*(?:(["\\\']).*?\\1|[^\\s>]+)~is';
        if(preg_match($rx,$tag))return (string)preg_replace($rx,' '.$name.'="'.$encoded.'"',$tag,1);
        return (string)preg_replace('~\\s*/?>$~',' '.$name.'="'.$encoded.'">',$tag,1);
    }

    /**
     * Outlook Classic uses Word's HTML engine. Give every IMG a real width
     * attribute plus conservative inline CSS and remove browser-only hints that
     * can interfere with desktop mail rendering.
     */
    private function outlookSafeHtml(string $html): string {
        $out=preg_replace_callback('~<img\\b[^>]*>~i',function(array $m){
            $tag=(string)$m[0];
            $tag=(string)preg_replace('~\\s+(?:loading|decoding|fetchpriority|srcset|sizes)\\s*=\\s*(?:(["\\\']).*?\\1|[^\\s>]+)~is','',$tag);
            $src=$this->htmlImgAttr($tag,'src');
            if($src==='')return $tag;
            $style=$this->htmlImgAttr($tag,'style');
            $info=$this->localImageFromUrl($src);
            $intrinsicW=0;$intrinsicH=0;
            if($info){
                $probe=@getimagesize((string)$info['file']);
                if(is_array($probe)){$intrinsicW=(int)($probe[0]??0);$intrinsicH=(int)($probe[1]??0);}
            }
            $widthAttr=$this->htmlImgAttr($tag,'width');
            $displayW=0;
            if(preg_match('/^\\d+$/',$widthAttr))$displayW=(int)$widthAttr;
            if(!$displayW && preg_match('~(?:^|;)\\s*width\\s*:\\s*(\\d+(?:\\.\\d+)?)px~i',$style,$wm))$displayW=(int)round((float)$wm[1]);
            if(!$displayW && preg_match('~(?:^|;)\\s*max-width\\s*:\\s*(\\d+(?:\\.\\d+)?)px~i',$style,$wm))$displayW=(int)round((float)$wm[1]);
            if(!$displayW && $intrinsicW>0)$displayW=$intrinsicW;
            if($displayW>0)$tag=$this->setHtmlImgAttr($tag,'width',(string)max(1,min(2400,$displayW)));
            if($this->htmlImgAttr($tag,'alt')==='')$tag=$this->setHtmlImgAttr($tag,'alt','');
            $safe='display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;';
            if($intrinsicW>0 && preg_match('~(?:^|;)\\s*width\\s*:\\s*100%~i',$style) && !preg_match('~max-width\\s*:~i',$style))$safe.='max-width:'.$intrinsicW.'px;';
            if(!preg_match('~(?:^|;)\\s*height\\s*:~i',$style))$safe.='height:auto;';
            $style=rtrim(trim($style),';');
            $tag=$this->setHtmlImgAttr($tag,'style',($style!==''?$style.';':'').$safe);
            $tag=$this->setHtmlImgAttr($tag,'border','0');
            return $tag;
        },$html);
        return is_string($out)?$out:$html;
    }

    private function mimeHeaderText(string $value): string {
        $value=$this->safeHeader($value);
        if($value==='' || preg_match('/^[\\x20-\\x7E]+$/',$value))return $value;
        return '=?UTF-8?B?'.base64_encode($value).'?=';
    }

    private function qp(string $value): string {
        $value=(string)preg_replace("~\\r?\\n~","\\r\\n",$value);
        return quoted_printable_encode($value);
    }

    public function diagnoseStaticImages(string $html,string $from=''): array {
        [$rewritten,$items,$bytes]=$this->rewriteLocalImagesToStatic($html);
        $candidates=[];
        if(preg_match_all('~\\b(?:src|background)\\s*=\\s*(["\\\'])(.*?)\\1~is',$html,$m)){
            foreach($m[2] as $u)$candidates[]=html_entity_decode(trim((string)$u),ENT_QUOTES|ENT_HTML5,'UTF-8');
        }
        if(preg_match_all('~url\\(\\s*(["\\\']?)([^)"\\\']+)\\1\\s*\\)~i',$html,$m)){
            foreach($m[2] as $u)$candidates[]=html_entity_decode(trim((string)$u),ENT_QUOTES|ENT_HTML5,'UTF-8');
        }
        $candidates=array_values(array_unique(array_filter($candidates,fn($u)=>$u!=='')));
        $all=[];$safeCount=0;
        foreach($candidates as $decoded){
            $parts=@parse_url($decoded);$path=(string)($parts['path']??'');
            if(!preg_match('~\\.(?:jpg|jpeg|png|gif|webp)(?:$|\\?)|(?:media|image|builder-media|builder3-media)\\.php~i',$decoded))continue;
            $info=$this->localImageFromUrl($decoded);
            $static=$info?$this->publicStaticUrlForFile((string)$info['file']):'';
            $isAlreadySafe=(bool)preg_match('~/email-assets/[a-f0-9]{40}\\.(?:jpg|jpeg|png|gif)$~i',$decoded);
            $ready=$static!=='' || $isAlreadySafe;
            if($ready)$safeCount++;
            $all[]=[
                'url'=>$decoded,
                'path'=>$path,
                'embeddable'=>$ready,
                'file'=>$info?(string)$info['file']:'',
                'name'=>$info?(string)$info['name']:'',
                'mime'=>$info?(string)$info['mime']:'',
                'bytes'=>$info?(int)@filesize((string)$info['file']):0,
                'static_url'=>$static!==''?$static:($isAlreadySafe?$decoded:''),
                'outlook_safe'=>$ready && !preg_match('~\\.webp(?:$|\\?)~i',$static!==''?$static:$decoded),
            ];
        }
        return [
            'detected'=>count($all),
            'embeddable'=>$safeCount,
            'bytes'=>$bytes,
            'items'=>$all,
            'html_uses_static'=>$safeCount>0 && ($rewritten!==$html || count($all)===$safeCount),
            'mode'=>'outlook-safe-cloud-html',
        ];
    }

    /**
     * v0.14.7 — Prepare local images for a dual Outlook/Gmail strategy.
     *
     * HTML keeps a public HTTPS URL as its src/background reference. The same
     * binary is also attached inside multipart/related with Content-Location
     * equal to that exact HTTPS URL. Exchange/Outlook can therefore resolve
     * the inline resource from the MIME part without needing to fetch it, while
     * Gmail and other clients still have a normal HTTPS fallback.
     */
    private function prepareRelatedLocationImages(string $html): array {
        [$rewritten,$items,$ignoredBytes]=$this->rewriteLocalImagesToStatic($html);
        $related=[];
        $bytes=0;
        foreach($items as $item){
            $file=(string)($item['file']??'');
            $url=(string)($item['static_url']??'');
            if($file===''||$url===''||!is_file($file)||!is_readable($file))continue;
            $size=(int)@filesize($file);
            if($size<1||$size>12*1024*1024)continue;
            $probe=@getimagesize($file);
            if(!is_array($probe))continue;
            $mime=strtolower((string)($probe['mime']??$this->mimeForFile($file)));
            if(!in_array($mime,['image/jpeg','image/png','image/gif','image/webp'],true))continue;
            $related[]=$item+['mime'=>$mime,'size'=>$size,'static_url'=>$url];
            $bytes+=$size;
        }
        return [(string)$rewritten,$related,$bytes];
    }

    /**
     * Embed locally hosted images as CID resources. This avoids Outlook/Gmail
     * having to request PHP image endpoints after the message arrives.
     */
    private function embedLocalImages(string $html,string $messageToken,string $cidDomain): array {
        $items=[];
        $byFile=[];
        $total=0;
        $maxTotal=25*1024*1024; // conservative safety ceiling for one message

        $pattern='~https?://[^"\'\s<>()]+~i';
        $rewritten=preg_replace_callback($pattern,function(array $m) use (&$items,&$byFile,&$total,$maxTotal,$messageToken,$cidDomain){
            $raw=(string)$m[0];
            $info=$this->localImageFromUrl($raw);
            if(!$info) return $raw;

            $file=(string)$info['file'];
            if(isset($byFile[$file])) return 'cid:'.$byFile[$file];

            $size=(int)@filesize($file);
            if($size<1 || $size>12*1024*1024 || ($total+$size)>$maxTotal) return $raw;

            $cid='img'.(count($items)+1).'.'.$messageToken.'@'.$cidDomain;
            $byFile[$file]=$cid;
            $total+=$size;
            $items[]=$info+['cid'=>$cid,'size'=>$size];
            return 'cid:'.$cid;
        },$html);

        return [(string)$rewritten,$items,$total];
    }

    /**
     * Diagnose which image URLs in a campaign can be converted to CID resources.
     * This does not send anything and never exposes file contents.
     */
    public function diagnoseInlineImages(string $html,string $from=''): array {
        $domain=strtolower(trim((string)substr(strrchr($from,'@')?:'',1)));
        if($domain==='' || !preg_match('/^[a-z0-9.-]+$/i',$domain)) $domain='emailsend.local';
        $token='diag'.substr(bin2hex(random_bytes(6)),0,12);
        [$rewritten,$items,$bytes]=$this->embedLocalImages($html,$token,$domain);

        $all=[];
        if(preg_match_all('~https?://[^"\'\s<>()]+~i',$html,$m)){
            foreach(array_values(array_unique($m[0])) as $url){
                $decoded=html_entity_decode((string)$url,ENT_QUOTES|ENT_HTML5,'UTF-8');
                $parts=@parse_url($decoded);
                $host=strtolower((string)($parts['host']??''));
                $path=(string)($parts['path']??'');
                if(!preg_match('~\.(?:jpg|jpeg|png|gif|webp)(?:$|\?)|(?:media|image|builder-media|builder3-media)\.php~i',$decoded)) continue;
                $info=$this->localImageFromUrl($decoded);
                $all[]=[
                    'url'=>$decoded,
                    'host'=>$host,
                    'path'=>$path,
                    'embeddable'=>(bool)$info,
                    'file'=>$info?(string)$info['file']:'',
                    'name'=>$info?(string)$info['name']:'',
                    'mime'=>$info?(string)$info['mime']:'',
                    'bytes'=>$info?(int)@filesize((string)$info['file']):0,
                ];
            }
        }
        $embeddedFiles=[];$cidByFile=[];$allCidValid=true;
        foreach($items as $it){
            $cid=(string)$it['cid'];
            $valid=(bool)preg_match('/^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+$/',$cid);
            if(!$valid)$allCidValid=false;
            $cidByFile[(string)$it['file']]=$cid;
            $embeddedFiles[]=[
                'url'=>(string)$it['url'],
                'file'=>(string)$it['file'],
                'name'=>(string)$it['name'],
                'mime'=>(string)$it['mime'],
                'bytes'=>(int)$it['size'],
                'cid'=>$cid,
                'cid_valid'=>$valid,
            ];
        }
        foreach($all as &$row){
            $row['cid']=$row['file']!==''?($cidByFile[$row['file']]??''):'';
            $row['cid_valid']=$row['cid']!=='' && (bool)preg_match('/^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+$/',(string)$row['cid']);
        }
        unset($row);
        return [
            'detected'=>count($all),
            'embeddable'=>count($items),
            'bytes'=>$bytes,
            'items'=>$all,
            'embedded'=>$embeddedFiles,
            'html_uses_cid'=>stripos($rewritten,'cid:')!==false,
            'cid_valid'=>count($items)>0 && $allCidValid,
            'cid_domain'=>$domain,
        ];
    }

    private function buildMimeMessage(array $cfg,string $from,string $to,string $subject,string $html,array $headers,string $ehlo,string $authUser): array {
        $fromName=trim((string)($cfg['from_name']??''));
        $safeName=str_replace(["\r","\n",'"'],['','','\\"'],$fromName);
        $fromHeader=$safeName!==''?$this->mimeHeaderText($safeName).' <'.$from.'>':$from;
        $token=bin2hex(random_bytes(10));
        $messageId='<'.bin2hex(random_bytes(12)).'@'.($ehlo?:'emailsend.local').'>';
        $altBoundary='=_EMAILSEND_ALT_'.$token;
        $matrixMode=((string)($headers['X-EMAILSEND-Diagnostic-Image-Matrix']??''))==='1';

        /*
         * v0.15.8 — The Outlook image matrix is deliberately transported
         * untouched. Delivery Lab already generated one immutable
         * /email-assets/ URL and places the same URL in five render methods.
         * Rewriting it here would invalidate the experiment.
         */
        if($matrixMode){
            $htmlForSend=$html;
            $cloudImages=[];$cloudBytes=0;$vmlImages=0;
            $mailer='EMAILSEND ONE/0.15.8';
            $mimeMode='alternative-outlook-image-matrix-v10';
            $imageMode='diagnostic-image-matrix';
        }else{
            [$htmlForSend,$cloudImages,$cloudBytes]=$this->rewriteLocalImagesToStatic($html);
            $htmlForSend=$this->normalizeEscapedBuilderBreaks($htmlForSend);
            [$htmlForSend,$vmlImages]=$this->outlookVmlImages($htmlForSend);
            $htmlForSend=$this->outlookSafeHtml($htmlForSend);
            $mailer='EMAILSEND ONE/0.15.8';
            $mimeMode='alternative-outlook-vml-fallback-v9';
            $imageMode='outlook-safe-cloud-html';
        }

        $h=[
            'From: '.$fromHeader,
            'To: '.$to,
            'Subject: '.$this->mimeHeaderText($subject),
            'Date: '.date(DATE_RFC2822),
            'Message-ID: '.$messageId,
            'MIME-Version: 1.0',
            'X-Mailer: '.$mailer,
            'X-EMAILSEND-MIME: '.$mimeMode,
            'X-EMAILSEND-Outlook-Renderer: '.($matrixMode?'five-method-matrix':'VML-for-local-images'),
            'X-EMAILSEND-Outlook-VML-Images: '.$vmlImages,
            'X-EMAILSEND-Image-Mode: '.$imageMode,
            'X-EMAILSEND-Cloud-Images: '.count($cloudImages),
            'X-EMAILSEND-Cloud-Bytes: '.$cloudBytes,
            'Content-Type: multipart/alternative; boundary="'.$altBoundary.'"',
        ];

        if($authUser!=='' && filter_var($authUser,FILTER_VALIDATE_EMAIL) && strcasecmp($authUser,$from)!==0){$h[]='Sender: '.$authUser;}
        foreach($headers as $k=>$v){
            $k=$this->safeHeader((string)$k);$v=$this->safeHeader((string)$v);
            if($k!==''&&$v!=='')$h[]=$k.': '.$v;
        }

        $plain=$this->plainText($htmlForSend);
        if($plain==='')$plain='Este mensaje contiene una campaña HTML.';
        $plainBody=$this->qp($plain);

        if($matrixMode){
            // Base64 keeps the diagnostic HTML byte-for-byte stable so that
            // quoted-printable wrapping cannot become another variable.
            $htmlBody=rtrim(chunk_split(base64_encode($htmlForSend),76,"\r\n"));
            $htmlTransfer='base64';
        }else{
            $htmlBody=$this->qp($htmlForSend);
            $htmlTransfer='quoted-printable';
        }

        $body='--'.$altBoundary."\r\n"
            .'Content-Type: text/plain; charset=UTF-8'."\r\n"
            .'Content-Transfer-Encoding: quoted-printable'."\r\n\r\n"
            .$plainBody."\r\n"
            .'--'.$altBoundary."\r\n"
            .'Content-Type: text/html; charset=UTF-8'."\r\n"
            .'Content-Transfer-Encoding: '.$htmlTransfer."\r\n\r\n"
            .$htmlBody."\r\n"
            .'--'.$altBoundary.'--'."\r\n";

        $msg=implode("\r\n",$h)."\r\n\r\n".$body;
        $msg=(string)preg_replace('/(?m)^\./','..',$msg);
        return [$msg,[
            'message_id'=>$messageId,
            'related_images'=>0,
            'related_image_bytes'=>0,
            'static_images'=>$matrixMode?0:count($cloudImages),
            'static_image_bytes'=>$matrixMode?0:$cloudBytes,
            'inline_images'=>0,
            'inline_image_bytes'=>0,
            'image_mode'=>$imageMode,
            'cid_domain'=>'',
        ]];
    }

    public function send(array $cfg,string $from,string $to,string $subject,string $html,array $headers=[]): array {
        // Defense in depth: the CRM-DEMO edition must never send real email.
        throw new RuntimeException('MODO DEMO: el envío de correos está desactivado permanentemente.');
        $this->cfg=$cfg;$host=trim((string)($cfg['host']??''));$port=(int)($cfg['port']??587);$secure=$cfg['secure']??'tls';
        if(!$host) throw new RuntimeException('Host SMTP vacío.');
        if(!filter_var($from,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Remitente SMTP inválido.');
        if(!filter_var($to,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Destinatario inválido.');
        if(!in_array($secure,['tls','ssl','none'],true))$secure='tls';
        $ssl=[
            'verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false,
            'peer_name'=>$host,'SNI_enabled'=>true,'capture_peer_cert'=>true,
            'crypto_method'=>STREAM_CRYPTO_METHOD_TLS_CLIENT,
        ];
        $context=stream_context_create(['ssl'=>$ssl]);
        $remote=($secure==='ssl'?'ssl://':'').$host.':'.$port;
        $this->fp=@stream_socket_client($remote,$errno,$errstr,20,STREAM_CLIENT_CONNECT,$context);
        if(!$this->fp) throw new RuntimeException('No se pudo conectar al SMTP: '.$errstr.' ('.$errno.')');
        stream_set_timeout($this->fp,30);
        $greeting=$this->read();if((int)substr($greeting,0,3)!==220)throw new RuntimeException('El servidor SMTP no respondió correctamente: '.trim($greeting));
        $ehlo=$_SERVER['SERVER_NAME']??(defined('APP_URL')?parse_url(APP_URL,PHP_URL_HOST):null)??'emailsend.local';
        $this->cmd('EHLO '.$ehlo,[250]);
        if($secure==='tls'){
            $this->cmd('STARTTLS',[220]);
            if(!@stream_socket_enable_crypto($this->fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('No se pudo establecer TLS con certificado válido.');
            $this->cmd('EHLO '.$ehlo,[250]);
        }
        $authUser=trim((string)($cfg['user']??''));
        if($authUser!==''){
            $this->cmd('AUTH LOGIN',[334]);
            $this->cmd(base64_encode($authUser),[334]);
            $this->cmd(base64_encode((string)($cfg['pass']??'')),[235]);
        }

        $envelopeFrom=$from;
        $this->cmd('MAIL FROM:<'.$envelopeFrom.'>',[250]);
        $rcptResponse=$this->cmd('RCPT TO:<'.$to.'>',[250,251]);
        $this->cmd('DATA',[354]);

        [$msg,$meta]=$this->buildMimeMessage($cfg,$from,$to,$subject,$html,$headers,(string)$ehlo,$authUser);
        $bytes=strlen($msg);
        fwrite($this->fp,$msg.".\r\n");
        $dataResponse=$this->read();$dataCode=(int)substr($dataResponse,0,3);
        if($dataCode!==250)throw new RuntimeException('SMTP DATA '.$dataCode.': '.trim($dataResponse));
        try{$this->cmd('QUIT',[221]);}catch(Throwable $e){}
        fclose($this->fp);

        $result=[
            'ok'=>true,'message_id'=>(string)$meta['message_id'],'queue_id'=>$this->queueId($dataResponse),
            'smtp_response'=>trim($dataResponse),'rcpt_response'=>trim($rcptResponse),
            'from'=>$from,'envelope_from'=>$envelopeFrom,'auth_user'=>$authUser,'to'=>$to,
            'subject'=>$subject,'bytes'=>$bytes,'host'=>$host,'port'=>$port,'secure'=>$secure,
            'static_images'=>(int)$meta['static_images'],'static_image_bytes'=>(int)$meta['static_image_bytes'],
            'inline_images'=>(int)$meta['inline_images'],'inline_image_bytes'=>(int)$meta['inline_image_bytes'],
            'related_images'=>(int)$meta['related_images'],'related_image_bytes'=>(int)$meta['related_image_bytes'],
            'image_mode'=>(string)$meta['image_mode'],'time'=>date('c')
        ];
        $this->writeDiagnostic($result);
        return $result;
    }

}