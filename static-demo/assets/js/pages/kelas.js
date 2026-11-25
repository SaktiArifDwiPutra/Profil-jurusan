/**
 * Static Portfolio Demo — Data Kelas Controller
 *
 * Mirrors app/Http/Controllers/Utama/KelasController.php,
 * app/Http/Controllers/Export/KelasController.php, and resources/views/admin/kelas/index.blade.php
 */

import {
  getAll,
  getKelasWithWaliAll,
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
let selectedKelas = null;
let detailModalInstance = null;

function populateWaliSelects() {
  const gurus = [...getAll('guru')].sort((a, b) => a.nama.localeCompare(b.nama));
  const addWali = document.getElementById('add-wali');
  const editWali = document.getElementById('editWali');

  if (addWali) {
    addWali.innerHTML =
      '<option value="">Pilih Wali Kelas</option>' +
      gurus.map((g) => `<option value="${g.id_guru}">${escapeHtml(g.nama)}</option>`).join('');
  }
  if (editWali) {
    editWali.innerHTML =
      '<option value="">-- Pilih Wali Kelas --</option>' +
      gurus.map((g) => `<option value="${g.id_guru}">${escapeHtml(g.nama)}</option>`).join('');
  }
}

function applySearch() {
  const q = (document.getElementById('searchInput')?.value || '').toLowerCase();
  document.querySelectorAll('.kelas-card-col').forEach((col) => {
    const match =
      (col.dataset.tingkat || '').includes(q) ||
      (col.dataset.nama || '').includes(q) ||
      (col.dataset.wali || '').includes(q);
    col.style.display = match ? '' : 'none';
  });
}

function renderKelasPage() {
  const kelasList = getKelasWithWaliAll();
  const container = document.getElementById('kelasContainer');

  container.innerHTML = kelasList
    .map((k) => {
      const waliNama = k.wali?.nama || '-';
      return `
        <div class="col kelas-card-col"
             data-id="${k.id_kelas}"
             data-tingkat="${escapeHtml(k.tingkat.toLowerCase())}"
             data-nama="${escapeHtml(k.kelas.toLowerCase())}"
             data-wali="${escapeHtml(waliNama.toLowerCase())}">
          <div class="card kelas-card h-100" data-open-id="${k.id_kelas}">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">${escapeHtml(k.tingkat)} - ${escapeHtml(k.kelas)}</h5>
                <span class="badge rounded-pill text-bg-secondary">${escapeHtml(k.tingkat)}</span>
              </div>
              <p class="card-text text-muted mb-0"><small>${escapeHtml(k.kelas)}</small></p>
              <div class="mt-2"><strong>Wali:</strong> ${escapeHtml(waliNama)}</div>
            </div>
          </div>
        </div>
      `;
    })
    .join('');

  container.querySelectorAll('[data-open-id]').forEach((card) => {
    card.addEventListener('click', () => openDetailModal(Number(card.dataset.openId)));
  });

  const tingkatCount = new Set(kelasList.map((k) => k.tingkat)).size;
  const waliCount = new Set(kelasList.filter((k) => k.id_wali).map((k) => k.id_wali)).size;

  document.getElementById('stat-total-kelas').textContent = kelasList.length;
  document.getElementById('stat-tingkat-count').textContent = tingkatCount;
  document.getElementById('stat-wali-count').textContent = waliCount;

  const recent = [...kelasList].sort((a, b) => Number(b.id_kelas) - Number(a.id_kelas)).slice(0, 5);
  const recentList = document.getElementById('recent-kelas-list');
  recentList.innerHTML =
    recent.length > 0
      ? recent
          .map(
            (r) => `
          <div class="recent-item" style="cursor:pointer" data-recent-id="${r.id_kelas}">
            <div>
              <strong>${escapeHtml(r.tingkat)} - ${escapeHtml(r.kelas)}</strong>
              <div class="small text-muted">${escapeHtml(r.wali?.nama || '-')}</div>
            </div>
          </div>
        `
          )
          .join('')
      : '<div class="recent-item">Belum ada data</div>';

  recentList.querySelectorAll('[data-recent-id]').forEach((item) => {
    item.addEventListener('click', () => openDetailModal(Number(item.dataset.recentId)));
  });

  applySearch();
}

function switchModalMode(mode) {
  const modalEl = document.getElementById('detailModalKelas');
  const viewBox = document.getElementById('modal-kelas-view');
  const editBox = document.getElementById('modal-kelas-edit');
  const viewBtns = document.getElementById('viewButtons');
  const editBtns = document.getElementById('editButtons');
  const exportBtn = document.getElementById('btnExportPdf');

  if (mode === 'edit' && currentUser?.role === 'admin') {
    viewBox.style.display = 'none';
    editBox.style.display = 'block';
    viewBtns.setAttribute('style', 'display: none !important');
    editBtns.setAttribute('style', 'display: flex !important');
    exportBtn.style.display = 'none';
    modalEl.classList.remove('vertical');
  } else {
    viewBox.style.display = 'block';
    editBox.style.display = 'none';
    editBtns.setAttribute('style', 'display: none !important');
    viewBtns.setAttribute('style', 'display: flex !important');
    exportBtn.style.display = 'inline-block';
    modalEl.classList.add('vertical');
  }
}

function openDetailModal(id) {
  const k = getKelasWithWaliAll().find((item) => Number(item.id_kelas) === Number(id));
  if (!k) return;
  selectedKelas = k;

  document.getElementById('modalNama').textContent = `${k.tingkat} ${k.kelas}`;
  document.getElementById('modalTingkat').textContent = k.tingkat;
  document.getElementById('modalKelas').textContent = k.kelas;
  document.getElementById('modalWali').textContent = k.wali?.nama || '-';

  if (currentUser?.role === 'admin') {
    document.getElementById('editId').value = k.id_kelas;
    document.getElementById('editTingkat').value = k.tingkat;
    document.getElementById('editKelas').value = k.kelas;
    document.getElementById('editWali').value = k.id_wali || '';
  }

  switchModalMode('view');
  detailModalInstance?.show();
}

function validateKelasPayload(payload) {
  return validateFields(payload, {
    tingkat: { label: 'Tingkat', required: true, in: ['X', 'XI', 'XII'] },
    kelas: { label: 'Nama Kelas', required: true, maxLength: 50 },
  });
}

document.addEventListener('DOMContentLoaded', () => {
  currentUser = requireAuth('login.html');
  if (!currentUser) return;

  renderLayout({ activeNav: 'kelas', title: 'Data Kelas - Profil Jurusan RPL' });
  populateWaliSelects();

  const isAdmin = currentUser.role === 'admin';
  if (isAdmin) {
    document.getElementById('btnToggleAddKelas').classList.remove('d-none');
    document.getElementById('formAddKelas').classList.remove('d-none');
    document.getElementById('btnModalEdit').classList.remove('d-none');
    document.getElementById('btnModalDelete').classList.remove('d-none');
  }

  const modalEl = document.getElementById('detailModalKelas');
  detailModalInstance = new window.bootstrap.Modal(modalEl);
  modalEl.addEventListener('hidden.bs.modal', () => switchModalMode('view'));

  renderKelasPage();

  document.getElementById('searchInput')?.addEventListener('input', applySearch);

  document.getElementById('formTambahKelas')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!isAdmin) return;

    const payload = {
      tingkat: document.getElementById('add-tingkat').value,
      kelas: document.getElementById('add-kelas').value.trim(),
      id_wali: Number(document.getElementById('add-wali').value) || null,
    };

    const check = validateKelasPayload(payload);
    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Simpan data kelas baru?', { icon: 'question' });
    if (!ok) return;

    insert('kelas', payload);
    e.target.reset();
    window.bootstrap.Collapse.getOrCreateInstance(document.getElementById('formAddKelas')).hide();
    renderKelasPage();
    showSuccess('Kelas berhasil ditambahkan');
  });

  document.getElementById('btnModalEdit')?.addEventListener('click', () => switchModalMode('edit'));
  document.getElementById('btnModalCancelEdit')?.addEventListener('click', () => switchModalMode('view'));

  document.getElementById('btnModalSave')?.addEventListener('click', async () => {
    if (!isAdmin || !selectedKelas) return;
    const id = Number(document.getElementById('editId').value);
    const payload = {
      tingkat: document.getElementById('editTingkat').value,
      kelas: document.getElementById('editKelas').value.trim(),
      id_wali: Number(document.getElementById('editWali').value) || null,
    };

    const check = validateKelasPayload(payload);
    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Simpan perubahan kelas?', { icon: 'question' });
    if (!ok) return;

    updateById('kelas', id, payload);
    detailModalInstance.hide();
    renderKelasPage();
    showSuccess('Kelas berhasil diupdate');
  });

  document.getElementById('btnModalDelete')?.addEventListener('click', async () => {
    if (!isAdmin || !selectedKelas) return;
    const ok = await confirmAction('Yakin hapus kelas ini?', { icon: 'warning' });
    if (!ok) return;

    removeById('kelas', selectedKelas.id_kelas);
    detailModalInstance.hide();
    renderKelasPage();
    showSuccess('Kelas berhasil dihapus');
  });

  document.getElementById('btnExportCsv')?.addEventListener('click', () => {
    const rows = getKelasWithWaliAll().map((k, i) => [
      i + 1,
      k.tingkat,
      k.kelas,
      k.wali?.nama || '-',
    ]);
    exportCsv('data_kelas.csv', ['No', 'Tingkat', 'Kelas', 'Wali'], rows);
  });

  document.getElementById('btnExportPdfAll')?.addEventListener('click', () => {
    const rows = getKelasWithWaliAll().map((k, i) => [
      i + 1,
      k.tingkat,
      k.kelas,
      k.wali?.nama || '-',
    ]);
    exportPrintReport({
      title: 'Data Kelas',
      headers: ['No', 'Tingkat', 'Kelas', 'Wali'],
      rows,
    });
  });

  document.getElementById('btnExportPdf')?.addEventListener('click', () => {
    if (!selectedKelas) return;
    exportPrintReport({
      title: 'Detail Kelas',
      subtitle: `${selectedKelas.tingkat} - ${selectedKelas.kelas}`,
      detailRows: [
        ['Tingkat', selectedKelas.tingkat],
        ['Nama Kelas', selectedKelas.kelas],
        ['Wali Kelas', selectedKelas.wali?.nama || '-'],
      ],
    });
  });
});

