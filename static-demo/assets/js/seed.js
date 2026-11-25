/**
 * Static Portfolio Demo — Seed Data
 *
 * Schema and foreign keys mirror the 10 Laravel Eloquent models in app/Models/:
 * - user (PK: id_user)
 * - guru (PK: id_guru)
 * - kelas (PK: id_kelas)
 * - siswa (PK: id_siswa)
 * - mapel (PK: id_mapel)
 * - mapel_diampu (PK: id_ampu)
 * - nilai (PK: id_nilai)
 * - absensi (PK: id_absensi)
 * - berita (PK: id_berita)
 * - komentar_berita (PK: id_komentar)
 *
 * All records and credentials are strictly fictional demo data.
 */

export const SEED_VERSION = '1.0.0';

function daysAgo(offsetDays) {
  const d = new Date();
  d.setDate(d.getDate() - offsetDays);
  return d.toISOString().slice(0, 10);
}

export const SEED_DATA = {
  guru: [
    {
      id_guru: 1,
      nip: '198504122010011001',
      nama: 'Budi Santoso, S.Kom',
      jenis_kelamin: 'Laki-laki',
      email: 'budi.santoso@demo-smk.sch.id',
      no_hp: '081234500101',
    },
    {
      id_guru: 2,
      nip: '198809232012122002',
      nama: 'Siti Aminah, S.Pd',
      jenis_kelamin: 'Perempuan',
      email: 'siti.aminah@demo-smk.sch.id',
      no_hp: '081234500102',
    },
    {
      id_guru: 3,
      nip: '199001152015031003',
      nama: 'Rizky Pratama, S.T',
      jenis_kelamin: 'Laki-laki',
      email: 'rizky.pratama@demo-smk.sch.id',
      no_hp: '081234500103',
    },
    {
      id_guru: 4,
      nip: '198706302014022004',
      nama: 'Dewi Lestari, M.Kom',
      jenis_kelamin: 'Perempuan',
      email: 'dewi.lestari@demo-smk.sch.id',
      no_hp: '081234500104',
    },
    {
      id_guru: 5,
      nip: '199211082019031005',
      nama: 'Hendra Wijaya, S.Pd',
      jenis_kelamin: 'Laki-laki',
      email: 'hendra.wijaya@demo-smk.sch.id',
      no_hp: '081234500105',
    },
    {
      id_guru: 6,
      nip: '199403192020122006',
      nama: 'Nadia Putri, S.Kom',
      jenis_kelamin: 'Perempuan',
      email: 'nadia.putri@demo-smk.sch.id',
      no_hp: '081234500106',
    },
  ],

  kelas: [
    { id_kelas: 1, tingkat: 'X', kelas: 'PPLG A', id_wali: 3 },
    { id_kelas: 2, tingkat: 'X', kelas: 'PPLG B', id_wali: 4 },
    { id_kelas: 3, tingkat: 'XI', kelas: 'PPLG A', id_wali: 5 },
    { id_kelas: 4, tingkat: 'XI', kelas: 'PPLG B', id_wali: 1 },
    { id_kelas: 5, tingkat: 'XII', kelas: 'RPL A', id_wali: 6 },
    { id_kelas: 6, tingkat: 'XII', kelas: 'RPL B', id_wali: null },
  ],

  siswa: [
    {
      id_siswa: 1,
      id_kelas: 4,
      nisn: '0081234501',
      nama: 'Andi Saputra',
      jenis_kelamin: 'Laki-laki',
      tanggal_lahir: '2008-05-14',
      alamat: 'Jl. Merdeka No. 12, Bandung',
      no_hp: '085611100001',
    },
    {
      id_siswa: 2,
      id_kelas: 4,
      nisn: '0081234502',
      nama: 'Bunga Citra',
      jenis_kelamin: 'Perempuan',
      tanggal_lahir: '2008-08-21',
      alamat: 'Jl. Anggrek No. 45, Bandung',
      no_hp: '085611100002',
    },
    {
      id_siswa: 3,
      id_kelas: 4,
      nisn: '0081234503',
      nama: 'Cahyo Nugroho',
      jenis_kelamin: 'Laki-laki',
      tanggal_lahir: '2008-02-09',
      alamat: 'Jl. Cempaka No. 8, Cimahi',
      no_hp: '085611100003',
    },
    {
      id_siswa: 4,
      id_kelas: 4,
      nisn: '0081234504',
      nama: 'Dina Maharani',
      jenis_kelamin: 'Perempuan',
      tanggal_lahir: '2008-11-30',
      alamat: 'Jl. Melati No. 19, Bandung',
      no_hp: '085611100004',
    },
    {
      id_siswa: 5,
      id_kelas: 3,
      nisn: '0081234505',
      nama: 'Eko Prasetyo',
      jenis_kelamin: 'Laki-laki',
      tanggal_lahir: '2008-04-03',
      alamat: 'Jl. Kenanga No. 27, Bandung',
      no_hp: '085611100005',
    },
    {
      id_siswa: 6,
      id_kelas: 3,
      nisn: '0081234506',
      nama: 'Fani Rahmawati',
      jenis_kelamin: 'Perempuan',
      tanggal_lahir: '2008-09-17',
      alamat: 'Jl. Flamboyan No. 3, Bandung',
      no_hp: '085611100006',
    },
    {
      id_siswa: 7,
      id_kelas: 1,
      nisn: '0091234507',
      nama: 'Gilang Ramadhan',
      jenis_kelamin: 'Laki-laki',
      tanggal_lahir: '2009-01-25',
      alamat: 'Jl. Teratai No. 14, Cimahi',
      no_hp: '085611100007',
    },
    {
      id_siswa: 8,
      id_kelas: 2,
      nisn: '0091234508',
      nama: 'Hana Kartika',
      jenis_kelamin: 'Perempuan',
      tanggal_lahir: '2009-06-11',
      alamat: 'Jl. Dahlia No. 5, Bandung',
      no_hp: '085611100008',
    },
    {
      id_siswa: 9,
      id_kelas: 5,
      nisn: '0071234509',
      nama: 'Ilham Maulana',
      jenis_kelamin: 'Laki-laki',
      tanggal_lahir: '2007-12-02',
      alamat: 'Jl. Mawar No. 33, Bandung',
      no_hp: '085611100009',
    },
    {
      id_siswa: 10,
      id_kelas: 6,
      nisn: '0071234510',
      nama: 'Jihan Aulia',
      jenis_kelamin: 'Perempuan',
      tanggal_lahir: '2007-07-19',
      alamat: 'Jl. Kamboja No. 22, Bandung',
      no_hp: '085611100010',
    },
  ],

  mapel: [
    { id_mapel: 1, nama_mapel: 'Pemrograman Web', kategori: 'Produktif' },
    { id_mapel: 2, nama_mapel: 'Pemrograman Mobile', kategori: 'Produktif' },
    { id_mapel: 3, nama_mapel: 'Basis Data', kategori: 'Produktif' },
    { id_mapel: 4, nama_mapel: 'Pemrograman Berorientasi Objek', kategori: 'Produktif' },
    { id_mapel: 5, nama_mapel: 'Pengembangan Gim', kategori: 'Produktif' },
    { id_mapel: 6, nama_mapel: 'Matematika', kategori: 'Umum' },
    { id_mapel: 7, nama_mapel: 'Bahasa Inggris', kategori: 'Umum' },
    { id_mapel: 8, nama_mapel: 'Pendidikan Pancasila', kategori: 'Umum' },
  ],

  mapel_diampu: [
    { id_ampu: 1, id_guru: 1, id_mapel: 1, id_kelas: 4 },
    { id_ampu: 2, id_guru: 4, id_mapel: 3, id_kelas: 4 },
    { id_ampu: 3, id_guru: 2, id_mapel: 7, id_kelas: 4 },
    { id_ampu: 4, id_guru: 5, id_mapel: 6, id_kelas: 4 },
    { id_ampu: 5, id_guru: 1, id_mapel: 2, id_kelas: 4 },
    { id_ampu: 6, id_guru: 3, id_mapel: 1, id_kelas: 1 },
    { id_ampu: 7, id_guru: 4, id_mapel: 3, id_kelas: 2 },
    { id_ampu: 8, id_guru: 1, id_mapel: 4, id_kelas: 3 },
    { id_ampu: 9, id_guru: 2, id_mapel: 7, id_kelas: 3 },
    { id_ampu: 10, id_guru: 6, id_mapel: 5, id_kelas: 5 },
    { id_ampu: 11, id_guru: 5, id_mapel: 6, id_kelas: 5 },
    { id_ampu: 12, id_guru: 6, id_mapel: 2, id_kelas: 6 },
  ],

  user: [
    {
      id_user: 1,
      username: 'admin',
      password: 'demo-admin-password',
      role: 'admin',
      id_guru: null,
      id_siswa: null,
    },
    {
      id_user: 2,
      username: 'guru_wali',
      password: 'demo-guru-password',
      role: 'guru',
      id_guru: 1,
      id_siswa: null,
    },
    {
      id_user: 3,
      username: 'guru_mapel',
      password: 'demo-guru-password',
      role: 'guru',
      id_guru: 2,
      id_siswa: null,
    },
    {
      id_user: 4,
      username: 'siswa_demo',
      password: 'demo-siswa-password',
      role: 'siswa',
      id_guru: null,
      id_siswa: 1,
    },
    {
      id_user: 5,
      username: 'siswa_bunga',
      password: 'demo-siswa-password',
      role: 'siswa',
      id_guru: null,
      id_siswa: 2,
    },
  ],

  nilai: [
    { id_nilai: 1, id_siswa: 1, id_ampu: 1, semester: 'Ganjil', nilai_tugas: 88, nilai_uts: 85, nilai_akhir: 86.2 },
    { id_nilai: 2, id_siswa: 1, id_ampu: 1, semester: 'Genap', nilai_tugas: 92, nilai_uts: 90, nilai_akhir: 90.8 },
    { id_nilai: 3, id_siswa: 1, id_ampu: 2, semester: 'Ganjil', nilai_tugas: 84, nilai_uts: 80, nilai_akhir: 81.6 },
    { id_nilai: 4, id_siswa: 1, id_ampu: 3, semester: 'Ganjil', nilai_tugas: 90, nilai_uts: 88, nilai_akhir: 88.8 },
    { id_nilai: 5, id_siswa: 1, id_ampu: 4, semester: 'Ganjil', nilai_tugas: 78, nilai_uts: 74, nilai_akhir: 75.6 },
    { id_nilai: 6, id_siswa: 1, id_ampu: 5, semester: 'Ganjil', nilai_tugas: 95, nilai_uts: 90, nilai_akhir: 92.0 },
    { id_nilai: 7, id_siswa: 2, id_ampu: 1, semester: 'Ganjil', nilai_tugas: 86, nilai_uts: 82, nilai_akhir: 83.6 },
    { id_nilai: 8, id_siswa: 2, id_ampu: 2, semester: 'Ganjil', nilai_tugas: 80, nilai_uts: 78, nilai_akhir: 78.8 },
    { id_nilai: 9, id_siswa: 3, id_ampu: 1, semester: 'Ganjil', nilai_tugas: 72, nilai_uts: 70, nilai_akhir: 70.8 },
    { id_nilai: 10, id_siswa: 5, id_ampu: 8, semester: 'Ganjil', nilai_tugas: 89, nilai_uts: 85, nilai_akhir: 86.6 },
  ],

  absensi: [
    { id_absensi: 1, id_siswa: 1, id_kelas: 4, tanggal: daysAgo(4), status: 'Hadir' },
    { id_absensi: 2, id_siswa: 1, id_kelas: 4, tanggal: daysAgo(3), status: 'Hadir' },
    { id_absensi: 3, id_siswa: 1, id_kelas: 4, tanggal: daysAgo(2), status: 'Izin' },
    { id_absensi: 4, id_siswa: 1, id_kelas: 4, tanggal: daysAgo(1), status: 'Hadir' },
    { id_absensi: 5, id_siswa: 2, id_kelas: 4, tanggal: daysAgo(1), status: 'Hadir' },
    { id_absensi: 6, id_siswa: 2, id_kelas: 4, tanggal: daysAgo(0), status: 'Hadir' },
    { id_absensi: 7, id_siswa: 3, id_kelas: 4, tanggal: daysAgo(0), status: 'Sakit' },
    { id_absensi: 8, id_siswa: 4, id_kelas: 4, tanggal: daysAgo(0), status: 'Izin' },
    { id_absensi: 9, id_siswa: 5, id_kelas: 3, tanggal: daysAgo(0), status: 'Hadir' },
    { id_absensi: 10, id_siswa: 7, id_kelas: 1, tanggal: daysAgo(0), status: 'Alpha' },
  ],

  berita: [
    {
      id_berita: 1,
      id_guru: 1,
      created_by: 1,
      judul: 'Pekan Proyek Aplikasi Web & Mobile Jurusan PPLG 2026',
      isi: 'Siswa kelas XI dan XII PPLG memamerkan 18 aplikasi web dan mobile hasil pembelajaran berbasis proyek industri.\n\nKegiatan ini dihadiri oleh mitra industri teknologi lokal yang memberikan ulasan langsung terkait arsitektur kode, desain UI/UX, serta kesiapan deployment.',
      gambar: 'berita_68d1e15c3ed5e.png',
      tanggal_post: '2026-11-18 09:30:00',
    },
    {
      id_berita: 2,
      id_guru: 4,
      created_by: 1,
      judul: 'Workshop Basis Data & Optimasi Query untuk Kelas XI',
      isi: 'Jurusan PPLG menyelenggarakan workshop intensif perancangan skema relasional dan optimasi query SQL bagi seluruh siswa kelas XI.\n\nMateri mencakup normalisasi tabel, indexing, serta praktik migrasi skema untuk proyek akhir semester.',
      gambar: 'berita_68d1e167bcb2e.jpg',
      tanggal_post: '2026-11-15 13:00:00',
    },
    {
      id_berita: 3,
      id_guru: 6,
      created_by: 2,
      judul: 'Tim Game Dev PPLG Lolos Final LKS Tingkat Provinsi',
      isi: 'Tim pengembang gim dari kelas XII RPL A berhasil melaju ke babak final Lomba Kompetensi Siswa (LKS) bidang Game Development.\n\nPurwarupa gim edukasi interaktif mereka dinilai unggul pada aspek gameplay dan performa rendering.',
      gambar: 'berita_6HjkhZ3Rkn.jpg',
      tanggal_post: '2026-11-10 10:15:00',
    },
    {
      id_berita: 4,
      id_guru: null,
      created_by: 1,
      judul: 'Kunjungan Industri ke Studio Perangkat Lunak Bandung',
      isi: 'Sebanyak 65 siswa jurusan PPLG mengikuti kunjungan industri untuk mengenal alur kerja tim engineering modern, mulai dari code review, pengujian otomatis, hingga CI/CD pipeline.',
      gambar: 'berita_cmMan2uSjt.png',
      tanggal_post: '2026-11-05 08:45:00',
    },
    {
      id_berita: 5,
      id_guru: 3,
      created_by: 1,
      judul: 'Pelatihan Git Workflow & Kolaborasi Tim untuk Kelas X',
      isi: 'Siswa baru kelas X PPLG mendapatkan pembekalan dasar version control menggunakan Git agar terbiasa berkolaborasi sejak semester awal.',
      gambar: 'berita_ud2REmXPyY.jpg',
      tanggal_post: '2026-10-28 14:20:00',
    },
    {
      id_berita: 6,
      id_guru: 1,
      created_by: 2,
      judul: 'Pengumuman Jadwal Ujian Praktik Pemrograman Semester Ganjil',
      isi: 'Draft pengumuman jadwal ujian praktik pemrograman web dan basis data untuk kelas XI PPLG B. Menunggu persetujuan admin sebelum ditayangkan ke halaman publik.',
      gambar: null,
      tanggal_post: null,
    },
  ],

  komentar_berita: [
    {
      id_komentar: 1,
      id_berita: 1,
      id_user: 2,
      komentar: 'Apresiasi untuk seluruh tim siswa yang sudah menyiapkan presentasi proyek dengan sangat baik.',
      tanggal_post: '2026-11-18 11:05:00',
    },
    {
      id_komentar: 2,
      id_berita: 1,
      id_user: 4,
      komentar: 'Seru sekali acaranya, banyak masukan berharga dari mentor industri!',
      tanggal_post: '2026-11-18 12:30:00',
    },
    {
      id_komentar: 3,
      id_berita: 2,
      id_user: 5,
      komentar: 'Materi optimasi join kemarin sangat membantu tugas basis data kami.',
      tanggal_post: '2026-11-15 15:10:00',
    },
    {
      id_komentar: 4,
      id_berita: 3,
      id_user: 1,
      komentar: 'Selamat untuk tim XII RPL A, semoga meraih hasil terbaik di tingkat provinsi!',
      tanggal_post: '2026-11-10 11:00:00',
    },
  ],
};

