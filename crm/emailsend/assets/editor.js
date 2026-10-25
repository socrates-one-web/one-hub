(function(){
  const root=document.querySelector('[data-editor]'); if(!root)return;
  const blocksInput=document.getElementById('blocks_json'), htmlInput=document.getElementById('compiled_html');
  const page=document.getElementById('emailPage'), inspector=document.getElementById('inspector'), device=document.getElementById('deviceFrame');
  const previewModal=document.getElementById('campaignPreviewModal'), previewFrame=document.getElementById('campaignPreviewFrame'), previewViewport=document.getElementById('campaignPreviewViewport');
  let blocks=[];try{blocks=JSON.parse(blocksInput.value||'[]')}catch(e){}
  if(!Array.isArray(blocks)||!blocks.length)blocks=[
    {type:'hero',src:'',title:'Tu próxima campaña empieza aquí',text:'Un mensaje claro, visual y directo para tus contactos.',button:'CONOCER MÁS',href:'#',style:{background:'#002f57',color:'#ffffff',textColor:'#ffffff',buttonBg:'#ff5300',buttonColor:'#ffffff',padding:'48px 30px',align:'left',fontSize:'34px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'4px',blockWidth:'100%',blockAlign:'center',radius:'0'}},
    {type:'text',content:'Escribe aquí el contenido principal de tu campaña.',style:{color:'#1f2937',background:'#ffffff',padding:'20px 28px',align:'left',fontSize:'16px',fontFamily:'Ubuntu Sans',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center',radius:'0'}}
  ];
  let selected=0, selectedCellPath=null, activeSelectionContext=null, history=[], future=[], activeDrag=null, suppressColumnContentInput=false;
  const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const clone=o=>JSON.parse(JSON.stringify(o));
  const clamp=(n,min,max)=>Math.min(max,Math.max(min,n));
  const px=n=>`${Math.round(Number(n)||0)}px`;
  const appBase=String(window.EMAILSEND_APP_URL||window.location.origin).replace(/\/+$/,'');
  function uploadProxyUrl(relative){
    const clean=String(relative||'').replace(/^\/+/,'');
    const m=clean.match(/^uploads\/(\d{4}\/\d{2}\/[a-f0-9]{16,128}\.(?:jpg|jpeg|png|gif|webp))$/i);
    if(!m)return appBase+'/'+clean;
    return appBase+'/media.php?f='+encodeURIComponent(m[1]);
  }
  function normalizeAssetUrl(value){
    let url=String(value||'').trim();
    if(!url)return '';

    if(/\/media\.php\?f=/i.test(url))return url;

    let m=url.match(/^(?:https?:\/\/[^/]+)?\/?image\.php\?f=([^&]+)/i);
    if(m)return appBase+'/media.php?f='+encodeURIComponent(decodeURIComponent(m[1]));

    m=url.match(/^(?:https?:\/\/[^/]+)?\/?index\.php\?route=image_asset&f=([^&]+)/i);
    if(m)return appBase+'/media.php?f='+encodeURIComponent(decodeURIComponent(m[1]));

    const replacements=[
    ];
    for(const re of replacements){
      if(re.test(url))return uploadProxyUrl(url.replace(re,'').replace(/^\/+/,''));
    }

    if(url.toLowerCase().startsWith((appBase+'/').toLowerCase())){
      const relative=url.slice(appBase.length+1);
      if(/^uploads\//i.test(relative))return uploadProxyUrl(relative);
    }

    if(/^\/sendmail\//i.test(url))return uploadProxyUrl(url.replace(/^\/sendmail\//i,'').replace(/^\/+/,''));
    if(/^\/uploads\//i.test(url))return uploadProxyUrl(url.replace(/^\/+/,''));
    if(/^uploads\//i.test(url))return uploadProxyUrl(url);

    return url;
  }
  function normalizeImageFields(block){
    if(!block||typeof block!=='object')return;
    ['src','thumbnail','avatar'].forEach(k=>{if(typeof block[k]==='string')block[k]=normalizeAssetUrl(block[k]);});
    if(block.style&&typeof block.style.backgroundImage==='string')block.style.backgroundImage=normalizeAssetUrl(block.style.backgroundImage);
  }
  function stripInlineTypography(html,props=['font-size']){
    const box=document.createElement('div');box.innerHTML=String(html??'');
    box.querySelectorAll('[style]').forEach(el=>{props.forEach(prop=>el.style.removeProperty(prop));if(!(el.getAttribute('style')||'').trim())el.removeAttribute('style');});
    box.querySelectorAll('font').forEach(el=>{if(props.includes('font-size'))el.removeAttribute('size');if(props.includes('font-family'))el.removeAttribute('face');});
    return box.innerHTML;
  }
  function cleanBlockTypography(block,props=['font-size']){
    if(!block)return;
    const clean=key=>{if(typeof block[key]==='string')block[key]=stripInlineTypography(block[key],props);};
    if(['title','text','quote','footer'].includes(block.type))clean('content');
    if(block.type==='hero'||block.type==='card'||block.type==='iconbox'||block.type==='video'||block.type==='banner'){clean('title');clean('text');}
    if(block.type==='product'){clean('name');clean('text');clean('price');clean('oldPrice');}
    if(block.type==='testimonial'){clean('quote');clean('author');clean('role');}
    if(block.type==='stats'){for(let i=1;i<=3;i++){clean('value'+i);clean('label'+i);}}
    if(block.type==='iconlist')clean('items');
    if(block.type==='pricing'){clean('badge');clean('plan');clean('price');clean('period');clean('features');}
    if(block.type==='qr')clean('label');
    if(block.type==='header'){clean('menu1');clean('menu2');clean('menu3');clean('button');}
    if(isColumns(block)){
      const walk=container=>{ensureColumns(container).forEach(cell=>{if(typeof cell.content==='string')cell.content=stripInlineTypography(cell.content,props);if(cell.nested)walk(cell.nested);});};
      walk(block);
    }
  }
  // IMPORTANT: font stacks are injected inside double-quoted HTML style attributes.
  // Use single quotes around family names with spaces so the attribute is never broken.
  const fontStacks={
    'Exo 2':"'Exo 2',Arial,sans-serif",
    'Ubuntu Sans':"'Ubuntu Sans',Arial,sans-serif",
    'Arial':'Arial,Helvetica,sans-serif',
    'Helvetica':'Helvetica,Arial,sans-serif',
    'Verdana':'Verdana,Arial,sans-serif',
    'Tahoma':'Tahoma,Arial,sans-serif',
    'Trebuchet MS':"'Trebuchet MS',Arial,sans-serif",
    'Georgia':"Georgia,'Times New Roman',serif",
    'Times New Roman':"'Times New Roman',Times,serif"
  };
  const fontCss=(name,fallback='Ubuntu Sans')=>fontStacks[name]||fontStacks[fallback];
  const qrUrl=(data,size=220)=>`https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=8&data=${encodeURIComponent(String(data||'https://emailsend.uno'))}`;
  const defaults={
    title:{type:'title',content:'Nuevo título',style:{color:'#002f57',background:'#ffffff',padding:'14px 28px',align:'left',fontSize:'28px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center',radius:'0'}},
    text:{type:'text',content:'Nuevo texto de campaña.',style:{color:'#1f2937',background:'#ffffff',padding:'14px 28px',align:'left',fontSize:'16px',fontFamily:'Ubuntu Sans',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center',radius:'0'}},
    image:{type:'image',src:'',alt:'',style:{background:'#ffffff',padding:'10px 28px',align:'center',width:'100%',blockWidth:'100%',blockAlign:'center',radius:'0'}},
    button:{type:'button',content:'VER MÁS',href:'#',style:{background:'#ffffff',padding:'16px 28px',align:'left',buttonBg:'#ff5300',buttonColor:'#ffffff',buttonPadding:'12px 18px',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center',radius:'4px'}},
    divider:{type:'divider',style:{background:'#ffffff',padding:'16px 28px',color:'#dce5ec',blockWidth:'100%',blockAlign:'center'}},
    spacer:{type:'spacer',style:{background:'#ffffff',height:'30px',blockWidth:'100%',blockAlign:'center'}},
    columns1:{type:'columns1',cells:[{content:'<h3>Columna</h3><p>Contenido editable.</p>',width:100,style:{background:'transparent',backgroundImage:'',backgroundSize:'cover',backgroundPosition:'center center',backgroundRepeat:'no-repeat',overlayColor:'#000000',overlayOpacity:'0',color:'#1f2937',padding:'18px 20px',align:'left',fontFamily:'Ubuntu Sans',fontSize:'16px',fontWeight:'400',lineHeight:'1.5',letterSpacing:'0px',radius:'0'},nested:null}],style:{background:'#ffffff',padding:'0',color:'#1f2937',fontFamily:'Ubuntu Sans',fontSize:'16px',fontWeight:'400',lineHeight:'1.5',blockWidth:'100%',blockAlign:'center'}},
    columns:{type:'columns',left:'<h3>Columna izquierda</h3><p>Contenido editable.</p>',right:'<h3>Columna derecha</h3><p>Contenido editable.</p>',style:{background:'#ffffff',padding:'18px 22px',color:'#1f2937',fontFamily:'Ubuntu Sans',fontSize:'16px',fontWeight:'400',lineHeight:'1.5',blockWidth:'100%',blockAlign:'center'}},
    columns3:{type:'columns3',one:'<h3>Uno</h3><p>Primer contenido.</p>',two:'<h3>Dos</h3><p>Segundo contenido.</p>',three:'<h3>Tres</h3><p>Tercer contenido.</p>',style:{background:'#ffffff',padding:'16px 14px',color:'#1f2937',fontFamily:'Ubuntu Sans',fontSize:'16px',fontWeight:'400',lineHeight:'1.5',blockWidth:'100%',blockAlign:'center'}},
    logo:{type:'logo',src:'assets/logo-emailsend.png',alt:'Logo',style:{background:'#ffffff',padding:'20px 28px',align:'left',width:'190px',blockWidth:'100%',blockAlign:'center'}},
    quote:{type:'quote',content:'Una frase, testimonio o idea destacada para reforzar tu mensaje.',style:{background:'#ffffff',padding:'18px 28px',align:'left',color:'#334155',accent:'#31b4e7',fontFamily:'Ubuntu Sans',fontSize:'18px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
    card:{type:'card',title:'Bloque destacado',text:'Presenta un servicio, beneficio o contenido especial dentro de una tarjeta.',button:'CONOCER MÁS',href:'#',style:{background:'#ffffff',padding:'16px 28px',color:'#002f57',textColor:'#516579',cardBg:'#f8fbfd',border:'#dce5ec',buttonBg:'#0074c7',buttonColor:'#ffffff',fontFamily:'Exo 2',fontSize:'22px',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'4px',blockWidth:'100%',blockAlign:'center',radius:'8px'}},
    footer:{type:'footer',content:'Recibes este correo porque formas parte de nuestra lista. <a href="{{unsubscribe_url}}">Darse de baja</a>.',style:{background:'#f1f5f9',padding:'22px 28px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
    social:{type:'social',instagram:'#',facebook:'',linkedin:'#',website:'#',style:{background:'#ffffff',padding:'18px 28px',align:'center',color:'#0074c7',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'600',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center'}},
    html:{type:'html',content:'<div style="padding:24px;text-align:center"><strong>HTML personalizado</strong></div>',style:{blockWidth:'100%',blockAlign:'center'}},
    hero:{type:'hero',src:'',title:'Título de impacto',text:'Texto de apoyo para explicar la propuesta de esta campaña.',button:'VER MÁS',href:'#',style:{background:'#002f57',color:'#ffffff',textColor:'#ffffff',buttonBg:'#ff5300',buttonColor:'#ffffff',padding:'48px 30px',align:'left',fontSize:'34px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'4px',blockWidth:'100%',blockAlign:'center',radius:'0'}},
    iconbox:{type:'iconbox',icon:'★',title:'Beneficio destacado',text:'Explica en pocas palabras por qué este punto es importante para tu audiencia.',style:{background:'#ffffff',padding:'18px 28px',align:'left',color:'#073b68',fontFamily:'Exo 2',fontSize:'22px',fontWeight:'700',lineHeight:'1.25',textColor:'#516579',textSize:'15px',iconBg:'#eaf6fd',iconColor:'#0074c7',iconSize:'26px',cardBg:'#f8fbfd',border:'#dce8f0',radius:'14px',blockWidth:'100%',blockAlign:'center'}},
    video:{type:'video',thumbnail:'https://placehold.co/680x360/073b68/ffffff?text=VIDEO',title:'Mira el video',text:'Una miniatura compatible con email que abre el video al hacer clic.',button:'REPRODUCIR VIDEO',href:'https://www.youtube.com/',style:{background:'#ffffff',padding:'18px 28px',align:'center',color:'#073b68',fontFamily:'Exo 2',fontSize:'25px',fontWeight:'700',lineHeight:'1.25',textColor:'#516579',textSize:'15px',playBg:'#ff5300',buttonBg:'#0074c7',buttonColor:'#ffffff',buttonRadius:'999px',radius:'14px',blockWidth:'100%',blockAlign:'center'}},
    product:{type:'product',src:'https://placehold.co/600x360/f3f7fa/073b68?text=PRODUCTO',name:'Nombre del producto',price:'US$ 99',oldPrice:'US$ 129',text:'Describe el beneficio principal del producto en una o dos líneas.',button:'COMPRAR AHORA',href:'#',style:{background:'#ffffff',padding:'18px 28px',align:'left',color:'#073b68',fontFamily:'Exo 2',fontSize:'25px',fontWeight:'700',lineHeight:'1.25',textColor:'#516579',textSize:'15px',priceColor:'#ff5300',cardBg:'#ffffff',border:'#dce8f0',buttonBg:'#0074c7',buttonColor:'#ffffff',buttonRadius:'999px',radius:'14px',blockWidth:'100%',blockAlign:'center'}},
    testimonial:{type:'testimonial',avatar:'https://placehold.co/160x160/eaf6fd/073b68?text=FOTO',quote:'“Este espacio es ideal para mostrar una opinión real y generar confianza.”',author:'Nombre del cliente',role:'Cargo · Empresa',stars:5,style:{background:'#ffffff',padding:'18px 28px',align:'left',color:'#334155',fontFamily:'Ubuntu Sans',fontSize:'18px',fontWeight:'400',lineHeight:'1.6',accent:'#f6bd00',cardBg:'#f8fbfd',border:'#dce8f0',radius:'14px',blockWidth:'100%',blockAlign:'center'}},
    stats:{type:'stats',value1:'+35%',label1:'Conversión',value2:'12K',label2:'Contactos',value3:'4.9/5',label3:'Satisfacción',style:{background:'#ffffff',padding:'18px 28px',align:'center',color:'#073b68',fontFamily:'Exo 2',fontSize:'30px',fontWeight:'800',lineHeight:'1.15',textColor:'#6b7f8f',textSize:'12px',cardBg:'#f8fbfd',border:'#dce8f0',radius:'14px',blockWidth:'100%',blockAlign:'center'}},
    iconlist:{type:'iconlist',icon:'✓',items:'Beneficio claro y fácil de entender\nSegundo punto importante\nTercer argumento o característica',style:{background:'#ffffff',padding:'18px 28px',align:'left',color:'#334155',fontFamily:'Ubuntu Sans',fontSize:'16px',fontWeight:'400',lineHeight:'1.6',iconColor:'#73bc25',iconBg:'#eef9e7',radius:'10px',blockWidth:'100%',blockAlign:'center'}},
    banner:{type:'banner',src:'https://placehold.co/680x320/00365f/ffffff?text=BANNER',title:'Una campaña que se ve profesional',text:'Combina imagen, mensaje y CTA en un bloque visual de alto impacto.',button:'CONOCER MÁS',href:'#',style:{background:'#00365f',padding:'54px 38px',align:'left',color:'#ffffff',fontFamily:'Exo 2',fontSize:'34px',fontWeight:'800',lineHeight:'1.18',textColor:'#e6f2f8',textSize:'16px',overlay:'0.58',buttonBg:'#ff5300',buttonColor:'#ffffff',buttonRadius:'999px',radius:'16px',blockWidth:'100%',blockAlign:'center'}},
    pricing:{type:'pricing',badge:'MÁS ELEGIDO',plan:'Plan Profesional',price:'US$ 49',period:'/ mes',features:'Hasta 25.000 contactos\nCampañas ilimitadas\nReportes avanzados\nSoporte prioritario',button:'COMENZAR AHORA',href:'#',style:{background:'#ffffff',padding:'18px 28px',align:'center',color:'#073b68',fontFamily:'Exo 2',fontSize:'27px',fontWeight:'800',lineHeight:'1.2',textColor:'#516579',textSize:'15px',priceColor:'#0074c7',badgeBg:'#ff5300',badgeColor:'#ffffff',cardBg:'#ffffff',border:'#bcd9e9',buttonBg:'#0074c7',buttonColor:'#ffffff',buttonRadius:'999px',radius:'16px',blockWidth:'100%',blockAlign:'center'}},
    qr:{type:'qr',data:'https://emailsend.uno',label:'Escanea el código para continuar',style:{background:'#ffffff',padding:'24px 28px',align:'center',color:'#073b68',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'600',lineHeight:'1.4',qrSize:'190px',qrBg:'#ffffff',radius:'12px',blockWidth:'100%',blockAlign:'center'}},
    header:{type:'header',src:'assets/logo-emailsend.png',alt:'Logo',menu1:'INICIO',url1:'#',menu2:'SERVICIOS',url2:'#',menu3:'CONTACTO',url3:'#',button:'HABLEMOS',href:'#',style:{background:'#ffffff',padding:'18px 28px',align:'left',color:'#073b68',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'700',lineHeight:'1.2',logoWidth:'170px',buttonBg:'#ff5300',buttonColor:'#ffffff',buttonRadius:'999px',border:'#e0e8ee',blockWidth:'100%',blockAlign:'center',radius:'0'}}
  };
  const templates={
    newsletter:[
      {...clone(defaults.logo),style:{background:'#ffffff',padding:'26px 38px 12px',align:'left',width:'182px'}},
      {type:'text',content:'NEWSLETTER MENSUAL · RESUMEN EJECUTIVO',style:{color:'#6b879a',background:'#ffffff',padding:'0 38px 12px',align:'left',fontSize:'11px',fontFamily:'Ubuntu Sans',fontWeight:'700',lineHeight:'1.4',blockWidth:'100%',blockAlign:'center'}},
      {...clone(defaults.hero),title:'LO MÁS RELEVANTE DE ESTE MES',text:'Abre tu newsletter con una idea clara, un mensaje humano y una jerarquía visual que invite a seguir leyendo.',button:'LEER DESTACADO',style:{background:'#073b68',color:'#ffffff',textColor:'#d8ecf7',buttonBg:'#ff5300',buttonColor:'#ffffff',padding:'56px 40px',align:'left',fontSize:'38px',radius:'14px',blockWidth:'92%',blockAlign:'center'}},
      {type:'title',content:'Tres puntos clave para ponerte al día',style:{color:'#073b68',background:'#ffffff',padding:'32px 38px 10px',align:'left',fontSize:'29px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center'}},
      {type:'text',content:'Utiliza este bloque para resumir el valor del envío. Una breve introducción hace que el resto del contenido se entienda mejor y se vea más profesional.',style:{color:'#445d70',background:'#ffffff',padding:'0 38px 16px',align:'left',fontSize:'16px',fontFamily:'Ubuntu Sans',fontWeight:'400',lineHeight:'1.65',blockWidth:'100%',blockAlign:'center'}},
      {type:'columns3',one:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">01 · Novedad</h3><p style="margin:0">Cuenta el primer tema en pocas líneas.</p>',two:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">02 · Tendencia</h3><p style="margin:0">Comparte un insight o dato útil.</p>',three:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">03 · Recomendado</h3><p style="margin:0">Cierra con un recurso o lectura.</p>',style:{background:'#ffffff',padding:'12px 26px 12px',color:'#334155',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
      {type:'card',title:'Bloque destacado de la edición',text:'Ideal para resaltar una historia principal, una oportunidad importante o una novedad que quieres que nadie pase por alto.',button:'VER MÁS',href:'#',style:{background:'#ffffff',padding:'18px 38px 10px',color:'#073b68',textColor:'#55697a',cardBg:'#f7fbfe',border:'#dce8f0',buttonBg:'#0074c7',buttonColor:'#ffffff',fontFamily:'Exo 2',fontSize:'24px',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'999px',blockWidth:'100%',blockAlign:'center',radius:'14px'}},
      {type:'button',content:'VER TODAS LAS NOVEDADES',href:'#',style:{background:'#ffffff',padding:'20px 38px 30px',align:'center',buttonBg:'#ff5300',buttonColor:'#ffffff',buttonPadding:'14px 22px',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center',radius:'999px'}},
      {...clone(defaults.social),style:{background:'#f8fbfd',padding:'20px 28px',align:'center',color:'#0074c7',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'600',lineHeight:'1.2',blockWidth:'92%',blockAlign:'center'}},
      {...clone(defaults.footer),style:{background:'#f1f5f9',padding:'22px 30px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'92%',blockAlign:'center'}}
    ],
    promo:[
      {...clone(defaults.logo),style:{background:'#ffffff',padding:'24px 36px 10px',align:'center',width:'175px'}},
      {...clone(defaults.hero),title:'OFERTA CLARA, DIRECTA Y LISTA PARA CONVERTIR',text:'Beneficio principal + urgencia real + un llamado a la acción imposible de confundir.',button:'APROVECHAR AHORA',style:{background:'#052f57',color:'#ffffff',textColor:'#e0eff8',buttonBg:'#ff5300',buttonColor:'#ffffff',padding:'58px 38px',align:'center',fontSize:'40px',radius:'18px',blockWidth:'94%',blockAlign:'center'}},
      {type:'card',title:'Hasta 30% de beneficio + bonus',text:'Resume aquí la promoción, su vigencia y el motivo por el que conviene actuar ahora. Mantén el mensaje simple y muy fácil de escanear.',button:'QUIERO APROVECHAR',href:'#',style:{background:'#ffffff',padding:'22px 36px 10px',color:'#073b68',textColor:'#506273',cardBg:'#fff8f3',border:'#ffd9c2',buttonBg:'#73bc25',buttonColor:'#ffffff',fontFamily:'Exo 2',fontSize:'26px',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'999px',blockWidth:'100%',blockAlign:'center',radius:'16px'}},
      {type:'title',content:'¿Por qué funciona esta oferta?',style:{color:'#073b68',background:'#ffffff',padding:'18px 36px 8px',align:'left',fontSize:'28px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center'}},
      {type:'columns3',one:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">Ahorro</h3><p style="margin:0">Describe el descuento o valor extra.</p>',two:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">Rapidez</h3><p style="margin:0">Explica lo fácil que es comprar o activar.</p>',three:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">Confianza</h3><p style="margin:0">Refuerza con garantía o respaldo.</p>',style:{background:'#ffffff',padding:'10px 24px 16px',color:'#334155',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
      {type:'button',content:'QUIERO ESTA PROMOCIÓN',href:'#',style:{background:'#ffffff',padding:'8px 36px 30px',align:'center',buttonBg:'#0074c7',buttonColor:'#ffffff',buttonPadding:'14px 24px',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center',radius:'999px'}},
      {...clone(defaults.footer),style:{background:'#f8fafc',padding:'22px 30px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'94%',blockAlign:'center'}}
    ],
    corporate:[
      {...clone(defaults.logo),style:{background:'#ffffff',padding:'24px 38px 10px',align:'left',width:'186px'}},
      {type:'text',content:'ACTUALIZACIÓN CORPORATIVA · COMUNICACIÓN OFICIAL',style:{color:'#6b879a',background:'#ffffff',padding:'0 38px 12px',align:'left',fontSize:'11px',fontFamily:'Ubuntu Sans',fontWeight:'700',lineHeight:'1.4',blockWidth:'100%',blockAlign:'center'}},
      {...clone(defaults.hero),title:'UNA COMUNICACIÓN CORPORATIVA QUE NO SE VE GENÉRICA',text:'Presenta servicios, avances o resultados con una estructura premium, limpia y muy ordenada.',button:'CONVERSEMOS',style:{background:'#00365f',color:'#ffffff',textColor:'#d8ecf7',buttonBg:'#31b4e7',buttonColor:'#ffffff',padding:'56px 40px',align:'left',fontSize:'36px',radius:'14px',blockWidth:'92%',blockAlign:'center'}},
      {type:'text',content:'Utiliza este espacio para una introducción ejecutiva de dos o tres párrafos. La idea es comunicar con claridad, sin que el email se vea pesado.',style:{color:'#40566a',background:'#ffffff',padding:'22px 38px 18px',align:'left',fontSize:'16px',fontFamily:'Ubuntu Sans',fontWeight:'400',lineHeight:'1.7',blockWidth:'100%',blockAlign:'center'}},
      {type:'columns3',one:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">ESTRATEGIA</h3><p style="margin:0">Qué estamos haciendo y por qué.</p>',two:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">EJECUCIÓN</h3><p style="margin:0">Cómo lo estamos implementando.</p>',three:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">RESULTADO</h3><p style="margin:0">Qué impacto esperamos generar.</p>',style:{background:'#f4f8fb',padding:'24px 20px',color:'#173b59',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.6',blockWidth:'92%',blockAlign:'center'}},
      {type:'card',title:'Mensaje clave para dirección o clientes',text:'Este bloque funciona bien para resumir un hito, un servicio estratégico, un anuncio de valor o un resumen ejecutivo.',button:'SOLICITAR REUNIÓN',href:'#',style:{background:'#ffffff',padding:'20px 38px 10px',color:'#073b68',textColor:'#506273',cardBg:'#f7fbfe',border:'#dce8f0',buttonBg:'#0074c7',buttonColor:'#ffffff',fontFamily:'Exo 2',fontSize:'24px',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'999px',blockWidth:'100%',blockAlign:'center',radius:'14px'}},
      {type:'quote',content:'“Una comunicación corporativa clara transmite orden, confianza y madurez de marca.”',style:{background:'#ffffff',padding:'10px 38px 24px',align:'left',color:'#334155',accent:'#31b4e7',fontFamily:'Ubuntu Sans',fontSize:'18px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
      {...clone(defaults.footer),style:{background:'#f1f5f9',padding:'22px 30px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'92%',blockAlign:'center'}}
    ],
    event:[
      {...clone(defaults.logo),style:{background:'#ffffff',padding:'24px 36px 10px',align:'center',width:'178px'}},
      {...clone(defaults.hero),title:'RESERVA ESTA FECHA',text:'Nombre del evento · Ciudad · Fecha · Hora',button:'CONFIRMAR ASISTENCIA',style:{background:'#052f57',color:'#ffffff',textColor:'#ffffff',buttonBg:'#ff5300',buttonColor:'#ffffff',padding:'62px 38px',align:'center',fontSize:'42px',radius:'18px',blockWidth:'94%',blockAlign:'center'}},
      {type:'card',title:'Invitación principal',text:'Ideal para explicar el valor del evento, quién debería asistir y qué recibirá a cambio de su tiempo.',button:'VER DETALLES',href:'#',style:{background:'#ffffff',padding:'24px 36px 14px',color:'#073b68',textColor:'#506273',cardBg:'#f8fbfd',border:'#dce8f0',buttonBg:'#0074c7',buttonColor:'#ffffff',fontFamily:'Exo 2',fontSize:'24px',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'999px',blockWidth:'100%',blockAlign:'center',radius:'16px'}},
      {type:'title',content:'Agenda resumida',style:{color:'#073b68',background:'#ffffff',padding:'14px 36px 8px',align:'center',fontSize:'28px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center'}},
      {type:'columns3',one:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">09:00</h3><p style="margin:0">Recepción y bienvenida.</p>',two:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">10:00</h3><p style="margin:0">Bloque principal o keynote.</p>',three:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">11:30</h3><p style="margin:0">Networking / cierre.</p>',style:{background:'#ffffff',padding:'10px 24px 20px',color:'#334155',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
      {type:'button',content:'AGENDAR MI LUGAR',href:'#',style:{background:'#ffffff',padding:'2px 36px 30px',align:'center',buttonBg:'#73bc25',buttonColor:'#ffffff',buttonPadding:'14px 24px',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center',radius:'999px'}},
      {...clone(defaults.footer),style:{background:'#f8fafc',padding:'22px 30px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'94%',blockAlign:'center'}}
    ],
    product:[
      {...clone(defaults.logo),style:{background:'#ffffff',padding:'24px 36px 10px',align:'left',width:'182px'}},
      {...clone(defaults.hero),title:'EL PRODUCTO QUE MERECE UNA PRESENTACIÓN MEJOR',text:'Usa esta estructura para lanzar, presentar o reactivar un producto de forma más moderna.',button:'DESCUBRIR PRODUCTO',style:{background:'#071f35',color:'#ffffff',textColor:'#dbeaf4',buttonBg:'#73bc25',buttonColor:'#ffffff',padding:'58px 40px',align:'left',fontSize:'38px',radius:'16px',blockWidth:'92%',blockAlign:'center'}},
      {...clone(defaults.image),src:'https://placehold.co/680x360?text=Foto+del+producto',style:{background:'#ffffff',padding:'18px 36px 8px',align:'center',width:'100%',blockWidth:'100%',blockAlign:'center',radius:'16px'}},
      {type:'title',content:'Beneficios que se entienden rápido',style:{color:'#073b68',background:'#ffffff',padding:'18px 36px 8px',align:'left',fontSize:'28px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center'}},
      {type:'columns3',one:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">RÁPIDO</h3><p style="margin:0">Beneficio concreto y fácil de leer.</p>',two:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">SIMPLE</h3><p style="margin:0">Segundo valor diferencial.</p>',three:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">CONFIABLE</h3><p style="margin:0">Respaldo, garantía o prueba social.</p>',style:{background:'#ffffff',padding:'8px 24px 18px',color:'#173b59',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
      {type:'card',title:'Oferta o argumento final',text:'Remata la presentación con un argumento de compra, una promoción o una llamada a la acción principal.',button:'VER EL PRODUCTO',href:'#',style:{background:'#ffffff',padding:'10px 36px 16px',color:'#073b68',textColor:'#506273',cardBg:'#f8fbfd',border:'#dce8f0',buttonBg:'#ff5300',buttonColor:'#ffffff',fontFamily:'Exo 2',fontSize:'24px',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'999px',blockWidth:'100%',blockAlign:'center',radius:'16px'}},
      {...clone(defaults.footer),style:{background:'#f1f5f9',padding:'22px 30px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'92%',blockAlign:'center'}}
    ],
    service:[
      {...clone(defaults.logo),style:{background:'#ffffff',padding:'24px 38px 10px',align:'left',width:'184px'}},
      {type:'text',content:'SERVICIO · PROPUESTA DE VALOR',style:{color:'#6b879a',background:'#ffffff',padding:'0 38px 12px',align:'left',fontSize:'11px',fontFamily:'Ubuntu Sans',fontWeight:'700',lineHeight:'1.4',blockWidth:'100%',blockAlign:'center'}},
      {type:'title',content:'¿Y si este problema pudiera resolverse mucho mejor?',style:{color:'#073b68',background:'#ffffff',padding:'24px 38px 8px',align:'left',fontSize:'34px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.18',blockWidth:'100%',blockAlign:'center'}},
      {type:'text',content:'Presenta el dolor del cliente y conecta rápidamente con la solución que ofreces. Esta plantilla está pensada para vender servicios con claridad y confianza.',style:{color:'#40566a',background:'#ffffff',padding:'0 38px 16px',align:'left',fontSize:'17px',fontFamily:'Ubuntu Sans',fontWeight:'400',lineHeight:'1.7',blockWidth:'100%',blockAlign:'center'}},
      {type:'columns3',one:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">Diagnóstico</h3><p style="margin:0">Entendemos el problema.</p>',two:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">Estrategia</h3><p style="margin:0">Definimos la solución.</p>',three:'<h3 style="margin:0 0 8px;color:#073b68;font-family:Exo 2">Resultado</h3><p style="margin:0">Implementamos y medimos.</p>',style:{background:'#f4f8fb',padding:'24px 20px',color:'#173b59',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.6',blockWidth:'92%',blockAlign:'center'}},
      {type:'card',title:'Nuestra solución',text:'Describe el servicio en lenguaje simple: qué haces, para quién y qué resultado genera. Este bloque debería concentrar la propuesta principal.',button:'AGENDAR UNA CONVERSACIÓN',href:'#',style:{background:'#ffffff',padding:'20px 38px 10px',color:'#073b68',textColor:'#506273',cardBg:'#ffffff',border:'#dce8f0',buttonBg:'#0074c7',buttonColor:'#ffffff',fontFamily:'Exo 2',fontSize:'24px',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'999px',blockWidth:'100%',blockAlign:'center',radius:'14px'}},
      {type:'quote',content:'“Cuando el problema está claro, la decisión de avanzar también lo está.”',style:{background:'#ffffff',padding:'8px 38px 24px',align:'left',color:'#334155',accent:'#31b4e7',fontFamily:'Ubuntu Sans',fontSize:'18px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
      {...clone(defaults.footer),style:{background:'#f1f5f9',padding:'22px 30px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'92%',blockAlign:'center'}}
    ],
    announcement:[
      {...clone(defaults.logo),style:{background:'#ffffff',padding:'24px 38px 10px',align:'left',width:'182px'}},
      {...clone(defaults.hero),title:'TENEMOS ALGO IMPORTANTE QUE CONTARTE',text:'Una plantilla ideal para comunicados, cambios relevantes, avisos internos o actualizaciones de marca.',button:'CONOCER LOS DETALLES',style:{background:'#00365f',color:'#ffffff',textColor:'#d8ecf7',buttonBg:'#31b4e7',buttonColor:'#ffffff',padding:'54px 40px',align:'left',fontSize:'36px',radius:'16px',blockWidth:'92%',blockAlign:'center'}},
      {type:'text',content:'Explica aquí el anuncio principal. Empieza por lo que cambia para la persona que recibe este email y luego agrega el contexto necesario.',style:{color:'#334155',background:'#ffffff',padding:'22px 38px 16px',align:'left',fontSize:'16px',fontFamily:'Ubuntu Sans',fontWeight:'400',lineHeight:'1.7',blockWidth:'100%',blockAlign:'center'}},
      {type:'card',title:'Resumen ejecutivo del anuncio',text:'Usa esta tarjeta para condensar las implicancias, fechas, responsables o próximos pasos del comunicado.',button:'VER MÁS INFORMACIÓN',href:'#',style:{background:'#ffffff',padding:'10px 38px 10px',color:'#073b68',textColor:'#506273',cardBg:'#f8fbfd',border:'#dce8f0',buttonBg:'#ff5300',buttonColor:'#ffffff',fontFamily:'Exo 2',fontSize:'24px',fontWeight:'700',lineHeight:'1.2',textFontFamily:'Ubuntu Sans',textSize:'16px',buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonRadius:'999px',blockWidth:'100%',blockAlign:'center',radius:'14px'}},
      {...clone(defaults.divider),style:{background:'#ffffff',padding:'16px 38px',color:'#dce5ec',blockWidth:'100%',blockAlign:'center'}},
      {type:'quote',content:'Destaca aquí una fecha, una cifra o una frase clave para que el lector la recuerde de inmediato.',style:{background:'#ffffff',padding:'6px 38px 12px',align:'left',color:'#334155',accent:'#ff5300',fontFamily:'Ubuntu Sans',fontSize:'18px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
      {type:'button',content:'LEER EL COMUNICADO COMPLETO',href:'#',style:{background:'#ffffff',padding:'6px 38px 28px',align:'left',buttonBg:'#0074c7',buttonColor:'#ffffff',buttonPadding:'14px 22px',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center',radius:'999px'}},
      {...clone(defaults.footer),style:{background:'#f1f5f9',padding:'22px 30px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'92%',blockAlign:'center'}}
    ],
    editorial:[
      {...clone(defaults.logo),style:{background:'#ffffff',padding:'26px 42px 8px',align:'left',width:'184px'}},
      {type:'text',content:'EDITORIAL · CONTENIDO DE MARCA',style:{color:'#6b879a',background:'#ffffff',padding:'0 42px 12px',align:'left',fontSize:'11px',fontFamily:'Ubuntu Sans',fontWeight:'700',lineHeight:'1.4',blockWidth:'100%',blockAlign:'center'}},
      {type:'title',content:'Una idea que merece cinco minutos de lectura',style:{color:'#073b68',background:'#ffffff',padding:'18px 42px 10px',align:'left',fontSize:'36px',fontFamily:'Exo 2',fontWeight:'700',lineHeight:'1.18',blockWidth:'100%',blockAlign:'center'}},
      {type:'text',content:'Ideal para artículos, reflexiones, tendencias o contenido de marca. Mantén una voz cercana, útil y bien estructurada.',style:{color:'#40566a',background:'#ffffff',padding:'0 42px 22px',align:'left',fontSize:'17px',fontFamily:'Ubuntu Sans',fontWeight:'400',lineHeight:'1.72',blockWidth:'100%',blockAlign:'center'}},
      {...clone(defaults.image),src:'https://placehold.co/680x360?text=Imagen+editorial',style:{background:'#ffffff',padding:'0 42px 18px',align:'center',width:'100%',blockWidth:'100%',blockAlign:'center',radius:'16px'}},
      {type:'text',content:'Desarrolla aquí el contenido principal en párrafos breves. La clave está en combinar jerarquía, aire y ritmo visual para que el correo no se vea plano.',style:{color:'#334155',background:'#ffffff',padding:'0 42px 18px',align:'left',fontSize:'16px',fontFamily:'Ubuntu Sans',fontWeight:'400',lineHeight:'1.72',blockWidth:'100%',blockAlign:'center'}},
      {type:'quote',content:'“Las mejores campañas editoriales se leen fácil, pero dejan una impresión duradera.”',style:{background:'#ffffff',padding:'0 42px 18px',align:'left',color:'#334155',accent:'#31b4e7',fontFamily:'Ubuntu Sans',fontSize:'18px',fontWeight:'400',lineHeight:'1.6',blockWidth:'100%',blockAlign:'center'}},
      {type:'button',content:'SEGUIR LEYENDO',href:'#',style:{background:'#ffffff',padding:'0 42px 28px',align:'left',buttonBg:'#0074c7',buttonColor:'#ffffff',buttonPadding:'14px 22px',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'700',lineHeight:'1.2',blockWidth:'100%',blockAlign:'center',radius:'999px'}},
      {...clone(defaults.social),style:{background:'#f8fbfd',padding:'20px 28px',align:'center',color:'#0074c7',fontFamily:'Ubuntu Sans',fontSize:'14px',fontWeight:'600',lineHeight:'1.2',blockWidth:'92%',blockAlign:'center'}},
      {...clone(defaults.footer),style:{background:'#f1f5f9',padding:'22px 30px',align:'center',color:'#64748b',fontFamily:'Ubuntu Sans',fontSize:'12px',fontWeight:'400',lineHeight:'1.6',blockWidth:'92%',blockAlign:'center'}}
    ]
  };

  function snapshot(){history.push(JSON.stringify(blocks));if(history.length>60)history.shift();future=[];}
  function undo(){if(!history.length)return;future.push(JSON.stringify(blocks));blocks=JSON.parse(history.pop());normalizeBlocks(blocks);selected=Math.min(selected,blocks.length-1);selectedCellPath=null;activeSelectionContext=blocks[selected]?{kind:'block',blockIndex:selected}:null;render();renderInspector();sync();}
  function redo(){if(!future.length)return;history.push(JSON.stringify(blocks));blocks=JSON.parse(future.pop());normalizeBlocks(blocks);selected=Math.min(selected,blocks.length-1);selectedCellPath=null;activeSelectionContext=blocks[selected]?{kind:'block',blockIndex:selected}:null;render();renderInspector();sync();}
  function ensureStyle(b){if(!b.style)b.style={};return b.style;}
  function typographyTarget(b){
    const s=ensureStyle(b),ctx=activeSelectionContext&&activeSelectionContext.blockIndex===selected?activeSelectionContext:null;
    const part=ctx?.kind==='part'?ctx.part:null;
    let keys={family:'fontFamily',size:'fontSize',weight:'fontWeight',line:'lineHeight',letter:'letterSpacing'}, label='BLOQUE';
    if(part){
      const cat=partCategory(b,part);label=partLabel(part).toUpperCase();
      if(cat==='button')keys={family:'buttonFontFamily',size:'buttonSize',weight:'buttonWeight',line:'buttonLineHeight',letter:'buttonLetterSpacing'};
      else if(part==='text'&&['hero','card','iconbox','video','product','banner'].includes(b.type))keys={family:'textFontFamily',size:'textSize',weight:'textWeight',line:'textLineHeight',letter:'textLetterSpacing'};
      else if(part==='price'&&['product','pricing'].includes(b.type))keys={family:'priceFontFamily',size:'priceSize',weight:'priceWeight',line:'priceLineHeight',letter:'priceLetterSpacing'};
      else if(part==='oldPrice'&&b.type==='product')keys={family:'oldPriceFontFamily',size:'oldPriceSize',weight:'oldPriceWeight',line:'oldPriceLineHeight',letter:'oldPriceLetterSpacing'};
      else if(['author','role'].includes(part)&&b.type==='testimonial')keys={family:'textFontFamily',size:'textSize',weight:'textWeight',line:'textLineHeight',letter:'textLetterSpacing'};
      else if(/^stat[123]$/.test(part)&&b.type==='stats')keys={family:'fontFamily',size:'fontSize',weight:'fontWeight',line:'lineHeight'};
      else if(['badge','period','features'].includes(part)&&b.type==='pricing')keys={family:'textFontFamily',size:'textSize',weight:'textWeight',line:'textLineHeight',letter:'textLetterSpacing'};
    }
    const defaultsMap={
      fontFamily:(b.type==='title'||['hero','card','banner','iconbox','video','product','pricing','stats'].includes(b.type))?'Exo 2':'Ubuntu Sans',
      fontSize:'16px',fontWeight:(b.type==='title'||['hero','card','banner','iconbox','video','product','pricing','stats'].includes(b.type))?'700':'400',lineHeight:'1.6',letterSpacing:'0px',
      textFontFamily:'Ubuntu Sans',textSize:'16px',textWeight:'400',textLineHeight:'1.6',textLetterSpacing:'0px',
      buttonFontFamily:'Ubuntu Sans',buttonSize:'14px',buttonWeight:'700',buttonLineHeight:'1.2',buttonLetterSpacing:'0px',
      priceFontFamily:'Exo 2',priceSize:b.type==='pricing'?'34px':'24px',priceWeight:'800',priceLineHeight:'1.1',priceLetterSpacing:'0px',
      oldPriceFontFamily:'Ubuntu Sans',oldPriceSize:'14px',oldPriceWeight:'400',oldPriceLineHeight:'1.2',oldPriceLetterSpacing:'0px'
    };
    return {part,label,keys,values:{family:s[keys.family]||defaultsMap[keys.family]||'Ubuntu Sans',size:s[keys.size]||defaultsMap[keys.size]||'16px',weight:s[keys.weight]||defaultsMap[keys.weight]||'400',line:s[keys.line]||defaultsMap[keys.line]||'1.6',letter:s[keys.letter]||defaultsMap[keys.letter]||'0px'}};
  }
  function patchSelectedTypography(prop,value){
    const ctx=activeSelectionContext;if(!ctx||ctx.kind!=='part'||ctx.blockIndex!==selected)return;
    const blockEl=page.querySelector(`.block[data-i="${selected}"]`),el=blockEl?.querySelector(`[data-builder-part="${ctx.part}"]`);if(!el)return;
    const css={family:'font-family',size:'font-size',weight:'font-weight',line:'line-height',letter:'letter-spacing'}[prop];if(css)el.style.setProperty(css,String(value),'important');
  }
  const isColumns=b=>!!b&&['columns1','columns','columns3'].includes(b.type);
  const pathKey=path=>(path||[]).join('.');
  const samePath=(a,b)=>Array.isArray(a)&&Array.isArray(b)&&a.length===b.length&&a.every((v,i)=>v===b[i]);
  const safeCssUrl=url=>String(url||'').replace(/\\/g,'/').replace(/'/g,'%27').replace(/\(/g,'%28').replace(/\)/g,'%29');
  const colorValue=(v,f='#ffffff')=>/^#[0-9a-f]{6}$/i.test(String(v||''))?String(v):f;
  function colorInputValue(v,f='#000000'){const x=String(v||'').trim();if(/^#[0-9a-f]{6}$/i.test(x))return x;const m=x.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);if(m)return '#'+[m[1],m[2],m[3]].map(n=>clamp(Number(n)||0,0,255).toString(16).padStart(2,'0')).join('');return /^#[0-9a-f]{3}$/i.test(x)?'#'+x.slice(1).split('').map(c=>c+c).join(''):f;}
  function canonicalFontWeight(v,f='400'){const x=String(v||'').toLowerCase();if(x==='bold')return '700';if(x==='normal')return '400';return ['300','400','500','600','700','800'].includes(x)?x:f;}
  function colorRgba(hex,alpha){hex=colorValue(hex,'#000000').slice(1);const r=parseInt(hex.slice(0,2),16),g=parseInt(hex.slice(2,4),16),b=parseInt(hex.slice(4,6),16);return `rgba(${r},${g},${b},${clamp(Number(alpha)||0,0,1)})`;}
  function backgroundImageCss(url,s,defaultOpacity=0,defaultColor='#000000'){
    if(!url)return '';const op=clamp(Number(s?.overlayOpacity??defaultOpacity)||0,0,1),overlay=op>0?`linear-gradient(${colorRgba(s?.overlayColor||defaultColor,op)},${colorRgba(s?.overlayColor||defaultColor,op)}),`:'';
    return `background-image:${overlay}url('${safeCssUrl(url)}');background-size:${s?.backgroundSize||'cover'};background-position:${s?.backgroundPosition||'center center'};background-repeat:${s?.backgroundRepeat||'no-repeat'};`;
  }
  function bgStyle(s,fallback='#ffffff'){
    s=s||{};let css=`background-color:${s.background||fallback};`;
    if(s.backgroundImage)css+=backgroundImageCss(s.backgroundImage,s,0,'#000000');
    return css;
  }
  function makeCell(content='Contenido editable.',width=null){
    return {content,width,items:[],style:{background:'transparent',backgroundImage:'',backgroundSize:'cover',backgroundPosition:'center center',backgroundRepeat:'no-repeat',backgroundSizeMobile:'cover',overlayColor:'#000000',overlayOpacity:'0',color:'#1f2937',padding:'18px 20px',margin:'0px',borderWidth:'0px',borderStyle:'solid',borderColor:'#dce5ec',align:'left',verticalAlign:'top',fontFamily:'Ubuntu Sans',fontSize:'16px',fontWeight:'400',lineHeight:'1.5',letterSpacing:'0px',radius:'0',minHeight:'0px'},nested:null};
  }
  function normalizeCellWidths(container){
    if(!isColumns(container))return [];
    const cells=Array.isArray(container.cells)?container.cells:[];if(!cells.length)return [];
    const raw=cells.map(c=>Number(c.width)),valid=raw.every(v=>Number.isFinite(v)&&v>0);
    if(!valid){const eq=100/cells.length;cells.forEach(c=>c.width=eq);return cells.map(c=>c.width);}
    const total=raw.reduce((a,b)=>a+b,0)||100;cells.forEach((c,i)=>c.width=raw[i]/total*100);return cells.map(c=>c.width);
  }
  function redistributeCellWidthsEvenly(container){
    if(!isColumns(container))return [];
    const cells=Array.isArray(container.cells)?container.cells:[];if(!cells.length)return [];
    const eq=100/cells.length;
    cells.forEach(c=>c.width=eq);
    return cells.map(c=>c.width);
  }
  function columnWidths(container){ensureColumns(container);return normalizeCellWidths(container);}
  function makeNestedColumns(count=2){
    const eq=100/Math.max(1,count);
    return {type:'columns',isNestedContainer:true,style:{background:'transparent',backgroundImage:'',padding:'0',color:'#1f2937',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.5'},cells:Array.from({length:count},(_,i)=>makeCell(`<h4 style="margin:0 0 6px">Subcolumna ${i+1}</h4><p style="margin:0">Contenido editable.</p>`,eq))};
  }
  function ensureColumns(b){
    if(!isColumns(b))return [];
    if(!Array.isArray(b.cells)){
      const vals=b.type==='columns1'?[b.content??'<h3>Columna</h3><p>Contenido editable.</p>']:(b.type==='columns'?[b.left??'<h3>Columna izquierda</h3><p>Contenido editable.</p>',b.right??'<h3>Columna derecha</h3><p>Contenido editable.</p>']:[b.one??'<h3>Uno</h3><p>Primer contenido.</p>',b.two??'<h3>Dos</h3><p>Segundo contenido.</p>',b.three??'<h3>Tres</h3><p>Tercer contenido.</p>']);
      const ps=ensureStyle(b),legacyPad=ps.padding||'18px 20px';
      const eq=100/Math.max(1,vals.length);
      b.cells=vals.map(v=>{const c=makeCell(v,eq);c.style={...c.style,background:'transparent',padding:legacyPad,color:ps.color||'#1f2937',fontFamily:ps.fontFamily||'Ubuntu Sans',fontSize:ps.fontSize||'16px',fontWeight:ps.fontWeight||'400',lineHeight:ps.lineHeight||'1.5'};return c;});
      ps.padding='0';
    }
    b.cells=b.cells.filter(Boolean).map(c=>{c.style={...makeCell('').style,...(c.style||{})};if(c.content===undefined)c.content='';c.content=normalizeCellContentHtml(c.content);if(!Array.isArray(c.items))c.items=[];c.items=c.items.filter(x=>x&&typeof x==='object'&&!isColumns(x)).map(x=>normalizeBlockTree(x));if(c.nested&&isColumns(c.nested))ensureColumns(c.nested);return c;});
    normalizeCellWidths(b);
    return b.cells;
  }
  function isDefaultImagePlaceholder(src){
    const v=String(src||'').toLowerCase();
    return v.includes('placehold.co/680x300')&&(v.includes('tu+imagen')||v.includes('tu%20imagen')||v.includes('tu-imagen'));
  }
  function normalizeBlockTree(b,nested=false){
    if(!b||typeof b!=='object')return b;
    normalizeImageFields(b);
    const bs=ensureStyle(b);
    if(b.type==='image'){
      const current=String(b.src||'').trim(),background=String(bs.backgroundImage||'').trim();
      // v0.9.34: reparar campañas donde la foto fue subida como fondo
      // dejando el placeholder "Tu imagen" delante.
      if(background&&(!current||isDefaultImagePlaceholder(current))){
        b.src=background;bs.backgroundImage='';bs.overlayOpacity='0';
      }else if(isDefaultImagePlaceholder(current)){
        b.src='';
      }
    }
    if(isColumns(b)){
      if(nested&&!bs.backgroundImage&&String(bs.background||'').toLowerCase()==='#ffffff'&&!bs.keepWhiteBackground)bs.background='transparent';
      if(nested)b.isNestedContainer=true;
      ensureColumns(b);
      b.cells.forEach(c=>{
        const cs=ensureStyle(c);
        if(typeof cs.backgroundImage==='string')cs.backgroundImage=normalizeAssetUrl(cs.backgroundImage);
        if(!['cover','contain','width'].includes(cs.backgroundSizeMobile))cs.backgroundSizeMobile='cover';
        if(Array.isArray(c.items))c.items.forEach(x=>normalizeBlockTree(x,false));
        if(c.nested)normalizeBlockTree(c.nested,true);
      });
    }
    return b;
  }
  function normalizeBlocks(list){(list||[]).forEach(normalizeBlockTree);}
  function getCellByPath(block,path){
    if(!isColumns(block)||!Array.isArray(path)||!path.length)return null;let container=block,cell=null;
    for(let d=0;d<path.length;d++){const cells=ensureColumns(container),idx=Number(path[d]);cell=cells[idx];if(!cell)return null;if(d<path.length-1){if(!cell.nested||!isColumns(cell.nested))return null;container=cell.nested;}}
    return cell;
  }
  function getContainerForCellPath(block,path){
    if(!isColumns(block)||!Array.isArray(path)||!path.length)return null;let container=block;
    for(let d=0;d<path.length-1;d++){const cell=ensureColumns(container)[Number(path[d])];if(!cell?.nested)return null;container=cell.nested;}
    return container;
  }
  function cellItems(cell){if(!cell)return [];if(!Array.isArray(cell.items))cell.items=[];return cell.items;}
  function getCellItem(block,path,index){const cell=getCellByPath(block,path),items=cellItems(cell),idx=Number(index);return idx>=0&&idx<items.length?items[idx]:null;}
  function isColumnStructureType(type){return ['columns1','columns','columns3'].includes(String(type||''));}
  function addBlockToCell(block,path,type,index=null){
    const cell=getCellByPath(block,path);if(!cell||!defaults[type]||isColumnStructureType(type))return false;
    snapshot();const items=cellItems(cell),at=index===null?items.length:clamp(Number(index)||0,0,items.length),item=clone(defaults[type]);normalizeBlockTree(item);items.splice(at,0,item);
    selectedCellPath=[...path];activeSelectionContext={kind:'cellblock',blockIndex:selected,path:[...path],itemIndex:at,item};render();renderInspector();sync();contextToast(`${contextBlockName(item)} agregado dentro de la columna`);return true;
  }
  function deleteCellBlockAt(block,path,index){const cell=getCellByPath(block,path),items=cellItems(cell),idx=Number(index);if(!cell||idx<0||idx>=items.length)return;snapshot();items.splice(idx,1);selectedCellPath=[...path];activeSelectionContext={kind:'cell',blockIndex:selected,path:[...path],cell};render();renderInspector();sync();contextToast('Elemento eliminado de la columna');}
  function duplicateCellBlockAt(block,path,index){const cell=getCellByPath(block,path),items=cellItems(cell),idx=Number(index);if(!cell||idx<0||idx>=items.length)return;snapshot();const item=clone(items[idx]);items.splice(idx+1,0,item);selectedCellPath=[...path];activeSelectionContext={kind:'cellblock',blockIndex:selected,path:[...path],itemIndex:idx+1,item};render();renderInspector();sync();contextToast('Elemento duplicado dentro de la columna');}
  function moveCellBlockAt(block,path,index,delta){const cell=getCellByPath(block,path),items=cellItems(cell),idx=Number(index),to=idx+Number(delta);if(!cell||idx<0||idx>=items.length||to<0||to>=items.length)return;snapshot();[items[idx],items[to]]=[items[to],items[idx]];selectedCellPath=[...path];activeSelectionContext={kind:'cellblock',blockIndex:selected,path:[...path],itemIndex:to,item:items[to]};render();renderInspector();sync();}
  function copyCellBlockAt(block,path,index,cut=false){const item=getCellItem(block,path,index);if(!item)return;setBuilderClipboard({kind:'cellblock',data:clone(item)},cut?'Elemento cortado':'Elemento copiado');if(cut)deleteCellBlockAt(block,path,index);}
  function pasteCellBlockAt(block,path,index=null,after=true){const clip=getBuilderClipboard(),cell=getCellByPath(block,path);if(!cell||!clip||!['cellblock','block'].includes(clip.kind))return contextToast('Copia primero un bloque o elemento');const data=clone(clip.data);if(isColumns(data))return contextToast('Las columnas se agregan como estructura interna');normalizeBlockTree(data);snapshot();const items=cellItems(cell);let at=index===null?items.length:Number(index)+(after?1:0);at=clamp(at,0,items.length);items.splice(at,0,data);selectedCellPath=[...path];activeSelectionContext={kind:'cellblock',blockIndex:selected,path:[...path],itemIndex:at,item:data};render();renderInspector();sync();contextToast('Elemento pegado dentro de la columna');}
  function beginColumnStructureMutation(){
    suppressColumnContentInput=true;
    try{hideContextMenu();}catch(e){}
    contextTarget=null;
    setTimeout(()=>{suppressColumnContentInput=false;},0);
  }
  function setCellSelectionAfterMutation(blockIndex,path){
    selected=blockIndex;
    selectedCellPath=Array.isArray(path)?[...path]:null;
    if(selectedCellPath&&isColumns(blocks[selected])){
      const c=getCellByPath(blocks[selected],selectedCellPath);
      activeSelectionContext=c?{kind:'cell',blockIndex:selected,path:[...selectedCellPath],cell:c}:{kind:'block',blockIndex:selected,block:blocks[selected]};
      if(!c)selectedCellPath=null;
    }else activeSelectionContext=blocks[selected]?{kind:'block',blockIndex:selected,block:blocks[selected]}:null;
  }
  function addNestedAtPath(block,path,count){const cell=getCellByPath(block,path);if(!cell||path.length>=3)return;if(cell.nested&&isColumns(cell.nested)){selectedCellPath=[...path];render();renderInspector();alert('Esta columna ya contiene una estructura interna. Selecciona una de sus subcolumnas para agregar otro nivel.');return;}snapshot();cell.nested=makeNestedColumns(count);selectedCellPath=[...path,0];render();renderInspector();sync();}
  function deleteCellAtPath(block,path){
    const blockIndex=selected,container=getContainerForCellPath(block,path);if(!container)return;
    const idx=Number(path[path.length-1]),cells=ensureColumns(container);if(idx<0||idx>=cells.length)return;
    beginColumnStructureMutation();snapshot();cells.splice(idx,1);
    let nextPath=null;
    if(!cells.length){
      if(path.length===1){
        blocks.splice(blockIndex,1);selected=Math.min(blockIndex,blocks.length-1);selectedCellPath=null;activeSelectionContext=blocks[selected]?{kind:'block',blockIndex:selected,block:blocks[selected]}:null;
      }else{
        const parentPath=path.slice(0,-1),parent=getCellByPath(block,parentPath);if(parent)parent.nested=null;nextPath=parentPath;setCellSelectionAfterMutation(blockIndex,nextPath);
      }
    }else{
      redistributeCellWidthsEvenly(container);nextPath=[...path.slice(0,-1),Math.min(idx,cells.length-1)];setCellSelectionAfterMutation(blockIndex,nextPath);
      contextToast(cells.length===1?'Columna restante ajustada a 100%':`${cells.length} columnas redistribuidas automáticamente`);
    }
    render();renderInspector();sync();
  }
  function addSiblingCell(block,path,after=true){const container=getContainerForCellPath(block,path);if(!container)return;const cells=ensureColumns(container);if(cells.length>=4)return alert('Máximo 4 columnas por fila para mantener compatibilidad de email.');snapshot();const idx=Number(path[path.length-1])+(after?1:0),eq=100/(cells.length+1);cells.forEach(c=>c.width=eq);cells.splice(idx,0,makeCell('<p>Nuevo contenido.</p>',eq));normalizeCellWidths(container);selectedCellPath=[...path.slice(0,-1),idx];render();renderInspector();sync();}
  function normalizeCellContentHtml(html){
    const box=document.createElement('div');box.innerHTML=String(html||'');
    [...box.childNodes].forEach(node=>{if(node.nodeType===Node.TEXT_NODE&&node.textContent.trim()){const p=document.createElement('p');p.textContent=node.textContent;node.replaceWith(p);}});
    return box.innerHTML;
  }
  function cleanColumnContentHtml(contentEl){
    const cloneEl=contentEl.cloneNode(true);
    cloneEl.querySelectorAll('.column-inner-tools').forEach(n=>n.remove());
    cloneEl.querySelectorAll('.column-inner-wrap').forEach(w=>{
      const original=w.querySelector(':scope > .column-inner-original');
      if(original){
        original.classList.remove('column-inner-original');
        original.removeAttribute('data-inner-index');
        w.replaceWith(original);
      }else{
        const frag=document.createDocumentFragment();while(w.firstChild)frag.appendChild(w.firstChild);w.replaceWith(frag);
      }
    });
    cloneEl.querySelectorAll('[data-inner-index]').forEach(n=>n.removeAttribute('data-inner-index'));
    return cloneEl.innerHTML;
  }
  function decorateColumnContent(contentEl){
    if(!contentEl)return;
    const path=contentEl.dataset.cellContent||'';
    const direct=[...contentEl.children].filter(el=>!el.classList.contains('column-inner-wrap'));
    direct.forEach((child,idx)=>{
      const wrap=document.createElement('div');wrap.className='column-inner-wrap';wrap.dataset.innerIndex=String(idx);
      child.before(wrap);child.classList.add('column-inner-original');child.dataset.innerIndex=String(idx);wrap.appendChild(child);
      const tools=document.createElement('span');tools.className='column-inner-tools';tools.setAttribute('contenteditable','false');
      const tag=(child.tagName||'ELEMENTO').toLowerCase();const labels={h1:'Título',h2:'Título',h3:'Título',h4:'Título',p:'Texto',img:'Imagen',a:'Enlace',table:'Tabla',ul:'Lista',ol:'Lista',div:'Bloque',span:'Texto',button:'Botón'};
      tools.innerHTML=`<span class="column-inner-label">${labels[tag]||tag.toUpperCase()}</span><button type="button" data-inner-delete="${path}" data-inner-index="${idx}" title="Eliminar este elemento">×</button>`;
      wrap.appendChild(tools);
    });
  }
  function deleteInnerElementAtPath(block,path,index){
    const blockIndex=selected,cell=getCellByPath(block,path);if(!cell)return;
    const box=document.createElement('div');box.innerHTML=normalizeCellContentHtml(cell.content||'');
    const elements=[...box.children],idx=Number(index);if(idx<0||idx>=elements.length)return;
    beginColumnStructureMutation();snapshot();elements[idx].remove();cell.content=box.innerHTML;setCellSelectionAfterMutation(blockIndex,path);render();renderInspector();sync();contextToast('Elemento eliminado');
  }
  function clearCellContentAtPath(block,path){const blockIndex=selected,cell=getCellByPath(block,path);if(!cell)return;beginColumnStructureMutation();snapshot();cell.content='';cell.items=[];setCellSelectionAfterMutation(blockIndex,path);render();renderInspector();sync();contextToast('Contenido de columna vaciado');}
  function deleteNestedAtPath(block,path){const blockIndex=selected,cell=getCellByPath(block,path);if(!cell?.nested)return;beginColumnStructureMutation();snapshot();cell.nested=null;setCellSelectionAfterMutation(blockIndex,path);render();renderInspector();sync();contextToast('Estructura interna eliminada');}

  const CONTEXT_CLIPBOARD_KEY='emailsend_builder_clipboard_v2';
  let builderClipboard=null,contextTarget=null,contextMenuEl=null,contextToastTimer=null,contextMenuOpenGuardUntil=0;
  try{builderClipboard=JSON.parse(localStorage.getItem(CONTEXT_CLIPBOARD_KEY)||'null');}catch(e){builderClipboard=null;}
  function setBuilderClipboard(payload,message='Copiado al portapapeles del builder'){
    builderClipboard=clone(payload);try{localStorage.setItem(CONTEXT_CLIPBOARD_KEY,JSON.stringify(builderClipboard));}catch(e){}
    contextToast(message);
  }
  function getBuilderClipboard(){return builderClipboard;}
  function contextToast(message){
    let el=document.getElementById('builderContextToast');
    if(!el){el=document.createElement('div');el.id='builderContextToast';el.className='builder-context-toast';document.body.appendChild(el);}
    el.textContent=message;el.classList.add('show');clearTimeout(contextToastTimer);contextToastTimer=setTimeout(()=>el.classList.remove('show'),1450);
  }
  function contextBlockName(b){const m={hero:'Hero',title:'Título',text:'Texto',image:'Imagen',button:'Botón',card:'Tarjeta',quote:'Destacado',logo:'Logo',columns1:'1 columna',columns:'Columnas',columns3:'Columnas',divider:'Separador',spacer:'Espacio',social:'Social',footer:'Footer',html:'HTML',iconbox:'Icon Box',video:'Video',product:'Producto',testimonial:'Testimonio',stats:'Estadísticas',iconlist:'Lista con iconos',banner:'Banner',pricing:'Pricing',qr:'Código QR',header:'Header'};return m[b?.type]||'Bloque';}
  function partLabel(part){const m={title:'Título',text:'Texto',content:'Contenido',button:'Botón',image:'Imagen',logo:'Logo',quote:'Destacado',footer:'Footer',instagram:'Instagram',facebook:'Facebook',linkedin:'LinkedIn',website:'Web',divider:'Separador',spacer:'Espacio',icon:'Icono',name:'Nombre',price:'Precio',oldPrice:'Precio anterior',avatar:'Foto',author:'Autor',role:'Cargo',stars:'Estrellas',items:'Lista',badge:'Etiqueta',plan:'Plan',period:'Periodo',features:'Características',qr:'Código QR',menu1:'Menú 1',menu2:'Menú 2',menu3:'Menú 3',stat1:'Estadística 1',stat2:'Estadística 2',stat3:'Estadística 3'};return m[part]||'Elemento';}
  function partCategory(block,part){if(part==='button')return 'button';if(['image','logo','avatar'].includes(part))return 'image';if(['title','name','plan'].includes(part))return 'title';if(['text','content','quote','footer','icon','price','oldPrice','author','role','stars','items','badge','period','features','qr','menu1','menu2','menu3'].includes(part))return 'text';if(['instagram','facebook','linkedin','website'].includes(part))return 'link';if(part==='divider'||part==='spacer')return part;return 'text';}
  function partHidden(block,part){return !!(block?.hiddenParts&&block.hiddenParts[part]);}
  function setPartHidden(block,part,hidden=true){if(!block)return;block.hiddenParts=block.hiddenParts&&typeof block.hiddenParts==='object'?block.hiddenParts:{};if(hidden)block.hiddenParts[part]=true;else delete block.hiddenParts[part];}
  function partIsWholeBlock(block,part){return !!block&&['title','text','image','button','divider','spacer','logo','quote','footer'].includes(block.type);}
  function partField(block,part){if(!block)return null;
    if(block.type==='hero'||block.type==='card'||block.type==='banner'){if(part==='title')return 'title';if(part==='text')return 'text';if(part==='button')return 'button';}
    if(block.type==='iconbox'){if(['icon','title','text'].includes(part))return part;}
    if(block.type==='video'){if(part==='image')return 'thumbnail';if(['title','text','button'].includes(part))return part;}
    if(block.type==='product'){if(part==='image')return 'src';if(part==='title'||part==='name')return 'name';if(['text','price','oldPrice','button'].includes(part))return part;}
    if(block.type==='testimonial'){if(part==='avatar'||part==='image')return 'avatar';if(part==='text')return 'quote';if(['author','role','stars'].includes(part))return part;}
    if(block.type==='iconlist'){if(part==='icon')return 'icon';if(part==='items'||part==='content')return 'items';}
    if(block.type==='pricing'){if(['badge','plan','price','period','features','button'].includes(part))return part;}
    if(block.type==='qr'){if(part==='qr')return 'data';if(part==='text'||part==='content')return 'label';}
    if(block.type==='header'){if(part==='logo'||part==='image')return 'src';if(['menu1','menu2','menu3','button'].includes(part))return part;}
    if(block.type==='stats'&&/^stat[123]$/.test(part))return 'value'+part.slice(-1);
    if(block.type==='title'||block.type==='text'||block.type==='button'||block.type==='quote'||block.type==='footer')return 'content';if(block.type==='image'||block.type==='logo')return 'src';if(block.type==='social'&&['instagram','facebook','linkedin','website'].includes(part))return part;return null;}
  function getPartPayload(block,part){if(!block)return null;const category=partCategory(block,part),field=partField(block,part),data={category,part};if(!field&&category!=='divider'&&category!=='spacer')return null;if(category==='button'){data.text=block[field]??'';data.href=block.href??'#';data.style={buttonBg:block.style?.buttonBg,buttonColor:block.style?.buttonColor,buttonPadding:block.style?.buttonPadding,buttonRadius:block.style?.buttonRadius,buttonFontFamily:block.style?.buttonFontFamily,buttonSize:block.style?.buttonSize};}else if(category==='image'){data.src=block[field]??'';data.alt=block.alt??'';data.style={width:block.style?.width,radius:block.style?.radius};}else if(category==='link'){data.url=block[field]??'';}else if(category==='divider'||category==='spacer'){data.style=clone(block.style||{});}else{data.html=block[field]??'';if(block.type==='stats'&&/^stat[123]$/.test(part))data.extra=block['label'+part.slice(-1)]??'';}return data;}
  function applyPartPayload(block,part,data){if(!block||!data)return false;const targetCat=partCategory(block,part),sourceCat=data.category;if(targetCat!==sourceCat&&!(['title','text'].includes(targetCat)&&['title','text'].includes(sourceCat)))return false;const field=partField(block,part);if(sourceCat==='button'){block[field]=data.text??'';block.href=data.href??'#';block.style=Object.assign({},block.style||{},data.style||{});}else if(sourceCat==='image'){block[field]=data.src??'';block.alt=data.alt??block.alt??'';block.style=Object.assign({},block.style||{},data.style||{});}else if(sourceCat==='link'){block[field]=data.url??'';}else if(sourceCat==='divider'||sourceCat==='spacer'){block.style=Object.assign({},block.style||{},data.style||{});}else{block[field]=data.html??'';if(block.type==='stats'&&/^stat[123]$/.test(part)&&data.extra!==undefined)block['label'+part.slice(-1)]=data.extra;}setPartHidden(block,part,false);return true;}
  function selectContext(ctx){activeSelectionContext=ctx||null;if(!ctx)return;if(Number.isFinite(ctx.blockIndex)){selected=ctx.blockIndex;if(ctx.kind==='cell'||ctx.kind==='inner'||ctx.kind==='cellblock')selectedCellPath=[...ctx.path];else selectedCellPath=null;}}
  function copyPartAt(blockIndex,part,cut=false){const b=blocks[blockIndex];if(!b)return;const data=getPartPayload(b,part);if(!data)return;setBuilderClipboard({kind:'part',data},cut?`${partLabel(part)} cortado`:`${partLabel(part)} copiado`);if(cut){snapshot();if(partIsWholeBlock(b,part)){blocks.splice(blockIndex,1);selected=Math.min(blockIndex,blocks.length-1);activeSelectionContext=blocks[selected]?{kind:'block',blockIndex:selected,block:blocks[selected]}:null;}else{setPartHidden(b,part,true);selected=blockIndex;activeSelectionContext=null;}selectedCellPath=null;render();renderInspector();sync();}}
  function deletePartAt(blockIndex,part){const b=blocks[blockIndex];if(!b)return;snapshot();if(partIsWholeBlock(b,part)){blocks.splice(blockIndex,1);selected=Math.min(blockIndex,blocks.length-1);activeSelectionContext=blocks[selected]?{kind:'block',blockIndex:selected,block:blocks[selected]}:null;}else{setPartHidden(b,part,true);selected=blockIndex;activeSelectionContext=null;}selectedCellPath=null;render();renderInspector();sync();contextToast(`${partLabel(part)} eliminado`);}
  function pastePartAt(blockIndex,part){const b=blocks[blockIndex],clip=getBuilderClipboard();if(!b||!clip||clip.kind!=='part')return contextToast('Copia primero un elemento');snapshot();if(!applyPartPayload(b,part,clip.data)){history.pop();return contextToast('Ese elemento no es compatible aquí');}activeSelectionContext={kind:'part',blockIndex,part};selected=blockIndex;selectedCellPath=null;render();renderInspector();sync();contextToast(`${partLabel(part)} pegado`);}
  function duplicatePartAt(blockIndex,part){const b=blocks[blockIndex];if(!b)return;const d=getPartPayload(b,part);if(!d)return;let nb=null;if(d.category==='button')nb=clone(defaults.button),nb.content=d.text||'VER MÁS',nb.href=d.href||'#',nb.style=Object.assign(nb.style||{},d.style||{});else if(d.category==='image')nb=clone(defaults.image),nb.src=d.src||'',nb.alt=d.alt||'',nb.style=Object.assign(nb.style||{},d.style||{});else if(d.category==='title')nb=clone(defaults.title),nb.content=d.html||'Nuevo título';else if(d.category==='text')nb=clone(defaults.text),nb.content=d.html||'Nuevo texto';else if(d.category==='divider')nb=clone(defaults.divider);else if(d.category==='spacer')nb=clone(defaults.spacer);else if(d.category==='link'){nb=clone(defaults.button);nb.content=partLabel(part);nb.href=d.url||'#';}if(!nb)return;snapshot();blocks.splice(blockIndex+1,0,nb);selected=blockIndex+1;selectedCellPath=null;activeSelectionContext={kind:'block',blockIndex:selected};render();renderInspector();sync();contextToast(`${partLabel(part)} duplicado como bloque`);}

  function blockFromPartPayload(data,label='Elemento'){
    if(!data)return null;let nb=null;
    if(data.category==='button'){nb=clone(defaults.button);nb.content=data.text||label||'VER MÁS';nb.href=data.href||'#';nb.style=Object.assign(nb.style||{},data.style||{});}
    else if(data.category==='image'){nb=clone(defaults.image);nb.src=data.src||'';nb.alt=data.alt||'';nb.style=Object.assign(nb.style||{},data.style||{});}
    else if(data.category==='title'){nb=clone(defaults.title);nb.content=data.html||label||'Nuevo título';}
    else if(data.category==='text'){nb=clone(defaults.text);nb.content=data.html||label||'Nuevo texto';}
    else if(data.category==='divider'){nb=clone(defaults.divider);nb.style=Object.assign(nb.style||{},data.style||{});}
    else if(data.category==='spacer'){nb=clone(defaults.spacer);nb.style=Object.assign(nb.style||{},data.style||{});}
    else if(data.category==='link'){nb=clone(defaults.button);nb.content=label||'ENLACE';nb.href=data.url||'#';}
    return nb;
  }
  function pastePartAsNewBlock(blockIndex,data,label='Elemento',after=true){
    const nb=blockFromPartPayload(data,label);if(!nb)return contextToast('Ese elemento no se puede pegar como bloque');
    snapshot();const at=clamp(blockIndex+(after?1:0),0,blocks.length);blocks.splice(at,0,nb);selected=at;selectedCellPath=null;activeSelectionContext={kind:'block',blockIndex:at,block:nb};render();renderInspector();sync();contextToast(`${label} pegado como nuevo elemento`);
  }
  function innerHtmlFromPartPayload(data,label='Elemento'){
    if(!data)return '';
    if(data.category==='button')return `<a href="${esc(data.href||'#')}" style="display:inline-block;padding:11px 16px;background:${esc(data.style?.buttonBg||'#0074c7')};color:${esc(data.style?.buttonColor||'#ffffff')};text-decoration:none;border-radius:${esc(data.style?.buttonRadius||'4px')}">${esc(data.text||label||'VER MÁS')}</a>`;
    if(data.category==='image')return `<img src="${esc(data.src||'')}" alt="${esc(data.alt||'')}" style="max-width:100%;height:auto">`;
    if(data.category==='title')return `<h3>${data.html||esc(label||'Título')}</h3>`;
    if(data.category==='text')return `<p>${data.html||esc(label||'Texto')}</p>`;
    if(data.category==='divider')return '<hr style="border:0;border-top:1px solid #dce5ec">';
    if(data.category==='spacer')return '<div style="height:30px"></div>';
    if(data.category==='link')return `<a href="${esc(data.url||'#')}">${esc(label||'Enlace')}</a>`;
    return '';
  }
  function appendPartToCell(block,path,data,label='Elemento'){
    const cell=getCellByPath(block,path);if(!cell)return;const html=innerHtmlFromPartPayload(data,label);if(!html)return contextToast('Ese elemento no se puede pegar dentro de una columna');
    snapshot();cell.content=(cell.content||'')+html;selectedCellPath=[...path];activeSelectionContext={kind:'cell',blockIndex:selected,path:[...path],cell};render();renderInspector();sync();contextToast(`${label} pegado en la columna`);
  }
  function deleteActiveSelection(){const ctx=activeSelectionContext;if(ctx?.kind==='cellblock'){const b=blocks[ctx.blockIndex];if(b)return deleteCellBlockAt(b,ctx.path,ctx.itemIndex);}if(ctx?.kind==='part')return deletePartAt(ctx.blockIndex,ctx.part);if(ctx?.kind==='inner'){const b=blocks[ctx.blockIndex];if(b)return deleteInnerElementAtPath(b,ctx.path,ctx.index);}if(ctx?.kind==='cell'){const b=blocks[ctx.blockIndex];if(b)return deleteCellAtPath(b,ctx.path);}if(ctx?.kind==='block')return deleteBlockAt(ctx.blockIndex);if(selectedCellPath&&isColumns(blocks[selected]))return deleteCellAtPath(blocks[selected],selectedCellPath);if(blocks[selected])return deleteBlockAt(selected);}
  function getInnerBox(block,path){const cell=getCellByPath(block,path);if(!cell)return null;const box=document.createElement('div');box.innerHTML=normalizeCellContentHtml(cell.content||'');return {cell,box,elements:[...box.children]};}
  function innerElementLabel(el){const tag=(el?.tagName||'ELEMENTO').toLowerCase();const labels={h1:'Título',h2:'Título',h3:'Título',h4:'Título',h5:'Título',h6:'Título',p:'Texto',span:'Texto',strong:'Texto',em:'Texto',a:'Enlace',button:'Botón',img:'Imagen',ul:'Lista',ol:'Lista',table:'Tabla',div:'Bloque'};return labels[tag]||tag.toUpperCase();}
  function canonicalFontName(value,fallback='Ubuntu Sans'){const v=String(value||'').toLowerCase();for(const name of Object.keys(fontStacks)){if(v.includes(name.toLowerCase()))return name;}return fallback;}
  function mutateInnerElement(block,path,index,mutator){const x=getInnerBox(block,path),idx=Number(index);if(!x||idx<0||idx>=x.elements.length)return null;const el=x.elements[idx];mutator(el,x);x.cell.content=x.box.innerHTML;return {cell:x.cell,element:el,index:idx};}
  function renderedInnerElement(blockIndex,path,index){const blockEl=page.querySelector(`.block[data-i="${blockIndex}"]`);if(!blockEl)return null;const content=blockEl.querySelector(`[data-cell-content="${pathKey(path)}"]`);if(!content)return null;return content.querySelector(`.column-inner-wrap[data-inner-index="${Number(index)}"] > .column-inner-original`);}
  function setInnerStyle(blockIndex,block,path,index,prop,value){const r=mutateInnerElement(block,path,index,el=>el.style.setProperty(prop,value));if(!r)return;const live=renderedInnerElement(blockIndex,path,index);if(live)live.style.setProperty(prop,value);sync();}
  function setInnerText(blockIndex,block,path,index,value){const r=mutateInnerElement(block,path,index,el=>{el.textContent=value;});if(!r)return;const live=renderedInnerElement(blockIndex,path,index);if(live)live.textContent=value;sync();}
  function resetInnerTypography(blockIndex,block,path,index){const props=['color','text-align','font-family','font-size','font-weight','line-height','letter-spacing'];const r=mutateInnerElement(block,path,index,el=>{props.forEach(prop=>el.style.removeProperty(prop));if(!(el.getAttribute('style')||'').trim())el.removeAttribute('style');});if(!r)return;const live=renderedInnerElement(blockIndex,path,index);if(live){props.forEach(prop=>live.style.removeProperty(prop));if(!(live.getAttribute('style')||'').trim())live.removeAttribute('style');}sync();}
  function applyTypographyToCellContent(cell,prop,value){const box=document.createElement('div');box.innerHTML=normalizeCellContentHtml(cell.content||'');[...box.children].forEach(el=>el.style.setProperty(prop,value));cell.content=box.innerHTML;}
  function renderedCellElement(blockIndex,path){return page.querySelector(`.block[data-i="${blockIndex}"] .email-column[data-cell-path="${pathKey(path)}"]`);}
  function refreshCellVisual(blockIndex,path,cell){const el=renderedCellElement(blockIndex,path);if(!el||!cell)return;const cs=ensureStyle(cell);el.style.backgroundColor=cs.background||'transparent';if(cs.backgroundImage){const op=clamp(Number(cs.overlayOpacity)||0,0,1),overlay=op>0?`linear-gradient(${colorRgba(cs.overlayColor||'#000000',op)},${colorRgba(cs.overlayColor||'#000000',op)}),`:'';el.style.backgroundImage=`${overlay}url('${safeCssUrl(cs.backgroundImage)}')`;el.style.backgroundSize=cs.backgroundSize||'cover';el.style.backgroundPosition=cs.backgroundPosition||'center center';el.style.backgroundRepeat=cs.backgroundRepeat||'no-repeat';}else el.style.backgroundImage='none';el.style.color=cs.color||'#1f2937';el.style.padding=cs.padding||'18px 20px';el.style.margin=cs.margin||'0px';el.style.borderWidth=cs.borderWidth||'0px';el.style.borderStyle=cs.borderStyle||'solid';el.style.borderColor=cs.borderColor||'#dce5ec';el.style.textAlign=cs.align||'left';el.style.fontFamily=fontCss(cs.fontFamily,'Ubuntu Sans');el.style.fontSize=cs.fontSize||'16px';el.style.fontWeight=cs.fontWeight||'400';el.style.lineHeight=cs.lineHeight||'1.5';el.style.letterSpacing=cs.letterSpacing||'0px';el.style.borderRadius=cs.radius||'0';el.style.minHeight=cs.minHeight||'0px';el.style.justifyContent=cs.verticalAlign==='middle'?'center':(cs.verticalAlign==='bottom'?'flex-end':'flex-start');}
  function refreshCellContentVisual(blockIndex,path,cell){const content=page.querySelector(`.block[data-i="${blockIndex}"] [data-cell-content="${pathKey(path)}"]`);if(!content||!cell)return;const box=document.createElement('div');box.innerHTML=normalizeCellContentHtml(cell.content||'');const modelEls=[...box.children],liveEls=[...content.querySelectorAll(':scope > .column-inner-wrap > .column-inner-original')];modelEls.forEach((m,i)=>{const l=liveEls[i];if(!l)return;['color','text-align','font-family','font-size','font-weight','line-height','letter-spacing'].forEach(prop=>{const val=m.style.getPropertyValue(prop);if(val)l.style.setProperty(prop,val);else l.style.removeProperty(prop);});});}
  function refreshColumnWidthsVisual(blockIndex,block,path){const container=getContainerForCellPath(block,path);if(!container)return;const widths=columnWidths(container),parentPath=path.slice(0,-1),key=parentPath.length?pathKey(parentPath):'root',blockEl=page.querySelector(`.block[data-i="${blockIndex}"]`);if(!blockEl)return;const grids=[...blockEl.querySelectorAll('.dynamic-columns[data-columns-path]')];const grid=grids.find(g=>g.dataset.columnsPath===key);if(!grid)return;grid.style.gridTemplateColumns=widths.map(w=>`${Math.max(.01,w).toFixed(4)}%`).join(' ');[...grid.children].filter(el=>el.classList?.contains('email-column')).forEach((el,j)=>{el.dataset.cellWidth=Number(container.cells?.[j]?.width||0).toFixed(2);const badge=el.querySelector(':scope > .column-width-badge');if(badge)badge.textContent=Math.round(Number(container.cells?.[j]?.width||0))+'%';});}
  function duplicateInnerAtPath(block,path,index){const x=getInnerBox(block,path),idx=Number(index);if(!x||idx<0||idx>=x.elements.length)return;snapshot();x.elements[idx].after(x.elements[idx].cloneNode(true));x.cell.content=x.box.innerHTML;selectedCellPath=[...path];render();renderInspector();sync();}
  function copyInnerAtPath(block,path,index,cut=false){const x=getInnerBox(block,path),idx=Number(index);if(!x||idx<0||idx>=x.elements.length)return;setBuilderClipboard({kind:'inner',html:x.elements[idx].outerHTML,label:(x.elements[idx].tagName||'Elemento').toLowerCase()},cut?'Elemento cortado':'Elemento copiado');if(cut){snapshot();x.elements[idx].remove();x.cell.content=x.box.innerHTML;selectedCellPath=[...path];render();renderInspector();sync();}}
  function pasteInnerAtPath(block,path,index,after=true){const clip=getBuilderClipboard();if(!clip||clip.kind!=='inner')return contextToast('Copia primero un elemento interno');const x=getInnerBox(block,path);if(!x)return;const holder=document.createElement('div');holder.innerHTML=clip.html||'';const nodes=[...holder.children];if(!nodes.length)return;snapshot();const idx=Number(index),target=x.elements[idx];if(target){if(after){let ref=target;nodes.forEach(n=>{ref.after(n);ref=n;});}else{nodes.forEach(n=>target.before(n));}}else nodes.forEach(n=>x.box.appendChild(n));x.cell.content=x.box.innerHTML;selectedCellPath=[...path];render();renderInspector();sync();contextToast('Elemento pegado');}
  function appendInnerToCell(block,path){const clip=getBuilderClipboard();if(!clip||clip.kind!=='inner')return contextToast('Copia primero un elemento interno');const x=getInnerBox(block,path);if(!x)return;const holder=document.createElement('div');holder.innerHTML=clip.html||'';const nodes=[...holder.children];if(!nodes.length)return;snapshot();nodes.forEach(n=>x.box.appendChild(n));x.cell.content=x.box.innerHTML;selectedCellPath=[...path];render();renderInspector();sync();contextToast('Elemento pegado en la columna');}
  function moveInnerAtPath(block,path,index,delta){const x=getInnerBox(block,path),idx=Number(index),to=idx+delta;if(!x||idx<0||idx>=x.elements.length||to<0||to>=x.elements.length)return;snapshot();const el=x.elements[idx],other=x.elements[to];if(delta<0)other.before(el);else other.after(el);x.cell.content=x.box.innerHTML;selectedCellPath=[...path];render();renderInspector();sync();}
  function insertCellDataAtPath(block,path,data,after=true){
    const container=getContainerForCellPath(block,path);if(!container)return;const cells=ensureColumns(container);if(cells.length>=4)return alert('Máximo 4 columnas por fila para mantener compatibilidad de email.');
    normalizeCellWidths(container);const idx=Number(path[path.length-1]),target=cells[idx];if(!target)return;snapshot();let copy=clone(data);if(!copy.style)copy.style=makeCell('').style;
    const donor=Number(target.width)||100/cells.length,newW=Math.max(8,donor/2);if(donor-newW>=8){target.width=donor-newW;copy.width=newW;}else{copy.width=100/(cells.length+1);cells.forEach(c=>c.width=copy.width);}
    const at=idx+(after?1:0);cells.splice(at,0,copy);normalizeCellWidths(container);selectedCellPath=[...path.slice(0,-1),at];render();renderInspector();sync();
  }
  function duplicateCellAtPath(block,path){const cell=getCellByPath(block,path);if(cell)insertCellDataAtPath(block,path,cell,true);}
  function copyCellAtPath(block,path,cut=false){const cell=getCellByPath(block,path);if(!cell)return;setBuilderClipboard({kind:'cell',data:clone(cell)},cut?'Columna cortada':'Columna copiada');if(cut)deleteCellAtPath(block,path);}
  function pasteCellAtPath(block,path,after=true){const clip=getBuilderClipboard();if(!clip||clip.kind!=='cell')return contextToast('Copia primero una columna');insertCellDataAtPath(block,path,clip.data,after);contextToast('Columna pegada');}
  function copyBlockAt(index,cut=false){const b=blocks[index];if(!b)return;setBuilderClipboard({kind:'block',data:clone(b)},cut?'Bloque cortado':'Bloque copiado');if(cut){snapshot();blocks.splice(index,1);selected=Math.min(index,blocks.length-1);selectedCellPath=null;render();renderInspector();sync();}}
  function duplicateBlockAt(index){const b=blocks[index];if(!b)return;snapshot();blocks.splice(index+1,0,clone(b));selected=index+1;selectedCellPath=null;render();renderInspector();sync();}
  function pasteBlockAt(index,after=true){const clip=getBuilderClipboard();if(!clip||clip.kind!=='block')return contextToast('Copia primero un bloque');snapshot();const at=clamp(index+(after?1:0),0,blocks.length);const data=clone(clip.data);normalizeBlockTree(data);blocks.splice(at,0,data);selected=at;selectedCellPath=null;render();renderInspector();sync();contextToast('Bloque pegado');}
  function pasteBlockAtEnd(){const clip=getBuilderClipboard();if(!clip||clip.kind!=='block')return contextToast('Copia primero un bloque');snapshot();const data=clone(clip.data);normalizeBlockTree(data);blocks.push(data);selected=blocks.length-1;selectedCellPath=null;render();renderInspector();sync();contextToast('Bloque pegado');}
  function deleteBlockAt(index){if(index<0||index>=blocks.length)return;snapshot();blocks.splice(index,1);selected=Math.min(index,blocks.length-1);selectedCellPath=null;render();renderInspector();sync();}
  function resetBlockSizeAt(index){const b=blocks[index];if(!b)return;snapshot();const s=ensureStyle(b);s.blockWidth='100%';delete s.minHeight;selected=index;selectedCellPath=null;render();renderInspector();sync();contextToast('Tamaño restablecido');}
  function contextSeparator(){return {separator:true};}
  function contextItem(label,action,icon='',shortcut='',danger=false,disabled=false){return {label,action,icon,shortcut,danger,disabled};}
  function contextMenuItems(ctx){
    const clip=getBuilderClipboard();if(!ctx)return [];
    if(ctx.kind==='block')return [
      contextItem('Editar propiedades','properties','⚙'),contextSeparator(),
      contextItem('Duplicar bloque','duplicate','⧉','Ctrl+D'),contextItem('Copiar bloque','copy','▣','Ctrl+C'),contextItem('Cortar bloque','cut','✂','Ctrl+X'),
      contextItem('Pegar antes','paste-before','⇧','Ctrl+V',false,clip?.kind!=='block'),contextItem('Pegar después','paste-after','⇩','',false,clip?.kind!=='block'),contextSeparator(),
      contextItem('Subir','move-up','↑'),contextItem('Bajar','move-down','↓'),contextItem('Restablecer tamaño','reset-size','↔'),contextSeparator(),contextItem('Eliminar bloque','delete','×','Del',true)
    ];
    if(ctx.kind==='cell')return [
      contextItem('Editar columna','properties','⚙'),contextSeparator(),contextItem('Agregar título aquí','add-title','T'),contextItem('Agregar texto aquí','add-text','¶'),contextItem('Agregar imagen aquí','add-image','▧'),contextItem('Agregar botón aquí','add-button','▰'),contextSeparator(),
      contextItem('Duplicar columna','duplicate','⧉'),contextItem('Copiar columna','copy','▣','Ctrl+C'),contextItem('Cortar columna','cut','✂','Ctrl+X'),
      contextItem('Pegar bloque dentro','paste-block-inside','↳','Ctrl+V',false,!['cellblock','block'].includes(clip?.kind)),contextItem('Pegar HTML interno','paste-inside','↳','',false,clip?.kind!=='inner'),contextSeparator(),
      contextItem('Agregar columna al lado','add-column','＋'),...(ctx.path.length<3?[contextItem('Agregar 1 columna dentro','nest-1','▯'),contextItem('Agregar 2 columnas dentro','nest-2','▥'),contextItem('Agregar 3 columnas dentro','nest-3','▦')]:[]),contextItem('Vaciar contenido','clear','⌫'),...(ctx.cell?.nested?[contextItem('Eliminar estructura interna','delete-nested','⊘','',true)]:[]),contextSeparator(),contextItem('Eliminar columna','delete','×','Del',true)
    ];
    if(ctx.kind==='cellblock')return [
      contextItem('Editar propiedades','properties','⚙'),contextSeparator(),contextItem('Duplicar elemento','duplicate','⧉','Ctrl+D'),contextItem('Copiar elemento','copy','▣','Ctrl+C'),contextItem('Cortar elemento','cut','✂','Ctrl+X'),contextItem('Pegar después','paste-after','⇩','Ctrl+V',false,!['cellblock','block'].includes(clip?.kind)),contextSeparator(),contextItem('Mover arriba','move-up','↑'),contextItem('Mover abajo','move-down','↓'),contextSeparator(),contextItem('Eliminar elemento','delete','×','Del',true)
    ];
    if(ctx.kind==='part')return [
      contextItem('Editar propiedades','properties','⚙'),contextSeparator(),contextItem('Duplicar elemento','duplicate','⧉','Ctrl+D'),contextItem('Copiar elemento','copy','▣','Ctrl+C'),contextItem('Cortar elemento','cut','✂','Ctrl+X'),contextItem('Pegar como nuevo después','paste-new','▣','Ctrl+V',false,!clip),contextItem('Reemplazar con copiado','paste','↺','',false,clip?.kind!=='part'),contextSeparator(),contextItem(`Eliminar solo ${partLabel(ctx.part).toLowerCase()}`,'delete','×','Del',true)
    ];
    if(ctx.kind==='inner')return [
      contextItem('Editar columna contenedora','properties','⚙'),contextSeparator(),contextItem('Duplicar elemento','duplicate','⧉','Ctrl+D'),contextItem('Copiar elemento','copy','▣','Ctrl+C'),contextItem('Cortar elemento','cut','✂','Ctrl+X'),contextItem('Pegar antes','paste-before','⇧','',false,clip?.kind!=='inner'),contextItem('Pegar después','paste-after','⇩','Ctrl+V',false,clip?.kind!=='inner'),contextSeparator(),contextItem('Mover arriba','move-up','↑'),contextItem('Mover abajo','move-down','↓'),contextSeparator(),contextItem('Eliminar elemento','delete','×','Del',true)
    ];
    if(ctx.kind==='canvas')return [contextItem('Pegar bloque','paste-end','▣','Ctrl+V',false,clip?.kind!=='block'),contextSeparator(),contextItem('Agregar título','add-title','T'),contextItem('Agregar texto','add-text','¶'),contextItem('Agregar imagen','add-image','▧'),contextItem('Agregar botón','add-button','▰'),contextItem('Agregar 2 columnas','add-columns','▥')];
    if(ctx.kind==='palette')return [contextItem('Agregar al final','palette-end','＋'),contextItem('Agregar después del seleccionado','palette-after','↳')];
    return [];
  }
  function ensureContextMenu(){
    if(contextMenuEl)return contextMenuEl;contextMenuEl=document.createElement('div');contextMenuEl.id='builderContextMenu';contextMenuEl.className='builder-context-menu';contextMenuEl.setAttribute('role','menu');contextMenuEl.setAttribute('aria-hidden','true');document.body.appendChild(contextMenuEl);
    contextMenuEl.addEventListener('pointerdown',e=>e.stopPropagation());contextMenuEl.addEventListener('contextmenu',e=>{e.preventDefault();e.stopPropagation();});contextMenuEl.addEventListener('click',e=>{const btn=e.target.closest('[data-context-action]');if(!btn||btn.disabled)return;e.preventDefault();e.stopPropagation();const action=btn.dataset.contextAction,ctx=contextTarget;hideContextMenu(true);runContextAction(action,ctx);});return contextMenuEl;
  }
  function showContextMenu(x,y,ctx){
    const menu=ensureContextMenu(),items=contextMenuItems(ctx);if(!items.length)return;contextTarget=ctx;contextMenuOpenGuardUntil=Date.now()+280;
    const title=ctx.kind==='block'?contextBlockName(blocks[ctx.blockIndex]):ctx.kind==='cellblock'?contextBlockName(getCellItem(blocks[ctx.blockIndex],ctx.path,ctx.itemIndex)):ctx.kind==='part'?partLabel(ctx.part):ctx.kind==='cell'?'Columna '+ctx.path.map(n=>n+1).join('.'):ctx.kind==='inner'?'Elemento interno':ctx.kind==='palette'?'Agregar elemento':'Lienzo';
    menu.innerHTML=`<div class="builder-context-title">${esc(title)}</div>`+items.map(it=>it.separator?'<div class="builder-context-separator"></div>':`<button type="button" role="menuitem" data-context-action="${it.action}" class="${it.danger?'danger':''}" ${it.disabled?'disabled':''}><span class="builder-context-icon">${it.icon||''}</span><span>${esc(it.label)}</span>${it.shortcut?`<kbd>${esc(it.shortcut)}</kbd>`:''}</button>`).join('');
    menu.style.left='0px';menu.style.top='0px';menu.classList.add('open');menu.setAttribute('aria-hidden','false');const r=menu.getBoundingClientRect(),pad=8,left=Math.max(pad,Math.min(x,window.innerWidth-r.width-pad)),top=Math.max(pad,Math.min(y,window.innerHeight-r.height-pad));menu.style.left=left+'px';menu.style.top=top+'px';
  }
  function hideContextMenu(force=false){if(!contextMenuEl)return;if(!force&&Date.now()<contextMenuOpenGuardUntil)return;contextMenuEl.classList.remove('open');contextMenuEl.setAttribute('aria-hidden','true');contextTarget=null;contextMenuOpenGuardUntil=0;}
  function contextFromEventTarget(target){
    try{
      if(!target)return null;
      const blockEl=target.closest?.('.block[data-i]');
      if(blockEl){
        const blockIndex=Number(blockEl.dataset.i),b=blocks[blockIndex];
        if(!b)return null;
        const cellBlock=target.closest?.('.column-child-block[data-cell-block-index]');
        if(cellBlock){
          const path=String(cellBlock.dataset.cellBlockPath||'').split('.').filter(x=>x!=='').map(Number);
          return {kind:'cellblock',blockIndex,block:b,path,itemIndex:Number(cellBlock.dataset.cellBlockIndex),item:getCellItem(b,path,Number(cellBlock.dataset.cellBlockIndex))};
        }
        const inner=target.closest?.('.column-inner-wrap');
        if(inner){
          const content=inner.closest?.('[data-cell-content]');
          if(content){
            const path=String(content.dataset.cellContent||'').split('.').filter(x=>x!=='').map(Number);
            return {kind:'inner',blockIndex,block:b,path,index:Number(inner.dataset.innerIndex)};
          }
        }
        const cellEl=target.closest?.('.email-column[data-cell-path]');
        if(cellEl){
          const path=String(cellEl.dataset.cellPath||'').split('.').filter(x=>x!=='').map(Number);
          const cell=getCellByPath(b,path);
          if(cell)return {kind:'cell',blockIndex,block:b,path,cell};
        }
        const partEl=target.closest?.('[data-builder-part]');
        if(partEl)return {kind:'part',blockIndex,block:b,part:partEl.dataset.builderPart};
        return {kind:'block',blockIndex,block:b};
      }
      const pal=target.closest?.('[data-add]');if(pal)return {kind:'palette',type:pal.dataset.add};
      if(target===page||page.contains(target))return {kind:'canvas'};
      return null;
    }catch(err){
      console.error('EMAILSEND context menu:',err);
      const fallback=target?.closest?.('.block[data-i]');
      if(fallback){const blockIndex=Number(fallback.dataset.i);return {kind:'block',blockIndex,block:blocks[blockIndex]};}
      return target===page||page.contains(target)?{kind:'canvas'}:null;
    }
  }
  function runContextAction(action,ctx){
    if(!ctx)return;selectContext(ctx);
    if(ctx.kind==='block'){
      if(action==='properties'){render();renderInspector();return;}if(action==='duplicate')return duplicateBlockAt(ctx.blockIndex);if(action==='copy')return copyBlockAt(ctx.blockIndex,false);if(action==='cut')return copyBlockAt(ctx.blockIndex,true);if(action==='paste-before')return pasteBlockAt(ctx.blockIndex,false);if(action==='paste-after')return pasteBlockAt(ctx.blockIndex,true);if(action==='move-up')return move(ctx.blockIndex,-1);if(action==='move-down')return move(ctx.blockIndex,1);if(action==='reset-size')return resetBlockSizeAt(ctx.blockIndex);if(action==='delete')return deleteBlockAt(ctx.blockIndex);
    }
    if(ctx.kind==='cell'){
      const b=blocks[ctx.blockIndex];if(!b)return;if(action==='properties'){render();renderInspector();return;}if(action==='duplicate')return duplicateCellAtPath(b,ctx.path);if(action==='copy')return copyCellAtPath(b,ctx.path,false);if(action==='cut')return copyCellAtPath(b,ctx.path,true);if(action==='paste-before')return pasteCellAtPath(b,ctx.path,false);if(action==='paste-after')return pasteCellAtPath(b,ctx.path,true);if(action==='paste-block-inside')return pasteCellBlockAt(b,ctx.path,null,true);if(action==='paste-inside')return appendInnerToCell(b,ctx.path);if(action==='add-title')return addBlockToCell(b,ctx.path,'title');if(action==='add-text')return addBlockToCell(b,ctx.path,'text');if(action==='add-image')return addBlockToCell(b,ctx.path,'image');if(action==='add-button')return addBlockToCell(b,ctx.path,'button');if(action==='add-column')return addSiblingCell(b,ctx.path,true);if(action==='nest-1')return addNestedAtPath(b,ctx.path,1);if(action==='nest-2')return addNestedAtPath(b,ctx.path,2);if(action==='nest-3')return addNestedAtPath(b,ctx.path,3);if(action==='clear')return clearCellContentAtPath(b,ctx.path);if(action==='delete-nested')return deleteNestedAtPath(b,ctx.path);if(action==='delete')return deleteCellAtPath(b,ctx.path);
    }
    if(ctx.kind==='cellblock'){
      const b=blocks[ctx.blockIndex];if(!b)return;if(action==='properties'){renderInspector();return;}if(action==='duplicate')return duplicateCellBlockAt(b,ctx.path,ctx.itemIndex);if(action==='copy')return copyCellBlockAt(b,ctx.path,ctx.itemIndex,false);if(action==='cut')return copyCellBlockAt(b,ctx.path,ctx.itemIndex,true);if(action==='paste-after')return pasteCellBlockAt(b,ctx.path,ctx.itemIndex,true);if(action==='move-up')return moveCellBlockAt(b,ctx.path,ctx.itemIndex,-1);if(action==='move-down')return moveCellBlockAt(b,ctx.path,ctx.itemIndex,1);if(action==='delete')return deleteCellBlockAt(b,ctx.path,ctx.itemIndex);
    }
    if(ctx.kind==='part'){
      const b=blocks[ctx.blockIndex];if(!b)return;if(action==='properties'){render();renderInspector();return;}if(action==='duplicate')return duplicatePartAt(ctx.blockIndex,ctx.part);if(action==='copy')return copyPartAt(ctx.blockIndex,ctx.part,false);if(action==='cut')return copyPartAt(ctx.blockIndex,ctx.part,true);if(action==='paste-new')return keyboardPaste(ctx);if(action==='paste')return pastePartAt(ctx.blockIndex,ctx.part);if(action==='restore'){snapshot();setPartHidden(b,ctx.part,false);render();renderInspector();sync();return;}if(action==='delete')return deletePartAt(ctx.blockIndex,ctx.part);
    }
    if(ctx.kind==='inner'){
      const b=blocks[ctx.blockIndex];if(!b)return;if(action==='properties'){selectedCellPath=[...ctx.path];render();renderInspector();return;}if(action==='duplicate')return duplicateInnerAtPath(b,ctx.path,ctx.index);if(action==='copy')return copyInnerAtPath(b,ctx.path,ctx.index,false);if(action==='cut')return copyInnerAtPath(b,ctx.path,ctx.index,true);if(action==='paste-before')return pasteInnerAtPath(b,ctx.path,ctx.index,false);if(action==='paste-after')return pasteInnerAtPath(b,ctx.path,ctx.index,true);if(action==='move-up')return moveInnerAtPath(b,ctx.path,ctx.index,-1);if(action==='move-down')return moveInnerAtPath(b,ctx.path,ctx.index,1);if(action==='delete')return deleteInnerElementAtPath(b,ctx.path,ctx.index);
    }
    if(ctx.kind==='canvas'){if(action==='paste-end')return pasteBlockAtEnd();if(action.startsWith('add-'))return insertPalette(action.replace('add-',''),blocks.length);}
    if(ctx.kind==='palette'){if(action==='palette-end')return insertPalette(ctx.type,blocks.length);if(action==='palette-after')return insertPalette(ctx.type,Math.min(blocks.length,selected+1));}
  }
  function previewCellItems(cell,path){
    const items=cellItems(cell);if(!items.length)return `<div class="cell-elements-dropzone" data-cell-items-zone="${pathKey(path)}" contenteditable="false"><span>Arrastra aquí Título, Texto, Imagen, Botón u otro elemento</span></div>`;
    return `<div class="column-child-blocks" data-cell-items-zone="${pathKey(path)}" contenteditable="false">${items.map((item,j)=>{const selectedChild=activeSelectionContext?.kind==='cellblock'&&activeSelectionContext.blockIndex===selected&&samePath(activeSelectionContext.path,path)&&Number(activeSelectionContext.itemIndex)===j;return `<div class="column-child-block${selectedChild?' selected-child-block':''}" data-cell-block-path="${pathKey(path)}" data-cell-block-index="${j}"><div class="column-child-toolbar" contenteditable="false"><span>${esc(contextBlockName(item))}</span><button type="button" data-cell-block-drag="${j}" draggable="true" title="Mover">⋮⋮</button><button type="button" data-cell-block-up="${j}" title="Subir">↑</button><button type="button" data-cell-block-down="${j}" title="Bajar">↓</button><button type="button" data-cell-block-copy="${j}" title="Duplicar">⧉</button><button type="button" data-cell-block-delete="${j}" title="Eliminar">×</button></div><div class="column-child-body">${previewInner(item)}</div></div>`;}).join('')}<div class="cell-elements-dropzone compact" data-cell-items-tail="${pathKey(path)}"><span>＋ Soltar otro elemento aquí</span></div></div>`;
  }
  function previewColumns(container,basePath=[]){
    const s=ensureStyle(container),cells=ensureColumns(container),widths=columnWidths(container),family=fontCss(s.fontFamily,'Ubuntu Sans'),size=s.fontSize||'16px',weight=s.fontWeight||'400',line=s.lineHeight||'1.5',color=s.color||'#1f2937',pad=s.padding||'0';
    const cols=widths.map(w=>`${Math.max(0.01,w).toFixed(4)}%`).join(' ');
    return `<div class="preview-columns dynamic-columns" data-columns-path="${basePath.length?pathKey(basePath):'root'}" style="${bgStyle(s)};padding:${pad};color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'};grid-template-columns:${cols}">${cells.map((cell,i)=>{const path=[...basePath,i],cs=ensureStyle(cell),sel=samePath(selectedCellPath,path)?' selected-cell':'',cf=fontCss(cs.fontFamily,s.fontFamily||'Ubuntu Sans'),divider=i<cells.length-1?`<button type="button" class="column-resizer" data-col-resize="${pathKey(path)}" title="Arrastra para cambiar el ancho de las columnas" contenteditable="false"><span></span></button>`:'',vAlign=cs.verticalAlign==='middle'?'center':(cs.verticalAlign==='bottom'?'flex-end':'flex-start'),borderStyle=['solid','dashed','dotted','double','none'].includes(cs.borderStyle)?cs.borderStyle:'solid';return `<div class="email-column${sel}${responsiveCellBgClass(cs)}" data-cell-path="${pathKey(path)}" data-cell-width="${Number(cell.width||0).toFixed(2)}" style="${bgStyle(cs,'transparent')}color:${cs.color||color};padding:${cs.padding||'18px 20px'};margin:${cs.margin||'0px'};border:${cs.borderWidth||'0px'} ${borderStyle} ${cs.borderColor||'#dce5ec'};text-align:${cs.align||'left'};font-family:${cf};font-size:${cs.fontSize||size};font-weight:${cs.fontWeight||weight};line-height:${cs.lineHeight||line};letter-spacing:${cs.letterSpacing||s.letterSpacing||'0px'};border-radius:${cs.radius||'0'};min-height:${cs.minHeight||'0px'};box-sizing:border-box;display:flex;flex-direction:column;justify-content:${vAlign}"><div class="column-width-badge" contenteditable="false">${Math.round(cell.width||0)}%</div><div class="column-tools" contenteditable="false"><button type="button" data-cell-select="${pathKey(path)}" title="Editar columna">⚙</button><button type="button" data-cell-add="${pathKey(path)}" title="Agregar columna al lado">＋</button>${path.length<3?`<button type="button" data-cell-nest="1" data-cell-target="${pathKey(path)}" title="Agregar 1 columna dentro">▯</button><button type="button" data-cell-nest="2" data-cell-target="${pathKey(path)}" title="Agregar 2 columnas dentro">▥</button><button type="button" data-cell-nest="3" data-cell-target="${pathKey(path)}" title="Agregar 3 columnas dentro">▦</button>`:''}${cell.nested?`<button type="button" data-cell-delete-nested="${pathKey(path)}" title="Eliminar estructura interna">⊘</button>`:''}<button type="button" data-cell-delete="${pathKey(path)}" title="Eliminar esta columna">×</button></div><div class="column-content" contenteditable="true" spellcheck="true" data-cell-content="${pathKey(path)}">${cell.content||''}</div>${previewCellItems(cell,path)}${(!cell.nested&&path.length<3)?`<div class="nested-column-adder" contenteditable="false"><span>Agregar columnas dentro</span><div><button type="button" data-cell-nest="1" data-cell-target="${pathKey(path)}" title="Agregar 1 columna dentro de esta columna">＋ 1 COLUMNA</button><button type="button" data-cell-nest="2" data-cell-target="${pathKey(path)}" title="Agregar 2 columnas dentro de esta columna">＋ 2 COLUMNAS</button><button type="button" data-cell-nest="3" data-cell-target="${pathKey(path)}" title="Agregar 3 columnas dentro de esta columna">＋ 3 COLUMNAS</button></div><small>También puedes arrastrar 1/2/3 columnas desde Elementos y soltarlas aquí.</small></div>`:''}${cell.nested?`<div class="nested-columns-wrap"><div class="nested-level-label" contenteditable="false">COLUMNAS DENTRO DE COLUMNA</div>${previewColumns(cell.nested,path)}</div>`:''}${divider}</div>`;}).join('')}</div>`;
  }

  normalizeBlocks(blocks);
  function blockWidthPct(s){const raw=String(s.blockWidth||'100%');const n=parseFloat(raw);return clamp(Number.isFinite(n)?n:100,30,100);}
  function blockMinHeightPx(s){const raw=String((s||{}).minHeight||'');const n=parseFloat(raw);return Number.isFinite(n)&&n>0?clamp(n,40,1200):0;}
  function blockMargin(s){const a=s.blockAlign||'center';if(a==='left')return '0 auto 0 0';if(a==='right')return '0 0 0 auto';return '0 auto';}
  function responsiveCellBgClass(s){
    if(!s?.backgroundImage)return '';
    const mode=['cover','contain','width'].includes(s.backgroundSizeMobile)?s.backgroundSizeMobile:'cover';
    return ` has-responsive-bg bg-mobile-${mode}`;
  }
  function partPlaceholder(){return '';}
  function previewInner(b){
    const s=ensureStyle(b),bg=s.background||'#fff',color=s.color||'#1f2937',pad=s.padding||'12px 0',align=s.align||'left',size=s.fontSize||'16px',radius=s.radius||'0',family=fontCss(s.fontFamily,(b.type==='title'||b.type==='hero'||b.type==='card')?'Exo 2':'Ubuntu Sans'),weight=s.fontWeight||((b.type==='title'||b.type==='hero'||b.type==='card')?'700':'400'),line=s.lineHeight||'1.6';
    if(b.type==='title')return partHidden(b,'content')?partPlaceholder(b,'content','Título'):`<h2 data-builder-part="content" contenteditable="true" spellcheck="true" data-edit="content" style="margin:0;${bgStyle(s)}color:${color};padding:${pad};text-align:${align};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'};border-radius:${radius}">${b.content||''}</h2>`;
    if(b.type==='text')return partHidden(b,'content')?partPlaceholder(b,'content','Texto'):`<div data-builder-part="content" contenteditable="true" spellcheck="true" data-edit="content" style="${bgStyle(s)}color:${color};padding:${pad};text-align:${align};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'};border-radius:${radius}">${b.content||''}</div>`;
    if(b.type==='image'){
      if(partHidden(b,'image'))return '';
      const src=String(b.src||'').trim();
      if(!src)return `<div style="background:${s.background||'#ffffff'};padding:${pad};text-align:${align}"><div data-builder-part="image" class="image-empty-placeholder"><strong>IMAGEN</strong><span>Selecciona este bloque y usa “Subir / reemplazar imagen”.</span></div></div>`;
      return `<div style="${bgStyle(s)}padding:${pad};text-align:${align}"><img data-builder-part="image" src="${esc(src)}" alt="${esc(b.alt||'')}" style="max-width:100%;width:${s.width||'100%'};height:auto;border-radius:${radius}"></div>`;
    }
    if(b.type==='button')return partHidden(b,'button')?'':`<div style="${bgStyle(s)}padding:${pad};text-align:${align}"><span data-builder-part="button" contenteditable="true" spellcheck="true" data-edit="content" style="display:inline-block;background:${s.buttonBg||'#ff5300'};color:${s.buttonColor||'#fff'};padding:${s.buttonPadding||'12px 18px'};border-radius:${radius};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};cursor:text">${esc(b.content||'VER MÁS')}</span></div>`;
    if(b.type==='divider')return partHidden(b,'divider')?partPlaceholder(b,'divider','Separador'):`<div data-builder-part="divider" style="${bgStyle(s)}padding:${pad}"><hr style="border:0;border-top:1px solid ${s.color||'#dce5ec'};margin:0"></div>`;
    if(b.type==='spacer')return partHidden(b,'spacer')?partPlaceholder(b,'spacer','Espacio'):`<div data-builder-part="spacer" style="height:${s.height||'30px'};${bgStyle(s)}"></div>`;
    if(isColumns(b))return previewColumns(b,[]);
    if(b.type==='logo')return partHidden(b,'logo')?'':`<div style="${bgStyle(s)}padding:${pad};text-align:${align}"><img data-builder-part="logo" src="${esc(b.src||'')}" alt="${esc(b.alt||'Logo')}" style="max-width:100%;width:${s.width||'190px'};height:auto"></div>`;
    if(b.type==='quote')return partHidden(b,'content')?'':`<div style="${bgStyle(s)}padding:${pad};text-align:${align}"><div data-builder-part="content" contenteditable="true" spellcheck="true" data-edit="content" style="border-left:4px solid ${s.accent||'#31b4e7'};padding:4px 0 4px 18px;color:${color};font-family:${family};font-size:${size};font-style:italic;font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.content||''}</div></div>`;
    if(b.type==='card'){
      const tf=fontCss(s.textFontFamily,'Ubuntu Sans'),bf=fontCss(s.buttonFontFamily,'Ubuntu Sans');
      const title=partHidden(b,'title')?partPlaceholder(b,'title','Título'):`<h3 data-builder-part="title" contenteditable="true" spellcheck="true" data-edit="title" style="margin:0 0 10px;color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.title||''}</h3>`;
      const text=partHidden(b,'text')?partPlaceholder(b,'text','Texto'):`<div data-builder-part="text" contenteditable="true" spellcheck="true" data-edit="text" style="color:${s.textColor||'#516579'};font-family:${tf};font-size:${s.textSize||'16px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.6'};letter-spacing:${s.textLetterSpacing||'0px'}">${b.text||''}</div>`;
      const button=partHidden(b,'button')?partPlaceholder(b,'button','Botón'):`<span data-builder-part="button" contenteditable="true" spellcheck="true" data-edit="button" style="display:inline-block;margin-top:16px;background:${s.buttonBg||'#0074c7'};color:${s.buttonColor||'#fff'};padding:11px 16px;border-radius:${s.buttonRadius||'4px'};font-family:${bf};font-size:${s.buttonSize||'14px'};font-weight:${s.buttonWeight||'700'};line-height:${s.buttonLineHeight||'1.2'};letter-spacing:${s.buttonLetterSpacing||'0px'}">${esc(b.button||'VER MÁS')}</span>`;
      return `<div style="${bgStyle(s)}padding:${pad}"><div style="border:1px solid ${s.border||'#dce5ec'};background:${s.cardBg||'#f8fbfd'};border-radius:${radius};padding:24px">${title}${text}${button}</div></div>`;
    }
    if(b.type==='footer')return partHidden(b,'content')?partPlaceholder(b,'content','Footer'):`<div data-builder-part="content" contenteditable="true" spellcheck="true" data-edit="content" style="${bgStyle(s)}color:${color};padding:${pad};text-align:${align};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.content||''}</div>`;
    if(b.type==='social'){let l='';[['instagram','Instagram'],['facebook','Facebook'],['linkedin','LinkedIn'],['website','Web']].forEach(([k,n])=>{if(b[k]&&!partHidden(b,k))l+=`<span data-builder-part="${k}">${n}</span>`});return l?`<div style="${bgStyle(s)}color:${s.color||'#0074c7'};padding:${pad};text-align:${align};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line}" class="social-preview">${l}</div>`:'';}
    if(b.type==='html')return `<iframe sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox" scrolling="no" class="html-preview" srcdoc="${esc(b.content||'')}"></iframe>`;
    if(b.type==='iconbox'){
      const title=partHidden(b,'title')?'':`<h3 data-builder-part="title" contenteditable="true" data-edit="title" style="margin:0 0 8px;color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.title||''}</h3>`;
      const text=partHidden(b,'text')?'':`<div data-builder-part="text" contenteditable="true" data-edit="text" style="color:${s.textColor||'#516579'};font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'15px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.55'};letter-spacing:${s.textLetterSpacing||'0px'}">${b.text||''}</div>`;
      const icon=partHidden(b,'icon')?'':`<div data-builder-part="icon" contenteditable="true" data-edit="icon" style="width:52px;height:52px;border-radius:12px;background:${s.iconBg||'#eaf6fd'};color:${s.iconColor||'#0074c7'};display:grid;place-items:center;font-size:${s.iconSize||'26px'};font-weight:800;flex:0 0 auto">${esc(b.icon||'★')}</div>`;
      return `<div style="${bgStyle(s)}padding:${pad}"><div style="display:flex;gap:16px;align-items:flex-start;background:${s.cardBg||'#f8fbfd'};border:1px solid ${s.border||'#dce8f0'};border-radius:${radius};padding:22px">${icon}<div style="flex:1">${title}${text}</div></div></div>`;
    }
    if(b.type==='video'){
      const image=partHidden(b,'image')?'':`<a data-builder-part="image" href="${esc(b.href||'#')}" style="display:block;position:relative;text-decoration:none"><img src="${esc(b.thumbnail||'')}" style="display:block;width:100%;max-width:100%;border-radius:${radius};height:auto"><span style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:64px;height:64px;border-radius:50%;background:${s.playBg||'#ff5300'};color:#fff;display:grid;place-items:center;font-size:27px">&#9654;</span></a>`;
      const title=partHidden(b,'title')?'':`<h3 data-builder-part="title" contenteditable="true" data-edit="title" style="margin:18px 0 8px;color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.title||''}</h3>`;
      const text=partHidden(b,'text')?'':`<div data-builder-part="text" contenteditable="true" data-edit="text" style="color:${s.textColor||'#516579'};font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'15px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.55'};letter-spacing:${s.textLetterSpacing||'0px'}">${b.text||''}</div>`;
      const button=partHidden(b,'button')?'':`<span data-builder-part="button" contenteditable="true" data-edit="button" style="display:inline-block;margin-top:16px;padding:11px 18px;background:${s.buttonBg||'#0074c7'};color:${s.buttonColor||'#fff'};border-radius:${s.buttonRadius||'999px'};font-family:${fontCss(s.buttonFontFamily||'Ubuntu Sans')};font-size:${s.buttonSize||'13px'};font-weight:${s.buttonWeight||'700'};line-height:${s.buttonLineHeight||'1.2'};letter-spacing:${s.buttonLetterSpacing||'0px'}">${esc(b.button||'REPRODUCIR VIDEO')}</span>`;
      return `<div style="${bgStyle(s)}padding:${pad};text-align:${align}">${image}${title}${text}${button}</div>`;
    }
    if(b.type==='product'){
      const image=partHidden(b,'image')?'':`<div style="text-align:center"><img data-builder-part="image" src="${esc(b.src||'')}" style="display:block;width:100%;max-width:100%;border-radius:${radius};height:auto"></div>`;
      const name=partHidden(b,'title')?'':`<h3 data-builder-part="title" contenteditable="true" data-edit="name" style="margin:18px 0 7px;color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.name||''}</h3>`;
      const prices=`${partHidden(b,'price')?'':`<strong data-builder-part="price" contenteditable="true" data-edit="price" style="color:${s.priceColor||'#ff5300'};font-family:${fontCss(s.priceFontFamily||'Exo 2')};font-size:${s.priceSize||'24px'};font-weight:${s.priceWeight||'800'};line-height:${s.priceLineHeight||'1.2'};letter-spacing:${s.priceLetterSpacing||'0px'}">${esc(b.price||'')}</strong>`}${partHidden(b,'oldPrice')?'':` <span data-builder-part="oldPrice" contenteditable="true" data-edit="oldPrice" style="color:#94a3b8;text-decoration:line-through;font-family:${fontCss(s.oldPriceFontFamily||'Ubuntu Sans')};font-size:${s.oldPriceSize||'14px'};font-weight:${s.oldPriceWeight||'400'};line-height:${s.oldPriceLineHeight||'1.2'};letter-spacing:${s.oldPriceLetterSpacing||'0px'}">${esc(b.oldPrice||'')}</span>`}`;
      const text=partHidden(b,'text')?'':`<div data-builder-part="text" contenteditable="true" data-edit="text" style="margin-top:10px;color:${s.textColor||'#516579'};font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'15px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.55'};letter-spacing:${s.textLetterSpacing||'0px'}">${b.text||''}</div>`;
      const button=partHidden(b,'button')?'':`<span data-builder-part="button" contenteditable="true" data-edit="button" style="display:inline-block;margin-top:16px;padding:12px 19px;background:${s.buttonBg||'#0074c7'};color:${s.buttonColor||'#fff'};border-radius:${s.buttonRadius||'999px'};font-family:${fontCss(s.buttonFontFamily||'Ubuntu Sans')};font-size:${s.buttonSize||'13px'};font-weight:${s.buttonWeight||'700'};line-height:${s.buttonLineHeight||'1.2'};letter-spacing:${s.buttonLetterSpacing||'0px'}">${esc(b.button||'COMPRAR')}</span>`;
      return `<div style="${bgStyle(s)}padding:${pad}"><div style="background:${s.cardBg||'#fff'};border:1px solid ${s.border||'#dce8f0'};border-radius:${radius};padding:18px">${image}${name}<div>${prices}</div>${text}${button}</div></div>`;
    }
    if(b.type==='testimonial'){
      const avatar=partHidden(b,'avatar')?'':`<img data-builder-part="avatar" src="${esc(b.avatar||'')}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;display:block">`;
      const quote=partHidden(b,'text')?'':`<div data-builder-part="text" contenteditable="true" data-edit="quote" style="font-family:${family};font-size:${size};font-style:italic;font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'};color:${color};margin-bottom:15px">${b.quote||''}</div>`;
      const stars=partHidden(b,'stars')?'':`<div data-builder-part="stars" style="color:${s.accent||'#f6bd00'};font-size:18px;letter-spacing:2px">${'★'.repeat(clamp(Number(b.stars)||0,0,5))}${'☆'.repeat(5-clamp(Number(b.stars)||0,0,5))}</div>`;
      const author=partHidden(b,'author')?'':`<strong data-builder-part="author" contenteditable="true" data-edit="author" style="display:block;color:#073b68;font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'14px'};font-weight:${s.textWeight||'700'};line-height:${s.textLineHeight||'1.3'};letter-spacing:${s.textLetterSpacing||'0px'}">${b.author||''}</strong>`;
      const role=partHidden(b,'role')?'':`<span data-builder-part="role" contenteditable="true" data-edit="role" style="color:#7b8e9d;font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'12px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.4'};letter-spacing:${s.textLetterSpacing||'0px'}">${b.role||''}</span>`;
      return `<div style="${bgStyle(s)}padding:${pad}"><div style="background:${s.cardBg||'#f8fbfd'};border:1px solid ${s.border||'#dce8f0'};border-radius:${radius};padding:24px">${stars}${quote}<div style="display:flex;gap:12px;align-items:center">${avatar}<div>${author}${role}</div></div></div></div>`;
    }
    if(b.type==='stats'){
      let cards='';for(let i=1;i<=3;i++){if(partHidden(b,'stat'+i))continue;cards+=`<div data-builder-part="stat${i}" style="flex:1;min-width:120px;padding:18px;border:1px solid ${s.border||'#dce8f0'};background:${s.cardBg||'#f8fbfd'};border-radius:${radius};text-align:center"><strong contenteditable="true" data-edit="value${i}" style="display:block;color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${esc(b['value'+i]||'')}</strong><span contenteditable="true" data-edit="label${i}" style="display:block;margin-top:5px;color:${s.textColor||'#6b7f8f'};font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'12px'};font-weight:${s.textWeight||'600'};line-height:${s.textLineHeight||'1.3'};letter-spacing:${s.textLetterSpacing||'0px'}">${esc(b['label'+i]||'')}</span></div>`;}
      return `<div style="${bgStyle(s)}padding:${pad}"><div style="display:flex;gap:12px;flex-wrap:wrap">${cards}</div></div>`;
    }
    if(b.type==='iconlist'){
      const rows=String(b.items||'').split(/\n+/).filter(Boolean).map((t,i)=>`<div style="display:flex;gap:10px;align-items:flex-start;margin:${i?'10px':'0'} 0 0"><span style="width:25px;height:25px;border-radius:50%;background:${s.iconBg||'#eef9e7'};color:${s.iconColor||'#73bc25'};display:grid;place-items:center;font-weight:800;flex:0 0 auto">${esc(b.icon||'✓')}</span><span>${esc(t)}</span></div>`).join('');
      return partHidden(b,'items')?'':`<div data-builder-part="items" style="${bgStyle(s)}padding:${pad};color:${color};text-align:${align};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${rows}</div>`;
    }
    if(b.type==='banner'){
      const op=clamp(Number(s.overlayOpacity??s.overlay??.58),0,1),bg=b.src?`background-color:${s.background||'#00365f'};${backgroundImageCss(b.src,{...s,overlayOpacity:op},op,s.overlayColor||'#002340')}`:bgStyle(s,'#00365f');
      const title=partHidden(b,'title')?'':`<h2 data-builder-part="title" contenteditable="true" data-edit="title" style="margin:0 0 10px;color:${s.color||'#fff'};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.title||''}</h2>`;
      const text=partHidden(b,'text')?'':`<div data-builder-part="text" contenteditable="true" data-edit="text" style="color:${s.textColor||'#e6f2f8'};font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'16px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.55'};letter-spacing:${s.textLetterSpacing||'0px'}">${b.text||''}</div>`;
      const button=partHidden(b,'button')?'':`<span data-builder-part="button" contenteditable="true" data-edit="button" style="display:inline-block;margin-top:18px;padding:12px 20px;background:${s.buttonBg||'#ff5300'};color:${s.buttonColor||'#fff'};border-radius:${s.buttonRadius||'999px'};font-family:${fontCss(s.buttonFontFamily||'Ubuntu Sans')};font-size:${s.buttonSize||'13px'};font-weight:${s.buttonWeight||'700'};line-height:${s.buttonLineHeight||'1.2'};letter-spacing:${s.buttonLetterSpacing||'0px'}">${esc(b.button||'CONOCER MÁS')}</span>`;
      return `<div style="${bg}padding:${pad};text-align:${align};border-radius:${radius}">${title}${text}${button}</div>`;
    }
    if(b.type==='pricing'){
      const badge=partHidden(b,'badge')?'':`<span data-builder-part="badge" contenteditable="true" data-edit="badge" style="display:inline-block;background:${s.badgeBg||'#ff5300'};color:${s.badgeColor||'#fff'};padding:6px 10px;border-radius:999px;font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'10px'};font-weight:${s.textWeight||'800'};line-height:${s.textLineHeight||'1'};letter-spacing:${s.textLetterSpacing||'.5px'}">${esc(b.badge||'')}</span>`;
      const plan=partHidden(b,'plan')?'':`<h3 data-builder-part="plan" contenteditable="true" data-edit="plan" style="margin:14px 0 8px;color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.plan||''}</h3>`;
      const price=partHidden(b,'price')?'':`<div><strong data-builder-part="price" contenteditable="true" data-edit="price" style="color:${s.priceColor||'#0074c7'};font-family:${fontCss(s.priceFontFamily||'Exo 2')};font-size:${s.priceSize||'34px'};font-weight:${s.priceWeight||'800'};line-height:${s.priceLineHeight||'1.1'};letter-spacing:${s.priceLetterSpacing||'0px'}">${esc(b.price||'')}</strong>${partHidden(b,'period')?'':`<span data-builder-part="period" contenteditable="true" data-edit="period" style="color:#7b8e9d;font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'13px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.3'};letter-spacing:${s.textLetterSpacing||'0px'}"> ${esc(b.period||'')}</span>`}</div>`;
      const features=partHidden(b,'features')?'':`<div data-builder-part="features" style="margin:18px 0;text-align:left;color:${s.textColor||'#516579'};font-family:${fontCss(s.textFontFamily||'Ubuntu Sans')};font-size:${s.textSize||'15px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.5'};letter-spacing:${s.textLetterSpacing||'0px'}">${String(b.features||'').split(/\n+/).filter(Boolean).map(x=>`<div style="margin:8px 0">✓ ${esc(x)}</div>`).join('')}</div>`;
      const button=partHidden(b,'button')?'':`<span data-builder-part="button" contenteditable="true" data-edit="button" style="display:inline-block;padding:12px 20px;background:${s.buttonBg||'#0074c7'};color:${s.buttonColor||'#fff'};border-radius:${s.buttonRadius||'999px'};font-family:${fontCss(s.buttonFontFamily||'Ubuntu Sans')};font-size:${s.buttonSize||'13px'};font-weight:${s.buttonWeight||'700'};line-height:${s.buttonLineHeight||'1.2'};letter-spacing:${s.buttonLetterSpacing||'0px'}">${esc(b.button||'COMENZAR')}</span>`;
      return `<div style="${bgStyle(s)}padding:${pad};text-align:${align}"><div style="background:${s.cardBg||'#fff'};border:1px solid ${s.border||'#bcd9e9'};border-radius:${radius};padding:26px">${badge}${plan}${price}${features}${button}</div></div>`;
    }
    if(b.type==='qr'){
      const qr=partHidden(b,'qr')?'':`<img data-builder-part="qr" src="${qrUrl(b.data,240)}" style="width:${s.qrSize||'190px'};max-width:100%;height:auto;background:${s.qrBg||'#fff'};padding:8px;border-radius:${radius}">`;
      const label=partHidden(b,'text')?'':`<div data-builder-part="text" contenteditable="true" data-edit="label" style="margin-top:10px;color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.label||''}</div>`;
      return `<div style="${bgStyle(s)}padding:${pad};text-align:${align}">${qr}${label}</div>`;
    }
    if(b.type==='header'){
      const logo=partHidden(b,'logo')?'':`<img data-builder-part="logo" src="${esc(b.src||'')}" alt="${esc(b.alt||'Logo')}" style="max-width:100%;width:${s.logoWidth||'170px'};height:auto;display:block">`;
      let menu='';for(let i=1;i<=3;i++){if(!partHidden(b,'menu'+i)&&b['menu'+i])menu+=`<span data-builder-part="menu${i}" contenteditable="true" data-edit="menu${i}" style="margin:0 7px;color:${color};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${esc(b['menu'+i])}</span>`;}
      const button=partHidden(b,'button')?'':`<span data-builder-part="button" contenteditable="true" data-edit="button" style="display:inline-block;padding:10px 15px;background:${s.buttonBg||'#ff5300'};color:${s.buttonColor||'#fff'};border-radius:${s.buttonRadius||'999px'};font-family:${fontCss(s.buttonFontFamily||'Ubuntu Sans')};font-size:${s.buttonSize||'11px'};font-weight:${s.buttonWeight||'700'};line-height:${s.buttonLineHeight||'1.2'}">${esc(b.button||'HABLEMOS')}</span>`;
      return `<div style="${bgStyle(s)}padding:${pad};border-bottom:1px solid ${s.border||'#e0e8ee'}"><div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">${logo}<div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">${menu}${button}</div></div></div>`;
    }
    if(b.type==='hero'){
      const bgImage=b.src?backgroundImageCss(b.src,s,.72,'#002f57'):(s.backgroundImage?backgroundImageCss(s.backgroundImage,s,0,'#000000'):'');
      const tf=fontCss(s.textFontFamily,'Ubuntu Sans'),bf=fontCss(s.buttonFontFamily,'Ubuntu Sans');
      const title=partHidden(b,'title')?partPlaceholder(b,'title','Título'):`<h1 data-builder-part="title" contenteditable="true" spellcheck="true" data-edit="title" style="margin:0 0 12px;color:${s.color||'#fff'};font-family:${family};font-size:${size};font-weight:${weight};line-height:${line};letter-spacing:${s.letterSpacing||'0px'}">${b.title||''}</h1>`;
      const text=partHidden(b,'text')?partPlaceholder(b,'text','Texto'):`<div data-builder-part="text" contenteditable="true" spellcheck="true" data-edit="text" style="color:${s.textColor||'#fff'};font-family:${tf};font-size:${s.textSize||'16px'};font-weight:${s.textWeight||'400'};line-height:${s.textLineHeight||'1.6'};letter-spacing:${s.textLetterSpacing||'0px'}">${b.text||''}</div>`;
      const button=partHidden(b,'button')?partPlaceholder(b,'button','Botón'):`<span data-builder-part="button" contenteditable="true" spellcheck="true" data-edit="button" style="display:inline-block;margin-top:18px;background:${s.buttonBg||'#ff5300'};color:${s.buttonColor||'#fff'};padding:12px 18px;border-radius:${s.buttonRadius||'4px'};font-family:${bf};font-size:${s.buttonSize||'14px'};font-weight:${s.buttonWeight||'700'};line-height:${s.buttonLineHeight||'1.2'};letter-spacing:${s.buttonLetterSpacing||'0px'}">${esc(b.button||'VER MÁS')}</span>`;
      return `<div style="${bgImage}background-color:${bg};padding:${pad};text-align:${align};border-radius:${radius}">${title}${text}${button}</div>`;
    }
    return '';
  }
  function resizeHtmlPreviewFrame(frame){
    if(!frame)return;
    try{
      const doc=frame.contentDocument||frame.contentWindow?.document;
      if(!doc)return;
      const html=doc.documentElement,body=doc.body;
      if(html){html.style.setProperty('overflow','hidden','important');html.style.setProperty('overflow-y','hidden','important');html.style.setProperty('height','auto','important');}
      if(body){body.style.setProperty('overflow','hidden','important');body.style.setProperty('overflow-y','hidden','important');body.style.setProperty('height','auto','important');}
      const previous=frame.style.height;
      frame.style.height='1px';
      const h=Math.max(
        180,
        body?.scrollHeight||0,
        body?.offsetHeight||0,
        html?.scrollHeight||0,
        html?.offsetHeight||0
      );
      frame.style.height=Math.ceil(h)+'px';
      frame.dataset.autoHeight=String(Math.ceil(h));
      if(!Number.isFinite(h)||h<=1)frame.style.height=previous||'180px';
    }catch(err){
      frame.style.minHeight='180px';
    }
  }
  function bindHtmlPreviewFrames(scope=page){
    scope.querySelectorAll('iframe.html-preview').forEach(frame=>{
      if(frame.dataset.autoHeightBound==='1')return;
      frame.dataset.autoHeightBound='1';
      frame.setAttribute('scrolling','no');
      frame.style.overflow='hidden';
      const refresh=()=>resizeHtmlPreviewFrame(frame);
      frame.addEventListener('load',()=>{
        refresh();
        [80,250,700,1400].forEach(ms=>window.setTimeout(refresh,ms));
        try{
          const doc=frame.contentDocument||frame.contentWindow?.document;
          doc?.querySelectorAll('img').forEach(img=>{if(!img.complete)img.addEventListener('load',refresh,{once:true});});
        }catch(err){}
      });
    });
  }
  function applyBlockLayout(w,b){const s=ensureStyle(b),pct=blockWidthPct(s),mh=blockMinHeightPx(s);w.style.width=pct+'%';w.style.maxWidth='100%';w.style.margin=blockMargin(s);if(mh>0){w.style.setProperty('--block-min-height',Math.round(mh)+'px');w.classList.add('has-custom-height');}else{w.style.removeProperty('--block-min-height');w.classList.remove('has-custom-height');}}
  function clearDropClasses(){page.querySelectorAll('.block.drag-before,.block.drag-after').forEach(el=>el.classList.remove('drag-before','drag-after'));}
  function insertPalette(type,index){if(!defaults[type])return;snapshot();const pos=clamp(index,0,blocks.length);blocks.splice(pos,0,clone(defaults[type]));selected=pos;selectedCellPath=null;normalizeBlockTree(blocks[pos]);render();renderInspector();sync();}
  function reorderBlock(from,to){if(from<0||from>=blocks.length)return;to=clamp(to,0,blocks.length);snapshot();const item=blocks.splice(from,1)[0];if(from<to)to--;to=clamp(to,0,blocks.length);blocks.splice(to,0,item);selected=to;selectedCellPath=null;render();renderInspector();sync();}
  function bindColumnResizers(block,blockIndex,scope){
    scope.querySelectorAll('[data-col-resize]').forEach(handle=>handle.addEventListener('pointerdown',ev=>{
      ev.preventDefault();ev.stopPropagation();
      const path=handle.dataset.colResize.split('.').map(Number),container=getContainerForCellPath(block,path);if(!container)return;
      const cells=ensureColumns(container),idx=Number(path[path.length-1]);if(idx<0||idx>=cells.length-1)return;
      normalizeCellWidths(container);const left=cells[idx],right=cells[idx+1],pairTotal=Number(left.width)+Number(right.width),startLeft=Number(left.width),grid=handle.closest('.dynamic-columns');if(!grid)return;
      const gridW=Math.max(1,grid.getBoundingClientRect().width),minPct=Math.min(18,Math.max(8,pairTotal*.18));
      snapshot();selected=blockIndex;selectedCellPath=path;document.body.classList.add('column-resizing');handle.classList.add('is-dragging');grid.classList.add('is-resizing');handle.setPointerCapture?.(ev.pointerId);
      const move=e=>{const delta=(e.clientX-ev.clientX)/gridW*100,nextLeft=clamp(startLeft+delta,minPct,pairTotal-minPct);left.width=nextLeft;right.width=pairTotal-nextLeft;const widths=columnWidths(container);grid.style.gridTemplateColumns=widths.map(w=>`${w.toFixed(4)}%`).join(' ');[...grid.children].filter(el=>el.classList?.contains('email-column')).forEach((el,j)=>{el.dataset.cellWidth=Number(cells[j]?.width||0).toFixed(2);const badge=el.querySelector(':scope > .column-width-badge');if(badge)badge.textContent=Math.round(cells[j]?.width||0)+'%';});sync(false);};
      const up=e=>{handle.releasePointerCapture?.(e.pointerId);handle.removeEventListener('pointermove',move);handle.removeEventListener('pointerup',up);handle.removeEventListener('pointercancel',up);document.body.classList.remove('column-resizing');handle.classList.remove('is-dragging');grid.classList.remove('is-resizing');sync();renderInspector();};
      handle.addEventListener('pointermove',move);handle.addEventListener('pointerup',up);handle.addEventListener('pointercancel',up);
    }));
  }
  function bindImageDiagnostics(scope){
    scope.querySelectorAll('img[data-builder-part]').forEach(img=>{
      const block=img.closest('.block');
      if(!block)return;
      let status=block.querySelector(':scope > .builder-image-status');
      const clear=()=>{
        block.classList.remove('has-image-error');
        if(status){status.remove();status=null;}
      };
      const broken=()=>{
        block.classList.add('has-image-error');
        if(!status){
          status=document.createElement('div');
          status.className='builder-image-status';
          const src=img.getAttribute('src')||'';
          status.innerHTML='<strong>Imagen no disponible</strong><span>La URL no respondió como imagen. Vuelve a subirla desde Propiedades.</span><code>'+esc(src)+'</code>';
          block.appendChild(status);
        }
      };
      img.addEventListener('load',clear,{once:true});
      img.addEventListener('error',broken,{once:true});
      if(img.complete){
        if(img.naturalWidth>0)clear(); else broken();
      }
    });
  }
  function render(){
    page.innerHTML='';
    blocks.forEach((b,i)=>{
      const s=ensureStyle(b),w=document.createElement('div');w.className='block'+(i===selected?' selected':'');w.dataset.i=i;applyBlockLayout(w,b);
      w.innerHTML=`<div class="block-label">${b.type.toUpperCase()}</div><div class="block-tools"><button type="button" data-drag draggable="true" title="Arrastrar">⋮⋮</button><button type="button" data-up title="Subir">↑</button><button type="button" data-down title="Bajar">↓</button><button type="button" data-copy title="Duplicar">⧉</button><button type="button" data-del title="Eliminar selección">×</button></div>${previewInner(b)}<button type="button" class="block-height-resize" data-height-resize title="Arrastra hacia abajo para cambiar la altura"><span></span></button><button type="button" class="block-resize" data-resize title="Arrastra la esquina para cambiar ancho y alto"></button>`;
      w.querySelectorAll('[data-cell-content]').forEach(decorateColumnContent);
      if(activeSelectionContext?.kind==='part'&&activeSelectionContext.blockIndex===i)w.querySelector(`[data-builder-part="${activeSelectionContext.part}"]`)?.classList.add('builder-part-selected');
      w.addEventListener('click',ev=>{if(ev.target.closest('.block-tools,.block-resize,.block-height-resize,.column-tools,.column-inner-tools,.column-child-toolbar'))return;const child=ev.target.closest('.column-child-block[data-cell-block-index]');if(child){const path=child.dataset.cellBlockPath.split('.').map(Number),idx=Number(child.dataset.cellBlockIndex),item=getCellItem(b,path,idx);selectContext({kind:'cellblock',blockIndex:i,path,itemIndex:idx,item});page.querySelectorAll('.block').forEach((el,n)=>el.classList.toggle('selected',n===i));w.querySelectorAll('.column-child-block').forEach(el=>el.classList.toggle('selected-child-block',el===child));renderInspector();return;}const inner=ev.target.closest('.column-inner-wrap');if(inner){const content=inner.closest('[data-cell-content]');if(content){const ctx={kind:'inner',blockIndex:i,path:content.dataset.cellContent.split('.').map(Number),index:Number(inner.dataset.innerIndex)};selectContext(ctx);page.querySelectorAll('.block').forEach((el,idx)=>el.classList.toggle('selected',idx===i));renderInspector();return;}}const cellEl=ev.target.closest('.email-column');if(cellEl){const ctx={kind:'cell',blockIndex:i,path:cellEl.dataset.cellPath.split('.').map(Number),cell:getCellByPath(b,cellEl.dataset.cellPath.split('.').map(Number))};selectContext(ctx);page.querySelectorAll('.block').forEach((el,idx)=>el.classList.toggle('selected',idx===i));page.querySelectorAll('.email-column').forEach(el=>el.classList.toggle('selected-cell',el.dataset.cellPath===pathKey(selectedCellPath)));renderInspector();return;}const partEl=ev.target.closest('[data-builder-part]');if(partEl){selectContext({kind:'part',blockIndex:i,part:partEl.dataset.builderPart});w.querySelectorAll('[data-builder-part]').forEach(el=>el.classList.toggle('builder-part-selected',el===partEl));page.querySelectorAll('.block').forEach((el,idx)=>el.classList.toggle('selected',idx===i));renderInspector();return;}selectContext({kind:'block',blockIndex:i});page.querySelectorAll('.block').forEach((el,idx)=>el.classList.toggle('selected',idx===i));w.querySelectorAll('[data-builder-part]').forEach(el=>el.classList.remove('builder-part-selected'));renderInspector();});
      w.querySelectorAll('[contenteditable="true"]').forEach(el=>{
        el.addEventListener('focus',()=>{snapshot();el.dataset.editing='1';});
        el.addEventListener('blur',()=>{delete el.dataset.editing;});
        el.addEventListener('keydown',ev=>{if(!(ev.ctrlKey||ev.metaKey))ev.stopPropagation();});
        const targetModel=()=>{const cw=el.closest('.column-child-block[data-cell-block-index]');if(!cw)return b;const path=cw.dataset.cellBlockPath.split('.').map(Number);return getCellItem(b,path,Number(cw.dataset.cellBlockIndex))||b;};
        el.addEventListener('input',()=>{const key=el.dataset.edit;if(!key)return;const target=targetModel();target[key]=el.innerHTML;sync();if(target===b)updateInspectorContentField(b.type,key,el.innerHTML);});
        el.addEventListener('paste',()=>setTimeout(()=>{const key=el.dataset.edit;if(key){const target=targetModel();target[key]=el.innerHTML;sync();if(target===b)updateInspectorContentField(b.type,key,el.innerHTML);}},0));
      });
      w.querySelectorAll('[data-cell-content]').forEach(el=>{
        el.addEventListener('focus',()=>{snapshot();selected=i;selectedCellPath=el.dataset.cellContent.split('.').map(Number);activeSelectionContext={kind:'cell',blockIndex:i,path:[...selectedCellPath],cell:getCellByPath(b,selectedCellPath)};renderInspector();});
        el.addEventListener('keydown',ev=>{if(!(ev.ctrlKey||ev.metaKey))ev.stopPropagation();});
        el.addEventListener('input',()=>{if(suppressColumnContentInput)return;const path=el.dataset.cellContent.split('.').map(Number),cell=getCellByPath(b,path);if(cell){const cleaned=cleanColumnContentHtml(el);cell.content=cleaned;sync();const ta=document.getElementById('cellContent');if(ta&&samePath(selectedCellPath,path)&&document.activeElement!==ta){const tmp=document.createElement('div');tmp.innerHTML=cleaned;ta.value=tmp.innerText;}}});
      });
      w.querySelectorAll('[data-cell-select]').forEach(btn=>btn.addEventListener('click',ev=>{ev.stopPropagation();selected=i;selectedCellPath=btn.dataset.cellSelect.split('.').map(Number);activeSelectionContext={kind:'cell',blockIndex:i,path:[...selectedCellPath],cell:getCellByPath(b,selectedCellPath)};page.querySelectorAll('.email-column').forEach(el=>el.classList.toggle('selected-cell',el.dataset.cellPath===pathKey(selectedCellPath)));renderInspector();}));
      w.querySelectorAll('[data-cell-delete]').forEach(btn=>btn.addEventListener('click',ev=>{ev.stopPropagation();selected=i;deleteCellAtPath(b,btn.dataset.cellDelete.split('.').map(Number));}));
      w.querySelectorAll('[data-cell-add]').forEach(btn=>btn.addEventListener('click',ev=>{ev.stopPropagation();selected=i;addSiblingCell(b,btn.dataset.cellAdd.split('.').map(Number),true);}));
      w.querySelectorAll('[data-cell-nest]').forEach(btn=>btn.addEventListener('click',ev=>{ev.stopPropagation();selected=i;addNestedAtPath(b,btn.dataset.cellTarget.split('.').map(Number),Number(btn.dataset.cellNest));}));
      w.querySelectorAll('[data-cell-delete-nested]').forEach(btn=>btn.addEventListener('click',ev=>{ev.stopPropagation();selected=i;deleteNestedAtPath(b,btn.dataset.cellDeleteNested.split('.').map(Number));}));
      w.querySelectorAll('[data-inner-delete]').forEach(btn=>{btn.addEventListener('pointerdown',ev=>{ev.preventDefault();ev.stopPropagation();});btn.addEventListener('click',ev=>{ev.preventDefault();ev.stopPropagation();selected=i;deleteInnerElementAtPath(b,btn.dataset.innerDelete.split('.').map(Number),Number(btn.dataset.innerIndex));});});
      w.querySelectorAll('.column-child-block[data-cell-block-index]').forEach(child=>{
        const path=child.dataset.cellBlockPath.split('.').map(Number),idx=Number(child.dataset.cellBlockIndex);
        child.querySelector('[data-cell-block-delete]')?.addEventListener('click',ev=>{ev.stopPropagation();selected=i;deleteCellBlockAt(b,path,idx);});
        child.querySelector('[data-cell-block-copy]')?.addEventListener('click',ev=>{ev.stopPropagation();selected=i;duplicateCellBlockAt(b,path,idx);});
        child.querySelector('[data-cell-block-up]')?.addEventListener('click',ev=>{ev.stopPropagation();selected=i;moveCellBlockAt(b,path,idx,-1);});
        child.querySelector('[data-cell-block-down]')?.addEventListener('click',ev=>{ev.stopPropagation();selected=i;moveCellBlockAt(b,path,idx,1);});
        const dh=child.querySelector('[data-cell-block-drag]');dh?.addEventListener('dragstart',ev=>{activeDrag={kind:'cellblock',blockIndex:i,path:[...path],itemIndex:idx};ev.dataTransfer.effectAllowed='move';ev.dataTransfer.setData('application/x-emailsend-cellblock',JSON.stringify({blockIndex:i,path,itemIndex:idx}));child.classList.add('dragging');});dh?.addEventListener('dragend',()=>{activeDrag=null;child.classList.remove('dragging');w.querySelectorAll('.cell-element-drop-before,.cell-element-drop-after').forEach(x=>x.classList.remove('cell-element-drop-before','cell-element-drop-after'));});
        child.addEventListener('dragover',ev=>{if(!ev.dataTransfer.types.includes('application/x-emailsend-cellblock'))return;ev.preventDefault();ev.stopPropagation();const r=child.getBoundingClientRect(),after=ev.clientY>r.top+r.height/2;child.classList.toggle('cell-element-drop-before',!after);child.classList.toggle('cell-element-drop-after',after);});
        child.addEventListener('drop',ev=>{if(!ev.dataTransfer.types.includes('application/x-emailsend-cellblock'))return;ev.preventDefault();ev.stopPropagation();let src;try{src=JSON.parse(ev.dataTransfer.getData('application/x-emailsend-cellblock')||'{}')}catch(e){return}const srcBlock=blocks[Number(src.blockIndex)],srcCell=getCellByPath(srcBlock,src.path||[]),srcItems=cellItems(srcCell),from=Number(src.itemIndex);if(!srcCell||from<0||from>=srcItems.length)return;const targetCell=getCellByPath(b,path),targetItems=cellItems(targetCell),r=child.getBoundingClientRect(),after=ev.clientY>r.top+r.height/2;snapshot();const moved=srcItems.splice(from,1)[0];let at=idx+(after?1:0);if(srcBlock===b&&samePath(src.path,path)&&from<at)at--;at=clamp(at,0,targetItems.length);targetItems.splice(at,0,moved);selected=i;selectedCellPath=[...path];activeSelectionContext={kind:'cellblock',blockIndex:i,path:[...path],itemIndex:at,item:moved};render();renderInspector();sync();});
      });
      w.querySelectorAll('.email-column[data-cell-path]').forEach(cellEl=>{
        cellEl.addEventListener('dragover',ev=>{const movingCellBlock=ev.dataTransfer.types.includes('application/x-emailsend-cellblock');const add=ev.dataTransfer.types.includes('application/x-emailsend-add');const path=cellEl.dataset.cellPath.split('.').map(Number),targetCell=getCellByPath(b,path);if(!targetCell)return;if(movingCellBlock){ev.preventDefault();ev.stopPropagation();cellEl.classList.add('column-element-drop-ready');ev.dataTransfer.dropEffect='move';return;}if(!add)return;const type=activeDrag?.type;if(isColumnStructureType(type)&&(!targetCell.nested&&path.length<3)){ev.preventDefault();ev.stopPropagation();cellEl.classList.add('column-drop-ready');ev.dataTransfer.dropEffect='copy';return;}if(defaults[type]&&!isColumnStructureType(type)){ev.preventDefault();ev.stopPropagation();cellEl.classList.add('column-element-drop-ready');ev.dataTransfer.dropEffect='copy';}});
        cellEl.addEventListener('dragleave',ev=>{if(!cellEl.contains(ev.relatedTarget)){cellEl.classList.remove('column-drop-ready');cellEl.classList.remove('column-element-drop-ready');}});
        cellEl.addEventListener('drop',ev=>{const path=cellEl.dataset.cellPath.split('.').map(Number),targetCell=getCellByPath(b,path);if(!targetCell)return;if(ev.dataTransfer.types.includes('application/x-emailsend-cellblock')){ev.preventDefault();ev.stopPropagation();cellEl.classList.remove('column-element-drop-ready');let src;try{src=JSON.parse(ev.dataTransfer.getData('application/x-emailsend-cellblock')||'{}')}catch(e){return}const srcBlock=blocks[Number(src.blockIndex)],srcCell=getCellByPath(srcBlock,src.path||[]),srcItems=cellItems(srcCell),from=Number(src.itemIndex);if(!srcCell||from<0||from>=srcItems.length)return;snapshot();const moved=srcItems.splice(from,1)[0],targetItems=cellItems(targetCell);targetItems.push(moved);selected=i;selectedCellPath=[...path];activeSelectionContext={kind:'cellblock',blockIndex:i,path:[...path],itemIndex:targetItems.length-1,item:moved};render();renderInspector();sync();return;}const type=ev.dataTransfer.getData('application/x-emailsend-add');if(!type)return;if(isColumnStructureType(type)){if(targetCell.nested||path.length>=3)return;ev.preventDefault();ev.stopPropagation();cellEl.classList.remove('column-drop-ready');selected=i;addNestedAtPath(b,path,type==='columns3'?3:(type==='columns1'?1:2));return;}if(defaults[type]){ev.preventDefault();ev.stopPropagation();cellEl.classList.remove('column-element-drop-ready');selected=i;addBlockToCell(b,path,type);}});
      });
      if(isColumns(b))bindColumnResizers(b,i,w);
      w.querySelector('[data-up]')?.addEventListener('click',()=>move(i,-1));
      w.querySelector('[data-down]')?.addEventListener('click',()=>move(i,1));
      w.querySelector('[data-copy]')?.addEventListener('click',()=>{snapshot();blocks.splice(i+1,0,clone(b));selected=i+1;selectedCellPath=null;render();renderInspector();sync();});
      w.querySelector('[data-del]')?.addEventListener('click',()=>{if(activeSelectionContext&&activeSelectionContext.blockIndex===i)return deleteActiveSelection();activeSelectionContext={kind:'block',blockIndex:i};deleteBlockAt(i);});
      const drag=w.querySelector('[data-drag]');
      drag?.addEventListener('dragstart',ev=>{activeDrag={kind:'block',index:i};ev.dataTransfer.effectAllowed='move';ev.dataTransfer.setData('application/x-emailsend-block',String(i));w.classList.add('dragging');});
      drag?.addEventListener('dragend',()=>{activeDrag=null;w.classList.remove('dragging');clearDropClasses();});
      w.addEventListener('dragover',ev=>{if(!ev.dataTransfer.types.includes('application/x-emailsend-block')&&!ev.dataTransfer.types.includes('application/x-emailsend-add'))return;ev.preventDefault();clearDropClasses();const r=w.getBoundingClientRect(),after=ev.clientY>r.top+r.height/2;w.classList.add(after?'drag-after':'drag-before');ev.dataTransfer.dropEffect=activeDrag?.kind==='palette'?'copy':'move';});
      w.addEventListener('drop',ev=>{ev.preventDefault();ev.stopPropagation();const r=w.getBoundingClientRect(),after=ev.clientY>r.top+r.height/2,target=i+(after?1:0),add=ev.dataTransfer.getData('application/x-emailsend-add'),from=Number(ev.dataTransfer.getData('application/x-emailsend-block'));clearDropClasses();if(add)insertPalette(add,target);else if(Number.isFinite(from))reorderBlock(from,target);});
      const resize=w.querySelector('[data-resize]');
      resize?.addEventListener('pointerdown',ev=>{ev.preventDefault();ev.stopPropagation();selected=i;renderInspector();snapshot();const startX=ev.clientX,startY=ev.clientY,startW=blockWidthPct(s),startH=blockMinHeightPx(s)||Math.round(w.getBoundingClientRect().height),pageW=Math.max(1,page.getBoundingClientRect().width);resize.setPointerCapture?.(ev.pointerId);document.body.classList.add('block-resizing');const moveEv=e=>{const nextW=clamp(startW+((e.clientX-startX)/pageW*100),30,100),nextH=clamp(startH+(e.clientY-startY),40,1200);s.blockWidth=Math.round(nextW)+'%';s.minHeight=Math.round(nextH)+'px';applyBlockLayout(w,b);sync(false);const rg=document.getElementById('pBlockWidth'),val=document.getElementById('pBlockWidthValue'),rh=document.getElementById('pBlockHeight'),vh=document.getElementById('pBlockHeightValue');if(rg)rg.value=Math.round(nextW);if(val)val.textContent=Math.round(nextW)+'%';if(rh)rh.value=Math.round(nextH);if(vh)vh.textContent=Math.round(nextH)+' px';};const upEv=e=>{document.body.classList.remove('block-resizing');resize.releasePointerCapture?.(e.pointerId);resize.removeEventListener('pointermove',moveEv);resize.removeEventListener('pointerup',upEv);resize.removeEventListener('pointercancel',upEv);sync();};resize.addEventListener('pointermove',moveEv);resize.addEventListener('pointerup',upEv);resize.addEventListener('pointercancel',upEv);});
      const heightResize=w.querySelector('[data-height-resize]');
      heightResize?.addEventListener('pointerdown',ev=>{ev.preventDefault();ev.stopPropagation();selected=i;renderInspector();snapshot();const startY=ev.clientY,startH=blockMinHeightPx(s)||Math.round(w.getBoundingClientRect().height);heightResize.setPointerCapture?.(ev.pointerId);heightResize.classList.add('is-dragging');document.body.classList.add('block-height-resizing');const moveEv=e=>{const nextH=clamp(startH+(e.clientY-startY),40,1200);s.minHeight=Math.round(nextH)+'px';applyBlockLayout(w,b);sync(false);const rh=document.getElementById('pBlockHeight'),vh=document.getElementById('pBlockHeightValue');if(rh)rh.value=Math.round(nextH);if(vh)vh.textContent=Math.round(nextH)+' px';};const upEv=e=>{document.body.classList.remove('block-height-resizing');heightResize.classList.remove('is-dragging');heightResize.releasePointerCapture?.(e.pointerId);heightResize.removeEventListener('pointermove',moveEv);heightResize.removeEventListener('pointerup',upEv);heightResize.removeEventListener('pointercancel',upEv);sync();};heightResize.addEventListener('pointermove',moveEv);heightResize.addEventListener('pointerup',upEv);heightResize.addEventListener('pointercancel',upEv);});
      page.appendChild(w);
    });
    bindHtmlPreviewFrames(page);
    bindImageDiagnostics(page);
    sync(false);
  }
  function move(i,d){const n=i+d;if(n<0||n>=blocks.length)return;snapshot();[blocks[i],blocks[n]]=[blocks[n],blocks[i]];selected=n;selectedCellPath=null;render();renderInspector();sync();}
  function field(label,id,value,type='text',extra=''){return `<label>${label}<input id="${id}" type="${type}" value="${esc(value??'')}" ${extra}></label>`;}
  function textarea(label,id,value){return `<label>${label}<textarea id="${id}">${esc(value??'')}</textarea></label>`;}
  function selectField(label,id,value,options){return `<label>${label}<select id="${id}">${options.map(o=>{const v=Array.isArray(o)?o[0]:o,l=Array.isArray(o)?o[1]:o;return `<option value="${esc(v)}" ${String(value)===String(v)?'selected':''}>${esc(l)}</option>`}).join('')}</select></label>`;}
  function numberField(label,id,value,min,max,step='1'){const n=parseFloat(value)||0;return `<label>${label}<input id="${id}" type="number" min="${min}" max="${max}" step="${step}" value="${n}"></label>`;}
  function fontSelect(label,id,value){return selectField(label,id,value||'Ubuntu Sans',Object.keys(fontStacks));}
  function expandBoxValue(value,fallback='0px'){
    const raw=String(value??fallback).trim().split(/\s+/).filter(Boolean);let v=raw.length?raw:[fallback];
    if(v.length===1)v=[v[0],v[0],v[0],v[0]];
    else if(v.length===2)v=[v[0],v[1],v[0],v[1]];
    else if(v.length===3)v=[v[0],v[1],v[2],v[1]];
    else v=[v[0],v[1],v[2],v[3]];
    return v;
  }
  function boxValueData(value,fallback='0px'){
    const expanded=expandBoxValue(value,fallback),units=expanded.map(v=>(String(v).match(/(px|%|em|rem)$/i)||[])[1]?.toLowerCase()).filter(Boolean);
    const unit=units.length&&units.every(u=>u===units[0])?units[0]:'px';
    return {values:expanded.map(v=>{const n=parseFloat(v);return Number.isFinite(n)?n:0;}),unit};
  }
  function boxValuesEqual(value){const d=boxValueData(value);return d.values.every(v=>Math.abs(v-d.values[0])<.0001);}
  function spacingControl(label,prefix,value,linked=true,allowNegative=false){
    const d=boxValueData(value),min=allowNegative?'-999':'0',max='9999',step=d.unit==='px'?'1':'0.1';
    const unitOptions=['px','%','em','rem'].map(u=>`<option value="${u}" ${d.unit===u?'selected':''}>${u}</option>`).join('');
    const names=['Superior','Derecha','Inferior','Izquierda'],ids=['Top','Right','Bottom','Left'];
    const fields=d.values.map((v,i)=>`<label class="spacing-side"><input id="${prefix}${ids[i]}" type="number" inputmode="decimal" min="${min}" max="${max}" step="${step}" value="${v}"><span>${names[i]}</span></label>`).join('');
    return `<div class="elementor-spacing-control" data-spacing="${prefix}"><div class="spacing-control-head"><div class="spacing-control-title"><strong>${label}</strong><span class="spacing-device" title="Vista de escritorio" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"></rect><path d="M8 21h8M12 17v4"></path></svg></span></div><select id="${prefix}Unit" class="spacing-unit" aria-label="Unidad de ${label.toLowerCase()}">${unitOptions}</select></div><div class="spacing-control-grid">${fields}<button type="button" id="${prefix}Link" class="spacing-link${linked?' is-linked':''}" title="${linked?'Desvincular valores':'Vincular valores'}" aria-label="${linked?'Desvincular valores':'Vincular valores'}"><svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"></path><path d="M14 11a5 5 0 0 0-7.1-.1l-2 2A5 5 0 0 0 12 20l1.1-1.1"></path></svg></button></div></div>`;
  }
  function bindSpacingControl(prefix,styleObj,key,linkedKey,redraw,allowNegative=false){
    const ids=['Top','Right','Bottom','Left'];
    const inferred=boxValuesEqual(styleObj[key]||'0px');
    const isLinked=()=>typeof styleObj[linkedKey]==='boolean'?styleObj[linkedKey]:inferred;
    const cleanNum=v=>{let n=Number(v);if(!Number.isFinite(n))n=0;if(!allowNegative)n=Math.max(0,n);return Math.round(n*100)/100;};
    const apply=(changedIndex=null)=>{
      const unit=document.getElementById(prefix+'Unit')?.value||'px',els=ids.map(id=>document.getElementById(prefix+id));
      let vals=els.map(el=>cleanNum(el?.value));
      if(isLinked()&&changedIndex!==null){const n=vals[changedIndex];vals=vals.map(()=>n);els.forEach(el=>{if(el)el.value=String(n);});}
      styleObj[linkedKey]=isLinked();
      styleObj[key]=vals.map(v=>`${v}${unit}`).join(' ');
      redraw();
    };
    ids.forEach((id,i)=>bindLive(prefix+id,()=>apply(i)));
    bindLive(prefix+'Unit',e=>{const step=e.target.value==='px'?'1':'0.1';ids.forEach(id=>{const el=document.getElementById(prefix+id);if(el)el.step=step;});apply(null);},'change');
    bind(prefix+'Link','click',()=>{snapshot();styleObj[linkedKey]=!isLinked();renderInspector();sync();});
  }
  function commonStyle(b){
    const s=ensureStyle(b),type=b.type,hasText=!['image','logo','spacer','html'].includes(type),hasRadius=!['divider','spacer','columns1','columns','columns3','social','footer','html'].includes(type),allowBackgroundImage=!['image','logo'].includes(type),overlayDefault=clamp(Number(s.overlayOpacity??(type==='hero'&&b.src ? .72:(type==='banner'&&b.src?Number(s.overlay??.58):0)))||0,0,1);
    let h='<div class="inspector-section"><span class="inspector-section-title">BLOQUE</span><div class="inspector-grid">';
    h+=field('Fondo','pBg',s.background||'#ffffff','color');
    if(allowBackgroundImage){
      h+=field('Imagen fondo','pBgImage',s.backgroundImage||'');
      h+=field('Color superposición','pOverlayColor',s.overlayColor||'#000000','color');
      h+=numberField('Opacidad overlay (%)','pOverlayOpacity',Math.round(overlayDefault*100),0,100,1);
      h+=selectField('Tamaño fondo','pBgSize',s.backgroundSize||'cover',[['cover','Cubrir'],['contain','Contener'],['auto','Original']]);
      h+=selectField('Posición fondo','pBgPosition',s.backgroundPosition||'center center',[['center center','Centro'],['center top','Arriba'],['center bottom','Abajo'],['left center','Izquierda'],['right center','Derecha']]);
    }
    if(hasText||type==='divider')h+=field(type==='divider'?'Color línea':'Color texto','pColor',s.color||(type==='divider'?'#dce5ec':'#1f2937'),'color');
    h+=field('Padding','pPad',s.padding||'14px 28px');
    h+=selectField('Alineación contenido','pAlign',s.align||'left',[['left','Izquierda'],['center','Centro'],['right','Derecha']]);
    h+=selectField('Posición bloque','pBlockAlign',s.blockAlign||'center',[['left','Izquierda'],['center','Centro'],['right','Derecha']]);
    if(hasRadius)h+=field('Radio','pRadius',s.radius||'0');
    const currentH=Math.round(blockMinHeightPx(s));
    h+='</div>'+(allowBackgroundImage?'<label>Subir imagen de fondo<input id="pBgUpload" type="file" accept="image/*"></label>':'')+'<label class="range-label"><span>Ancho del bloque <b id="pBlockWidthValue">'+Math.round(blockWidthPct(s))+'%</b></span><input id="pBlockWidth" type="range" min="30" max="100" step="1" value="'+Math.round(blockWidthPct(s))+'"></label><label class="range-label block-height-range"><span>Altura mínima <b id="pBlockHeightValue">'+(currentH?currentH+' px':'Automática')+'</b></span><input id="pBlockHeight" type="range" min="0" max="1000" step="5" value="'+currentH+'"></label><div class="block-height-help"><small>0 = automática. También puedes arrastrar la línea inferior del bloque.</small><button type="button" class="btn btn-light btn-mini" id="pBlockHeightAuto">AUTO</button></div></div>';
    if(hasText&&type!=='divider'){
      const tt=typographyTarget(b);
      h+='<div class="inspector-section"><span class="inspector-section-title">TIPOGRAFÍA'+(tt.part?' · '+esc(tt.label):'')+'</span><div class="inspector-grid">';
      h+=fontSelect('Fuente','pFont',tt.values.family);
      h+=numberField('Tamaño (px)','pSize',parseFloat(tt.values.size||'16'),8,120,1);
      h+=selectField('Peso','pWeight',tt.values.weight,[['300','Light'],['400','Regular'],['500','Medium'],['600','Semibold'],['700','Bold'],['800','Extra Bold']]);
      h+=numberField('Interlineado','pLine',parseFloat(tt.values.line||'1.6'),.6,4,.05);
      h+=numberField('Separación letras (px)','pLetter',parseFloat(tt.values.letter||'0'),-5,20,.1);
      h+='</div><div class="typography-size-quick"><button type="button" id="pSizeMinus">−</button><span>AJUSTE RÁPIDO</span><button type="button" id="pSizePlus">+</button></div></div>';
    }
    return h;
  }
  function updateInspectorContentField(type,key,value){const map={title:{content:'textContent'},text:{content:'textContent'},button:{content:'btnText'},columns1:{content:'colOne'},columns:{left:'colLeft',right:'colRight'},columns3:{one:'colOne',two:'colTwo',three:'colThree'},quote:{content:'quoteText'},card:{title:'cardTitle',text:'cardText',button:'cardButton'},footer:{content:'footerText'},hero:{title:'heroTitle',text:'heroText',button:'heroButton'},iconbox:{icon:'proIcon',title:'proTitle',text:'proText'},video:{title:'proTitle',text:'proText',button:'proButton'},product:{name:'proName',price:'proPrice',oldPrice:'proOldPrice',text:'proText',button:'proButton'},testimonial:{quote:'proQuote',author:'proAuthor',role:'proRole'},stats:{value1:'statValue1',label1:'statLabel1',value2:'statValue2',label2:'statLabel2',value3:'statValue3',label3:'statLabel3'},banner:{title:'proTitle',text:'proText',button:'proButton'},pricing:{badge:'proBadge',plan:'proPlan',price:'proPrice',period:'proPeriod',button:'proButton'},qr:{label:'proQrLabel'},header:{menu1:'proMenu1',menu2:'proMenu2',menu3:'proMenu3',button:'proButton'}};const id=map[type]?.[key],el=id?document.getElementById(id):null;if(el&&document.activeElement!==el)el.value=String(value??'').replace(/<br\s*\/?\s*>/gi,'\n').replace(/<[^>]+>/g,'');}
  function bind(id,event,fn){document.getElementById(id)?.addEventListener(event,fn);}
  function bindLive(id,fn,event='input'){const el=document.getElementById(id);if(!el)return;let snap=false;el.addEventListener('focus',()=>{if(!snap){snapshot();snap=true;}});el.addEventListener('blur',()=>{snap=false;});el.addEventListener(event,fn);if(event==='input')el.addEventListener('change',fn);}
  function setImageUploadStatus(message,type='info'){
    const box=document.getElementById('imgUploadStatus');
    if(!box)return;
    box.textContent=message||'';
    box.dataset.type=type;
    box.hidden=!message;
  }

  function iframeUpload(file){
    return new Promise((resolve)=>{
      if(!file){resolve('');return;}

      const token='up_'+Date.now()+'_'+Math.random().toString(36).slice(2);
      const iframe=document.createElement('iframe');
      iframe.name='emailsend_upload_frame_'+token;
      iframe.hidden=true;

      const form=document.createElement('form');
      form.method='post';
      form.enctype='multipart/form-data';
      form.action='upload-image.php?transport=iframe';
      form.target=iframe.name;
      form.hidden=true;

      const csrf=document.createElement('input');
      csrf.type='hidden';csrf.name='csrf';csrf.value=window.CSRF||'';
      form.appendChild(csrf);

      const tok=document.createElement('input');
      tok.type='hidden';tok.name='upload_token';tok.value=token;
      form.appendChild(tok);

      const input=document.createElement('input');
      input.type='file';
      input.name='image';

      try{
        const dt=new DataTransfer();
        dt.items.add(file);
        input.files=dt.files;
      }catch(err){
        cleanup();
        resolve('');
        return;
      }
      form.appendChild(input);

      let done=false;
      const timer=setTimeout(()=>{
        if(done)return;
        done=true;cleanup();
        setImageUploadStatus('La subida alternativa no respondió a tiempo.','error');
        resolve('');
      },30000);

      function cleanup(){
        clearTimeout(timer);
        window.removeEventListener('message',onMessage);
        try{form.remove();}catch(e){}
        try{iframe.remove();}catch(e){}
      }
      function onMessage(ev){
        if(ev.origin!==window.location.origin)return;
        const d=ev.data||{};
        if(d.source!=='EMAILSEND_IMAGE_UPLOAD'||d.token!==token)return;
        if(done)return;
        done=true;
        const payload=d.payload||{};
        cleanup();
        if(!payload.ok){
          setImageUploadStatus(payload.error||'No se pudo subir la imagen.','error');
          resolve('');
          return;
        }
        const url=normalizeAssetUrl(payload.url||'');
        setImageUploadStatus('Imagen subida correctamente mediante transporte alternativo.','ok');
        resolve(url);
      }

      window.addEventListener('message',onMessage);
      document.body.appendChild(iframe);
      document.body.appendChild(form);
      form.submit();
    });
  }

  async function verifyImageUrl(url){
    return new Promise((resolve)=>{
      const img=new Image();
      const bust=(url.includes('?')?'&':'?')+'_v='+Date.now();
      const timer=setTimeout(()=>{img.onload=img.onerror=null;resolve(false);},12000);
      img.onload=()=>{clearTimeout(timer);resolve(true);};
      img.onerror=()=>{clearTimeout(timer);resolve(false);};
      img.src=url+bust;
    });
  }

  async function uploadImageFile(file){
    if(!file)return '';
    const allowed=['image/jpeg','image/png','image/gif','image/webp'];
    if(file.type && !allowed.includes(file.type)){
      setImageUploadStatus('Formato no permitido. Usa JPG, PNG, GIF o WEBP.','error');
      contextToast('Formato no permitido. Usa JPG, PNG, GIF o WEBP.');
      return '';
    }
    if(file.size>12*1024*1024){
      setImageUploadStatus('La imagen supera el máximo de 12 MB.','error');
      contextToast('La imagen supera el máximo de 12 MB.');
      return '';
    }

    setImageUploadStatus('Subiendo imagen…','loading');
    contextToast('Subiendo imagen…');

    // Primera vía: endpoint PHP dedicado, sin route= en index.php.
    try{
      const fd=new FormData();
      fd.append('csrf',window.CSRF);
      fd.append('image',file,file.name||'imagen');

      const r=await fetch('upload-image.php',{
        method:'POST',
        body:fd,
        cache:'no-store',
        credentials:'same-origin'
      });

      const raw=await r.text();
      let j=null;
      try{j=JSON.parse(raw);}catch(e){}

      if(r.ok && j && j.ok){
        const url=normalizeAssetUrl(j.url||'');
        if(url && await verifyImageUrl(url)){
          setImageUploadStatus('Imagen cargada y verificada correctamente.','ok');
          contextToast('Imagen cargada correctamente');
          return url;
        }
        setImageUploadStatus('La imagen se guardó, pero no pudo verificarse. Reintentando…','loading');
      }else{
        setImageUploadStatus((j&&j.error)?j.error:'El servidor no aceptó la subida AJAX. Reintentando…','loading');
      }
    }catch(err){
      setImageUploadStatus('La subida AJAX fue bloqueada. Reintentando como formulario tradicional…','loading');
      console.warn('EMAILSEND AJAX image upload failed; trying iframe transport.',err);
    }

    // Segunda vía: multipart/form-data tradicional en iframe.
    const fallbackUrl=await iframeUpload(file);
    if(fallbackUrl){
      if(await verifyImageUrl(fallbackUrl)){
        setImageUploadStatus('Imagen cargada y verificada correctamente.','ok');
        contextToast('Imagen cargada correctamente');
        return fallbackUrl;
      }
      setImageUploadStatus('El archivo se guardó pero la URL pública no pudo abrirse.','error');
      return '';
    }

    if(!document.getElementById('imgUploadStatus')?.textContent){
      setImageUploadStatus('No fue posible completar la subida de la imagen.','error');
    }
    return '';
  }

  function renderInspector(){
    if(selected<0||!blocks[selected]){inspector.innerHTML='<h3>Propiedades</h3><p class="muted">Selecciona un bloque.</p>';return;}
    const rootBlock=blocks[selected],cellBlockCtx=activeSelectionContext?.kind==='cellblock'&&activeSelectionContext.blockIndex===selected?activeSelectionContext:null;
    const cellBlockModel=cellBlockCtx?getCellItem(rootBlock,cellBlockCtx.path,cellBlockCtx.itemIndex):null;
    const b=cellBlockModel||rootBlock,s=ensureStyle(b);
    if(activeSelectionContext?.kind==='inner'&&activeSelectionContext.blockIndex===selected&&isColumns(rootBlock)){
      const path=[...(activeSelectionContext.path||[])],idx=Number(activeSelectionContext.index),x=getInnerBox(rootBlock,path);
      if(x&&idx>=0&&idx<x.elements.length){
        const el=x.elements[idx],cell=x.cell,cs=ensureStyle(cell),tag=(el.tagName||'').toLowerCase(),isText=tag!=='img',label=innerElementLabel(el);
        const family=canonicalFontName(el.style.fontFamily,cs.fontFamily||'Ubuntu Sans'),size=parseFloat(el.style.fontSize||cs.fontSize||'16'),weight=canonicalFontWeight(el.style.fontWeight,canonicalFontWeight(cs.fontWeight,'400')),line=(parseFloat(el.style.lineHeight||cs.lineHeight||'1.5')||1.5),letter=(parseFloat(el.style.letterSpacing||cs.letterSpacing||'0')||0),color=colorInputValue(el.style.color,colorInputValue(cs.color,'#1f2937')),align=el.style.textAlign||cs.align||'left';
        let ih=`<div class="inspector-head"><span>${esc(label)}</span><strong>Elemento seleccionado</strong></div>`;
        ih+=`<div class="inspector-section inner-element-panel"><span class="inspector-section-title">TIPOGRAFÍA DEL ELEMENTO</span>`;
        if(isText){ih+='<div class="inspector-grid">'+field('Color','innerColor',color,'color')+selectField('Alineación','innerAlign',align,[['left','Izquierda'],['center','Centro'],['right','Derecha']])+fontSelect('Fuente','innerFont',family)+numberField('Tamaño (px)','innerSize',size,8,120,1)+selectField('Peso','innerWeight',weight,[['300','Light'],['400','Regular'],['500','Medium'],['600','Semibold'],['700','Bold'],['800','Extra Bold']])+numberField('Interlineado','innerLine',line,.6,4,.05)+numberField('Separación letras','innerLetter',letter,-5,20,.1)+'</div>';
          ih+=textarea('Contenido','innerText',el.textContent||'');}
        else ih+='<p class="muted">Seleccionaste una imagen. Sus propiedades visuales se editan desde su bloque o HTML.</p>';
        ih+=`<div class="column-inspector-actions"><button type="button" class="btn btn-light" id="innerResetTypography">Restablecer tipografía</button><button type="button" class="btn btn-light" id="innerBackToColumn">← Editar columna</button><button type="button" class="btn btn-danger-soft" id="innerDeleteSelected">× Eliminar elemento</button></div><p class="muted">Estos controles afectan únicamente al elemento seleccionado, no a toda la columna.</p></div>`;
        inspector.innerHTML=ih;
        if(isText){
          bindLive('innerColor',e=>setInnerStyle(selected,rootBlock,path,idx,'color',e.target.value));
          bindLive('innerAlign',e=>setInnerStyle(selected,rootBlock,path,idx,'text-align',e.target.value),'change');
          bindLive('innerFont',e=>setInnerStyle(selected,rootBlock,path,idx,'font-family',fontCss(e.target.value,'Ubuntu Sans')),'change');
          bindLive('innerSize',e=>setInnerStyle(selected,rootBlock,path,idx,'font-size',px(e.target.value)));
          bindLive('innerWeight',e=>setInnerStyle(selected,rootBlock,path,idx,'font-weight',e.target.value),'change');
          bindLive('innerLine',e=>setInnerStyle(selected,rootBlock,path,idx,'line-height',String(e.target.value)));
          bindLive('innerLetter',e=>setInnerStyle(selected,rootBlock,path,idx,'letter-spacing',`${Number(e.target.value)||0}px`));
          bindLive('innerText',e=>setInnerText(selected,rootBlock,path,idx,e.target.value));
        }
        bind('innerResetTypography','click',()=>{snapshot();resetInnerTypography(selected,rootBlock,path,idx);render();renderInspector();});
        bind('innerBackToColumn','click',()=>{activeSelectionContext={kind:'cell',blockIndex:selected,path:[...path],cell:getCellByPath(b,path)};selectedCellPath=[...path];renderInspector();});
        bind('innerDeleteSelected','click',()=>deleteInnerElementAtPath(rootBlock,path,idx));
        return;
      }
    }
    if(!cellBlockCtx&&selectedCellPath&&isColumns(rootBlock)){
      const cell=getCellByPath(rootBlock,selectedCellPath);if(!cell){selectedCellPath=null;return renderInspector();}
      const cs=ensureStyle(cell),depth=selectedCellPath.length;
      let ch=`<div class="inspector-head"><span>COLUMNA ${selectedCellPath.map(n=>n+1).join('.')}</span><strong>Propiedades</strong></div><div class="inspector-section"><span class="inspector-section-title">FONDO DE COLUMNA</span><div class="inspector-grid">`;
      const cellTransparent=String(cs.background||'transparent').toLowerCase()==='transparent';
      ch+=`<div class="column-transparent-control"><label class="transparent-toggle"><input id="cellBgTransparent" type="checkbox" ${cellTransparent?'checked':''}><span></span><b>Fondo transparente</b></label><small>Deja visible el fondo de la columna o bloque que está detrás.</small></div>`;
      ch+=field('Color','cellBg',colorInputValue(cs.background,'#ffffff'),'color',cellTransparent?'disabled':'')+field('Imagen fondo','cellBgImage',cs.backgroundImage||'')+field('Overlay','cellOverlayColor',cs.overlayColor||'#000000','color')+numberField('Opacidad %','cellOverlayOpacity',Math.round(clamp(Number(cs.overlayOpacity??0),0,1)*100),0,100,1)+selectField('Tamaño escritorio','cellBgSize',cs.backgroundSize||'cover',[['cover','Cubrir'],['contain','Contener'],['auto','Original']])+selectField('Posición','cellBgPosition',cs.backgroundPosition||'center center',[['center center','Centro'],['center top','Arriba'],['center bottom','Abajo'],['left center','Izquierda'],['right center','Derecha']])+selectField('Ajuste en móvil','cellBgSizeMobile',cs.backgroundSizeMobile||'cover',[['cover','Cubrir área'],['contain','Ver imagen completa'],['width','Ajustar al ancho']]);
      ch+='</div><div class="column-inspector-actions"><button type="button" class="btn btn-light" id="cellClearBg">Transparente</button><button type="button" class="btn btn-light" id="cellClearBgImage">Quitar imagen</button></div><label>Subir imagen de fondo<input id="cellBgUpload" type="file" accept="image/*"></label><p class="muted">En móvil puedes elegir si la imagen cubre el área, se muestra completa o se ajusta al 100% del ancho.</p></div>';
      const padLinked=typeof cs.paddingLinked==='boolean'?cs.paddingLinked:boxValuesEqual(cs.padding||'18px 20px'),marginLinked=typeof cs.marginLinked==='boolean'?cs.marginLinked:boxValuesEqual(cs.margin||'0px');
      ch+='<div class="inspector-section elementor-section"><span class="inspector-section-title">ESPACIADO</span>'+spacingControl('Margen','cellMarginBox',cs.margin||'0px',marginLinked,true)+spacingControl('Relleno','cellPaddingBox',cs.padding||'18px 20px',padLinked,false)+'<p class="spacing-hint">Usa la cadena para vincular o editar cada lado de forma independiente.</p></div>';
      ch+='<div class="inspector-section"><span class="inspector-section-title">BORDE Y ESQUINAS</span><div class="inspector-grid">'+numberField('Grosor (px)','cellBorderWidth',parseFloat(cs.borderWidth||'0'),0,20,.5)+selectField('Estilo','cellBorderStyle',cs.borderStyle||'solid',[['solid','Sólido'],['dashed','Discontinuo'],['dotted','Punteado'],['double','Doble'],['none','Sin borde']])+field('Color','cellBorderColor',cs.borderColor||'#dce5ec','color')+field('Radio','cellRadius',cs.radius||'0')+'</div><div class="column-inspector-actions"><button type="button" class="btn btn-light" id="cellNoBorder">Sin borde</button></div></div>';
      ch+='<div class="inspector-section"><span class="inspector-section-title">TAMAÑO Y POSICIÓN</span><div class="inspector-grid">'+numberField('Altura mínima','cellMinHeight',parseFloat(cs.minHeight||'0'),0,1200,5)+selectField('Alineación vertical','cellVAlign',cs.verticalAlign||'top',[['top','Arriba'],['middle','Centro'],['bottom','Abajo']])+'</div></div>';
      ch+='<div class="inspector-section"><span class="inspector-section-title">CONTENIDO Y TIPOGRAFÍA</span><div class="inspector-grid">'+field('Color texto','cellColor',cs.color||'#1f2937','color')+selectField('Alineación','cellAlign',cs.align||'left',[['left','Izquierda'],['center','Centro'],['right','Derecha']])+fontSelect('Fuente','cellFont',cs.fontFamily||'Ubuntu Sans')+numberField('Tamaño','cellSize',parseFloat(cs.fontSize||'16'),8,60,1)+selectField('Peso','cellWeight',cs.fontWeight||'400',[['300','Light'],['400','Regular'],['500','Medium'],['600','Semibold'],['700','Bold'],['800','Extra Bold']])+numberField('Interlineado','cellLine',parseFloat(cs.lineHeight||'1.5'),.6,4,.05)+numberField('Separación letras','cellLetter',parseFloat(cs.letterSpacing||'0'),-5,20,.1)+'</div>'+textarea('Contenido','cellContent',String(cell.content||'').replace(/<br\s*\/?\s*>/gi,'\n').replace(/<[^>]+>/g,''))+'</div>';
      ch+=`<div class="inspector-section"><span class="inspector-section-title">TAMAÑO HORIZONTAL</span><label class="range-label"><span>Ancho de columna <b id="cellWidthValue">${Math.round(Number(cell.width)||0)}%</b></span><input id="cellWidth" type="range" min="8" max="92" step="1" value="${Math.round(Number(cell.width)||0)}"></label><div class="column-inspector-actions"><button type="button" class="btn btn-light" id="cellEqualWidths">↔ Igualar columnas</button></div><p class="muted">Arrastra la línea divisoria en el lienzo o usa el control horizontal. Las columnas del mismo nivel siempre conservan el 100% del ancho total.</p></div>`;
      ch+=`<div class="inspector-section"><span class="inspector-section-title">ELEMENTOS DENTRO DE LA COLUMNA</span><p class="muted">Esta columna contiene ${cellItems(cell).length} bloque${cellItems(cell).length===1?'':'s'} estructurado${cellItems(cell).length===1?'':'s'}. Puedes arrastrar cualquier elemento desde la barra izquierda o usar estos accesos rápidos.</p><div class="column-inspector-actions"><button type="button" class="btn btn-light" id="cellAddTitle">＋ Título</button><button type="button" class="btn btn-light" id="cellAddText">＋ Texto</button><button type="button" class="btn btn-light" id="cellAddImage">＋ Imagen</button><button type="button" class="btn btn-light" id="cellAddButton">＋ Botón</button></div></div><div class="inspector-section"><span class="inspector-section-title">ESTRUCTURA</span><div class="column-inspector-actions"><button type="button" class="btn btn-light" id="cellAddSibling">＋ Columna</button>${depth<3?'<button type="button" class="btn btn-light" id="cellNest1">▯ 1 dentro</button><button type="button" class="btn btn-light" id="cellNest2">▥ 2 dentro</button><button type="button" class="btn btn-light" id="cellNest3">▦ 3 dentro</button>':''}<button type="button" class="btn btn-light" id="cellClearContent">⌫ Vaciar contenido</button>${cell.nested?'<button type="button" class="btn btn-danger-soft" id="cellDeleteNested">⊘ Eliminar estructura interna</button>':''}<button type="button" class="btn btn-danger-soft" id="cellDelete">× Eliminar columna</button><button type="button" class="btn btn-light" id="cellBack">← Bloque padre</button></div><p class="muted">Las columnas pueden contener bloques reales y hasta 3 niveles de columnas anidadas.</p></div>`;
      inspector.innerHTML=ch;
      const redrawCell=()=>{refreshCellVisual(selected,selectedCellPath,cell);refreshCellContentVisual(selected,selectedCellPath,cell);sync();};
      bind('cellBgTransparent','change',e=>{snapshot();const picker=document.getElementById('cellBg');if(e.target.checked){cs.background='transparent';if(picker)picker.disabled=true;}else{cs.background=(picker?.value||'#ffffff');if(picker)picker.disabled=false;}redrawCell();});
      bindLive('cellBg',e=>{cs.background=e.target.value;const t=document.getElementById('cellBgTransparent');if(t)t.checked=false;e.target.disabled=false;redrawCell();});bindLive('cellBgImage',e=>{cs.backgroundImage=e.target.value;render();renderInspector();sync();});bindLive('cellOverlayColor',e=>{cs.overlayColor=e.target.value;redrawCell();});bindLive('cellOverlayOpacity',e=>{cs.overlayOpacity=String(clamp((Number(e.target.value)||0)/100,0,1));redrawCell();});bindLive('cellBgSize',e=>{cs.backgroundSize=e.target.value;redrawCell();},'change');bindLive('cellBgPosition',e=>{cs.backgroundPosition=e.target.value;redrawCell();},'change');bindLive('cellBgSizeMobile',e=>{cs.backgroundSizeMobile=e.target.value;render();renderInspector();sync();},'change');bindLive('cellColor',e=>{cs.color=e.target.value;applyTypographyToCellContent(cell,'color',e.target.value);redrawCell();});bindSpacingControl('cellPaddingBox',cs,'padding','paddingLinked',redrawCell,false);bindSpacingControl('cellMarginBox',cs,'margin','marginLinked',redrawCell,true);bindLive('cellBorderWidth',e=>{cs.borderWidth=px(clamp(Number(e.target.value)||0,0,20));redrawCell();});bindLive('cellBorderStyle',e=>{cs.borderStyle=e.target.value;redrawCell();},'change');bindLive('cellBorderColor',e=>{cs.borderColor=e.target.value;redrawCell();});bindLive('cellAlign',e=>{cs.align=e.target.value;applyTypographyToCellContent(cell,'text-align',e.target.value);redrawCell();},'change');bindLive('cellVAlign',e=>{cs.verticalAlign=e.target.value;redrawCell();},'change');bindLive('cellRadius',e=>{cs.radius=e.target.value;redrawCell();});bindLive('cellMinHeight',e=>{const n=clamp(Number(e.target.value)||0,0,1200);cs.minHeight=n?px(n):'0px';redrawCell();});bindLive('cellFont',e=>{cs.fontFamily=e.target.value;applyTypographyToCellContent(cell,'font-family',fontCss(e.target.value,'Ubuntu Sans'));redrawCell();},'change');bindLive('cellSize',e=>{cs.fontSize=px(e.target.value);applyTypographyToCellContent(cell,'font-size',cs.fontSize);redrawCell();});bindLive('cellWeight',e=>{cs.fontWeight=e.target.value;applyTypographyToCellContent(cell,'font-weight',e.target.value);redrawCell();},'change');bindLive('cellLine',e=>{cs.lineHeight=String(e.target.value);applyTypographyToCellContent(cell,'line-height',cs.lineHeight);redrawCell();});bindLive('cellLetter',e=>{cs.letterSpacing=`${Number(e.target.value)||0}px`;applyTypographyToCellContent(cell,'letter-spacing',cs.letterSpacing);redrawCell();});bindLive('cellContent',e=>{cell.content=e.target.value.replace(/\n/g,'<br>');const live=page.querySelector(`.block[data-i="${selected}"] [data-cell-content="${pathKey(selectedCellPath)}"]`);if(live){live.innerHTML=cell.content;decorateColumnContent(live);}sync();});
      bindLive('cellWidth',e=>{const container=getContainerForCellPath(rootBlock,selectedCellPath);if(!container)return;const cells=ensureColumns(container),idx=Number(selectedCellPath[selectedCellPath.length-1]);if(idx<0||idx>=cells.length)return;normalizeCellWidths(container);const adj=idx<cells.length-1?idx+1:idx-1;if(adj<0)return;const pair=Number(cells[idx].width)+Number(cells[adj].width),desired=clamp(Number(e.target.value)||cells[idx].width,8,pair-8);cells[idx].width=desired;cells[adj].width=pair-desired;normalizeCellWidths(container);const v=document.getElementById('cellWidthValue');if(v)v.textContent=Math.round(cells[idx].width)+'%';refreshColumnWidthsVisual(selected,b,selectedCellPath);sync();});
      bind('cellNoBorder','click',()=>{snapshot();cs.borderWidth='0px';cs.borderStyle='none';render();renderInspector();sync();});bind('cellEqualWidths','click',()=>{const container=getContainerForCellPath(rootBlock,selectedCellPath);if(!container)return;snapshot();redistributeCellWidthsEvenly(container);render();renderInspector();sync();contextToast('Columnas igualadas');});
      bind('cellClearBg','click',()=>{snapshot();cs.background='transparent';const t=document.getElementById('cellBgTransparent'),picker=document.getElementById('cellBg');if(t)t.checked=true;if(picker)picker.disabled=true;redrawCell();contextToast('Fondo transparente');});bind('cellClearBgImage','click',()=>{if(!cs.backgroundImage)return;snapshot();cs.backgroundImage='';const f=document.getElementById('cellBgImage');if(f)f.value='';redrawCell();contextToast('Imagen de fondo eliminada');});bind('cellAddTitle','click',()=>addBlockToCell(rootBlock,selectedCellPath,'title'));bind('cellAddText','click',()=>addBlockToCell(rootBlock,selectedCellPath,'text'));bind('cellAddImage','click',()=>addBlockToCell(rootBlock,selectedCellPath,'image'));bind('cellAddButton','click',()=>addBlockToCell(rootBlock,selectedCellPath,'button'));bind('cellAddSibling','click',()=>addSiblingCell(rootBlock,selectedCellPath,true));bind('cellNest1','click',()=>addNestedAtPath(rootBlock,selectedCellPath,1));bind('cellNest2','click',()=>addNestedAtPath(rootBlock,selectedCellPath,2));bind('cellNest3','click',()=>addNestedAtPath(rootBlock,selectedCellPath,3));bind('cellClearContent','click',()=>clearCellContentAtPath(b,selectedCellPath));bind('cellDeleteNested','click',()=>deleteNestedAtPath(rootBlock,selectedCellPath));bind('cellDelete','click',()=>deleteCellAtPath(rootBlock,selectedCellPath));bind('cellBack','click',()=>{selectedCellPath=null;render();renderInspector();});
      bind('cellBgUpload','change',async e=>{const url=await uploadImageFile(e.target.files?.[0]);if(url){snapshot();cs.backgroundImage=url;render();renderInspector();sync();}});
      return;
    }
    let h=`<div class="inspector-head"><span>${b.type.toUpperCase()}</span><strong>${cellBlockCtx?'Elemento dentro de columna':'Propiedades'}</strong></div>${cellBlockCtx?`<div class="cellblock-breadcrumb">Columna ${cellBlockCtx.path.map(n=>n+1).join('.')} · elemento ${Number(cellBlockCtx.itemIndex)+1}</div>`:''}`+commonStyle(b);
    if(b.type==='title')h+='<div class="inspector-section"><span class="inspector-section-title">CONTENIDO</span>'+field('Texto del título','textContent',String(b.content||'').replace(/<[^>]+>/g,''))+'</div>';
    if(b.type==='text')h+='<div class="inspector-section"><span class="inspector-section-title">CONTENIDO</span>'+textarea('Texto','textContent',String(b.content||'').replace(/<br\s*\/?\s*>/gi,'\n').replace(/<[^>]+>/g,''))+'</div>';
    if(b.type==='image')h+='<div class="inspector-section image-main-panel"><span class="inspector-section-title">IMAGEN PRINCIPAL</span>'+field('URL imagen','imgSrc',b.src||'')+field('Texto alternativo','imgAlt',b.alt||'')+field('Ancho imagen','imgWidth',s.width||'100%')+`<label class="primary-upload-label">Subir / reemplazar imagen<input id="imgUpload" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"></label><div id="imgUploadStatus" class="image-upload-status" hidden></div><div class="column-inspector-actions"><button type="button" class="btn btn-light" id="imgOpen">Ver imagen</button><button type="button" class="btn btn-light" id="imgClear">Quitar imagen</button></div><p class="muted">La subida usa un endpoint dedicado y, si el hosting bloquea AJAX, EMAILSEND cambia automáticamente a un formulario tradicional.</p></div>`;
    if(b.type==='button')h+='<div class="inspector-section"><span class="inspector-section-title">BOTÓN</span>'+field('Texto','btnText',String(b.content||'').replace(/<[^>]+>/g,''))+field('Enlace','btnHref',b.href||'#')+field('Fondo','btnBg',s.buttonBg||'#ff5300','color')+field('Color','btnColor',s.buttonColor||'#ffffff','color')+field('Padding botón','btnPad',s.buttonPadding||'12px 18px')+'</div>';
    if(b.type==='spacer')h+='<div class="inspector-section"><span class="inspector-section-title">ESPACIO</span>'+field('Altura','spaceH',s.height||'30px')+'</div>';
    if(isColumns(b)){const cells=ensureColumns(b);h+=`<div class="inspector-section"><span class="inspector-section-title">ESTRUCTURA</span><p class="muted">${cells.length} columna${cells.length===1?'':'s'}. Haz clic en una columna del lienzo para editarla individualmente.</p><div class="column-inspector-actions"><button type="button" class="btn btn-light" id="rootAddColumn">＋ Agregar columna</button></div></div>`;}
    if(b.type==='logo')h+='<div class="inspector-section"><span class="inspector-section-title">LOGO</span>'+field('URL logo','logoSrc',b.src||'')+field('Texto alternativo','logoAlt',b.alt||'Logo')+field('Ancho','logoWidth',s.width||'190px')+`<label>Subir logo<input id="logoUpload" type="file" accept="image/*"></label></div>`;
    if(b.type==='quote')h+='<div class="inspector-section"><span class="inspector-section-title">DESTACADO</span>'+textarea('Texto','quoteText',String(b.content||'').replace(/<[^>]+>/g,''))+field('Color acento','quoteAccent',s.accent||'#31b4e7','color')+'</div>';
    if(b.type==='card')h+='<div class="inspector-section"><span class="inspector-section-title">TARJETA</span>'+field('Título','cardTitle',String(b.title||'').replace(/<[^>]+>/g,''))+textarea('Texto','cardText',String(b.text||'').replace(/<[^>]+>/g,''))+field('Texto botón','cardButton',String(b.button||'').replace(/<[^>]+>/g,''))+field('Enlace','cardHref',b.href||'#')+field('Fondo tarjeta','cardBg',s.cardBg||'#f8fbfd','color')+field('Borde','cardBorder',s.border||'#dce5ec','color')+field('Fondo botón','cardBtnBg',s.buttonBg||'#0074c7','color')+field('Color texto','cardTextColor',s.textColor||'#516579','color')+fontSelect('Fuente texto','cardTextFont',s.textFontFamily||'Ubuntu Sans')+numberField('Tamaño texto','cardTextSize',parseFloat(s.textSize||'16'),8,48,1)+fontSelect('Fuente botón','cardBtnFont',s.buttonFontFamily||'Ubuntu Sans')+numberField('Tamaño botón','cardBtnSize',parseFloat(s.buttonSize||'14'),8,32,1)+'</div>';
    if(b.type==='footer')h+='<div class="inspector-section"><span class="inspector-section-title">FOOTER</span>'+textarea('Contenido footer (HTML)','footerText',b.content||'')+'<p class="muted">Usa {{unsubscribe_url}} para el enlace de baja.</p></div>';
    if(b.type==='social')h+='<div class="inspector-section"><span class="inspector-section-title">REDES</span>'+field('Instagram','socInstagram',b.instagram||'')+field('Facebook','socFacebook',b.facebook||'')+field('LinkedIn','socLinkedin',b.linkedin||'')+field('Sitio web','socWebsite',b.website||'')+'</div>';
    if(b.type==='html')h+='<div class="inspector-section"><span class="inspector-section-title">HTML</span>'+textarea('Código HTML','htmlCode',b.content||'')+'<p class="muted">El HTML avanzado se muestra aislado por seguridad.</p></div>';
    if(b.type==='iconbox')h+='<div class="inspector-section"><span class="inspector-section-title">ICON BOX</span>'+field('Icono','proIcon',b.icon||'★')+field('Título','proTitle',String(b.title||'').replace(/<[^>]+>/g,''))+textarea('Texto','proText',String(b.text||'').replace(/<[^>]+>/g,''))+field('Fondo icono','proIconBg',s.iconBg||'#eaf6fd','color')+field('Color icono','proIconColor',s.iconColor||'#0074c7','color')+numberField('Tamaño icono','proIconSize',parseFloat(s.iconSize||'26'),12,64,1)+field('Fondo tarjeta','proCardBg',s.cardBg||'#f8fbfd','color')+field('Borde','proBorder',s.border||'#dce8f0','color')+'</div>';
    if(b.type==='video')h+='<div class="inspector-section"><span class="inspector-section-title">VIDEO</span>'+field('Miniatura','proImage',b.thumbnail||'')+field('URL video','proHref',b.href||'#')+field('Título','proTitle',String(b.title||'').replace(/<[^>]+>/g,''))+textarea('Texto','proText',String(b.text||'').replace(/<[^>]+>/g,''))+field('Texto botón','proButton',b.button||'')+field('Color play','proPlayBg',s.playBg||'#ff5300','color')+field('Fondo botón','proButtonBg',s.buttonBg||'#0074c7','color')+'<label>Subir miniatura<input id="proImageUpload" type="file" accept="image/*"></label></div>';
    if(b.type==='product')h+='<div class="inspector-section"><span class="inspector-section-title">PRODUCTO</span>'+field('Imagen','proImage',b.src||'')+field('Nombre','proName',b.name||'')+field('Precio','proPrice',b.price||'')+field('Precio anterior','proOldPrice',b.oldPrice||'')+textarea('Descripción','proText',String(b.text||'').replace(/<[^>]+>/g,''))+field('Texto botón','proButton',b.button||'')+field('Enlace','proHref',b.href||'#')+field('Color precio','proPriceColor',s.priceColor||'#ff5300','color')+field('Fondo tarjeta','proCardBg',s.cardBg||'#ffffff','color')+field('Borde','proBorder',s.border||'#dce8f0','color')+'<label>Subir producto<input id="proImageUpload" type="file" accept="image/*"></label></div>';
    if(b.type==='testimonial')h+='<div class="inspector-section"><span class="inspector-section-title">TESTIMONIO</span>'+field('Foto','proAvatar',b.avatar||'')+textarea('Testimonio','proQuote',String(b.quote||'').replace(/<[^>]+>/g,''))+field('Nombre','proAuthor',b.author||'')+field('Cargo / empresa','proRole',b.role||'')+numberField('Estrellas','proStars',Number(b.stars)||5,0,5,1)+field('Color estrellas','proAccent',s.accent||'#f6bd00','color')+field('Fondo tarjeta','proCardBg',s.cardBg||'#f8fbfd','color')+'<label>Subir foto<input id="proAvatarUpload" type="file" accept="image/*"></label></div>';
    if(b.type==='stats')h+='<div class="inspector-section"><span class="inspector-section-title">ESTADÍSTICAS</span>'+field('Valor 1','statValue1',b.value1||'')+field('Etiqueta 1','statLabel1',b.label1||'')+field('Valor 2','statValue2',b.value2||'')+field('Etiqueta 2','statLabel2',b.label2||'')+field('Valor 3','statValue3',b.value3||'')+field('Etiqueta 3','statLabel3',b.label3||'')+field('Fondo tarjetas','proCardBg',s.cardBg||'#f8fbfd','color')+field('Borde','proBorder',s.border||'#dce8f0','color')+'</div>';
    if(b.type==='iconlist')h+='<div class="inspector-section"><span class="inspector-section-title">LISTA CON ICONOS</span>'+field('Icono','proIcon',b.icon||'✓')+textarea('Elementos (uno por línea)','proItems',b.items||'')+field('Color icono','proIconColor',s.iconColor||'#73bc25','color')+field('Fondo icono','proIconBg',s.iconBg||'#eef9e7','color')+'</div>';
    if(b.type==='banner')h+='<div class="inspector-section"><span class="inspector-section-title">BANNER</span>'+field('Imagen fondo','proImage',b.src||'')+field('Título','proTitle',b.title||'')+textarea('Texto','proText',String(b.text||'').replace(/<[^>]+>/g,''))+field('Texto botón','proButton',b.button||'')+field('Enlace','proHref',b.href||'#')+field('Fondo botón','proButtonBg',s.buttonBg||'#ff5300','color')+'<label>Subir imagen<input id="proImageUpload" type="file" accept="image/*"></label></div>';
    if(b.type==='pricing')h+='<div class="inspector-section"><span class="inspector-section-title">PRICING</span>'+field('Etiqueta','proBadge',b.badge||'')+field('Plan','proPlan',b.plan||'')+field('Precio','proPrice',b.price||'')+field('Periodo','proPeriod',b.period||'')+textarea('Características (una por línea)','proFeatures',b.features||'')+field('Texto botón','proButton',b.button||'')+field('Enlace','proHref',b.href||'#')+field('Color precio','proPriceColor',s.priceColor||'#0074c7','color')+field('Fondo etiqueta','proBadgeBg',s.badgeBg||'#ff5300','color')+field('Fondo tarjeta','proCardBg',s.cardBg||'#ffffff','color')+'</div>';
    if(b.type==='qr')h+='<div class="inspector-section"><span class="inspector-section-title">CÓDIGO QR</span>'+field('URL / texto del QR','proQrData',b.data||'')+field('Texto inferior','proQrLabel',b.label||'')+field('Tamaño QR','proQrSize',s.qrSize||'190px')+'<p class="muted">El QR se genera automáticamente como imagen compatible con email.</p></div>';
    if(b.type==='header')h+='<div class="inspector-section"><span class="inspector-section-title">HEADER</span>'+field('Logo','proImage',b.src||'')+field('Ancho logo','proLogoWidth',s.logoWidth||'170px')+field('Menú 1','proMenu1',b.menu1||'')+field('URL 1','proUrl1',b.url1||'#')+field('Menú 2','proMenu2',b.menu2||'')+field('URL 2','proUrl2',b.url2||'#')+field('Menú 3','proMenu3',b.menu3||'')+field('URL 3','proUrl3',b.url3||'#')+field('Botón','proButton',b.button||'')+field('Enlace botón','proHref',b.href||'#')+field('Fondo botón','proButtonBg',s.buttonBg||'#ff5300','color')+'<label>Subir logo<input id="proImageUpload" type="file" accept="image/*"></label></div>';
    if(b.type==='hero')h+='<div class="inspector-section"><span class="inspector-section-title">HERO</span>'+field('Imagen de fondo','heroSrc',b.src||'')+field('Título','heroTitle',String(b.title||'').replace(/<[^>]+>/g,''))+textarea('Texto','heroText',String(b.text||'').replace(/<[^>]+>/g,''))+field('Texto botón','heroButton',String(b.button||'').replace(/<[^>]+>/g,''))+field('Enlace botón','heroHref',b.href||'#')+field('Color texto secundario','heroTextColor',s.textColor||'#ffffff','color')+fontSelect('Fuente texto','heroTextFont',s.textFontFamily||'Ubuntu Sans')+numberField('Tamaño texto','heroTextSize',parseFloat(s.textSize||'16'),8,48,1)+field('Fondo botón','heroBtnBg',s.buttonBg||'#ff5300','color')+field('Color botón','heroBtnColor',s.buttonColor||'#ffffff','color')+fontSelect('Fuente botón','heroBtnFont',s.buttonFontFamily||'Ubuntu Sans')+numberField('Tamaño botón','heroBtnSize',parseFloat(s.buttonSize||'14'),8,32,1)+`<label>Subir fondo<input id="heroUpload" type="file" accept="image/*"></label></div>`;
    inspector.innerHTML=h;
    const redraw=()=>{render();sync();};
    bindLive('pBg',e=>{s.background=e.target.value;redraw();});
    bindLive('pBgImage',e=>{s.backgroundImage=e.target.value;redraw();});bindLive('pOverlayColor',e=>{s.overlayColor=e.target.value;redraw();});bindLive('pOverlayOpacity',e=>{s.overlayOpacity=String(clamp((Number(e.target.value)||0)/100,0,1));redraw();});
    bindLive('pBgSize',e=>{s.backgroundSize=e.target.value;redraw();},'change');
    bindLive('pBgPosition',e=>{s.backgroundPosition=e.target.value;redraw();},'change');
    bindLive('pColor',e=>{s.color=e.target.value;redraw();});
    bindLive('pPad',e=>{s.padding=e.target.value;redraw();});
    bindLive('pAlign',e=>{s.align=e.target.value;redraw();},'change');
    bindLive('pBlockAlign',e=>{s.blockAlign=e.target.value;redraw();},'change');
    bindLive('pRadius',e=>{s.radius=e.target.value;redraw();});
    bindLive('pBlockWidth',e=>{s.blockWidth=e.target.value+'%';const v=document.getElementById('pBlockWidthValue');if(v)v.textContent=e.target.value+'%';render();sync();});
    bindLive('pBlockHeight',e=>{const n=Math.round(Number(e.target.value)||0),v=document.getElementById('pBlockHeightValue');if(n<=0){delete s.minHeight;if(v)v.textContent='Automática';}else{s.minHeight=n+'px';if(v)v.textContent=n+' px';}render();sync();});
    bind('pBlockHeightAuto','click',()=>{snapshot();delete s.minHeight;const r=document.getElementById('pBlockHeight'),v=document.getElementById('pBlockHeightValue');if(r)r.value=0;if(v)v.textContent='Automática';render();sync();});
    const tt=typographyTarget(b);
    bindLive('pFont',e=>{cleanBlockTypography(b,['font-family']);s[tt.keys.family]=e.target.value;patchSelectedTypography('family',fontCss(e.target.value));redraw();},'change');
    bindLive('pSize',e=>{const v=clamp(Number(e.target.value)||8,8,120);cleanBlockTypography(b,['font-size']);s[tt.keys.size]=px(v);patchSelectedTypography('size',px(v));redraw();});
    bindLive('pWeight',e=>{cleanBlockTypography(b,['font-weight']);s[tt.keys.weight]=e.target.value;patchSelectedTypography('weight',e.target.value);redraw();},'change');
    bindLive('pLine',e=>{const v=String(e.target.value||'1.6');cleanBlockTypography(b,['line-height']);s[tt.keys.line]=v;patchSelectedTypography('line',v);redraw();});
    bindLive('pLetter',e=>{const v=px(clamp(Number(e.target.value)||0,-5,20));cleanBlockTypography(b,['letter-spacing']);s[tt.keys.letter]=v;patchSelectedTypography('letter',v);redraw();});
    bind('pSizeMinus','click',()=>{const el=document.getElementById('pSize');if(!el)return;snapshot();el.value=clamp((Number(el.value)||16)-2,8,120);s[tt.keys.size]=px(el.value);patchSelectedTypography('size',px(el.value));render();renderInspector();sync();});
    bind('pSizePlus','click',()=>{const el=document.getElementById('pSize');if(!el)return;snapshot();el.value=clamp((Number(el.value)||16)+2,8,120);s[tt.keys.size]=px(el.value);patchSelectedTypography('size',px(el.value));render();renderInspector();sync();});
    const modelText=(id,key,asText=false)=>bindLive(id,e=>{b[key]=asText?e.target.value:e.target.value;let part=null;if((b.type==='hero'||b.type==='card')&&['title','text','button'].includes(key))part=key;else if(['title','text','quote','footer'].includes(b.type)&&key==='content')part='content';else if(b.type==='button'&&key==='content')part='button';else if(b.type==='image'&&key==='src')part='image';else if(b.type==='logo'&&key==='src')part='logo';else if(b.type==='social'&&['instagram','facebook','linkedin','website'].includes(key))part=key;if(part)setPartHidden(b,part,false);render();sync();});
    modelText('textContent','content');
    bindLive('imgSrc',e=>{b.src=normalizeAssetUrl(e.target.value);setPartHidden(b,'image',false);render();sync();});
    modelText('imgAlt','alt');
    bindLive('imgWidth',e=>{s.width=e.target.value;redraw();});
    bind('imgOpen','click',()=>{if(!b.src){contextToast('Este bloque no tiene imagen');return;}window.open(normalizeAssetUrl(b.src),'_blank','noopener');});
    bind('imgClear','click',()=>{if(b.type!=='image')return;snapshot();b.src='';s.backgroundImage='';render();renderInspector();sync();contextToast('Imagen eliminada');});
    modelText('logoSrc','src');modelText('logoAlt','alt');bindLive('logoWidth',e=>{s.width=e.target.value;redraw();});
    modelText('btnText','content',true);modelText('btnHref','href',true);bindLive('btnBg',e=>{s.buttonBg=e.target.value;redraw();});bindLive('btnColor',e=>{s.buttonColor=e.target.value;redraw();});bindLive('btnPad',e=>{s.buttonPadding=e.target.value;redraw();});
    bindLive('spaceH',e=>{s.height=e.target.value;redraw();});modelText('quoteText','content');bindLive('quoteAccent',e=>{s.accent=e.target.value;redraw();});
    modelText('cardTitle','title',true);modelText('cardText','text');modelText('cardButton','button',true);modelText('cardHref','href',true);bindLive('cardBg',e=>{s.cardBg=e.target.value;redraw();});bindLive('cardBorder',e=>{s.border=e.target.value;redraw();});bindLive('cardBtnBg',e=>{s.buttonBg=e.target.value;redraw();});bindLive('cardTextColor',e=>{s.textColor=e.target.value;redraw();});bindLive('cardTextFont',e=>{b.text=stripInlineTypography(b.text||'',['font-family']);s.textFontFamily=e.target.value;redraw();},'change');bindLive('cardTextSize',e=>{b.text=stripInlineTypography(b.text||'',['font-size']);s.textSize=px(e.target.value);redraw();});bindLive('cardBtnFont',e=>{s.buttonFontFamily=e.target.value;redraw();},'change');bindLive('cardBtnSize',e=>{s.buttonSize=px(e.target.value);redraw();});
    modelText('footerText','content');modelText('socInstagram','instagram',true);modelText('socFacebook','facebook',true);modelText('socLinkedin','linkedin',true);modelText('socWebsite','website',true);
    bindLive('htmlCode',e=>{b.content=e.target.value;sync();const fr=document.querySelector('.block.selected iframe');if(fr)fr.setAttribute('srcdoc',b.content);});
    modelText('heroSrc','src',true);modelText('heroTitle','title',true);modelText('heroText','text');modelText('heroButton','button',true);modelText('heroHref','href',true);bindLive('heroTextColor',e=>{s.textColor=e.target.value;redraw();});bindLive('heroTextFont',e=>{b.text=stripInlineTypography(b.text||'',['font-family']);s.textFontFamily=e.target.value;redraw();},'change');bindLive('heroTextSize',e=>{b.text=stripInlineTypography(b.text||'',['font-size']);s.textSize=px(e.target.value);redraw();});bindLive('heroBtnBg',e=>{s.buttonBg=e.target.value;redraw();});bindLive('heroBtnColor',e=>{s.buttonColor=e.target.value;redraw();});bindLive('heroBtnFont',e=>{s.buttonFontFamily=e.target.value;redraw();},'change');bindLive('heroBtnSize',e=>{s.buttonSize=px(e.target.value);redraw();});
    const setPro=(id,key,part=null)=>bindLive(id,e=>{b[key]=e.target.value;if(part)setPartHidden(b,part,false);render();sync();});
    setPro('proIcon','icon','icon');setPro('proTitle','title','title');setPro('proText','text','text');setPro('proButton','button','button');setPro('proHref','href');setPro('proName','name','title');setPro('proPrice','price','price');setPro('proOldPrice','oldPrice','oldPrice');setPro('proQuote','quote','text');setPro('proAuthor','author','author');setPro('proRole','role','role');setPro('proItems','items','items');setPro('proBadge','badge','badge');setPro('proPlan','plan','plan');setPro('proPeriod','period','period');setPro('proFeatures','features','features');setPro('proQrData','data','qr');setPro('proQrLabel','label','text');setPro('proMenu1','menu1','menu1');setPro('proUrl1','url1');setPro('proMenu2','menu2','menu2');setPro('proUrl2','url2');setPro('proMenu3','menu3','menu3');setPro('proUrl3','url3');
    bindLive('proStars',e=>{b.stars=clamp(Number(e.target.value)||0,0,5);setPartHidden(b,'stars',false);redraw();});
    bindLive('statValue1',e=>{b.value1=e.target.value;setPartHidden(b,'stat1',false);redraw();});bindLive('statLabel1',e=>{b.label1=e.target.value;setPartHidden(b,'stat1',false);redraw();});bindLive('statValue2',e=>{b.value2=e.target.value;setPartHidden(b,'stat2',false);redraw();});bindLive('statLabel2',e=>{b.label2=e.target.value;setPartHidden(b,'stat2',false);redraw();});bindLive('statValue3',e=>{b.value3=e.target.value;setPartHidden(b,'stat3',false);redraw();});bindLive('statLabel3',e=>{b.label3=e.target.value;setPartHidden(b,'stat3',false);redraw();});
    bindLive('proIconBg',e=>{s.iconBg=e.target.value;redraw();});bindLive('proIconColor',e=>{s.iconColor=e.target.value;redraw();});bindLive('proIconSize',e=>{s.iconSize=px(e.target.value);redraw();});bindLive('proCardBg',e=>{s.cardBg=e.target.value;redraw();});bindLive('proBorder',e=>{s.border=e.target.value;redraw();});bindLive('proPlayBg',e=>{s.playBg=e.target.value;redraw();});bindLive('proButtonBg',e=>{s.buttonBg=e.target.value;redraw();});bindLive('proPriceColor',e=>{s.priceColor=e.target.value;redraw();});bindLive('proAccent',e=>{s.accent=e.target.value;redraw();});bindLive('proBadgeBg',e=>{s.badgeBg=e.target.value;redraw();});bindLive('proQrSize',e=>{s.qrSize=e.target.value;redraw();});bindLive('proLogoWidth',e=>{s.logoWidth=e.target.value;redraw();});
    setPro('proImage',b.type==='video'?'thumbnail':'src',b.type==='header'?'logo':'image');setPro('proAvatar','avatar','avatar');
    bind('proImageUpload','change',async e=>{const url=await uploadImageFile(e.target.files?.[0]);if(!url)return;snapshot();if(b.type==='video')b.thumbnail=url;else b.src=url;setPartHidden(b,b.type==='header'?'logo':'image',false);render();renderInspector();sync();});
    bind('proAvatarUpload','change',async e=>{const url=await uploadImageFile(e.target.files?.[0]);if(!url)return;snapshot();b.avatar=url;setPartHidden(b,'avatar',false);render();renderInspector();sync();});
    async function uploadToSrc(input){const url=await uploadImageFile(input.files?.[0]);if(!url)return;snapshot();b.src=url;if(b.type==='image')setPartHidden(b,'image',false);if(b.type==='logo')setPartHidden(b,'logo',false);render();renderInspector();sync();}
    bind('imgUpload','change',e=>uploadToSrc(e.target));bind('heroUpload','change',e=>uploadToSrc(e.target));bind('logoUpload','change',e=>uploadToSrc(e.target));
    bind('pBgUpload','change',async e=>{const url=await uploadImageFile(e.target.files?.[0]);if(!url)return;snapshot();if(b.type==='image'||b.type==='logo'){b.src=url;s.backgroundImage='';}else{s.backgroundImage=url;}render();renderInspector();sync();});
    bind('rootAddColumn','click',()=>{if(!isColumns(b))return;const cells=ensureColumns(b);if(cells.length>=4)return alert('Máximo 4 columnas por fila para mantener compatibilidad de email.');snapshot();const eq=100/(cells.length+1);cells.forEach(c=>c.width=eq);cells.push(makeCell('<p>Nuevo contenido.</p>',eq));normalizeCellWidths(b);selectedCellPath=[cells.length-1];render();renderInspector();sync();});
  }
  let compileTimer=null;
  async function compileNow(){clearTimeout(compileTimer);blocksInput.value=JSON.stringify(blocks);const r=await fetch('index.php?route=compile',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':window.CSRF},body:new URLSearchParams({blocks_json:blocksInput.value})});if(!r.ok)throw new Error(await r.text()||'No fue posible generar la vista previa.');const t=await r.text();htmlInput.value=t;return t;}
  function sync(compile=true){blocksInput.value=JSON.stringify(blocks);if(!compile)return;clearTimeout(compileTimer);compileTimer=setTimeout(()=>compileNow().catch(()=>{}),160);}
  document.querySelectorAll('[data-add]').forEach(btn=>{
    btn.draggable=true;
    btn.addEventListener('dragstart',ev=>{activeDrag={kind:'palette',type:btn.dataset.add};btn.dataset.didDrag='1';ev.dataTransfer.effectAllowed='copy';ev.dataTransfer.setData('application/x-emailsend-add',btn.dataset.add);btn.classList.add('dragging-palette');});
    btn.addEventListener('dragend',()=>{activeDrag=null;btn.classList.remove('dragging-palette');clearDropClasses();page.classList.remove('palette-drop-ready');setTimeout(()=>delete btn.dataset.didDrag,0);});
    btn.addEventListener('click',()=>{if(btn.dataset.didDrag)return;const type=btn.dataset.add;if(selectedCellPath&&isColumns(blocks[selected])&&!isColumnStructureType(type)){addBlockToCell(blocks[selected],selectedCellPath,type);return;}insertPalette(type,blocks.length);});
  });
  page.addEventListener('contextmenu',ev=>{ev.preventDefault();ev.stopPropagation();const ctx=contextFromEventTarget(ev.target)||{kind:'canvas'};selectContext(ctx);showContextMenu(ev.clientX,ev.clientY,ctx);});
  document.querySelectorAll('[data-add]').forEach(btn=>btn.addEventListener('contextmenu',ev=>{ev.preventDefault();ev.stopPropagation();showContextMenu(ev.clientX,ev.clientY,{kind:'palette',type:btn.dataset.add});}));
  document.addEventListener('pointerdown',ev=>{
    if(ev.target.closest?.('#builderContextMenu'))return;
    // El clic derecho no debe cerrar el menú antes de que llegue el evento contextmenu.
    // Solo clic izquierdo/táctil fuera del menú lo cierra.
    if(ev.button===0||ev.pointerType==='touch')hideContextMenu();
  });
  document.addEventListener('contextmenu',ev=>{
    const insideBuilder=page.contains(ev.target)||ev.target.closest?.('[data-add]')||ev.target.closest?.('#builderContextMenu');
    if(!insideBuilder)hideContextMenu(true);
  });
  // Mantener el menú abierto aunque el inspector/editor produzca scroll automático.
  // Al ser position:fixed no necesita cerrarse durante scroll.
  window.addEventListener('resize',()=>hideContextMenu(true));
  page.addEventListener('dragover',ev=>{if(!ev.dataTransfer.types.includes('application/x-emailsend-add'))return;ev.preventDefault();page.classList.add('palette-drop-ready');});
  page.addEventListener('dragleave',ev=>{if(!page.contains(ev.relatedTarget))page.classList.remove('palette-drop-ready');});
  page.addEventListener('drop',ev=>{const add=ev.dataTransfer.getData('application/x-emailsend-add');if(!add)return;ev.preventDefault();page.classList.remove('palette-drop-ready');insertPalette(add,blocks.length);});
  document.querySelectorAll('[data-template]').forEach(btn=>btn.addEventListener('click',async()=>{if(blocks.length){if(!window.emailSendConfirm)return;var ok=await window.emailSendConfirm({variant:'warning',title:'Reemplazar diseño actual',message:'¿Quieres reemplazar el diseño actual por esta plantilla?',note:'El contenido actual del editor será sustituido por la plantilla seleccionada.',label:'Sí, reemplazar'});if(!ok)return;}snapshot();blocks=templates[btn.dataset.template].map(clone);normalizeBlocks(blocks);selected=0;selectedCellPath=null;render();renderInspector();sync();}));
  document.getElementById('importHtmlBtn')?.addEventListener('click',()=>{const code=prompt('Pega aquí el HTML completo de tu campaña:');if(code!==null){snapshot();blocks=[{type:'html',content:code,style:{blockWidth:'100%',blockAlign:'center'}}];selected=0;selectedCellPath=null;render();renderInspector();sync();}});
  document.getElementById('undoBtn')?.addEventListener('click',undo);document.getElementById('redoBtn')?.addEventListener('click',redo);
  const refreshHtmlPreviewFrames=()=>window.setTimeout(()=>page.querySelectorAll('iframe.html-preview').forEach(resizeHtmlPreviewFrame),40);
  let campaignPreviewResizeTimers=[];
  function clearCampaignPreviewWatchers(){
    campaignPreviewResizeTimers.forEach(id=>window.clearTimeout(id));
    campaignPreviewResizeTimers=[];
    try{previewFrame?.__emailsendResizeObserver?.disconnect?.();}catch(e){}
  }
  function campaignPreviewContentHeight(){
    if(!previewFrame)return 0;
    try{
      const doc=previewFrame.contentDocument||previewFrame.contentWindow?.document;
      if(!doc?.body||!doc?.documentElement)return 0;
      const body=doc.body,de=doc.documentElement;
      // El iframe ya es same-origin gracias a allow-same-origin. Medimos el documento completo.
      const oldBodyOverflow=body.style.overflow,oldHtmlOverflow=de.style.overflow;
      body.style.overflow='visible';de.style.overflow='visible';
      let h=Math.max(body.scrollHeight||0,body.offsetHeight||0,de.scrollHeight||0,de.offsetHeight||0);
      const bodyTop=body.getBoundingClientRect().top;
      body.querySelectorAll('*').forEach(el=>{
        const cs=previewFrame.contentWindow?.getComputedStyle?.(el);
        if(cs?.position==='fixed'||cs?.display==='none')return;
        const r=el.getBoundingClientRect();
        if(r&&Number.isFinite(r.bottom))h=Math.max(h,Math.ceil(r.bottom-bodyTop+(parseFloat(cs?.marginBottom)||0)));
      });
      body.style.overflow=oldBodyOverflow;de.style.overflow=oldHtmlOverflow;
      return Math.max(240,Math.min(100000,Math.ceil(h)+8));
    }catch(e){
      // Si alguien instala editor.js sin actualizar index.php, no volvemos a cortar a 720 px.
      return Math.max(3000,Math.ceil(page?.scrollHeight||0)+1200);
    }
  }
  function fitCampaignPreviewFrame(){
    if(!previewFrame||!previewModal?.classList.contains('open'))return;
    const h=campaignPreviewContentHeight();
    if(!h)return;
    previewFrame.style.height=h+'px';
    previewFrame.dataset.autoHeight=String(h);
    previewFrame.style.visibility='visible';
  }
  function scheduleCampaignPreviewResize(){
    campaignPreviewResizeTimers.forEach(id=>window.clearTimeout(id));
    campaignPreviewResizeTimers=[0,40,100,220,450,800,1400,2400].map(ms=>window.setTimeout(fitCampaignPreviewFrame,ms));
  }
  function attachCampaignPreviewWatchers(){
    try{
      const doc=previewFrame.contentDocument||previewFrame.contentWindow?.document;
      if(!doc?.body)return;
      doc.documentElement.style.overflow='hidden';
      doc.body.style.overflow='hidden';
      doc.querySelectorAll('img').forEach(img=>{
        if(!img.complete){img.addEventListener('load',scheduleCampaignPreviewResize,{once:true});img.addEventListener('error',scheduleCampaignPreviewResize,{once:true});}
      });
      if(doc.fonts?.ready)doc.fonts.ready.then(scheduleCampaignPreviewResize).catch(()=>{});
      if(window.ResizeObserver){
        const ro=new ResizeObserver(()=>scheduleCampaignPreviewResize());
        ro.observe(doc.body);
        previewFrame.__emailsendResizeObserver=ro;
      }
    }catch(e){}
  }
  function loadCampaignPreviewHtml(html){
    if(!previewFrame)return;
    clearCampaignPreviewWatchers();
    previewFrame.setAttribute('scrolling','no');
    previewFrame.style.visibility='hidden';
    previewFrame.style.height='720px';
    previewFrame.dataset.autoHeight='720';
    const source=String(html||'');
    previewFrame.onload=()=>{
      previewFrame.onload=null;
      window.requestAnimationFrame(()=>window.requestAnimationFrame(()=>{
        fitCampaignPreviewFrame();
        attachCampaignPreviewWatchers();
        scheduleCampaignPreviewResize();
      }));
    };
    previewFrame.srcdoc=source;
  }
  document.getElementById('desktopBtn')?.addEventListener('click',()=>{device.classList.remove('mobile');document.getElementById('desktopBtn')?.classList.add('is-active');document.getElementById('mobileBtn')?.classList.remove('is-active');refreshHtmlPreviewFrames();});
  document.getElementById('mobileBtn')?.addEventListener('click',()=>{device.classList.add('mobile');document.getElementById('mobileBtn')?.classList.add('is-active');document.getElementById('desktopBtn')?.classList.remove('is-active');refreshHtmlPreviewFrames();});
  window.addEventListener('resize',refreshHtmlPreviewFrames,{passive:true});
  document.getElementById('desktopBtn')?.classList.add('is-active');
  async function openPreview(){if(!previewModal||!previewFrame)return;const btn=document.getElementById('previewBtn');try{if(btn){btn.disabled=true;btn.classList.add('loading');}const html=await compileNow();if(!html||!String(html).trim())throw new Error('El compilador devolvió una vista previa vacía.');const subject=document.querySelector('input[name="subject"]')?.value||'Vista previa de campaña';const title=document.getElementById('campaignPreviewTitle');if(title)title.textContent=subject;previewModal.classList.add('open');previewModal.setAttribute('aria-hidden','false');document.body.classList.add('campaign-preview-open');setPreviewMode('desktop');const stage=previewModal.querySelector('.campaign-preview-stage');if(stage)stage.scrollTop=0;window.requestAnimationFrame(()=>loadCampaignPreviewHtml(html));}catch(err){alert(err.message||'No fue posible previsualizar.');}finally{if(btn){btn.disabled=false;btn.classList.remove('loading');}}}
  function closePreview(){if(!previewModal)return;clearCampaignPreviewWatchers();previewModal.classList.remove('open');previewModal.setAttribute('aria-hidden','true');document.body.classList.remove('campaign-preview-open');}
  function setPreviewMode(mode){if(!previewViewport)return;previewViewport.classList.toggle('mobile',mode==='mobile');document.getElementById('previewDesktopBtn')?.classList.toggle('is-active',mode==='desktop');document.getElementById('previewMobileBtn')?.classList.toggle('is-active',mode==='mobile');if(previewFrame?.contentDocument?.body){window.requestAnimationFrame(()=>window.requestAnimationFrame(scheduleCampaignPreviewResize));}}
  document.getElementById('previewBtn')?.addEventListener('click',openPreview);document.getElementById('previewCloseBtn')?.addEventListener('click',closePreview);document.getElementById('previewDesktopBtn')?.addEventListener('click',()=>setPreviewMode('desktop'));document.getElementById('previewMobileBtn')?.addEventListener('click',()=>setPreviewMode('mobile'));previewModal?.addEventListener('click',e=>{if(e.target===previewModal)closePreview();});
  function keyboardContext(){if(activeSelectionContext)return activeSelectionContext;if(selectedCellPath&&isColumns(blocks[selected]))return {kind:'cell',blockIndex:selected,path:[...selectedCellPath],cell:getCellByPath(blocks[selected],selectedCellPath)};return blocks[selected]?{kind:'block',blockIndex:selected,block:blocks[selected]}:{kind:'canvas'};}
  function hasNativeTextSelection(target){if(target?.matches?.('input,textarea')){try{return Number(target.selectionEnd)>Number(target.selectionStart);}catch(e){return true;}}if(target?.isContentEditable){const sel=window.getSelection?.();return !!(sel&&sel.rangeCount&&!sel.isCollapsed&&target.contains(sel.anchorNode));}return false;}
  function keyboardCopy(ctx,cut=false){if(!ctx)return;if(ctx.kind==='cellblock')return copyCellBlockAt(blocks[ctx.blockIndex],ctx.path,ctx.itemIndex,cut);if(ctx.kind==='part')return copyPartAt(ctx.blockIndex,ctx.part,cut);if(ctx.kind==='inner')return copyInnerAtPath(blocks[ctx.blockIndex],ctx.path,ctx.index,cut);if(ctx.kind==='cell')return copyCellAtPath(blocks[ctx.blockIndex],ctx.path,cut);if(ctx.kind==='block')return copyBlockAt(ctx.blockIndex,cut);}
  function keyboardDuplicate(ctx){if(!ctx)return;if(ctx.kind==='cellblock')return duplicateCellBlockAt(blocks[ctx.blockIndex],ctx.path,ctx.itemIndex);if(ctx.kind==='part')return duplicatePartAt(ctx.blockIndex,ctx.part);if(ctx.kind==='inner')return duplicateInnerAtPath(blocks[ctx.blockIndex],ctx.path,ctx.index);if(ctx.kind==='cell')return duplicateCellAtPath(blocks[ctx.blockIndex],ctx.path);if(ctx.kind==='block')return duplicateBlockAt(ctx.blockIndex);}
  function keyboardPaste(ctx){
    const clip=getBuilderClipboard();if(!clip)return contextToast('No hay nada copiado');
    if(clip.kind==='block'){
      if(ctx?.kind==='cell')return pasteCellBlockAt(blocks[ctx.blockIndex],ctx.path,null,true);
      if(ctx?.kind==='cellblock')return pasteCellBlockAt(blocks[ctx.blockIndex],ctx.path,ctx.itemIndex,true);
      const at=Number.isFinite(ctx?.blockIndex)?ctx.blockIndex:selected;
      return pasteBlockAt(Number.isFinite(at)?at:Math.max(0,blocks.length-1),true);
    }
    if(clip.kind==='cellblock'){
      if(ctx?.kind==='cellblock')return pasteCellBlockAt(blocks[ctx.blockIndex],ctx.path,ctx.itemIndex,true);
      if(ctx?.kind==='cell')return pasteCellBlockAt(blocks[ctx.blockIndex],ctx.path,null,true);
      return contextToast('Selecciona una columna para pegar el elemento');
    }
    if(clip.kind==='part'){
      const label=partLabel(clip.data?.part||'content');
      if(ctx?.kind==='cell')return appendPartToCell(blocks[ctx.blockIndex],ctx.path,clip.data,label);
      if(ctx?.kind==='inner')return appendPartToCell(blocks[ctx.blockIndex],ctx.path,clip.data,label);
      const at=Number.isFinite(ctx?.blockIndex)?ctx.blockIndex:selected;
      return pastePartAsNewBlock(Number.isFinite(at)?at:Math.max(0,blocks.length-1),clip.data,label,true);
    }
    if(clip.kind==='inner'){
      if(ctx?.kind==='inner')return pasteInnerAtPath(blocks[ctx.blockIndex],ctx.path,ctx.index,true);
      if(ctx?.kind==='cell')return appendInnerToCell(blocks[ctx.blockIndex],ctx.path);
      return contextToast('Selecciona una columna para pegar este elemento interno');
    }
    if(clip.kind==='cell'){
      if(ctx?.kind==='cell')return pasteCellAtPath(blocks[ctx.blockIndex],ctx.path,true);
      return contextToast('Selecciona una columna para pegar la columna copiada');
    }
    contextToast('No se reconoce el contenido copiado');
  }
  function isInspectorField(target){return !!target?.closest?.('#inspector')&&!!target.matches?.('input,textarea,select,[contenteditable=true]');}
  function shouldUseNativeClipboard(target,key){
    if(isInspectorField(target))return true;
    if(target?.matches?.('input,textarea,select'))return true;
    const editable=target?.isContentEditable?target:target?.closest?.('[contenteditable="true"]');
    // Cuando el foco está dentro del contenido editable, copiar/cortar/pegar
    // pertenece al texto, no al portapapeles estructural del builder.
    if(editable&&(editable.dataset?.editing==='1'||hasNativeTextSelection(editable)||document.activeElement===editable))return true;
    return false;
  }
  document.addEventListener('keydown',e=>{
    const mod=e.ctrlKey||e.metaKey,key=String(e.key||'').toLowerCase();
    const ctx=keyboardContext();
    const builderHasSelection=!!(ctx&&ctx.kind!=='canvas');
    const targetInBuilder=root.contains(e.target)||page.contains(e.target)||inspector.contains(e.target);
    if(!targetInBuilder&&!builderHasSelection&&e.target!==document.body)return;
    if(e.key==='Escape'){hideContextMenu(true);if(previewModal?.classList.contains('open'))closePreview();return;}
    if(!mod)return;
    if(key==='z'){
      if(isInspectorField(e.target))return;
      e.preventDefault();e.stopPropagation();e.shiftKey?redo():undo();return;
    }
    if(key==='y'){
      if(isInspectorField(e.target))return;
      e.preventDefault();e.stopPropagation();redo();return;
    }
    if(key==='c'){
      if(shouldUseNativeClipboard(e.target,key))return;
      e.preventDefault();e.stopPropagation();keyboardCopy(ctx,false);return;
    }
    if(key==='x'){
      if(shouldUseNativeClipboard(e.target,key))return;
      e.preventDefault();e.stopPropagation();keyboardCopy(ctx,true);return;
    }
    if(key==='v'){
      // Dentro de un campo de texto editable, Ctrl/Cmd+V debe conservar el
      // comportamiento nativo del navegador: pegar en el cursor o reemplazar
      // exactamente la selección de texto. El clipboard del builder se usa
      // solo cuando no estamos editando texto.
      if(isInspectorField(e.target)||e.target?.matches?.('input,textarea,select'))return;
      if(e.target?.isContentEditable||e.target?.closest?.('[contenteditable="true"]'))return;
      e.preventDefault();e.stopPropagation();keyboardPaste(ctx);return;
    }
    if(key==='d'){
      if(isInspectorField(e.target))return;
      e.preventDefault();e.stopPropagation();keyboardDuplicate(ctx);return;
    }
  },true);
  document.addEventListener('keydown',e=>{
    const editable=e.target.matches?.('input,textarea,select,[contenteditable=true]')||e.target.isContentEditable;
    if((e.key==='Delete'||e.key==='Backspace')&&!editable&&(root.contains(e.target)||page.contains(e.target)||activeSelectionContext)){
      e.preventDefault();deleteActiveSelection();
    }
  },true);
  render();renderInspector();sync();
})();
