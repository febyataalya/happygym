<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Data Member</title>
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
    <h2>LAPORAN DATA MEMBER</h2>
    @if(isset($filterString))
        <p style="text-align: center; margin-top: -10px; font-size: 14px; color: #555;">{{ $filterString }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Kontak</th>
                <th>Status</th>
                <th>Paket Gym Umum</th>
                <th>Paket PT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $index => $member)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $member->nama }}</td>
                <td>{{ $member->email }}<br>{{ $member->no_hp }}</td>
                <td>{{ $member->status_membership }}</td>
                <td>
                    {{ ($member->status_membership == 'Aktif' && $member->tanggal_berakhir_member) ? 'Aktif s/d ' . \Carbon\Carbon::parse($member->tanggal_berakhir_member)->format('d M Y') : '-' }}
                </td>
                <td>
                    @if($member->paketPts && $member->paketPts->count() > 0)
                        @php $pt = $member->paketPts->first(); @endphp
                        Sisa: {{ $pt->sisa_sesi }} Sesi<br>
                        Coach: {{ $pt->instruktur ? $pt->instruktur->nama : 'Belum Ada' }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
