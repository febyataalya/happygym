<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi Happy Gym</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0; color: #e45151; }
        .text-center { text-align: center; }
        .status-br { color: green; font-weight: bold; }
        .footer { margin-top: 20px; text-align: right; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN TRANSAKSI HAPPY GYM</h2>
        @if(isset($filterString))
            <p style="margin-top: 5px; font-size: 14px; color: #555;">{{ $filterString }}</p>
        @endif
        <p>Tanggal Cetak: {{ date('d F Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Order ID</th>
                <th>Tanggal</th>
                <th>Member</th>
                <th>Cabang</th>
                <th>Paket</th>
                <th>Jumlah</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($transaksis as $index => $t)
            @php $total += $t->jumlah; @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $t->order_id }}</td>
                <td>{{ $t->created_at->format('d/m/Y') }}</td>
                <td>{{ $t->member->nama }}</td>
                <td>{{ $t->member->lokasi->nama_cabang ?? '-' }}</td>
                <td>{{ $t->pemesanan->paket->nama_paket ?? '-' }}</td>
                <td>Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                <td class="text-center">{{ strtoupper($t->status) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="6" style="text-align: right;">TOTAL PENDAPATAN</td>
                <td colspan="2">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak otomatis oleh Sistem Administrasi Happy Gym
    </div>
</body>
</html>
