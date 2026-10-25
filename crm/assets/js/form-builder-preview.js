(() => {
  const root=document.querySelector('[data-live-form-builder]');if(!root)return;
  const preview=root.querySelector('[data-live-form-preview]');const fieldsTarget=root.querySelector('[data-fields]');
  const esc=value=>String(value??'').replace(/[&<>'"]/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
  const val=name=>root.querySelector(`[name="${name}"]`)?.value||'';
  const render=()=>{
    const style={primary:val('primary_color')||'#0074c7',accent:val('accent_color')||'#ff5300',bg:val('background_color')||'#eef4f7',card:val('card_color')||'#fff',text:val('text_color')||'#153149',radius:Number(val('border_radius')||16),columns:Number(val('columns')||1)};
    const fields=[...fieldsTarget.querySelectorAll('[data-field-row]')].map(row=>({label:row.querySelector('[name="field_label[]"]')?.value||'Campo',type:row.querySelector('[name="field_type[]"]')?.value||'text',required:row.querySelector('[name="field_required[]"]')?.checked,placeholder:row.querySelector('[name="field_placeholder[]"]')?.value||''}));
    const controls=fields.filter(f=>f.type!=='hidden').map(f=>{const wide=['textarea','radio','checkbox','file'].includes(f.type);let control='<div class="preview-control">'+esc(f.placeholder||(['select','radio'].includes(f.type)?'Seleccionar…':''))+'</div>';if(f.type==='textarea')control='<div class="preview-control textarea"></div>';if(f.type==='checkbox')control='<div class="preview-control">☐ '+esc(f.label)+'</div>';return `<div class="preview-field ${wide?'wide':''}">${f.type==='checkbox'?'':`<span>${esc(f.label)}${f.required?' *':''}</span>`}${control}</div>`}).join('');
    preview.style.background=style.bg;preview.innerHTML=`<div class="preview-card" style="--preview-primary:${style.primary};--preview-accent:${style.accent};--preview-card:${style.card};--preview-text:${style.text};--preview-radius:${style.radius}px;--preview-columns:${style.columns}"><div class="preview-head"><div class="preview-brand">SÓCRATES · AI Creative Studio</div><h2>${esc(val('title')||'Título del formulario')}</h2><p>${esc(val('description')||'Descripción del formulario')}</p></div><div class="preview-fields">${controls}<div class="preview-button">${esc(val('button_label')||'ENVIAR')}</div></div></div>`;
    const rv=root.querySelector('[data-radius-value]');if(rv)rv.textContent=String(style.radius);
    [...fieldsTarget.querySelectorAll('.field-row-number')].forEach((n,i)=>n.textContent=String(i+1));
  };
  root.addEventListener('input',render);root.addEventListener('change',render);new MutationObserver(render).observe(fieldsTarget,{childList:true,subtree:true});render();
})();
