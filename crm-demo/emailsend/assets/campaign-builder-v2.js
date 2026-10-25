(() => {
'use strict';

const root=document.querySelector('[data-ob2]');
if(!root)return;

const form=document.getElementById('campaignForm');
const hidden=document.getElementById('blocks_json');
const page=document.getElementById('ob2Page');
const device=document.getElementById('ob2Device');
const inspector=document.getElementById('ob2Inspector');
const saveState=document.getElementById('ob2SaveState');
const previewModal=document.getElementById('ob2PreviewModal');
const previewFrame=document.getElementById('ob2PreviewFrame');
const previewForm=document.getElementById('ob2PreviewForm');
const previewJson=document.getElementById('ob2PreviewJson');
const codeModal=document.getElementById('ob2CodeModal');
const codeText=document.getElementById('ob2CodeText');

let blocks=[];
try{blocks=JSON.parse(hidden.value||'[]');if(!Array.isArray(blocks))blocks=[];}catch(e){blocks=[];}
let selected=blocks.length?0:-1;
let history=[];
let future=[];
let dirty=false;
let dragIndex=null;

const uid=()=>Math.random().toString(36).slice(2)+Date.now().toString(36);
const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
const strip=s=>String(s??'').replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim();
const clamp=(n,a,b)=>Math.max(a,Math.min(b,Number(n)||0));
const clone=o=>JSON.parse(JSON.stringify(o));
const ensureStyle=b=>b.style||(b.style={});
const px=v=>typeof v==='number'?v+'px':String(v||'');
const isOldPlaceholder=url=>/placehold\.co|placeholder/i.test(String(url||''));

function normalizeUrl(url){
  url=String(url||'').trim();
  if(!url)return '';
  const base=String(window.EMAILSEND_APP_URL||location.origin).replace(/\/+$/,'');

  let m=url.match(/builder-media\.php\?show=([^&]+)/i);
  if(m)return base+'/builder-media.php?show='+encodeURIComponent(decodeURIComponent(m[1]));

  /* Keep v0.10.0 dated builder-media URLs readable until the image is replaced. */
  m=url.match(/builder-media\.php\?f=([^&]+)/i);
  if(m)return base+'/builder-media.php?f='+encodeURIComponent(decodeURIComponent(m[1]));

  m=url.match(/(?:image\.php\?f=|media\.php\?f=|index\.php\?route=image_asset&f=)([^&]+)/i);
  if(m)return base+'/builder-media.php?f='+encodeURIComponent(decodeURIComponent(m[1]));

  m=url.match(/\/uploads\/(\d{4}\/\d{2}\/[a-f0-9]{16,128}\.(?:jpg|jpeg|png|gif|webp))/i);
  if(m)return base+'/builder-media.php?f='+encodeURIComponent(m[1]);

  m=url.match(/\/sendmail\/uploads\/(\d{4}\/\d{2}\/[a-f0-9]{16,128}\.(?:jpg|jpeg|png|gif|webp))/i);
  if(m)return base+'/builder-media.php?f='+encodeURIComponent(m[1]);

  return url;
}

function normalizeBlock(b){
  if(!b||typeof b!=='object')return makeBlock('text');
  if(!b.type)b.type='text';
  if(b.src)b.src=normalizeUrl(b.src);
  if(b.thumbnail)b.thumbnail=normalizeUrl(b.thumbnail);
  if(b.avatar)b.avatar=normalizeUrl(b.avatar);
  if(b.style&&b.style.backgroundImage)b.style.backgroundImage=normalizeUrl(b.style.backgroundImage);
  return b;
}
blocks=blocks.map(normalizeBlock);

const typeNames={
  header:'HEADER',hero:'HERO',title:'TÍTULO',text:'TEXTO',image:'IMAGEN',button:'BOTÓN',
  card:'TARJETA',quote:'DESTACADO',divider:'SEPARADOR',spacer:'ESPACIO',columns1:'1 COLUMNA',
  columns:'2 COLUMNAS',columns3:'3 COLUMNAS',social:'SOCIAL',footer:'FOOTER',html:'HTML',
  banner:'BANNER',iconbox:'ICON BOX',pricing:'PRICING',qr:'QR',video:'VIDEO'
};

function defaultStyle(type){
  const base={background:'#ffffff',color:'#1f2937',padding:'16px 20px',align:'left',fontFamily:'Ubuntu Sans',fontSize:'16px',fontWeight:'400',lineHeight:'1.6',letterSpacing:'0px',radius:'0px'};
  if(type==='title')Object.assign(base,{fontFamily:'Exo 2',fontSize:'30px',fontWeight:'800',lineHeight:'1.2',padding:'18px 24px'});
  if(type==='hero')Object.assign(base,{background:'#073e66',color:'#ffffff',fontFamily:'Exo 2',fontSize:'34px',fontWeight:'800',lineHeight:'1.15',padding:'48px 34px',radius:'0px',buttonBg:'#ff5300',buttonColor:'#ffffff',buttonRadius:'5px',textColor:'#ffffff',textSize:'16px'});
  if(type==='image')Object.assign(base,{padding:'10px 20px',align:'center',width:'100%',radius:'0px'});
  if(type==='button')Object.assign(base,{padding:'15px 20px',align:'center',background:'#ffffff',color:'#ffffff',fontSize:'14px',fontWeight:'700',radius:'5px',buttonBg:'#ff5300',buttonColor:'#ffffff'});
  if(type==='card')Object.assign(base,{padding:'16px 20px',fontFamily:'Exo 2',fontSize:'22px',fontWeight:'800',radius:'10px',cardBg:'#ffffff',border:'#dce5ec',textColor:'#516579',textSize:'15px',buttonBg:'#0074c7',buttonColor:'#ffffff'});
  if(type==='quote')Object.assign(base,{padding:'18px 24px',fontSize:'18px',fontStyle:'italic',accent:'#31b4e7'});
  if(type==='divider')Object.assign(base,{padding:'14px 20px',color:'#d7e3ea'});
  if(type==='spacer')Object.assign(base,{height:'30px',background:'#ffffff'});
  if(type==='footer')Object.assign(base,{background:'#07304f',color:'#dce9f1',padding:'28px 24px',align:'center',fontSize:'12px'});
  if(type==='banner')Object.assign(base,{background:'#00365f',color:'#ffffff',fontFamily:'Exo 2',fontSize:'28px',fontWeight:'800',padding:'46px 34px',textColor:'#eef8ff',buttonBg:'#ff5300',buttonColor:'#ffffff'});
  return base;
}

function makeBlock(type){
  const style=defaultStyle(type);
  const id=uid();
  switch(type){
    case 'header':return {id,type,src:'',alt:'Logo',menu1:'Inicio',url1:'#',menu2:'Servicios',url2:'#',menu3:'Contacto',url3:'#',button:'HABLEMOS',href:'#',style:{...style,background:'#ffffff',padding:'14px 22px',color:'#36556b',fontSize:'12px',fontWeight:'700',logoWidth:'150px',buttonBg:'#ff5300',buttonColor:'#fff',buttonRadius:'999px'}};
    case 'hero':return {id,type,src:'',title:'Tu gran mensaje',text:'Escribe una introducción breve, clara y atractiva.',button:'CONOCER MÁS',href:'#',style};
    case 'title':return {id,type,content:'Escribe aquí tu título',style};
    case 'text':return {id,type,content:'Escribe aquí el contenido principal de tu campaña.',style};
    case 'image':return {id,type,src:'',alt:'',style};
    case 'button':return {id,type,content:'CONOCER MÁS',href:'#',style};
    case 'card':return {id,type,title:'Título de la tarjeta',text:'Describe aquí el beneficio o contenido principal.',button:'VER MÁS',href:'#',style};
    case 'quote':return {id,type,content:'“Un mensaje destacado para reforzar tu campaña.”',style};
    case 'divider':return {id,type,style};
    case 'spacer':return {id,type,style};
    case 'columns1':return {id,type,cells:[cell('Columna 1')],style:{background:'#ffffff',padding:'0px'}};
    case 'columns':return {id,type,cells:[cell('Columna 1'),cell('Columna 2')],style:{background:'#ffffff',padding:'0px'}};
    case 'columns3':return {id,type,cells:[cell('Columna 1'),cell('Columna 2'),cell('Columna 3')],style:{background:'#ffffff',padding:'0px'}};
    case 'social':return {id,type,instagram:'',facebook:'',linkedin:'',website:'',style:{...style,align:'center',color:'#0074c7',padding:'18px 20px'}};
    case 'footer':return {id,type,content:'© Comercial EuroAndina · Todos los derechos reservados.<br>Si no deseas recibir más mensajes puedes darte de baja.',style};
    case 'html':return {id,type,content:'<div style="padding:24px;text-align:center">HTML personalizado</div>',style:{background:'#ffffff',padding:'0px'}};
    case 'banner':return {id,type,src:'',title:'Una oferta que merece atención',text:'Agrega una frase breve que explique el beneficio.',button:'CONOCER MÁS',href:'#',style};
    case 'iconbox':return {id,type,icon:'★',title:'Beneficio principal',text:'Explica brevemente esta característica.',style:{...style,background:'#ffffff',fontFamily:'Exo 2',fontSize:'20px',fontWeight:'800',radius:'10px',iconBg:'#eaf6fd',iconColor:'#0074c7',cardBg:'#f8fbfd',textColor:'#516579'}};
    case 'pricing':return {id,type,badge:'PLAN',plan:'Plan recomendado',price:'$0',period:'/ mes',features:'Característica uno\nCaracterística dos\nCaracterística tres',button:'COMENZAR',href:'#',style:{...style,align:'center',fontFamily:'Exo 2',fontSize:'23px',fontWeight:'800',radius:'12px',cardBg:'#ffffff',buttonBg:'#0074c7',buttonColor:'#ffffff'}};
    case 'qr':return {id,type,data:new URL('./',window.location.href).href,label:'Escanea el código',style:{...style,align:'center',qrSize:'170px',qrBg:'#ffffff'}};
    case 'video':return {id,type,thumbnail:'',href:'#',title:'Mira nuestro video',text:'Una descripción breve del contenido.',button:'VER VIDEO',style:{...style,fontFamily:'Exo 2',fontSize:'22px',fontWeight:'800',radius:'8px',textColor:'#516579'}};
    default:return {id,type:'text',content:'Nuevo bloque de texto',style:defaultStyle('text')};
  }
}
function cell(label){return {content:'<strong>'+label+'</strong><br>Contenido editable.',style:{background:'#ffffff',padding:'18px 18px',color:'#1f2937',align:'left',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.5',letterSpacing:'0px',margin:'0px',borderWidth:'0px',borderStyle:'solid',borderColor:'#dce5ec',verticalAlign:'top',minHeight:'100px',radius:'0px'},nested:null};}

const templates={
  newsletter:()=>[
    makeBlock('header'),
    Object.assign(makeBlock('title'),{content:'Novedades que vale la pena conocer'}),
    Object.assign(makeBlock('text'),{content:'Hola, compartimos contigo una selección de novedades y contenidos relevantes.'}),
    makeBlock('image'),
    makeBlock('divider'),
    makeBlock('footer')
  ],
  promo:()=>[
    Object.assign(makeBlock('hero'),{title:'Oferta especial por tiempo limitado',text:'Una propuesta directa para tus clientes.',button:'VER OFERTA'}),
    makeBlock('image'),
    Object.assign(makeBlock('title'),{content:'¿Por qué aprovechar esta oportunidad?'}),
    makeBlock('text'),
    makeBlock('button'),
    makeBlock('footer')
  ],
  corporate:()=>[
    makeBlock('header'),
    Object.assign(makeBlock('hero'),{title:'Soluciones para impulsar tu operación',text:'Tecnología, experiencia y acompañamiento.',button:'CONVERSEMOS'}),
    makeBlock('columns'),
    makeBlock('card'),
    makeBlock('footer')
  ]
};

function snapshot(){
  history.push(JSON.stringify(blocks));
  if(history.length>60)history.shift();
  future=[];
}
function markDirty(){
  dirty=true;
  saveState.textContent='Cambios sin guardar';
  saveState.classList.add('is-dirty');
}
function sync(){
  hidden.value=JSON.stringify(blocks);
  markDirty();
}
function restore(json){
  try{blocks=JSON.parse(json);if(!Array.isArray(blocks))blocks=[];}catch(e){return;}
  selected=blocks.length?Math.min(selected,blocks.length-1):-1;
  hidden.value=JSON.stringify(blocks);
  render();
  markDirty();
}
function undo(){
  if(!history.length)return;
  future.push(JSON.stringify(blocks));
  restore(history.pop());
}
function redo(){
  if(!future.length)return;
  history.push(JSON.stringify(blocks));
  restore(future.pop());
}

function blockOuterStyle(b){
  const s=ensureStyle(b);
  const bg=s.background||'#ffffff';
  return `background:${esc(bg)};`;
}
function renderImage(url,alt,width='100%',radius='0px'){
  url=normalizeUrl(url);
  if(!url || isOldPlaceholder(url))return `<div class="ob2-img-placeholder"><div><b>Sube una imagen</b><span>Selecciona este bloque y usa “Subir / reemplazar”.</span></div></div>`;
  return `<div class="ob2-img-wrap"><img src="${esc(url)}" alt="${esc(alt||'')}" style="width:${esc(width||'100%')};border-radius:${esc(radius||'0px')}" onload="this.closest('.ob2-block')&&this.closest('.ob2-block').classList.add('ob2-image-loaded')" onerror="this.style.display='none';this.parentNode.innerHTML='<div class=&quot;ob2-img-placeholder ob2-image-broken&quot;><div><b>Imagen no disponible</b><span>${esc(url)}</span></div></div>'"></div>`;
}
function renderBlockContent(b){
  const s=ensureStyle(b),pad=s.padding||'16px 20px',align=s.align||'left',color=s.color||'#1f2937',ff=s.fontFamily||'Ubuntu Sans',fs=s.fontSize||'16px',fw=s.fontWeight||'400',lh=s.lineHeight||'1.6',bg=s.background||'#fff',radius=s.radius||'0px';
  const common=`padding:${esc(pad)};text-align:${esc(align)};color:${esc(color)};font-family:${esc(ff)},Arial,sans-serif;font-size:${esc(fs)};font-weight:${esc(fw)};line-height:${esc(lh)};background:${esc(bg)};border-radius:${esc(radius)}`;
  switch(b.type){
    case 'title': return `<div class="ob2-render-text" style="${common}">${b.content||''}</div>`;
    case 'text': return `<div class="ob2-render-text" style="${common}">${b.content||''}</div>`;
    case 'image': return `<div style="padding:${esc(pad)};background:${esc(bg)};text-align:${esc(align)}">${renderImage(b.src,b.alt,s.width||'100%',radius)}</div>`;
    case 'logo': return `<div style="padding:${esc(pad)};background:${esc(bg)};text-align:${esc(align)}">${renderImage(b.src,b.alt,s.width||'180px','0px')}</div>`;
    case 'button': return `<div style="padding:${esc(pad)};background:${esc(bg)};text-align:${esc(align)}"><a class="ob2-btn-preview" href="javascript:void(0)" style="background:${esc(s.buttonBg||'#ff5300')};color:${esc(s.buttonColor||'#fff')};padding:12px 20px;border-radius:${esc(radius)};font:${esc(fw)} ${esc(fs)} ${esc(ff)},Arial,sans-serif">${esc(strip(b.content||'CONOCER MÁS'))}</a></div>`;
    case 'divider': return `<div style="padding:${esc(pad)};background:${esc(bg)}"><hr style="border:0;border-top:1px solid ${esc(color)};margin:0"></div>`;
    case 'spacer': return `<div style="height:${esc(s.height||'30px')};background:${esc(bg)}"></div>`;
    case 'quote': return `<div style="${common}"><div style="border-left:4px solid ${esc(s.accent||'#31b4e7')};padding-left:16px;font-style:italic">${b.content||''}</div></div>`;
    case 'card': return `<div style="padding:${esc(pad)};background:${esc(bg)}"><div style="padding:23px;border:1px solid ${esc(s.border||'#dce5ec')};border-radius:${esc(radius||'10px')};background:${esc(s.cardBg||'#fff')}"><h3 style="margin:0 0 9px;font:800 ${esc(fs)} Exo 2,Arial;color:${esc(color)}">${b.title||''}</h3><div style="color:${esc(s.textColor||'#516579')};font:15px/1.6 Ubuntu Sans,Arial">${b.text||''}</div><div style="margin-top:17px"><span style="display:inline-block;background:${esc(s.buttonBg||'#0074c7')};color:#fff;padding:10px 15px;border-radius:5px;font-weight:700">${esc(b.button||'VER MÁS')}</span></div></div></div>`;
    case 'header': return `<div style="display:flex;align-items:center;justify-content:space-between;padding:${esc(pad)};background:${esc(bg)}">${b.src?`<img src="${esc(normalizeUrl(b.src))}" style="max-width:${esc(s.logoWidth||'150px')};max-height:55px">`:`<strong style="color:#0074c7">LOGO</strong>`}<div style="font-size:${esc(fs)};color:${esc(color)}">${esc(b.menu1||'')} &nbsp; ${esc(b.menu2||'')} &nbsp; ${esc(b.menu3||'')} &nbsp; <span style="background:${esc(s.buttonBg||'#ff5300')};color:#fff;padding:8px 12px;border-radius:999px">${esc(b.button||'')}</span></div></div>`;
    case 'hero': {
      const bgimg=normalizeUrl(b.src);const bgcss=bgimg?`background-image:linear-gradient(rgba(0,47,87,.72),rgba(0,47,87,.72)),url('${esc(bgimg)}');background-size:cover;background-position:center;`:'';
      return `<div style="${common};${bgcss};padding:${esc(pad||'48px 34px')};color:${esc(color||'#fff')}"><h1 style="margin:0 0 12px;font:800 ${esc(fs)} Exo 2,Arial">${b.title||''}</h1><div style="font:16px/1.6 Ubuntu Sans,Arial;color:${esc(s.textColor||'#fff')}">${b.text||''}</div><div style="margin-top:18px"><span style="display:inline-block;background:${esc(s.buttonBg||'#ff5300')};color:${esc(s.buttonColor||'#fff')};padding:11px 18px;border-radius:${esc(s.buttonRadius||'5px')};font-weight:700">${esc(b.button||'CONOCER MÁS')}</span></div></div>`;
    }
    case 'banner': {
      const bgimg=normalizeUrl(b.src);const bgcss=bgimg?`background-image:linear-gradient(rgba(0,47,87,.62),rgba(0,47,87,.62)),url('${esc(bgimg)}');background-size:cover;background-position:center;`:'';
      return `<div style="${common};${bgcss};padding:${esc(pad)}"><h2 style="margin:0 0 10px;font:800 ${esc(fs)} Exo 2,Arial">${b.title||''}</h2><div style="color:${esc(s.textColor||'#eef8ff')}">${b.text||''}</div><div style="margin-top:18px"><span style="display:inline-block;background:${esc(s.buttonBg||'#ff5300')};color:#fff;padding:10px 17px;border-radius:999px;font-weight:700">${esc(b.button||'CONOCER MÁS')}</span></div></div>`;
    }
    case 'columns1':
    case 'columns':
    case 'columns3': {
      const cells=Array.isArray(b.cells)?b.cells:[cell('Columna')];const cls=cells.length===1?'cols-1':cells.length===3?'cols-3':'cols-2';
      return `<div class="ob2-columns-preview ${cls}" style="background:${esc(bg)}">${cells.map((c,i)=>`<div class="ob2-column-cell"><strong>Columna ${i+1}</strong>${c.content||''}</div>`).join('')}</div>`;
    }
    case 'social': return `<div style="${common}"><span style="color:#0074c7">Instagram</span> · <span style="color:#0074c7">Facebook</span> · <span style="color:#0074c7">LinkedIn</span> · <span style="color:#0074c7">Web</span></div>`;
    case 'footer': return `<div class="ob2-render-text" style="${common}">${b.content||''}</div>`;
    case 'iconbox': return `<div style="padding:${esc(pad)};background:${esc(bg)}"><div style="display:flex;gap:14px;padding:20px;border:1px solid #dce8f0;border-radius:${esc(radius||'10px')};background:${esc(s.cardBg||'#f8fbfd')}"><div style="width:48px;height:48px;border-radius:10px;background:${esc(s.iconBg||'#eaf6fd')};color:${esc(s.iconColor||'#0074c7')};display:grid;place-items:center;font-size:24px;font-weight:800">${esc(b.icon||'★')}</div><div><h3 style="margin:0 0 6px;font:800 ${esc(fs)} Exo 2,Arial">${b.title||''}</h3><div style="color:${esc(s.textColor||'#516579')}">${b.text||''}</div></div></div></div>`;
    case 'pricing': return `<div style="padding:${esc(pad)};background:${esc(bg)};text-align:center"><div style="padding:24px;border:1px solid #bcd9e9;border-radius:${esc(radius||'12px')};background:${esc(s.cardBg||'#fff')}"><small style="background:#ff5300;color:#fff;padding:5px 9px;border-radius:999px;font-weight:800">${esc(b.badge||'PLAN')}</small><h3 style="font:800 ${esc(fs)} Exo 2;margin:14px 0 8px">${esc(b.plan||'')}</h3><strong style="font:800 34px Exo 2;color:#0074c7">${esc(b.price||'')}</strong><span>${esc(b.period||'')}</span><div style="white-space:pre-line;text-align:left;margin:18px 0;color:#516579">${esc(b.features||'')}</div><span style="display:inline-block;background:#0074c7;color:#fff;padding:11px 18px;border-radius:999px;font-weight:700">${esc(b.button||'COMENZAR')}</span></div></div>`;
    case 'qr': return `<div style="${common};text-align:center"><div style="display:inline-grid;place-items:center;width:170px;height:170px;background:#fff;border:1px solid #dce5ec;color:#34566d;font:800 20px Exo 2">QR</div><div style="margin-top:8px">${esc(b.label||'')}</div></div>`;
    case 'video': return `<div style="padding:${esc(pad)};background:${esc(bg)}">${b.thumbnail?renderImage(b.thumbnail,'Video','100%',radius):`<div class="ob2-img-placeholder"><div><b>Miniatura de video</b><span>Sube una imagen de portada.</span></div></div>`}<h3 style="font:800 ${esc(fs)} Exo 2;margin:14px 0 7px">${b.title||''}</h3><div style="color:#516579">${b.text||''}</div></div>`;
    case 'html': return `<div style="${common}"><div style="border:1px dashed #9ebfd3;padding:12px;font:12px monospace;color:#4c697d;background:#f8fbfd">HTML PERSONALIZADO<br>${esc(strip(b.content).slice(0,180))}</div></div>`;
    default:return `<div style="${common}">${b.content||''}</div>`;
  }
}

function render(){
  blocks=blocks.map(normalizeBlock);
  hidden.value=JSON.stringify(blocks);
  page.innerHTML='';
  if(!blocks.length){
    page.innerHTML='<div class="ob2-empty"><div><strong>Tu campaña está vacía</strong><span>Agrega un bloque desde la izquierda o usa una plantilla.</span></div></div>';
    inspectorEmpty();
    return;
  }
  blocks.forEach((b,i)=>{
    const el=document.createElement('div');
    el.className='ob2-block'+(i===selected?' is-selected':'');
    el.dataset.index=i;
    el.draggable=true;
    el.style.cssText=blockOuterStyle(b);
    el.innerHTML=`<span class="ob2-block-tag">${esc(typeNames[b.type]||b.type.toUpperCase())}</span>
      <div class="ob2-block-tools">
        <button type="button" data-tool="up" title="Subir">↑</button>
        <button type="button" data-tool="down" title="Bajar">↓</button>
        <button type="button" data-tool="duplicate" title="Duplicar">⧉</button>
        <button type="button" data-tool="delete" class="danger" title="Eliminar">×</button>
      </div>${renderBlockContent(b)}`;
    el.addEventListener('click',e=>{
      const tool=e.target.closest('[data-tool]');
      if(tool){e.stopPropagation();handleTool(tool.dataset.tool,i);return;}
      selected=i;render();renderInspector();
    });
    el.addEventListener('dragstart',()=>{dragIndex=i;el.classList.add('is-dragging')});
    el.addEventListener('dragend',()=>{dragIndex=null;document.querySelectorAll('.ob2-block').forEach(x=>x.classList.remove('is-dragging','ob2-block-drop-before','ob2-block-drop-after'))});
    el.addEventListener('dragover',e=>{
      e.preventDefault();if(dragIndex===null||dragIndex===i)return;
      const r=el.getBoundingClientRect();const before=e.clientY<r.top+r.height/2;
      el.classList.toggle('ob2-block-drop-before',before);el.classList.toggle('ob2-block-drop-after',!before);
    });
    el.addEventListener('dragleave',()=>el.classList.remove('ob2-block-drop-before','ob2-block-drop-after'));
    el.addEventListener('drop',e=>{
      e.preventDefault();el.classList.remove('ob2-block-drop-before','ob2-block-drop-after');
      if(dragIndex===null||dragIndex===i)return;
      snapshot();
      const item=blocks.splice(dragIndex,1)[0];
      let target=i;if(dragIndex<i)target--;
      const r=el.getBoundingClientRect();if(e.clientY>=r.top+r.height/2)target++;
      blocks.splice(Math.max(0,target),0,item);selected=blocks.indexOf(item);sync();render();renderInspector();
    });
    page.appendChild(el);
  });
  renderInspector();
}

function handleTool(tool,i){
  if(tool==='up'&&i>0){snapshot();[blocks[i-1],blocks[i]]=[blocks[i],blocks[i-1]];selected=i-1;}
  if(tool==='down'&&i<blocks.length-1){snapshot();[blocks[i+1],blocks[i]]=[blocks[i],blocks[i+1]];selected=i+1;}
  if(tool==='duplicate'){snapshot();const cp=clone(blocks[i]);cp.id=uid();blocks.splice(i+1,0,cp);selected=i+1;}
  if(tool==='delete'){snapshot();blocks.splice(i,1);selected=blocks.length?Math.min(i,blocks.length-1):-1;}
  sync();render();
}

function inspectorEmpty(){
  inspector.innerHTML='<div class="ob2-inspector-empty"><div><strong>Propiedades</strong><span>Selecciona un bloque en el lienzo para editarlo.</span></div></div>';
}

function field(label,key,value,type='text',extra=''){
  return `<label class="ob2-field"><span>${label}</span><input ${type==='number'?'type="number"':'type="'+type+'"'} data-field="${key}" value="${esc(value??'')}" ${extra}></label>`;
}
function textarea(label,key,value){
  return `<label class="ob2-field"><span>${label}</span><textarea data-field="${key}">${esc(value??'')}</textarea></label>`;
}
function colorField(label,key,value){
  const v=/^#[0-9a-f]{6}$/i.test(String(value||''))?value:'#ffffff';
  return `<label class="ob2-field"><span>${label}</span><div class="ob2-color-row"><input type="color" data-color="${key}" value="${esc(v)}"><input data-field="${key}" value="${esc(value||v)}"></div></label>`;
}
function selectField(label,key,value,options){
  return `<label class="ob2-field"><span>${label}</span><select data-field="${key}">${options.map(o=>`<option value="${esc(o[0])}" ${String(value)===String(o[0])?'selected':''}>${esc(o[1])}</option>`).join('')}</select></label>`;
}
function commonInspector(b){
  const s=ensureStyle(b);
  return `<div class="ob2-group"><div class="ob2-group-title">ESTILO</div>
    ${colorField('Fondo','style.background',s.background||'#ffffff')}
    ${colorField('Color texto','style.color',s.color||'#1f2937')}
    ${selectField('Alineación','style.align',s.align||'left',[['left','Izquierda'],['center','Centro'],['right','Derecha']])}
    ${field('Padding','style.padding',s.padding||'16px 20px')}
    <div class="ob2-field-row">${field('Tamaño','style.fontSize',s.fontSize||'16px')}${field('Peso','style.fontWeight',s.fontWeight||'400')}</div>
    <div class="ob2-field-row">${field('Interlineado','style.lineHeight',s.lineHeight||'1.6')}${field('Radio','style.radius',s.radius||'0px')}</div>
  </div>`;
}
function imageUploadHtml(label='Subir / reemplazar imagen',target='image'){
  return `<div class="ob2-uploader-box"><div class="ob2-uploader-label">${esc(label)}</div><iframe class="ob2-uploader-frame" data-uploader-frame data-target="${esc(target)}" src="builder-uploader.php?target=${encodeURIComponent(target)}" title="${esc(label)}"></iframe><div class="ob2-upload-status" id="ob2UploadStatus"></div></div>`;
}
function renderInspector(){
  if(selected<0||!blocks[selected]){inspectorEmpty();return;}
  const b=blocks[selected],s=ensureStyle(b),type=b.type;
  let h=`<div class="ob2-inspector-title"><strong>Propiedades</strong><span>${esc(typeNames[type]||type)}</span></div>`;
  if(type==='title'||type==='text'||type==='quote'||type==='footer')h+=textarea(type==='title'?'Título':'Contenido','content',b.content||'');
  else if(type==='image'||type==='logo'){
    h+=field('URL imagen','src',b.src||'')+field('Texto alternativo','alt',b.alt||'')+field('Ancho','style.width',s.width||'100%')+imageUploadHtml();
    if(b.src)h+=`<button type="button" class="ob2-mini-btn" data-open-image>Ver imagen actual</button>`;
  } else if(type==='button'){
    h+=field('Texto del botón','content',strip(b.content||''))+field('Enlace','href',b.href||'#')+colorField('Color botón','style.buttonBg',s.buttonBg||'#ff5300')+colorField('Color texto','style.buttonColor',s.buttonColor||'#ffffff');
  } else if(type==='hero'||type==='banner'){
    h+=field('Título','title',strip(b.title||''))+textarea('Texto','text',strip(b.text||''))+field('Texto botón','button',strip(b.button||''))+field('Enlace','href',b.href||'#')+field('Imagen de fondo','src',b.src||'')+imageUploadHtml('Subir / reemplazar fondo')+colorField('Color botón','style.buttonBg',s.buttonBg||'#ff5300');
  } else if(type==='card'){
    h+=field('Título','title',strip(b.title||''))+textarea('Texto','text',strip(b.text||''))+field('Botón','button',strip(b.button||''))+field('Enlace','href',b.href||'#')+colorField('Fondo tarjeta','style.cardBg',s.cardBg||'#ffffff')+colorField('Color botón','style.buttonBg',s.buttonBg||'#0074c7');
  } else if(type==='header'){
    h+=field('URL logo','src',b.src||'')+imageUploadHtml('Subir / reemplazar logo')+field('Menú 1','menu1',b.menu1||'')+field('URL 1','url1',b.url1||'#')+field('Menú 2','menu2',b.menu2||'')+field('URL 2','url2',b.url2||'#')+field('Menú 3','menu3',b.menu3||'')+field('URL 3','url3',b.url3||'#')+field('Botón','button',b.button||'')+field('Enlace botón','href',b.href||'#')+field('Ancho logo','style.logoWidth',s.logoWidth||'150px');
  } else if(type==='social'){
    h+=field('Instagram','instagram',b.instagram||'')+field('Facebook','facebook',b.facebook||'')+field('LinkedIn','linkedin',b.linkedin||'')+field('Sitio web','website',b.website||'');
  } else if(type==='spacer'){
    h+=field('Altura','style.height',s.height||'30px');
  } else if(type==='divider'){
    h+=colorField('Color línea','style.color',s.color||'#d7e3ea');
  } else if(type==='columns1'||type==='columns'||type==='columns3'){
    const cells=Array.isArray(b.cells)?b.cells:[];
    h+='<div class="ob2-group"><div class="ob2-group-title">COLUMNAS</div>';
    cells.forEach((c,i)=>{h+=textarea('Contenido columna '+(i+1),'cell.'+i+'.content',c.content||'')+colorField('Fondo columna '+(i+1),'cell.'+i+'.style.background',(c.style||{}).background||'#ffffff');});
    h+='</div>';
  } else if(type==='html'){
    h+=textarea('HTML personalizado','content',b.content||'');
  } else if(type==='iconbox'){
    h+=field('Icono','icon',b.icon||'★')+field('Título','title',b.title||'')+textarea('Texto','text',b.text||'');
  } else if(type==='pricing'){
    h+=field('Badge','badge',b.badge||'')+field('Plan','plan',b.plan||'')+field('Precio','price',b.price||'')+field('Periodo','period',b.period||'')+textarea('Características (una por línea)','features',b.features||'')+field('Botón','button',b.button||'')+field('Enlace','href',b.href||'#');
  } else if(type==='qr'){
    h+=field('URL / dato QR','data',b.data||'')+field('Texto','label',b.label||'');
  } else if(type==='video'){
    h+=field('URL miniatura','thumbnail',b.thumbnail||'')+imageUploadHtml('Subir miniatura','thumbnail')+field('URL video','href',b.href||'#')+field('Título','title',b.title||'')+textarea('Texto','text',b.text||'');
  }
  h+=commonInspector(b);
  h+=`<div class="ob2-inspector-actions"><button type="button" class="ob2-mini-btn" data-inspector-duplicate>Duplicar</button><button type="button" class="ob2-mini-btn danger" data-inspector-delete>Eliminar</button></div>`;
  inspector.innerHTML=h;
  bindInspector();
}

function getPath(obj,path){
  return path.split('.').reduce((a,k)=>a&&a[k],obj);
}
function setPath(obj,path,value){
  const parts=path.split('.');let cur=obj;
  if(parts[0]==='cell'){
    const i=Number(parts[1]);cur=obj.cells[i];parts.splice(0,2);
  }
  for(let i=0;i<parts.length-1;i++){
    const p=parts[i];if(!cur[p]||typeof cur[p]!=='object')cur[p]={};cur=cur[p];
  }
  cur[parts[parts.length-1]]=value;
}
function bindInspector(){
  const b=blocks[selected];if(!b)return;
  inspector.querySelectorAll('[data-field]').forEach(el=>{
    el.addEventListener('change',()=>{
      snapshot();setPath(b,el.dataset.field,el.value);sync();render();
    });
    if(el.tagName==='TEXTAREA'||el.type==='text'||el.type==='url'||el.type==='email'){
      el.addEventListener('input',()=>{
        setPath(b,el.dataset.field,el.value);hidden.value=JSON.stringify(blocks);markDirty();
        const idx=selected;const blockEl=page.querySelector(`[data-index="${idx}"]`);
        if(blockEl)blockEl.innerHTML=`<span class="ob2-block-tag">${esc(typeNames[b.type]||b.type.toUpperCase())}</span><div class="ob2-block-tools"><button type="button" data-tool="up">↑</button><button type="button" data-tool="down">↓</button><button type="button" data-tool="duplicate">⧉</button><button type="button" data-tool="delete" class="danger">×</button></div>${renderBlockContent(b)}`;
      });
    }
  });
  inspector.querySelectorAll('[data-color]').forEach(el=>{
    el.addEventListener('input',()=>{
      const path=el.dataset.color;const text=inspector.querySelector(`[data-field="${path}"]`);
      if(text)text.value=el.value;setPath(b,path,el.value);hidden.value=JSON.stringify(blocks);markDirty();render();
    });
  });
  const uploaderFrame=inspector.querySelector('[data-uploader-frame]');
  if(uploaderFrame){
    const status=document.getElementById('ob2UploadStatus');
    if(status){status.textContent='Selecciona la imagen dentro del cuadro superior.';status.className='ob2-upload-status is-show';}
  }
  const open=inspector.querySelector('[data-open-image]');
  if(open)open.addEventListener('click',()=>window.open(normalizeUrl(b.src),'_blank','noopener'));
  const dup=inspector.querySelector('[data-inspector-duplicate]');
  if(dup)dup.addEventListener('click',()=>handleTool('duplicate',selected));
  const del=inspector.querySelector('[data-inspector-delete]');
  if(del)del.addEventListener('click',()=>handleTool('delete',selected));
}

window.addEventListener('message',function(ev){
  if(ev.origin!==location.origin)return;
  const d=ev.data||{};
  if(d.source!=='EMAILSEND_BUILDER_IFRAME_UPLOADER')return;

  const status=document.getElementById('ob2UploadStatus');
  if(!d.ok){
    if(status){
      status.textContent=d.message||'No se pudo cargar la imagen.';
      status.className='ob2-upload-status is-show err';
    }
    return;
  }

  const b=blocks[selected];
  if(!b)return;

  const url=normalizeUrl(d.url||'');
  if(!url){
    if(status){
      status.textContent='El uploader no devolvió una URL válida.';
      status.className='ob2-upload-status is-show err';
    }
    return;
  }

  snapshot();
  if(d.target==='thumbnail' || b.type==='video') b.thumbnail=url;
  else b.src=url;

  hidden.value=JSON.stringify(blocks);
  markDirty();
  render();
  renderInspector();

  setTimeout(()=>{
    const s=document.getElementById('ob2UploadStatus');
    if(s){
      s.textContent='✓ Imagen guardada e insertada en el bloque.';
      s.className='ob2-upload-status is-show ok';
    }
  },30);
});

function addBlock(type,index=null){
  snapshot();const b=makeBlock(type);
  if(index===null||index<0||index>blocks.length)blocks.push(b);else blocks.splice(index,0,b);
  selected=blocks.indexOf(b);sync();render();
}

document.querySelectorAll('[data-ob2-add]').forEach(btn=>{
  btn.draggable=true;
  btn.addEventListener('click',()=>addBlock(btn.dataset.ob2Add));
  btn.addEventListener('dragstart',e=>{e.dataTransfer.setData('text/ob2-type',btn.dataset.ob2Add);e.dataTransfer.effectAllowed='copy';});
});
page.addEventListener('dragover',e=>e.preventDefault());
page.addEventListener('drop',e=>{
  const type=e.dataTransfer.getData('text/ob2-type');if(!type)return;
  e.preventDefault();addBlock(type);
});

document.querySelectorAll('[data-ob2-template]').forEach(btn=>btn.addEventListener('click',()=>{
  const key=btn.dataset.ob2Template;if(!templates[key])return;
  const apply=()=>{snapshot();blocks=templates[key]();selected=0;sync();render();};
  if(blocks.length&&window.emailSendConfirm)window.emailSendConfirm({variant:'warning',title:'Aplicar plantilla',message:'¿Reemplazar el contenido actual por esta plantilla?',note:'Puedes deshacer inmediatamente con Ctrl+Z.',label:'Sí, aplicar'}).then(ok=>{if(ok)apply();});else apply();
}));

document.getElementById('ob2Undo').addEventListener('click',undo);
document.getElementById('ob2Redo').addEventListener('click',redo);
document.getElementById('ob2Desktop').addEventListener('click',e=>{device.classList.remove('is-mobile');document.getElementById('ob2Desktop').classList.add('is-active');document.getElementById('ob2Mobile').classList.remove('is-active');});
document.getElementById('ob2Mobile').addEventListener('click',e=>{device.classList.add('is-mobile');document.getElementById('ob2Mobile').classList.add('is-active');document.getElementById('ob2Desktop').classList.remove('is-active');});

document.getElementById('ob2Preview').addEventListener('click',()=>{
  hidden.value=JSON.stringify(blocks);
  previewJson.value=hidden.value;
  previewModal.classList.add('is-open');
  previewForm.submit();
});
document.querySelectorAll('[data-ob2-preview-close]').forEach(x=>x.addEventListener('click',()=>previewModal.classList.remove('is-open')));
document.getElementById('ob2PreviewDesktop').addEventListener('click',()=>previewModal.querySelector('.ob2-preview-body').classList.remove('is-mobile'));
document.getElementById('ob2PreviewMobile').addEventListener('click',()=>previewModal.querySelector('.ob2-preview-body').classList.add('is-mobile'));

document.getElementById('ob2ImportHtml').addEventListener('click',()=>{codeText.value='';codeModal.classList.add('is-open');codeText.focus();});
document.querySelectorAll('[data-ob2-code-close]').forEach(x=>x.addEventListener('click',()=>codeModal.classList.remove('is-open')));
document.getElementById('ob2ApplyHtml').addEventListener('click',()=>{
  const html=codeText.value.trim();if(!html)return;
  snapshot();const b=makeBlock('html');b.content=html;blocks.push(b);selected=blocks.length-1;sync();render();codeModal.classList.remove('is-open');
});

form.addEventListener('submit',()=>{
  hidden.value=JSON.stringify(blocks);
  saveState.textContent='Guardando…';
  saveState.classList.remove('is-dirty');
});

document.addEventListener('keydown',e=>{
  const tag=(e.target.tagName||'').toLowerCase();const editing=['input','textarea','select'].includes(tag)||e.target.isContentEditable;
  if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='z'&&!editing){e.preventDefault();e.shiftKey?redo():undo();}
  if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='y'&&!editing){e.preventDefault();redo();}
  if(e.key==='Escape'){previewModal.classList.remove('is-open');codeModal.classList.remove('is-open');}
});

render();
saveState.textContent='Editor listo';
})();
