<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detail Guru - {{ $guru->nama }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; }
        .label { width: 30%; font-weight: bold; }
        .value { width: 70%; }
        .ampu-list { margin-top: 10px; }
        .ampu-item { margin: 4px 0; }
    </style>
</head>
<body>
    <h2>Detail Guru</h2>
    <table>
        <tr><td class="label">NIP</td><td class="value">{{ $guru->nip ?: '-' }}</td></tr>
        <tr><td class="label">Nama</td><td class="value">{{ $guru->nama }}</td></tr>
        <tr><td class="label">Jenis Kelamin</td><td class="value">{{ $guru->jenis_kelamin }}</td></tr>
        <tr><td class="label">Email</td><td class="value">{{ $guru->email }}</td></tr>
        <tr><td class="label">No HP</td><td class="value">{{ $guru->no_hp }}</td></tr>
    </table>

    <div class="ampu-list">
        <p><strong>Mapel Diampu:</strong></p>
        @if($guru->mapelDiampu->count())
            @foreach($guru->mapelDiampu as $a)
                <div class="ampu-item">• {{ $a->mapel->nama_mapel }} ({{ $a->kelas->tingkat }} {{ $a->kelas->kelas }})</div>
            @endforeach
        @else
            <div class="ampu-item">Tidak ada mapel diampu</div>
        @endif
    </div>
</body>
</html>