<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaController extends Controller
{
    // ================= EXPORT PDF (SINGLE) =================
    public function exportPdf($id)
    {
        $siswa = Siswa::with('kelas')->findOrFail($id);

        $pdf = Pdf::loadView('siswa.export-pdf', compact('siswa'))
            ->setPaper('A4', 'portrait');

        $filename = 'Siswa_' . str_replace(' ', '_', $siswa->nama) . '.pdf';
        return $pdf->download($filename);
    }

    // ================= EXPORT PDF (ALL) =================
    public function exportPdfAll()
    {
        $siswas = Siswa::with('kelas')
            ->orderBy('nama')
            ->get();

        $pdf = Pdf::loadView('siswa.export-pdf-all', compact('siswas'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Data_Siswa.pdf');
    }

    // ================= EXPORT CSV (ALL) =================
    public function exportCsv()
    {
        $siswas = Siswa::with('kelas')
            ->orderBy('nama')
            ->get();

        return new StreamedResponse(function () use ($siswas) {
            $handle = fopen('php://output', 'w');

            // Header CSV
            fputcsv($handle, [
                'No',
                'NISN',
                'Nama',
                'Jenis Kelamin',
                'Tanggal Lahir',
                'Kelas',
                'No HP',
                'Alamat'
            ]);

            $no = 1;
            foreach ($siswas as $s) {
                fputcsv($handle, [
                    $no++,
                    $s->nisn,
                    $s->nama,
                    $s->jenis_kelamin,
                    $s->tanggal_lahir ?: '-',
                    $s->kelas ? $s->kelas->tingkat . ' - ' . $s->kelas->kelas : 'Belum Ada Kelas',
                    $s->no_hp,
                    $s->alamat ?: '-'
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="data_siswa.csv"',
        ]);
    }
}