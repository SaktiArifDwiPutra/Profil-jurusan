/**
 * Static Portfolio Demo — Data Mapel Controller
 *
 * Mirrors app/Http/Controllers/Utama/MapelController.php,
 * app/Http/Controllers/Export/MapelController.php, and resources/views/admin/mapel/index.blade.php
 */

import {
  getMapelWithPengampuAll,
  insert,
  updateById,
  removeById,
  validateFields,
} from '../store.js';
import { requireAuth } from '../auth.js';
import {
  renderLayout,
  confirmAction,
  showSuccess,
  showValidationErrors,
  escapeHtml,
} from '../layout.js';
import { exportCsv, exportPrintReport } from '../export.js';

let currentUser = null;
let selectedMapel = null;
let detailModalInstance = null;

function getUniqueGuruNames(m) {
  const names = m.mapelDiampu
    .map((a) => a.guru?.nama)
    .filter(Boolean);
  return [...new Set(names)];
}

function applySearch() {
  const q = (document.getElementById('searchInput')?.value || '').toLowerCase();
  document.querySelectorAll('.mapel-card-col').forEach((col) => {
    const match =
      (col.dataset.nama || '').includes(q) ||
      (col.dataset.kategori || '').includes(q) ||
      (col.dataset.gurus || '').includes(q);
    col.style.display = match ? '' : 'none';
  });
}

function renderMapelPage() {
  const mapels = getMapelWithPengampuAll();
  const container = document.getElementById('mapelContainer');

  container.innerHTML = mapels
    .map((m) => {
      const guruNames = getUniqueGuruNames(m);
      const badgeClass = m.kategori === 'Produktif' ? 'primary' : 'secondary';
      const gurusHtml =
        guruNames.length > 0
          ? escapeHtml(guruNames.join(', '))
          : '<span class="text-muted">Belum ada guru pengampu</span>';

      return `
        <div class="col mapel-card-col"
             data-id="${m.id_mapel}"
             data-nama="${escapeHtml(m.nama_mapel.toLowerCase())}"
             data-kategori="${escapeHtml(m.kategori.toLowerCase())}"
             data-gurus="${escapeHtml(guruNames.join(' ').toLowerCase())}">
          <div class="card siswa-card shadow-sm h-100" data-open-id="${m.id_mapel}">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-start">${escapeHtml(m.nama_mapel)}</h5>
                <span class="badge rounded-pill text-bg-${badgeClass}">${escapeHtml(m.kategori)}</span>
              </div>
              <div class="mt-2" style="font-size:.9rem;color:#555;">${gurusHtml}</div>
            </div>
          </div>
        </div>
      `;
    })
    .join('');

  container.querySelectorAll('[data-open-id]').forEach((card) => {
    card.addEventListener('click', () => openDetailModal(Number(card.dataset.openId)));
  });

  document.getElementById('stat-total-mapel').textContent = mapels.length;

  const kategoriCount = {};
  mapels.forEach((m) => {
    kategoriCount[m.kategori] = (kategoriCount[m.kategori] || 0) + 1;
  });
  document.getElementById('stat-kategori-list').innerHTML = Object.entries(kategoriCount)
    .map(
      ([k, cnt]) =>
        `<div style="font-size:.95rem; color:#333; margin-bottom:4px;">${escapeHtml(k)}: <strong>${cnt}</strong></div>`
    )
    .join('');

  const recent = [...mapels].sort((a, b) => Number(b.id_mapel) - Number(a.id_mapel)).slice(0, 6);
  const recentList = document.getElementById('recent-mapel-list');
  recentList.innerHTML =
    recent.length > 0
      ? recent
          .map((rm) => {
            const gNames = getUniqueGuruNames(rm).join(', ') || 'Belum ada guru';
            return `
              <div class="recent-item" style="cursor:pointer" data-recent-id="${rm.id_mapel}">
                <div style="min-width:0;">
                  <strong>${escapeHtml(rm.nama_mapel)}</strong>
                  <div style="font-size:0.85rem;color:#666">${escapeHtml(rm.kategori)}</div>
                  <div style="font-size:0.82rem;color:#666">${escapeHtml(gNames)}</div>
                </div>
              </div>
            `;
          })
          .join('')
      : '<div class="recent-item">Belum ada data</div>';

  recentList.querySelectorAll('[data-recent-id]').forEach((item) => {
    item.addEventListener('click', () => openDetailModal(Number(item.dataset.recentId)));
  });

  applySearch();
}

function switchModalMode(mode) {
  const modalEl = document.getElementById('detailModalMapel');
  const viewBox = document.getElementById('modal-mapel-view');
  const editBox = document.getElementById('modal-mapel-edit');
  const viewBtns = document.getElementById('view-buttons');
  const editBtns = document.getElementById('edit-buttons');
  const exportWrap = document.getElementById('export-wrap');

  if (mode === 'edit' && currentUser?.role === 'admin') {
    viewBox.style.display = 'none';
    editBox.style.display = 'block';
    viewBtns.setAttribute('style', 'display: none !important;');
    editBtns.setAttribute('style', 'display: flex !important; justify-content: center;');
    exportWrap.style.display = 'none';
    modalEl.classList.remove('vertical');
  } else {
    viewBox.style.display = 'block';
    editBox.style.display = 'none';
    viewBtns.setAttribute('style', 'display: flex !important; justify-content: center;');
    editBtns.setAttribute('style', 'display: none !important;');
    exportWrap.style.display = 'block';
    modalEl.classList.add('vertical');
  }
}

function openDetailModal(id) {
  const m = getMapelWithPengampuAll().find((item) => Number(item.id_mapel) === Number(id));
  if (!m) return;
  selectedMapel = m;

  document.getElementById('modal-mapel-nama').textContent = m.nama_mapel || '-';
  document.getElementById('detail-nama').textContent = m.nama_mapel || '-';
  document.getElementById('detail-kategori').textContent = m.kategori || '-';

  const guruListEl = document.getElementById('detail-guru-list');
  const guruNames = getUniqueGuruNames(m);
  if (guruNames.length > 0) {
    guruListEl.innerHTML = guruNames
      .map((name) => `<div class="py-1 border-bottom small">${escapeHtml(name)}</div>`)
      .join('');
  } else {
    guruListEl.innerHTML = '<div class="text-muted small">Belum ada guru pengampu</div>';
  }

  if (currentUser?.role === 'admin') {
    document.getElementById('edit-id-mapel').value = m.id_mapel;
    document.getElementById('edit-nama-mapel').value = m.nama_mapel;
    document.getElementById('edit-kategori').value = m.kategori;
  }

  switchModalMode('view');
  detailModalInstance?.show();
}

function validateMapelPayload(payload) {
  return validateFields(payload, {
    nama_mapel: { label: 'Nama Mapel', required: true, maxLength: 100 },
    kategori: { label: 'Kategori', required: true, in: ['Produktif', 'Umum'] },
  });
}

document.addEventListener('DOMContentLoaded', () => {
  currentUser = requireAuth('login.html');
  if (!currentUser) return;

  renderLayout({ activeNav: 'mapel', title: 'Data Mapel - Profil Jurusan RPL' });

  const isAdmin = currentUser.role === 'admin';
  if (isAdmin) {
    document.getElementById('btnToggleAddMapel').classList.remove('d-none');
    document.getElementById('formAdd').classList.remove('d-none');
    document.getElementById('btnModalEdit').classList.remove('d-none');
    document.getElementById('btnModalDelete').classList.remove('d-none');
  }

  const modalEl = document.getElementById('detailModalMapel');
  detailModalInstance = new window.bootstrap.Modal(modalEl);
  modalEl.addEventListener('hidden.bs.modal', () => switchModalMode('view'));

  renderMapelPage();

  document.getElementById('searchInput')?.addEventListener('input', applySearch);

  document.getElementById('formTambahMapel')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!isAdmin) return;

    const payload = {
      nama_mapel: document.getElementById('add-nama-mapel').value.trim(),
      kategori: document.getElementById('add-kategori').value,
    };

    const check = validateMapelPayload(payload);
    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Simpan mapel baru?', { icon: 'question' });
    if (!ok) return;

    insert('mapel', payload);
    e.target.reset();
    window.bootstrap.Collapse.getOrCreateInstance(document.getElementById('formAdd')).hide();
    renderMapelPage();
    showSuccess('Mapel berhasil ditambahkan');
  });

  document.getElementById('btnModalEdit')?.addEventListener('click', () => switchModalMode('edit'));
  document.getElementById('btnModalCancelEdit')?.addEventListener('click', () => switchModalMode('view'));

  document.getElementById('btnModalSave')?.addEventListener('click', async () => {
    if (!isAdmin || !selectedMapel) return;
    const id = Number(document.getElementById('edit-id-mapel').value);
    const payload = {
      nama_mapel: document.getElementById('edit-nama-mapel').value.trim(),
      kategori: document.getElementById('edit-kategori').value,
    };

    const check = validateMapelPayload(payload);
    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Simpan perubahan mapel?', { icon: 'question' });
    if (!ok) return;

    updateById('mapel', id, payload);
    detailModalInstance.hide();
    renderMapelPage();
    showSuccess('Mapel berhasil diperbarui');
  });

  document.getElementById('btnModalDelete')?.addEventListener('click', async () => {
    if (!isAdmin || !selectedMapel) return;
    const ok = await confirmAction('Yakin hapus mapel ini?', { icon: 'warning' });
    if (!ok) return;

    removeById('mapel', selectedMapel.id_mapel);
    detailModalInstance.hide();
    renderMapelPage();
    showSuccess('Mapel berhasil dihapus');
  });

  document.getElementById('btnExportCsv')?.addEventListener('click', () => {
    const mapels = [...getMapelWithPengampuAll()].sort(
      (a, b) => a.kategori.localeCompare(b.kategori) || a.nama_mapel.localeCompare(b.nama_mapel)
    );
    const rows = mapels.map((m, i) => [
      i + 1,
      m.nama_mapel,
      m.kategori,
      getUniqueGuruNames(m).join('; ') || 'Belum ada guru',
    ]);
    exportCsv('data_mapel.csv', ['No', 'Nama Mapel', 'Kategori', 'Guru Pengampu'], rows);
  });

  document.getElementById('btnExportPdfAll')?.addEventListener('click', () => {
    const mapels = [...getMapelWithPengampuAll()].sort(
      (a, b) => a.kategori.localeCompare(b.kategori) || a.nama_mapel.localeCompare(b.nama_mapel)
    );
    const rows = mapels.map((m, i) => [
      i + 1,
      m.nama_mapel,
      m.kategori,
      getUniqueGuruNames(m).join(', ') || '–',
    ]);
    exportPrintReport({
      title: 'Data Mata Pelajaran',
      headers: ['No', 'Nama Mapel', 'Kategori', 'Guru Pengampu'],
      rows,
    });
  });

  document.getElementById('btn-export-mapel-pdf')?.addEventListener('click', () => {
    if (!selectedMapel) return;
    exportPrintReport({
      title: 'Detail Mata Pelajaran',
      subtitle: selectedMapel.nama_mapel,
      detailRows: [
        ['Nama Mapel', selectedMapel.nama_mapel],
        ['Kategori', selectedMapel.kategori],
      ],
      sections: [
        {
          title: 'Guru Pengampu:',
          items: getUniqueGuruNames(selectedMapel),
        },
      ],
    });
  });
});

