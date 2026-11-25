<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Data Siswa</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NISN</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Tgl Lahir</th>
                <th>Kelas</th>
                <th>No HP</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($siswas as $i => $s)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $s->nisn }}</td>
                <td>{{ $s->nama }}</td>
                <td>{{ $s->jenis_kelamin }}</td>
                <td>{{ $s->tanggal_lahir ?: '-' }}</td>
                <td>{{ $s->kelas ? $s->kelas->tingkat . ' - ' . $s->kelas->kelas : '–' }}</td>
                <td>{{ $s->no_hp }}</td>
                <td>{{ $s->alamat ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>