<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AkunController extends Controller
{
    // ================= EXPORT PDF (ALL) =================
    public function exportPdfAll()
    {
        $users = User::with('siswa', 'guru')
            ->orderBy('username')
            ->get();

        $pdf = Pdf::loadView('akun.export-pdf-all', compact('users'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('Data_Akun.pdf');
    }

    // ================= EXPORT CSV (ALL) =================
    public function exportCsv()
    {
        $users = User::with('siswa', 'guru')
            ->orderBy('username')
            ->get();

        return new StreamedResponse(function () use ($users) {
            $handle = fopen('php://output', 'w');

            // BOM untuk Excel (opsional tapi direkomendasikan)
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header CSV
            fputcsv($handle, [
                'No',
                'Username',
                'Role',
                'Linked To'
            ]);

            $no = 1;
            foreach ($users as $u) {
                $linked = '-';
                if ($u->role === 'siswa' && $u->siswa) {
                    $linked = 'Siswa: ' . $u->siswa->nama;
                } elseif ($u->role === 'guru' && $u->guru) {
                    $linked = 'Guru: ' . $u->guru->nama;
                } elseif ($u->role === 'admin') {
                    $linked = 'Admin';
                }

                fputcsv($handle, [
                    $no++,
                    $u->username,
                    $u->role,
                    $linked
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="data_akun.csv"',
        ]);
    }
}