<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Redirect berdasarkan role (pengganti session PHP native)
        if (Auth::check()) {
            if (Auth::user()->role == 'admin') {
                return redirect('/admin/dashboard');
            } elseif (Auth::user()->role == 'guru') {
                return redirect('/guru/dashboard');
            } elseif (Auth::user()->role == 'siswa') {
                return redirect('/siswa/dashboard');
            }
        }

        // Statistik
        $siswa_count = Siswa::count();
        $guru_count  = Guru::count();
        $kelas_count = Kelas::count();
        $mapel_count = Mapel::count();

        // Berita terbaru
        $berita = Berita::with(['guru','user'])
                    ->orderBy('tanggal_post','desc')
                    ->limit(5)
                    ->get();

        // Preview data
        $guru = Guru::select('id_guru','nama','nip')
                    ->orderBy('nama')
                    ->limit(6)
                    ->get();

        $kelas = Kelas::select('id_kelas','tingkat','kelas')
                      ->orderBy('tingkat')
                      ->limit(8)
                      ->get();

        $mapel = Mapel::select('id_mapel','nama_mapel','kategori')
                      ->orderBy('nama_mapel')
                      ->limit(8)
                      ->get();

        $siswa_preview = Siswa::select('id_siswa','nama','nisn')
                              ->orderBy('nama')
                              ->limit(6)
                              ->get();

        return view('home', compact(
            'siswa_count',
            'guru_count',
            'kelas_count',
            'mapel_count',
            'berita',
            'guru',
            'kelas',
            'mapel',
            'siswa_preview'
        ));
    }
}
