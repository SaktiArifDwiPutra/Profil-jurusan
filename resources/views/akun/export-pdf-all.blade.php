<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Akun</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Data Akun Pengguna</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Role</th>
                <th>Linked To</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $i => $u)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $u->username }}</td>
                <td>{{ $u->role }}</td>
                <td>
                    @if($u->role === 'siswa' && $u->siswa)
                        Siswa: {{ $u->siswa->nama }}
                    @elseif($u->role === 'guru' && $u->guru)
                        Guru: {{ $u->guru->nama }}
                    @elseif($u->role === 'admin')
                        Admin
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>