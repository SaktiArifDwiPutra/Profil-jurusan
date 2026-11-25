/**
 * Static Portfolio Demo — Shared Layout, Navigation, Footer & SweetAlert2 Utilities
 *
 * Mirrors resources/views/layouts/app.blade.php, partials/header.blade.php,
 * partials/footer.blade.php, and shared SweetAlert2 confirm/flash dialogs.
 */

import { getCurrentUser, logout, consumeFlash, setFlash } from './auth.js';
import { resetDemoData } from './store.js';

export function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

export function nl2br(str) {
  return escapeHtml(str).replace(/\r?\n/g, '<br>');
}

export function showSuccess(message, timer = 3000) {
  if (!window.Swal) return alert(message);
  return window.Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: message,
    timer,
    showConfirmButton: false,
  });
}

export function showError(message, title = 'Gagal!') {
  if (!window.Swal) return alert(message);
  return window.Swal.fire({
    icon: 'error',
    title,
    text: message,
  });
}

export function showValidationErrors(errors) {
  const list = Array.isArray(errors) ? errors : [String(errors)];
  const html = `<ul style="text-align: left; margin-left: 1rem;">${list
    .map((err) => `<li style="margin-bottom: 5px;">${escapeHtml(err)}</li>`)
    .join('')}</ul>`;

  if (!window.Swal) return alert(list.join('\n'));
  return window.Swal.fire({
    icon: 'error',
    title: 'Gagal Menyimpan Data!',
    html,
    confirmButtonText: 'OK, Saya Perbaiki',
    confirmButtonColor: '#d33',
    width: '600px',
  });
}

export async function confirmAction(
  message,
  { confirmText = 'Ya, Lanjutkan!', icon = 'warning', title = 'Konfirmasi' } = {}
) {
  if (!window.Swal) return window.confirm(message);
  const result = await window.Swal.fire({
    title,
    text: message,
    icon,
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: confirmText,
    cancelButtonText: 'Batal',
  });
  return Boolean(result.isConfirmed);
}

export function flushFlashMessages() {
  const flash = consumeFlash();
  if (!flash) return;
  if (flash.type === 'success') {
    showSuccess(flash.message);
  } else if (flash.type === 'error') {
    showError(flash.message);
  } else if (window.Swal) {
    window.Swal.fire({
      icon: flash.type || 'info',
      title: flash.type === 'warning' ? 'Perhatian' : 'Info',
      text: flash.message,
    });
  }
}

function buildGuestNav(activeNav) {
  return `
    <li class="nav-item">
      <a class="nav-link ${activeNav === 'home' ? 'active' : ''}" href="index.html">
        <i class="fa fa-home"></i> Beranda
      </a>
    </li>
    <li class="nav-item"><a class="nav-link" href="login.html">Siswa</a></li>
    <li class="nav-item"><a class="nav-link" href="login.html">Guru</a></li>
    <li class="nav-item"><a class="nav-link" href="login.html">Kelas</a></li>
    <li class="nav-item"><a class="nav-link" href="login.html">Mapel</a></li>
    <li class="nav-item"><a class="nav-link" href="login.html">Nilai</a></li>
    <li class="nav-item ms-2">
      <a class="btn btn-primary btn-sm px-4 rounded-pill" href="login.html">Login</a>
    </li>
  `;
}

function buildAuthNav(user, activeNav) {
  const navClass = (key) =>
    `nav-link ${activeNav === key ? 'active text-primary fw-bold' : ''}`;

  const akunItem =
    user.role === 'admin'
      ? `<li class="nav-item"><a class="${navClass('akun')}" href="akun.html">Akun</a></li>`
      : '';

  return `
    <li class="nav-item">
      <a class="${navClass('dashboard')}" href="dashboard.html">
        <i class="fa fa-home"></i> Dashboard
      </a>
    </li>
    <li class="nav-item"><a class="${navClass('siswa')}" href="siswa.html">Siswa</a></li>
    <li class="nav-item"><a class="${navClass('guru')}" href="guru.html">Guru</a></li>
    <li class="nav-item"><a class="${navClass('kelas')}" href="kelas.html">Kelas</a></li>
    <li class="nav-item"><a class="${navClass('mapel')}" href="mapel.html">Mapel</a></li>
    <li class="nav-item"><a class="${navClass('nilai')}" href="nilai.html">Nilai</a></li>
    <li class="nav-item"><a class="${navClass('absensi')}" href="absensi.html">Absen</a></li>
    ${akunItem}
    <li class="nav-item ms-3">
      <button type="button" class="btn btn-outline-danger btn-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#logoutModal">
        Logout
      </button>
    </li>
  `;
}

function buildLogoutModal() {
  return `
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
          <div class="modal-header border-bottom-0 pb-0 justify-content-center mt-3">
            <h5 class="fw-bold m-0 text-dark" id="logoutModalLabel">Konfirmasi Logout</h5>
          </div>
          <div class="modal-body text-center py-4 px-4">
            <div class="mb-3 text-danger">
              <i class="fa fa-sign-out-alt" style="font-size: 3rem;"></i>
            </div>
            <p class="mb-0 text-muted">Apakah Anda yakin ingin keluar dari sistem?</p>
          </div>
          <div class="modal-footer border-top-0 pt-0 justify-content-center pb-4" style="padding-bottom: 1.5rem;">
            <button type="button" class="btn btn-light px-4 rounded-pill border" data-bs-dismiss="modal">Batal</button>
            <button type="button" id="btnConfirmLogout" class="btn btn-danger px-4 rounded-pill shadow-sm">Ya, Keluar</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

/**
 * Mounts shared navbar, optional logout modal, footer, and displays pending flash alerts.
 * Expects optional mount targets `#app-header` and `#app-footer` in the HTML shell.
 */
export function renderLayout({ activeNav = '', title = '' } = {}) {
  if (title) {
    document.title = title;
  }

  const user = getCurrentUser();
  const headerMount = document.getElementById('app-header');
  if (headerMount) {
    headerMount.innerHTML = `
      <nav class="navbar navbar-expand-lg sticky-top bg-white shadow-sm">
        <div class="container">
          <img src="assets/logo/logorilll2.png" alt="logo" style="height:75px; margin-right:8px;">
          <span class="navbar-brand fw-bold text-primary mt-2">ProfilJurusan</span>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-center">
              ${user ? buildAuthNav(user, activeNav) : buildGuestNav(activeNav)}
            </ul>
          </div>
        </div>
      </nav>
      ${user ? buildLogoutModal() : ''}
    `;

    const logoutBtn = document.getElementById('btnConfirmLogout');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', () => {
        setFlash('success', 'Anda telah keluar dari sesi demo.');
        logout('login.html');
      });
    }
  }

  const footerMount = document.getElementById('app-footer');
  if (footerMount) {
    footerMount.innerHTML = `
      <footer class="footer mt-auto py-3 bg-white text-center shadow-sm border-top">
        <div class="container d-flex flex-column flex-sm-row justify-content-center align-items-center gap-2">
          <small class="text-muted">&copy; 2026 Dibuat oleh ❤️ Sakti Arif XI PPLG B</small>
          <span class="text-muted d-none d-sm-inline">&middot;</span>
          <button type="button" id="btnResetDemoData" class="btn btn-link btn-sm p-0 text-muted text-decoration-none" style="font-size: 0.8rem;">
            <i class="fa fa-rotate-left me-1"></i>Reset Demo Data
          </button>
        </div>
      </footer>
    `;

    const resetBtn = document.getElementById('btnResetDemoData');
    if (resetBtn) {
      resetBtn.addEventListener('click', async () => {
        const confirmed = await confirmAction(
          'Kembalikan semua data demo ke kondisi awal?',
          { confirmText: 'Ya, Reset Data!', icon: 'question' }
        );
        if (confirmed) {
          resetDemoData();
          setFlash('success', 'Data demo berhasil dikembalikan ke kondisi awal.');
          window.location.reload();
        }
      });
    }
  }

  flushFlashMessages();
}

