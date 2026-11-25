/**
 * Static Portfolio Demo — Multi-Role Absensi Controller
 *
 * Mirrors app/Http/Controllers/Utama/AbsensiController.php and
 * resources/views/admin/absensi/{index,index_guru,index_siswa,bukan_wali}.blade.php
 */

import {
  getAll,
  getSiswaWithKelasAll,
  getKelasWithWaliAll,
  insert,
  upsert,
} from '../store.js';
import { requireAuth, setFlash } from '../auth.js';
import {
  renderLayout,
  confirmAction,
  showSuccess,
  showError,
  escapeHtml,
} from '../layout.js';

function todayIso() {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function formatDmy(iso) {
  if (!iso) return '-';
  const parts = String(iso).split('-');
  return parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : iso;
}

const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function formatShortDate(iso) {
  if (!iso) return '-';
  const [y, m, d] = String(iso).split('-');
  if (!y || !m || !d) return iso;
  return `${d} ${MONTHS_SHORT[Number(m) - 1] || m} ${y}`;
}

function statusBadgeColor(status) {
  switch (String(status || '').toLowerCase()) {
    case 'hadir':
      return 'success';
    case 'izin':
      return 'warning';
    case 'sakit':
      return 'info';
    case 'alpha':
      return 'danger';
    default:
      return 'secondary';
  }
}

function getAbsenStatusMap(tanggal) {
  const map = new Map();
  getAll('absensi')
    .filter((a) => a.tanggal === tanggal)
    .forEach((a) => map.set(Number(a.id_siswa), a.status));
  return map;
}

// ========================
// 1. SISWA VIEW
// ========================
function renderSiswaAbsensi(root, user) {
  const siswa = user.siswa;
  if (!siswa) {
    setFlash('error', 'Data siswa tidak ditemukan pada akun ini.');
    window.location.href = 'dashboard.html';
    return;
  }

  const today = todayIso();
  const riwayat = getAll('absensi')
    .filter((a) => Number(a.id_siswa) === Number(siswa.id_siswa))
    .sort((a, b) => String(b.tanggal).localeCompare(String(a.tanggal)));

  const cekAbsen = riwayat.find((a) => a.tanggal === today) || null;
  const stats = { Hadir: 0, Izin: 0, Sakit: 0, Alpha: 0 };
  riwayat.forEach((r) => {
    const key = String(r.status || '').toLowerCase();
    if (key === 'hadir') stats.Hadir += 1;
    else if (key === 'izin') stats.Izin += 1;
    else if (key === 'sakit') stats.Sakit += 1;
    else if (key === 'alpha') stats.Alpha += 1;
  });

  const absenFormHtml = cekAbsen
    ? `<div class="alert alert-success mt-2 mb-0">Kamu sudah absen hari ini sebagai <strong>${escapeHtml(cekAbsen.status)}</strong>.</div>`
    : `
      <div class="card p-3 mt-2 border border-primary">
        <h6 class="mb-3 text-primary">Input Kehadiran Hari Ini</h6>
        <form id="formSiswaAbsen" class="row g-3" novalidate>
          <div class="col-md-6">
            <label for="statusKehadiran" class="form-label">Status Kehadiran</label>
            <select name="status" id="statusKehadiran" class="form-select" required>
              <option value="">Pilih</option>
              <option value="Hadir">Hadir</option>
              <option value="Izin">Izin</option>
              <option value="Sakit">Sakit</option>
            </select>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Kirim Absen</button>
          </div>
        </form>
      </div>
    `;

  const riwayatRows =
    riwayat.length > 0
      ? riwayat
          .map(
            (r, i) => `
          <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(formatDmy(r.tanggal))}</td>
            <td><span class="badge rounded-pill text-bg-${statusBadgeColor(r.status)}">${escapeHtml(r.status)}</span></td>
          </tr>
        `
          )
          .join('')
      : '<tr><td colspan="3" class="text-center text-muted">Belum ada riwayat absensi.</td></tr>';

  root.innerHTML = `
    <div class="layout-golden">
      <div class="left-col">
        <div class="card shadow-sm p-4 mb-4">
          <h4 class="fw-bold mb-1">Absensi Siswa</h4>
          <div class="mb-3">
            <p class="mb-0"><strong>${escapeHtml(siswa.nama)}</strong> (${escapeHtml(siswa.nisn)})</p>
            <p class="text-muted mb-0">Tanggal Hari Ini: ${escapeHtml(formatDmy(today))}</p>
          </div>
          ${absenFormHtml}
        </div>

        <div class="card shadow-sm p-4 mb-4">
          <h5 class="mb-3">Riwayat Absensi</h5>
          <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>${riwayatRows}</tbody>
            </table>
          </div>
        </div>
      </div>

      <aside class="sidebar">
        <div class="stat-card">
          <h6>Total Absensi</h6>
          <div class="stat-value">${riwayat.length} Hari</div>
        </div>
        <div class="stat-card">
          <h6>Statistik</h6>
          <div class="recent-list">
            <div class="recent-item"><div>Hadir:</div><strong>${stats.Hadir}</strong></div>
            <div class="recent-item"><div>Sakit:</div><strong>${stats.Sakit}</strong></div>
            <div class="recent-item"><div>Izin:</div><strong>${stats.Izin}</strong></div>
            <div class="recent-item"><div>Alpha:</div><strong>${stats.Alpha}</strong></div>
          </div>
        </div>
      </aside>
    </div>
  `;

  document.getElementById('formSiswaAbsen')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const status = document.getElementById('statusKehadiran').value;
    if (!['Hadir', 'Izin', 'Sakit'].includes(status)) {
      showError('Silakan pilih status kehadiran terlebih dahulu.');
      return;
    }

    insert('absensi', {
      id_siswa: siswa.id_siswa,
      id_kelas: siswa.id_kelas,
      tanggal: today,
      status,
    });

    renderSiswaAbsensi(root, user);
    showSuccess('Absensi berhasil dikirim.');
  });
}

// ========================
// 2. GURU VIEW (WALI / BUKAN WALI)
// ========================
function renderGuruAbsensi(root, user, state) {
  const kelasWali = getKelasWithWaliAll().find(
    (k) => Number(k.id_wali) === Number(user.id_guru)
  );

  if (!kelasWali) {
    root.innerHTML = `
      <div class="container mt-5">
        <div class="alert alert-warning text-center shadow-sm p-5">
          <h4 class="alert-heading fw-bold"><i class="fa fa-exclamation-triangle me-2"></i> Akses Dibatasi</h4>
          <p class="mb-0 fs-5">Anda tidak terdaftar sebagai Wali Kelas.</p>
          <hr>
          <p class="mb-0">Fitur absensi kelas hanya tersedia bagi guru yang menjabat sebagai Wali Kelas.</p>
          <a href="dashboard.html" class="btn btn-outline-dark mt-3">Kembali ke Dashboard</a>
        </div>
      </div>
    `;
    return;
  }

  const tanggal = state.tanggal;
  const siswa = getSiswaWithKelasAll().filter(
    (s) => Number(s.id_kelas) === Number(kelasWali.id_kelas)
  );
  const absenMap = getAbsenStatusMap(tanggal);

  const rowsHtml =
    siswa.length > 0
      ? siswa
          .map((s, i) => {
            const st = absenMap.get(Number(s.id_siswa)) || '-';
            const options = ['Hadir', 'Izin', 'Sakit', 'Alpha']
              .map(
                (opt) =>
                  `<option value="${opt}" ${st.toLowerCase() === opt.toLowerCase() ? 'selected' : ''}>${opt}</option>`
              )
              .join('');

            return `
              <tr id="row-${s.id_siswa}">
                <td class="view">${i + 1}</td>
                <td class="view fw-semibold">${escapeHtml(s.nama)}</td>
                <td class="view"><span class="badge text-bg-${statusBadgeColor(st)}">${escapeHtml(st)}</span></td>
                <td class="view">
                  <button type="button" class="btn btn-sm btn-primary" data-edit-row="${s.id_siswa}">Edit</button>
                </td>
                <td class="edit d-none" colspan="4">
                  <div class="d-flex gap-2 align-items-center w-100 absen-edit-row">
                    <div class="fw-bold" style="min-width:120px">${escapeHtml(s.nama)}</div>
                    <select class="form-select form-select-sm" data-status-for="${s.id_siswa}">${options}</select>
                    <button type="button" class="btn btn-sm btn-success" data-save-row="${s.id_siswa}" data-kelas-id="${s.id_kelas}">Simpan</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-cancel-row="${s.id_siswa}">Batal</button>
                  </div>
                </td>
              </tr>
            `;
          })
          .join('')
      : '<tr><td colspan="4" class="text-center py-3">Tidak ada siswa di kelas ini.</td></tr>';

  root.innerHTML = `
    <div class="layout-golden">
      <div class="left-col">
        <div class="card p-4 shadow-sm mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="mb-0">Absensi Kelas ${escapeHtml(kelasWali.tingkat)} ${escapeHtml(kelasWali.kelas)}</h4>
              <p class="text-muted small mb-0 mt-1">Kelola kehadiran siswa perwalian Anda.</p>
            </div>
          </div>

          <div class="row g-2 align-items-end mb-4">
            <div class="col-auto"><label for="guruTanggalInput" class="form-label mb-0">Pilih Tanggal:</label></div>
            <div class="col-auto">
              <input type="date" id="guruTanggalInput" value="${escapeHtml(tanggal)}" class="form-control">
            </div>
            <div class="col-auto">
              <button type="button" id="btnGuruHariIni" class="btn btn-secondary">Hari Ini</button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped align-middle" id="absensiTable">
              <thead>
                <tr>
                  <th style="width:50px">No</th>
                  <th>Nama Siswa</th>
                  <th style="width:100px">Status</th>
                  <th style="width:120px">Aksi</th>
                </tr>
              </thead>
              <tbody>${rowsHtml}</tbody>
            </table>
          </div>
        </div>
      </div>

      <aside class="sidebar">
        <div class="stat-card">
          <h6>Info Kelas</h6>
          <div class="stat-value">${escapeHtml(kelasWali.tingkat)} ${escapeHtml(kelasWali.kelas)}</div>
        </div>
        <div class="stat-card">
          <h6>Total Siswa</h6>
          <div>${siswa.length}</div>
        </div>
        <div class="stat-card">
          <h6>Tanggal</h6>
          <div>${escapeHtml(formatShortDate(tanggal))}</div>
        </div>
      </aside>
    </div>
  `;

  document.getElementById('guruTanggalInput')?.addEventListener('change', (e) => {
    state.tanggal = e.target.value || todayIso();
    renderGuruAbsensi(root, user, state);
  });

  document.getElementById('btnGuruHariIni')?.addEventListener('click', () => {
    state.tanggal = todayIso();
    renderGuruAbsensi(root, user, state);
  });

  bindInlineRowHandlers(root, () => renderGuruAbsensi(root, user, state), tanggal, 'Update status kehadiran?');
}

// ========================
// 3. ADMIN VIEW
// ========================
function renderAdminAbsensi(root, state) {
  const kelasList = getKelasWithWaliAll();
  const uniqueTingkat = [...new Set(kelasList.map((k) => k.tingkat))];

  let siswa = getSiswaWithKelasAll();
  if (state.tingkat) {
    siswa = siswa.filter((s) => s.kelas?.tingkat === state.tingkat);
  }
  if (state.kelas) {
    siswa = siswa.filter((s) => s.kelas?.kelas === state.kelas);
  }

  const absenMap = getAbsenStatusMap(state.tanggal);

  const tingkatOptions =
    '<option value="">Semua</option>' +
    uniqueTingkat
      .map((t) => `<option value="${escapeHtml(t)}" ${state.tingkat === t ? 'selected' : ''}>${escapeHtml(t)}</option>`)
      .join('');

  const kelasOptions =
    '<option value="">Semua</option>' +
    kelasList
      .map(
        (k) =>
          `<option value="${escapeHtml(k.kelas)}" ${state.kelas === k.kelas ? 'selected' : ''}>${escapeHtml(k.kelas)}</option>`
      )
      .join('');

  const rowsHtml =
    siswa.length > 0
      ? siswa
          .map((s, i) => {
            const st = absenMap.get(Number(s.id_siswa)) || '-';
            const kLabel = s.kelas ? `${s.kelas.tingkat} ${s.kelas.kelas}` : '-';
            const options = ['Hadir', 'Izin', 'Sakit', 'Alpha']
              .map(
                (opt) =>
                  `<option value="${opt}" ${st.toLowerCase() === opt.toLowerCase() ? 'selected' : ''}>${opt}</option>`
              )
              .join('');

            return `
              <tr id="row-${s.id_siswa}">
                <td class="view">${i + 1}</td>
                <td class="view">${escapeHtml(s.nama)}</td>
                <td class="view">${escapeHtml(kLabel)}</td>
                <td class="view">${escapeHtml(st)}</td>
                <td class="view">
                  <button type="button" class="btn btn-sm btn-primary" data-edit-row="${s.id_siswa}">Edit</button>
                </td>
                <td class="edit d-none" colspan="5">
                  <div class="d-flex gap-2 align-items-center absen-edit-row">
                    <select class="form-select form-select-sm" data-status-for="${s.id_siswa}">${options}</select>
                    <button type="button" class="btn btn-sm btn-primary" data-save-row="${s.id_siswa}" data-kelas-id="${s.id_kelas || ''}">Simpan</button>
                    <button type="button" class="btn btn-sm btn-danger" data-cancel-row="${s.id_siswa}">Batal</button>
                  </div>
                </td>
              </tr>
            `;
          })
          .join('')
      : '<tr><td colspan="5" class="text-center py-3">Tidak ada siswa.</td></tr>';

  const recent = [...siswa].sort((a, b) => Number(b.id_siswa) - Number(a.id_siswa)).slice(0, 6);
  const recentHtml =
    recent.length > 0
      ? recent
          .map(
            (rs) => `
          <div class="recent-item" style="cursor:pointer" data-scroll-row="row-${rs.id_siswa}">
            <strong>${escapeHtml(rs.nama)}</strong>
            <div style="font-size:.85rem;color:#666">${escapeHtml(rs.kelas ? `${rs.kelas.tingkat} ${rs.kelas.kelas}` : '-')}</div>
          </div>
        `
          )
          .join('')
      : '<div class="recent-item">Belum ada data</div>';

  root.innerHTML = `
    <div class="layout-golden">
      <div class="left-col">
        <div class="card p-4 shadow-sm mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="mb-0">Absensi - Admin</h4>
              <div class="text-muted" style="font-size:.9rem; margin-top:3px;">
                Tanggal: <strong>${escapeHtml(state.tanggal)}</strong>
                ${state.tingkat ? ` &bull; Tingkat: <strong>${escapeHtml(state.tingkat)}</strong>` : ''}
                ${state.kelas ? ` &bull; Kelas: <strong>${escapeHtml(state.kelas)}</strong>` : ''}
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label" for="adminFilterTingkat">Tingkat</label>
              <select id="adminFilterTingkat" class="form-select">${tingkatOptions}</select>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="adminFilterKelas">Kelas</label>
              <select id="adminFilterKelas" class="form-select">${kelasOptions}</select>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="adminFilterTanggal">Tanggal</label>
              <input type="date" id="adminFilterTanggal" value="${escapeHtml(state.tanggal)}" class="form-control">
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button type="button" id="btnAdminReset" class="btn btn-secondary w-100">Reset</button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped" id="absensiTable">
              <thead>
                <tr>
                  <th style="width:60px">No</th>
                  <th>Nama</th>
                  <th>Kelas</th>
                  <th>Status</th>
                  <th style="width:140px">Aksi</th>
                </tr>
              </thead>
              <tbody>${rowsHtml}</tbody>
            </table>
          </div>
        </div>
      </div>

      <aside class="sidebar">
        <div class="stat-card">
          <h6>Total Siswa</h6>
          <div class="stat-value">${siswa.length}</div>
        </div>
        <div class="stat-card">
          <h6>Filter</h6>
          <div style="font-size:.95rem; color:#333;">
            Tingkat: <strong>${escapeHtml(state.tingkat || 'Semua')}</strong><br>
            Kelas: <strong>${escapeHtml(state.kelas || 'Semua')}</strong><br>
            Tanggal: <strong>${escapeHtml(state.tanggal)}</strong>
          </div>
        </div>
        <div class="stat-card">
          <h6>Terakhir Ditambahkan</h6>
          <div class="recent-list">${recentHtml}</div>
        </div>
      </aside>
    </div>
  `;

  document.getElementById('adminFilterTingkat')?.addEventListener('change', (e) => {
    state.tingkat = e.target.value;
    renderAdminAbsensi(root, state);
  });

  document.getElementById('adminFilterKelas')?.addEventListener('change', (e) => {
    state.kelas = e.target.value;
    renderAdminAbsensi(root, state);
  });

  document.getElementById('adminFilterTanggal')?.addEventListener('change', (e) => {
    state.tanggal = e.target.value || todayIso();
    renderAdminAbsensi(root, state);
  });

  document.getElementById('btnAdminReset')?.addEventListener('click', () => {
    state.tingkat = '';
    state.kelas = '';
    state.tanggal = todayIso();
    renderAdminAbsensi(root, state);
  });

  root.querySelectorAll('[data-scroll-row]').forEach((item) => {
    item.addEventListener('click', () => {
      document.getElementById(item.dataset.scrollRow)?.scrollIntoView({ behavior: 'smooth' });
    });
  });

  bindInlineRowHandlers(
    root,
    () => renderAdminAbsensi(root, state),
    state.tanggal,
    'Simpan perubahan status kehadiran siswa ini?'
  );
}

function bindInlineRowHandlers(root, rerender, tanggal, confirmMessage) {
  root.querySelectorAll('[data-edit-row]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const row = document.getElementById(`row-${btn.dataset.editRow}`);
      if (!row) return;
      row.querySelectorAll('.view').forEach((el) => el.classList.add('d-none'));
      row.querySelectorAll('.edit').forEach((el) => el.classList.remove('d-none'));
    });
  });

  root.querySelectorAll('[data-cancel-row]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const row = document.getElementById(`row-${btn.dataset.cancelRow}`);
      if (!row) return;
      row.querySelectorAll('.view').forEach((el) => el.classList.remove('d-none'));
      row.querySelectorAll('.edit').forEach((el) => el.classList.add('d-none'));
    });
  });

  root.querySelectorAll('[data-save-row]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const idSiswa = Number(btn.dataset.saveRow);
      const idKelas = Number(btn.dataset.kelasId) || null;
      const select = root.querySelector(`select[data-status-for="${idSiswa}"]`);
      const status = select?.value;
      if (!status) return;

      const ok = await confirmAction(confirmMessage, {
        confirmText: 'Ya, Simpan!',
        icon: 'question',
      });
      if (!ok) return;

      upsert(
        'absensi',
        (a) => Number(a.id_siswa) === idSiswa && a.tanggal === tanggal,
        {
          id_siswa: idSiswa,
          id_kelas: idKelas,
          tanggal,
          status,
        }
      );

      rerender();
      showSuccess('Data absensi berhasil diperbarui');
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const user = requireAuth('login.html');
  if (!user) return;

  renderLayout({ activeNav: 'absensi', title: 'Absensi - Profil Jurusan RPL' });
  const root = document.getElementById('absensi-root');
  const state = { tingkat: '', kelas: '', tanggal: todayIso() };

  if (user.role === 'siswa') {
    renderSiswaAbsensi(root, user);
  } else if (user.role === 'guru') {
    renderGuruAbsensi(root, user, state);
  } else {
    renderAdminAbsensi(root, state);
  }
});

