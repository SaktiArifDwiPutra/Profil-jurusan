/**
 * Static Portfolio Demo — Public News Detail Controller
 *
 * Mirrors app/Http/Controllers/Beritacontroller2.php and resources/views/berita/detail.blade.php
 */

import { getBeritaWithAuthorAll, getKomentarByBerita } from '../store.js';
import { getCurrentUser } from '../auth.js';
import { renderLayout, escapeHtml, nl2br } from '../layout.js';
import { resolveBeritaImageSrc, formatBeritaAuthor } from '../berita-helpers.js';

document.addEventListener('DOMContentLoaded', () => {
  renderLayout({ activeNav: 'home', title: 'Detail Berita - Profil Jurusan RPL' });

  const root = document.getElementById('berita-detail-root');
  if (!root) return;

  const params = new URLSearchParams(window.location.search);
  const id = Number(params.get('id'));

  const allBerita = getBeritaWithAuthorAll();
  const berita = allBerita.find((b) => Number(b.id_berita) === id && Boolean(b.tanggal_post));

  if (!berita) {
    root.innerHTML = `
      <div class="card p-4 shadow-sm text-center">
        <h4 class="fw-bold mb-2">Berita tidak ditemukan</h4>
        <p class="text-muted mb-3">Artikel yang Anda cari tidak tersedia atau belum ditayangkan.</p>
        <div><a href="index.html" class="btn btn-primary">← Kembali ke Beranda</a></div>
      </div>
    `;
    return;
  }

  document.title = `${berita.judul} - Profil Jurusan RPL`;

  const comments = getKomentarByBerita(id);
  const otherBerita = allBerita
    .filter((b) => Number(b.id_berita) !== id && Boolean(b.tanggal_post))
    .sort((a, b) => String(b.tanggal_post).localeCompare(String(a.tanggal_post)))
    .slice(0, 5);

  const imgSrc = resolveBeritaImageSrc(berita.gambar);
  const imageHtml = imgSrc
    ? `<img src="${escapeHtml(imgSrc)}" alt="gambar berita" class="img-fluid mb-3 rounded" style="max-height:400px; object-fit:cover; width:100%;">`
    : '';

  const commentsHtml =
    comments.length === 0
      ? '<div class="text-muted mb-2">Belum ada komentar.</div>'
      : comments
          .map((c) => {
            const author = c.user?.username || 'Pengguna';
            const initial = escapeHtml(author.charAt(0).toUpperCase());
            return `
              <div class="d-flex mb-3">
                <div class="me-3">
                  <div style="width:40px;height:40px;border-radius:50%;background:#dee7fb;display:flex;align-items:center;justify-content:center;font-weight:600;color:#0b5ed7;">
                    ${initial}
                  </div>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center mb-1">
                    <strong>${escapeHtml(author)}</strong>
                    <div class="small text-muted ms-2">${escapeHtml(c.tanggal_post)}</div>
                  </div>
                  <div>${nl2br(c.komentar)}</div>
                </div>
              </div>
            `;
          })
          .join('');

  const user = getCurrentUser();
  const commentActionBlock = user
    ? `
      <div class="mt-4 pt-3 border-top">
        <div class="bg-light p-3 rounded text-center border">
          <p class="small text-muted mb-2">Anda login sebagai <strong>${escapeHtml(user.username)}</strong>.</p>
          <a href="berita-show.html?id=${encodeURIComponent(id)}" class="btn btn-primary px-4">
            <i class="fa fa-comments me-1"></i> Tulis Komentar di Mode Anggota
          </a>
        </div>
      </div>
    `
    : `
      <div class="mt-4 pt-3 border-top">
        <div class="bg-light p-3 rounded text-center border">
          <i class="fa fa-lock mb-2 text-muted" style="font-size: 1.5rem;"></i>
          <h6 class="text-muted fw-semibold">Ingin bergabung dalam diskusi?</h6>
          <p class="small text-muted mb-3">Silahkan login terlebih dahulu untuk menulis komentar.</p>
          <textarea class="form-control mb-3" rows="2" disabled
            style="cursor: not-allowed; background-color: #e9ecef;">Login terlebih dahulu untuk berkomentar...</textarea>
          <a href="login.html" class="btn btn-primary px-4">
            <i class="fa fa-sign-in-alt me-1"></i> Login Sekarang
          </a>
        </div>
      </div>
    `;

  const otherHtml =
    otherBerita.length > 0
      ? otherBerita
          .map(
            (o) => `
          <li class="mb-2">
            <a href="berita-detail.html?id=${encodeURIComponent(o.id_berita)}" class="link-primary fw-medium">
              ${escapeHtml(o.judul)}
            </a>
            <div class="small text-muted">${escapeHtml(o.tanggal_post)}</div>
          </li>
        `
          )
          .join('')
      : '<li class="text-muted small">Tidak ada berita lainnya.</li>';

  root.innerHTML = `
    <div class="row">
      <div class="col-md-8">
        <article class="card p-4 mb-4 shadow-sm">
          <header class="mb-3">
            <h2 class="fw-bold">${escapeHtml(berita.judul)}</h2>
            <div class="small text-muted">
              ${escapeHtml(berita.tanggal_post || '-')} &middot; ${escapeHtml(formatBeritaAuthor(berita))}
            </div>
          </header>
          ${imageHtml}
          <div class="mb-3">${nl2br(berita.isi)}</div>
          <div class="d-flex justify-content-between">
            <a href="index.html" class="btn btn-danger">← Kembali</a>
          </div>
        </article>

        <section id="comments" class="card p-4 shadow-sm">
          <h5 class="fw-semibold mb-3">Komentar (${comments.length})</h5>
          ${commentsHtml}
          ${commentActionBlock}
        </section>
      </div>

      <div class="col-md-4">
        <div class="card p-4 shadow-sm mb-4">
          <h6 class="fw-semibold mb-3">Berita Lainnya</h6>
          <ul class="list-unstyled mb-0">${otherHtml}</ul>
        </div>
      </div>
    </div>
  `;
});

