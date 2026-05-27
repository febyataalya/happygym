<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Pembayaran;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Carbon\Carbon;

class LaporanController extends Controller
{
    // ==========================================
    // LAPORAN MEMBER
    // ==========================================
    public function memberIndex(Request $request)
    {
        $lokasis = Lokasi::all();
        $query = Member::with(['paketPts.instruktur']);

        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
        }
        
        if ($request->filled('bulan')) {
            $query->whereMonth('created_at', $request->bulan);
        }

        if ($request->filled('status_membership')) {
            $status = $request->status_membership;
            if ($status == 'Aktif') {
                $query->where('status_membership', 'Aktif')->whereDate('tanggal_berakhir_member', '>=', now());
            } elseif ($status == 'Tidak Aktif') {
                $query->where(function($q) {
                    $q->where('status_membership', '!=', 'Aktif')
                      ->orWhereNull('tanggal_berakhir_member')
                      ->orWhereDate('tanggal_berakhir_member', '<', now());
                });
            } elseif (strpos($status, 'paket_') === 0) {
                $paketId = str_replace('paket_', '', $status);
                $query->whereHas('pembayarans', function($q) use ($paketId) {
                    $q->where('status', 'settlement')
                      ->whereHas('pemesanan', function($q2) use ($paketId) {
                          $q2->where('paket_id', $paketId);
                      });
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        // Stats before pagination
        $statsQuery = clone $query;
        $totalMember = $statsQuery->count();
        $memberAktif = (clone $statsQuery)->where('status_membership', 'Aktif')->whereDate('tanggal_berakhir_member', '>=', now())->count();
        $memberTidakAktif = $totalMember - $memberAktif;

        $members = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $pakets = \App\Models\Paket::all();

        return view('laporan.member', compact('members', 'lokasis', 'totalMember', 'memberAktif', 'memberTidakAktif', 'pakets'));
    }

    public function exportMemberExcel(Request $request)
    {
        $query = Member::with(['paketPts.instruktur']);
        
        $filterText = [];
        
        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
            $lokasi = Lokasi::find($request->lokasi_id);
            $filterText[] = "Cabang: " . ($lokasi ? $lokasi->nama_cabang : $request->lokasi_id);
        } else {
            $filterText[] = "Cabang: Semua";
        }
        
        if ($request->filled('bulan')) {
            $query->whereMonth('created_at', $request->bulan);
            $filterText[] = "Bulan: " . date("F", mktime(0, 0, 0, $request->bulan, 10));
        }

        if ($request->filled('status_membership')) {
            $status = $request->status_membership;
            if ($status == 'Aktif') {
                $query->where('status_membership', 'Aktif')->whereDate('tanggal_berakhir_member', '>=', now());
                $filterText[] = "Status Membership: Aktif";
            } elseif ($status == 'Tidak Aktif') {
                $query->where(function($q) {
                    $q->where('status_membership', '!=', 'Aktif')
                      ->orWhereNull('tanggal_berakhir_member')
                      ->orWhereDate('tanggal_berakhir_member', '<', now());
                });
                $filterText[] = "Status Membership: Tidak Aktif";
            } elseif (strpos($status, 'paket_') === 0) {
                $paketId = str_replace('paket_', '', $status);
                $query->whereHas('pembayarans', function($q) use ($paketId) {
                    $q->where('status', 'settlement')
                      ->whereHas('pemesanan', function($q2) use ($paketId) {
                          $q2->where('paket_id', $paketId);
                      });
                });
                $paketData = \App\Models\Paket::find($paketId);
                $filterText[] = "Paket: " . ($paketData ? $paketData->nama_paket : $paketId);
            }
        } else {
            $filterText[] = "Status Membership: Semua";
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
            $filterText[] = "Pencarian: " . $search;
        }

        $members = $query->orderBy('created_at', 'desc')->get();
        $filterString = implode(' | ', $filterText);

        return Excel::download(new class($members, $filterString) implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents {
            private $members;
            private $filterString;

            public function __construct($members, $filterString) {
                $this->members = $members;
                $this->filterString = $filterString;
            }

            public function collection() {
                return $this->members;
            }

            public function startCell(): string {
                return 'A4';
            }

            public function registerEvents(): array {
                return [
                    BeforeSheet::class => function(BeforeSheet $event) {
                        $event->sheet->setCellValue('A1', 'LAPORAN DATA MEMBER');
                        $event->sheet->setCellValue('A2', $this->filterString);
                        $event->sheet->mergeCells('A1:F1');
                        $event->sheet->mergeCells('A2:F2');
                    }
                ];
            }

            public function headings(): array {
                return [
                    'No',
                    'Nama',
                    'Kontak (Email/HP)',
                    'Status Membership',
                    'Paket Gym Umum',
                    'Paket Personal Trainer'
                ];
            }

            public function map($member): array {
                static $row = 0;
                $row++;

                $status_gym = ($member->status_membership == 'Aktif' && $member->tanggal_berakhir_member && \Carbon\Carbon::parse($member->tanggal_berakhir_member)->isFuture()) 
                    ? 'Aktif s/d ' . \Carbon\Carbon::parse($member->tanggal_berakhir_member)->format('d M Y') 
                    : 'Tidak Aktif';
                
                $pt_info = '-';
                if ($member->paketPts && $member->paketPts->count() > 0) {
                    $pt = $member->paketPts->first();
                    $pt_info = $pt->status == 'Aktif' 
                        ? 'Aktif (' . $pt->sisa_sesi . ' sesi) - Coach ' . ($pt->instruktur ? $pt->instruktur->nama : '-')
                        : 'Habis';
                }

                return [
                    $row,
                    $member->nama,
                    $member->email . ' / ' . $member->no_hp,
                    $member->status_membership,
                    $status_gym,
                    $pt_info
                ];
            }
        }, 'laporan_member_'.date('Ymd_His').'.xlsx');
    }

    public function exportMemberPdf(Request $request)
    {
        $query = Member::with(['paketPts.instruktur']);
        $filterText = [];
        
        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
            $lokasi = Lokasi::find($request->lokasi_id);
            $filterText[] = "Cabang: " . ($lokasi ? $lokasi->nama_cabang : $request->lokasi_id);
        } else {
            $filterText[] = "Cabang: Semua";
        }
        
        if ($request->filled('bulan')) {
            $query->whereMonth('created_at', $request->bulan);
            $filterText[] = "Bulan: " . date("F", mktime(0, 0, 0, $request->bulan, 10));
        }

        if ($request->filled('status_membership')) {
            $status = $request->status_membership;
            if ($status == 'Aktif') {
                $query->where('status_membership', 'Aktif')->whereDate('tanggal_berakhir_member', '>=', now());
                $filterText[] = "Status Membership: Aktif";
            } elseif ($status == 'Tidak Aktif') {
                $query->where(function($q) {
                    $q->where('status_membership', '!=', 'Aktif')
                      ->orWhereNull('tanggal_berakhir_member')
                      ->orWhereDate('tanggal_berakhir_member', '<', now());
                });
                $filterText[] = "Status Membership: Tidak Aktif";
            } elseif (strpos($status, 'paket_') === 0) {
                $paketId = str_replace('paket_', '', $status);
                $query->whereHas('pembayarans', function($q) use ($paketId) {
                    $q->where('status', 'settlement')
                      ->whereHas('pemesanan', function($q2) use ($paketId) {
                          $q2->where('paket_id', $paketId);
                      });
                });
                $paketData = \App\Models\Paket::find($paketId);
                $filterText[] = "Paket: " . ($paketData ? $paketData->nama_paket : $paketId);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
            $filterText[] = "Pencarian: " . $search;
        }

        $members = $query->orderBy('created_at', 'desc')->get();
        $filterString = count($filterText) > 0 ? implode(' | ', $filterText) : "Semua Data";

        $pdf = Pdf::loadView('member.pdf', compact('members', 'filterString'));
        return $pdf->download('laporan_member_'.date('Ymd_His').'.pdf');
    }

    // ==========================================
    // LAPORAN TRANSAKSI
    // ==========================================
    public function transaksiIndex(Request $request)
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

        // Stats and stats collection before pagination
        $statsQuery = clone $query;
        $allSettled = (clone $statsQuery)->where('status', 'settlement')->with(['pemesanan.paket'])->get();
        $totalPendapatan = $allSettled->sum('jumlah');

        $statistikPaket = $allSettled
            ->filter(function($t) { return $t->pemesanan && $t->pemesanan->paket; })
            ->groupBy(function($t) { return $t->pemesanan->paket->nama_paket; })
            ->map(function($group) {
                return (object)[
                    'nama_paket' => $group->first()->pemesanan->paket->nama_paket,
                    'total' => $group->count()
                ];
            })
            ->sortByDesc('total')
            ->values();

        $maxPaket = $statistikPaket->max('total') ?: 1;

        $transaksis = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('laporan.transaksi', compact('transaksis', 'lokasis', 'totalPendapatan', 'statistikPaket', 'maxPaket'));
    }

    public function exportTransaksiExcel(Request $request)
    {
        $query = Pembayaran::with(['member.lokasi', 'pemesanan.paket']);
        $filterText = [];
        
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
            $filterText[] = "Dari: " . $request->tanggal_mulai;
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_selesai);
            $filterText[] = "Sampai: " . $request->tanggal_selesai;
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
            $filterText[] = "Status: " . $request->status;
        } else {
            $filterText[] = "Status: Semua";
        }
        
        if ($request->filled('lokasi_id')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('lokasi_id', $request->lokasi_id);
            });
            $lokasi = Lokasi::find($request->lokasi_id);
            $filterText[] = "Cabang: " . ($lokasi ? $lokasi->nama_cabang : $request->lokasi_id);
        } else {
            $filterText[] = "Cabang: Semua";
        }

        $data = $query->orderBy('created_at', 'desc')->get();
        $filterString = implode(' | ', $filterText);

        return Excel::download(new class($data, $filterString) implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents {
            private $data;
            private $filterString;

            public function __construct($data, $filterString) {
                $this->data = $data;
                $this->filterString = $filterString;
            }

            public function collection() {
                return $this->data;
            }

            public function startCell(): string {
                return 'A4';
            }

            public function registerEvents(): array {
                return [
                    BeforeSheet::class => function(BeforeSheet $event) {
                        $event->sheet->setCellValue('A1', 'LAPORAN TRANSAKSI');
                        $event->sheet->setCellValue('A2', $this->filterString);
                        $event->sheet->mergeCells('A1:I1');
                        $event->sheet->mergeCells('A2:I2');
                    }
                ];
            }

            public function headings(): array {
                return [
                    'No',
                    'Order ID',
                    'Tanggal',
                    'Member',
                    'Cabang',
                    'Paket',
                    'Jumlah',
                    'Status',
                    'Metode'
                ];
            }

            public function map($t): array {
                static $row = 0;
                $row++;

                return [
                    $row,
                    $t->order_id,
                    $t->created_at->format('d/m/Y H:i'),
                    $t->member->nama ?? '-',
                    $t->member->lokasi->nama_cabang ?? '-',
                    $t->pemesanan->paket->nama_paket ?? '-',
                    $t->jumlah,
                    $t->status,
                    $t->metode
                ];
            }
        }, 'laporan_transaksi_'.date('YmdHis').'.xlsx');
    }

    public function exportTransaksiPdf(Request $request)
    {
        $query = Pembayaran::with(['member.lokasi', 'pemesanan.paket']);
        $filterText = [];
        
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
            $filterText[] = "Dari: " . $request->tanggal_mulai;
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_selesai);
            $filterText[] = "Sampai: " . $request->tanggal_selesai;
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
            $filterText[] = "Status: " . $request->status;
        }
        if ($request->filled('lokasi_id')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('lokasi_id', $request->lokasi_id);
            });
            $lokasi = Lokasi::find($request->lokasi_id);
            $filterText[] = "Cabang: " . ($lokasi ? $lokasi->nama_cabang : $request->lokasi_id);
        }

        $transaksis = $query->orderBy('created_at', 'desc')->get();
        $filterString = count($filterText) > 0 ? implode(' | ', $filterText) : "Semua Data";

        $pdf = Pdf::loadView('transaksi.pdf', compact('transaksis', 'filterString'))->setPaper('a4', 'landscape');
        return $pdf->download('laporan_transaksi_'.date('YmdHis').'.pdf');
    }
}
