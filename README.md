# 🌐 Web Profil Jurusan RPL / PPLG

Website profil jurusan berbasis role user dengan sistem manajemen data lengkap (admin, guru, siswa, dan guest).  
Dibuat untuk menampilkan informasi jurusan sekaligus sebagai sistem manajemen data sekolah yang terintegrasi.

---

## 🚀 Fitur Utama

### 👥 Role & Akses
| Role | Deskripsi |
|------|------------|
| **Guest** | Bisa melihat berita tapi tidak bisa berkomentar atau mengakses data siswa/guru. |
| **Admin** | Akses penuh untuk semua halaman (berita, data siswa, guru, kelas, mapel, absen, nilai, akun). |
| **Guru** | Bisa melihat semua data, menilai siswa sesuai mapel & kelas yang dia ampu, dan mengatur absensi kelas yang dia wali kelasi. |
| **Siswa** | Bisa melihat semua data publik, nilai sendiri, dan mengisi absensi harian satu kali per hari. |

---

## 📰 Halaman Berita
- Menampilkan daftar berita terbaru.
- **Admin** bisa tambah, edit, hapus berita.
- **Guru & Siswa** bisa membuat berita baru → **harus disetujui Admin** dulu.
- Jika disetujui → berita tampil, jika ditolak → berita dihapus otomatis.
- Fitur **Komentar Berita**:
  - Nama komentar otomatis sesuai akun login.
  - **Admin** bisa hapus/edit komentar siapa pun.
  - **Guru/Siswa** bisa hapus/edit komentar hanya milik sendiri.
  - **Guest** hanya bisa membaca komentar.

---

## 🧑‍🎓 Data Siswa
- Tampil semua data siswa lengkap.
- **Admin** bisa tambah, edit, hapus data siswa.
- Fitur **search**, **export PDF**, dan **export CSV**.
- Setiap siswa terhubung dengan:
  - Kelas
  - Guru wali kelas

---

## 🧑‍🏫 Data Guru
- Menampilkan seluruh guru beserta data wali kelas-nya.
- **Admin** bisa tambah, edit, hapus.
- Guru bisa tidak memiliki wali kelas (opsional).
- Terhubung dengan mapel yang dia ampu.

---

## 🏫 Data Kelas & Mapel
- Sama seperti halaman siswa/guru.
- Bisa tambah, edit, hapus (khusus admin).
- Fitur **search** & **export** tersedia.
- Guru dan siswa hanya bisa melihat.

---

## 📊 Nilai
- **Guru** dapat menginput nilai hanya untuk:
  - Kelas yang dia ajar.
  - Mapel yang dia ampu.
- **Admin** hanya bisa melihat nilai tanpa mengedit.
- **Siswa** hanya bisa melihat nilai miliknya sendiri.

---

## 🗓️ Absensi
- Menampilkan semua absensi siswa dengan tanggal.
- **Admin** dapat edit semua absen.
- **Guru (wali kelas)** bisa edit/hapus absen siswa kelasnya.
- **Siswa** hanya bisa mengisi absen **1x per hari**.

---

## 🔐 Manajemen Akun
- **Admin** bisa membuat akun baru:
  - Admin
  - Guru
  - Siswa
- Akun guru/siswa harus **dilink** ke data guru/siswa yang sudah ada.
- Username otomatis mengambil nama dari data yang terhubung.

---

## 💡 Fitur Tambahan
- 🔍 **Search engine** di setiap tabel.
- 📤 **Export PDF / CSV**.
- 👋 **Sambutan selamat datang** untuk admin saat login.
- 🔄 **Sistem login/logout** aman dengan session.
- ⚙️ **Validasi role** di setiap halaman.

---

## 🛠️ Tech Stack
- **Frontend:** HTML, CSS, JavaScript  
- **Backend:** PHP (CodeIgniter / Native)  
- **Database:** MySQL  
- **Server:** Laragon / XAMPP  

---

## 📦 Instalasi Lokal
<ol>
	<li>Download Laragon/XAMPP/AMPPS atau sejenisnya dan install</li>
	<li>Jalankan <b>Apache</b> dan <b>Mysql</b></li>
	<li>Copy File <b>Profil-jurusan.zip</b> ke Folder <b>C://xampp/htdocs/</b>, <b>C://laragon/www/</b>, <b>C://ampps/www/</b> lalu extract</li>
</ol>
<br/>
<h2>Creating Database</h2>
<ol>
	<li>Masuk ke Browser kemudian tulis di Address Bar http://localhost/phpmyadmin</li>
	<li>Buat Database dengan Nama <b>profil_jurusan_pplg</b></li>
	<li>Import Database <b>profil_jurusan_pplg.sql</b> <a href="https://www.domainesia.com/panduan/cara-import-database-mysql-di-phpmyadmin/" target="_blank">Tutorial Disini</a></li>
</ol>
<br/>

<h2>Akses Aplikasi</h2>
<b>Akses Admin</b>
<ul> 
	<li>Masuk ke Browser kemudian tulis di address bar <b>http://localhost/ProfilJurusan/</b></li>
	<li>Login dengan menggunakan <b>Username = admin</b> dan <b>Password = admin123</b></li> 
</ul>
<b>Akses User </b>
<ul> 
	<li>Masuk Ke Browser kemudian tulis di address bar <b>http://localhost/ProfilJurusan/</b></li>
	<li>Login dengan menggunakan <b>Username dan Password </b>yang telah di INPUT oleh Admin sebelumnya</li>
</ul>

## 🧑‍💻 Pengembang

**Sakti Arif Dwi Putra**  
💼 *Backend & Fullstack Developer (Entry Level)*  
📍 Indonesia  

---

<p align="center">
  © 2025 Profil jurusan Web App — Dibuat untuk untuk memenuhi projek sekolah
</p>
