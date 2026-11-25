# Profil Jurusan PPLG / RPL — School Department Portal & Static Portfolio Demo

A role-based vocational school department portal (**Pengembangan Perangkat Lunak dan Gim / Rekayasa Perangkat Lunak**) originally built with **Laravel 12 (Blade + MySQL)** and paired with a **self-contained static frontend demo** (`static-demo/`) deployable directly to **Vercel**.

---

## Project Variants in This Repository

This repository intentionally preserves both versions side by side:

| Variant | Location | Purpose | Requirements |
| :--- | :--- | :--- | :--- |
| **1. Original Laravel Fullstack App** | Repository root (`app/`, `resources/`, `routes/`, `public/`, `database/`) | Production-style server-rendered MVC app with database persistence, server sessions, CSRF protection, and role middleware | PHP `^8.2`, Composer, MySQL, Node.js (optional Vite) |
| **2. Static Frontend Portfolio Demo** | [`static-demo/`](./static-demo) | Zero-backend, framework-free static migration built for instant live previews on **Vercel** using ES Modules, mock seed data, and `localStorage` | Any static HTTP server (or Vercel) |

> **Note:** Original Laravel files were kept completely untouched during the static migration.

---

## Key Features

- **Public Portal & News Feed:** Department profile, vision & mission, live summary statistics, previews of teachers/classes/students/subjects, and read-only news articles with comments.
- **Interactive Authentication UI:** Split-card login screen featuring a password visibility toggle with an animated peeking hacker character and a live PHP code-preview panel (`AuthController.php`, `Validation.php`, `ErrorLog.php`).
- **Role-Based Access Control (`admin`, `guru`, `siswa`):**
  - **Admin:** Full CRUD for Students (`Siswa`), Teachers (`Guru` + multi-subject `Mapel Diampu`), Classes (`Kelas` + Homeroom Teacher), Subjects (`Mapel`), Accounts (`Akun`), News approval (`ACC` / `Tolak`), and school-wide Attendance (`Absensi`).
  - **Guru (Teacher):** Homeroom teachers (`Wali Kelas`) manage class attendance; non-homeroom teachers see an access notice on `Absensi`. Teachers can manage (`Ganjil`/`Genap`) grades (`Nilai`) strictly for subjects they teach (`id_ampu` ownership).
  - **Siswa (Student):** View personal grade transcripts with global & per-semester averages, submit once-daily attendance check-in, view attendance history/stats, and submit news drafts for admin review.
- **Exports:** Client-side CSV downloads (UTF-8 BOM `Blob`) and A4 print-to-PDF reports (`print.css`) in `static-demo/` replacing Laravel `StreamedResponse` / DomPDF views.

---

## Architecture & Technologies

### Original Laravel Application
- **Backend:** PHP `^8.2`, Laravel `^12.0`, Eloquent ORM (`10` domain models: `User`, `Siswa`, `Guru`, `Kelas`, `Mapel`, `MapelDiampu`, `Nilai`, `Absensi`, `Berita`, `KomentarBerita`)
- **Frontend:** Laravel Blade templates (`resources/views/`), Bootstrap `5.3.3` (CDN), Font Awesome `6.5.0`, Bootstrap Icons `1.11.3`, SweetAlert2 `v11`, Google Fonts (`Poppins`), custom CSS (`public/assets/css/style.css`)
- **Database:** MySQL (`DB_DATABASE=projeklaravel` in `.env.example`)

### Static Frontend Demo (`static-demo/`)
- **Core Stack:** Semantic HTML5, CSS3 (`style.css`, `print.css`), Vanilla JavaScript (**ES Modules**, zero build tools, no React/Vue)
- **UI Libraries:** Bootstrap `5.3.3`, SweetAlert2 `v11`, Font Awesome `6.5.0`, Bootstrap Icons `1.11.3`, Google Fonts (`Poppins`)
- **Client-Side Persistence & Modules:**
  - `assets/js/seed.js` — Fictional initial dataset for all 10 domain entities
  - `assets/js/store.js` — `localStorage` CRUD engine, relational joins, cascade cleanup on delete, and form validators
  - `assets/js/auth.js` — Simulated session persistence, role guards (`requireAuth`, `requireRole`), and `sessionStorage` flash alerts
  - `assets/js/layout.js` — Shared navbar, footer, logout modal, SweetAlert2 helpers, and `Reset Demo Data` control
  - `assets/js/export.js` — RFC-4180 UTF-8 CSV generator and print-report renderer
  - `assets/js/pages/*.js` — Page-level ES Module controllers for all 14 views

---

## Repository Structure

```text
.
├── app/
│   ├── Http/Controllers/       # Home, Auth, Utama/* CRUD, Export/* controllers
│   └── Models/                 # Eloquent models (User, Siswa, Guru, Kelas, Mapel, etc.)
├── database/migrations/        # Laravel default migrations (users, cache, jobs)
├── public/                     # Original public assets (assets/, uploads/)
├── resources/views/            # Original Blade layouts, partials, guest & admin views
├── routes/web.php              # Web route definitions
├── static-demo/                # Standalone static portfolio build (Vercel-ready)
│   ├── index.html              # Public homepage
│   ├── login.html              # Interactive login page with demo account quick-fill
│   ├── berita-detail.html      # Public news detail
│   ├── dashboard.html          # Role-aware dashboard & news moderation
│   ├── berita-form.html        # Create / edit news article
│   ├── berita-show.html        # Authenticated news detail & comments
│   ├── siswa.html              # Student management (CRUD + export)
│   ├── guru.html               # Teacher & subject-assignment management
│   ├── kelas.html              # Class & homeroom management
│   ├── mapel.html              # Subject catalog management
│   ├── akun.html               # User account management (Admin only)
│   ├── nilai.html              # Grade index (Staff list / Student transcript)
│   ├── nilai-detail.html       # Per-student subject grades (Ganjil/Genap modal)
│   ├── absensi.html            # Multi-role attendance module
│   ├── assets/
│   │   ├── css/                # style.css & print.css
│   │   ├── js/                 # seed.js, store.js, auth.js, layout.js, export.js, pages/
│   │   ├── bg/, img/, logo/    # Visual assets
│   └── uploads/                # Sample news images
├── .env.example                # Safe environment template
├── .gitignore                  # Excludes .env, vendor/, node_modules/, public/build/
├── composer.json               # PHP dependencies
└── package.json                # Node/Vite dev dependencies (optional)
```

---

## Running Locally

### Option A: Static Portfolio Demo (`static-demo/`)
Because the static demo uses native ES Modules (`<script type="module">`), serve it over HTTP rather than opening `.html` files directly via `file://`:

```bash
# Using Node.js (npx)
npx serve static-demo

# Or using Python 3
python -m http.server 8000 --directory static-demo
```

Then open `http://localhost:3000` (for `serve`) or `http://localhost:8000` (for Python).

### Option B: Original Laravel Application
> **Database Note:** `database/migrations/` contains only Laravel's default `users`, `cache`, and `jobs` migrations. The domain tables (`user`, `siswa`, `guru`, `kelas`, `mapel`, `mapel_diampu`, `nilai`, `absensi`, `berita`, `komentar_berita`) must exist in your MySQL database (`projeklaravel`).

```bash
# 1. Install PHP dependencies
composer install

# 2. Create environment file and app key
cp .env.example .env
php artisan key:generate

# 3. Configure DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env, then import your SQL schema

# 4. Start the Laravel development server
php artisan serve
```

---

## Demo Accounts (Static Demo Only)

All accounts below are **fictional demo accounts** pre-seeded in `static-demo/assets/js/seed.js` and accessible via one-click pills on `login.html`:

| Role | Username | Password | Access & Behavior Scope |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `demo-admin-password` | Full CRUD across `Siswa`, `Guru`, `Kelas`, `Mapel`, `Akun`, news `ACC`/`Tolak`, and all-class `Absensi` |
| **Guru (Wali Kelas)** | `guru_wali` | `demo-guru-password` | Homeroom teacher for `XI PPLG B` (`Budi Santoso, S.Kom`); manages `XI PPLG B` attendance and grades for owned subjects |
| **Guru (Non-Wali)** | `guru_mapel` | `demo-guru-password` | Subject teacher (`Siti Aminah, S.Pd`); triggers homeroom restriction notice on `Absensi` and edits only owned subjects in `Nilai` |
| **Siswa (Primary)** | `siswa_demo` | `demo-siswa-password` | Student (`Andi Saputra`, `XI PPLG B`); personal `Nilai` transcript + daily `Absensi` self check-in |
| **Siswa (Secondary)** | `siswa_bunga` | `demo-siswa-password` | Student (`Bunga Citra`, `XI PPLG B`); secondary student perspective |

---

## Static Demo Limitations

- **Simulated Authentication Only:** `static-demo/assets/js/auth.js` checks credentials in the browser using `localStorage` and `sessionStorage`. It provides **no real server-side security or password hashing** and exists solely for portfolio demonstration.
- **Browser-Local Storage:** All CRUD changes, comments, grades, and attendance records persist only in the visitor's browser `localStorage`. Click **Reset Demo Data** in the footer at any time to restore the initial dataset.
- **Image Upload Quota:** Images uploaded via `berita-form.html` are converted to Base64 Data URLs (capped at 2 MB) inside `localStorage`, which is subject to browser storage limits (~5 MB).
- **PDF Exports:** Server-side DomPDF exports are replaced with formatted print stylesheets (`assets/css/print.css`) triggered via `window.print()` ("Save as PDF").

---

## Deploying `static-demo/` to Vercel

`static-demo/` requires no build step and deploys directly as a static site:

1. Import this GitHub repository into **Vercel**.
2. Set the following **Build & Development Settings**:
   - **Framework Preset:** `Other`
   - **Root Directory:** `static-demo`
   - **Build Command:** `None` *(leave empty / override off)*
   - **Output Directory:** `.`
3. Click **Deploy**.

---

## Screenshots (Portfolio Placeholders)

Replace these placeholders with captured screenshots before showcasing:

| View | Preview Placeholder |
| :--- | :--- |
| **Public Homepage (`index.html`)** | `![Public Homepage](docs/screenshots/home.png)` |
| **Interactive Login (`login.html`)** | `![Login Page with Hacker Peek](docs/screenshots/login.png)` |
| **Role-Based Dashboard (`dashboard.html`)** | `![Admin Dashboard](docs/screenshots/dashboard.png)` |
| **Master Data CRUD Modal (`siswa.html` / `guru.html`)** | `![Master Data Detail Modal](docs/screenshots/crud-modal.png)` |
| **Grades & Attendance (`nilai-detail.html` / `absensi.html`)** | `![Nilai and Absensi](docs/screenshots/academic.png)` |

---

## License

This project is open-sourced under the [MIT License](https://opensource.org/licenses/MIT).
