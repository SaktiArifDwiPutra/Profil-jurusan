<?php

namespace App\Http\Controllers\Utama;

use App\Http\Controllers\Controller;
use App\Models\Mapel;
use App\Models\MapelDiampu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class MapelController extends Controller
{
    public static function middleware(): array
    {
        return [
            'role:admin' => ['only' => ['store', 'update', 'destroy']],
        ];
    }

    public function index()
    {
        $mapels = Mapel::with('mapelDiampu.guru')->orderBy('nama_mapel')->get();

        // Statistik
        $total_mapel = $mapels->count();
        $kategori_count = [];
        foreach ($mapels as $m) {
            $kategori_count[$m->kategori] = ($kategori_count[$m->kategori] ?? 0) + 1;
        }
        $recent = $mapels->take(-6)->values(); // 6 terakhir

        return view('admin.mapel.index', compact(
            'mapels', 'total_mapel', 'kategori_count', 'recent'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'kategori' => 'required|in:Produktif,Umum',
        ], [
            'nama_mapel.required' => 'Nama mata pelajaran wajib diisi.',
            'kategori.required' => 'Kategori harus dipilih.',
        ]);

        Mapel::create($request->only(['nama_mapel', 'kategori']));

        Session::flash('success', 'Mapel berhasil ditambahkan');
        return redirect()->route('admin.mapel.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'kategori' => 'required|in:Produktif,Umum',
        ], [
            'nama_mapel.required' => 'Nama mata pelajaran wajib diisi.',
            'kategori.required' => 'Kategori harus dipilih.',
        ]);

        $mapel = Mapel::findOrFail($id);
        $mapel->update($request->only(['nama_mapel', 'kategori']));

        Session::flash('success', 'Mapel berhasil diperbarui');
        return redirect()->route('admin.mapel.index');
    }

    public function destroy($id)
    {
        Mapel::destroy($id);
        Session::flash('success', 'Mapel berhasil dihapus');
        return redirect()->route('admin.mapel.index');
    }
}