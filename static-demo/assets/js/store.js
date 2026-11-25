/**
 * Static Portfolio Demo — localStorage Data Store, Relational Lookups & Validators
 *
 * Replaces Laravel Eloquent models and server-side validation for the static demo.
 */

import { SEED_DATA, SEED_VERSION } from './seed.js';

const DB_STORAGE_KEY = 'rpl_portal_demo_db_v1';
const META_STORAGE_KEY = 'rpl_portal_demo_meta_v1';

export const PRIMARY_KEYS = {
  user: 'id_user',
  siswa: 'id_siswa',
  guru: 'id_guru',
  kelas: 'id_kelas',
  mapel: 'id_mapel',
  mapel_diampu: 'id_ampu',
  nilai: 'id_nilai',
  absensi: 'id_absensi',
  berita: 'id_berita',
  komentar_berita: 'id_komentar',
};

function deepClone(obj) {
  return JSON.parse(JSON.stringify(obj));
}

function assertTable(table) {
  if (!PRIMARY_KEYS[table]) {
    throw new Error(`Unknown table "${table}" in static store.`);
  }
}

export function initStore(forceReset = false) {
  if (!forceReset) {
    try {
      const raw = localStorage.getItem(DB_STORAGE_KEY);
      const metaRaw = localStorage.getItem(META_STORAGE_KEY);
      if (raw && metaRaw) {
        const meta = JSON.parse(metaRaw);
        const parsed = JSON.parse(raw);
        if (meta.version === SEED_VERSION && parsed && typeof parsed === 'object') {
          return parsed;
        }
      }
    } catch {
      // Fall through to re-seed if localStorage is corrupted
    }
  }

  const seeded = deepClone(SEED_DATA);
  localStorage.setItem(DB_STORAGE_KEY, JSON.stringify(seeded));
  localStorage.setItem(
    META_STORAGE_KEY,
    JSON.stringify({ version: SEED_VERSION, seededAt: new Date().toISOString() })
  );
  return seeded;
}

export function getDb() {
  return initStore(false);
}

export function saveDb(db) {
  localStorage.setItem(DB_STORAGE_KEY, JSON.stringify(db));
}

export function resetDemoData() {
  return initStore(true);
}

// ========================
// GENERIC CRUD UTILITIES
// ========================

export function getAll(table) {
  assertTable(table);
  const db = getDb();
  return deepClone(db[table] || []);
}

export function findById(table, id) {
  assertTable(table);
  const pk = PRIMARY_KEYS[table];
  const numericId = Number(id);
  const row = getDb()[table]?.find((item) => Number(item[pk]) === numericId) || null;
  return row ? deepClone(row) : null;
}

export function queryWhere(table, predicate) {
  return getAll(table).filter(predicate);
}

export function insert(table, data) {
  assertTable(table);
  const pk = PRIMARY_KEYS[table];
  const db = getDb();
  const list = db[table] || [];
  const maxId = list.reduce((max, item) => Math.max(max, Number(item[pk]) || 0), 0);
  const nextId = maxId + 1;

  const newRecord = { ...deepClone(data), [pk]: nextId };
  list.push(newRecord);
  db[table] = list;
  saveDb(db);
  return deepClone(newRecord);
}

export function updateById(table, id, patch) {
  assertTable(table);
  const pk = PRIMARY_KEYS[table];
  const numericId = Number(id);
  const db = getDb();
  const idx = db[table].findIndex((item) => Number(item[pk]) === numericId);
  if (idx === -1) return null;

  db[table][idx] = { ...db[table][idx], ...deepClone(patch), [pk]: numericId };
  saveDb(db);
  return deepClone(db[table][idx]);
}

export function upsert(table, matchPredicate, payload) {
  assertTable(table);
  const pk = PRIMARY_KEYS[table];
  const db = getDb();
  const idx = db[table].findIndex(matchPredicate);
  if (idx !== -1) {
    const existingId = db[table][idx][pk];
    db[table][idx] = { ...db[table][idx], ...deepClone(payload), [pk]: existingId };
    saveDb(db);
    return deepClone(db[table][idx]);
  }
  return insert(table, payload);
}

export function removeById(table, id) {
  assertTable(table);
  const pk = PRIMARY_KEYS[table];
  const numericId = Number(id);
  const db = getDb();
  const before = db[table].length;
  db[table] = db[table].filter((item) => Number(item[pk]) !== numericId);

  // Mirror controller/relational cleanup rules
  if (table === 'guru') {
    db.mapel_diampu = db.mapel_diampu.filter((a) => Number(a.id_guru) !== numericId);
    db.kelas = db.kelas.map((k) =>
      Number(k.id_wali) === numericId ? { ...k, id_wali: null } : k
    );
    db.berita = db.berita.map((b) =>
      Number(b.id_guru) === numericId ? { ...b, id_guru: null } : b
    );
    db.user = db.user.map((u) =>
      Number(u.id_guru) === numericId ? { ...u, id_guru: null } : u
    );
  } else if (table === 'berita') {
    db.komentar_berita = db.komentar_berita.filter(
      (c) => Number(c.id_berita) !== numericId
    );
  } else if (table === 'kelas') {
    db.siswa = db.siswa.map((s) =>
      Number(s.id_kelas) === numericId ? { ...s, id_kelas: null } : s
    );
    db.mapel_diampu = db.mapel_diampu.filter((a) => Number(a.id_kelas) !== numericId);
  } else if (table === 'mapel') {
    db.mapel_diampu = db.mapel_diampu.filter((a) => Number(a.id_mapel) !== numericId);
  } else if (table === 'siswa') {
    db.nilai = db.nilai.filter((n) => Number(n.id_siswa) !== numericId);
    db.absensi = db.absensi.filter((a) => Number(a.id_siswa) !== numericId);
    db.user = db.user.map((u) =>
      Number(u.id_siswa) === numericId ? { ...u, id_siswa: null } : u
    );
  }

  saveDb(db);
  return db[table].length < before;
}

export function removeWhere(table, predicate) {
  assertTable(table);
  const db = getDb();
  const before = db[table].length;
  db[table] = db[table].filter((item) => !predicate(item));
  saveDb(db);
  return before - db[table].length;
}

// ========================
// RELATIONAL LOOKUP HELPERS
// ========================

function indexBy(items, key) {
  const map = new Map();
  for (const item of items) {
    map.set(Number(item[key]), item);
  }
  return map;
}

export function getKelasWithWaliAll() {
  const db = getDb();
  const guruMap = indexBy(db.guru, 'id_guru');
  return db.kelas
    .map((k) => ({
      ...k,
      wali: k.id_wali ? guruMap.get(Number(k.id_wali)) || null : null,
    }))
    .sort((a, b) => a.tingkat.localeCompare(b.tingkat) || a.kelas.localeCompare(b.kelas));
}

export function getSiswaWithKelasAll() {
  const db = getDb();
  const kelasMap = indexBy(db.kelas, 'id_kelas');
  return db.siswa
    .map((s) => ({
      ...s,
      kelas: s.id_kelas ? kelasMap.get(Number(s.id_kelas)) || null : null,
    }))
    .sort((a, b) => a.nama.localeCompare(b.nama));
}

export function getGuruWithMapelDiampuAll() {
  const db = getDb();
  const mapelMap = indexBy(db.mapel, 'id_mapel');
  const kelasMap = indexBy(db.kelas, 'id_kelas');

  return db.guru
    .map((g) => {
      const ampu = db.mapel_diampu
        .filter((a) => Number(a.id_guru) === Number(g.id_guru))
        .map((a) => ({
          ...a,
          mapel: mapelMap.get(Number(a.id_mapel)) || null,
          kelas: kelasMap.get(Number(a.id_kelas)) || null,
        }))
        .filter((a) => a.mapel && a.kelas);
      return { ...g, mapelDiampu: ampu };
    })
    .sort((a, b) => a.nama.localeCompare(b.nama));
}

export function getMapelWithPengampuAll() {
  const db = getDb();
  const guruMap = indexBy(db.guru, 'id_guru');
  const kelasMap = indexBy(db.kelas, 'id_kelas');

  return db.mapel
    .map((m) => {
      const ampu = db.mapel_diampu
        .filter((a) => Number(a.id_mapel) === Number(m.id_mapel))
        .map((a) => ({
          ...a,
          guru: guruMap.get(Number(a.id_guru)) || null,
          kelas: kelasMap.get(Number(a.id_kelas)) || null,
        }));
      return { ...m, mapelDiampu: ampu };
    })
    .sort((a, b) => a.nama_mapel.localeCompare(b.nama_mapel));
}

export function getUsersWithRelationsAll() {
  const db = getDb();
  const siswaMap = indexBy(db.siswa, 'id_siswa');
  const guruMap = indexBy(db.guru, 'id_guru');

  return db.user
    .map((u) => {
      const { password: _hidden, ...safeUser } = u;
      return {
        ...safeUser,
        siswa: u.id_siswa ? siswaMap.get(Number(u.id_siswa)) || null : null,
        guru: u.id_guru ? guruMap.get(Number(u.id_guru)) || null : null,
      };
    })
    .sort((a, b) => a.username.localeCompare(b.username));
}

export function getBeritaWithAuthorAll() {
  const db = getDb();
  const userMap = indexBy(db.user, 'id_user');
  const guruMap = indexBy(db.guru, 'id_guru');

  return db.berita.map((b) => {
    const rawUser = b.created_by ? userMap.get(Number(b.created_by)) || null : null;
    const safeUser = rawUser
      ? { id_user: rawUser.id_user, username: rawUser.username, role: rawUser.role }
      : null;
    return {
      ...b,
      user: safeUser,
      guru: b.id_guru ? guruMap.get(Number(b.id_guru)) || null : null,
    };
  });
}

export function getKomentarByBerita(idBerita) {
  const db = getDb();
  const userMap = indexBy(db.user, 'id_user');
  return db.komentar_berita
    .filter((c) => Number(c.id_berita) === Number(idBerita))
    .map((c) => {
      const rawUser = c.id_user ? userMap.get(Number(c.id_user)) || null : null;
      return {
        ...c,
        user: rawUser
          ? { id_user: rawUser.id_user, username: rawUser.username, role: rawUser.role }
          : null,
      };
    })
    .sort((a, b) => String(b.tanggal_post).localeCompare(String(a.tanggal_post)));
}

export function getNilaiForSiswa(idSiswa) {
  const db = getDb();
  const mapelMap = indexBy(db.mapel, 'id_mapel');
  const guruMap = indexBy(db.guru, 'id_guru');
  const kelasMap = indexBy(db.kelas, 'id_kelas');
  const ampuMap = indexBy(db.mapel_diampu, 'id_ampu');

  return db.nilai
    .filter((n) => Number(n.id_siswa) === Number(idSiswa))
    .map((n) => {
      const ampu = ampuMap.get(Number(n.id_ampu)) || null;
      return {
        ...n,
        ampu: ampu
          ? {
              ...ampu,
              mapel: mapelMap.get(Number(ampu.id_mapel)) || null,
              guru: guruMap.get(Number(ampu.id_guru)) || null,
              kelas: kelasMap.get(Number(ampu.id_kelas)) || null,
            }
          : null,
      };
    });
}

export function getMapelDiampuForKelas(idKelas) {
  const db = getDb();
  const mapelMap = indexBy(db.mapel, 'id_mapel');
  const guruMap = indexBy(db.guru, 'id_guru');
  const kelasMap = indexBy(db.kelas, 'id_kelas');

  return db.mapel_diampu
    .filter((a) => Number(a.id_kelas) === Number(idKelas))
    .map((a) => ({
      ...a,
      mapel: mapelMap.get(Number(a.id_mapel)) || null,
      guru: guruMap.get(Number(a.id_guru)) || null,
      kelas: kelasMap.get(Number(a.id_kelas)) || null,
    }))
    .sort((a, b) =>
      String(a.mapel?.nama_mapel || '').localeCompare(String(b.mapel?.nama_mapel || ''))
    );
}

// ========================
// VALIDATION UTILITIES
// ========================

/**
 * Validates a data object against field rules.
 * Supported rule properties per field:
 * - label: display label
 * - required: boolean
 * - numeric: boolean (digits or numeric string depending on integerOnly)
 * - integerOnly: boolean (digits only)
 * - digits: number (exact digit length when non-empty)
 * - digitsBetween: [min, max]
 * - minLength: number
 * - maxLength: number
 * - min: number (numeric value)
 * - max: number (numeric value)
 * - email: boolean
 * - in: string[]
 * - unique: { table, field, ignoreId? }
 * - message: optional custom messages map
 */
export function validateFields(data, schema) {
  const errors = [];
  const db = getDb();

  for (const [field, rule] of Object.entries(schema)) {
    const raw = data[field];
    const value = raw === undefined || raw === null ? '' : String(raw).trim();
    const label = rule.label || field;

    if (rule.required && value === '') {
      errors.push(rule.messages?.required || `${label} wajib diisi.`);
      continue;
    }

    if (value === '') continue;

    if (rule.integerOnly && !/^\d+$/.test(value)) {
      errors.push(rule.messages?.numeric || `${label} harus berupa angka.`);
    } else if (rule.numeric && Number.isNaN(Number(value))) {
      errors.push(rule.messages?.numeric || `${label} harus berupa angka.`);
    }

    if (rule.digits && value.length !== rule.digits) {
      errors.push(
        rule.messages?.digits || `${label} harus berjumlah tepat ${rule.digits} digit.`
      );
    }

    if (rule.digitsBetween) {
      const [minD, maxD] = rule.digitsBetween;
      if (value.length < minD || value.length > maxD) {
        errors.push(
          rule.messages?.digitsBetween ||
            `${label} harus berjumlah antara ${minD} sampai ${maxD} digit.`
        );
      }
    }

    if (rule.minLength && value.length < rule.minLength) {
      errors.push(rule.messages?.minLength || `${label} minimal ${rule.minLength} karakter.`);
    }

    if (rule.maxLength && value.length > rule.maxLength) {
      errors.push(rule.messages?.maxLength || `${label} maksimal ${rule.maxLength} karakter.`);
    }

    if (rule.min !== undefined && Number(value) < rule.min) {
      errors.push(rule.messages?.min || `${label} minimal ${rule.min}.`);
    }

    if (rule.max !== undefined && Number(value) > rule.max) {
      errors.push(rule.messages?.max || `${label} maksimal ${rule.max}.`);
    }

    if (rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      errors.push(
        rule.messages?.email || `Format ${label.toLowerCase()} tidak valid (contoh: nama@gmail.com).`
      );
    }

    if (rule.in && !rule.in.includes(value)) {
      errors.push(rule.messages?.in || `${label} yang dipilih tidak valid.`);
    }

    if (rule.unique) {
      const { table, field: col = field, ignoreId = null } = rule.unique;
      const pk = PRIMARY_KEYS[table];
      const duplicate = (db[table] || []).some((row) => {
        if (ignoreId !== null && Number(row[pk]) === Number(ignoreId)) return false;
        return String(row[col] ?? '').toLowerCase() === value.toLowerCase();
      });
      if (duplicate) {
        errors.push(rule.messages?.unique || `${label} ini sudah terdaftar.`);
      }
    }
  }

  return { valid: errors.length === 0, errors };
}

