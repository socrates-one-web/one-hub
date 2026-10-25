(function(){
  'use strict';
  var html=document.documentElement;
  var toggle=document.getElementById('themeToggle');
  function currentTheme(){return html.getAttribute('data-theme')==='dark'?'dark':'light';}
  function applyTheme(theme){theme=theme==='dark'?'dark':'light';html.setAttribute('data-theme',theme);try{localStorage.setItem('emailsend-theme',theme);}catch(e){}if(toggle){toggle.setAttribute('aria-pressed',theme==='dark'?'true':'false');toggle.title=theme==='dark'?'Cambiar a modo claro':'Cambiar a modo oscuro';}}
  applyTheme(currentTheme());
  if(toggle)toggle.addEventListener('click',function(){applyTheme(currentTheme()==='dark'?'light':'dark');});

  var root=document.getElementById('globalSearch'), trigger=document.getElementById('globalSearchTrigger'), panel=document.getElementById('globalSearchPanel'), input=document.getElementById('globalSearchInput'), close=document.getElementById('globalSearchClose'), results=document.getElementById('globalSearchResults'), hint=document.getElementById('globalSearchHint');
  if(!root||!trigger||!panel||!input||!results)return;
  var timer=null, controller=null, active=-1;
  function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
  function openSearch(){panel.hidden=false;root.classList.add('open');setTimeout(function(){input.focus();input.select();},10);}
  function closeSearch(){panel.hidden=true;root.classList.remove('open');active=-1;}
  function setActive(i){var items=[].slice.call(results.querySelectorAll('a'));if(!items.length){active=-1;return;}active=(i+items.length)%items.length;items.forEach(function(a,n){a.classList.toggle('active',n===active);});items[active].scrollIntoView({block:'nearest'});}
  function render(items){active=-1;if(!items.length){results.innerHTML='';hint.textContent='No encontramos coincidencias. Prueba por nombre, email, asunto o lista.';hint.hidden=false;return;}hint.hidden=true;results.innerHTML=items.map(function(r){return '<a href="'+esc(r.url)+'"><span class="global-result-type">'+esc(r.type)+'</span><span class="global-result-copy"><strong>'+esc(r.title)+'</strong><small>'+esc(r.subtitle)+'</small></span><b>→</b></a>';}).join('');}
  function run(){var q=input.value.trim();clearTimeout(timer);if(controller)controller.abort();if(q.length<2){results.innerHTML='';hint.hidden=false;hint.textContent='Escribe al menos 2 caracteres para buscar.';return;}hint.hidden=false;hint.textContent='Buscando…';timer=setTimeout(function(){controller=new AbortController();fetch('index.php?route=global_search&q='+encodeURIComponent(q),{signal:controller.signal,headers:{'X-Requested-With':'fetch'}}).then(function(r){if(!r.ok)throw new Error('search');return r.json();}).then(function(data){render(data.results||[]);}).catch(function(err){if(err.name==='AbortError')return;results.innerHTML='';hint.hidden=false;hint.textContent='No fue posible buscar en este momento.';});},180);}
  trigger.addEventListener('click',function(){panel.hidden?openSearch():closeSearch();});
  if(close)close.addEventListener('click',closeSearch);
  input.addEventListener('input',run);
  input.addEventListener('keydown',function(e){if(e.key==='ArrowDown'){e.preventDefault();setActive(active+1);}else if(e.key==='ArrowUp'){e.preventDefault();setActive(active-1);}else if(e.key==='Enter'&&active>=0){var a=results.querySelectorAll('a')[active];if(a){e.preventDefault();location.href=a.href;}}else if(e.key==='Escape'){e.preventDefault();closeSearch();trigger.focus();}});
  document.addEventListener('keydown',function(e){if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();openSearch();}else if(e.key==='Escape'&&!panel.hidden){closeSearch();}});
  document.addEventListener('click',function(e){if(!root.contains(e.target))closeSearch();});
})();


/* EMAILSEND ONE v0.9.58 — Confirmaciones visuales del sistema */
(function(){
  'use strict';

  var pending=null, previousFocus=null;

  function createModal(){
    if(document.getElementById('emailsendConfirmModal')) return document.getElementById('emailsendConfirmModal');

    var wrap=document.createElement('div');
    wrap.id='emailsendConfirmModal';
    wrap.className='es-confirm-modal';
    wrap.hidden=true;
    wrap.setAttribute('aria-hidden','true');
    wrap.innerHTML=
      '<div class="es-confirm-backdrop" data-confirm-close></div>'+
      '<section class="es-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="esConfirmTitle" aria-describedby="esConfirmMessage">'+
        '<div class="es-confirm-accent"></div>'+
        '<button type="button" class="es-confirm-x" data-confirm-close aria-label="Cerrar">×</button>'+
        '<div class="es-confirm-head">'+
          '<div class="es-confirm-icon" id="esConfirmIcon" aria-hidden="true">'+
            '<svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6"/></svg>'+
          '</div>'+
          '<div><span class="es-confirm-kicker" id="esConfirmKicker">CONFIRMACIÓN</span><h2 id="esConfirmTitle">Confirmar acción</h2></div>'+
        '</div>'+
        '<p class="es-confirm-message" id="esConfirmMessage"></p>'+
        '<div class="es-confirm-note" id="esConfirmNote" hidden></div>'+
        '<div class="es-confirm-actions">'+
          '<button type="button" class="btn btn-light es-confirm-cancel" data-confirm-close>Cancelar</button>'+
          '<button type="button" class="btn btn-danger es-confirm-ok" id="esConfirmOk">Confirmar</button>'+
        '</div>'+
      '</section>';
    document.body.appendChild(wrap);

    wrap.querySelectorAll('[data-confirm-close]').forEach(function(el){
      el.addEventListener('click', closeModal);
    });

    wrap.querySelector('#esConfirmOk').addEventListener('click', function(){
      var action=pending;
      closeModal(false);
      if(action) action();
    });

    return wrap;
  }

  function iconSvg(variant){
    if(variant==='warning'){
      return '<svg viewBox="0 0 24 24"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg>';
    }
    if(variant==='info'){
      return '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 10v7m0-10h.01"/></svg>';
    }
    return '<svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6"/></svg>';
  }

  function openModal(opts, action){
    var modal=createModal();
    previousFocus=document.activeElement;
    pending=action || null;

    var variant=opts.variant||'danger';
    modal.dataset.variant=variant;
    modal.querySelector('#esConfirmIcon').innerHTML=iconSvg(variant);
    modal.querySelector('#esConfirmTitle').textContent=opts.title||'Confirmar acción';
    modal.querySelector('#esConfirmMessage').textContent=opts.message||'¿Quieres continuar?';

    var note=modal.querySelector('#esConfirmNote');
    if(opts.note){
      note.textContent=opts.note;
      note.hidden=false;
    }else{
      note.textContent='';
      note.hidden=true;
    }

    var ok=modal.querySelector('#esConfirmOk');
    ok.textContent=opts.label||'Confirmar';
    ok.className='btn es-confirm-ok '+(variant==='warning'?'btn-orange':variant==='info'?'btn-blue':'btn-danger');

    modal.hidden=false;
    modal.setAttribute('aria-hidden','false');
    document.body.classList.add('es-modal-open');
    requestAnimationFrame(function(){modal.classList.add('is-open');});
    setTimeout(function(){ok.focus();},30);
  }

  function closeModal(restoreFocus){
    var modal=document.getElementById('emailsendConfirmModal');
    if(!modal)return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden','true');
    document.body.classList.remove('es-modal-open');
    pending=null;
    setTimeout(function(){
      modal.hidden=true;
      if(restoreFocus!==false && previousFocus && typeof previousFocus.focus==='function'){
        previousFocus.focus();
      }
    },160);
  }

  window.emailSendConfirm=function(options){
    return new Promise(function(resolve){
      openModal(options||{}, function(){resolve(true);});
      var modal=document.getElementById('emailsendConfirmModal');
      var cancelHandler=function(e){
        var close=e.target.closest('[data-confirm-close]');
        if(!close)return;
        modal.removeEventListener('click',cancelHandler,true);
        resolve(false);
      };
      modal.addEventListener('click',cancelHandler,true);
    });
  };

  document.addEventListener('click', function(e){
    var el=e.target.closest('[data-confirm]');
    if(!el || el.disabled)return;

    if(el.dataset.confirmBypass==='1'){
      delete el.dataset.confirmBypass;
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    var form=el.form || el.closest('form');
    var href=el.getAttribute('href');

    openModal({
      variant:el.dataset.confirmVariant||'danger',
      title:el.dataset.confirmTitle||'Confirmar acción',
      message:el.dataset.confirmMessage||'¿Quieres continuar?',
      note:el.dataset.confirmNote||'',
      label:el.dataset.confirmLabel||'Confirmar'
    }, function(){
      el.dataset.confirmBypass='1';
      if(form){
        if(typeof el.click==='function'){
          el.click();
        }else if(form.requestSubmit){
          form.requestSubmit();
        }else{
          form.submit();
        }
      }else if(href){
        window.location.href=href;
      }
      setTimeout(function(){delete el.dataset.confirmBypass;},0);
    });
  }, true);

  document.addEventListener('keydown', function(e){
    var modal=document.getElementById('emailsendConfirmModal');
    if(!modal || modal.hidden)return;
    if(e.key==='Escape'){
      e.preventDefault();
      closeModal();
    }
  });
})();
