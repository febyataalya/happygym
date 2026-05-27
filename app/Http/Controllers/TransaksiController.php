<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $lokasis = Lokasi::all();
        $query = Pembayaran::with(['member.lokasi', 'pemesanan.paket']);

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_selesai);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('lokasi_id')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('lokasi_id', $request->lokasi_id);
            });
        }

        $transaksis = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('transaksi.index', compact('transaksis', 'lokasis'));
    }

    public function exportExcel(Request $request)
    {
        $query = Pembayaran::with(['member.lokasi', 'pemesanan.paket']);
        
        if ($request->filled('tanggal_mulai')) $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        if ($request->filled('tanggal_selesai')) $query->whereDate('created_at', '<=', $request->tanggal_selesai);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('lokasi_id')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('lokasi_id', $request->lokasi_id);
            });
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        $filename = "laporan_transaksi_" . date('Ymd') . ".csv";
        $handle = fopen('php://output', 'w');
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        ob_start();
        fputcsv($handle, ['No', 'Order ID', 'Tanggal', 'Member', 'Cabang', 'Paket', 'Jumlah', 'Status', 'Metode']);

        foreach ($data as $i => $t) {
            fputcsv($handle, [
                $i + 1,
                $t->order_id,
                $t->created_at->format('d/m/Y H:i'),
                $t->member->nama,
                $t->member->lokasi->nama_cabang ?? '-',
                $t->pemesanan->paket->nama_paket ?? '-',
                $t->jumlah,
                $t->status,
                $t->metode
            ]);
        }
        fclose($handle);
        return \Response::make(ob_get_clean(), 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $query = Pembayaran::with(['member.lokasi', 'pemesanan.paket']);
        
        if ($request->filled('tanggal_mulai')) $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        if ($request->filled('tanggal_selesai')) $query->whereDate('created_at', '<=', $request->tanggal_selesai);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('lokasi_id')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('lokasi_id', $request->lokasi_id);
            });
        }

        $transaksis = $query->orderBy('created_at', 'desc')->get();
        $pdf = Pdf::loadView('transaksi.pdf', compact('transaksis'));
        return $pdf->download('laporan_transaksi_'.date('YmdHis').'.pdf');
    }

    public function show($id)
    {
        $transaksi = Pembayaran::with(['member', 'pemesanan.paket'])->findOrFail($id);
        return view('transaksi.show', compact('transaksi'));
    }
}