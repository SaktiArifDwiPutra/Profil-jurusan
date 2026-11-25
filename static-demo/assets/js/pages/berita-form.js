/**
 * Static Portfolio Demo — Create & Edit Berita Controller
 *
 * Mirrors app/Http/Controllers/Utama/BeritaController.php@create/store/edit/update
 * and resources/views/admin/berita/{create,edit}.blade.php
 */

import { findById, insert, updateById, validateFields } from '../store.js';
import { requireAuth, setFlash } from '../auth.js';
import {
  renderLayout,
  showError,
  showValidationErrors,
  escapeHtml,
} from '../layout.js';
import {
  resolveBeritaImageSrc,
  nowSqlDatetime,
  readImageFileAsDataUrl,
} from '../berita-helpers.js';

document.addEventListener('DOMContentLoaded', () => {
  const user = requireAuth('login.html');
  if (!user) return;

  const params = new URLSearchParams(window.location.search);
  const editIdParam = params.get('id');
  const isEdit = editIdParam !== null && editIdParam !== '';
  const editId = isEdit ? Number(editIdParam) : null;

  let existingBerita = null;
  if (isEdit) {
    existingBerita = findById('berita', editId);
    if (!existingBerita) {
      setFlash('error', 'Berita tidak ditemukan.');
      window.location.href = 'dashboard.html';
      return;
    }
    if (
      user.role !== 'admin' &&
      Number(existingBerita.created_by) !== Number(user.id_user)
    ) {
      setFlash('error', 'Anda tidak berhak mengedit berita ini.');
      window.location.href = 'dashboard.html';
      return;
    }
  }

  renderLayout({
    activeNav: 'dashboard',
    title: isEdit ? 'Edit Berita - Profil Jurusan RPL' : 'Tambah Berita - Profil Jurusan RPL',
  });

  const heading = document.getElementById('formHeading');
  const postingAs = document.getElementById('postingAsInfo');
  const judulInput = document.getElementById('judulInput');
  const isiInput = document.getElementById('isiInput');
  const gambarInput = document.getElementById('gambarInput');
  const currentImageBox = document.getElementById('currentImageBox');
  const imagePreviewLabel = document.getElementById('imagePreviewLabel');
  const imagePreview = document.getElementById('imagePreview');
  const submitBtn = document.getElementById('submitBtn');
  const form = document.getElementById('beritaForm');

  let pendingImageDataUrl = null;

  if (isEdit && existingBerita) {
    heading.textContent = 'Edit Berita';
    submitBtn.textContent = 'Update';
    judulInput.value = existingBerita.judul || '';
    isiInput.value = existingBerita.isi || '';

    if (existingBerita.gambar) {
      imagePreview.src = resolveBeritaImageSrc(existingBerita.gambar);
      currentImageBox.classList.remove('d-none');
    }
  } else {
    heading.textContent = 'Tambah Berita';
    submitBtn.textContent = 'Simpan Berita';
    postingAs.innerHTML = `Posting sebagai: <strong>${escapeHtml(user.username)} (${escapeHtml(user.role)})</strong>`;
    postingAs.classList.remove('d-none');
  }

  gambarInput?.addEventListener('change', async () => {
    const file = gambarInput.files?.[0];
    if (!file) {
      pendingImageDataUrl = null;
      return;
    }

    try {
      pendingImageDataUrl = await readImageFileAsDataUrl(file);
      imagePreview.src = pendingImageDataUrl;
      imagePreviewLabel.textContent = 'Pratinjau gambar baru:';
      currentImageBox.classList.remove('d-none');
    } catch (err) {
      gambarInput.value = '';
      pendingImageDataUrl = null;
      showError(err.message);
    }
  });

  form?.addEventListener('submit', (e) => {
    e.preventDefault();

    const judul = judulInput.value;
    const isi = isiInput.value;

    const check = validateFields(
      { judul, isi },
      {
        judul: { label: 'Judul', required: true, maxLength: 255 },
        isi: { label: 'Isi', required: true },
      }
    );

    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    try {
      if (isEdit && existingBerita) {
        const patch = {
          judul: judul.trim(),
          isi: isi.trim(),
        };
        if (pendingImageDataUrl) {
          patch.gambar = pendingImageDataUrl;
        }
        updateById('berita', editId, patch);
        setFlash('success', 'Berita berhasil diperbarui.');
      } else {
        const tanggalPost = user.role === 'admin' ? nowSqlDatetime() : null;
        insert('berita', {
          judul: judul.trim(),
          isi: isi.trim(),
          id_guru: user.id_guru || null,
          created_by: user.id_user,
          gambar: pendingImageDataUrl || null,
          tanggal_post: tanggalPost,
        });
        const msg =
          user.role === 'admin'
            ? 'Berita berhasil dipublish.'
            : 'Berita berhasil dikirim dan menunggu persetujuan Admin.';
        setFlash('success', msg);
      }

      window.location.href = 'dashboard.html';
    } catch (err) {
      if (err?.name === 'QuotaExceededError') {
        showError('Penyimpanan browser penuh. Coba gunakan gambar berukuran lebih kecil.');
      } else {
        showError('Terjadi kesalahan saat menyimpan berita.');
      }
    }
  });
});

