(() => {
'use strict';
const root=document.getElementById('cb3Root');if(!root)return;
const form=document.getElementById('cb3Form'),hidden=document.getElementById('cb3Blocks'),
canvas=document.getElementById('cb3Canvas'),device=document.getElementById('cb3Device'),
inspector=document.getElementById('cb3Inspector'),dirty=document.getElementById('cb3Dirty'),
previewModal=document.getElementById('cb3PreviewModal'),previewForm=document.getElementById('cb3PreviewForm'),
previewJson=document.getElementById('cb3PreviewJson'),htmlModal=document.getElementById('cb3HtmlModal'),
htmlCode=document.getElementById('cb3HtmlCode');

let rawBlocks=[];try{rawBlocks=JSON.parse(hidden.value||'[]');if(!Array.isArray(rawBlocks))rawBlocks=[];}catch(e){rawBlocks=[]}
const defaultDocumentSettings=()=>({backgroundColor:'#eef5f8',backgroundImage:'',backgroundSize:'cover',backgroundPosition:'center top',contentBackground:'#ffffff',contentBackgroundImage:'',contentWidth:680,outerPadding:'24px 16px',contentRadius:'0px'});
let documentSettings=defaultDocumentSettings();
const docMeta=rawBlocks.find(x=>x&&x.type==='__document');
if(docMeta&&docMeta.settings&&typeof docMeta.settings==='object')documentSettings={...documentSettings,...docMeta.settings};
let blocks=rawBlocks.filter(x=>!(x&&x.type==='__document'));
let selectedId=null,editingDocument=false,history=[],future=[],dragPayload=null,columnResize=null,heightResize=null;

const origin=location.origin;
const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
const strip=s=>String(s??'').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim();
const clone=o=>JSON.parse(JSON.stringify(o));
const uid=()=>Date.now().toString(36)+Math.random().toString(36).slice(2);
const S=b=>b.style||(b.style={});
const clamp=(n,min,max)=>Math.max(min,Math.min(max,Number(n)||0));
const px=n=>Math.round(Number(n)||0)+'px';
const labels={header:'HEADER',hero:'HERO',title:'TÍTULO',text:'TEXTO',image:'IMAGEN',logo:'LOGO',button:'BOTÓN',card:'TARJETA',quote:'DESTACADO',divider:'SEPARADOR',spacer:'ESPACIO',columns1:'1 COLUMNA',columns:'2 COLUMNAS',columns3:'3 COLUMNAS',social:'SOCIAL',footer:'FOOTER',html:'HTML',banner:'BANNER',iconbox:'ICON BOX',pricing:'PRICING',video:'VIDEO',qr:'QR',product:'PRODUCTO',testimonial:'TESTIMONIO',stats:'ESTADÍSTICAS',iconlist:'LISTA ICONOS'};

function normalizeUrl(url){
  url=String(url||'').trim();if(!url)return '';
  const m=url.match(/builder3-media\.php\?show=([^&]+)/i);
  return m?origin+'/builder3-media.php?show='+encodeURIComponent(decodeURIComponent(m[1])):url;
}
function normalizeCell(c){
  if(!c||typeof c!=='object')c={};
  if(!c.id)c.id=uid();
  if(!c.style)c.style={background:'#ffffff',padding:'16px',margin:'0px',minHeight:'120px'};
  if(!Array.isArray(c.items))c.items=[];
  c.items=c.items.map(normalizeBlock);
  if(!Number(c.width))c.width=0;
  return c;
}
function cleanLegalContamination(value){
  if(typeof value!=='string')return value;
  let v=value;
  v=v.replace(/\s*<a[^>]*>\s*Pol[ií]tica de Privacidad\s*<\/a>\s*/gi,' ');
  v=v.replace(/\s*<a[^>]*>\s*Darse de baja\s*<\/a>\s*/gi,' ');
  v=v.replace(/\s*Pol[ií]tica de Privacidad\s*[·|\-–—]?\s*Darse de baja\s*/gi,' ');
  v=v.replace(/\s*Pol[ií]tica de Privacidad\s*/gi,' ');
  v=v.replace(/\s*Darse de baja\s*/gi,' ');
  v=v.replace(/(?:<br\s*\/?>\s*){2,}/gi,'<br>');
  return v.replace(/\s{2,}/g,' ').trim();
}
function normalizeBlock(b){
  if(!b||typeof b!=='object')b=makeBlock('text');
  if(!b.id)b.id=uid();if(!b.type)b.type='text';if(!b.style)b.style={};
  if(b.type!=='footer'){
    for(const key of ['content','title','text']){
      if(typeof b[key]==='string')b[key]=cleanLegalContamination(b[key]);
    }
  }
  if(b.src)b.src=normalizeUrl(b.src);if(b.thumbnail)b.thumbnail=normalizeUrl(b.thumbnail);if(b.avatar)b.avatar=normalizeUrl(b.avatar);
  if(['columns1','columns','columns3'].includes(b.type)){
    if(!Array.isArray(b.cells)||!b.cells.length){
      const n=b.type==='columns1'?1:b.type==='columns3'?3:2;
      b.cells=Array.from({length:n},(_,i)=>makeCell('Columna '+(i+1),100/n));
    }
    b.cells=b.cells.map(normalizeCell);normalizeWidths(b);
  }
  return b;
}
function walk(arr,fn,parentCell=null){
  for(let i=0;i<arr.length;i++){
    const b=arr[i];if(fn(b,arr,i,parentCell))return true;
    if(['columns1','columns','columns3'].includes(b.type)){
      for(const cell of b.cells||[])if(walk(cell.items||[],fn,cell))return true;
    }
  }return false;
}
function findBlock(id){let found=null;walk(blocks,(b,arr,i,cell)=>{if(b.id===id){found={block:b,array:arr,index:i,parentCell:cell};return true}});return found}
function findCell(id){let found=null;walk(blocks,b=>{if(['columns1','columns','columns3'].includes(b.type)){const c=(b.cells||[]).find(x=>x.id===id);if(c){found={cell:c,columns:b};return true}}});return found}
function ensureIds(){blocks=blocks.map(normalizeBlock);if(!selectedId&&blocks.length)selectedId=blocks[0].id}
blocks=blocks.map(normalizeBlock);ensureIds();
function serializedBlocks(){return JSON.stringify([{type:'__document',settings:documentSettings},...blocks])}
function stateJSON(){return JSON.stringify({blocks,documentSettings,selectedId,editingDocument})}
function blockContainsCell(block,cellId){
  if(!block||!['columns1','columns','columns3'].includes(block.type))return false;
  for(const c of block.cells||[]){
    if(c.id===cellId)return true;
    for(const item of c.items||[])if(blockContainsCell(item,cellId))return true;
  }
  return false;
}

function makeCell(label,width){return{id:uid(),width:width||0,content:'',items:[],style:{background:'#ffffff',padding:'16px',margin:'0px',color:'#1f2937',align:'left',fontFamily:'Ubuntu Sans',fontSize:'15px',fontWeight:'400',lineHeight:'1.5',letterSpacing:'0px',borderWidth:'0px',borderStyle:'solid',borderColor:'#dce5ec',verticalAlign:'top',minHeight:'120px',radius:'0px'}}}
function defaultStyle(type){
  const s={background:'#ffffff',color:'#1f2937',padding:'16px 20px',margin:'0px',align:'left',fontFamily:'Ubuntu Sans',fontSize:'16px',fontWeight:'400',lineHeight:'1.55',letterSpacing:'0px',radius:'0px',blockWidth:'100',blockAlign:'center',minHeight:'0px'};
  if(type==='title')Object.assign(s,{fontFamily:'Exo 2',fontSize:'30px',fontWeight:'800',lineHeight:'1.18'});
  if(type==='hero')Object.assign(s,{background:'#003d68',color:'#fff',padding:'50px 34px',fontFamily:'Exo 2',fontSize:'34px',fontWeight:'800',textColor:'#fff',textSize:'16px',buttonBg:'#ff5300',buttonColor:'#fff',buttonRadius:'5px',minHeight:'300px'});
  if(type==='image'||type==='logo')Object.assign(s,{align:'center',width:type==='logo'?'180px':'100%',padding:'10px 20px'});
  if(type==='button')Object.assign(s,{align:'center',buttonBg:'#ff5300',buttonColor:'#fff',fontSize:'14px',fontWeight:'700',radius:'5px'});
  if(type==='footer')Object.assign(s,{background:'#07304f',color:'#dce9f1',align:'center',fontSize:'12px',padding:'28px 24px'});
  if(type==='banner')Object.assign(s,{background:'#00365f',color:'#fff',padding:'45px 34px',fontFamily:'Exo 2',fontSize:'28px',fontWeight:'800',textColor:'#eef8ff',buttonBg:'#ff5300',buttonColor:'#fff',minHeight:'240px'});
  return s;
}
function makeBlock(type){
  const id=uid(),s=defaultStyle(type);
  switch(type){
    case'header':return{id,type,src:'',alt:'Logo',menu1:'Inicio',url1:'#',menu2:'Servicios',url2:'#',menu3:'Contacto',url3:'#',button:'HABLEMOS',href:'#',style:{...s,background:'#fff',color:'#36556b',fontSize:'12px',fontWeight:'700',logoWidth:'150px',buttonBg:'#ff5300',buttonColor:'#fff',buttonRadius:'999px'}};
    case'hero':return{id,type,src:'',title:'Tu gran mensaje',text:'Escribe una introducción breve y atractiva.',button:'CONOCER MÁS',href:'#',style:s};
    case'title':return{id,type,content:'Escribe aquí tu título',style:s};
    case'text':return{id,type,content:'Escribe aquí el contenido principal de tu campaña.',style:s};
    case'image':return{id,type,src:'',alt:'',style:s};
    case'logo':return{id,type,src:'',alt:'Logo',style:s};
    case'button':return{id,type,content:'CONOCER MÁS',href:'#',style:s};
    case'card':return{id,type,title:'Título de la tarjeta',text:'Describe aquí el beneficio principal.',button:'VER MÁS',href:'#',style:{...s,fontFamily:'Exo 2',fontSize:'22px',fontWeight:'800',cardBg:'#fff',border:'#dce5ec',textColor:'#516579',buttonBg:'#0074c7',buttonColor:'#fff',radius:'10px'}};
    case'quote':return{id,type,content:'“Un mensaje destacado para reforzar tu campaña.”',style:{...s,fontSize:'18px',accent:'#31b4e7'}};
    case'divider':return{id,type,iconEnabled:false,icon:'◆',iconPosition:'center',iconBg:'#ffffff',iconColor:'#ff5300',iconSize:26,lineThickness:1,style:{...s,color:'#d7e3ea',padding:'14px 20px'}};
    case'spacer':return{id,type,style:{...s,height:'30px',padding:'0px'}};
    case'columns1':return{id,type,cells:[makeCell('Columna 1',100)],style:{...s,padding:'0px'}};
    case'columns':return{id,type,cells:[makeCell('Columna 1',50),makeCell('Columna 2',50)],style:{...s,padding:'0px'}};
    case'columns3':return{id,type,cells:[makeCell('Columna 1',33),makeCell('Columna 2',33),makeCell('Columna 3',34)],style:{...s,padding:'0px'}};
    case'social':return{id,type,displayMode:'iconsText',iconShape:'circle',iconSize:30,instagram:'',facebook:'',linkedin:'',website:'',style:{...s,align:'center',color:'#0074c7'}};
    case'footer':return{id,type,content:'© ONE HUB · DEMO — Contenido ficticio.',showPrivacy:true,privacyLabel:'Política de Privacidad',privacyUrl:'privacy.php',showUnsubscribe:true,unsubscribeLabel:'Darse de baja',style:s};
    case'html':return{id,type,content:'<div style="padding:24px;text-align:center">HTML personalizado</div>',style:{...s,padding:'0px'}};
    case'banner':return{id,type,src:'',title:'Una oferta que merece atención',text:'Agrega una frase breve que explique el beneficio.',button:'CONOCER MÁS',href:'#',style:s};
    case'iconbox':return{id,type,icon:'★',title:'Beneficio principal',text:'Explica brevemente esta característica.',style:{...s,fontFamily:'Exo 2',fontSize:'20px',fontWeight:'800',iconBg:'#eaf6fd',iconColor:'#0074c7',cardBg:'#f8fbfd',textColor:'#516579',radius:'10px'}};
    case'pricing':return{id,type,badge:'PLAN',plan:'Plan recomendado',price:'$0',period:'/ mes',features:'Característica uno\nCaracterística dos\nCaracterística tres',button:'COMENZAR',href:'#',style:{...s,align:'center',fontFamily:'Exo 2',fontSize:'23px',fontWeight:'800',cardBg:'#fff',buttonBg:'#0074c7',buttonColor:'#fff',radius:'12px'}};
    case'video':return{id,type,thumbnail:'',href:'#',title:'Mira nuestro video',text:'Una descripción breve del contenido.',button:'VER VIDEO',style:{...s,fontFamily:'Exo 2',fontSize:'22px',fontWeight:'800',radius:'8px',textColor:'#516579'}};
    case'qr':return{id,type,data:new URL('./',window.location.href).href,label:'Escanea el código',style:{...s,align:'center',qrSize:'170px',qrBg:'#fff'}};
    case'product':return{id,type,src:'',name:'Producto destacado',price:'$99.990',oldPrice:'',text:'Descripción breve del producto y su principal beneficio.',button:'COMPRAR',href:'#',style:{...s,fontFamily:'Exo 2',fontSize:'22px',fontWeight:'800',priceColor:'#ff5300',priceSize:'25px',cardBg:'#fff',border:'#dce8f0',buttonBg:'#0074c7',buttonColor:'#fff',radius:'10px'}};
    case'testimonial':return{id,type,avatar:'',stars:5,quote:'“Una experiencia excelente. El resultado superó nuestras expectativas.”',author:'Nombre Apellido',role:'Cargo · Empresa',style:{...s,fontSize:'17px',cardBg:'#f8fbfd',border:'#dce8f0',accent:'#f6bd00',radius:'12px',avatarSize:'64px'}};
    case'stats':return{id,type,value1:'98%',label1:'Satisfacción',value2:'+250',label2:'Clientes',value3:'24/7',label3:'Soporte',style:{...s,align:'center',fontFamily:'Exo 2',fontSize:'28px',fontWeight:'800',cardBg:'#f8fbfd',textColor:'#6b7f8f',radius:'10px'}};
    case'iconlist':return{id,type,icon:'✓',items:'Beneficio principal\nSegundo beneficio\nTercer beneficio',style:{...s,iconBg:'#eef9e7',iconColor:'#73bc25'}};
    default:return{id,type:'text',content:'Nuevo bloque de texto',style:defaultStyle('text')};
  }
}
function normalizeWidths(b){
  if(!b||!Array.isArray(b.cells)||!b.cells.length)return;
  let vals=b.cells.map(c=>Math.max(0,Number(c.width)||0)),sum=vals.reduce((a,v)=>a+v,0);
  if(sum<=0){vals=vals.map(()=>1);sum=vals.length}
  const raw=vals.map(v=>v/sum*100),ints=raw.map(v=>Math.floor(v));
  let remain=100-ints.reduce((a,v)=>a+v,0);
  const order=raw.map((v,i)=>({i,r:v-Math.floor(v)})).sort((a,z)=>z.r-a.r);
  for(let k=0;k<remain;k++)ints[order[k%order.length].i]++;
  b.cells.forEach((c,i)=>c.width=ints[i]);
}
function snapshot(){history.push(stateJSON());if(history.length>80)history.shift();future=[]}
function sync(){hidden.value=serializedBlocks();dirty.textContent='Cambios sin guardar';dirty.className='cb3-dirty'}
function restore(raw){const st=JSON.parse(raw);blocks=(st.blocks||[]).map(normalizeBlock);documentSettings={...defaultDocumentSettings(),...(st.documentSettings||{})};selectedId=st.selectedId||blocks[0]?.id||null;editingDocument=!!st.editingDocument;ensureIds();sync();render()}
function undo(){if(!history.length)return;future.push(stateJSON());restore(history.pop())}
function redo(){if(!future.length)return;history.push(stateJSON());restore(future.pop())}

function renderImage(url,alt,width='100%',radius='0px'){
  url=normalizeUrl(url);if(!url)return`<div class="cb3-img-placeholder"><div><strong>Sube una imagen</strong><span>Usa Propiedades para cargarla.</span></div></div>`;
  return`<div class="cb3-img"><img src="${esc(url)}" alt="${esc(alt||'')}" style="width:${esc(width)};border-radius:${esc(radius)}" onerror="this.style.display='none';this.parentNode.innerHTML='<div class=&quot;cb3-img-placeholder&quot;><strong>Imagen no disponible</strong></div>'"></div>`;
}
function socialPreview(b,s,ff,fs,fw){
  const mode=b.displayMode||'iconsText',shape=b.iconShape||'circle',radius=shape==='square'?'5px':(shape==='rounded'?'9px':'50%'),sz=clamp(b.iconSize||30,22,48);
  const data={instagram:['◎','#E1306C','Instagram'],facebook:['f','#1877F2','Facebook'],linkedin:['in','#0A66C2','LinkedIn'],website:['↗','#0074C7','Web']};
  const nodes=Object.entries(data).filter(([k])=>b[k]).map(([k,[ico,bg,label]])=>{
    const icon=mode==='text'?'':`<span style="display:inline-grid;place-items:center;width:${sz}px;height:${sz}px;border-radius:${radius};background:${bg};color:#fff;font-weight:800">${ico}</span>`;
    const text=mode==='icons'?'':`<span style="margin-left:${mode==='iconsText'?7:0}px;color:${esc(s.color||'#0074c7')};font-family:${esc(ff)},Arial,sans-serif;font-size:${esc(fs)};font-weight:${esc(fw)}">${label}</span>`;
    return`<span style="display:inline-flex;align-items:center;margin:5px 7px">${icon}${text}</span>`;
  });
  return nodes.length?nodes.join(''):'<span style="color:#8ca0ae">Configura tus redes sociales en Propiedades</span>';
}
function dividerPreview(b,s){
  const color=s.color||'#d7e3ea',th=clamp(b.lineThickness||1,1,8);
  if(!b.iconEnabled)return`<hr style="border:0;border-top:${th}px solid ${esc(color)};margin:0">`;
  const pos=b.iconPosition||'center',sz=clamp(b.iconSize||26,18,44),icon=`<span style="display:grid;place-items:center;flex:0 0 ${sz}px;width:${sz}px;height:${sz}px;border-radius:50%;background:${esc(b.iconBg||'#fff')};color:${esc(b.iconColor||'#ff5300')};font-weight:800">${esc(b.icon||'◆')}</span>`,line=`<span style="height:${th}px;background:${esc(color)};flex:1"></span>`;
  return`<div style="display:flex;align-items:center;gap:8px">${pos==='start'?icon+line:pos==='end'?line+icon:line+icon+line}</div>`;
}
function developerLockedHtml(){
  return`<div class="cb3-developer-lock"><span>DESARROLLADO POR</span><a href="https://www.socrates.la/" target="_blank" rel="noopener"><img src="assets/socrates-developer.png" alt="Sócrates Creative Studio"></a><b>Este bloque es fijo y no puede moverse ni eliminarse.</b></div>`;
}

function blockBody(b,depth=0){
  const s=S(b),pad=s.padding||'16px 20px',bg=s.background||'#fff',color=s.color||'#1f2937',align=s.align||'left',ff=s.fontFamily||'Ubuntu Sans',fs=s.fontSize||'16px',fw=s.fontWeight||'400',lh=s.lineHeight||'1.55',radius=s.radius||'0px',minh=s.minHeight||'0px',margin=s.margin||'0px';
  const common=`padding:${esc(pad)};margin:${esc(margin)};min-height:${esc(minh)};background:${esc(bg)};color:${esc(color)};text-align:${esc(align)};font-family:${esc(ff)},Arial,sans-serif;font-size:${esc(fs)};font-weight:${esc(fw)};line-height:${esc(lh)};border-radius:${esc(radius)}`;
  if(['columns1','columns','columns3'].includes(b.type))return renderColumns(b,depth);
  switch(b.type){
    case'title':
    case'text':
      return`<div style="${common}">${b.content||''}</div>`;
    case'footer':{
      const links=[];
      if(b.showPrivacy!==false)links.push(`<u>${esc(b.privacyLabel||'Política de Privacidad')}</u>`);
      if(b.showUnsubscribe!==false)links.push(`<u>${esc(b.unsubscribeLabel||'Darse de baja')}</u>`);
      return`<div style="${common}">${b.content||''}${links.length?`<div style="margin-top:9px">${links.join(' &nbsp;·&nbsp; ')}</div>`:''}</div>`;
    }
    case'quote':return`<div style="${common}"><div style="border-left:4px solid ${esc(s.accent||'#31b4e7')};padding-left:16px;font-style:italic">${b.content||''}</div></div>`;
    case'image':{const sc=elementScale(s),w=scaledDimension(s.width||'100%',sc,'100%');return`<div style="padding:${esc(pad)};margin:${esc(margin)};background:${esc(bg)};text-align:${esc(align)}">${renderImage(b.src,b.alt,w,radius)}</div>`}
    case'logo':{const sc=elementScale(s),w=scaledDimension(s.width||'180px',sc,'180px');return`<div style="padding:${esc(pad)};margin:${esc(margin)};background:${esc(bg)};text-align:${esc(align)}">${renderImage(b.src,b.alt,w,radius)}</div>`}
    case'button':{const sc=elementScale(s),btnFs=scaledPx(s.fontSize||'14px',sc,'14px'),py=Math.round(11*sc*10)/10,px=Math.round(18*sc*10)/10;return`<div style="padding:${esc(pad)};margin:${esc(margin)};background:${esc(bg)};text-align:${esc(align)}"><span style="display:inline-block;background:${esc(s.buttonBg||'#ff5300')};color:${esc(s.buttonColor||'#fff')};padding:${py}px ${px}px;border-radius:${esc(radius)};font-size:${esc(btnFs)};font-weight:700">${esc(strip(b.content||'CONOCER MÁS'))}</span></div>`}
    case'divider':return`<div style="padding:${esc(pad)};margin:${esc(margin)};background:${esc(bg)}">${dividerPreview(b,s)}</div>`;
    case'spacer':return`<div style="height:${esc(s.height||'30px')};margin:${esc(margin)};background:${esc(bg)}"></div>`;
    case'hero':{const img=normalizeUrl(b.src),back=img?`background-image:linear-gradient(rgba(0,47,87,.70),rgba(0,47,87,.70)),url('${esc(img)}');background-size:cover;background-position:center;`:'';return`<div style="${common};${back}"><h1 style="margin:0 0 12px;font-family:${esc(ff)},Arial,sans-serif;font-size:${esc(fs)};font-weight:${esc(fw)}">${b.title||''}</h1><div style="font-family:${esc(ff)},Arial,sans-serif;font-size:16px;line-height:1.6;color:${esc(s.textColor||'#fff')}">${b.text||''}</div><div style="margin-top:18px"><span style="display:inline-block;background:${esc(s.buttonBg||'#ff5300')};color:#fff;padding:11px 17px;border-radius:${esc(s.buttonRadius||'5px')};font-weight:700">${esc(b.button||'CONOCER MÁS')}</span></div></div>`}
    case'banner':{const img=normalizeUrl(b.src),back=img?`background-image:linear-gradient(rgba(0,47,87,.62),rgba(0,47,87,.62)),url('${esc(img)}');background-size:cover;background-position:center;`:'';return`<div style="${common};${back}"><h2 style="margin:0 0 10px;font-family:${esc(ff)},Arial,sans-serif;font-size:${esc(fs)};font-weight:${esc(fw)}">${b.title||''}</h2><div>${b.text||''}</div><div style="margin-top:16px"><span style="background:${esc(s.buttonBg||'#ff5300')};color:#fff;padding:10px 16px;border-radius:999px;font-weight:700">${esc(b.button||'CONOCER MÁS')}</span></div></div>`}
    case'card':return`<div style="padding:${esc(pad)};margin:${esc(margin)};background:${esc(bg)}"><div style="padding:22px;background:${esc(s.cardBg||'#fff')};border:1px solid ${esc(s.border||'#dce5ec')};border-radius:${esc(radius||'10px')}"><h3 style="margin:0 0 8px;font-family:${esc(ff)},Arial,sans-serif;font-size:${esc(fs)};font-weight:${esc(fw)}">${b.title||''}</h3><div style="color:${esc(s.textColor||'#516579')}">${b.text||''}</div></div></div>`;
    case'header':return`<div style="display:flex;align-items:center;justify-content:space-between;padding:${esc(pad)};margin:${esc(margin)};background:${esc(bg)}">${b.src?`<img src="${esc(normalizeUrl(b.src))}" style="max-width:${esc(s.logoWidth||'150px')};max-height:52px">`:`<strong style="color:#0074c7">LOGO</strong>`}<div style="font-size:${esc(fs)}">${esc(b.menu1||'')} &nbsp; ${esc(b.menu2||'')} &nbsp; ${esc(b.menu3||'')} &nbsp; <span style="background:${esc(s.buttonBg||'#ff5300')};color:#fff;padding:8px 12px;border-radius:999px">${esc(b.button||'')}</span></div></div>`;
    case'social':return`<div style="${common}">${socialPreview(b,s,ff,fs,fw)}</div>`;
    case'iconbox':return`<div style="padding:${esc(pad)};margin:${esc(margin)};background:${esc(bg)}"><div style="display:flex;gap:13px;padding:20px;background:${esc(s.cardBg||'#f8fbfd')};border:1px solid #dce8f0;border-radius:${esc(radius||'10px')}"><div style="width:48px;height:48px;border-radius:10px;background:${esc(s.iconBg||'#eaf6fd')};color:${esc(s.iconColor||'#0074c7')};display:grid;place-items:center;font-size:23px">${esc(b.icon||'★')}</div><div><h3 style="margin:0 0 5px;font-family:${esc(ff)},Arial,sans-serif;font-size:${esc(fs)};font-weight:${esc(fw)}">${b.title||''}</h3><div>${b.text||''}</div></div></div></div>`;
    case'pricing':return`<div style="${common};text-align:center"><small style="background:#ff5300;color:#fff;padding:5px 9px;border-radius:999px;font-weight:800">${esc(b.badge||'PLAN')}</small><h3>${esc(b.plan||'')}</h3><strong style="font-family:${esc(ff)},Arial,sans-serif;font-size:34px;font-weight:${esc(fw)};color:#0074c7">${esc(b.price||'')}</strong><span>${esc(b.period||'')}</span><div style="white-space:pre-line;text-align:left;margin:17px 0">${esc(b.features||'')}</div></div>`;
    case'video':{const sc=elementScale(s),w=`${Math.round(sc*100)}%`;return`<div style="${common}">${b.thumbnail?renderImage(b.thumbnail,'Video',w,radius):renderImage('','',w,radius)}<h3>${b.title||''}</h3><div>${b.text||''}</div></div>`}
    case'qr':{const sc=elementScale(s),base=parseFloat(s.qrSize)||170,q=Math.max(24,Math.round(base*sc));return`<div style="${common};text-align:center"><div style="display:inline-grid;place-items:center;width:${q}px;height:${q}px;background:#fff;border:1px solid #dce5ec;font-family:${esc(ff)},Arial,sans-serif;font-size:${Math.max(10,Math.round(20*sc))}px;font-weight:${esc(fw)}">QR</div><div>${esc(b.label||'')}</div></div>`}
    case'product':{const sc=elementScale(s),w=`${Math.round(sc*100)}%`;return`<div style="${common}">${renderImage(b.src,'Producto',w,radius)}<h3 style="margin:15px 0 6px">${b.name||''}</h3><strong style="color:${esc(s.priceColor||'#ff5300')};font-size:${esc(s.priceSize||'25px')}">${esc(b.price||'')}</strong>${b.oldPrice?` <del>${esc(b.oldPrice)}</del>`:''}<div style="margin-top:8px">${b.text||''}</div></div>`}
    case'testimonial':{const av=normalizeUrl(b.avatar||''),asz=s.avatarSize||'64px';return`<div style="${common}"><div style="color:${esc(s.accent||'#f6bd00')};font-size:18px">${'★'.repeat(clamp(b.stars,0,5))}${'☆'.repeat(5-clamp(b.stars,0,5))}</div><div style="font-style:italic;margin:8px 0 14px">${b.quote||''}</div><div style="display:flex;align-items:center;gap:12px">${av?`<img src="${esc(av)}" style="width:${esc(asz)};height:${esc(asz)};border-radius:50%;object-fit:cover;flex:0 0 ${esc(asz)}">`:''}<div><strong>${esc(b.author||'')}</strong><div style="font-size:12px;color:#7890a1">${esc(b.role||'')}</div></div></div></div>`}
    case'stats':return`<div style="${common}"><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">${[1,2,3].map(i=>`<div style="padding:16px;background:${esc(s.cardBg||'#f8fbfd')};border:1px solid #dce8f0;border-radius:${esc(radius)};text-align:center"><strong style="display:block;font-size:${esc(fs)}">${esc(b['value'+i]||'')}</strong><span style="font-size:12px">${esc(b['label'+i]||'')}</span></div>`).join('')}</div></div>`;
    case'iconlist':return`<div style="${common}">${String(b.items||'').split(/\r?\n/).filter(Boolean).map(x=>`<div style="display:flex;gap:9px;align-items:center;margin:7px 0"><span style="display:grid;place-items:center;width:25px;height:25px;border-radius:50%;background:${esc(s.iconBg||'#eef9e7')};color:${esc(s.iconColor||'#73bc25')}">${esc(b.icon||'✓')}</span><span>${esc(x)}</span></div>`).join('')}</div>`;
    case'html':return`<div style="${common}"><div style="padding:12px;border:1px dashed #9ebfd3;background:#f8fbfd;font:11px Consolas">HTML PERSONALIZADO<br>${esc(strip(b.content||'').slice(0,170))}</div></div>`;
    default:return`<div style="${common}">${b.content||''}</div>`;
  }
}
function renderBlock(b,depth=0){
  return`<div class="cb3-block ${b.id===selectedId?'selected':''}" data-block-id="${esc(b.id)}" draggable="${depth<4?'true':'false'}"><span class="cb3-tag">${esc(labels[b.type]||b.type)}</span><div class="cb3-blocktools"><button type="button" data-tool="up">↑</button><button type="button" data-tool="down">↓</button><button type="button" data-tool="copy">⧉</button><button type="button" data-tool="delete" class="x">×</button></div>${blockBody(b,depth)}</div>`;
}
function renderColumns(b,depth){
  normalizeWidths(b);const widths=b.cells.map(c=>c.width);
  let cum=0,handles='';
  const cells=b.cells.map((c,i)=>{
    const items=(c.items||[]).map(x=>renderBlock(x,depth+1)).join('');
    const cs=c.style||{};
    const cell=`<div class="cb3-col-cell" data-cell-id="${esc(c.id)}" style="min-height:${esc(cs.minHeight||'120px')};padding:${esc(cs.padding||'16px')};background:${esc(cs.background||'#fff')};color:${esc(cs.color||'#1f2937')};text-align:${esc(cs.align||'left')};font-family:${esc(cs.fontFamily||'Ubuntu Sans')},Arial,sans-serif;font-size:${esc(cs.fontSize||'15px')};font-weight:${esc(cs.fontWeight||'400')};line-height:${esc(cs.lineHeight||'1.5')};border:${esc(cs.borderWidth||'0px')} ${esc(cs.borderStyle||'solid')} ${esc(cs.borderColor||'#dce5ec')};border-radius:${esc(cs.radius||'0px')}"><div class="cb3-col-label">COLUMNA ${i+1} · ${widths[i].toFixed(1)}%</div><div class="cb3-cell-items">${items||'<div class="cb3-cell-empty">Arrastra aquí cualquier elemento</div>'}</div><div class="cb3-height-resizer" data-height-cell="${esc(c.id)}" title="Arrastra para cambiar alto"></div></div>`;
    if(i<widths.length-1){cum+=widths[i];handles+=`<div class="cb3-col-resizer" data-resize-cols="${esc(b.id)}" data-left="${i}" style="left:${cum}%"><span>⋮</span></div>`}
    return cell;
  }).join('');
  return`<div class="cb3-columns-shell" style="margin:${esc(S(b).margin||'0px')};padding:${esc(S(b).padding||'0px')};background:${esc(S(b).background||'#fff')}"><div class="cb3-columns-grid" data-columns-id="${esc(b.id)}" style="grid-template-columns:${widths.map(v=>v+'%').join(' ')}">${cells}</div>${handles}</div>`;
}
function applyDocumentStyles(){
  const stage=document.querySelector('.cb3-stage');
  if(stage){
    stage.style.backgroundColor=documentSettings.backgroundColor||'#eef5f8';
    stage.style.backgroundImage=documentSettings.backgroundImage?`url("${normalizeUrl(documentSettings.backgroundImage)}")`:'none';
    stage.style.backgroundSize=documentSettings.backgroundSize||'cover';
    stage.style.backgroundPosition=documentSettings.backgroundPosition||'center top';
    stage.style.backgroundRepeat='no-repeat';
    stage.style.padding=documentSettings.outerPadding||'24px 16px';
  }
  if(device){
    if(!device.classList.contains('mobile'))device.style.width=`${clamp(documentSettings.contentWidth||680,480,800)}px`;
    device.style.backgroundColor=documentSettings.contentBackground||'#fff';
    device.style.backgroundImage=documentSettings.contentBackgroundImage?`url("${normalizeUrl(documentSettings.contentBackgroundImage)}")`:'none';
    device.style.backgroundSize='cover';
    device.style.backgroundPosition='center top';
    device.style.borderRadius=documentSettings.contentRadius||'0px';
    device.style.overflow='hidden';
  }
}
function renderCanvas(){
  ensureIds();hidden.value=serializedBlocks();
  const body=blocks.length?blocks.map(b=>renderBlock(b,0)).join(''):'<div class="cb3-empty"><div><strong>Tu campaña está vacía</strong><span>Agrega bloques o usa una plantilla profesional.</span></div></div>';
  canvas.innerHTML=body+developerLockedHtml();
  applyDocumentStyles();
}
function render(){
  renderCanvas();
  renderInspector();
}
function markDirty(){
  hidden.value=serializedBlocks();
  dirty.textContent='Cambios sin guardar';
  dirty.className='cb3-dirty';
}
function parentArrayOf(id){const f=findBlock(id);return f?f.array:null}
function moveSelected(dir){
  const f=findBlock(selectedId);if(!f)return;const j=f.index+dir;if(j<0||j>=f.array.length)return;snapshot();[f.array[f.index],f.array[j]]=[f.array[j],f.array[f.index]];sync();render()
}
function tool(action,id){
  const f=findBlock(id);if(!f)return;
  if(action==='up'||action==='down'){selectedId=id;moveSelected(action==='up'?-1:1);return}
  snapshot();
  if(action==='copy'){const cp=clone(f.block);reseed(cp);f.array.splice(f.index+1,0,cp);selectedId=cp.id}
  if(action==='delete'){f.array.splice(f.index,1);selectedId=f.array[Math.min(f.index,f.array.length-1)]?.id||blocks[0]?.id||null}
  sync();render()
}
function reseed(b){b.id=uid();if(['columns1','columns','columns3'].includes(b.type))for(const c of b.cells||[]){c.id=uid();for(const x of c.items||[])reseed(x)}}
function addBlock(type,targetCell=null){
  snapshot();const b=makeBlock(type);
  if(targetCell){const f=findCell(targetCell);if(!f)return;f.cell.items.push(b)}else blocks.push(b);
  selectedId=b.id;sync();render()
}

canvas.addEventListener('click',e=>{
  const toolBtn=e.target.closest('[data-tool]'),blockEl=e.target.closest('[data-block-id]');
  if(toolBtn&&blockEl){e.stopPropagation();tool(toolBtn.dataset.tool,blockEl.dataset.blockId);return}
  if(blockEl){editingDocument=false;selectedId=blockEl.dataset.blockId;render()}
});
canvas.addEventListener('dragstart',e=>{
  const blockEl=e.target.closest('[data-block-id]');if(!blockEl)return;
  dragPayload={kind:'existing',id:blockEl.dataset.blockId};e.dataTransfer.effectAllowed='move';
});
canvas.addEventListener('dragover',e=>{const cell=e.target.closest('[data-cell-id]');if(cell){e.preventDefault();e.dataTransfer.dropEffect=dragPayload?.kind==='existing'?'move':'copy';canvas.querySelectorAll('.cb3-drop-active').forEach(x=>x.classList.remove('cb3-drop-active'));cell.classList.add('cb3-drop-active')}});
canvas.addEventListener('drop',e=>{
  const cellEl=e.target.closest('[data-cell-id]');canvas.querySelectorAll('.cb3-drop-active').forEach(x=>x.classList.remove('cb3-drop-active'));if(!cellEl||!dragPayload)return;e.preventDefault();
  const target=findCell(cellEl.dataset.cellId);if(!target)return;
  if(dragPayload.kind==='palette'){snapshot();const b=makeBlock(dragPayload.type);target.cell.items.push(b);selectedId=b.id;editingDocument=false}
  else if(dragPayload.kind==='existing'){
    const f=findBlock(dragPayload.id);
    if(!f||f.block.id===target.columns.id||blockContainsCell(f.block,target.cell.id)){dragPayload=null;return}
    snapshot();f.array.splice(f.index,1);target.cell.items.push(f.block);selectedId=f.block.id;editingDocument=false
  }
  dragPayload=null;sync();render()
});
document.querySelectorAll('[data-add]').forEach(btn=>{
  btn.onclick=()=>addBlock(btn.dataset.add);
  btn.draggable=true;btn.addEventListener('dragstart',e=>{dragPayload={kind:'palette',type:btn.dataset.add};e.dataTransfer.setData('text/plain',btn.dataset.add);e.dataTransfer.effectAllowed='copy'});
  btn.addEventListener('dragend',()=>dragPayload=null);
});

function css4(v){const a=String(v||'0').trim().split(/\s+/).map(x=>Math.round(parseFloat(x)||0));if(a.length===1)return[a[0],a[0],a[0],a[0]];if(a.length===2)return[a[0],a[1],a[0],a[1]];if(a.length===3)return[a[0],a[1],a[2],a[1]];return[a[0],a[1],a[2],a[3]]}
function boxControl(label,path,val){
  const a=css4(val);return`<div class="cb3-prop"><span>${label}</span><div class="cb3-box4">${['T','R','B','L'].map((x,i)=>`<label><small>${x}</small><input type="number" data-box="${path}" data-side="${i}" value="${a[i]}" step="1"></label>`).join('')}</div></div>`;
}
function stepper(label,path,val,unit='px',step=1,min=0,max=999){
  let n=parseFloat(String(val??'').replace(',','.'));if(!Number.isFinite(n))n=0;
  const ratioMode=unit===''&&step<1&&max<=5;
  const scale=ratioMode?.01:1;
  const shown=Math.round(n/scale),displayMin=Math.round(min/scale),displayMax=Math.round(max/scale),displayStep=Math.max(1,Math.round(step/scale));
  const displayUnit=ratioMode?'%':unit;
  return`<label class="cb3-prop"><span>${label}</span><div class="cb3-stepper"><button type="button" aria-label="Disminuir ${esc(label)}" data-step-path="${path}" data-step-delta="-${displayStep}" data-step-unit="${unit}" data-step-scale="${scale}" data-step-min="${displayMin}" data-step-max="${displayMax}">−</button><input type="number" inputmode="numeric" data-num="${path}" data-num-unit="${unit}" data-num-scale="${scale}" value="${shown}" step="1" min="${displayMin}" max="${displayMax}" aria-label="${esc(label)}"><button type="button" aria-label="Aumentar ${esc(label)}" data-step-path="${path}" data-step-delta="${displayStep}" data-step-unit="${unit}" data-step-scale="${scale}" data-step-min="${displayMin}" data-step-max="${displayMax}">+</button><em>${displayUnit||''}</em></div></label>`;
}
function rangeControl(label,path,val,min=10,max=100,step=1,unit='%'){
  let n=parseFloat(String(val??'').replace(',','.'));if(!Number.isFinite(n))n=max;n=Math.round(clamp(n,min,max));
  return`<label class="cb3-prop cb3-range-prop"><span>${label}</span><div class="cb3-range-control"><input type="range" data-range="${path}" min="${Math.round(min)}" max="${Math.round(max)}" step="1" value="${n}" aria-label="${esc(label)}"><div class="cb3-range-value"><input type="number" inputmode="numeric" data-range-number="${path}" min="${Math.round(min)}" max="${Math.round(max)}" step="1" value="${n}"><em>${unit}</em></div></div><small class="cb3-help">10% = más pequeño · 100% = tamaño original</small></label>`;
}
function elementScale(s){return clamp(parseFloat(s?.elementScale)||100,10,100)/100}
function scaledDimension(value,scale,def='100%'){
  const raw=String(value||def).trim(),m=raw.match(/^(-?\d+(?:\.\d+)?)(px|%)$/i);if(!m)return raw;
  const n=Math.max(0,parseFloat(m[1])*scale),unit=m[2];return `${Math.round(n*100)/100}${unit}`;
}
function scaledPx(value,scale,def){const n=parseFloat(String(value||def));return `${Math.round((Number.isFinite(n)?n:parseFloat(def))*scale*100)/100}px`}
function field(label,path,val,type='text'){return`<label class="cb3-prop"><span>${label}</span><input type="${type}" data-field="${path}" value="${esc(val??'')}"></label>`}
function area(label,path,val){return`<label class="cb3-prop"><span>${label}</span><textarea data-field="${path}">${esc(val??'')}</textarea></label>`}
function select(label,path,val,opts){return`<label class="cb3-prop"><span>${label}</span><select data-field="${path}">${opts.map(o=>`<option value="${esc(o[0])}" ${String(val)===String(o[0])?'selected':''}>${esc(o[1])}</option>`).join('')}</select></label>`}
function fontSelect(label,path,val){
  const corporate=[
    ['Exo 2','Exo 2 · Corporativa / títulos'],
    ['Ubuntu Sans','Ubuntu Sans · Corporativa / textos']
  ];
  const sans=[
    ['Arial','Arial · Muy compatible'],
    ['Helvetica','Helvetica · Muy compatible'],
    ['Verdana','Verdana · Excelente legibilidad'],
    ['Tahoma','Tahoma · Compacta y clara'],
    ['Trebuchet MS','Trebuchet MS · Moderna y segura'],
    ['Arial Black','Arial Black · Alto impacto']
  ];
  const serif=[
    ['Georgia','Georgia · Serif elegante'],
    ['Times New Roman','Times New Roman · Clásica'],
    ['Courier New','Courier New · Monoespaciada']
  ];
  const options=(arr)=>arr.map(o=>`<option value="${esc(o[0])}" ${String(val)===String(o[0])?'selected':''}>${esc(o[1])}</option>`).join('');
  return`<label class="cb3-prop"><span>${label}</span><select data-field="${path}" class="cb3-font-select">
    <optgroup label="Corporativas">${options(corporate)}</optgroup>
    <optgroup label="Seguras para email · Sans Serif">${options(sans)}</optgroup>
    <optgroup label="Seguras para email · Serif / Mono">${options(serif)}</optgroup>
  </select><small class="cb3-help">Las fuentes corporativas usan fallback seguro cuando el cliente de correo no admite web fonts.</small></label>`;
}
function weightSelect(label,path,val){
  const opts=[
    ['300','300 · Light'],
    ['400','400 · Regular'],
    ['500','500 · Medium'],
    ['600','600 · Semi Bold'],
    ['700','700 · Bold'],
    ['800','800 · Extra Bold'],
    ['900','900 · Black']
  ];
  return select(label,path,String(val||'400'),opts);
}
function color(label,path,val){
  const raw=String(val||'').trim(),v=/^#[0-9a-f]{6}$/i.test(raw)?raw:'#ffffff';
  return`<label class="cb3-prop"><span>${label}</span><div class="cb3-color"><input type="color" data-color="${path}" value="${esc(v)}"><input type="text" data-color-text="${path}" value="${esc(raw||v)}" spellcheck="false" autocomplete="off"></div></label>`
}
function uploader(title,target='image'){return`<div class="cb3-upload-wrap"><div class="cb3-upload-title">${esc(title)}</div><iframe class="cb3-upload-frame" src="builder3-upload.php?target=${encodeURIComponent(target)}"></iframe></div>`}
function getPath(obj,path){return path.split('.').reduce((a,k)=>a&&a[k],obj)}
function setPath(obj,path,val){const p=path.split('.');let cur=obj;for(let i=0;i<p.length-1;i++){if(!cur[p[i]]||typeof cur[p[i]]!=='object')cur[p[i]]={};cur=cur[p[i]]}cur[p[p.length-1]]=val}
function common(b){
  const s=S(b);return`<div class="cb3-group"><div class="cb3-group-title">TIPOGRAFÍA</div>
  ${fontSelect('Tipo de tipografía','style.fontFamily',s.fontFamily||(['title','hero','card','banner','pricing','iconbox'].includes(b.type)?'Exo 2':'Ubuntu Sans'))}
  ${weightSelect('Peso del texto','style.fontWeight',s.fontWeight||'400')}
  </div><div class="cb3-group"><div class="cb3-group-title">TAMAÑO Y ESPACIADO</div>
  ${stepper('Ancho del bloque','style.blockWidth',s.blockWidth||100,'%',1,10,100)}
  ${select('Alineación bloque','style.blockAlign',s.blockAlign||'center',[['left','Izquierda'],['center','Centro'],['right','Derecha']])}
  ${boxControl('Padding','style.padding',s.padding||'0px')}${boxControl('Margen','style.margin',s.margin||'0px')}
  <div class="cb3-two">${stepper('Tamaño texto','style.fontSize',s.fontSize||'16px','px',1,8,96)}${stepper('Radio','style.radius',s.radius||'0px','px',1,0,80)}</div>
  <div class="cb3-two">${stepper('Alto mínimo','style.minHeight',s.minHeight||'0px','px',5,0,1200)}${stepper('Interlineado','style.lineHeight',s.lineHeight||1.55,'',0.05,.7,3)}</div>
  ${color('Fondo','style.background',s.background||'#ffffff')}${color('Color texto','style.color',s.color||'#1f2937')}
  ${select('Alineación texto','style.align',s.align||'left',[['left','Izquierda'],['center','Centro'],['right','Derecha']])}</div>`;
}
function columnsInspector(b){
  normalizeWidths(b);let out=`<div class="cb3-group"><div class="cb3-group-title">COLUMNAS · ARRASTRA LOS SEPARADORES EN EL LIENZO</div>`;
  (b.cells||[]).forEach((c,i)=>{
    out+=`<div class="cb3-col-props"><strong>Columna ${i+1}</strong>${stepper('Ancho','cell:'+c.id+':width',c.width,'%',1,10,90)}${stepper('Alto mínimo','cell:'+c.id+':minHeight',c.style?.minHeight||120,'px',5,40,1200)}${boxControl('Padding','cell:'+c.id+':padding',c.style?.padding||'16px')}${color('Fondo','cell:'+c.id+':background',c.style?.background||'#ffffff')}
    <div class="cb3-add-to-cell"><select data-cell-type="${c.id}">${['text','title','image','button','card','iconbox','product','testimonial','stats','iconlist','columns','columns3','divider','spacer'].map(t=>`<option value="${t}">${labels[t]||t}</option>`).join('')}</select><button type="button" data-add-cell="${c.id}">+ AGREGAR</button></div><small>${(c.items||[]).length} componente(s) dentro</small></div>`;
  });return out+'</div>';
}
function renderDocumentInspector(){
  const d=documentSettings;
  let h=`<div class="cb3-inspector-head"><h3>Documento</h3><span class="cb3-badge">GLOBAL</span></div>
  <div class="cb3-doc-note">Estos ajustes afectan a todo el mailing.</div>
  <div class="cb3-group"><div class="cb3-group-title">FONDO EXTERIOR</div>
  ${color('Color de fondo','backgroundColor',d.backgroundColor)}
  ${field('URL imagen de fondo','backgroundImage',d.backgroundImage||'')}
  ${uploader('Subir fondo del documento','document-bg')}
  ${select('Tamaño del fondo','backgroundSize',d.backgroundSize||'cover',[['cover','Cubrir'],['contain','Contener'],['100% auto','Ancho completo'],['auto','Tamaño natural']])}
  ${select('Posición','backgroundPosition',d.backgroundPosition||'center top',[['center top','Centro arriba'],['center center','Centro'],['left top','Izquierda arriba'],['right top','Derecha arriba']])}
  </div>
  <div class="cb3-group"><div class="cb3-group-title">CONTENEDOR DEL EMAIL</div>
  ${stepper('Ancho del documento','contentWidth',d.contentWidth||680,'px',10,480,800)}
  ${color('Fondo del contenido','contentBackground',d.contentBackground||'#ffffff')}
  ${field('URL fondo del contenido','contentBackgroundImage',d.contentBackgroundImage||'')}
  ${uploader('Subir fondo del contenido','document-content-bg')}
  ${boxControl('Margen / aire exterior','outerPadding',d.outerPadding||'24px 16px')}
  ${stepper('Radio del documento','contentRadius',d.contentRadius||'0px','px',1,0,50)}
  </div>`;
  inspector.innerHTML=h;bindDocumentInspector();
}
function bindDocumentInspector(){
  inspector.querySelectorAll('[data-field]').forEach(el=>{
    el.addEventListener('change',()=>{snapshot();documentSettings[el.dataset.field]=el.value;sync();render()});
    if(el.tagName==='TEXTAREA'||['text','url'].includes(el.type))el.addEventListener('input',()=>{documentSettings[el.dataset.field]=el.value;markDirty();renderCanvas()});
  });
  inspector.querySelectorAll('[data-color]').forEach(el=>{
    let snap=false;
    const take=()=>{if(!snap){snapshot();snap=true}};
    el.addEventListener('pointerdown',take);
    el.addEventListener('focus',take);
    el.addEventListener('input',()=>{
      take();const p=el.dataset.color;documentSettings[p]=el.value;
      const text=inspector.querySelector(`[data-color-text="${p}"]`);if(text)text.value=el.value;
      markDirty();renderCanvas();
    });
    el.addEventListener('change',()=>{snap=false});
  });
  inspector.querySelectorAll('[data-color-text]').forEach(el=>{
    let snap=false;
    const apply=()=>{
      const p=el.dataset.colorText,v=el.value.trim();
      if(!/^#[0-9a-f]{6}$/i.test(v))return false;
      if(!snap){snapshot();snap=true}
      documentSettings[p]=v;
      const picker=inspector.querySelector(`[data-color="${p}"]`);if(picker)picker.value=v;
      markDirty();renderCanvas();return true;
    };
    el.addEventListener('input',apply);
    el.addEventListener('change',()=>{if(!apply())el.value=documentSettings[el.dataset.colorText]||'#ffffff';snap=false});
  });
  inspector.querySelectorAll('[data-num]').forEach(el=>el.addEventListener('change',()=>{snapshot();const unit=el.dataset.numUnit||'',scale=Number(el.dataset.numScale||1),raw=Math.round(clamp(el.value,Number(el.min??-9999),Number(el.max??9999))),v=raw*scale;el.value=String(raw);documentSettings[el.dataset.num]=unit?`${v}${unit}`:v;sync();render()}));
  inspector.querySelectorAll('[data-step-path]').forEach(btn=>btn.onclick=()=>{snapshot();const p=btn.dataset.stepPath,unit=btn.dataset.stepUnit||'',scale=Number(btn.dataset.stepScale||1),min=Number(btn.dataset.stepMin),max=Number(btn.dataset.stepMax),inp=inspector.querySelector(`[data-num="${p}"]`);let raw=Math.round(clamp((parseFloat(inp?.value)||0)+Number(btn.dataset.stepDelta),min,max));if(inp)inp.value=String(raw);const v=raw*scale;documentSettings[p]=unit?`${v}${unit}`:v;sync();render()});
  inspector.querySelectorAll('[data-box]').forEach(el=>el.addEventListener('change',()=>{snapshot();const p=el.dataset.box,els=[...inspector.querySelectorAll(`[data-box="${p}"]`)].sort((a,z)=>Number(a.dataset.side)-Number(z.dataset.side));documentSettings[p]=els.map(x=>`${Math.max(0,Math.round(Number(x.value)||0))}px`).join(' ');sync();render()}));
}

function renderInspector(){
  if(editingDocument){renderDocumentInspector();return}
  const f=findBlock(selectedId);if(!f){inspector.innerHTML='<div class="cb3-inspector-empty"><div><strong>Propiedades</strong><span>Selecciona un bloque o abre Documento.</span></div></div>';return}
  const b=f.block,s=S(b);let h=`<div class="cb3-inspector-head"><h3>Propiedades</h3><span class="cb3-badge">${esc(labels[b.type]||b.type)}</span></div>`;
  if(['title','text','quote'].includes(b.type))h+=area(b.type==='title'?'Título':'Contenido','content',b.content||'');
  else if(b.type==='footer')h+=`<div class="cb3-legal-note">Privacidad y baja pertenecen únicamente al Footer legal.</div>`+area('Contenido','content',b.content||'')+select('Mostrar privacidad','showPrivacy',b.showPrivacy===false?'0':'1',[['1','Sí'],['0','No']])+field('Texto privacidad','privacyLabel',b.privacyLabel||'Política de Privacidad')+field('URL privacidad','privacyUrl',b.privacyUrl||'privacy.php')+select('Mostrar baja','showUnsubscribe',b.showUnsubscribe===false?'0':'1',[['1','Sí'],['0','No']])+field('Texto baja','unsubscribeLabel',b.unsubscribeLabel||'Darse de baja');
  else if(['image','logo'].includes(b.type)){h+=field('URL imagen','src',b.src||'')+field('Texto alternativo','alt',b.alt||'')+rangeControl('Tamaño visual del elemento','style.elementScale',s.elementScale||100,10,100,1,'%')+stepper('Ancho base de imagen','style.width',s.width||(b.type==='logo'?'180px':'100%'),String(s.width||'').includes('%')?'%':'px',1,10,1000)+uploader('Subir / reemplazar imagen','image')}
  else if(b.type==='button')h+=field('Texto','content',strip(b.content||''))+field('Enlace','href',b.href||'#')+rangeControl('Tamaño del botón','style.elementScale',s.elementScale||100,10,100,1,'%')+color('Fondo botón','style.buttonBg',s.buttonBg||'#ff5300')+color('Texto botón','style.buttonColor',s.buttonColor||'#fff');
  else if(['hero','banner'].includes(b.type))h+=field('Título','title',strip(b.title||''))+area('Texto','text',strip(b.text||''))+field('Botón','button',b.button||'')+field('Enlace','href',b.href||'#')+field('URL fondo','src',b.src||'')+uploader('Subir imagen de fondo','image');
  else if(b.type==='card')h+=field('Título','title',b.title||'')+area('Texto','text',b.text||'')+field('Botón','button',b.button||'')+field('Enlace','href',b.href||'#');
  else if(b.type==='header')h+=field('URL logo','src',b.src||'')+uploader('Subir logo','image')+stepper('Ancho logo','style.logoWidth',s.logoWidth||'150px','px',5,40,400)+field('Menú 1','menu1',b.menu1||'')+field('URL 1','url1',b.url1||'#')+field('Menú 2','menu2',b.menu2||'')+field('URL 2','url2',b.url2||'#')+field('Menú 3','menu3',b.menu3||'')+field('URL 3','url3',b.url3||'#');
  else if(['columns1','columns','columns3'].includes(b.type))h+=columnsInspector(b);
  else if(b.type==='social')h+=select('Presentación','displayMode',b.displayMode||'iconsText',[['text','Solo textos'],['icons','Solo iconos'],['iconsText','Iconos + texto']])+select('Forma iconos','iconShape',b.iconShape||'circle',[['circle','Círculos'],['rounded','Redondeados'],['square','Cuadrados']])+stepper('Tamaño iconos','iconSize',b.iconSize||30,'px',1,22,48)+field('Instagram','instagram',b.instagram||'')+field('Facebook','facebook',b.facebook||'')+field('LinkedIn','linkedin',b.linkedin||'')+field('Web','website',b.website||'');
  else if(b.type==='spacer')h+=stepper('Altura','style.height',s.height||'30px','px',5,0,1000);
  else if(b.type==='divider')h+=color('Color línea','style.color',s.color||'#d7e3ea')+stepper('Grosor línea','lineThickness',b.lineThickness||1,'px',1,1,8)+select('Mostrar icono','iconEnabled',b.iconEnabled?'1':'0',[['0','No'],['1','Sí']])+field('Icono','icon',b.icon||'◆')+select('Posición icono','iconPosition',b.iconPosition||'center',[['start','Inicio'],['center','Centro'],['end','Final']])+color('Color icono','iconColor',b.iconColor||'#ff5300')+color('Fondo icono','iconBg',b.iconBg||'#ffffff')+stepper('Tamaño icono','iconSize',b.iconSize||26,'px',1,18,44);
  else if(b.type==='html')h+=area('HTML personalizado','content',b.content||'');
  else if(b.type==='iconbox')h+=field('Icono','icon',b.icon||'★')+field('Título','title',b.title||'')+area('Texto','text',b.text||'');
  else if(b.type==='pricing')h+=field('Badge','badge',b.badge||'')+field('Plan','plan',b.plan||'')+field('Precio','price',b.price||'')+field('Periodo','period',b.period||'')+area('Características','features',b.features||'')+field('Botón','button',b.button||'')+field('Enlace','href',b.href||'#');
  else if(b.type==='video')h+=field('URL miniatura','thumbnail',b.thumbnail||'')+uploader('Subir miniatura','thumbnail')+rangeControl('Tamaño de miniatura','style.elementScale',s.elementScale||100,10,100,1,'%')+field('URL video','href',b.href||'#')+field('Título','title',b.title||'')+area('Texto','text',b.text||'');
  else if(b.type==='qr')h+=field('URL / dato','data',b.data||'')+rangeControl('Tamaño del QR','style.elementScale',s.elementScale||100,10,100,1,'%')+stepper('Tamaño base QR','style.qrSize',s.qrSize||'170px','px',5,60,400)+field('Texto','label',b.label||'');
  else if(b.type==='product')h+=field('URL imagen','src',b.src||'')+uploader('Subir producto','image')+rangeControl('Tamaño de imagen','style.elementScale',s.elementScale||100,10,100,1,'%')+field('Producto','name',b.name||'')+field('Precio','price',b.price||'')+field('Precio anterior','oldPrice',b.oldPrice||'')+area('Descripción','text',b.text||'')+field('Botón','button',b.button||'')+field('Enlace','href',b.href||'#');
  else if(b.type==='testimonial')h+=field('URL foto','avatar',b.avatar||'')+uploader('Subir foto del testimonio','avatar')+stepper('Tamaño foto','style.avatarSize',s.avatarSize||'64px','px',2,36,120)+stepper('Estrellas','stars',b.stars||5,'',1,0,5)+area('Testimonio','quote',b.quote||'')+field('Autor','author',b.author||'')+field('Cargo','role',b.role||'');
  else if(b.type==='stats')h+=[1,2,3].map(i=>`<div class="cb3-two">${field('Valor '+i,'value'+i,b['value'+i]||'')}${field('Etiqueta '+i,'label'+i,b['label'+i]||'')}</div>`).join('');
  else if(b.type==='iconlist')h+=field('Icono','icon',b.icon||'✓')+area('Items · uno por línea','items',b.items||'');
  h+=common(b)+`<div class="cb3-ins-actions"><button type="button" class="cb3-btn" data-dup>Duplicar</button><button type="button" class="cb3-btn danger" data-del>Eliminar</button></div>`;
  inspector.innerHTML=h;bindInspector(b);
}
function bindInspector(b){
  inspector.querySelectorAll('[data-field]').forEach(el=>{
    if(String(el.dataset.field||'').startsWith('cell:'))return;
    el.addEventListener('change',()=>{snapshot();let v=el.value;if(['showPrivacy','showUnsubscribe','iconEnabled'].includes(el.dataset.field))v=v==='1';setPath(b,el.dataset.field,v);sync();render()});
    if(el.tagName==='TEXTAREA'||['text','url'].includes(el.type))el.addEventListener('input',()=>{setPath(b,el.dataset.field,el.value);markDirty();renderCanvas()});
  });
  inspector.querySelectorAll('[data-color]').forEach(el=>{
    const p=String(el.dataset.color||'');if(p.startsWith('cell:'))return;
    let snap=false;const take=()=>{if(!snap){snapshot();snap=true}};
    el.addEventListener('pointerdown',take);el.addEventListener('focus',take);
    el.addEventListener('input',()=>{take();const text=inspector.querySelector(`[data-color-text="${p}"]`);if(text)text.value=el.value;setPath(b,p,el.value);markDirty();renderCanvas()});
    el.addEventListener('change',()=>{snap=false});
  });
  inspector.querySelectorAll('[data-color-text]').forEach(el=>{
    const p=String(el.dataset.colorText||'');if(p.startsWith('cell:'))return;
    let snap=false;
    const apply=()=>{const v=el.value.trim();if(!/^#[0-9a-f]{6}$/i.test(v))return false;if(!snap){snapshot();snap=true}setPath(b,p,v);const picker=inspector.querySelector(`[data-color="${p}"]`);if(picker)picker.value=v;markDirty();renderCanvas();return true};
    el.addEventListener('input',apply);el.addEventListener('change',()=>{if(!apply())el.value=getPath(b,p)||'#ffffff';snap=false});
  });
  inspector.querySelectorAll('[data-range]').forEach(el=>{
    const p=String(el.dataset.range||'');let snap=false;const take=()=>{if(!snap){snapshot();snap=true}};
    el.addEventListener('pointerdown',take);el.addEventListener('focus',take);
    el.addEventListener('input',()=>{take();const v=Math.round(clamp(Number(el.value)||100,Number(el.min)||10,Number(el.max)||100));el.value=String(v);setPath(b,p,String(v));const n=inspector.querySelector(`[data-range-number="${p}"]`);if(n)n.value=String(v);markDirty();renderCanvas()});
    el.addEventListener('change',()=>{sync();snap=false});
  });
  inspector.querySelectorAll('[data-range-number]').forEach(el=>{
    const p=String(el.dataset.rangeNumber||'');let snap=false;const apply=()=>{if(!snap){snapshot();snap=true}const v=Math.round(clamp(Number(el.value)||100,Number(el.min)||10,Number(el.max)||100));el.value=String(v);setPath(b,p,String(v));const r=inspector.querySelector(`[data-range="${p}"]`);if(r)r.value=String(v);markDirty();renderCanvas()};
    el.addEventListener('input',apply);el.addEventListener('change',()=>{apply();sync();snap=false});
  });
  inspector.querySelectorAll('[data-num]').forEach(el=>{if(String(el.dataset.num||'').startsWith('cell:'))return;el.addEventListener('change',()=>{snapshot();const unit=el.dataset.numUnit||'',scale=Number(el.dataset.numScale||1),raw=Math.round(clamp(el.value,Number(el.min??-9999),Number(el.max??9999))),v=raw*scale;el.value=String(raw);setPath(b,el.dataset.num,unit?`${v}${unit}`:String(v));sync();render()})});
  inspector.querySelectorAll('[data-step-path]').forEach(btn=>{if(String(btn.dataset.stepPath||'').startsWith('cell:'))return;btn.onclick=()=>{snapshot();const p=btn.dataset.stepPath,unit=btn.dataset.stepUnit||'',scale=Number(btn.dataset.stepScale||1),min=Number(btn.dataset.stepMin),max=Number(btn.dataset.stepMax),inp=inspector.querySelector(`[data-num="${p}"]`);let raw=Math.round(clamp((parseFloat(inp?.value)||0)+Number(btn.dataset.stepDelta),min,max));if(inp)inp.value=String(raw);const v=raw*scale;setPath(b,p,unit?`${v}${unit}`:String(v));sync();render()}});
  inspector.querySelectorAll('[data-box]').forEach(el=>{if(String(el.dataset.box||'').startsWith('cell:'))return;el.addEventListener('change',()=>{snapshot();const p=el.dataset.box,els=[...inspector.querySelectorAll(`[data-box="${p}"]`)].sort((a,z)=>Number(a.dataset.side)-Number(z.dataset.side)),v=els.map(x=>`${Math.max(0,Math.round(Number(x.value)||0))}px`).join(' ');setPath(b,p,v);sync();render()})});
  inspector.querySelectorAll('[data-add-cell]').forEach(btn=>btn.onclick=()=>{const sel=inspector.querySelector(`[data-cell-type="${btn.dataset.addCell}"]`);addBlock(sel.value,btn.dataset.addCell)});
  inspector.querySelector('[data-dup]')?.addEventListener('click',()=>tool('copy',b.id));inspector.querySelector('[data-del]')?.addEventListener('click',()=>tool('delete',b.id));
}
function parseCellPath(path){
  const m=String(path).match(/^cell:([^:]+):(.+)$/);return m?{id:m[1],key:m[2]}:null;
}
// Override change handlers for cell controls by event delegation.
inspector.addEventListener('input',e=>{
  const el=e.target;
  const key=el.dataset.color||el.dataset.colorText;
  if(!key||!key.startsWith('cell:'))return;
  e.stopImmediatePropagation();
  const p=parseCellPath(key),fc=findCell(p.id);if(!fc)return;
  let value=el.value.trim();
  if(el.dataset.colorText && !/^#[0-9a-f]{6}$/i.test(value))return;
  if(el.dataset.color){
    const text=inspector.querySelector(`[data-color-text="${key}"]`);if(text)text.value=value;
  }else{
    const picker=inspector.querySelector(`[data-color="${key}"]`);if(picker)picker.value=value;
  }
  if(!fc.cell.style)fc.cell.style={};fc.cell.style[p.key]=value;
  markDirty();renderCanvas();
},true);
inspector.addEventListener('pointerdown',e=>{
  const el=e.target.closest('[data-color^="cell:"]');if(!el)return;
  snapshot();
},true);
inspector.addEventListener('change',e=>{
  const el=e.target;
  const key=el.dataset.num||el.dataset.box;if(!key||!key.startsWith('cell:'))return;
  e.stopImmediatePropagation();const p=parseCellPath(key),fc=findCell(p.id);if(!fc)return;snapshot();
  if(el.dataset.num){let unit=el.dataset.numUnit||'',v=Math.round(Number(el.value)||0);el.value=String(v);if(p.key==='width'){fc.cell.width=clamp(v,10,90);normalizeWidths(fc.columns)}else if(p.key==='minHeight')fc.cell.style.minHeight=`${Math.round(clamp(v,40,1200))}px`}
  if(el.dataset.box){const els=[...inspector.querySelectorAll(`[data-box="${key}"]`)].sort((a,z)=>Number(a.dataset.side)-Number(z.dataset.side));fc.cell.style[p.key]=els.map(x=>`${Math.max(0,Math.round(Number(x.value)||0))}px`).join(' ')}
  sync();render();
},true);
inspector.addEventListener('click',e=>{
  const btn=e.target.closest('[data-step-path^="cell:"]');if(!btn)return;e.preventDefault();e.stopImmediatePropagation();
  const p=parseCellPath(btn.dataset.stepPath),fc=findCell(p.id);if(!fc)return;snapshot();const delta=Number(btn.dataset.stepDelta),min=Number(btn.dataset.stepMin),max=Number(btn.dataset.stepMax);
  if(p.key==='width'){fc.cell.width=clamp(fc.cell.width+delta,min,max);normalizeWidths(fc.columns)}
  else if(p.key==='minHeight'){const cur=parseFloat(fc.cell.style.minHeight)||120;fc.cell.style.minHeight=`${clamp(cur+delta,min,max)}px`}
  sync();render();
},true);

window.addEventListener('message',ev=>{if(ev.origin!==origin)return;const d=ev.data||{};if(d.source!=='EMAILSEND_CB3_UPLOAD'||!d.ok)return;snapshot();const url=normalizeUrl(d.url||'');if(d.target==='document-bg'){documentSettings.backgroundImage=url;editingDocument=true;sync();render();return}if(d.target==='document-content-bg'){documentSettings.contentBackgroundImage=url;editingDocument=true;sync();render();return}const f=findBlock(selectedId);if(!f)return;if(d.target==='thumbnail'||f.block.type==='video')f.block.thumbnail=url;else if(d.target==='avatar'||f.block.type==='testimonial')f.block.avatar=url;else f.block.src=url;sync();render()});

// Column width drag.
canvas.addEventListener('pointerdown',e=>{
  const rh=e.target.closest('[data-resize-cols]');if(rh){e.preventDefault();const f=findBlock(rh.dataset.resizeCols);if(!f)return;const left=Number(rh.dataset.left),b=f.block,grid=rh.closest('.cb3-columns-shell').querySelector('.cb3-columns-grid'),rect=grid.getBoundingClientRect();snapshot();columnResize={b,left,startX:e.clientX,start:[b.cells[left].width,b.cells[left+1].width],rect};document.body.classList.add('cb3-resizing');return}
  const hh=e.target.closest('[data-height-cell]');if(hh){e.preventDefault();const fc=findCell(hh.dataset.heightCell);if(!fc)return;snapshot();heightResize={cell:fc.cell,startY:e.clientY,start:parseFloat(fc.cell.style.minHeight)||120};document.body.classList.add('cb3-resizing')}
});
window.addEventListener('pointermove',e=>{
  if(columnResize){const r=columnResize,delta=(e.clientX-r.startX)/r.rect.width*100,total=Math.round(r.start[0]+r.start[1]),a=Math.round(clamp(r.start[0]+delta,10,total-10));r.b.cells[r.left].width=a;r.b.cells[r.left+1].width=total-a;sync();render()}
  if(heightResize){heightResize.cell.style.minHeight=`${Math.round(clamp(heightResize.start+(e.clientY-heightResize.startY),40,1200))}px`;sync();render()}
});
window.addEventListener('pointerup',()=>{if(columnResize||heightResize){columnResize=null;heightResize=null;document.body.classList.remove('cb3-resizing')}});

const templates={
promo:()=>[makeBlock('header'),Object.assign(makeBlock('hero'),{title:'Oferta especial por tiempo limitado',text:'Una propuesta clara y directa para tus clientes.'}),makeBlock('image'),makeBlock('iconlist'),makeBlock('button'),makeBlock('footer')],
newsletter:()=>[makeBlock('header'),Object.assign(makeBlock('title'),{content:'Novedades que vale la pena conocer'}),makeBlock('text'),makeBlock('image'),makeBlock('divider'),makeBlock('footer')],
corporate:()=>[makeBlock('header'),Object.assign(makeBlock('hero'),{title:'Soluciones para impulsar tu operación',text:'Tecnología, experiencia y acompañamiento.'}),makeBlock('stats'),makeBlock('columns'),makeBlock('testimonial'),makeBlock('footer')],
product:()=>[makeBlock('header'),Object.assign(makeBlock('banner'),{title:'Nuevo producto',text:'Descubre una solución diseñada para hacer la diferencia.'}),makeBlock('product'),makeBlock('testimonial'),makeBlock('button'),makeBlock('footer')],
event:()=>[makeBlock('header'),Object.assign(makeBlock('hero'),{title:'Te invitamos a nuestro próximo evento',text:'Reserva la fecha y participa con nuestro equipo.'}),makeBlock('stats'),makeBlock('iconlist'),makeBlock('button'),makeBlock('footer')],
minimal:()=>[makeBlock('logo'),Object.assign(makeBlock('title'),{content:'Un mensaje simple y elegante'}),makeBlock('text'),makeBlock('image'),makeBlock('button'),makeBlock('footer')],
services:()=>{const c=makeBlock('columns3');c.cells.forEach((x,i)=>x.items=[Object.assign(makeBlock('iconbox'),{icon:['◆','★','✓'][i],title:['Servicio Premium','Implementación','Soporte'][i]})]);return[makeBlock('header'),Object.assign(makeBlock('title'),{content:'Todo lo que podemos hacer por tu empresa'}),c,makeBlock('testimonial'),makeBlock('footer')]},
blackfriday:()=>[Object.assign(makeBlock('banner'),{title:'BLACK FRIDAY',text:'Oferta exclusiva · stock limitado',style:{...defaultStyle('banner'),background:'#07131e',color:'#fff',buttonBg:'#ff5300',minHeight:'300px'}}),makeBlock('product'),makeBlock('iconlist'),makeBlock('button'),makeBlock('footer')],
webinar:()=>[makeBlock('header'),Object.assign(makeBlock('hero'),{title:'Webinar en vivo',text:'Aprende estrategias prácticas con nuestros especialistas.'}),makeBlock('video'),makeBlock('stats'),makeBlock('button'),makeBlock('footer')],
followup:()=>[makeBlock('logo'),Object.assign(makeBlock('title'),{content:'Gracias por conversar con nosotros'}),Object.assign(makeBlock('text'),{content:'Queríamos compartir contigo un resumen y los próximos pasos.'}),makeBlock('iconlist'),makeBlock('button'),makeBlock('footer')]
};
document.querySelectorAll('[data-template]').forEach(btn=>btn.onclick=()=>{const fn=templates[btn.dataset.template];if(!fn)return;if(blocks.length&&!confirm('¿Reemplazar el contenido actual por esta plantilla?'))return;snapshot();blocks=fn().map(normalizeBlock);selectedId=blocks[0]?.id||null;sync();render()});

document.getElementById('cb3Undo').onclick=undo;document.getElementById('cb3Redo').onclick=redo;
document.getElementById('cb3Desktop').onclick=()=>{device.classList.remove('mobile');device.style.width=`${clamp(documentSettings.contentWidth||680,480,800)}px`;document.getElementById('cb3Desktop').classList.add('active');document.getElementById('cb3Mobile').classList.remove('active')};
document.getElementById('cb3Mobile').onclick=()=>{device.classList.add('mobile');device.style.width='390px';document.getElementById('cb3Mobile').classList.add('active');document.getElementById('cb3Desktop').classList.remove('active')};
document.getElementById('cb3Document').onclick=()=>{editingDocument=true;selectedId=null;render()};
document.getElementById('cb3Preview').onclick=()=>{previewJson.value=serializedBlocks();previewModal.classList.add('open');previewForm.submit()};
document.querySelectorAll('[data-preview-close]').forEach(x=>x.onclick=()=>previewModal.classList.remove('open'));
document.getElementById('cb3Html').onclick=()=>{htmlCode.value='';htmlModal.classList.add('open')};document.querySelectorAll('[data-html-close]').forEach(x=>x.onclick=()=>htmlModal.classList.remove('open'));
document.getElementById('cb3AddHtml').onclick=()=>{if(!htmlCode.value.trim())return;const b=makeBlock('html');b.content=htmlCode.value;blocks.push(b);selectedId=b.id;sync();render();htmlModal.classList.remove('open')};
form.addEventListener('submit',()=>{hidden.value=serializedBlocks();dirty.textContent='Guardando…'});
document.addEventListener('keydown',e=>{const edit=['input','textarea','select'].includes((e.target.tagName||'').toLowerCase());if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='z'&&!edit){e.preventDefault();e.shiftKey?redo():undo()}if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='y'&&!edit){e.preventDefault();redo()}if(e.key==='Escape'){previewModal.classList.remove('open');htmlModal.classList.remove('open')}});
function audienceUpdate(){const boxes=[...document.querySelectorAll('.cb3-list input[type=checkbox]')],out=document.getElementById('cb3AudienceCount');let n=0,c=0;boxes.forEach(b=>{if(b.checked){n+=Number(b.dataset.count||0);c++}});out.textContent=c?(n+' contactos · '+c+(c===1?' lista':' listas')):'Seleccionar listas'}document.querySelectorAll('.cb3-list input').forEach(b=>b.addEventListener('change',audienceUpdate));audienceUpdate();
render();dirty.textContent='Editor PRO listo';dirty.className='cb3-saved';
})();