(() => {
  'use strict';

  /*
   * Generador vectorial A4 para cotizaciones.
   * No captura la pantalla y por eso no depende del ancho del navegador,
   * del zoom ni de los estilos responsive.
   */

  // Same-origin only. First preference: vendored verified jsPDF 4.2.1 static file.
  // Second preference: PHP local resolver, which downloads once and caches upstream.
  // No direct browser request to public CDN domains is required.
  const librarySources = [
    'assets/js/vendor/jspdf.umd.min.js?v=4.2.1',
    'assets/js/vendor/jspdf-local.php?v=4.2.1'
  ];

  const C = {
    navy: [0, 47, 87],
    navy2: [5, 82, 132],
    blue: [0, 116, 199],
    orange: [241, 90, 36],
    orangeSoft: [255, 247, 240],
    orangeBorder: [255, 207, 177],
    text: [21, 49, 73],
    muted: [92, 116, 133],
    border: [217, 230, 238],
    light: [248, 251, 253],
    footer: [238, 244, 248],
    white: [255, 255, 255]
  };

  const PAGE = {
    width: 210,
    height: 297,
    left: 14,
    right: 14,
    contentWidth: 182,
    contentBottom: 276,
    footerLine: 282
  };

  // Never silently reuse an old jsPDF 2.x/3.x script from another page or cache.
  // The version pin matches the reviewed security update.
  const JSPDF_REQUIRED_VERSION = '4.2.1';
  const availableLibrary = () => {
    const ctor = window.jspdf && window.jspdf.jsPDF;
    return ctor && ctor.version === JSPDF_REQUIRED_VERSION ? ctor : null;
  };

  let loaderPromise = null;

  const loadScript = src => new Promise((resolve, reject) => {
    const existing = Array.from(document.querySelectorAll('script[data-onehub-jspdf]'))
      .find(script => script.src === src);
    if (existing && availableLibrary()) { resolve(); return; }
    // A previously failed or expired script must not prevent the next attempt.
    if (existing) existing.remove();

    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.crossOrigin = 'anonymous';
    script.dataset.onehubJspdf = JSPDF_REQUIRED_VERSION;
    let completed = false;
    const timeout = setTimeout(() => finish(false, 'Tiempo agotado al cargar la biblioteca PDF.'), 25000);
    function finish(ok, message) {
      if (completed) return;
      completed = true;
      clearTimeout(timeout);
      if (ok && availableLibrary()) resolve();
      else {
        script.remove();
        reject(new Error(message || `La biblioteca PDF ${JSPDF_REQUIRED_VERSION} no está disponible desde el servidor externo.`));
      }
    }
    script.onload = () => finish(true, `No se detectó jsPDF ${JSPDF_REQUIRED_VERSION} después de cargar ${src}.`);
    script.onerror = () => finish(false, `No se pudo cargar jsPDF 4.2.1 desde el sitio. Comprueba assets/js/vendor/jspdf-local.php?check=1, o sube manualmente jspdf.umd.min.js.`);
    document.head.appendChild(script);
  });

  const ensureLibrary = async () => {
    const alreadyLoaded = availableLibrary();
    if (alreadyLoaded) return alreadyLoaded;
    if (!loaderPromise) {
      loaderPromise = (async () => {
        let lastError = null;
        for (const url of librarySources) {
          try {
            await loadScript(url);
            const ctor = availableLibrary();
            if (ctor) return ctor;
          } catch (error) {
            console.warn('[ONE HUB PDF] Falló un origen jsPDF; se probará el siguiente.', error);
            lastError = error;
          }
        }
        throw lastError || new Error('jsPDF 4.2.1 no está disponible localmente. Consulta assets/js/vendor/jspdf-local.php?check=1.');
      })().catch(error => { loaderPromise = null; throw error; });
    }
    return loaderPromise;
  };

  const cleanText = value => String(value ?? '')
    .replace(/\u00a0/g, ' ')
    .replace(/[“”]/g, '"')
    .replace(/[‘’]/g, "'")
    .replace(/[–—]/g, '-')
    .replace(/…/g, '...')
    .replace(/•/g, '-')
    .replace(/[^\u0000-\u00ff\n\r\t]/g, ' ')
    .replace(/\r\n?/g, '\n')
    .trim();

  const parsePayload = source => {
    if (source && typeof source === 'object' && !(source instanceof Element)) return source;
    const element = source instanceof Element ? source : document.getElementById(String(source || 'quote-pdf-data'));
    if (!element) throw new Error('No se encontraron los datos de la cotización.');
    return JSON.parse(element.textContent || '{}');
  };

  const toAbsoluteUrl = url => {
    if (!url) return '';
    try { return new URL(url, document.baseURI).href; }
    catch { return String(url); }
  };

  const loadImageAsPng = url => new Promise(resolve => {
    if (!url) { resolve(null); return; }
    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.onload = () => {
      try {
        const maxSide = 1800;
        const scale = Math.min(1, maxSide / Math.max(image.naturalWidth || 1, image.naturalHeight || 1));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round((image.naturalWidth || 1) * scale));
        canvas.height = Math.max(1, Math.round((image.naturalHeight || 1) * scale));
        const context = canvas.getContext('2d');
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        resolve({
          data: canvas.toDataURL('image/png'),
          width: canvas.width,
          height: canvas.height
        });
      } catch (error) {
        console.warn('No fue posible preparar una imagen para el PDF.', error);
        resolve(null);
      }
    };
    image.onerror = () => resolve(null);
    image.src = toAbsoluteUrl(url);
  });

  const setFill = (doc, color) => doc.setFillColor(color[0], color[1], color[2]);
  const setDraw = (doc, color) => doc.setDrawColor(color[0], color[1], color[2]);
  const setText = (doc, color) => doc.setTextColor(color[0], color[1], color[2]);

  const roundedBox = (doc, x, y, width, height, fill, border = C.border, radius = 3) => {
    setFill(doc, fill);
    setDraw(doc, border);
    doc.setLineWidth(0.25);
    doc.roundedRect(x, y, width, height, radius, radius, 'FD');
  };

  const configureFont = (doc, size, style = 'normal', color = C.text) => {
    doc.setFont('helvetica', style);
    doc.setFontSize(size);
    setText(doc, color);
  };

  const splitLines = (doc, text, maxWidth, size = 8, style = 'normal') => {
    configureFont(doc, size, style);
    const normalized = cleanText(text);
    if (!normalized) return [];
    const paragraphs = normalized.split('\n');
    const lines = [];
    paragraphs.forEach((paragraph, index) => {
      if (paragraph === '') lines.push('');
      else lines.push(...doc.splitTextToSize(paragraph, maxWidth));
      if (index < paragraphs.length - 1 && paragraph !== '') lines.push('');
    });
    return lines;
  };

  const writeLines = (doc, lines, x, y, options = {}) => {
    const {
      size = 8,
      style = 'normal',
      color = C.text,
      lineHeight = size * 0.43,
      align = 'left',
      maxLines = null
    } = options;
    configureFont(doc, size, style, color);
    const visible = maxLines ? lines.slice(0, maxLines) : lines;
    visible.forEach((line, index) => {
      doc.text(String(line), x, y + (index * lineHeight), { align });
    });
    return y + (visible.length * lineHeight);
  };

  const fitFontSize = (doc, text, maxWidth, preferred, minimum = 6, style = 'bold') => {
    let size = preferred;
    while (size > minimum) {
      configureFont(doc, size, style);
      if (doc.getTextWidth(cleanText(text)) <= maxWidth) break;
      size -= 0.5;
    }
    return size;
  };

  const addContainedImage = (doc, image, x, y, maxWidth, maxHeight) => {
    if (!image?.data || !image.width || !image.height) return false;
    const ratio = Math.min(maxWidth / image.width, maxHeight / image.height);
    const width = image.width * ratio;
    const height = image.height * ratio;
    doc.addImage(image.data, 'PNG', x, y + ((maxHeight - height) / 2), width, height, undefined, 'FAST');
    return true;
  };

  const drawAccent = doc => {
    setFill(doc, C.blue);
    doc.rect(PAGE.left, 8, 111, 2.4, 'F');
    setFill(doc, C.orange);
    doc.rect(PAGE.left + 111, 8, PAGE.contentWidth - 111, 2.4, 'F');
  };

  const drawFirstHeader = (doc, data, assets) => {
    drawAccent(doc);

    if (!addContainedImage(doc, assets.brand, PAGE.left + 2, 13, 58, 18)) {
      configureFont(doc, 17, 'bold', C.blue);
      doc.text('SOCRATES', PAGE.left + 2, 23);
      configureFont(doc, 6.5, 'bold', C.orange);
      doc.text('AI Creative Studio', PAGE.left + 2, 28);
    }

    setDraw(doc, C.border);
    doc.setLineWidth(0.3);
    doc.line(76, 15, 76, 31);
    configureFont(doc, 8, 'bold', C.navy);
    doc.text(cleanText(data.brand_tagline), 80, 21);
    configureFont(doc, 6.2, 'normal', C.muted);
    doc.text(cleanText(data.brand_subtitle), 80, 26);

    roundedBox(doc, 154, 12.5, 42, 25, C.navy, C.navy, 4);
    configureFont(doc, 6.2, 'normal', [190, 222, 244]);
    doc.text(cleanText(data.labels?.quotation || 'COTIZACIÓN'), 192, 18, { align: 'right' });
    const numberSize = fitFontSize(doc, data.quotation_number, 35, 12.5, 8, 'bold');
    configureFont(doc, numberSize, 'bold', C.white);
    doc.text(cleanText(data.quotation_number), 192, 27, { align: 'right' });
    configureFont(doc, 6.5, 'normal', [207, 234, 255]);
    doc.text(cleanText(data.issue_date), 192, 33, { align: 'right' });

    setDraw(doc, [255, 205, 174]);
    doc.setLineWidth(0.25);
    doc.circle(199, 11, 22, 'S');
    return 43;
  };

  const drawCompactHeader = (doc, data, assets) => {
    drawAccent(doc);
    if (!addContainedImage(doc, assets.brand, PAGE.left, 13, 40, 12)) {
      configureFont(doc, 12, 'bold', C.blue);
      doc.text('SOCRATES', PAGE.left, 21);
    }
    configureFont(doc, 8, 'bold', C.navy);
    doc.text(cleanText(data.quotation_number), 196, 18, { align: 'right' });
    configureFont(doc, 6.5, 'normal', C.muted);
    doc.text(cleanText(data.client?.company_name || ''), 196, 23, { align: 'right' });
    setDraw(doc, C.border);
    doc.line(PAGE.left, 28, 196, 28);
    return 33;
  };

  const drawClientAndMeta = (doc, data, assets, startY) => {
    const clientX = PAGE.left;
    const clientW = 116;
    const gap = 4;
    const metaX = clientX + clientW + gap;
    const metaW = PAGE.contentWidth - clientW - gap;
    const logoSpace = assets.client ? 23 : 0;
    const textX = clientX + 5 + logoSpace;
    const textW = clientW - 10 - logoSpace;

    const companyLines = splitLines(doc, data.client?.company_name || '', textW, 12, 'bold').slice(0, 2);
    const detailValues = [];
    if (data.client?.recipient_name) detailValues.push(data.client.recipient_name);
    if (data.client?.tax_id) detailValues.push(`${data.labels?.tax_id || 'ID'}: ${data.client.tax_id}`);
    if (data.client?.address) detailValues.push(data.client.address);
    if (data.client?.email) detailValues.push(data.client.email);

    const detailLines = [];
    detailValues.forEach(value => detailLines.push(...splitLines(doc, value, textW, 7.2, 'normal')));
    const clientHeight = Math.max(39, 10 + (companyLines.length * 5.2) + (detailLines.length * 3.5) + 8);

    roundedBox(doc, clientX, startY, clientW, clientHeight, C.white, C.border, 3.5);
    configureFont(doc, 6, 'bold', C.muted);
    doc.text(cleanText(data.labels?.client || 'CLIENTE').toUpperCase(), textX, startY + 6);

    if (assets.client) {
      roundedBox(doc, clientX + 5, startY + 7, 18, 18, C.white, C.border, 2.5);
      addContainedImage(doc, assets.client, clientX + 6.5, startY + 8.5, 15, 15);
    }

    let cursor = startY + 12;
    cursor = writeLines(doc, companyLines, textX, cursor, {
      size: 12,
      style: 'bold',
      color: C.navy,
      lineHeight: 5.2
    });
    cursor += 0.8;
    writeLines(doc, detailLines, textX, cursor, {
      size: 7.2,
      style: 'normal',
      color: C.muted,
      lineHeight: 3.5
    });

    const cardGap = 2.3;
    const cardW = (metaW - cardGap) / 2;
    const cardH = (clientHeight - cardGap) / 2;
    const cards = [
      [data.labels?.number || 'Número', data.quotation_number],
      [data.labels?.date || 'Fecha', data.issue_date],
      [data.labels?.valid_until || 'Válida hasta', data.valid_until || '-'],
      [data.labels?.currency || 'Moneda', data.currency]
    ];

    cards.forEach((card, index) => {
      const col = index % 2;
      const row = Math.floor(index / 2);
      const x = metaX + (col * (cardW + cardGap));
      const y = startY + (row * (cardH + cardGap));
      roundedBox(doc, x, y, cardW, cardH, C.white, C.border, 3);
      configureFont(doc, 5.8, 'bold', C.muted);
      doc.text(cleanText(card[0]).toUpperCase(), x + 3.2, y + 5.5);
      const valueLines = splitLines(doc, card[1], cardW - 6.4, 7.8, 'bold').slice(0, 2);
      writeLines(doc, valueLines, x + 3.2, y + 11.2, {
        size: 7.8,
        style: 'bold',
        color: C.text,
        lineHeight: 3.8
      });
    });

    return startY + clientHeight + 6;
  };

  const columns = {
    description: { x: PAGE.left, width: 105 },
    quantity: { x: PAGE.left + 105, width: 17 },
    unit: { x: PAGE.left + 122, width: 30 },
    total: { x: PAGE.left + 152, width: 30 }
  };

  const drawTableHeader = (doc, data, y) => {
    setFill(doc, C.navy);
    doc.roundedRect(PAGE.left, y, PAGE.contentWidth, 8.5, 2.5, 2.5, 'F');
    doc.rect(PAGE.left, y + 4, PAGE.contentWidth, 4.5, 'F');
    configureFont(doc, 5.8, 'bold', C.white);
    doc.text(cleanText(data.labels?.description || 'DESCRIPCIÓN').toUpperCase(), columns.description.x + 3, y + 5.5);
    doc.text(cleanText(data.labels?.quantity || 'CANT.').toUpperCase(), columns.quantity.x + columns.quantity.width - 2.5, y + 5.5, { align: 'right' });
    doc.text(cleanText(data.labels?.unit_price || 'VALOR UNITARIO').toUpperCase(), columns.unit.x + columns.unit.width - 2.5, y + 5.5, { align: 'right' });
    doc.text(cleanText(data.labels?.total || 'TOTAL').toUpperCase(), columns.total.x + columns.total.width - 2.5, y + 5.5, { align: 'right' });
    return y + 8.5;
  };

  const newContentPage = (doc, data, assets, withTableHeader = false) => {
    doc.addPage();
    let y = drawCompactHeader(doc, data, assets);
    if (withTableHeader) y = drawTableHeader(doc, data, y);
    return y;
  };

  const drawItems = (doc, data, assets, startY) => {
    let y = drawTableHeader(doc, data, startY);
    let rowIndex = 0;
    const lineHeight = 3.5;

    for (const item of data.items || []) {
      const allLines = splitLines(doc, item.description || '', columns.description.width - 6, 7.2, 'normal');
      const descriptionLines = allLines.length ? allLines : [''];
      let offset = 0;
      let firstSegment = true;

      while (offset < descriptionLines.length) {
        const availableHeight = PAGE.contentBottom - y;
        const maxLines = Math.max(1, Math.floor((availableHeight - 5) / lineHeight));

        if (availableHeight < 12) {
          y = newContentPage(doc, data, assets, true);
          continue;
        }

        const count = Math.min(descriptionLines.length - offset, maxLines);
        const segment = descriptionLines.slice(offset, offset + count);
        const rowHeight = Math.max(9, (segment.length * lineHeight) + 5);

        if (y + rowHeight > PAGE.contentBottom + 0.1) {
          y = newContentPage(doc, data, assets, true);
          continue;
        }

        setFill(doc, rowIndex % 2 === 0 ? C.white : C.light);
        setDraw(doc, C.border);
        doc.setLineWidth(0.2);
        doc.rect(PAGE.left, y, PAGE.contentWidth, rowHeight, 'FD');
        [columns.quantity.x, columns.unit.x, columns.total.x].forEach(x => doc.line(x, y, x, y + rowHeight));

        writeLines(doc, segment, columns.description.x + 3, y + 4.2, {
          size: 7.2,
          style: 'normal',
          color: C.text,
          lineHeight
        });

        if (firstSegment) {
          configureFont(doc, 7, 'normal', C.text);
          doc.text(cleanText(item.quantity), columns.quantity.x + columns.quantity.width - 2.5, y + 5.2, { align: 'right' });
          doc.text(cleanText(item.unit_price), columns.unit.x + columns.unit.width - 2.5, y + 5.2, { align: 'right' });
          doc.text(cleanText(item.line_total), columns.total.x + columns.total.width - 2.5, y + 5.2, { align: 'right' });
        }

        y += rowHeight;
        offset += count;
        firstSegment = false;
      }
      rowIndex += 1;
    }

    return y;
  };

  const totalsRows = data => {
    const rows = [[data.labels?.subtotal || 'Subtotal', data.totals?.subtotal || '']];
    if (data.totals?.discount) rows.push([data.labels?.discount || 'Descuento', data.totals.discount]);
    if (data.totals?.tax_label) rows.push([data.totals.tax_label, data.totals.tax_amount]);
    return rows;
  };

  const drawTotalsCard = (doc, data, x, y, width) => {
    const rows = totalsRows(data);
    const height = 13 + (rows.length * 8) + 14;
    roundedBox(doc, x, y, width, height, C.navy, C.navy, 4);
    let cursor = y + 8;

    rows.forEach(row => {
      configureFont(doc, 6.8, 'normal', [210, 233, 250]);
      doc.text(cleanText(row[0]), x + 4, cursor);
      const amountSize = fitFontSize(doc, row[1], width - 28, 7.2, 5.8, 'bold');
      configureFont(doc, amountSize, 'bold', C.white);
      doc.text(cleanText(row[1]), x + width - 4, cursor, { align: 'right' });
      cursor += 8;
      setDraw(doc, [48, 101, 142]);
      doc.setLineWidth(0.2);
      doc.line(x + 4, cursor - 3.5, x + width - 4, cursor - 3.5);
    });

    setDraw(doc, C.orange);
    doc.setLineWidth(0.6);
    doc.line(x + 4, cursor - 1.5, x + width - 4, cursor - 1.5);
    configureFont(doc, 9.5, 'bold', C.white);
    doc.text(cleanText(data.labels?.grand_total || 'TOTAL'), x + 4, cursor + 7);
    const totalSize = fitFontSize(doc, data.totals?.total || '', width - 28, 11.5, 8, 'bold');
    configureFont(doc, totalSize, 'bold', [255, 181, 129]);
    doc.text(cleanText(data.totals?.total || ''), x + width - 4, cursor + 7, { align: 'right' });
    return height;
  };

  const buildInfoCards = data => {
    const cards = [];
    if (data.notes) cards.push({ title: data.labels?.notes || 'Observaciones', body: data.notes, type: 'normal' });
    if (data.payment_method_title || data.payment_method_details) {
      cards.push({
        title: data.labels?.payment_method || 'Forma de pago',
        strong: data.payment_method_title || '',
        body: data.payment_method_details || '',
        type: 'payment'
      });
    }
    if (data.terms) cards.push({ title: data.labels?.terms || 'Condiciones', body: data.terms, type: 'normal' });
    return cards;
  };

  const cardMetrics = (doc, card, width) => {
    const strongLines = splitLines(doc, card.strong || '', width - 8, 8.5, 'bold');
    const bodyLines = splitLines(doc, card.body || '', width - 8, 7, 'normal');
    const height = 10 + (strongLines.length * 4.1) + (bodyLines.length * 3.4) + (strongLines.length && bodyLines.length ? 1 : 0);
    return { strongLines, bodyLines, height: Math.max(18, height) };
  };

  const drawInfoCardBox = (doc, card, x, y, width, strongLines, bodyLines, continued = false) => {
    const height = Math.max(18, 10 + (strongLines.length * 4.1) + (bodyLines.length * 3.4) + (strongLines.length && bodyLines.length ? 1 : 0));
    const fill = card.type === 'payment' ? C.orangeSoft : C.light;
    const border = card.type === 'payment' ? C.orangeBorder : C.border;
    roundedBox(doc, x, y, width, height, fill, border, 3.5);
    if (card.type === 'payment') {
      setFill(doc, C.orange);
      doc.roundedRect(x, y, 1.7, height, 0.8, 0.8, 'F');
    }

    configureFont(doc, 6, 'bold', C.orange);
    const title = `${cleanText(card.title).toUpperCase()}${continued ? ' (CONT.)' : ''}`;
    doc.text(title, x + 4, y + 6);
    let cursor = y + 11;
    if (strongLines.length) {
      cursor = writeLines(doc, strongLines, x + 4, cursor, {
        size: 8.5,
        style: 'bold',
        color: C.navy,
        lineHeight: 4.1
      });
      cursor += 1;
    }
    writeLines(doc, bodyLines, x + 4, cursor, {
      size: 7,
      style: 'normal',
      color: C.muted,
      lineHeight: 3.4
    });
    return height;
  };

  const drawSummary = (doc, data, assets, startY) => {
    const leftW = 116;
    const gap = 4;
    const rightX = PAGE.left + leftW + gap;
    const rightW = PAGE.contentWidth - leftW - gap;
    let y = startY + 6;

    if (PAGE.contentBottom - y < 42) y = newContentPage(doc, data, assets, false);

    const summaryStart = y;
    const totalsHeight = drawTotalsCard(doc, data, rightX, summaryStart, rightW);
    const cards = buildInfoCards(data);
    let cardY = summaryStart;
    let currentWidth = leftW;
    let currentX = PAGE.left;

    for (const card of cards) {
      let metrics = cardMetrics(doc, card, currentWidth);
      let strongLines = metrics.strongLines;
      let bodyLines = metrics.bodyLines;
      let continued = false;

      while (strongLines.length || bodyLines.length || (!card.strong && !card.body && !continued)) {
        const available = PAGE.contentBottom - cardY;
        if (available < 20) {
          cardY = newContentPage(doc, data, assets, false);
          currentWidth = PAGE.contentWidth;
          currentX = PAGE.left;
          continued = true;
        }

        const fixedHeight = 10 + (strongLines.length * 4.1) + (strongLines.length && bodyLines.length ? 1 : 0);
        const maxBodyLines = bodyLines.length
          ? Math.max(1, Math.floor((PAGE.contentBottom - cardY - fixedHeight) / 3.4))
          : 0;
        const bodyChunk = bodyLines.slice(0, maxBodyLines);
        const candidateHeight = Math.max(18, fixedHeight + (bodyChunk.length * 3.4));

        if (candidateHeight > PAGE.contentBottom - cardY && cardY > 40) {
          cardY = newContentPage(doc, data, assets, false);
          currentWidth = PAGE.contentWidth;
          currentX = PAGE.left;
          continued = true;
          continue;
        }

        const drawnHeight = drawInfoCardBox(doc, card, currentX, cardY, currentWidth, strongLines, bodyChunk, continued);
        cardY += drawnHeight + 3;
        bodyLines = bodyLines.slice(bodyChunk.length);
        strongLines = [];

        if (!bodyLines.length) break;
        cardY = newContentPage(doc, data, assets, false);
        currentWidth = PAGE.contentWidth;
        currentX = PAGE.left;
        continued = true;
      }
    }

    return Math.max(cardY, summaryStart + totalsHeight + 3);
  };

  const drawPageFooters = (doc, data) => {
    const totalPages = doc.getNumberOfPages();
    for (let page = 1; page <= totalPages; page += 1) {
      doc.setPage(page);
      setDraw(doc, C.border);
      doc.setLineWidth(0.25);
      doc.line(PAGE.left, PAGE.footerLine, 196, PAGE.footerLine);
      configureFont(doc, 6.3, 'bold', C.navy);
      doc.text(cleanText(data.brand_name || 'SÓCRATES ONE · Creative Studio'), PAGE.left, 286.5);
      configureFont(doc, 5.8, 'normal', C.muted);
      doc.text(cleanText(data.brand_contact || ''), PAGE.left, 290.3);
      if (data.valid_until) {
        configureFont(doc, 5.8, 'normal', C.muted);
        doc.text(`${cleanText(data.labels?.validity || 'Válida hasta')} `, 196, 286.3, { align: 'right' });
        configureFont(doc, 6.3, 'bold', C.orange);
        doc.text(cleanText(data.valid_until), 196, 290.2, { align: 'right' });
      }
      configureFont(doc, 5.5, 'normal', C.muted);
      doc.text(`${page}/${totalPages}`, 105, 290.2, { align: 'center' });
    }
  };

  const generate = async payload => {
    const JsPDF = await ensureLibrary();
    const data = parsePayload(payload);
    const [brand, client] = await Promise.all([
      loadImageAsPng(data.brand_logo),
      loadImageAsPng(data.client?.logo)
    ]);
    const assets = { brand, client };

    const doc = new JsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
      compress: true,
      putOnlyUsedFonts: true
    });

    doc.setProperties({
      title: cleanText(data.quotation_number || 'Cotización'),
      subject: cleanText(data.labels?.quotation || 'Cotización'),
      author: 'SÓCRATES ONE · Creative Studio',
      creator: 'SÓCRATES ONE CRM'
    });

    let y = drawFirstHeader(doc, data, assets);
    y = drawClientAndMeta(doc, data, assets, y);
    y = drawItems(doc, data, assets, y);
    drawSummary(doc, data, assets, y);
    drawPageFooters(doc, data);
    return doc;
  };

  const nativePrintFallback = fallbackUrl => {
    if (fallbackUrl) {
      const url = new URL(fallbackUrl, document.baseURI);
      url.searchParams.set('autoprint', '1');
      window.location.assign(url.href);
    } else {
      window.print();
    }
  };

  const safeFilename = filename => {
    const clean = String(filename || 'cotizacion.pdf').replace(/[\\/:*?"<>|\u0000-\u001f]/g, '-').trim();
    return /\.pdf$/i.test(clean) ? clean : `${clean || 'cotizacion'}.pdf`;
  };

  // Blob/object URL avoids browser integrations around jsPDF.save (FileSaver).
  const triggerPdfDownload = (doc, filename) => {
    const pdf = doc.output('blob');
    if (!(pdf instanceof Blob) || pdf.size < 30) {
      throw new Error('La biblioteca generó un PDF vacío.');
    }
    const objectUrl = URL.createObjectURL(pdf);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = safeFilename(filename);
    link.style.display = 'none';
    document.body.appendChild(link);
    try {
      link.click();
    } finally {
      link.remove();
      setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
    }
  };

  const download = async (payload, filename, button = null, fallbackUrl = '') => {
    const originalHtml = button?.innerHTML || '';
    if (button) {
      button.disabled = true;
      button.textContent = 'GENERANDO PDF…';
    }
    let stage = 'load_or_generate';
    try {
      const doc = await generate(payload);
      stage = 'download';
      triggerPdfDownload(doc, filename);
      window.SocratesQuotePdf.lastError = null;
      return true;
    } catch (error) {
      console.error(`[ONE HUB PDF] Error en etapa ${stage}`, error);
      const details = String(error?.message || error || 'Error desconocido').slice(0, 210);
      window.SocratesQuotePdf.lastError = { stage, message: details };
      // Display a concrete reason instead of an unhelpful generic alert.
      const wantsPrint = window.confirm(
        `No fue posible descargar el PDF directamente.\n\nDetalle: ${details}\n\n¿Deseas abrir la versión para imprimir o guardar como PDF?`
      );
      if (wantsPrint) nativePrintFallback(fallbackUrl);
      return false;
    } finally {
      if (button) {
        button.disabled = false;
        button.innerHTML = originalHtml;
      }
    }
  };

  window.SocratesQuotePdf = { download, generate, lastError: null };
})();
