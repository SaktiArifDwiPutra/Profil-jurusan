<?php

namespace App\Http\Controllers\Utama;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SiswaController extends Controller
{
    public static function middleware(): array
    {
        return [
            'role:admin' => ['only' => ['store', 'update', 'destroy']],
        ];
    }

    public function index()
    {
        $siswas = Siswa::with('kelas')->orderBy('nama')->get();
        $kelas = Kelas::orderBy('tingkat')->orderBy('kelas')->get();

        $total_siswa = $siswas->count();
        $laki = $siswas->where('jenis_kelamin', 'Laki-laki')->count();
        $perempuan = $siswas->where('jenis_kelamin', 'Perempuan')->count();
        $jumlah_kelas = $kelas->count();
        $recent = $siswas->take(-5)->values(); // 5 terakhir

        return view('admin.siswa.index', compact(
            'siswas', 'kelas', 'total_siswa', 'laki', 'perempuan', 'jumlah_kelas', 'recent'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nisn' => 'required|numeric|digits_between:9,10|unique:siswa,nisn',
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_hp' => 'required|numeric', 
            'id_kelas' => 'required|exists:kelas,id_kelas',
        ], [
            'nisn.numeric' => 'NISN harus berupa angka.',
            'nisn.digits_between' => 'NISN harus berjumlah 9 atau 10 digit.',
            'no_hp.numeric' => 'No HP harus berupa angka.'
        ]);

        Siswa::create($request->only([
            'id_kelas', 'nisn', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'alamat', 'no_hp'
        ]));

        Session::flash('success', 'Siswa berhasil ditambahkan');
        return redirect()->route('admin.siswa.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nisn' => 'required|numeric|digits_between:9,10|unique:siswa,nisn,' . $id . ',id_siswa',
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_hp' => 'required|numeric',
            'id_kelas' => 'required|exists:kelas,id_kelas',
        ], [
            'nisn.numeric' => 'NISN harus berupa angka.',
            'nisn.digits_between' => 'NISN harus berjumlah 9 atau 10 digit.',
            'no_hp.numeric' => 'No HP harus berupa angka.'
        ]);

        $siswa = Siswa::findOrFail($id);
        $siswa->update($request->only([
            'id_kelas', 'nisn', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'alamat', 'no_hp'
        ]));

        Session::flash('success', 'Siswa berhasil diupdate');
        return redirect()->route('admin.siswa.index');
    }

    public function destroy($id)
    {
        Siswa::destroy($id);
        Session::flash('success', 'Siswa berhasil dihapus');
        return redirect()->route('admin.siswa.index');
    }
}