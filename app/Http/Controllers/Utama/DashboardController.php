<?php

namespace App\Http\Controllers\Utama;
use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Hitungan Statistik (Tetap sama)
        $siswa_count = Siswa::count();
        $guru_count  = Guru::count();
        $kelas_count = Kelas::count();
        $mapel_count = Mapel::count();

        // LOGIKA QUERY BERITA
        $user = Auth::user();
        
        $query = Berita::with(['user','guru']);

        // Jika BUKAN admin, filter beritanya
        if ($user->role !== 'admin') {
            $query->where(function($q) use ($user) {
                $q->whereNotNull('tanggal_post') // Tampilkan yang sudah di-ACC
                  ->orWhere('created_by', $user->id_user); // ATAU tampilkan punya saya sendiri (meski pending)
            });
        }

        $berita = $query->orderByRaw('(tanggal_post IS NULL) DESC') // Pending paling atas (untuk admin/pemilik)
            ->orderBy('tanggal_post','DESC')
            ->orderBy('id_berita','DESC')
            ->limit(10)
            ->get();

        // Data Preview lainnya (Tetap sama)
        $guru = Guru::select('id_guru','nama','nip')->orderBy('nama')->limit(6)->get();
        $kelas = Kelas::select('id_kelas','tingkat','kelas')->orderBy('tingkat')->orderBy('kelas')->limit(8)->get();
        $mapel = Mapel::select('id_mapel','nama_mapel','kategori')->orderBy('nama_mapel')->limit(12)->get();
        $siswa_preview = Siswa::select('id_siswa','nama','nisn')->orderBy('nama')->limit(6)->get();

        return view('admin.dashboard', compact(
            'berita',
            'siswa_count',
            'guru_count',
            'kelas_count',
            'mapel_count',
            'guru',
            'kelas',
            'mapel',
            'siswa_preview'
        ));
    }
}