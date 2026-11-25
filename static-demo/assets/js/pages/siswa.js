/**
 * Static Portfolio Demo — Data Siswa Controller
 *
 * Mirrors app/Http/Controllers/Utama/SiswaController.php,
 * app/Http/Controllers/Export/SiswaController.php, and resources/views/admin/siswa/index.blade.php
 */

import {
  getSiswaWithKelasAll,
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
let selectedSiswa = null;
let detailModalInstance = null;

function sanitizeDigitsInput(el) {
  el?.addEventListener('input', () => {
    el.value = el.value.replace(/[^0-9]/g, '');
  });
}

function getKelasLabel(s) {
  return s.kelas ? `${s.kelas.tingkat} - ${s.kelas.kelas}` : 'Belum Ada Kelas';
}

function populateKelasSelects() {
  const kelasList = getKelasWithWaliAll();
  const addSel = document.getElementById('add-id-kelas');
  const editSel = document.getElementById('edit-id-kelas');

  if (addSel) {
    addSel.innerHTML =
      '<option value="">Kelas</option>' +
      kelasList
        .map(
          (k) =>
            `<option value="${k.id_kelas}">${escapeHtml(k.tingkat)} - ${escapeHtml(k.kelas)}</option>`
        )
        .join('');
  }
  if (editSel) {
    editSel.innerHTML =
      '<option value="">--Kelas--</option>' +
      kelasList
        .map(
          (k) =>
            `<option value="${k.id_kelas}">${escapeHtml(k.tingkat)} - ${escapeHtml(k.kelas)}</option>`
        )
        .join('');
  }
}

function applySearchFilter() {
  const q = (document.getElementById('searchInput')?.value || '').toLowerCase();
  document.querySelectorAll('.siswa-card-col').forEach((col) => {
    const match =
      (col.dataset.nisn || '').includes(q) ||
      (col.dataset.nama || '').includes(q) ||
      (col.dataset.kelas || '').includes(q);
    col.style.display = match ? '' : 'none';
  });
}

function renderSiswaPage() {
  const siswas = getSiswaWithKelasAll();
  const kelasList = getKelasWithWaliAll();

  const container = document.getElementById('siswaContainer');
  container.innerHTML = siswas
    .map((s) => {
      const kelasLabel = getKelasLabel(s);
      const badgeClass = s.jenis_kelamin === 'Laki-laki' ? 'primary' : 'danger';
      return `
        <div class="col siswa-card-col"
             data-id="${s.id_siswa}"
             data-nisn="${escapeHtml(String(s.nisn || '').toLowerCase())}"
             data-nama="${escapeHtml(String(s.nama || '').toLowerCase())}"
             data-kelas="${escapeHtml(s.kelas ? kelasLabel.toLowerCase() : '')}">
          <div class="card siswa-card shadow-sm h-100" data-open-id="${s.id_siswa}">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">${escapeHtml(s.nama)}</h5>
                <span class="badge rounded-pill text-bg-${badgeClass}">${escapeHtml(s.jenis_kelamin)}</span>
              </div>
              <p class="card-text text-muted mb-0">
                <small>${escapeHtml(kelasLabel)}</small>
              </p>
            </div>
          </div>
        </div>
      `;
    })
    .join('');

  container.querySelectorAll('[data-open-id]').forEach((card) => {
    card.addEventListener('click', () => openDetailModal(Number(card.dataset.openId)));
  });

  // Sidebar stats
  const laki = siswas.filter((s) => s.jenis_kelamin === 'Laki-laki').length;
  const perempuan = siswas.filter((s) => s.jenis_kelamin === 'Perempuan').length;
  document.getElementById('stat-total-siswa').textContent = siswas.length;
  document.getElementById('stat-jk').textContent = `Laki: ${laki} • Perempuan: ${perempuan}`;
  document.getElementById('stat-jumlah-kelas').textContent = kelasList.length;

  const recent = [...siswas].sort((a, b) => Number(b.id_siswa) - Number(a.id_siswa)).slice(0, 5);
  const recentList = document.getElementById('recent-siswa-list');
  recentList.innerHTML =
    recent.length > 0
      ? recent
          .map(
            (r) => `
          <div class="recent-item" style="cursor:pointer" data-recent-id="${r.id_siswa}">
            <div style="min-width:0;">
              <strong>${escapeHtml(r.nama)}</strong>
              <div style="font-size:0.85rem;color:#666">${escapeHtml(getKelasLabel(r))}</div>
            </div>
            <div style="font-size:0.9rem;color:#333">${escapeHtml(r.no_hp)}</div>
          </div>
        `
          )
          .join('')
      : '<div class="recent-item">Belum ada data</div>';

  recentList.querySelectorAll('[data-recent-id]').forEach((item) => {
    item.addEventListener('click', () => openDetailModal(Number(item.dataset.recentId)));
  });

  applySearchFilter();
}

function switchModalMode(mode) {
  const modalEl = document.getElementById('detailModal');
  const detailView = document.getElementById('modal-detail-view');
  const editFormBox = document.getElementById('modal-edit-form');
  const viewBtns = document.getElementById('view-buttons');
  const editBtns = document.getElementById('edit-buttons');
  const exportBtn = document.getElementById('btn-export-siswa-pdf');

  if (mode === 'edit' && currentUser?.role === 'admin') {
    detailView.style.display = 'none';
    editFormBox.style.display = 'block';
    viewBtns.setAttribute('style', 'display: none !important');
    editBtns.setAttribute('style', 'display: flex !important');
    exportBtn.style.display = 'none';
    modalEl.classList.remove('vertical');
  } else {
    detailView.style.display = 'block';
    editFormBox.style.display = 'none';
    editBtns.setAttribute('style', 'display: none !important');
    viewBtns.setAttribute('style', 'display: flex !important');
    exportBtn.style.display = 'inline-block';
    modalEl.classList.add('vertical');
  }
}

function openDetailModal(id) {
  const s = getSiswaWithKelasAll().find((item) => Number(item.id_siswa) === Number(id));
  if (!s) return;
  selectedSiswa = s;

  document.getElementById('modal-nama-view').textContent = s.nama;
  document.getElementById('detail-nisn').textContent = s.nisn || '-';
  document.getElementById('detail-jk').textContent = s.jenis_kelamin || '-';
  document.getElementById('detail-tgl').textContent = s.tanggal_lahir || '-';
  document.getElementById('detail-kelas').textContent = getKelasLabel(s);
  document.getElementById('detail-nohp').textContent = s.no_hp || '-';
  document.getElementById('detail-alamat').textContent = s.alamat || '-';

  if (currentUser?.role === 'admin') {
    document.getElementById('edit-id-siswa').value = s.id_siswa;
    document.getElementById('edit-nisn').value = s.nisn || '';
    document.getElementById('edit-nama').value = s.nama || '';
    document.getElementById('edit-jk').value = s.jenis_kelamin || 'Laki-laki';
    document.getElementById('edit-tgl').value = s.tanggal_lahir || '';
    document.getElementById('edit-id-kelas').value = s.id_kelas || '';
    document.getElementById('edit-nohp').value = s.no_hp || '';
    document.getElementById('edit-alamat').value = s.alamat || '';
  }

  switchModalMode('view');
  detailModalInstance?.show();
}

function validateSiswaPayload(payload, ignoreId = null) {
  return validateFields(payload, {
    nisn: {
      label: 'NISN',
      required: true,
      integerOnly: true,
      digitsBetween: [9, 10],
      unique: { table: 'siswa', field: 'nisn', ignoreId },
    },
    nama: { label: 'Nama', required: true, maxLength: 100 },
    jenis_kelamin: { label: 'Jenis Kelamin', required: true, in: ['Laki-laki', 'Perempuan'] },
    id_kelas: { label: 'Kelas', required: true },
    no_hp: { label: 'No HP', required: true, integerOnly: true },
  });
}

document.addEventListener('DOMContentLoaded', () => {
  currentUser = requireAuth('login.html');
  if (!currentUser) return;

  renderLayout({ activeNav: 'siswa', title: 'Data Siswa - Profil Jurusan RPL' });
  populateKelasSelects();

  const isAdmin = currentUser.role === 'admin';
  if (isAdmin) {
    document.getElementById('btnToggleAddSiswa').classList.remove('d-none');
    document.getElementById('formAdd').classList.remove('d-none');
    document.getElementById('btnModalEdit').classList.remove('d-none');
    document.getElementById('btnModalDelete').classList.remove('d-none');
  }

  sanitizeDigitsInput(document.getElementById('add-nisn'));
  sanitizeDigitsInput(document.getElementById('add-nohp'));
  sanitizeDigitsInput(document.getElementById('edit-nisn'));
  sanitizeDigitsInput(document.getElementById('edit-nohp'));

  const modalEl = document.getElementById('detailModal');
  detailModalInstance = new window.bootstrap.Modal(modalEl);
  modalEl.addEventListener('hidden.bs.modal', () => switchModalMode('view'));

  renderSiswaPage();

  document.getElementById('searchInput')?.addEventListener('input', applySearchFilter);

  // Add Siswa
  document.getElementById('formTambahSiswa')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!isAdmin) return;

    const payload = {
      nisn: document.getElementById('add-nisn').value.trim(),
      nama: document.getElementById('add-nama').value.trim(),
      jenis_kelamin: document.getElementById('add-jk').value,
      tanggal_lahir: document.getElementById('add-tgl').value || null,
      id_kelas: Number(document.getElementById('add-id-kelas').value) || '',
      no_hp: document.getElementById('add-nohp').value.trim(),
      alamat: document.getElementById('add-alamat').value.trim() || null,
    };

    const check = validateSiswaPayload(payload);
    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Simpan data siswa baru?', { icon: 'question' });
    if (!ok) return;

    insert('siswa', payload);
    e.target.reset();
    window.bootstrap.Collapse.getOrCreateInstance(document.getElementById('formAdd')).hide();
    renderSiswaPage();
    showSuccess('Siswa berhasil ditambahkan');
  });

  // Modal Edit & Delete
  document.getElementById('btnModalEdit')?.addEventListener('click', () => switchModalMode('edit'));
  document.getElementById('btnModalCancelEdit')?.addEventListener('click', () => switchModalMode('view'));

  document.getElementById('btnModalSave')?.addEventListener('click', async () => {
    if (!isAdmin || !selectedSiswa) return;
    const id = Number(document.getElementById('edit-id-siswa').value);
    const payload = {
      nisn: document.getElementById('edit-nisn').value.trim(),
      nama: document.getElementById('edit-nama').value.trim(),
      jenis_kelamin: document.getElementById('edit-jk').value,
      tanggal_lahir: document.getElementById('edit-tgl').value || null,
      id_kelas: Number(document.getElementById('edit-id-kelas').value) || '',
      no_hp: document.getElementById('edit-nohp').value.trim(),
      alamat: document.getElementById('edit-alamat').value.trim() || null,
    };

    const check = validateSiswaPayload(payload, id);
    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Simpan perubahan data siswa?', { icon: 'question' });
    if (!ok) return;

    updateById('siswa', id, payload);
    detailModalInstance.hide();
    renderSiswaPage();
    showSuccess('Siswa berhasil diupdate');
  });

  document.getElementById('btnModalDelete')?.addEventListener('click', async () => {
    if (!isAdmin || !selectedSiswa) return;
    const ok = await confirmAction(
      'Yakin hapus siswa ini? Data tidak bisa dikembalikan.',
      { icon: 'warning' }
    );
    if (!ok) return;

    removeById('siswa', selectedSiswa.id_siswa);
    detailModalInstance.hide();
    renderSiswaPage();
    showSuccess('Siswa berhasil dihapus');
  });

  // Exports
  document.getElementById('btnExportCsv')?.addEventListener('click', () => {
    const rows = getSiswaWithKelasAll().map((s, i) => [
      i + 1,
      s.nisn,
      s.nama,
      s.jenis_kelamin,
      s.tanggal_lahir || '-',
      getKelasLabel(s),
      s.no_hp,
      s.alamat || '-',
    ]);
    exportCsv(
      'data_siswa.csv',
      ['No', 'NISN', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'No HP', 'Alamat'],
      rows
    );
  });

  document.getElementById('btnExportPdfAll')?.addEventListener('click', () => {
    const rows = getSiswaWithKelasAll().map((s, i) => [
      i + 1,
      s.nisn,
      s.nama,
      s.jenis_kelamin,
      s.tanggal_lahir || '-',
      s.kelas ? `${s.kelas.tingkat} - ${s.kelas.kelas}` : '–',
      s.no_hp,
      s.alamat || '-',
    ]);
    exportPrintReport({
      title: 'Data Siswa',
      headers: ['No', 'NISN', 'Nama', 'Jenis Kelamin', 'Tgl Lahir', 'Kelas', 'No HP', 'Alamat'],
      rows,
    });
  });

  document.getElementById('btn-export-siswa-pdf')?.addEventListener('click', () => {
    if (!selectedSiswa) return;
    exportPrintReport({
      title: 'Detail Siswa',
      subtitle: selectedSiswa.nama,
      detailRows: [
        ['NISN', selectedSiswa.nisn],
        ['Nama', selectedSiswa.nama],
        ['Jenis Kelamin', selectedSiswa.jenis_kelamin],
        ['Tanggal Lahir', selectedSiswa.tanggal_lahir || '-'],
        ['Kelas', getKelasLabel(selectedSiswa)],
        ['No HP', selectedSiswa.no_hp],
        ['Alamat', selectedSiswa.alamat || '-'],
      ],
    });
  });
});

