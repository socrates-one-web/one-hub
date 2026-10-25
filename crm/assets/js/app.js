(() => {
  const root = document.documentElement;
  document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('soc-crm-theme', next); } catch (e) {}
  });
  const sidebar = document.getElementById('sidebar');
  const closeSidebar = () => document.body.classList.remove('sidebar-open');
  document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
  document.querySelector('[data-sidebar-overlay]')?.addEventListener('click', closeSidebar);
  sidebar?.querySelectorAll('a').forEach(a => a.addEventListener('click', closeSidebar));
  document.querySelectorAll('[data-confirm]').forEach(el => el.addEventListener('click', e => {
    if (!window.confirm(el.dataset.confirm || '¿Confirmar esta acción?')) e.preventDefault();
  }));
  const contract = document.querySelector('[name="contract_type"]');
  const customWrap = document.querySelector('[data-custom-period]');
  const updateCustom = () => { if (customWrap) customWrap.hidden = contract?.value !== 'custom'; };
  contract?.addEventListener('change', updateCustom); updateCustom();

  const roleSelect = document.querySelector('[data-role-select]');
  if (roleSelect) {
    const applyRole = () => {
      const defaults = JSON.parse(roleSelect.selectedOptions[0]?.dataset.permissions || '[]');
      if (roleSelect.value === 'custom') return;
      document.querySelectorAll('[name="permissions[]"]').forEach(cb => cb.checked = defaults.includes(cb.value));
    };
    roleSelect.addEventListener('change', applyRole);
  }

  const serviceCatalogSelect = document.querySelector('[data-service-catalog]');
  if (serviceCatalogSelect) {
    let serviceData = {};
    try { serviceData = JSON.parse(document.getElementById('service-catalog-data')?.textContent || '{}'); } catch (e) {}
    const fillService = () => {
      const item = serviceData[serviceCatalogSelect.value];
      if (!item) return;
      const set = (selector, value) => { const el=document.querySelector(selector); if(el) el.value=value ?? ''; };
      set('[data-service-name]', item.name_es);
      set('[data-service-category]', item.category);
      set('[data-service-description]', item.description_es);
      set('[data-service-contract]', item.default_contract_type || 'one_time');
      set('[data-service-custom]', item.custom_period_months || '');
      const cur=document.querySelector('[data-service-currency]')?.value || 'USD';
      set('[data-service-amount]', item['price_' + cur.toLowerCase()] ?? '');
      contract?.dispatchEvent(new Event('change'));
    };
    serviceCatalogSelect.addEventListener('change', fillService);
    document.querySelector('[data-service-currency]')?.addEventListener('change', () => {
      const item=serviceData[serviceCatalogSelect.value]; if(!item) return;
      const cur=document.querySelector('[data-service-currency]').value;
      const amount=document.querySelector('[data-service-amount]'); if(amount) amount.value=item['price_'+cur.toLowerCase()] ?? '';
    });
  }

  const clientSelect = document.querySelector('[data-quote-client]');
  const quoteLanguage = document.querySelector('[name="language"]');
  const recipientName = document.querySelector('[name="recipient_name"]');
  const recipientEmail = document.querySelector('[name="recipient_email"]');
  clientSelect?.addEventListener('change', () => {
    const option = clientSelect.selectedOptions[0];
    if (!option) return;
    if (recipientName) recipientName.value = option.dataset.contact || '';
    if (recipientEmail) recipientEmail.value = option.dataset.email || '';
    if (quoteLanguage) quoteLanguage.value = option.dataset.language || 'es';
    if (currencySelect) currencySelect.value = option.dataset.currency || 'USD';
    quoteLanguage?.dispatchEvent(new Event('change'));
    currencySelect?.dispatchEvent(new Event('change'));
    rebuildPaymentMethods(false);
    applyPaymentMethod();
    recalculate();
  });

  const quoteItems = document.querySelector('[data-quote-items]');
  const addItemButton = document.querySelector('[data-add-quote-item]');
  const rowTemplate = document.getElementById('quote-item-template');
  const currencySelect = document.querySelector('[name="currency"]');
  const currencyDecimals = () => currencySelect?.value === 'CLP' ? 0 : 2;
  const money = n => new Intl.NumberFormat('es-CL', {minimumFractionDigits: currencyDecimals(), maximumFractionDigits: currencyDecimals()}).format(Number(n || 0));
  const catalogData = (() => { try { return JSON.parse(document.getElementById('catalog-data')?.textContent || '{}'); } catch(e) { return {}; } })();
  const paymentMethodSelect = document.querySelector('[data-payment-method-select]');
  const paymentMethodTitle = document.querySelector('[data-payment-method-title]');
  const paymentMethodDetails = document.querySelector('[data-payment-method-details]');
  const paymentMethodsData = (() => { try { return JSON.parse(document.getElementById('payment-methods-data')?.textContent || '{}'); } catch(e) { return {}; } })();
  const normalizeText = value => String(value || '').trim().toLocaleLowerCase('es');
  const currentClientCountry = () => clientSelect?.selectedOptions?.[0]?.dataset?.country || '';
  const paymentMethodScore = item => {
    const currency = currencySelect?.value || 'CLP';
    const language = quoteLanguage?.value || 'es';
    const country = normalizeText(currentClientCountry());
    const methodCountry = normalizeText(item.country);
    let score = 0;
    if (item.currency === currency) score += 50; else score -= 100;
    if (item.language === language) score += 15;
    if (!methodCountry) score += 5;
    else if (country && methodCountry === country) score += 35;
    else score -= 20;
    if (Number(item.is_default || 0)) score += 20;
    score -= Math.min(999, Number(item.sort_order || 0)) / 1000;
    return score;
  };
  const rebuildPaymentMethods = (preserve = true) => {
    if (!paymentMethodSelect || !currencySelect) return;
    const previousValue = String(paymentMethodSelect.value || paymentMethodSelect.dataset.currentPayment || '');
    const items = Object.values(paymentMethodsData)
      .filter(item => Number(item.is_active || 0) || String(item.id) === previousValue)
      .sort((a, b) => paymentMethodScore(b) - paymentMethodScore(a) || String(a.name || '').localeCompare(String(b.name || ''), 'es'));

    paymentMethodSelect.innerHTML = '<option value="">Seleccionar / personalizada</option>';
    let preserved = false;
    items.forEach(item => {
      const option = document.createElement('option');
      option.value = String(item.id);
      const countryLabel = String(item.country || 'Todos los países');
      option.textContent = `${countryLabel} · ${item.currency} · ${item.name}`;
      option.dataset.title = item.name || '';
      option.dataset.content = item.details || '';
      option.dataset.currency = item.currency || '';
      option.dataset.language = item.language || '';
      option.dataset.country = item.country || '';
      if (preserve && previousValue === String(item.id)) {
        option.selected = true;
        preserved = true;
      }
      paymentMethodSelect.appendChild(option);
    });

    if (!preserved && !preserve) {
      const preferred = items.find(item => paymentMethodScore(item) >= 50) || items[0];
      if (preferred) {
        paymentMethodSelect.value = String(preferred.id);
        if (paymentMethodTitle) paymentMethodTitle.value = preferred.name || '';
        if (paymentMethodDetails) paymentMethodDetails.value = preferred.details || '';
      }
    }
    paymentMethodSelect.dataset.currentPayment = paymentMethodSelect.value || '';
  };
  const applyPaymentMethod = () => {
    if (!paymentMethodSelect) return;
    const option = paymentMethodSelect.selectedOptions[0];
    paymentMethodSelect.dataset.currentPayment = paymentMethodSelect.value || '';
    if (!option || !option.value) return;
    if (paymentMethodTitle) paymentMethodTitle.value = option.dataset.title || '';
    if (paymentMethodDetails) paymentMethodDetails.value = option.dataset.content || '';
  };
  const fillCatalog = row => {
    const select = row.querySelector('[data-item-catalog]');
    const id = select?.value;
    if (!id || !catalogData[id]) return;
    const item = catalogData[id];
    const lang = quoteLanguage?.value || 'es';
    const cur = currencySelect?.value || 'CLP';
    const desc = row.querySelector('[data-item-description]');
    const price = row.querySelector('[data-item-price]');
    const name = item['name_' + lang] || item.name_es || '';
    const description = item['description_' + lang] || item.description_es || '';
    if (desc) desc.value = description ? `${name}\n${description}` : name;
    if (price && item['price_' + cur.toLowerCase()] !== null && item['price_' + cur.toLowerCase()] !== '') price.value = item['price_' + cur.toLowerCase()];
    recalculate();
  };
  const recalculate = () => {
    let subtotal = 0;
    quoteItems?.querySelectorAll('[data-quote-row]').forEach(row => {
      const qty = Number(row.querySelector('[data-item-quantity]')?.value || 0);
      const price = Number(row.querySelector('[data-item-price]')?.value || 0);
      const total = qty * price; subtotal += total;
      const out = row.querySelector('[data-line-total]'); if (out) out.textContent = money(total);
    });
    const discount = Math.max(0, Number(document.querySelector('[name="discount_amount"]')?.value || 0));
    const taxRate = Math.max(0, Number(document.querySelector('[name="tax_rate"]')?.value || 0));
    const taxable = Math.max(0, subtotal - discount); const tax = taxable * taxRate / 100; const total = taxable + tax;
    [['subtotal',subtotal],['discount',discount],['tax',tax],['total',total]].forEach(([k,v]) => { const el=document.querySelector(`[data-summary-${k}]`); if(el) el.textContent=money(v); });
  };
  const bindRow = row => {
    row.querySelector('[data-item-catalog]')?.addEventListener('change', () => fillCatalog(row));
    row.querySelectorAll('input,textarea').forEach(el => el.addEventListener('input', recalculate));
    row.querySelector('[data-remove-item]')?.addEventListener('click', () => { if (quoteItems.querySelectorAll('[data-quote-row]').length > 1) row.remove(); else { row.querySelectorAll('input,textarea').forEach(x=>x.value=''); row.querySelector('select').value=''; } recalculate(); });
  };
  quoteItems?.querySelectorAll('[data-quote-row]').forEach(bindRow);
  addItemButton?.addEventListener('click', () => { if (!rowTemplate || !quoteItems) return; const node=rowTemplate.content.firstElementChild.cloneNode(true); quoteItems.appendChild(node); bindRow(node); recalculate(); });
  quoteLanguage?.addEventListener('change', () => {
    quoteItems?.querySelectorAll('[data-quote-row]').forEach(row => { if(row.querySelector('[data-item-catalog]')?.value) fillCatalog(row); });
    rebuildPaymentMethods(true);
  });
  currencySelect?.addEventListener('change', () => {
    quoteItems?.querySelectorAll('[data-quote-row]').forEach(row => { if(row.querySelector('[data-item-catalog]')?.value) fillCatalog(row); });
    rebuildPaymentMethods(true);
  });
  paymentMethodSelect?.addEventListener('change', applyPaymentMethod);
  document.querySelectorAll('[name="discount_amount"],[name="tax_rate"]').forEach(el=>el.addEventListener('input',recalculate));
  rebuildPaymentMethods(true);
  recalculate();
})();


(() => {
  const menu = document.querySelector('[data-profile-menu]');
  const toggle = document.querySelector('[data-profile-toggle]');
  const dropdown = document.querySelector('[data-profile-dropdown]');
  if (!menu || !toggle || !dropdown) return;
  const close = () => {
    menu.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
  };
  toggle.addEventListener('click', event => {
    event.stopPropagation();
    const open = menu.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  dropdown.addEventListener('click', event => event.stopPropagation());
  document.addEventListener('click', close);
  document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
})();

(() => {
  document.querySelectorAll('[data-avatar-preview-input]').forEach(input => {
    input.addEventListener('change', () => {
      const file = input.files?.[0];
      const target = document.querySelector(input.dataset.avatarPreviewInput);
      if (!file || !target) return;
      const reader = new FileReader();
      reader.onload = () => {
        target.innerHTML = `<img src="${String(reader.result)}" alt="Vista previa">`;
        target.classList.add('avatar-photo');
      };
      reader.readAsDataURL(file);
    });
  });
})();

// CRM 1.6.2: comportamiento responsivo y cambios de orientación.
(() => {
  const desktopBreakpoint = 1100;
  const sidebar = document.getElementById('sidebar');
  const closeSidebar = () => document.body.classList.remove('sidebar-open');
  let lastWidth = window.innerWidth;
  const syncResponsiveState = () => {
    const width = window.innerWidth;
    if (width > desktopBreakpoint) {
      closeSidebar();
      document.querySelectorAll('.nav-group.open').forEach(group => group.classList.remove('open'));
    }
    if (Math.abs(width - lastWidth) > 40) {
      window.setTimeout(() => window.dispatchEvent(new Event('resize')), 80);
      lastWidth = width;
    }
  };
  window.addEventListener('orientationchange', () => window.setTimeout(syncResponsiveState, 180));
  window.addEventListener('resize', syncResponsiveState, {passive:true});
  sidebar?.querySelectorAll('.nav-flyout a').forEach(link => link.addEventListener('click', closeSidebar));
})();
