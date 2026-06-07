<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Data Kehadiran Member</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>LAPORAN KEHADIRAN MEMBER</h2>
    @if(isset($filterString))
        <p style="text-align: center; margin-top: -10px; font-size: 14px; color: #555;">{{ $filterString }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Member</th>
                <th>Cabang</th>
                <th>Waktu Masuk</th>
                <th>Waktu Keluar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kehadirans as $index => $k)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($k->tanggal)->format('d/m/Y') }}</td>
                <td>{{ $k->member->nama ?? '-' }}</td>
                <td>{{ $k->lokasi ? $k->lokasi->nama_cabang : '-' }}</td>
                <td>{{ $k->waktu_masuk ?? '-' }}</td>
                <td>{{ $k->waktu_keluar ?? '-' }}</td>
                <td>{{ $k->status_kunjungan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
