/**
 * Static Portfolio Demo — Authenticated Dashboard Controller
 *
 * Mirrors app/Http/Controllers/Utama/DashboardController.php and
 * resources/views/admin/dashboard.blade.php
 */

import {
  getAll,
  getBeritaWithAuthorAll,
  getKelasWithWaliAll,
  getSiswaWithKelasAll,
  updateById,
  removeById,
} from '../store.js';
import { requireAuth } from '../auth.js';
import {
  renderLayout,
  confirmAction,
  showSuccess,
  escapeHtml,
  nl2br,
} from '../layout.js';
import {
  resolveBeritaImageSrc,
  formatBeritaAuthor,
  formatShortDate,
  nowSqlDatetime,
} from '../berita-helpers.js';

function renderWelcomeCard(user) {
  const card = document.getElementById('dashboard-welcome-card');
  if (!card) return;

  if (user.role === 'admin') {
    card.innerHTML = `
      <h1 class="fw-bold mb-1">Dashboard Admin</h1>
      <p class="text-muted mb-0">Kelola berita, guru, siswa, kelas, dan mapel 👋</p>
    `;
  } else {
    card.innerHTML = `
      <h1 class="fw-bold mb-1">Halo, ${escapeHtml(user.username)}!</h1>
      <p class="text-muted mb-0">Selamat datang di halaman dashboard ${escapeHtml(user.role)} 👋</p>
    `;
  }
}

function getVisibleBerita(user) {
  let list = getBeritaWithAuthorAll();

  if (user.role !== 'admin') {
    list = list.filter(
      (b) => Boolean(b.tanggal_post) || Number(b.created_by) === Number(user.id_user)
    );
  }

  return list
    .sort((a, b) => {
      const aPending = a.tanggal_post ? 0 : 1;
      const bPending = b.tanggal_post ? 0 : 1;
      if (aPending !== bPending) return bPending - aPending;

      const dateCmp = String(b.tanggal_post || '').localeCompare(String(a.tanggal_post || ''));
      if (dateCmp !== 0) return dateCmp;

      return Number(b.id_berita) - Number(a.id_berita);
    })
    .slice(0, 10);
}

function renderBeritaList(user) {
  const container = document.getElementById('dashboard-berita-list');
  if (!container) return;

  const beritaList = getVisibleBerita(user);

  if (beritaList.length === 0) {
    container.innerHTML = '<div class="alert alert-info">Belum ada berita.</div>';
    return;
  }

  container.innerHTML = beritaList
    .map((b) => {
      const isPending = !b.tanggal_post;
      const isOwnerOrAdmin =
        user.role === 'admin' || Number(b.created_by) === Number(user.id_user);
      const imgSrc = resolveBeritaImageSrc(b.gambar);

      const imageCol = imgSrc
        ? `
          <div class="col-md-5 d-none d-md-block" style="max-height:220px; overflow:hidden;">
            <img src="${escapeHtml(imgSrc)}" class="img-fluid h-100 w-100" style="object-fit:cover;" alt="gambar berita">
          </div>
        `
        : '';

      const statusMeta = isPending
        ? '<span class="badge bg-warning text-dark">Menunggu Persetujuan</span>'
        : escapeHtml(formatShortDate(b.tanggal_post));

      const rawIsi = String(b.isi || '');
      const excerpt = rawIsi.length > 200 ? `${rawIsi.slice(0, 200)}...` : rawIsi;

      let leftAction = '';
      if (!isPending) {
        leftAction = `<a href="berita-show.html?id=${encodeURIComponent(b.id_berita)}" class="btn btn-outline-dark btn-sm">Baca selengkapnya</a>`;
      } else if (isOwnerOrAdmin) {
        leftAction = `<a href="berita-show.html?id=${encodeURIComponent(b.id_berita)}" class="btn btn-outline-secondary btn-sm">Preview</a>`;
      } else {
        leftAction = '<span class="text-muted small">Sedang ditinjau</span>';
      }

      const accButtons =
        user.role === 'admin' && isPending
          ? `
            <button type="button" class="btn btn-success btn-sm" data-action="acc" data-id="${b.id_berita}">ACC</button>
            <button type="button" class="btn btn-warning btn-sm" data-action="reject" data-id="${b.id_berita}">Tolak</button>
          `
          : '';

      const ownerButtons = isOwnerOrAdmin
        ? `
          <a href="berita-form.html?id=${encodeURIComponent(b.id_berita)}" class="btn btn-primary btn-sm">Edit</a>
          <button type="button" class="btn btn-danger btn-sm" data-action="delete" data-id="${b.id_berita}">Hapus</button>
        `
        : '';

      return `
        <div class="card mb-4 shadow-sm overflow-hidden border ${isPending ? 'border-warning' : ''}">
          <div class="row g-0">
            ${imageCol}
            <div class="${imgSrc ? 'col-md-7' : 'col-12'}">
              <div class="p-3 d-flex flex-column h-100">
                <div class="small text-muted mb-2">
                  ${statusMeta} &middot; ${escapeHtml(formatBeritaAuthor(b))}
                </div>
                <h5 class="fw-bold mb-2">${escapeHtml(b.judul || 'Tanpa Judul')}</h5>
                <p class="flex-grow-1 mb-2">${nl2br(excerpt)}</p>
                <div class="d-flex justify-content-between align-items-center mt-auto flex-wrap gap-2">
                  <div>${leftAction}</div>
                  <div class="d-flex gap-2">${accButtons}${ownerButtons}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    })
    .join('');

  container.querySelectorAll('button[data-action]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const action = btn.dataset.action;
      const id = Number(btn.dataset.id);

      if (action === 'acc') {
        const ok = await confirmAction('Setujui berita ini agar tayang?', {
          confirmText: 'Ya, Setujui!',
          icon: 'question',
        });
        if (!ok) return;
        updateById('berita', id, { tanggal_post: nowSqlDatetime() });
        renderBeritaList(user);
        showSuccess('Berita disetujui dan ditayangkan.');
      } else if (action === 'reject') {
        const ok = await confirmAction('Tolak dan hapus berita ini?', {
          confirmText: 'Ya, Tolak!',
          icon: 'warning',
        });
        if (!ok) return;
        removeById('berita', id);
        renderBeritaList(user);
        showSuccess('Berita ditolak dan dihapus.');
      } else if (action === 'delete') {
        const ok = await confirmAction('Yakin ingin menghapus berita ini?', {
          confirmText: 'Ya, Hapus!',
          icon: 'warning',
        });
        if (!ok) return;
        removeById('berita', id);
        renderBeritaList(user);
        showSuccess('Berita dihapus.');
      }
    });
  });
}

function renderSidebarAndMapel() {
  const siswaList = getSiswaWithKelasAll();
  const guruList = [...getAll('guru')].sort((a, b) => a.nama.localeCompare(b.nama));
  const kelasList = getKelasWithWaliAll();
  const mapelList = [...getAll('mapel')].sort((a, b) =>
    a.nama_mapel.localeCompare(b.nama_mapel)
  );

  document.getElementById('stat-siswa').textContent = siswaList.length;
  document.getElementById('stat-guru').textContent = guruList.length;
  document.getElementById('stat-kelas').textContent = kelasList.length;
  document.getElementById('stat-mapel').textContent = mapelList.length;

  document.getElementById('dashboard-mapel-list').innerHTML = mapelList
    .slice(0, 12)
    .map(
      (m) => `
      <span class="badge bg-light text-dark border">
        ${escapeHtml(m.nama_mapel)} <small>/ ${escapeHtml(m.kategori)}</small>
      </span>
    `
    )
    .join('');

  const guruUl = document.getElementById('dashboard-guru-list');
  guruUl.innerHTML =
    guruList.length > 0
      ? guruList
          .slice(0, 6)
          .map(
            (g) => `
          <li class="list-group-item">
            <strong>${escapeHtml(g.nama)}</strong><br>
            <small>${escapeHtml(g.nip || '-')}</small>
          </li>
        `
          )
          .join('')
      : '<li class="list-group-item">Belum ada data guru.</li>';

  const siswaUl = document.getElementById('dashboard-siswa-list');
  siswaUl.innerHTML =
    siswaList.length > 0
      ? siswaList
          .slice(0, 6)
          .map(
            (s) => `
          <li class="list-group-item">
            ${escapeHtml(s.nama)}<br>
            <small>NISN: ${escapeHtml(s.nisn)}</small>
          </li>
        `
          )
          .join('')
      : '<li class="list-group-item">Belum ada data siswa.</li>';

  const kelasUl = document.getElementById('dashboard-kelas-list');
  kelasUl.innerHTML =
    kelasList.length > 0
      ? kelasList
          .slice(0, 8)
          .map(
            (k) => `
          <li class="list-group-item">
            <small>${escapeHtml(k.tingkat)}</small> /
            <small>${escapeHtml(k.kelas)}</small>
          </li>
        `
          )
          .join('')
      : '<li class="list-group-item">Belum ada data kelas.</li>';
}

document.addEventListener('DOMContentLoaded', () => {
  const user = requireAuth('login.html');
  if (!user) return;

  renderLayout({ activeNav: 'dashboard', title: 'Dashboard - Profil Jurusan RPL' });
  renderWelcomeCard(user);
  renderBeritaList(user);
  renderSidebarAndMapel();
});

