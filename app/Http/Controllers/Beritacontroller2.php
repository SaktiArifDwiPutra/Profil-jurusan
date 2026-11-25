<?php

namespace App\Http\Controllers; // <--- Pastikan Namespace ini benar (tanpa \Utama)

use Illuminate\Http\Request;
use App\Models\Berita;
use App\Models\KomentarBerita; // <--- Pastikan pakai Model yang benar

// Pastikan nama class SAMA PERSIS dengan nama file (Beritacontroller2.php)
class Beritacontroller2 extends Controller 
{
    /**
     * Menampilkan detail berita untuk Guest/Public
     */
    public function detail($id)
    {
        // 1. Ambil Berita Utama
        $berita = Berita::with(['user', 'guru'])->findOrFail($id);

        // 2. Ambil Komentar
        $comments = KomentarBerita::with('user')
                    ->where('id_berita', $id)
                    ->orderBy('tanggal_post', 'desc') // Pakai tanggal_post
                    ->get();

        // 3. Ambil Berita Lainnya (Sidebar)
        $otherBerita = Berita::where('id_berita', '!=', $id)
                    ->orderBy('tanggal_post', 'desc') // Pakai tanggal_post
                    ->take(5)
                    ->get();

        // 4. Kirim ke View (Sesuaikan nama view kamu)
        // Misal view khusus guest ada di folder: resources/views/guest/berita.blade.php
        // Maka tulis: return view('guest.berita', ...);
        
        // Kalau viewnya sama dengan yang admin (pake logika @auth tadi):
        return view('berita.detail', compact('berita', 'comments', 'otherBerita'));
    }
}