/**
 * Static Portfolio Demo — Shared Berita Helpers
 */

export function resolveBeritaImageSrc(gambar) {
  if (!gambar) return '';
  if (gambar.startsWith('data:') || gambar.startsWith('http')) return gambar;
  return `uploads/${encodeURIComponent(gambar)}`;
}

export function formatBeritaAuthor(b) {
  return b.user?.username || b.guru?.nama || 'Admin';
}

export function nowSqlDatetime() {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return (
    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ` +
    `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
  );
}

const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

export function formatShortDate(dateStr) {
  if (!dateStr) return '';
  const parsed = new Date(String(dateStr).replace(' ', 'T'));
  if (Number.isNaN(parsed.getTime())) return String(dateStr);
  const day = String(parsed.getDate()).padStart(2, '0');
  const mon = MONTHS_SHORT[parsed.getMonth()];
  const year = parsed.getFullYear();
  return `${day} ${mon} ${year}`;
}

export function readImageFileAsDataUrl(file, maxBytes = 2 * 1024 * 1024) {
  return new Promise((resolve, reject) => {
    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowed.includes(file.type)) {
      reject(new Error('Format gambar harus jpeg, png, jpg, gif, atau webp.'));
      return;
    }
    if (file.size > maxBytes) {
      reject(new Error('Ukuran gambar maksimal 2 MB.'));
      return;
    }
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result));
    reader.onerror = () => reject(new Error('Gagal membaca file gambar.'));
    reader.readAsDataURL(file);
  });
}

