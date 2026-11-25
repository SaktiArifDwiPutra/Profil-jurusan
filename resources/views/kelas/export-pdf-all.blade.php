<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Kelas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #000; padding:6px; text-align:left; }
        th { background:#eee; }
    </style>
</head>
<body>

<h3>Data Kelas</h3>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Tingkat</th>
            <th>Kelas</th>
            <th>Wali</th>
        </tr>
    </thead>
    <tbody>
        @foreach($kelas as $i => $k)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $k->tingkat }}</td>
            <td>{{ $k->kelas }}</td>
            <td>{{ $k->wali->nama ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
