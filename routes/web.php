<?php

use App\Http\Controllers\Utama\DashboardController;
use App\Http\Controllers\Utama\AbsensiController;
use App\Http\Controllers\Utama\AkunController;
use App\Http\Controllers\Utama\BeritaController as UtamaBeritaController;
use App\Http\Controllers\Utama\GuruController;
use App\Http\Controllers\Utama\NilaiController;
use App\Http\Controllers\Export\SiswaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Export\KelasController;
use App\Http\Controllers\Beritacontroller2;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Utama\KelasController as UtamaKelasController;
use App\Http\Controllers\Utama\MapelController;
use App\Http\Controllers\Utama\SiswaController as UtamaSiswaController;
use App\Http\Controllers\Export\AkunController as ExportAkunController;
use App\Http\Controllers\Export\GuruController as ExportGuruController;
use App\Http\Controllers\Export\MapelController as ExportMapelController;
use App\Http\Controllers\Export\NilaiController as ExportNilaiController;

Route::get('/', [HomeController::class, 'index'])->name('home');

//logika login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

//logika detail berita
Route::get('/berita/{id}', [Beritacontroller2::class, 'detail'])
    ->name('berita.detail');

//halaman tamu/guest
Route::get('/siswa', fn() => 'Halaman siswa')
    ->name('guest.siswa');
Route::get('/guru', fn() => 'Halaman guru')
    ->name('guest.guru');
Route::get('/kelas', [KelasController::class, 'index'])
    ->name('guest.kelas');
Route::get('/mapel', fn() => 'Halaman mapel')->name('guest.mapel');
Route::get('/nilai', fn() => 'Halaman nilai')->name('guest.nilai');


Route::middleware(['auth'])->group(function () {
    

    // DASHBOARD (Satu jalur untuk semua)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::prefix('admin')->name('admin.')->group(function() {
        
        Route::get('/berita', [UtamaBeritaController::class, 'index'])->name('berita.index');
        Route::get('/berita/create', [UtamaBeritaController::class, 'create'])->name('berita.create');
        Route::post('/berita', [UtamaBeritaController::class, 'store'])->name('berita.store');
        Route::get('/berita/{id}/edit', [UtamaBeritaController::class, 'edit'])->name('berita.edit');
        Route::put('/berita/{id}', [UtamaBeritaController::class, 'update'])->name('berita.update');
        Route::delete('/berita/{id}', [UtamaBeritaController::class, 'destroy'])->name('berita.destroy'); 
        Route::get('/berita/{id}/show', [UtamaBeritaController::class, 'show'])->name('berita.show'); // Ubah URL dikit biar ga konflik
        
        // Fitur Tambahan Berita
        Route::post('/berita/{id}/acc', [UtamaBeritaController::class, 'acc'])->name('berita.acc');
        Route::delete('/komentar/{id}', [UtamaBeritaController::class, 'deleteComment'])->name('komentar.delete');
        Route::post('/berita/{id}/comment', [UtamaBeritaController::class, 'storeComment'])->name('berita.comment.store');
    });

        Route::get('/absensi', [AbsensiController::class, 'index'])->name('admin.absensi.index'); // Sesuaikan nama route jika perlu
        Route::post('/absensi', [AbsensiController::class, 'update'])->name('admin.absensi.update');
});


    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // siswa
    Route::resource('siswa', UtamaSiswaController::class)->except(['show', 'create', 'edit']);
    Route::get('/siswa/export/csv', [SiswaController::class, 'exportCsv'])->name('siswa.export.csv');
    Route::get('/siswa/export/pdf/{id}', [SiswaController::class, 'exportPdf'])->name('siswa.export.pdf');
    Route::get('/siswa/export/pdf', [SiswaController::class, 'exportPdfAll'])->name('siswa.export.pdf.all');
    
    // guru
    Route::resource('guru', GuruController::class)->except(['show', 'create', 'edit']);
    Route::get('/guru/export/csv', [ExportGuruController::class, 'exportCsv'])->name('guru.export.csv');
    Route::get('/guru/export/pdf/{id}', [ExportGuruController::class, 'exportPdf'])->name('guru.export.pdf');
    Route::get('/guru/export/pdf', [ExportGuruController::class, 'exportPdfAll'])->name('guru.export.pdf.all');

    // kelas
    Route::resource('kelas', UtamaKelasController::class)->except(['show', 'create', 'edit']);
    Route::delete('kelas/{id}', [UtamaKelasController::class, 'destroy'])->name('kelas.destroy');
    Route::get('/kelas/export/csv', [KelasController::class, 'exportCsv'])->name('kelas.export.csv');
    Route::get('/kelas/export/pdf/{id}', [KelasController::class, 'exportPdf'])->name('kelas.export.pdf');
    Route::get('/kelas/export/pdf', [KelasController::class, 'exportPdfAll'])->name('kelas.export.pdf.all');

    // mapel
    Route::resource('mapel', MapelController::class)->except(['show', 'create', 'edit']);
    Route::get('/mapel/export/csv', [ExportMapelController::class, 'exportCsv'])->name('mapel.export.csv');
    Route::get('/mapel/export/pdf/{id}', [ExportMapelController::class, 'exportPdf'])->name('mapel.export.pdf');
    Route::get('/mapel/export/pdf', [ExportMapelController::class, 'exportPdfAll'])->name('mapel.export.pdf.all');

    // nilai
    Route::get('/nilai', [NilaiController::class, 'index'])->name('nilai.index');
    Route::get('/nilai/{id_siswa}', [NilaiController::class, 'show'])->name('nilai.show');
    Route::get('/nilai/export/csv', [ExportNilaiController::class, 'exportCsv'])->name('nilai.export.csv');
    Route::get('/nilai/export/pdf/{id_siswa}', [ExportNilaiController::class, 'exportPdf'])->name('nilai.export.pdf');
    Route::post('/nilai/update', [NilaiController::class, 'update'])->name('nilai.update');
    Route::delete('/nilai/{id}', [NilaiController::class, 'destroy'])->name('nilai.destroy');
    // akun
    Route::resource('akun', AkunController::class)->except(['show', 'create', 'edit']);
    Route::get('/akun/export/csv', [ExportAkunController::class, 'exportCsv'])->name('akun.export.csv');
    Route::get('/akun/export/pdf', [ExportAkunController::class, 'exportPdfAll'])->name('akun.export.pdf.all');

});
