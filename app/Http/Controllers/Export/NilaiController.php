<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\Nilai;
use App\Models\MapelDiampu;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NilaiController extends Controller
{
    // ================= EXPORT PDF (SINGLE) =================
public function exportPdf($id_siswa)
{
    $siswa = Siswa::with('kelas')->findOrFail($id_siswa);

    // ✅ Perbaiki query dengan join eksplisit
    $mapels = MapelDiampu::with('mapel', 'guru')
        ->join('mapel', 'mapel_diampu.id_mapel', '=', 'mapel.id_mapel')
        ->where('mapel_diampu.id_kelas', $siswa->id_kelas)
        ->orderBy('mapel.nama_mapel')
        ->select('mapel_diampu.*')
        ->get();

    $nilaiData = [];
    foreach ($mapels as $m) {
        $nilai = Nilai::where('id_siswa', $id_siswa)
            ->where('id_ampu', $m->id_ampu)
            ->orderBy('semester', 'desc')
            ->first();
        $nilaiData[] = [
            'mapel' => $m->mapel->nama_mapel,
            'guru' => $m->guru?->nama ?? '-',
            'semester' => $nilai?->semester ?? '-',
            'tugas' => $nilai?->nilai_tugas ?? '-',
            'uts' => $nilai?->nilai_uts ?? '-',
            'akhir' => $nilai?->nilai_akhir ?? '-',
        ];
    }

    $pdf = Pdf::loadView('nilai.export-pdf', compact('siswa', 'nilaiData'))
        ->setPaper('A4', 'portrait');

    $filename = 'Nilai_' . str_replace(' ', '_', $siswa->nama) . '.pdf';
    return $pdf->download($filename);
}

// ================= EXPORT CSV (SINGLE) =================
public function exportCsv($id_siswa = null)
{
    if (!$id_siswa) {
        return redirect()->route('admin.nilai.index');
    }

    $siswa = Siswa::with('kelas')->findOrFail($id_siswa);

    // ✅ Perbaiki query dengan join eksplisit
    $mapels = MapelDiampu::with('mapel', 'guru')
        ->join('mapel', 'mapel_diampu.id_mapel', '=', 'mapel.id_mapel')
        ->where('mapel_diampu.id_kelas', $siswa->id_kelas)
        ->orderBy('mapel.nama_mapel')
        ->select('mapel_diampu.*')
        ->get();

    return new StreamedResponse(function () use ($siswa, $mapels) {
        $handle = fopen('php://output', 'w');

        fputcsv($handle, [
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Mapel',
            'Guru',
            'Semester',
            'Nilai Tugas',
            'Nilai UTS',
            'Nilai Akhir'
        ]);

        foreach ($mapels as $m) {
            $nilai = Nilai::where('id_siswa', $siswa->id_siswa)
                ->where('id_ampu', $m->id_ampu)
                ->orderBy('semester', 'desc')
                ->first();

            fputcsv($handle, [
                $siswa->nisn,
                $siswa->nama,
                $siswa->kelas ? $siswa->kelas->tingkat . ' ' . $siswa->kelas->kelas : 'Belum Ada Kelas',
                $m->mapel->nama_mapel,
                $m->guru?->nama ?? '-',
                $nilai?->semester ?? '-',
                $nilai?->nilai_tugas ?? '-',
                $nilai?->nilai_uts ?? '-',
                $nilai?->nilai_akhir ?? '-',
            ]);
        }

        fclose($handle);
    }, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="nilai_' . str_replace(' ', '_', $siswa->nama) . '.csv"',
    ]);
}
}