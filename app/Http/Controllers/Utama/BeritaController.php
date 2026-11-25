<?php

namespace App\Http\Controllers\Utama;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\KomentarBerita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BeritaController extends Controller
{
    // HAPUS atau comment middleware ini agar Siswa/Guru bisa akses method store/update
    // public static function middleware(): array
    // {
    //     return [
    //         'role:admin' => ['only' => ['index', 'store', 'update', 'destroy', 'acc', 'deleteComment']],
    //     ];
    // }

    public function index()
    {
        // Biasanya diarahkan kembali ke dashboard karena list ada di dashboard
        return redirect()->route('dashboard');
    }

    public function create()
    {
        return view('admin.berita.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // LOGIKA PENDING BERITA
        // Jika Admin: langsung terisi tanggal (tayang)
        // Jika Guru/Siswa: null (pending)
        $tanggal_post = Auth::user()->role === 'admin' ? now() : null;

        $data = [
            'judul' => $request->judul,
            'isi' => $request->isi,
            'created_by' => Auth::id(), // ID User yang login
            'tanggal_post' => $tanggal_post,
        ];

        if ($request->hasFile('gambar')) {
            $filename = 'berita_' . Str::random(10) . '.' . $request->gambar->extension();
            $request->gambar->move(public_path('uploads'), $filename);
            $data['gambar'] = $filename;
        }

        Berita::create($data);

        $pesan = Auth::user()->role === 'admin' 
            ? 'Berita berhasil dipublish.' 
            : 'Berita berhasil dikirim dan menunggu persetujuan Admin.';

        return redirect()->route('dashboard')->with('success', $pesan);
    }

    public function edit($id)
    {
        $berita = Berita::findOrFail($id);

        // CEK KEPEMILIKAN
        if (Auth::user()->role !== 'admin' && $berita->created_by !== Auth::id()) {
            abort(403, 'Anda tidak berhak mengedit berita ini.');
        }

        return view('admin.berita.edit', compact('berita'));
    }

    public function update(Request $request, $id)
    {
        $berita = Berita::findOrFail($id);

        // CEK KEPEMILIKAN
        if (Auth::user()->role !== 'admin' && $berita->created_by !== Auth::id()) {
            abort(403, 'Anda tidak berhak mengedit berita ini.');
        }

        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = [
            'judul' => $request->judul,
            'isi' => $request->isi,
        ];

        if ($request->hasFile('gambar')) {
            if ($berita->gambar && file_exists(public_path('uploads/' . $berita->gambar))) {
                unlink(public_path('uploads/' . $berita->gambar));
            }
            $filename = 'berita_' . Str::random(10) . '.' . $request->gambar->extension();
            $request->gambar->move(public_path('uploads'), $filename);
            $data['gambar'] = $filename;
        }

        $berita->update($data);
        return redirect()->route('dashboard')->with('success', 'Berita berhasil diperbarui.');
    }

    public function acc($id)
    {
        // Hanya Admin yang boleh ACC
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $berita = Berita::findOrFail($id);
        $berita->update(['tanggal_post' => now()]);
        return redirect()->route('dashboard')->with('success', 'Berita disetujui dan ditayangkan.');
    }

    public function destroy($id)
    {
        $berita = Berita::findOrFail($id);

        // CEK KEPEMILIKAN: Hanya Admin atau Pemilik berita yang boleh hapus
        if (Auth::user()->role !== 'admin' && $berita->created_by !== Auth::id()) {
            abort(403, 'Anda tidak berhak menghapus berita ini.');
        }

        if ($berita->gambar && file_exists(public_path('uploads/' . $berita->gambar))) {
            unlink(public_path('uploads/' . $berita->gambar));
        }
        $berita->delete();
        return redirect()->route('dashboard')->with('success', 'Berita dihapus.');
    }

    public function show($id)
    {
        // Semua user boleh lihat detail
        $berita = Berita::with('guru', 'user')->findOrFail($id);
        
        // Logika agar user lain tidak bisa lihat berita pending orang lain
        if (!$berita->tanggal_post && Auth::user()->role !== 'admin' && $berita->created_by !== Auth::id()) {
             abort(404);
        }

        $comments = KomentarBerita::with('user')
            ->where('id_berita', $id)
            ->orderBy('tanggal_post', 'desc')
            ->get();

        $otherBerita = Berita::where('id_berita', '!=', $id)
            ->whereNotNull('tanggal_post') // Hanya tampilkan yang sudah acc
            ->orderBy('tanggal_post', 'desc')
            ->limit(6)
            ->get();

        return view('admin.berita.show', compact('berita', 'comments', 'otherBerita'));
    }

    public function storeComment(Request $request, $id)
    {
        $request->validate(['komentar' => 'required|string|max:1000']);
        KomentarBerita::create([
            'id_berita' => $id,
            'id_user' => Auth::id(),
            'komentar' => $request->komentar,
            'tanggal_post' => now(),
        ]);
        return redirect()->route('admin.berita.show', $id)->with('success', 'Komentar terkirim.');
    }

    public function deleteComment($id)
    {
        $komentar = KomentarBerita::findOrFail($id);
        // Admin atau Pemilik Komentar boleh hapus
        if (Auth::user()->role !== 'admin' && $komentar->id_user !== Auth::id()) {
            abort(403);
        }
        $komentar->delete();
        return redirect()->back()->with('success', 'Komentar dihapus.');
    }
}