<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\JobdeskTask;

// PDF
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanHarianAdminController extends Controller
{
    public function index(Request $r)
    {
        $filters = [
            'q'         => $r->query('q'),
            'user_id'   => $r->query('user_id'),
            'division'  => $r->query('division'),
            'status'    => $r->query('status'),
            'date_from' => $r->query('date_from'),
            'date_to'   => $r->query('date_to'),
        ];

        $base = JobdeskTask::with(['photos', 'jobdesk.user']);
        $this->applyFilters($base, $filters);

        $rows = (clone $base)
            ->orderByDesc('schedule_date')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        $summary = ['total' => (clone $base)->count()];
        foreach (['to_do','pending','in_progress','verification','rework','delayed','cancelled','done'] as $st) {
            $summary[$st] = (clone $base)->where('status', $st)->count();
        }

        /**
         * ✅ PERBAIKAN:
         * Dropdown Teams hanya menampilkan user biasa (bukan admin).
         *
         * Asumsi struktur tabel users ada kolom role (admin/user).
         * Jika nama kolomnya berbeda (misal: is_admin, level, type) bilang ya, nanti aku sesuaikan.
         */
        $users = User::where(function ($q) {
                $q->whereNull('role')
                  ->orWhere('role', 'user');
            })
            ->orderBy('name')
            ->get();

        return view('admin.laporanharian.harianAdmin', [
            'rows'    => $rows,
            'summary' => $summary,
            'users'   => $users,
            'filters' => $filters,
        ]);
    }

    public function exportPdf(Request $r)
    {
        $filters = [
            'q'         => $r->query('q'),
            'user_id'   => $r->query('user_id'),
            'division'  => $r->query('division'),
            'status'    => $r->query('status'),
            'date_from' => $r->query('date_from'),
            'date_to'   => $r->query('date_to'),
        ];

        $base = JobdeskTask::with(['photos', 'jobdesk.user']);
        $this->applyFilters($base, $filters);

        $rows = (clone $base)
            ->orderByDesc('schedule_date')
            ->orderBy('start_time')
            ->get();

        $summary = ['total' => (clone $base)->count()];
        foreach (['to_do','pending','in_progress','verification','rework','delayed','cancelled','done'] as $st) {
            $summary[$st] = (clone $base)->where('status', $st)->count();
        }

        $from = $filters['date_from'] ?: 'Semua';
        $to   = $filters['date_to']   ?: 'Semua';
        $rangeLabel = $from . ' s.d ' . $to;

        $pdf = Pdf::loadView('admin.laporanharian.pdf', [
            'rows'       => $rows,
            'summary'    => $summary,
            'filters'    => $filters,
            'rangeLabel' => $rangeLabel,
        ])->setPaper('a4', 'landscape');

        $filename = 'laporan-harian-' . ($filters['date_from'] ?: 'semua') . '-' . ($filters['date_to'] ?: 'semua') . '.pdf';

        return $pdf->download($filename);
    }

    private function applyFilters($q, array $filters): void
    {
        if (!empty($filters['q'])) {
            $v = $filters['q'];
            $q->where(function ($w) use ($v) {
                $w->where('judul', 'like', "%{$v}%")
                  ->orWhereHas('jobdesk.user', fn($u) => $u->where('name', 'like', "%{$v}%"))
                  ->orWhereHas('jobdesk', fn($j) => $j->where('division', 'like', "%{$v}%"));
            });
        }

        /**
         * ✅ Tambahan keamanan:
         * kalau user_id yg dipilih ternyata admin, jangan diterima.
         */
        if (!empty($filters['user_id'])) {
            $safeUser = User::where('id', $filters['user_id'])
                ->where(function ($q) {
                    $q->whereNull('role')
                      ->orWhere('role', 'user');
                })
                ->exists();

            if ($safeUser) {
                $q->whereHas('jobdesk', fn($j) => $j->where('user_id', $filters['user_id']));
            }
        }

        if (!empty($filters['division'])) {
            $v = $filters['division'];
            $q->where(function ($w) use ($v) {
                $w->whereHas('jobdesk', fn($j) => $j->where('division', $v))
                  ->orWhereHas('jobdesk.user', fn($u) => $u->where('division', $v));
            });
        }

        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $q->whereDate('schedule_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $q->whereDate('schedule_date', '<=', $filters['date_to']);
        }
    }
}
