<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Mata Pelajaran</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .guru-cell { font-size: 9pt; }
    </style>
</head>
<body>
    <h2>Data Mata Pelajaran</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Mapel</th>
                <th>Kategori</th>
                <th>Guru Pengampu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mapels as $i => $m)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $m->nama_mapel }}</td>
                <td>{{ $m->kategori }}</td>
                <td class="guru-cell">
                    @if($m->mapelDiampu->count())
                        @foreach($m->mapelDiampu as $a)
                            {{ $a->guru->nama ?? '–' }}<br>
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