/**
 * Static Portfolio Demo — Client-Side CSV & Print-to-PDF Export Utilities
 *
 * Replaces app/Http/Controllers/Export/* (StreamedResponse CSV + DomPDF).
 */

import { escapeHtml } from './layout.js';

function ensurePrintStylesheet() {
  if (document.querySelector('link[href*="print.css"]')) return;
  const link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = 'assets/css/print.css';
  document.head.appendChild(link);
}

function getOrCreatePrintArea() {
  let area = document.getElementById('print-area');
  if (!area) {
    area = document.createElement('div');
    area.id = 'print-area';
    document.body.appendChild(area);
  }
  return area;
}

function escapeCsvCell(value) {
  const str = value === null || value === undefined ? '' : String(value);
  if (/[",\r\n]/.test(str)) {
    return `"${str.replace(/"/g, '""')}"`;
  }
  return str;
}

/**
 * Generates a UTF-8 BOM CSV file via Blob and triggers browser download.
 */
export function exportCsv(filename, headers, rows) {
  const lines = [
    headers.map(escapeCsvCell).join(','),
    ...rows.map((row) => row.map(escapeCsvCell).join(',')),
  ];

  const csvContent = '\uFEFF' + lines.join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.download = filename.endsWith('.csv') ? filename : `${filename}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

/**
 * Renders a structured print report into `#print-area` and invokes `window.print()`
 * so users can print or Save as PDF without backend DomPDF.
 *
 * @param {Object} options
 * @param {string} options.title - Document and report heading
 * @param {string} [options.subtitle] - Optional centered subtitle
 * @param {string} [options.infoHtml] - Optional sanitized/trusted HTML box above table
 * @param {string[]} [options.headers] - Table column headers (for list reports)
 * @param {Array<Array<string|number>>} [options.rows] - Table rows (escaped automatically)
 * @param {Array<[string, string|number]>} [options.detailRows] - Key-value rows (for single-record PDF)
 * @param {Array<{title: string, items: string[]}>} [options.sections] - Bulleted sections below table
 */
export function exportPrintReport({
  title,
  subtitle = '',
  infoHtml = '',
  headers = null,
  rows = null,
  detailRows = null,
  sections = [],
}) {
  ensurePrintStylesheet();
  const area = getOrCreatePrintArea();

  const parts = [`<h2 class="print-title">${escapeHtml(title)}</h2>`];

  if (subtitle) {
    parts.push(`<p class="print-subtitle">${escapeHtml(subtitle)}</p>`);
  }

  if (infoHtml) {
    parts.push(`<div class="print-info-box">${infoHtml}</div>`);
  }

  if (Array.isArray(detailRows) && detailRows.length > 0) {
    const body = detailRows
      .map(
        ([label, val]) =>
          `<tr><td class="label">${escapeHtml(label)}</td><td class="value">${escapeHtml(
            val ?? '-'
          )}</td></tr>`
      )
      .join('');
    parts.push(`<table class="print-detail-table"><tbody>${body}</tbody></table>`);
  }

  if (Array.isArray(headers) && Array.isArray(rows)) {
    const thead = headers.map((h) => `<th>${escapeHtml(h)}</th>`).join('');
    const tbody =
      rows.length > 0
        ? rows
            .map(
              (r) =>
                `<tr>${r
                  .map((cell) => `<td>${escapeHtml(cell ?? '-')}</td>`)
                  .join('')}</tr>`
            )
            .join('')
        : `<tr><td colspan="${headers.length}" style="text-align:center;">Tidak ada data.</td></tr>`;

    parts.push(
      `<table class="print-table"><thead><tr>${thead}</tr></thead><tbody>${tbody}</tbody></table>`
    );
  }

  for (const sec of sections) {
    const itemsHtml =
      sec.items && sec.items.length > 0
        ? sec.items
            .map((item) => `<div class="print-list-item">&bull; ${escapeHtml(item)}</div>`)
            .join('')
        : `<div class="print-list-item">Tidak ada data</div>`;
    parts.push(`
      <div class="print-section">
        <div class="print-section-title">${escapeHtml(sec.title)}</div>
        ${itemsHtml}
      </div>
    `);
  }

  const timestamp = new Date().toLocaleString('id-ID');
  parts.push(`<div class="print-meta">Dicetak pada ${escapeHtml(timestamp)} (Mode Demo Statis)</div>`);

  area.innerHTML = parts.join('');

  const previousTitle = document.title;
  document.title = title;

  const restore = () => {
    document.title = previousTitle;
    window.removeEventListener('afterprint', restore);
  };
  window.addEventListener('afterprint', restore);

  window.print();
}

