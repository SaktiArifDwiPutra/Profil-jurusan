<?php

namespace App\Http\Controllers\Utama;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\MapelDiampu;
use App\Models\Nilai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NilaiController extends Controller
{
    // ... (Method index biarkan sama seperti sebelumnya) ...
    public function index()
    {
        $user = Auth::user();

        // LOGIKA SISWA
        if ($user->role === 'siswa') {
            $siswa = Siswa::with('kelas')->where('id_siswa', $user->id_siswa)->firstOrFail();
            $nilai = Nilai::with(['ampu.mapel', 'ampu.guru'])->where('id_siswa', $siswa->id_siswa)->get();
            // ... (Statistik siswa sama seperti sebelumnya) ...
            $total_mapel = $nilai->count();
            $nilai_per_semester = [];
            $total_nilai = 0;
            foreach($nilai as $n) {
                $total_nilai += $n->nilai_akhir;
                $sem = $n->semester;
                if(!isset($nilai_per_semester[$sem])) $nilai_per_semester[$sem] = ['sum'=>0, 'count'=>0];
                $nilai_per_semester[$sem]['sum'] += $n->nilai_akhir;
                $nilai_per_semester[$sem]['count']++;
            }
            $rata_rata_global = $total_mapel > 0 ? round($total_nilai/$total_mapel, 2) : 0;
            
            return view('admin.nilai.index_siswa', compact('siswa', 'nilai', 'rata_rata_global', 'total_mapel', 'nilai_per_semester'));
        }

        // LOGIKA ADMIN & GURU
        $siswas = Siswa::with('kelas')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->orderBy('kelas.tingkat')->orderBy('kelas.kelas')->orderBy('siswa.nama')
            ->select('siswa.*', 'kelas.tingkat', 'kelas.kelas')
            ->get();

        // ... (Statistik admin sama) ...
        $total_siswa = $siswas->count();
        $kelas_count = [];
        foreach ($siswas as $s) {
            $label = $s->tingkat ? $s->tingkat . ' - ' . $s->kelas : 'Belum Ada Kelas';
            $kelas_count[$label] = ($kelas_count[$label] ?? 0) + 1;
        }
        $recent = $siswas->take(-6)->values();

        return view('admin.nilai.index', compact('siswas', 'total_siswa', 'kelas_count', 'recent'));
    }

    public function show($id_siswa)
    {
        $user = Auth::user();
        if ($user->role === 'siswa' && $user->id_siswa != $id_siswa) abort(403);

        $siswa = Siswa::with('kelas')->findOrFail($id_siswa);

        // Ambil Mapel + Info Kelas (untuk filter Tahun/Tingkat)
        $mapels = MapelDiampu::with(['mapel', 'guru', 'kelas']) // Tambah load 'kelas'
            ->where('id_kelas', $siswa->id_kelas)
            ->join('mapel', 'mapel_diampu.id_mapel', '=', 'mapel.id_mapel')
            ->orderBy('mapel.nama_mapel')
            ->select('mapel_diampu.*') 
            ->get();

        // Ambil Nilai Raw
        $raw_nilai = Nilai::where('id_siswa', $id_siswa)->get();

        // Grouping Nilai: $existing_nilai[id_ampu]['Ganjil'] = ObjectNilai
        $existing_nilai = [];
        foreach($raw_nilai as $n) {
            $existing_nilai[$n->id_ampu][$n->semester] = $n;
        }

        // Logika Editable Guru
        $editable_ampu_ids = []; 
        if ($user->role === 'guru') {
            foreach ($mapels as $m) {
                if ($m->id_guru == $user->id_guru) {
                    $editable_ampu_ids[] = $m->id_ampu;
                }
            }
        } 
        
        $total_mapel = $mapels->count();

        return view('admin.nilai.show', compact(
            'siswa', 'mapels', 'existing_nilai', 
            'editable_ampu_ids', 'total_mapel'
        ));
    }

    public function update(Request $request)
    {
        if (Auth::user()->role !== 'guru' && Auth::user()->role !== 'admin') abort(403);

        $request->validate([
            'id_siswa' => 'required',
            'id_ampu' => 'required',
            'semester' => 'required|in:Ganjil,Genap', // Pastikan cuma Ganjil/Genap
            'nilai_tugas' => 'required|numeric|min:0|max:100',
            'nilai_uts' => 'required|numeric|min:0|max:100',
        ]);

        // Validasi Guru
        if (Auth::user()->role === 'guru') {
            $is_owner = MapelDiampu::where('id_ampu', $request->id_ampu)
                        ->where('id_guru', Auth::user()->id_guru)
                        ->exists();
            if (!$is_owner) abort(403, 'Akses Ditolak');
        }

        $akhir = ($request->nilai_tugas * 0.4) + ($request->nilai_uts * 0.6);

        // UPDATE OR CREATE DENGAN KUNCI SEMESTER JUGA
        Nilai::updateOrCreate(
            [
                'id_siswa' => $request->id_siswa, 
                'id_ampu' => $request->id_ampu,
                'semester' => $request->semester // <--- KUNCI PENTING: Pisahkan baris berdasarkan semester
            ],
            [
                'nilai_tugas' => $request->nilai_tugas,
                'nilai_uts' => $request->nilai_uts,
                'nilai_akhir' => $akhir
            ]
        );

        return back()->with('success', 'Nilai semester ' . $request->semester . ' berhasil disimpan');
    }

    public function destroy($id)
    {
        $nilai = Nilai::with('ampu')->findOrFail($id);
        if (Auth::user()->role === 'guru' && $nilai->ampu->id_guru !== Auth::user()->id_guru) {
            abort(403);
        }
        $nilai->delete();
        return back()->with('success', 'Nilai berhasil dihapus');
    }
}