/**
 * Static Portfolio Demo — Data Akun Controller (Admin Only)
 *
 * Mirrors app/Http/Controllers/Utama/AkunController.php,
 * app/Http/Controllers/Export/AkunController.php, and resources/views/admin/akun/index.blade.php
 */

import {
  getAll,
  getUsersWithRelationsAll,
  insert,
  removeById,
  validateFields,
} from '../store.js';
import { requireRole } from '../auth.js';
import {
  renderLayout,
  confirmAction,
  showSuccess,
  showError,
  showValidationErrors,
  escapeHtml,
} from '../layout.js';
import { exportCsv, exportPrintReport } from '../export.js';

let currentUser = null;

function populateLinkSelects() {
  const siswas = [...getAll('siswa')].sort((a, b) => a.nama.localeCompare(b.nama));
  const gurus = [...getAll('guru')].sort((a, b) => a.nama.localeCompare(b.nama));

  document.getElementById('add-id-siswa').innerHTML =
    '<option value="">Pilih Siswa</option>' +
    siswas.map((s) => `<option value="${s.id_siswa}">${escapeHtml(s.nama)}</option>`).join('');

  document.getElementById('add-id-guru').innerHTML =
    '<option value="">Pilih Guru</option>' +
    gurus.map((g) => `<option value="${g.id_guru}">${escapeHtml(g.nama)}</option>`).join('');
}

function applySearch() {
  const filter = (document.getElementById('searchInput')?.value || '').toLowerCase();
  document.querySelectorAll('#akunTable tbody tr').forEach((tr) => {
    tr.style.display = tr.innerText.toLowerCase().includes(filter) ? '' : 'none';
  });
}

function renderAkunTable() {
  const users = getUsersWithRelationsAll();
  const tbody = document.getElementById('akunTableBody');

  tbody.innerHTML = users
    .map((u, i) => {
      const badgeColor = u.role === 'admin' ? 'danger' : u.role === 'guru' ? 'primary' : 'success';
      const roleCap = u.role.charAt(0).toUpperCase() + u.role.slice(1);
      const linked = u.siswa?.nama || u.guru?.nama || '-';

      return `
        <tr>
          <td>${i + 1}</td>
          <td>${escapeHtml(u.username)}</td>
          <td><span class="badge rounded-pill text-bg-${badgeColor}">${escapeHtml(roleCap)}</span></td>
          <td>${escapeHtml(linked)}</td>
          <td>
            <button type="button" class="btn btn-sm btn-danger" data-del-user="${u.id_user}">Hapus</button>
          </td>
        </tr>
      `;
    })
    .join('');

  tbody.querySelectorAll('[data-del-user]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = Number(btn.dataset.delUser);
      if (id === Number(currentUser.id_user)) {
        showError('Tidak dapat menghapus akun yang sedang login.');
        return;
      }

      const ok = await confirmAction('Yakin hapus akun ini?', { icon: 'warning' });
      if (!ok) return;

      removeById('user', id);
      renderAkunTable();
      showSuccess('Akun berhasil dihapus');
    });
  });

  applySearch();
}

document.addEventListener('DOMContentLoaded', () => {
  currentUser = requireRole('admin', 'dashboard.html');
  if (!currentUser) return;

  renderLayout({ activeNav: 'akun', title: 'Data Akun - Profil Jurusan RPL' });
  populateLinkSelects();
  renderAkunTable();

  const usernameEl = document.getElementById('add-username');
  usernameEl?.addEventListener('input', () => {
    usernameEl.value = usernameEl.value.replace(/\s/g, '');
  });

  const roleSelect = document.getElementById('roleSelect');
  const siswaDiv = document.getElementById('siswaSelect');
  const guruDiv = document.getElementById('guruSelect');

  function syncRoleSelect() {
    const role = roleSelect.value;
    siswaDiv.classList.add('d-none');
    guruDiv.classList.add('d-none');
    document.getElementById('add-id-siswa').required = false;
    document.getElementById('add-id-guru').required = false;

    if (role === 'siswa') {
      siswaDiv.classList.remove('d-none');
      document.getElementById('add-id-siswa').required = true;
    } else if (role === 'guru') {
      guruDiv.classList.remove('d-none');
      document.getElementById('add-id-guru').required = true;
    }
  }

  roleSelect?.addEventListener('change', syncRoleSelect);
  document.getElementById('searchInput')?.addEventListener('input', applySearch);

  document.getElementById('formTambahAkun')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const role = roleSelect.value;
    const idSiswa = document.getElementById('add-id-siswa').value;
    const idGuru = document.getElementById('add-id-guru').value;

    const payload = {
      username: usernameEl.value.trim(),
      password: document.getElementById('add-password').value,
      role,
    };

    const rules = {
      username: {
        label: 'Username',
        required: true,
        maxLength: 50,
        unique: { table: 'user', field: 'username' },
        messages: {
          unique: 'Username ini sudah digunakan, silakan pilih username lain.',
        },
      },
      password: { label: 'Password', required: true, minLength: 6 },
      role: { label: 'Role', required: true, in: ['admin', 'guru', 'siswa'] },
    };

    const check = validateFields(payload, rules);
    if (role === 'siswa' && !idSiswa) {
      check.valid = false;
      check.errors.push('Silakan pilih Siswa untuk role Siswa.');
    }
    if (role === 'guru' && !idGuru) {
      check.valid = false;
      check.errors.push('Silakan pilih Guru untuk role Guru.');
    }

    if (!check.valid) {
      showValidationErrors(check.errors);
      return;
    }

    const ok = await confirmAction('Buat akun baru?', { icon: 'question' });
    if (!ok) return;

    insert('user', {
      username: payload.username,
      password: payload.password,
      role: payload.role,
      id_siswa: role === 'siswa' ? Number(idSiswa) : null,
      id_guru: role === 'guru' ? Number(idGuru) : null,
    });

    e.target.reset();
    syncRoleSelect();
    window.bootstrap.Collapse.getOrCreateInstance(document.getElementById('formAdd')).hide();
    renderAkunTable();
    showSuccess('Akun berhasil dibuat');
  });

  function getLinkedLabel(u) {
    if (u.role === 'siswa' && u.siswa) return `Siswa: ${u.siswa.nama}`;
    if (u.role === 'guru' && u.guru) return `Guru: ${u.guru.nama}`;
    if (u.role === 'admin') return 'Admin';
    return '-';
  }

  document.getElementById('btnExportCsv')?.addEventListener('click', () => {
    const rows = getUsersWithRelationsAll().map((u, i) => [
      i + 1,
      u.username,
      u.role,
      getLinkedLabel(u),
    ]);
    exportCsv('data_akun.csv', ['No', 'Username', 'Role', 'Linked To'], rows);
  });

  document.getElementById('btnExportPdfAll')?.addEventListener('click', () => {
    const rows = getUsersWithRelationsAll().map((u, i) => [
      i + 1,
      u.username,
      u.role,
      getLinkedLabel(u),
    ]);
    exportPrintReport({
      title: 'Data Akun Pengguna',
      headers: ['No', 'Username', 'Role', 'Linked To'],
      rows,
    });
  });
});

