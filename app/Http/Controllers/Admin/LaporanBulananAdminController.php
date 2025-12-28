<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\JobdeskTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanBulananAdminController extends Controller
{
    /**
     * Rekap Pekerjaan (Bulanan)
     * Filter: Month + Year, Division, User, Status
     */
    public function index(Request $r)
    {
        $now = Carbon::now('Asia/Jakarta');

        $reqMonth = $r->query('month');
        $reqYear  = $r->query('year');

        // ✅ Jika month & year dipilih => ambil range bulan tersebut
        if ($reqMonth && $reqYear) {
            $dt = Carbon::createFromDate($reqYear, $reqMonth, 1);
            $dateFrom = $dt->copy()->startOfMonth()->toDateString();
            $dateTo   = $dt->copy()->endOfMonth()->toDateString();
        } else {
            // ✅ Default: bulan ini
            $dateFrom = $r->query('date_from', $now->copy()->startOfMonth()->toDateString());
            $dateTo   = $r->query('date_to', $now->copy()->endOfMonth()->toDateString());
        }

        $filters = [
            'user_id'   => $r->query('user_id'),
            'division'  => $r->query('division'),
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'month'     => $reqMonth,
            'year'      => $reqYear,
            'status'    => $r->query('status'),
        ];

        // ✅ Base query
        $base = $this->buildBaseQuery($filters);

        // ✅ Data table
        $rows = (clone $base)
            ->orderByDesc('schedule_date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // ✅ Summary sesuai filter (FIX: hitung semua status)
        $total = (clone $base)->count();
        $summary = ['total' => $total];

        $statusList = ['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'];
        foreach ($statusList as $st) {
            $summary[$st] = (clone $base)->where('status', $st)->count();
        }

        // ✅ Teams hanya USER (bukan admin)
        $users = User::where(function ($q) {
            $q->whereNull('role')
                ->orWhere('role', 'user');
        })
            ->orderBy('name')
            ->get();

        return view('admin.laporanbulanan.bulananAdmin', [
            'rows'    => $rows,
            'summary' => $summary,
            'filters' => $filters,
            'users'   => $users,
        ]);
    }

    /**
     * Export PDF Rekap Bulanan
     */
    public function exportPdf(Request $r)
    {
        $now = Carbon::now('Asia/Jakarta');

        $reqMonth = $r->query('month');
        $reqYear  = $r->query('year');

        if ($reqMonth && $reqYear) {
            $dt = Carbon::createFromDate($reqYear, $reqMonth, 1);
            $dateFrom = $dt->copy()->startOfMonth()->toDateString();
            $dateTo   = $dt->copy()->endOfMonth()->toDateString();
        } else {
            $dateFrom = $r->query('date_from', $now->copy()->startOfMonth()->toDateString());
            $dateTo   = $r->query('date_to', $now->copy()->endOfMonth()->toDateString());
        }

        $filters = [
            'user_id'   => $r->query('user_id'),
            'division'  => $r->query('division'),
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'status'    => $r->query('status'),
        ];

        $base = $this->buildBaseQuery($filters);

        $rows = (clone $base)
            ->orderByDesc('schedule_date')
            ->orderByDesc('created_at')
            ->get();

        // ✅ Summary untuk PDF (FIX: hitung semua status)
        $total = $rows->count();
        $summary = ['total' => $total];

        $statusList = ['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'];
        foreach ($statusList as $st) {
            $summary[$st] = $rows->where('status', $st)->count();
        }

        $dF = Carbon::parse($dateFrom)->format('d-m-Y');
        $dT = Carbon::parse($dateTo)->format('d-m-Y');
        $periodeLabel = "$dF s.d $dT";

        $pdf = Pdf::loadView('admin.laporanbulanan.pdf', [
            'rows'    => $rows,
            'summary' => $summary,
            'bulan'   => $periodeLabel,
        ])->setPaper('a4', 'portrait');

        $filename = 'rekap-pekerjaan-' . $dateFrom . '-to-' . $dateTo . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Base query builder
     */
    private function buildBaseQuery(array $filters)
    {
        $q = JobdeskTask::with(['photos', 'jobdesk.user']);

        // ✅ Fix Duplicate: Exclude template tasks
        $q->where(function ($w) {
            $w->where('is_template', 0)->orWhereNull('is_template');
        });

        // ✅ Filter status (optional)
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        // ✅ Filter tanggal
        $start = $filters['date_from'] ?? null;
        $end   = $filters['date_to'] ?? null;

        if ($start && $end) {
            $q->where(function ($sq) use ($start, $end) {
                $sq->whereBetween(DB::raw('DATE(schedule_date)'), [$start, $end])
                    ->orWhere(function ($qq) use ($start, $end) {
                        $qq->whereNull('schedule_date')
                            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
                    });
            });
        }

        // ✅ Filter division
        if (!empty($filters['division'])) {
            $v = $filters['division'];
            $q->whereHas('jobdesk', fn($w) => $w->where('division', $v));
        }

        /**
         * ✅ Filter teams hanya USER (bukan admin)
         * jika admin dipaksa lewat URL => ditolak
         */
        if (!empty($filters['user_id'])) {

            $safeUser = User::where('id', $filters['user_id'])
                ->where(function ($qq) {
                    $qq->whereNull('role')
                        ->orWhere('role', 'user');
                })
                ->exists();

            if ($safeUser) {
                $q->whereHas('jobdesk', fn($w) => $w->where('user_id', $filters['user_id']));
            }
        }

        return $q;
    }
}
