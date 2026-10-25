(() => {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
  const lang = document.documentElement.lang || 'es';
  const labels = {
    es:{edit:'Personalizar dashboard',save:'Guardar disposición',moveError:'No se pudo mover el negocio.',assistantError:'No fue posible consultar el asistente.',empty:'No encontré una respuesta.',confirmTemplate:'La plantilla reemplazará los campos actuales. ¿Continuar?'},
    pt:{edit:'Personalizar dashboard',save:'Salvar layout',moveError:'Não foi possível mover o negócio.',assistantError:'Não foi possível consultar o assistente.',empty:'Não encontrei uma resposta.',confirmTemplate:'O modelo substituirá os campos atuais. Continuar?'},
    en:{edit:'Customize dashboard',save:'Save layout',moveError:'The deal could not be moved.',assistantError:'The assistant could not be reached.',empty:'I could not find an answer.',confirmTemplate:'The template will replace the current fields. Continue?'}
  }[lang] || {};

  // Menús laterales: el flyout permanece abierto durante el paso del mouse.
  document.querySelectorAll('.nav-group').forEach(group => {
    const trigger = group.querySelector('.nav-group-trigger');
    const flyout = group.querySelector('.nav-flyout');
    let closeTimer = null;
    const position = () => {
      if (!trigger || !flyout || window.innerWidth <= 1100) return;
      const rect = trigger.getBoundingClientRect();
      const maxHeight = Math.min(flyout.scrollHeight || 320, 520);
      const desired = Math.min(rect.top, window.innerHeight - maxHeight - 12);
      flyout.style.top = `${Math.max(10, desired)}px`;
    };
    const open = () => {
      if (window.innerWidth <= 1100) return;
      window.clearTimeout(closeTimer);
      document.querySelectorAll('.nav-group.open').forEach(item => { if (item !== group) item.classList.remove('open'); });
      position(); group.classList.add('open');
    };
    const close = () => {
      if (window.innerWidth <= 1100) return;
      closeTimer = window.setTimeout(() => group.classList.remove('open'), 180);
    };
    group.addEventListener('mouseenter', open);
    group.addEventListener('mouseleave', close);
    flyout?.addEventListener('mouseenter', () => window.clearTimeout(closeTimer));
    flyout?.addEventListener('mouseleave', close);
    trigger?.addEventListener('click', event => {
      if (window.innerWidth > 1100) return;
      event.preventDefault();
      document.querySelectorAll('.nav-group.open').forEach(item => { if (item !== group) item.classList.remove('open'); });
      group.classList.toggle('open');
    });
  });

  // Idiomas.
  const langMenu = document.querySelector('[data-language-menu]');
  const langToggle = document.querySelector('[data-language-toggle]');
  const closeLanguage = () => { langMenu?.classList.remove('open'); langToggle?.setAttribute('aria-expanded','false'); };
  langToggle?.addEventListener('click', event => {
    event.stopPropagation();
    const open = !langMenu?.classList.contains('open');
    langMenu?.classList.toggle('open', open); langToggle.setAttribute('aria-expanded', String(open));
  });
  langMenu?.querySelector('.language-dropdown')?.addEventListener('click', event => event.stopPropagation());
  document.addEventListener('click', closeLanguage);

  // Buscador global: Ctrl/Cmd + K.
  const globalSearch = document.querySelector('[data-global-search-form] input[name="q"]');
  document.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      if (globalSearch) { globalSearch.focus(); globalSearch.select(); }
      else window.location.href = 'global_search.php';
    }
    if (event.key === 'Escape') { closeLanguage(); document.querySelector('[data-assistant-float]')?.classList.remove('open'); }
  });

  // Pipeline Kanban.
  document.querySelectorAll('[data-deal-card]').forEach(card => {
    card.draggable = true;
    card.addEventListener('dragstart', event => {
      card.classList.add('dragging');
      event.dataTransfer?.setData('text/plain', card.dataset.dealCard || '');
      if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', () => card.classList.remove('dragging'));
  });
  document.querySelectorAll('[data-stage-dropzone]').forEach(zone => {
    zone.addEventListener('dragover', event => { event.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', async event => {
      event.preventDefault(); zone.classList.remove('drag-over');
      const dealId = event.dataTransfer?.getData('text/plain');
      const stageId = zone.dataset.stageDropzone;
      const card = document.querySelector(`[data-deal-card="${CSS.escape(String(dealId))}"]`);
      if (!dealId || !stageId || !card) return;
      const original = card.parentElement;
      zone.appendChild(card);
      try {
        const body = new URLSearchParams({deal_id: dealId, stage_id: stageId, csrf_token: csrf()});
        const response = await fetch('deal_move.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body});
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || labels.moveError);
        card.style.setProperty('--stage-color', data.color || '#0074c7');
        const ring = card.querySelector('.probability-ring');
        if (ring) {
          ring.style.setProperty('--probability', data.probability || 0);
          const text = ring.querySelector('span'); if (text) text.textContent = `${data.probability || 0}%`;
        }
        if (data.status === 'won' && data.convert_url && window.confirm('Negocio cerrado como ganado. ¿Deseas convertirlo ahora en un servicio activo con cobros y renovaciones?')) { window.location.href = data.convert_url; return; }
      } catch (error) { original?.appendChild(card); alert(error.message || labels.moveError); }
    });
  });

  // Dashboard 2.0: drag & drop robusto + tamaño + guardado inmediato.
  const dashboard = document.querySelector('[data-dashboard-layout]');
  if (dashboard) {
    let editing = false, dragged = null, saveTimer = null;
    const editButton = document.querySelector('[data-dashboard-edit]');
    const widgets = () => [...dashboard.querySelectorAll('[data-widget-id]')];
    const save = async () => {
      const layout = widgets().map((widget, order) => ({
        id: widget.dataset.widgetId,
        size: widget.dataset.size || 'medium',
        order
      }));
      const body = new URLSearchParams({layout: JSON.stringify(layout), csrf_token: csrf()});
      try {
        const response = await fetch('dashboard_layout.php', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body
        });
        if (!response.ok) throw new Error('save_failed');
      } catch (_) {}
    };
    const queueSave = () => {
      window.clearTimeout(saveTimer);
      saveTimer = window.setTimeout(save, 180);
    };
    const resizeCharts = () => window.setTimeout(() => window.dispatchEvent(new Event('resize')), 80);
    const setEditing = state => {
      editing = !!state;
      dashboard.classList.toggle('dashboard-editing', editing);
      if (editButton) editButton.textContent = editing ? labels.save : labels.edit;
      widgets().forEach(widget => { widget.draggable = editing; });
    };
    const ensureHandle = widget => {
      if (widget.querySelector('.dashboard-drag-handle')) return;
      const tools = widget.querySelector('.dashboard-widget-tools, .widget-tools, .widget-size-tools, [data-widget-tools]');
      const handle = document.createElement('span');
      handle.className = 'dashboard-drag-handle';
      handle.title = 'Arrastrar para mover';
      handle.innerHTML = '↕ MOVER';
      (tools || widget).prepend(handle);
    };
    widgets().forEach(ensureHandle);
    editButton?.addEventListener('click', () => {
      setEditing(!editing);
      if (!editing) save();
    });
    dashboard.addEventListener('dragstart', event => {
      const widget = event.target.closest?.('[data-widget-id]');
      if (!editing || !widget) { event.preventDefault(); return; }
      dragged = widget;
      widget.classList.add('dragging');
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', widget.dataset.widgetId || 'widget');
      }
    });
    dashboard.addEventListener('dragover', event => {
      if (!editing || !dragged) return;
      event.preventDefault();
      if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
      const candidates = widgets().filter(w => w !== dragged && !w.classList.contains('dragging'));
      let closest = null, closestDistance = Number.POSITIVE_INFINITY, before = true;
      candidates.forEach(widget => {
        const r = widget.getBoundingClientRect();
        const cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        const dx = event.clientX - cx, dy = event.clientY - cy;
        const distance = Math.sqrt(dx*dx + dy*dy);
        if (distance < closestDistance) {
          closestDistance = distance;
          closest = widget;
          before = Math.abs(dy) > Math.abs(dx) ? event.clientY < cy : event.clientX < cx;
        }
      });
      if (closest) dashboard.insertBefore(dragged, before ? closest : closest.nextSibling);
      else dashboard.appendChild(dragged);
    });
    dashboard.addEventListener('drop', event => {
      if (!editing || !dragged) return;
      event.preventDefault();
      dragged.classList.remove('dragging');
      dragged = null;
      queueSave();
      resizeCharts();
    });
    dashboard.addEventListener('dragend', () => {
      if (dragged) dragged.classList.remove('dragging');
      dragged = null;
      queueSave();
      resizeCharts();
    });
    dashboard.addEventListener('click', event => {
      const button = event.target.closest?.('[data-widget-size]');
      if (!button) return;
      const widget = button.closest('[data-widget-id]');
      if (!widget) return;
      event.preventDefault(); event.stopPropagation();
      widget.dataset.size = button.dataset.widgetSize || 'medium';
      queueSave(); resizeCharts();
    });
    setEditing(false);
  }

  // Configuración 2.0: tarjetas reordenables y redimensionables.
  const settingsBoard = document.querySelector('[data-settings-board]');
  if (settingsBoard) {
    let draggedCard = null;
    const settingsCards = () => [...settingsBoard.querySelectorAll('[data-settings-card]')];
    const saveSettingsLayout = async () => {
      const layout = settingsCards().map((card, order) => ({
        id: card.dataset.settingsCard,
        size: card.dataset.size || 'l',
        order
      }));
      try {
        const response = await fetch('settings_layout.php', {
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-Token':csrf()},
          body:JSON.stringify({layout})
        });
        if (!response.ok) throw new Error('save_failed');
      } catch (_) {}
    };
    settingsBoard.classList.add('editing');
    settingsCards().forEach(card => {
      card.draggable = true;
      card.addEventListener('dragstart', event => {
        draggedCard = card;
        card.classList.add('dragging');
        if (event.dataTransfer) {
          event.dataTransfer.effectAllowed = 'move';
          event.dataTransfer.setData('text/plain', card.dataset.settingsCard || 'card');
        }
      });
      card.addEventListener('dragend', () => {
        card.classList.remove('dragging');
        draggedCard = null;
        saveSettingsLayout();
      });
    });
    settingsBoard.addEventListener('dragover', event => {
      if (!draggedCard) return;
      event.preventDefault();
      const cards = settingsCards().filter(c => c !== draggedCard);
      let target = null, best = Number.POSITIVE_INFINITY, before = true;
      cards.forEach(card => {
        const r = card.getBoundingClientRect(), cx=r.left+r.width/2, cy=r.top+r.height/2;
        const dx=event.clientX-cx, dy=event.clientY-cy, d=Math.sqrt(dx*dx+dy*dy);
        if (d < best) { best=d; target=card; before=Math.abs(dy)>Math.abs(dx) ? event.clientY<cy : event.clientX<cx; }
      });
      if (target) settingsBoard.insertBefore(draggedCard, before ? target : target.nextSibling);
      else settingsBoard.appendChild(draggedCard);
    });
    settingsBoard.addEventListener('drop', event => {
      if (!draggedCard) return;
      event.preventDefault();
      draggedCard.classList.remove('dragging');
      draggedCard = null;
      saveSettingsLayout();
    });
    settingsBoard.addEventListener('click', event => {
      const button = event.target.closest?.('[data-card-size]');
      if (!button) return;
      const card = button.closest('[data-settings-card]');
      if (!card) return;
      event.preventDefault();
      card.dataset.size = button.dataset.cardSize || 'l';
      saveSettingsLayout();
    });
    document.querySelector('[data-settings-save-layout]')?.addEventListener('click', event => {
      event.preventDefault(); saveSettingsLayout();
    });
  }

  // Creador visual de formularios y plantillas predefinidas.
  const fieldsTarget = document.querySelector('[data-fields], [data-builder-fields]');
  const fieldTemplate = document.getElementById('form-field-template') || document.getElementById('builder-field-template');
  const reindexFields = () => {
    fieldsTarget?.querySelectorAll('[data-field-row], .builder-field').forEach((row, index) => {
      const required = row.querySelector('input[name="field_required[]"]');
      if (required) required.value = String(index);
    });
  };
  const bindField = row => row.querySelector('[data-remove-field], [data-remove-builder-field]')?.addEventListener('click', () => { row.remove(); reindexFields(); });
  const addField = field => {
    if (!fieldTemplate || !fieldsTarget) return;
    const node = fieldTemplate.content.firstElementChild?.cloneNode(true); if (!node) return;
    const set = (selector, value) => { const input=node.querySelector(selector); if(input) input.value=value ?? ''; };
    set('[name="field_label[]"]', field?.label || ''); set('[name="field_name[]"]', field?.name || ''); set('[name="field_type[]"]', field?.type || 'text');
    set('[name="field_placeholder[]"]', field?.placeholder || ''); set('[name="field_options[]"]', String(field?.options || '').replace(/\\n/g, '\n'));
    const required=node.querySelector('[name="field_required[]"]'); if(required) required.checked=Boolean(field?.required);
    fieldsTarget.appendChild(node); bindField(node); reindexFields();
  };
  fieldsTarget?.querySelectorAll('[data-field-row], .builder-field').forEach(bindField);
  document.querySelectorAll('[data-add-field], [data-add-builder-field]').forEach(button => button.addEventListener('click', () => addField({})));
  reindexFields();

  let formTemplates = {};
  try { formTemplates = JSON.parse(document.getElementById('form-template-data')?.textContent || '{}'); } catch (_) {}
  document.querySelector('[data-apply-form-template]')?.addEventListener('click', () => {
    const select = document.querySelector('[data-form-template-select]');
    const selected = formTemplates[select?.value || '']; if (!selected || !fieldsTarget) return;
    if (fieldsTarget.children.length && !window.confirm(labels.confirmTemplate)) return;
    fieldsTarget.innerHTML = '';
    selected.fields.forEach(addField);
    const setMain = (name, value) => { const el=document.querySelector(`[name="${name}"]`); if(el && value !== undefined) el.value=value; };
    setMain('title', selected.title); setMain('description', selected.description); setMain('success_message', selected.success_message);
    reindexFields();
  });

  // SÓCRATES Copilot · asistente contextual conectado con el CRM.
  const assistantStorageKey = `socrates.crm.copilot.v220.${document.body.dataset.userId || 'me'}`;
  const assistantContext = () => ({
    page: (location.pathname.split('/').pop() || 'dashboard.php'),
    title: document.querySelector('.topbar-title h1')?.textContent?.trim() || document.title,
    url: location.href
  });
  const assistantStore = (() => {
    const key = `socrates.crm.copilot.history.${(window.SOCRATES_TOUR_CONTEXT?.userId || '0')}`;
    const load = () => { try { const v=JSON.parse(localStorage.getItem(key)||'[]'); return Array.isArray(v)?v.slice(-20):[]; } catch { return []; } };
    const save = rows => { try { localStorage.setItem(key,JSON.stringify(rows.slice(-20))); } catch {} };
    const push = row => { const rows=load(); rows.push(row); save(rows); };
    const clear = () => { try { localStorage.removeItem(key); } catch {} };
    return {load,push,clear};
  })();
  const assistantEscape = text => { const d=document.createElement('div'); d.textContent=String(text||''); return d.innerHTML; };
  const assistantScroll = messages => { if(messages) requestAnimationFrame(()=>{messages.scrollTop=messages.scrollHeight;}); };
  const assistantAppendActions = (bot, data, form) => {
    const actions = Array.isArray(data.actions) ? data.actions : [];
    if (actions.length) {
      const wrap=document.createElement('div'); wrap.className='assistant-response-actions';
      actions.forEach(a=>{ const link=document.createElement('a'); link.className=`assistant-action-button is-${a.style||'light'}`; link.href=a.url||'#'; link.innerHTML=`<span>${assistantEscape(a.label||'Abrir')}</span><b>→</b>`; wrap.appendChild(link); });
      bot.appendChild(wrap);
    }
    const suggestions=Array.isArray(data.suggestions)?data.suggestions:[];
    if(suggestions.length){
      const chips=document.createElement('div');chips.className='assistant-response-suggestions';
      suggestions.slice(0,5).forEach(q=>{const b=document.createElement('button');b.type='button';b.textContent=q;b.dataset.assistantSuggestion='';b.dataset.prompt=q;b.addEventListener('click',()=>assistantSubmitPrompt(form,q));chips.appendChild(b);});
      bot.appendChild(chips);
    }
  };
  const assistantTyping = messages => {
    const box=document.createElement('div');box.className='assistant-message bot assistant-typing';box.innerHTML='<i></i><i></i><i></i><span>Consultando el CRM…</span>';messages?.appendChild(box);assistantScroll(messages);return box;
  };
  const assistantSubmitPrompt = (form,prompt) => {
    if(!form) return; const input=form.querySelector('input[name="question"]'); if(!input) return; input.value=prompt; form.requestSubmit();
  };
  const askAssistant = async (form, messages) => {
    const input = form?.querySelector('input[name="question"]');
    const question = input?.value.trim(); if (!question) return;
    const userMessage = document.createElement('div'); userMessage.className = 'assistant-message user'; userMessage.textContent = question; messages?.appendChild(userMessage);
    assistantStore.push({role:'user',text:question,at:Date.now()}); input.value=''; assistantScroll(messages);
    const submit = form.querySelector('button[type="submit"],button'); if (submit) submit.disabled = true;
    const typing=assistantTyping(messages);
    try {
      const c=assistantContext();
      const body = new URLSearchParams({question,csrf_token:csrf(),page:c.page,title:c.title,url:c.url});
      const response = await fetch('assistant_answer.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body});
      const data = await response.json(); typing.remove();
      const bot = document.createElement('div'); bot.className = 'assistant-message bot assistant-rich-answer'; bot.innerHTML = data.answer || labels.empty;
      assistantAppendActions(bot,data,form); messages?.appendChild(bot);assistantScroll(messages);
      assistantStore.push({role:'bot',html:data.answer||labels.empty,actions:data.actions||[],suggestions:data.suggestions||[],at:Date.now()});
    } catch (error) { typing.remove(); const bot=document.createElement('div');bot.className='assistant-message bot';bot.textContent=labels.assistantError;messages?.appendChild(bot);assistantScroll(messages); }
    finally { if (submit) submit.disabled = false; input?.focus(); }
  };
  const restoreAssistantHistory = (messages,form) => {
    if(!messages || messages.dataset.historyRestored==='1') return; const rows=assistantStore.load(); if(!rows.length){messages.dataset.historyRestored='1';return;}
    const divider=document.createElement('div');divider.className='assistant-history-divider';divider.textContent=lang==='pt'?'CONVERSA RECENTE':(lang==='en'?'RECENT CONVERSATION':'CONVERSACIÓN RECIENTE');messages.appendChild(divider);
    rows.slice(-10).forEach(row=>{const el=document.createElement('div');el.className=`assistant-message ${row.role==='user'?'user':'bot assistant-rich-answer'}`;if(row.role==='user')el.textContent=row.text||'';else{el.innerHTML=row.html||'';assistantAppendActions(el,row,form);}messages.appendChild(el);});
    messages.dataset.historyRestored='1';assistantScroll(messages);
  };
  const bindAssistantForm = (form,messages) => { if(!form)return; form.addEventListener('submit',event=>{event.preventDefault();askAssistant(form,messages);});restoreAssistantHistory(messages,form); };
  const assistantForm=document.querySelector('[data-assistant-form]');
  const assistantMessages=document.querySelector('[data-assistant-messages]');
  bindAssistantForm(assistantForm,assistantMessages);

  const assistantFloat=document.querySelector('[data-assistant-float]');
  const assistantFloatPanel=document.querySelector('[data-assistant-float-panel]');
  const assistantFloatToggle=document.querySelector('[data-assistant-float-toggle]');
  const assistantFloatForm=document.querySelector('[data-assistant-float-form]');
  const assistantFloatMessages=document.querySelector('[data-assistant-float-messages]');
  bindAssistantForm(assistantFloatForm,assistantFloatMessages);
  const setAssistantOpen=open=>{
    assistantFloat?.classList.toggle('open',open);assistantFloatToggle?.setAttribute('aria-expanded',String(open));assistantFloatPanel?.setAttribute('aria-hidden',String(!open));
    if(open){restoreAssistantHistory(assistantFloatMessages,assistantFloatForm);window.setTimeout(()=>assistantFloatForm?.querySelector('input')?.focus(),80);}
  };
  assistantFloatToggle?.addEventListener('click',()=>setAssistantOpen(!assistantFloat?.classList.contains('open')));
  document.querySelector('[data-assistant-float-close]')?.addEventListener('click',()=>setAssistantOpen(false));

  document.addEventListener('click',event=>{
    const button=event.target.closest('[data-assistant-suggestion],[data-assistant-question]'); if(!button)return;
    const prompt=(button.dataset.prompt||button.textContent||'').trim(); if(!prompt)return;
    const scope=button.closest('.assistant-float-panel')?'float':'main'; const form=scope==='float'?assistantFloatForm:assistantForm;
    if(!form && assistantFloatForm){setAssistantOpen(true);assistantSubmitPrompt(assistantFloatForm,prompt);} else assistantSubmitPrompt(form,prompt);
  });
  document.querySelectorAll('[data-assistant-clear]').forEach(btn=>btn.addEventListener('click',()=>{assistantStore.clear();if(assistantMessages){assistantMessages.querySelectorAll('.assistant-message,.assistant-history-divider').forEach((el,i)=>{if(i>0)el.remove();});assistantMessages.dataset.historyRestored='1';}if(assistantFloatMessages){assistantFloatMessages.querySelectorAll('.assistant-message,.assistant-history-divider').forEach((el,i)=>{if(i>0)el.remove();});assistantFloatMessages.dataset.historyRestored='1';}}));
  if(window.SOCRATES_ASSISTANT_AUTORUN && assistantForm){window.setTimeout(()=>assistantSubmitPrompt(assistantForm,String(window.SOCRATES_ASSISTANT_AUTORUN)),250);}

})();
