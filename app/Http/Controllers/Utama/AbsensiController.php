<?php

namespace App\Http\Controllers\Utama;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AbsensiController extends Controller
{
    // Hapus middleware role:admin agar guru & siswa bisa masuk
    // public static function middleware()... (HAPUS)

    public function index(Request $request)
    {
        $user = Auth::user();

        // === 1. LOGIKA SISWA ===
        if ($user->role === 'siswa') {
            $siswa = Siswa::where('id_siswa', $user->id_siswa)->firstOrFail();
            
            // Cek absen hari ini
            $today = date('Y-m-d');
            $cek_absen = Absensi::where('id_siswa', $siswa->id_siswa)
                                ->where('tanggal', $today)
                                ->first();

            // Riwayat absen
            $riwayat = Absensi::where('id_siswa', $siswa->id_siswa)
                              ->orderBy('tanggal', 'desc')
                              ->get();

            // Statistik
            $total_absen = $riwayat->count();
            $stats = [
                'Hadir' => $riwayat->where('status', 'hadir')->count(),
                'Izin' => $riwayat->where('status', 'izin')->count(),
                'Sakit' => $riwayat->where('status', 'sakit')->count(),
                'Alpha' => $riwayat->where('status', 'alpha')->count(), // Perhatikan ejaan DB (Alpha/Alpa)
            ];

            return view('admin.absensi.index_siswa', compact('siswa', 'cek_absen', 'riwayat', 'stats', 'total_absen'));
        }

        // === 2. LOGIKA GURU (WALI KELAS) ===
        if ($user->role === 'guru') {
            // Cek apakah guru ini wali kelas
            $kelas_wali = Kelas::where('id_wali', $user->id_guru)->first();

            if (!$kelas_wali) {
                // Tampilkan view khusus "Bukan Wali Kelas"
                return view('admin.absensi.bukan_wali');
            }

            // Logic Guru Wali Kelas mirip dengan Admin, tapi terkunci di kelasnya saja
            $tanggal = $request->get('tanggal', date('Y-m-d'));
            
            // Ambil siswa hanya di kelas yg dia walikan
            $siswa = Siswa::where('id_kelas', $kelas_wali->id_kelas)
                          ->orderBy('nama')
                          ->get();

            // Ambil data absen
            $data_absen = [];
            foreach ($siswa as $s) {
                $absen = Absensi::where('id_siswa', $s->id_siswa)->where('tanggal', $tanggal)->first();
                $data_absen[$s->id_siswa] = $absen ? $absen->status : '-';
            }

            return view('admin.absensi.index_guru', compact('kelas_wali', 'siswa', 'data_absen', 'tanggal'));
        }

        // === 3. LOGIKA ADMIN (Original Code) ===
        $f_tingkat = $request->get('tingkat', '');
        $f_kelas = $request->get('kelas', '');
        $tanggal = $request->get('tanggal', date('Y-m-d'));
        
        $kelas_list = Kelas::orderBy('tingkat')->orderBy('kelas')->get();
        $unique_tingkat = $kelas_list->pluck('tingkat')->unique();
        
        $siswaQuery = Siswa::with('kelas')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->select('siswa.*', 'kelas.tingkat', 'kelas.kelas');

        if ($f_tingkat) $siswaQuery->where('kelas.tingkat', $f_tingkat);
        if ($f_kelas) $siswaQuery->where('kelas.kelas', $f_kelas);

        $siswa = $siswaQuery->orderBy('siswa.nama')->get();
        
        $data_absen = [];
        foreach ($siswa as $s) {
            $absen = Absensi::where('id_siswa', $s->id_siswa)->where('tanggal', $tanggal)->first();
            $data_absen[$s->id_siswa] = $absen ? $absen->status : '-';
        }

        $total_siswa = $siswa->count();
        $jumlah_tingkat = $unique_tingkat->count();

        return view('admin.absensi.index', compact(
            'siswa', 'kelas_list', 'unique_tingkat', 'data_absen',
            'f_tingkat', 'f_kelas', 'tanggal', 'total_siswa', 'jumlah_tingkat'
        ));
    }

    public function update(Request $request)
    {
        // Validasi akses (Siswa punya update sendiri, lihat updateSiswa di bawah)
        if (Auth::user()->role === 'siswa') {
            return $this->updateSiswa($request);
        }

        // Validasi Admin & Guru
        $request->validate([
            'id_siswa' => 'required',
            'tanggal' => 'required|date',
            'status' => 'required',
        ]);

        // Security check untuk Guru: Pastikan siswa ada di kelas walinya
        if (Auth::user()->role === 'guru') {
            $kelas_wali = Kelas::where('id_wali', Auth::user()->id_guru)->first();
            $siswa_target = Siswa::find($request->id_siswa);
            
            if (!$kelas_wali || !$siswa_target || $siswa_target->id_kelas != $kelas_wali->id_kelas) {
                abort(403, 'Anda tidak berhak mengabsen siswa ini.');
            }
        }

        // Logic Update/Create (Admin & Guru)
        // Kita gunakan updateOrCreate agar lebih ringkas
        Absensi::updateOrCreate(
            [
                'id_siswa' => $request->id_siswa,
                'tanggal' => $request->tanggal
            ],
            [
                'id_kelas' => $request->id_kelas ?? Siswa::find($request->id_siswa)->id_kelas, // fallback ambil kelas dari siswa
                'status' => $request->status
            ]
        );

        Session::flash('success', 'Data absensi berhasil diperbarui');
        return back();
    }

    // Method khusus untuk Absen Mandiri Siswa
    public function updateSiswa(Request $request)
    {
        $user = Auth::user();
        $today = date('Y-m-d');

        // Cek apakah sudah absen
        $cek = Absensi::where('id_siswa', $user->id_siswa)->where('tanggal', $today)->exists();
        if ($cek) {
            return back()->with('error', 'Anda sudah melakukan absen hari ini.');
        }

        $request->validate([
            'status' => 'required|in:Hadir,Izin,Sakit,Alpha', // Sesuaikan enum DB
        ]);

        // Ambil data siswa untuk dapat id_kelas
        $siswa = Siswa::find($user->id_siswa);

        Absensi::create([
            'id_siswa' => $user->id_siswa,
            'id_kelas' => $siswa->id_kelas,
            'tanggal' => $today,
            'status' => $request->status
        ]);

        Session::flash('success', 'Absensi berhasil dikirim.');
        return back();
    }
}