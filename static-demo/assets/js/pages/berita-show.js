/**
 * Static Portfolio Demo — Authenticated News Detail & Comments Controller
 *
 * Mirrors app/Http/Controllers/Utama/BeritaController.php@show/storeComment/deleteComment
 * and resources/views/admin/berita/show.blade.php
 */

import {
  getBeritaWithAuthorAll,
  getKomentarByBerita,
  insert,
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
  nl2br,
} from '../layout.js';
import {
  resolveBeritaImageSrc,
  formatBeritaAuthor,
  nowSqlDatetime,
} from '../berita-helpers.js';

function renderCommentsSection(user, idBerita) {
  const section = document.getElementById('comments');
  if (!section) return;

  const comments = getKomentarByBerita(idBerita);
  const listHtml =
    comments.length === 0
      ? '<div class="text-muted mb-2">Belum ada komentar. Jadilah yang pertama!</div>'
      : comments
          .map((c) => {
            const author = c.user?.username || 'admin';
            const initial = escapeHtml(author.charAt(0).toUpperCase());
            const canDelete =
              user.role === 'admin' || Number(c.id_user) === Number(user.id_user);

            const delBtn = canDelete
              ? `<button type="button" class="btn btn-sm btn-outline-danger" data-del-komentar="${c.id_komentar}">Hapus</button>`
              : '';

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
                    <div class="ms-auto">${delBtn}</div>
                  </div>
                  <div>${nl2br(c.komentar)}</div>
                </div>
              </div>
            `;
          })
          .join('');

  section.innerHTML = `
    <h5 class="fw-semibold mb-3">Komentar (${comments.length})</h5>
    ${listHtml}
    <form id="commentForm" class="mt-3" novalidate>
      <label for="komentarInput" class="form-label">Komentar sebagai <strong>${escapeHtml(user.username)}</strong></label>
      <textarea id="komentarInput" name="komentar" rows="3" class="form-control mb-2" maxlength="1000" required></textarea>
      <button type="submit" class="btn btn-primary">Kirim Komentar</button>
    </form>
  `;

  section.querySelectorAll('button[data-del-komentar]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const ok = await confirmAction('Hapus komentar ini?', {
        confirmText: 'Ya, Hapus!',
        icon: 'warning',
      });
      if (!ok) return;
      removeById('komentar_berita', Number(btn.dataset.delKomentar));
      renderCommentsSection(user, idBerita);
      showSuccess('Komentar dihapus.');
    });
  });

  document.getElementById('commentForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const textarea = document.getElementById('komentarInput');
    const komentar = textarea?.value || '';

    const check = validateFields(
      { komentar },
      {
        komentar: { label: 'Komentar', required: true, maxLength: 1000 },
      }
    );

    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    insert('komentar_berita', {
      id_berita: idBerita,
      id_user: user.id_user,
      komentar: komentar.trim(),
      tanggal_post: nowSqlDatetime(),
    });

    renderCommentsSection(user, idBerita);
    showSuccess('Komentar terkirim.');
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const user = requireAuth('login.html');
  if (!user) return;

  renderLayout({ activeNav: 'dashboard', title: 'Detail Berita - Profil Jurusan RPL' });

  const root = document.getElementById('berita-show-root');
  if (!root) return;

  const params = new URLSearchParams(window.location.search);
  const id = Number(params.get('id'));

  const allBerita = getBeritaWithAuthorAll();
  const berita = allBerita.find((b) => Number(b.id_berita) === id);

  if (!berita) {
    setFlash('error', 'Berita tidak ditemukan.');
    window.location.href = 'dashboard.html';
    return;
  }

  // Prevent non-admin users from viewing someone else's pending berita
  if (
    !berita.tanggal_post &&
    user.role !== 'admin' &&
    Number(berita.created_by) !== Number(user.id_user)
  ) {
    setFlash('error', 'Berita ini sedang menunggu persetujuan admin.');
    window.location.href = 'dashboard.html';
    return;
  }

  document.title = `${berita.judul} - Profil Jurusan RPL`;

  const imgSrc = resolveBeritaImageSrc(berita.gambar);
  const imageHtml = imgSrc
    ? `<img src="${escapeHtml(imgSrc)}" alt="gambar berita" class="img-fluid mb-3 rounded" style="max-height:400px; object-fit:cover; width:100%;">`
    : '';

  const metaStatus = berita.tanggal_post
    ? escapeHtml(berita.tanggal_post)
    : '<span class="badge bg-warning text-dark">Menunggu Persetujuan</span>';

  const otherBerita = allBerita
    .filter((b) => Number(b.id_berita) !== id && Boolean(b.tanggal_post))
    .sort((a, b) => String(b.tanggal_post).localeCompare(String(a.tanggal_post)))
    .slice(0, 6);

  const otherHtml =
    otherBerita.length > 0
      ? otherBerita
          .map(
            (o) => `
          <li class="mb-2">
            <a href="berita-show.html?id=${encodeURIComponent(o.id_berita)}" class="link-primary fw-medium">
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
              ${metaStatus} &middot; ${escapeHtml(formatBeritaAuthor(berita))}
            </div>
          </header>
          ${imageHtml}
          <div class="mb-3">${nl2br(berita.isi)}</div>
          <div class="d-flex justify-content-between">
            <a href="dashboard.html" class="btn btn-danger">← Kembali</a>
          </div>
        </article>

        <section id="comments" class="card p-4 shadow-sm"></section>
      </div>

      <div class="col-md-4">
        <div class="card p-4 shadow-sm mb-4">
          <h6 class="fw-semibold mb-3">Berita Lainnya</h6>
          <ul class="list-unstyled mb-0">${otherHtml}</ul>
        </div>
      </div>
    </div>
  `;

  renderCommentsSection(user, id);
});

