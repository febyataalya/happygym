<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Data Personal Trainer (PT)</title>
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
    <h2>LAPORAN DATA PERSONAL TRAINER (PT)</h2>
    @if(isset($filterString))
        <p style="text-align: center; margin-top: -10px; font-size: 14px; color: #555;">{{ $filterString }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama PT</th>
                <th>Username</th>
                <th>Spesialisasi</th>
                <th>Cabang</th>
                <th>Jumlah Client Aktif</th>
                <th>Rating Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pts as $index => $pt)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $pt->nama }}</td>
                <td>{{ $pt->username }}</td>
                <td>{{ $pt->spesialisasi ?? '-' }}</td>
                <td>{{ $pt->lokasi ? $pt->lokasi->nama_cabang : '-' }}</td>
                <td>{{ $pt->active_clients_count }} Client</td>
                <td>{{ $pt->rating_avg }} / 5.0</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
