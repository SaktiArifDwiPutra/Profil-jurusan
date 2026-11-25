<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuruController extends Controller
{
    // ================= EXPORT PDF (SINGLE) =================
    public function exportPdf($id)
    {
        $guru = Guru::with('mapelDiampu.mapel', 'mapelDiampu.kelas')->findOrFail($id);

        $pdf = Pdf::loadView('guru.export-pdf', compact('guru'))
            ->setPaper('A4', 'portrait');

        $filename = 'Guru_' . str_replace(' ', '_', $guru->nama) . '.pdf';
        return $pdf->download($filename);
    }

    // ================= EXPORT PDF (ALL) =================
    public function exportPdfAll()
    {
        $gurus = Guru::with('mapelDiampu.mapel', 'mapelDiampu.kelas')
            ->orderBy('nama')
            ->get();

        $pdf = Pdf::loadView('guru.export-pdf-all', compact('gurus'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Data_Guru.pdf');
    }

    // ================= EXPORT CSV (ALL) =================
    public function exportCsv()
    {
        $gurus = Guru::with('mapelDiampu.mapel', 'mapelDiampu.kelas')
            ->orderBy('nama')
            ->get();

        return new StreamedResponse(function () use ($gurus) {
            $handle = fopen('php://output', 'w');

            // Header CSV
            fputcsv($handle, [
                'No',
                'NIP',
                'Nama',
                'Jenis Kelamin',
                'Email',
                'No HP',
                'Mapel Diampu'
            ]);

            $no = 1;
            foreach ($gurus as $g) {
                // Gabungkan mapel diampu jadi string
                $mapelList = $g->mapelDiampu->map(function ($a) {
                    return $a->mapel->nama_mapel . ' (' . $a->kelas->tingkat . ' ' . $a->kelas->kelas . ')';
                })->implode('; ');

                fputcsv($handle, [
                    $no++,
                    $g->nip ?: '-',
                    $g->nama,
                    $g->jenis_kelamin,
                    $g->email,
                    $g->no_hp,
                    $mapelList ?: 'Tidak ada'
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="data_guru.csv"',
        ]);
    }
}