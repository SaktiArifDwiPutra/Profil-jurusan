<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Mapel;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MapelController extends Controller
{
    // ================= EXPORT PDF (SINGLE) =================
    public function exportPdf($id)
    {
        $mapel = Mapel::with('mapelDiampu.guru')->findOrFail($id);

        $pdf = Pdf::loadView('mapel.export-pdf', compact('mapel'))
            ->setPaper('A4', 'portrait');

        $filename = 'Mapel_' . str_replace(' ', '_', $mapel->nama_mapel) . '.pdf';
        return $pdf->download($filename);
    }

    // ================= EXPORT PDF (ALL) =================
    public function exportPdfAll()
    {
        $mapels = Mapel::with('mapelDiampu.guru')
            ->orderBy('kategori')
            ->orderBy('nama_mapel')
            ->get();

        $pdf = Pdf::loadView('mapel.export-pdf-all', compact('mapels'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Data_Mapel.pdf');
    }

    // ================= EXPORT CSV (ALL) =================
    public function exportCsv()
    {
        $mapels = Mapel::with('mapelDiampu.guru')
            ->orderBy('kategori')
            ->orderBy('nama_mapel')
            ->get();

        return new StreamedResponse(function () use ($mapels) {
            $handle = fopen('php://output', 'w');

            // Header CSV
            fputcsv($handle, [
                'No',
                'Nama Mapel',
                'Kategori',
                'Guru Pengampu'
            ]);

            $no = 1;
            foreach ($mapels as $m) {
                // Gabungkan nama guru pengampu
                $guruList = $m->mapelDiampu->map(fn($a) => $a->guru->nama ?? '–')->implode('; ');

                fputcsv($handle, [
                    $no++,
                    $m->nama_mapel,
                    $m->kategori,
                    $guruList ?: 'Belum ada guru'
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="data_mapel.csv"',
        ]);
    }
}