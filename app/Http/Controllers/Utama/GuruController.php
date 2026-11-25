<?php

namespace App\Http\Controllers\Utama;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\MapelDiampu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GuruController extends Controller
{
    public static function middleware(): array
    {
        return [
            'role:admin' => ['only' => ['store', 'update', 'destroy']],
        ];
    }

    public function index()
    {
        $gurus = Guru::with('mapelDiampu.mapel', 'mapelDiampu.kelas')->orderBy('nama')->get();
        $mapels = Mapel::orderBy('nama_mapel')->get();
        $kelas = Kelas::orderBy('tingkat')->orderBy('kelas')->get();

        $total_guru = $gurus->count();
        $laki = $gurus->where('jenis_kelamin', 'Laki-laki')->count();
        $perempuan = $gurus->where('jenis_kelamin', 'Perempuan')->count();

        $mapel_set = [];
        $kelas_set = [];
        foreach ($gurus as $g) {
            foreach ($g->mapelDiampu as $a) {
                $mapel_set[$a->id_mapel] = $a->mapel->nama_mapel;
                $kelas_set[$a->id_kelas] = $a->kelas->tingkat . ' ' . $a->kelas->kelas;
            }
        }

        $recent = $gurus->take(-5)->values();

        return view('admin.guru.index', compact(
            'gurus', 'mapels', 'kelas', 'total_guru', 'laki', 'perempuan',
            'mapel_set', 'kelas_set', 'recent'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nip' => 'nullable|numeric|digits:18|unique:guru,nip',
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'email' => 'required|email:dns|max:100|unique:guru,email',
            'no_hp' => 'required|numeric',
        ], [
            'nip.numeric' => 'NIP harus berupa angka.',
            'nip.digits' => 'NIP harus berjumlah tepat 18 digit.',
            'nip.unique' => 'NIP ini sudah terdaftar.',
            'email.email' => 'Format email tidak valid (contoh: nama@gmail.com).',
            'email.unique' => 'Email ini sudah digunakan oleh guru lain.',
            'no_hp.numeric' => 'No HP harus berupa angka.',
        ]);

        $guru = Guru::create($request->only(['nip', 'nama', 'jenis_kelamin', 'email', 'no_hp']));
        $ampu_mapel = $request->input('ampu_mapel', []);
        $ampu_kelas = $request->input('ampu_kelas', []);
        foreach ($ampu_mapel as $i => $mp) {
            if ($mp && isset($ampu_kelas[$i]) && $ampu_kelas[$i]) {
                MapelDiampu::create([
                    'id_guru' => $guru->id_guru,
                    'id_mapel' => $mp,
                    'id_kelas' => $ampu_kelas[$i]
                ]);
            }
        }

        Session::flash('success', 'Guru berhasil ditambahkan');
        return redirect()->route('admin.guru.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nip' => 'nullable|numeric|digits:18|unique:guru,nip,' . $id . ',id_guru',
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'email' => 'required|email:dns|max:100|unique:guru,email,' . $id . ',id_guru',
            'no_hp' => 'required|numeric',
        ], [
            'nip.numeric' => 'NIP harus berupa angka.',
            'nip.digits' => 'NIP harus berjumlah tepat 18 digit.',
            'nip.unique' => 'NIP ini sudah terdaftar.',
            'email.email' => 'Format email tidak valid (contoh: nama@gmail.com).',
            'email.unique' => 'Email ini sudah digunakan oleh guru lain.',
            'no_hp.numeric' => 'No HP harus berupa angka.',
        ]);

        $guru = Guru::findOrFail($id);
        $guru->update($request->only(['nip', 'nama', 'jenis_kelamin', 'email', 'no_hp']));
        MapelDiampu::where('id_guru', $id)->delete();
        $ampu_mapel = $request->input('ampu_mapel', []);
        $ampu_kelas = $request->input('ampu_kelas', []);
        foreach ($ampu_mapel as $i => $mp) {
            if ($mp && isset($ampu_kelas[$i]) && $ampu_kelas[$i]) {
                MapelDiampu::create([
                    'id_guru' => $id,
                    'id_mapel' => $mp,
                    'id_kelas' => $ampu_kelas[$i]
                ]);
            }
        }

        Session::flash('success', 'Data guru berhasil diperbarui');
        return redirect()->route('admin.guru.index');
    }

    public function destroy($id)
    {
        MapelDiampu::where('id_guru', $id)->delete();
        Guru::destroy($id);

        Session::flash('success', 'Guru berhasil dihapus');
        return redirect()->route('admin.guru.index');
    }
}