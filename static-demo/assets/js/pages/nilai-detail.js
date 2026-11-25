/**
 * Static Portfolio Demo — Detail Nilai Siswa Controller
 *
 * Mirrors app/Http/Controllers/Utama/NilaiController.php@show/update/destroy,
 * app/Http/Controllers/Export/NilaiController.php, and resources/views/admin/nilai/show.blade.php
 */

import {
  getSiswaWithKelasAll,
  getMapelDiampuForKelas,
  getAll,
  upsert,
  removeById,
  validateFields,
} from '../store.js';
import { requireAuth, setFlash } from '../auth.js';
import {
  renderLayout,
  confirmAction,
  showSuccess,
  showValidationErrors,
  escapeHtml,
} from '../layout.js';
import { exportCsv, exportPrintReport } from '../export.js';

let currentUser = null;
let targetSiswa = null;
let modalInstance = null;

function buildExistingNilaiMap(idSiswa) {
  const raw = getAll('nilai').filter((n) => Number(n.id_siswa) === Number(idSiswa));
  const map = {};
  raw.forEach((n) => {
    if (!map[n.id_ampu]) map[n.id_ampu] = {};
    map[n.id_ampu][n.semester] = n;
  });
  return map;
}

function renderNilaiDetail() {
  const root = document.getElementById('nilai-detail-root');
  const mapels = getMapelDiampuForKelas(targetSiswa.id_kelas);
  const existingNilai = buildExistingNilaiMap(targetSiswa.id_siswa);

  const editableAmpuIds = new Set();
  if (currentUser.role === 'guru') {
    mapels.forEach((m) => {
      if (Number(m.id_guru) === Number(currentUser.id_guru)) {
        editableAmpuIds.add(Number(m.id_ampu));
      }
    });
  }

  const kelasLabel = targetSiswa.kelas
    ? `${targetSiswa.kelas.tingkat} ${targetSiswa.kelas.kelas}`
    : 'Belum Ada Kelas';

  const rowsHtml =
    mapels.length > 0
      ? mapels
          .map((m, i) => {
            const nGanjil = existingNilai[m.id_ampu]?.Ganjil || null;
            const nGenap = existingNilai[m.id_ampu]?.Genap || null;
            const editable = editableAmpuIds.has(Number(m.id_ampu));
            const tingkatMapel = m.kelas?.tingkat || '';

            const badge = (n) => {
              if (!n) return '<span class="text-muted">-</span>';
              const cls = Number(n.nilai_akhir) >= 75 ? 'bg-success' : 'bg-warning';
              return `<span class="badge ${cls}">${escapeHtml(n.nilai_akhir)}</span>`;
            };

            const actionBtn = editable
              ? `<button type="button" class="btn btn-sm btn-primary" data-manage-ampu="${m.id_ampu}">
                   <i class="fa fa-edit"></i> Kelola
                 </button>`
              : '<span class="text-muted"><i class="fa fa-lock"></i></span>';

            return `
              <tr class="align-middle"
                  data-mapel="${escapeHtml((m.mapel?.nama_mapel || '').toLowerCase())}"
                  data-tingkat="${escapeHtml(tingkatMapel)}">
                <td class="text-center">${i + 1}</td>
                <td>
                  <span class="fw-bold text-primary">${escapeHtml(m.mapel?.nama_mapel || '-')}</span>
                  <div class="small text-muted">Kelas: ${escapeHtml(tingkatMapel)}</div>
                </td>
                <td class="small">${escapeHtml(m.guru?.nama || '-')}</td>
                <td class="text-center">${badge(nGanjil)}</td>
                <td class="text-center">${badge(nGenap)}</td>
                <td class="text-center">${actionBtn}</td>
              </tr>
            `;
          })
          .join('')
      : '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada mata pelajaran untuk kelas ini.</td></tr>';

  root.innerHTML = `
    <div class="layout-golden">
      <div class="left-col">
        <div class="card shadow-sm p-4 mb-4">
          <h4 class="fw-bold mb-1">Detail Nilai Siswa</h4>
          <p class="mb-3 text-muted">
            <strong>${escapeHtml(targetSiswa.nama)}</strong> &middot; ${escapeHtml(kelasLabel)}
          </p>

          <div class="row g-2 mb-4 align-items-center">
            <div class="col-md-4">
              <div class="input-group">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
                <input type="text" id="searchInputMapel" class="form-control" placeholder="Cari Mapel...">
              </div>
            </div>
            <div class="col-md-3">
              <select id="filterTahun" class="form-select">
                <option value="">Semua Tahun (Tingkat)</option>
                <option value="X">Tahun ke-1 (Kelas X)</option>
                <option value="XI">Tahun ke-2 (Kelas XI)</option>
                <option value="XII">Tahun ke-3 (Kelas XII)</option>
              </select>
            </div>
            <div class="col-md-5 text-end d-flex justify-content-md-end gap-2 flex-wrap">
              <button type="button" id="btnExportCsv" class="btn btn-sm btn-outline-primary">Export CSV</button>
              <button type="button" id="btnExportPdf" class="btn btn-sm btn-outline-danger">Export PDF</button>
              <a href="nilai.html" class="btn btn-sm btn-secondary">← Kembali</a>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" id="nilaiTable">
              <thead class="table-light">
                <tr>
                  <th rowspan="2" class="text-center">No</th>
                  <th rowspan="2">Mata Pelajaran</th>
                  <th rowspan="2">Guru</th>
                  <th colspan="2" class="text-center">Nilai Akhir</th>
                  <th rowspan="2" class="text-center">Aksi</th>
                </tr>
                <tr>
                  <th class="text-center text-muted small">Ganjil</th>
                  <th class="text-center text-muted small">Genap</th>
                </tr>
              </thead>
              <tbody>${rowsHtml}</tbody>
            </table>
          </div>
        </div>
      </div>

      <aside class="sidebar">
        <div class="stat-card">
          <h6>Info Siswa</h6>
          <p class="mb-1"><strong>NISN:</strong> ${escapeHtml(targetSiswa.nisn)}</p>
          <p class="mb-1"><strong>Kelas:</strong> ${escapeHtml(kelasLabel)}</p>
        </div>
        <div class="stat-card">
          <h6>Total Mapel</h6>
          <div class="stat-value">${mapels.length}</div>
        </div>
      </aside>
    </div>
  `;

  function filterRows() {
    const q = (document.getElementById('searchInputMapel')?.value || '').trim().toLowerCase();
    const tahun = document.getElementById('filterTahun')?.value || '';
    document.querySelectorAll('#nilaiTable tbody tr[data-mapel]').forEach((row) => {
      const matchName = (row.dataset.mapel || '').includes(q);
      const matchTahun = tahun === '' || (row.dataset.tingkat || '') === tahun;
      row.style.display = matchName && matchTahun ? '' : 'none';
    });
  }

  document.getElementById('searchInputMapel')?.addEventListener('input', filterRows);
  document.getElementById('filterTahun')?.addEventListener('change', filterRows);

  root.querySelectorAll('[data-manage-ampu]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const idAmpu = Number(btn.dataset.manageAmpu);
      const ampu = mapels.find((m) => Number(m.id_ampu) === idAmpu);
      if (!ampu) return;
      openModalNilai({
        id_ampu: idAmpu,
        mapel: ampu.mapel?.nama_mapel || '-',
        ganjil: existingNilai[idAmpu]?.Ganjil || null,
        genap: existingNilai[idAmpu]?.Genap || null,
      });
    });
  });

  function getExportRows() {
    return mapels.map((m) => {
      const n = existingNilai[m.id_ampu]?.Genap || existingNilai[m.id_ampu]?.Ganjil || null;
      return {
        mapel: m.mapel?.nama_mapel || '-',
        guru: m.guru?.nama || '-',
        semester: n?.semester || '-',
        tugas: n?.nilai_tugas ?? '-',
        uts: n?.nilai_uts ?? '-',
        akhir: n?.nilai_akhir ?? '-',
      };
    });
  }

  document.getElementById('btnExportCsv')?.addEventListener('click', () => {
    const rows = getExportRows().map((r) => [
      targetSiswa.nisn,
      targetSiswa.nama,
      kelasLabel,
      r.mapel,
      r.guru,
      r.semester,
      r.tugas,
      r.uts,
      r.akhir,
    ]);
    exportCsv(
      `nilai_${targetSiswa.nama.replace(/\s+/g, '_')}.csv`,
      ['NISN', 'Nama Siswa', 'Kelas', 'Mapel', 'Guru', 'Semester', 'Nilai Tugas', 'Nilai UTS', 'Nilai Akhir'],
      rows
    );
  });

  document.getElementById('btnExportPdf')?.addEventListener('click', () => {
    const rows = getExportRows().map((r, i) => [
      i + 1,
      r.mapel,
      r.guru,
      r.semester,
      r.tugas,
      r.uts,
      r.akhir,
    ]);
    exportPrintReport({
      title: 'Laporan Nilai Siswa',
      infoHtml: `<strong>${escapeHtml(targetSiswa.nama)}</strong><br>NISN: ${escapeHtml(
        targetSiswa.nisn
      )}<br>Kelas: ${escapeHtml(kelasLabel)}`,
      headers: ['No', 'Mapel', 'Guru', 'Semester', 'Tugas', 'UTS', 'Akhir'],
      rows,
    });
  });
}

function openModalNilai(data) {
  document.getElementById('modalMapelName').innerText = data.mapel;
  document.getElementById('inputAmpuGanjil').value = data.id_ampu;
  document.getElementById('inputAmpuGenap').value = data.id_ampu;

  const g = data.ganjil;
  document.getElementById('tugasGanjil').value = g ? g.nilai_tugas : '';
  document.getElementById('utsGanjil').value = g ? g.nilai_uts : '';
  const delGanjil = document.getElementById('btnDeleteGanjilContainer');
  delGanjil.innerHTML = g
    ? `<button type="button" class="btn btn-outline-danger" data-del-nilai="${g.id_nilai}" data-sem="Ganjil">Hapus Nilai</button>`
    : '';

  const gp = data.genap;
  document.getElementById('tugasGenap').value = gp ? gp.nilai_tugas : '';
  document.getElementById('utsGenap').value = gp ? gp.nilai_uts : '';
  const delGenap = document.getElementById('btnDeleteGenapContainer');
  delGenap.innerHTML = gp
    ? `<button type="button" class="btn btn-outline-danger" data-del-nilai="${gp.id_nilai}" data-sem="Genap">Hapus Nilai</button>`
    : '';

  document.querySelectorAll('[data-del-nilai]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const idNilai = Number(btn.dataset.delNilai);
      const sem = btn.dataset.sem;
      const ok = await confirmAction(`Hapus nilai semester ${sem}?`, {
        title: 'Hapus Nilai?',
        confirmText: 'Ya, Hapus!',
        icon: 'warning',
      });
      if (!ok) return;

      removeById('nilai', idNilai);
      modalInstance.hide();
      renderNilaiDetail();
      showSuccess('Nilai berhasil dihapus');
    });
  });

  modalInstance?.show();
}

function handleSemesterSave(semester, ampuIdEl, tugasEl, utsEl) {
  const idAmpu = Number(ampuIdEl.value);
  const tugas = tugasEl.value;
  const uts = utsEl.value;

  const check = validateFields(
    { nilai_tugas: tugas, nilai_uts: uts },
    {
      nilai_tugas: { label: 'Nilai Tugas', required: true, numeric: true, min: 0, max: 100 },
      nilai_uts: { label: 'Nilai UTS', required: true, numeric: true, min: 0, max: 100 },
    }
  );

  if (!check.valid) {
    showValidationErrors(check.errors);
    return;
  }

  // Ownership check matching NilaiController@update
  if (currentUser.role === 'guru') {
    const owns = getAll('mapel_diampu').some(
      (a) => Number(a.id_ampu) === idAmpu && Number(a.id_guru) === Number(currentUser.id_guru)
    );
    if (!owns) {
      showValidationErrors(['Akses ditolak: Anda bukan pengampu mapel ini.']);
      return;
    }
  }

  const numTugas = Number(tugas);
  const numUts = Number(uts);
  const akhir = Math.round((numTugas * 0.4 + numUts * 0.6) * 100) / 100;

  upsert(
    'nilai',
    (n) =>
      Number(n.id_siswa) === Number(targetSiswa.id_siswa) &&
      Number(n.id_ampu) === idAmpu &&
      n.semester === semester,
    {
      id_siswa: Number(targetSiswa.id_siswa),
      id_ampu: idAmpu,
      semester,
      nilai_tugas: numTugas,
      nilai_uts: numUts,
      nilai_akhir: akhir,
    }
  );

  modalInstance.hide();
  renderNilaiDetail();
  showSuccess(`Nilai semester ${semester} berhasil disimpan`);
}

document.addEventListener('DOMContentLoaded', () => {
  currentUser = requireAuth('login.html');
  if (!currentUser) return;

  const params = new URLSearchParams(window.location.search);
  const idSiswa = Number(params.get('id_siswa'));

  if (currentUser.role === 'siswa' && Number(currentUser.id_siswa) !== idSiswa) {
    setFlash('error', 'Anda hanya dapat melihat nilai Anda sendiri.');
    window.location.href = 'nilai.html';
    return;
  }

  targetSiswa = getSiswaWithKelasAll().find((s) => Number(s.id_siswa) === idSiswa);
  if (!targetSiswa) {
    setFlash('error', 'Siswa tidak ditemukan.');
    window.location.href = 'nilai.html';
    return;
  }

  renderLayout({ activeNav: 'nilai', title: `Nilai ${targetSiswa.nama} - Profil Jurusan RPL` });
  modalInstance = new window.bootstrap.Modal(document.getElementById('modalInputNilai'));
  renderNilaiDetail();

  document.getElementById('formNilaiGanjil')?.addEventListener('submit', (e) => {
    e.preventDefault();
    handleSemesterSave(
      'Ganjil',
      document.getElementById('inputAmpuGanjil'),
      document.getElementById('tugasGanjil'),
      document.getElementById('utsGanjil')
    );
  });

  document.getElementById('formNilaiGenap')?.addEventListener('submit', (e) => {
    e.preventDefault();
    handleSemesterSave(
      'Genap',
      document.getElementById('inputAmpuGenap'),
      document.getElementById('tugasGenap'),
      document.getElementById('utsGenap')
    );
  });
});

