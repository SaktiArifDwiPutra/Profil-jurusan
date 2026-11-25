<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KelasController extends Controller
{
    public function index()
    {
        $kelas = Kelas::with('wali')
            ->orderBy('tingkat')
            ->orderBy('kelas')
            ->get();

        return view('guest.kelas', [
            'kelas' => $kelas,
            'total_kelas' => $kelas->count(),
            'tingkat_count' => $kelas->pluck('tingkat')->unique()->count(),
            'wali_count' => $kelas->pluck('id_wali')->filter()->unique()->count(),
            'recent' => $kelas->take(-5)
        ]);
    }

    // ================= EXPORT PDF (SINGLE) =================
    public function exportPdf($id)
    {
        $kelas = Kelas::with('wali')->findOrFail($id);

        $pdf = Pdf::loadView('kelas.export-pdf', compact('kelas'))
            ->setPaper('A4', 'portrait');

        return $pdf->download(
            'Kelas_' . $kelas->tingkat . '_' . $kelas->kelas . '.pdf'
        );
    }

    public function exportPdfAll()
    {
        $kelas = Kelas::with('wali')
            ->orderBy('tingkat')
            ->orderBy('kelas')
            ->get();

        $pdf = Pdf::loadView('kelas.export-pdf-all', compact('kelas'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Data_Kelas.pdf');
    }


    // ================= EXPORT CSV (ALL) =================
    public function exportCsv()
    {
        $kelas = Kelas::with('wali')
            ->orderBy('tingkat')
            ->orderBy('kelas')
            ->get();

        return new StreamedResponse(function () use ($kelas) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['No', 'Tingkat', 'Kelas', 'Wali']);

            $no = 1;
            foreach ($kelas as $k) {
                fputcsv($handle, [
                    $no++,
                    $k->tingkat,
                    $k->kelas,
                    $k->wali->nama ?? '-'
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="data_kelas.csv"',
        ]);
    }
}
