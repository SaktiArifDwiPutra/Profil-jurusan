<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Guru</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .ampu-cell { font-size: 9pt; }
    </style>
</head>
<body>
    <h2>Data Guru</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Email</th>
                <th>No HP</th>
                <th>Mapel Diampu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gurus as $i => $g)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $g->nip ?: '-' }}</td>
                <td>{{ $g->nama }}</td>
                <td>{{ $g->jenis_kelamin }}</td>
                <td>{{ $g->email }}</td>
                <td>{{ $g->no_hp }}</td>
                <td class="ampu-cell">
                    @if($g->mapelDiampu->count())
                        @foreach($g->mapelDiampu as $a)
                            {{ $a->mapel->nama_mapel }} ({{ $a->kelas->tingkat }} {{ $a->kelas->kelas }})<br>
                        @endforeach
                    @else
                        –
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>