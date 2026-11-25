<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nilai Siswa - {{ $siswa->nama }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .info-box { margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-radius: 4px; }
    </style>
</head>
<body>
    <h2>Laporan Nilai Siswa</h2>
    
    <div class="info-box">
        <strong>{{ $siswa->nama }}</strong><br>
        NISN: {{ $siswa->nisn }}<br>
        Kelas: {{ $siswa->kelas ? $siswa->kelas->tingkat . ' ' . $siswa->kelas->kelas : 'Belum Ada Kelas' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Mapel</th>
                <th>Guru</th>
                <th>Semester</th>
                <th>Tugas</th>
                <th>UTS</th>
                <th>Akhir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nilaiData as $i => $n)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $n['mapel'] }}</td>
                <td>{{ $n['guru'] }}</td>
                <td>{{ $n['semester'] }}</td>
                <td>{{ $n['tugas'] }}</td>
                <td>{{ $n['uts'] }}</td>
                <td>{{ $n['akhir'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>