/**
 * Static Portfolio Demo — Public Homepage Controller
 *
 * Mirrors app/Http/Controllers/HomeController.php and resources/views/home.blade.php
 */

import {
  getAll,
  getBeritaWithAuthorAll,
  getKelasWithWaliAll,
  getSiswaWithKelasAll,
} from '../store.js';
import { getCurrentUser } from '../auth.js';
import { renderLayout, escapeHtml } from '../layout.js';

function limitStr(text, max = 200) {
  const str = String(text || '');
  return str.length > max ? `${str.slice(0, max)}...` : str;
}

function capitalize(str) {
  const s = String(str || '');
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

function resolveImageSrc(gambar) {
  if (!gambar) return '';
  if (gambar.startsWith('data:') || gambar.startsWith('http')) return gambar;
  return `uploads/${encodeURIComponent(gambar)}`;
}

function renderBerita(beritaList) {
  const container = document.getElementById('home-berita-list');
  if (!container) return;

  const published = beritaList
    .filter((b) => Boolean(b.tanggal_post))
    .sort((a, b) => String(b.tanggal_post).localeCompare(String(a.tanggal_post)))
    .slice(0, 5);

  if (published.length === 0) {
    container.innerHTML = '<div class="alert alert-info">Belum ada berita.</div>';
    return;
  }

  container.innerHTML = published
    .map((b) => {
      const authorText = b.user
        ? `${capitalize(b.user.role)} - ${b.user.username}`
        : b.guru
          ? `Guru - ${b.guru.nama}`
          : 'Admin';

      const imgSrc = resolveImageSrc(b.gambar);
      const imageCol = imgSrc
        ? `
          <div class="col-md-5 d-none d-md-block" style="max-height:220px; overflow:hidden;">
            <img src="${escapeHtml(imgSrc)}" alt="${escapeHtml(b.judul)}" class="img-fluid h-100 w-100" style="object-fit:cover;">
          </div>
        `
        : '';

      return `
        <div class="card mb-4 shadow-sm overflow-hidden">
          <div class="row g-0">
            ${imageCol}
            <div class="${imgSrc ? 'col-md-7' : 'col-12'}">
              <div class="p-3 d-flex flex-column h-100">
                <div class="small text-muted mb-2">
                  ${escapeHtml(b.tanggal_post || '-')} &middot; ${escapeHtml(authorText)}
                </div>
                <h5 class="fw-bold">${escapeHtml(b.judul)}</h5>
                <p class="flex-grow-1">${escapeHtml(limitStr(b.isi, 200))}</p>
                <a href="berita-detail.html?id=${encodeURIComponent(b.id_berita)}" class="btn btn-outline-dark mt-auto">
                  Baca selengkapnya
                </a>
              </div>
            </div>
          </div>
        </div>
      `;
    })
    .join('');
}

function renderMapelPreview(mapelList) {
  const container = document.getElementById('home-mapel-list');
  if (!container) return;

  const items = [...mapelList]
    .sort((a, b) => a.nama_mapel.localeCompare(b.nama_mapel))
    .slice(0, 8);

  container.innerHTML = items
    .map(
      (m) => `
      <span class="badge bg-light text-dark border">
        ${escapeHtml(m.nama_mapel)} <small>/ ${escapeHtml(m.kategori)}</small>
      </span>
    `
    )
    .join('');
}

function renderGuruPreview(guruList) {
  const container = document.getElementById('home-guru-list');
  if (!container) return;

  const items = [...guruList]
    .sort((a, b) => a.nama.localeCompare(b.nama))
    .slice(0, 6);

  if (items.length === 0) {
    container.innerHTML = '<li class="list-group-item">Belum ada data guru.</li>';
    return;
  }

  container.innerHTML = items
    .map(
      (g) => `
      <li class="list-group-item">
        <strong>${escapeHtml(g.nama)}</strong><br>
        <small>${escapeHtml(g.nip || '-')}</small>
      </li>
    `
    )
    .join('');
}

function renderKelasPreview(kelasList) {
  const container = document.getElementById('home-kelas-list');
  if (!container) return;

  const items = kelasList.slice(0, 8);
  if (items.length === 0) {
    container.innerHTML = '<li class="list-group-item">Belum ada data kelas.</li>';
    return;
  }

  container.innerHTML = items
    .map(
      (k) => `
      <li class="list-group-item">
        <small>${escapeHtml(k.tingkat)}</small> /
        <small>${escapeHtml(k.kelas)}</small>
      </li>
    `
    )
    .join('');
}

function renderSiswaPreview(siswaList) {
  const container = document.getElementById('home-siswa-list');
  if (!container) return;

  const items = siswaList.slice(0, 6);
  if (items.length === 0) {
    container.innerHTML = '<li class="list-group-item">Belum ada data siswa.</li>';
    return;
  }

  container.innerHTML = items
    .map(
      (s) => `
      <li class="list-group-item">
        ${escapeHtml(s.nama)}<br>
        <small>NISN: ${escapeHtml(s.nisn)}</small>
      </li>
    `
    )
    .join('');
}

function syncPreviewButtons(user) {
  if (!user) return;
  const targets = [
    { id: 'btn-home-mapel', href: 'mapel.html', label: 'Lihat semua mapel' },
    { id: 'btn-home-guru', href: 'guru.html', label: 'Lihat semua guru' },
    { id: 'btn-home-kelas', href: 'kelas.html', label: 'Lihat semua kelas' },
    { id: 'btn-home-siswa', href: 'siswa.html', label: 'Lihat semua siswa' },
  ];
  for (const t of targets) {
    const el = document.getElementById(t.id);
    if (el) {
      el.href = t.href;
      el.textContent = t.label;
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  renderLayout({ activeNav: 'home', title: 'Profil Jurusan RPL' });

  const siswaList = getSiswaWithKelasAll();
  const guruList = getAll('guru');
  const kelasList = getKelasWithWaliAll();
  const mapelList = getAll('mapel');
  const beritaList = getBeritaWithAuthorAll();

  document.getElementById('stat-siswa').textContent = siswaList.length;
  document.getElementById('stat-guru').textContent = guruList.length;
  document.getElementById('stat-kelas').textContent = kelasList.length;
  document.getElementById('stat-mapel').textContent = mapelList.length;

  renderBerita(beritaList);
  renderMapelPreview(mapelList);
  renderGuruPreview(guruList);
  renderKelasPreview(kelasList);
  renderSiswaPreview(siswaList);
  syncPreviewButtons(getCurrentUser());
});

