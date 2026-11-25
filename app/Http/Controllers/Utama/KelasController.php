<?php

namespace App\Http\Controllers\Utama;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class KelasController extends Controller
{
    public static function middleware(): array
    {
        return [
            'role:admin' => ['only' => ['store', 'update', 'destroy']],
        ];
    }

    public function index()
    {
        $kelas = Kelas::with('wali')->orderBy('tingkat')->orderBy('kelas')->get();
        $gurus = Guru::orderBy('nama')->get();

        $total_kelas = $kelas->count();
        $tingkat_count = $kelas->pluck('tingkat')->unique()->count();
        $wali_count = $kelas->filter(fn($k) => $k->id_wali !== null)->pluck('id_wali')->unique()->count();
        $recent = $kelas->take(-5)->values();

        return view('admin.kelas.index', compact(
            'kelas', 'gurus', 'total_kelas', 'tingkat_count', 'wali_count', 'recent'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tingkat' => 'required|in:X,XI,XII',
            'kelas' => 'required|string|max:50',
            'id_wali' => 'nullable|exists:guru,id_guru',
        ], [
            'tingkat.required' => 'Tingkat kelas harus dipilih.',
            'kelas.required' => 'Nama kelas wajib diisi.',
        ]);

        Kelas::create([
            'tingkat' => $request->tingkat,
            'kelas' => $request->kelas,
            'id_wali' => $request->id_wali ?: null,
        ]);

        Session::flash('success', 'Kelas berhasil ditambahkan');
        return redirect()->route('admin.kelas.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tingkat' => 'required|in:X,XI,XII',
            'kelas' => 'required|string|max:50',
            'id_wali' => 'nullable|exists:guru,id_guru',
        ], [
            'tingkat.required' => 'Tingkat kelas harus dipilih.',
            'kelas.required' => 'Nama kelas wajib diisi.',
        ]);

        $kelas = Kelas::findOrFail($id);
        $kelas->update([
            'tingkat' => $request->tingkat,
            'kelas' => $request->kelas,
            'id_wali' => $request->id_wali ?: null,
        ]);

        Session::flash('success', 'Kelas berhasil diupdate');
        return redirect()->route('admin.kelas.index');
    }

    public function destroy($id)
    {
        Kelas::destroy($id);

        Session::flash('success', 'Kelas berhasil dihapus');
        return redirect()->route('admin.kelas.index');
    }
}