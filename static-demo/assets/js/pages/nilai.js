/**
 * Static Portfolio Demo — Nilai Index Controller
 *
 * Mirrors app/Http/Controllers/Utama/NilaiController.php@index and
 * resources/views/admin/nilai/{index,index_siswa}.blade.php
 */

import { getSiswaWithKelasAll, getNilaiForSiswa } from '../store.js';
import { requireAuth, setFlash } from '../auth.js';
import { renderLayout, escapeHtml } from '../layout.js';

function renderSiswaView(root, user) {
  const siswa = user.siswa;
  if (!siswa) {
    setFlash('error', 'Data siswa tidak ditemukan pada akun ini.');
    window.location.href = 'dashboard.html';
    return;
  }

  const nilai = getNilaiForSiswa(siswa.id_siswa);
  const totalMapel = nilai.length;
  const nilaiPerSemester = {};
  let totalNilai = 0;

  nilai.forEach((n) => {
    totalNilai += Number(n.nilai_akhir || 0);
    const sem = n.semester;
    if (!nilaiPerSemester[sem]) nilaiPerSemester[sem] = { sum: 0, count: 0 };
    nilaiPerSemester[sem].sum += Number(n.nilai_akhir || 0);
    nilaiPerSemester[sem].count += 1;
  });

  const rataGlobal = totalMapel > 0 ? Math.round((totalNilai / totalMapel) * 100) / 100 : 0;
  const kelasLabel = siswa.kelas ? `${siswa.kelas.tingkat} ${siswa.kelas.kelas}` : 'Belum Ada Kelas';

  const rowsHtml =
    nilai.length > 0
      ? nilai
          .map(
            (n, i) => `
          <tr>
            <td>${i + 1}</td>
            <td>
              ${escapeHtml(n.ampu?.mapel?.nama_mapel || '-')}<br>
              <span class="badge text-bg-secondary">${escapeHtml(n.ampu?.mapel?.kategori || '-')}</span>
            </td>
            <td>${escapeHtml(n.ampu?.guru?.nama || '-')}</td>
            <td>${escapeHtml(n.semester)}</td>
            <td>${escapeHtml(n.nilai_tugas)}</td>
            <td>${escapeHtml(n.nilai_uts)}</td>
            <td class="fw-bold">${escapeHtml(n.nilai_akhir)}</td>
          </tr>
        `
          )
          .join('')
      : '<tr><td colspan="7" class="text-center">Belum ada nilai.</td></tr>';

  const semEntries = Object.entries(nilaiPerSemester);
  const semHtml =
    semEntries.length > 0
      ? semEntries
          .map(([sem, d]) => {
            const avg = d.count > 0 ? Math.round((d.sum / d.count) * 100) / 100 : 0;
            return `
              <div class="recent-item">
                <div>Semester ${escapeHtml(sem)} (${d.count} mapel):</div>
                <strong class="text-primary">${avg}</strong>
              </div>
            `;
          })
          .join('')
      : '<div class="recent-item">Belum ada data nilai</div>';

  root.innerHTML = `
    <div class="layout-golden">
      <div class="left-col">
        <div class="card shadow-sm p-4 mb-4">
          <h4 class="fw-bold mb-1">Nilai Saya</h4>
          <p class="mb-3 text-muted">Data nilai untuk <strong>${escapeHtml(siswa.nama)}</strong> (Kelas ${escapeHtml(kelasLabel)})</p>
          <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Mapel</th>
                  <th>Guru</th>
                  <th>Semester</th>
                  <th>Tugas</th>
                  <th>UTS</th>
                  <th>Akhir</th>
                </tr>
              </thead>
              <tbody>${rowsHtml}</tbody>
            </table>
          </div>
        </div>
      </div>

      <aside class="sidebar">
        <div class="stat-card">
          <h6>Rata-rata Nilai Akhir (Global)</h6>
          <div class="stat-value text-primary">${rataGlobal}</div>
        </div>
        <div class="stat-card">
          <h6>Jumlah Mapel Tercatat</h6>
          <div class="stat-value">${totalMapel}</div>
        </div>
        <div class="stat-card">
          <h6>Rata-rata Nilai per Semester</h6>
          <div class="recent-list">${semHtml}</div>
        </div>
      </aside>
    </div>
  `;
}

function renderStaffView(root) {
  const siswas = [...getSiswaWithKelasAll()].sort((a, b) => {
    const tA = a.kelas?.tingkat || '';
    const tB = b.kelas?.tingkat || '';
    if (tA !== tB) return tA.localeCompare(tB);
    const kA = a.kelas?.kelas || '';
    const kB = b.kelas?.kelas || '';
    if (kA !== kB) return kA.localeCompare(kB);
    return a.nama.localeCompare(b.nama);
  });

  const kelasCount = {};
  siswas.forEach((s) => {
    const label = s.kelas ? `${s.kelas.tingkat} - ${s.kelas.kelas}` : 'Belum Ada Kelas';
    kelasCount[label] = (kelasCount[label] || 0) + 1;
  });

  const cardsHtml =
    siswas.length > 0
      ? siswas
          .map((s) => {
            const label = s.kelas ? `${s.kelas.tingkat} - ${s.kelas.kelas}` : 'Belum Ada Kelas';
            return `
              <div class="col siswa-card-col"
                   data-id="${s.id_siswa}"
                   data-nisn="${escapeHtml(String(s.nisn || '').toLowerCase())}"
                   data-nama="${escapeHtml(s.nama.toLowerCase())}"
                   data-kelas="${escapeHtml(label.toLowerCase())}">
                <div class="card siswa-card shadow-sm h-100">
                  <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h5 class="card-title mb-0" style="font-size:1rem;">${escapeHtml(s.nama)}</h5>
                      <span class="badge rounded-pill text-bg-secondary" style="font-size:.75rem;">${escapeHtml(label)}</span>
                    </div>
                    <div class="mb-2 text-muted" style="font-size:.9rem;">
                      <div><strong>NISN:</strong> ${escapeHtml(s.nisn || '-')}</div>
                    </div>
                    <div style="margin-top:auto; display:flex; gap:.5rem; width:100%;">
                      <a href="nilai-detail.html?id_siswa=${encodeURIComponent(s.id_siswa)}" class="btn btn-sm btn-primary flex-grow-1">Lihat Nilai</a>
                    </div>
                  </div>
                </div>
              </div>
            `;
          })
          .join('')
      : '<div class="col"><div class="card p-3 text-center text-muted">Tidak ada data siswa</div></div>';

  const distribusiHtml = Object.entries(kelasCount)
    .map(
      ([label, cnt]) =>
        `<div style="font-size:.95rem; color:#333; margin-bottom:4px;">${escapeHtml(label)}: <strong>${cnt}</strong></div>`
    )
    .join('');

  const recent = [...siswas].sort((a, b) => Number(b.id_siswa) - Number(a.id_siswa)).slice(0, 6);
  const recentHtml =
    recent.length > 0
      ? recent
          .map(
            (r) => `
          <div class="recent-item" style="cursor:pointer" data-scroll-id="${r.id_siswa}">
            <div style="min-width:0;">
              <strong>${escapeHtml(r.nama)}</strong>
              <div style="font-size:0.85rem;color:#666">${escapeHtml(r.kelas ? `${r.kelas.tingkat} - ${r.kelas.kelas}` : 'Belum Ada Kelas')}</div>
            </div>
          </div>
        `
          )
          .join('')
      : '<div class="recent-item">Belum ada data</div>';

  root.innerHTML = `
    <div class="layout-golden">
      <div class="left-col">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Daftar Nilai Siswa</h4>
        </div>

        <div class="d-flex justify-content-between mb-2 align-items-center">
          <div class="input-group w-50" style="min-width:220px;">
            <span class="input-group-text"><i class="fa fa-search"></i></span>
            <input type="text" id="searchInputNilai" class="form-control" placeholder="Cari siswa (NISN, Nama, Kelas)...">
          </div>
        </div>

        <div id="siswaContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">${cardsHtml}</div>
      </div>

      <aside class="sidebar">
        <div class="stat-card">
          <h6>Total Siswa</h6>
          <div class="stat-value">${siswas.length}</div>
        </div>
        <div class="stat-card">
          <h6>Jumlah Kelas</h6>
          <div>${Object.keys(kelasCount).length}</div>
        </div>
        <div class="stat-card">
          <h6>Distribusi Kelas</h6>
          <div>${distribusiHtml}</div>
        </div>
        <div class="stat-card">
          <h6>Terakhir Ditambahkan</h6>
          <div class="recent-list">${recentHtml}</div>
        </div>
      </aside>
    </div>
  `;

  document.getElementById('searchInputNilai')?.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.siswa-card-col').forEach((col) => {
      const hay = `${col.dataset.nisn || ''} ${col.dataset.nama || ''} ${col.dataset.kelas || ''}`;
      col.style.display = !q || hay.includes(q) ? '' : 'none';
    });
  });

  root.querySelectorAll('[data-scroll-id]').forEach((item) => {
    item.addEventListener('click', () => {
      document
        .querySelector(`.siswa-card-col[data-id="${item.dataset.scrollId}"]`)
        ?.scrollIntoView({ behavior: 'smooth' });
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const user = requireAuth('login.html');
  if (!user) return;

  renderLayout({ activeNav: 'nilai', title: 'Daftar Nilai - Profil Jurusan RPL' });
  const root = document.getElementById('nilai-root');

  if (user.role === 'siswa') {
    renderSiswaView(root, user);
  } else {
    renderStaffView(root);
  }
});

