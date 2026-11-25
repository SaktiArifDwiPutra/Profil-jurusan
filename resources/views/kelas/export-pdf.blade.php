<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kelas {{ $kelas->tingkat }} - {{ $kelas->kelas }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Detail Kelas</h2>
    <p>{{ $kelas->tingkat }} - {{ $kelas->kelas }}</p>
</div>

<table>
    <tr>
        <th>Tingkat</th>
        <td>{{ $kelas->tingkat }}</td>
    </tr>
    <tr>
        <th>Nama Kelas</th>
        <td>{{ $kelas->kelas }}</td>
    </tr>
    <tr>
        <th>Wali Kelas</th>
        <td>{{ $kelas->wali?->nama ?? '-' }}</td>
    </tr>
</table>

</body>
</html>