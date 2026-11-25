<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detail Siswa - {{ $siswa->nama }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; }
        .label { width: 30%; font-weight: bold; }
        .value { width: 70%; }
    </style>
</head>
<body>
    <h2>Detail Siswa</h2>
    <table>
        <tr><td class="label">NISN</td><td class="value">{{ $siswa->nisn }}</td></tr>
        <tr><td class="label">Nama</td><td class="value">{{ $siswa->nama }}</td></tr>
        <tr><td class="label">Jenis Kelamin</td><td class="value">{{ $siswa->jenis_kelamin }}</td></tr>
        <tr><td class="label">Tanggal Lahir</td><td class="value">{{ $siswa->tanggal_lahir ?: '-' }}</td></tr>
        <tr><td class="label">Kelas</td><td class="value">{{ $siswa->kelas ? $siswa->kelas->tingkat . ' - ' . $siswa->kelas->kelas : 'Belum Ada Kelas' }}</td></tr>
        <tr><td class="label">No HP</td><td class="value">{{ $siswa->no_hp }}</td></tr>
        <tr><td class="label">Alamat</td><td class="value">{{ $siswa->alamat ?: '-' }}</td></tr>
    </table>
</body>
</html>