<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detail Mapel - {{ $mapel->nama_mapel }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; }
        .label { width: 30%; font-weight: bold; }
        .value { width: 70%; }
        .guru-list { margin-top: 15px; }
        .guru-item { margin: 4px 0; }
    </style>
</head>
<body>
    <h2>Detail Mata Pelajaran</h2>
    <table>
        <tr><td class="label">Nama Mapel</td><td class="value">{{ $mapel->nama_mapel }}</td></tr>
        <tr><td class="label">Kategori</td><td class="value">{{ $mapel->kategori }}</td></tr>
    </table>

    <div class="guru-list">
        <p><strong>Guru Pengampu:</strong></p>
        @if($mapel->mapelDiampu->count())
            @foreach($mapel->mapelDiampu as $a)
                <div class="guru-item">• {{ $a->guru->nama ?? 'Guru tidak ditemukan' }}</div>
            @endforeach
        @else
            <div class="guru-item">Belum ada guru pengampu</div>
        @endif
    </div>
</body>
</html>