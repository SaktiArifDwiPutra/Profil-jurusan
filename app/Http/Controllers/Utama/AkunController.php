<?php

namespace App\Http\Controllers\Utama;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AkunController extends Controller
{
    public static function middleware(): array
    {
        return [
            'role:admin' => ['only' => ['index', 'store', 'destroy']],
        ];
    }

    public function index()
    {
        $users = User::with('siswa', 'guru')
            ->orderBy('username')
            ->get();

        $siswas = Siswa::orderBy('nama')->get();
        $gurus = Guru::orderBy('nama')->get();

        return view('admin.akun.index', compact('users', 'siswas', 'gurus'));
    }

    public function store(Request $request)
    {
        $request->validate([ 
            'username' => 'required|string|max:50|unique:user,username',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,guru,siswa',
            'id_siswa' => 'nullable|required_if:role,siswa|exists:siswa,id_siswa',
            'id_guru' => 'nullable|required_if:role,guru|exists:guru,id_guru',
        ], [ 
            'username.unique' => 'Username ini sudah digunakan, silakan pilih username lain.',
            'username.required' => 'Username wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'id_siswa.required_if' => 'Silakan pilih Siswa untuk role Siswa.',
            'id_guru.required_if' => 'Silakan pilih Guru untuk role Guru.',
        ]);

        $data = [
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'id_siswa' => $request->role === 'siswa' ? $request->id_siswa : null,
            'id_guru' => $request->role === 'guru' ? $request->id_guru : null,
        ];

        User::create($data);

        Session::flash('success', 'Akun berhasil dibuat');
        return redirect()->route('admin.akun.index');
    }

    public function destroy($id)
    {
        User::destroy($id);
        Session::flash('success', 'Akun berhasil dihapus');
        return redirect()->route('admin.akun.index');
    }
}