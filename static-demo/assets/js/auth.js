/**
 * Static Portfolio Demo — Simulated Authentication & Session Helpers
 *
 * IMPORTANT SECURITY NOTICE:
 * This module implements SIMULATED client-side authentication using localStorage
 * and sessionStorage strictly for a static frontend portfolio demo.
 * It provides NO real authentication, password hashing, or server-side security.
 */

import { getAll, findById, getDb } from './store.js';

const SESSION_KEY = 'rpl_portal_demo_session_v1';
const FLASH_KEY = 'rpl_portal_demo_flash_v1';
const INTENDED_KEY = 'rpl_portal_demo_intended_v1';

/**
 * Returns non-sensitive demo account metadata derived from the current store
 * (plus demo login helper hints) without duplicating seed data.
 */
export function getDemoAccounts() {
  const db = getDb();
  const kelasByWali = new Map(
    db.kelas.filter((k) => k.id_wali).map((k) => [Number(k.id_wali), k])
  );
  const guruMap = new Map(db.guru.map((g) => [Number(g.id_guru), g]));
  const siswaMap = new Map(db.siswa.map((s) => [Number(s.id_siswa), s]));

  return db.user.map((u) => {
    let badge = 'Admin';
    let detail = 'Akses penuh seluruh modul';
    if (u.role === 'guru') {
      const guru = guruMap.get(Number(u.id_guru));
      const waliKelas = kelasByWali.get(Number(u.id_guru));
      badge = waliKelas ? `Guru (Wali ${waliKelas.tingkat} ${waliKelas.kelas})` : 'Guru (Non-Wali)';
      detail = guru ? guru.nama : 'Guru';
    } else if (u.role === 'siswa') {
      const siswa = siswaMap.get(Number(u.id_siswa));
      badge = 'Siswa';
      detail = siswa ? siswa.nama : 'Siswa';
    }

    return {
      username: u.username,
      password: u.password,
      role: u.role,
      badge,
      detail,
    };
  });
}

export function getSession() {
  try {
    const raw = localStorage.getItem(SESSION_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

/**
 * Returns the currently logged-in user (without password), enriched with
 * linked `guru` or `siswa` (including `kelas`) records, or null if guest.
 */
export function getCurrentUser() {
  const session = getSession();
  if (!session?.id_user) return null;

  const user = findById('user', session.id_user);
  if (!user) {
    localStorage.removeItem(SESSION_KEY);
    return null;
  }

  const { password: _hidden, ...safeUser } = user;
  const guru = user.id_guru ? findById('guru', user.id_guru) : null;
  let siswa = user.id_siswa ? findById('siswa', user.id_siswa) : null;
  if (siswa && siswa.id_kelas) {
    siswa = { ...siswa, kelas: findById('kelas', siswa.id_kelas) };
  }

  return {
    ...safeUser,
    guru,
    siswa,
  };
}

export function isAuthenticated() {
  return getCurrentUser() !== null;
}

export function login(username, password) {
  const cleanUser = String(username || '').trim();
  const cleanPass = String(password || '');

  if (!cleanUser) {
    return { ok: false, error: 'Username wajib diisi.' };
  }
  if (!cleanPass) {
    return { ok: false, error: 'Password wajib diisi.' };
  }

  const users = getAll('user');
  const match = users.find(
    (u) => u.username === cleanUser && u.password === cleanPass
  );

  if (!match) {
    return { ok: false, error: 'Username atau password yang Anda masukkan salah.' };
  }

  localStorage.setItem(
    SESSION_KEY,
    JSON.stringify({ id_user: match.id_user, loggedInAt: new Date().toISOString() })
  );

  return { ok: true, user: getCurrentUser() };
}

export function logout(redirectTo = 'login.html') {
  localStorage.removeItem(SESSION_KEY);
  sessionStorage.removeItem(INTENDED_KEY);
  if (redirectTo) {
    window.location.href = redirectTo;
  }
}

export function getIntendedUrl(fallback = 'dashboard.html') {
  const url = sessionStorage.getItem(INTENDED_KEY);
  sessionStorage.removeItem(INTENDED_KEY);
  return url || fallback;
}

export function requireAuth(redirectTo = 'login.html') {
  const user = getCurrentUser();
  if (!user) {
    sessionStorage.setItem(INTENDED_KEY, window.location.pathname.split('/').pop() + window.location.search);
    setFlash('error', 'Silakan login terlebih dahulu.');
    window.location.href = redirectTo;
    return null;
  }
  return user;
}

export function requireRole(allowedRoles, redirectTo = 'dashboard.html') {
  const user = requireAuth();
  if (!user) return null;

  const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];
  if (!roles.includes(user.role)) {
    setFlash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    window.location.href = redirectTo;
    return null;
  }
  return user;
}

// ========================
// FLASH MESSAGE HELPERS
// ========================

export function setFlash(type, message) {
  sessionStorage.setItem(FLASH_KEY, JSON.stringify({ type, message }));
}

export function consumeFlash() {
  try {
    const raw = sessionStorage.getItem(FLASH_KEY);
    if (!raw) return null;
    sessionStorage.removeItem(FLASH_KEY);
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

