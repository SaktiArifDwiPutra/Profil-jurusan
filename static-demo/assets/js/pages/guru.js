/**
 * Static Portfolio Demo — Data Guru Controller
 *
 * Mirrors app/Http/Controllers/Utama/GuruController.php,
 * app/Http/Controllers/Export/GuruController.php, and resources/views/admin/guru/index.blade.php
 */

import {
  getAll,
  getGuruWithMapelDiampuAll,
  getKelasWithWaliAll,
  insert,
  updateById,
  removeById,
  removeWhere,
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
let selectedGuru = null;
let detailModalInstance = null;

function sanitizeDigits(el) {
  el?.addEventListener('input', () => {
    el.value = el.value.replace(/[^0-9]/g, '');
  });
}

function addAmpuRow(containerId, existingData = null) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const mapels = [...getAll('mapel')].sort((a, b) => a.nama_mapel.localeCompare(b.nama_mapel));
  const kelasList = getKelasWithWaliAll();

  const row = document.createElement('div');
  row.className = 'row g-2 ampu-row mb-2';

  const mapelOptions =
    '<option value="">-- Mapel --</option>' +
    mapels
      .map((m) => {
        const sel = existingData && Number(existingData.id_mapel) === Number(m.id_mapel) ? 'selected' : '';
        return `<option value="${m.id_mapel}" ${sel}>${escapeHtml(m.nama_mapel)}</option>`;
      })
      .join('');

  const kelasOptions =
    '<option value="">-- Kelas --</option>' +
    kelasList
      .map((k) => {
        const sel = existingData && Number(existingData.id_kelas) === Number(k.id_kelas) ? 'selected' : '';
        return `<option value="${k.id_kelas}" ${sel}>${escapeHtml(k.tingkat)} ${escapeHtml(k.kelas)}</option>`;
      })
      .join('');

  row.innerHTML = `
    <div class="col-md-5">
      <select name="ampu_mapel[]" class="form-select form-select-sm" required>${mapelOptions}</select>
    </div>
    <div class="col-md-5">
      <select name="ampu_kelas[]" class="form-select form-select-sm" required>${kelasOptions}</select>
    </div>
    <div class="col-md-2">
      <button type="button" class="btn btn-sm btn-outline-danger w-100 btn-remove-ampu">
        <i class="fa fa-times"></i>
      </button>
    </div>
  `;

  row.querySelector('.btn-remove-ampu').addEventListener('click', () => row.remove());
  container.appendChild(row);
}

function collectAmpuPairs(containerId) {
  const pairs = [];
  document.querySelectorAll(`#${containerId} .ampu-row`).forEach((row) => {
    const id_mapel = Number(row.querySelector('select[name="ampu_mapel[]"]')?.value || 0);
    const id_kelas = Number(row.querySelector('select[name="ampu_kelas[]"]')?.value || 0);
    if (id_mapel && id_kelas) {
      pairs.push({ id_mapel, id_kelas });
    }
  });
  return pairs;
}

function applySearch() {
  const q = (document.getElementById('searchInputGuru')?.value || '').toLowerCase();
  document.querySelectorAll('.guru-card-col').forEach((col) => {
    const match =
      (col.dataset.nama || '').includes(q) ||
      (col.dataset.mapel || '').includes(q) ||
      (col.dataset.nip || '').includes(q);
    col.style.display = match ? '' : 'none';
  });
}

function renderGuruPage() {
  const gurus = getGuruWithMapelDiampuAll();
  const container = document.getElementById('guruContainer');

  container.innerHTML = gurus
    .map((g) => {
      const badgeClass = g.jenis_kelamin === 'Laki-laki' ? 'primary' : 'danger';
      const mapelText = g.mapelDiampu.map((a) => a.mapel.nama_mapel.toLowerCase()).join(', ');
      const ampuHtml =
        g.mapelDiampu.length > 0
          ? g.mapelDiampu
              .map(
                (a) =>
                  `<div>${escapeHtml(a.mapel.nama_mapel)} &bull; ${escapeHtml(a.kelas.tingkat)} ${escapeHtml(a.kelas.kelas)}</div>`
              )
              .join('')
          : '<div class="text-muted">Belum ada mapel</div>';

      return `
        <div class="col guru-card-col"
             data-id="${g.id_guru}"
             data-nama="${escapeHtml(g.nama.toLowerCase())}"
             data-nip="${escapeHtml(String(g.nip || '').toLowerCase())}"
             data-mapel="${escapeHtml(mapelText)}">
          <div class="card siswa-card shadow-sm h-100" data-open-id="${g.id_guru}">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">${escapeHtml(g.nama)}</h5>
                <span class="badge rounded-pill text-bg-${badgeClass}">${escapeHtml(g.jenis_kelamin)}</span>
              </div>
              <p class="card-text text-muted mb-0"><small>${escapeHtml(g.nip || '-')}</small></p>
              <div class="mt-2" style="font-size:.9rem;color:#555;">${ampuHtml}</div>
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
  const laki = gurus.filter((g) => g.jenis_kelamin === 'Laki-laki').length;
  const perempuan = gurus.filter((g) => g.jenis_kelamin === 'Perempuan').length;
  const mapelSet = new Set();
  const kelasSet = new Set();
  gurus.forEach((g) => {
    g.mapelDiampu.forEach((a) => {
      mapelSet.add(a.id_mapel);
      kelasSet.add(a.id_kelas);
    });
  });

  document.getElementById('stat-total-guru').textContent = gurus.length;
  document.getElementById('stat-jk').textContent = `Laki: ${laki} • Perempuan: ${perempuan}`;
  document.getElementById('stat-mapel-unik').textContent = mapelSet.size;
  document.getElementById('stat-kelas-unik').textContent = kelasSet.size;

  const recent = [...gurus].sort((a, b) => Number(b.id_guru) - Number(a.id_guru)).slice(0, 5);
  const recentList = document.getElementById('recent-guru-list');
  recentList.innerHTML =
    recent.length > 0
      ? recent
          .map(
            (rg) => `
          <div class="recent-item" style="cursor:pointer" data-recent-id="${rg.id_guru}">
            <div style="min-width:0;">
              <strong>${escapeHtml(rg.nama)}</strong>
              <div style="font-size:0.85rem;color:#666">${escapeHtml(rg.nip || '')}</div>
            </div>
            <div style="font-size:0.9rem;color:#333">${escapeHtml(rg.no_hp)}</div>
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
  const modalEl = document.getElementById('detailModalGuru');
  const viewBox = document.getElementById('modal-guru-view');
  const editBox = document.getElementById('modal-guru-edit');
  const viewBtns = document.getElementById('view-buttons');
  const editBtns = document.getElementById('edit-buttons');
  const exportWrap = document.getElementById('btn-export-guru-pdf').parentElement;

  if (mode === 'edit' && currentUser?.role === 'admin') {
    viewBox.style.display = 'none';
    editBox.style.display = 'block';
    viewBtns.setAttribute('style', 'display: none !important');
    editBtns.setAttribute('style', 'display: flex !important');
    exportWrap.style.display = 'none';
    modalEl.classList.remove('vertical');
  } else {
    viewBox.style.display = 'block';
    editBox.style.display = 'none';
    editBtns.setAttribute('style', 'display: none !important');
    viewBtns.setAttribute('style', 'display: flex !important');
    exportWrap.style.display = 'block';
    modalEl.classList.add('vertical');
  }
}

function openDetailModal(id) {
  const g = getGuruWithMapelDiampuAll().find((item) => Number(item.id_guru) === Number(id));
  if (!g) return;
  selectedGuru = g;

  document.getElementById('modal-guru-nama').textContent = g.nama || '-';
  document.getElementById('detail-nip').textContent = g.nip || '-';
  document.getElementById('detail-email').textContent = g.email || '-';
  document.getElementById('detail-jk').textContent = g.jenis_kelamin || '-';
  document.getElementById('detail-nohp').textContent = g.no_hp || '-';

  const ampuList = document.getElementById('detail-ampu-list');
  if (g.mapelDiampu.length > 0) {
    ampuList.innerHTML = g.mapelDiampu
      .map(
        (a) =>
          `<div class="small py-1 border-bottom">${escapeHtml(a.mapel.nama_mapel)} &bull; Kelas ${escapeHtml(a.kelas.tingkat)} ${escapeHtml(a.kelas.kelas)}</div>`
      )
      .join('');
  } else {
    ampuList.innerHTML = '<div class="text-muted small">Belum ada mapel diampu</div>';
  }

  if (currentUser?.role === 'admin') {
    document.getElementById('edit-id-guru').value = g.id_guru;
    document.getElementById('edit-nip').value = g.nip || '';
    document.getElementById('edit-nama').value = g.nama || '';
    document.getElementById('edit-email').value = g.email || '';
    document.getElementById('edit-nohp').value = g.no_hp || '';
    document.getElementById('edit-jk').value = g.jenis_kelamin || 'Laki-laki';

    const editAmpuContainer = document.getElementById('edit-ampu-list');
    editAmpuContainer.innerHTML = '';
    g.mapelDiampu.forEach((a) => addAmpuRow('edit-ampu-list', a));
  }

  switchModalMode('view');
  detailModalInstance?.show();
}

function validateGuruPayload(payload, ignoreId = null) {
  return validateFields(payload, {
    nip: {
      label: 'NIP',
      required: false,
      integerOnly: true,
      digits: 18,
      unique: { table: 'guru', field: 'nip', ignoreId },
    },
    nama: { label: 'Nama', required: true, maxLength: 100 },
    jenis_kelamin: { label: 'Jenis Kelamin', required: true, in: ['Laki-laki', 'Perempuan'] },
    email: {
      label: 'Email',
      required: true,
      email: true,
      maxLength: 100,
      unique: { table: 'guru', field: 'email', ignoreId },
    },
    no_hp: { label: 'No HP', required: true, integerOnly: true },
  });
}

document.addEventListener('DOMContentLoaded', () => {
  currentUser = requireAuth('login.html');
  if (!currentUser) return;

  renderLayout({ activeNav: 'guru', title: 'Data Guru - Profil Jurusan RPL' });

  const isAdmin = currentUser.role === 'admin';
  if (isAdmin) {
    document.getElementById('btnToggleAddGuru').classList.remove('d-none');
    document.getElementById('formAddGuru').classList.remove('d-none');
    document.getElementById('btnModalEdit').classList.remove('d-none');
    document.getElementById('btnModalDelete').classList.remove('d-none');
  }

  sanitizeDigits(document.getElementById('add-nip'));
  sanitizeDigits(document.getElementById('add-nohp'));
  sanitizeDigits(document.getElementById('edit-nip'));
  sanitizeDigits(document.getElementById('edit-nohp'));

  const modalEl = document.getElementById('detailModalGuru');
  detailModalInstance = new window.bootstrap.Modal(modalEl);
  modalEl.addEventListener('hidden.bs.modal', () => switchModalMode('view'));

  renderGuruPage();

  document.getElementById('searchInputGuru')?.addEventListener('input', applySearch);
  document.getElementById('btnAddAmpuNew')?.addEventListener('click', () => addAmpuRow('ampu-list-new'));
  document.getElementById('btnAddAmpuEdit')?.addEventListener('click', () => addAmpuRow('edit-ampu-list'));

  // Add Guru
  document.getElementById('formTambahGuru')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!isAdmin) return;

    const payload = {
      nip: document.getElementById('add-nip').value.trim() || null,
      nama: document.getElementById('add-nama').value.trim(),
      jenis_kelamin: document.getElementById('add-jk').value,
      email: document.getElementById('add-email').value.trim(),
      no_hp: document.getElementById('add-nohp').value.trim(),
    };

    const check = validateGuruPayload(payload);
    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Simpan data guru baru?', { icon: 'question' });
    if (!ok) return;

    const guru = insert('guru', payload);
    const pairs = collectAmpuPairs('ampu-list-new');
    pairs.forEach((p) => insert('mapel_diampu', { id_guru: guru.id_guru, ...p }));

    e.target.reset();
    document.getElementById('ampu-list-new').innerHTML = '';
    window.bootstrap.Collapse.getOrCreateInstance(document.getElementById('formAddGuru')).hide();
    renderGuruPage();
    showSuccess('Guru berhasil ditambahkan');
  });

  // Modal Edit & Delete
  document.getElementById('btnModalEdit')?.addEventListener('click', () => switchModalMode('edit'));
  document.getElementById('btnModalCancelEdit')?.addEventListener('click', () => switchModalMode('view'));

  document.getElementById('btnModalSave')?.addEventListener('click', async () => {
    if (!isAdmin || !selectedGuru) return;
    const id = Number(document.getElementById('edit-id-guru').value);
    const payload = {
      nip: document.getElementById('edit-nip').value.trim() || null,
      nama: document.getElementById('edit-nama').value.trim(),
      jenis_kelamin: document.getElementById('edit-jk').value,
      email: document.getElementById('edit-email').value.trim(),
      no_hp: document.getElementById('edit-nohp').value.trim(),
    };

    const check = validateGuruPayload(payload, id);
    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Simpan perubahan data guru?', { icon: 'question' });
    if (!ok) return;

    updateById('guru', id, payload);
    removeWhere('mapel_diampu', (a) => Number(a.id_guru) === id);
    collectAmpuPairs('edit-ampu-list').forEach((p) =>
      insert('mapel_diampu', { id_guru: id, ...p })
    );

    detailModalInstance.hide();
    renderGuruPage();
    showSuccess('Data guru berhasil diperbarui');
  });

  document.getElementById('btnModalDelete')?.addEventListener('click', async () => {
    if (!isAdmin || !selectedGuru) return;
    const ok = await confirmAction(
      'Yakin hapus guru ini? Semua mapel yang diampu juga akan terhapus.',
      { icon: 'warning' }
    );
    if (!ok) return;

    removeById('guru', selectedGuru.id_guru);
    detailModalInstance.hide();
    renderGuruPage();
    showSuccess('Guru berhasil dihapus');
  });

  // Exports
  document.getElementById('btnExportCsv')?.addEventListener('click', () => {
    const rows = getGuruWithMapelDiampuAll().map((g, i) => {
      const mapelList = g.mapelDiampu
        .map((a) => `${a.mapel.nama_mapel} (${a.kelas.tingkat} ${a.kelas.kelas})`)
        .join('; ');
      return [
        i + 1,
        g.nip || '-',
        g.nama,
        g.jenis_kelamin,
        g.email,
        g.no_hp,
        mapelList || 'Tidak ada',
      ];
    });
    exportCsv(
      'data_guru.csv',
      ['No', 'NIP', 'Nama', 'Jenis Kelamin', 'Email', 'No HP', 'Mapel Diampu'],
      rows
    );
  });

  document.getElementById('btnExportPdfAll')?.addEventListener('click', () => {
    const rows = getGuruWithMapelDiampuAll().map((g, i) => {
      const mapelList = g.mapelDiampu
        .map((a) => `${a.mapel.nama_mapel} (${a.kelas.tingkat} ${a.kelas.kelas})`)
        .join(', ');
      return [
        i + 1,
        g.nip || '-',
        g.nama,
        g.jenis_kelamin,
        g.email,
        g.no_hp,
        mapelList || '–',
      ];
    });
    exportPrintReport({
      title: 'Data Guru',
      headers: ['No', 'NIP', 'Nama', 'Jenis Kelamin', 'Email', 'No HP', 'Mapel Diampu'],
      rows,
    });
  });

  document.getElementById('btn-export-guru-pdf')?.addEventListener('click', () => {
    if (!selectedGuru) return;
    exportPrintReport({
      title: 'Detail Guru',
      subtitle: selectedGuru.nama,
      detailRows: [
        ['NIP', selectedGuru.nip || '-'],
        ['Nama', selectedGuru.nama],
        ['Jenis Kelamin', selectedGuru.jenis_kelamin],
        ['Email', selectedGuru.email],
        ['No HP', selectedGuru.no_hp],
      ],
      sections: [
        {
          title: 'Mapel Diampu:',
          items: selectedGuru.mapelDiampu.map(
            (a) => `${a.mapel.nama_mapel} (${a.kelas.tingkat} ${a.kelas.kelas})`
          ),
        },
      ],
    });
  });
});

