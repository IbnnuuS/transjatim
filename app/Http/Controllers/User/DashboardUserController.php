<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JobdeskTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardUserController extends Controller
{
    /**
     * Dashboard Karyawan - Rekap bulan berjalan (1 Bulan).
     * View: resources/views/user/dashboardUser/dashboardUser.blade.php
     */
    public function index(Request $request)
    {
        $me = Auth::user();

        // ✅ Paksa WIB untuk semua perhitungan dashboard user
        $appTz = 'Asia/Jakarta';

        // =======================
        // RANGE 1 BULAN (bulan berjalan) - WIB
        // =======================
        $now        = Carbon::now($appTz);
        $startMonth = $now->copy()->startOfMonth()->startOfDay();
        $endMonth   = $now->copy()->endOfMonth()->endOfDay();

        // =======================
        // RECURRING JOBDESK GENERATION
        // =======================
        (new \App\Services\DailyRecurringTaskService)->generateForUser($me->id);

        // =======================
        // SINGLE QUERY STRATEGY: ambil semua task user dalam bulan berjalan sekaligus
        // =======================
        $tasksMonth = JobdeskTask::query()
            ->with([
                'photos',
                'jobdesk:id,user_id,assignment_id',
            ])
            ->whereHas('jobdesk', fn($q) => $q->where('user_id', $me->id))
            ->whereBetween(DB::raw('DATE(schedule_date)'), [
                $startMonth->toDateString(),
                $endMonth->toDateString(),
            ])
            ->orderByDesc('created_at')
            ->get();

        // =======================
        // METRIK 1 BULAN
        // =======================
        $avgProgressMonth = $tasksMonth->count()
            ? (int) round($tasksMonth->avg(fn($x) => (int) ($x->progress ?? 0)))
            : 0;

        $doneCountMonth = $tasksMonth->where('status', 'done')->count();

        // =======================
        // METRIK HARI INI (WIB)
        // =======================
        $todayYmd   = $now->toDateString(); // WIB
        $todayStart = $now->copy()->startOfDay(); // WIB
        $todayEnd   = $now->copy()->endOfDay();   // WIB

        $myDailyTasks = $tasksMonth->filter(function ($t) use ($todayYmd, $todayStart, $todayEnd, $appTz) {
            // 1) filter berdasarkan schedule_date (tanggal kerja)
            if (!empty($t->schedule_date)) {
                $d = $t->schedule_date instanceof Carbon
                    ? $t->schedule_date->copy()->timezone($appTz)->toDateString()
                    : substr((string) $t->schedule_date, 0, 10);

                if ($d === $todayYmd) {
                    return true;
                }
            }

            // 2) fallback jika schedule_date null: pakai created_at WIB
            if (!empty($t->created_at)) {
                $c = $t->created_at instanceof Carbon
                    ? $t->created_at->copy()->timezone($appTz)
                    : Carbon::parse((string) $t->created_at, $appTz);

                return $c->between($todayStart, $todayEnd);
            }

            return false;
        });

        // ✅ Supaya tampilan "Aktivitas pada ..." selalu berisi yang terbaru hari ini
        $myDailyTasks = $myDailyTasks->sortByDesc(function ($t) use ($appTz) {
            // Prioritas urut: submitted_at -> created_at -> schedule_date
            if (!empty($t->submitted_at)) return Carbon::parse($t->submitted_at)->timestamp;
            if (!empty($t->created_at)) return $t->created_at->copy()->timezone($appTz)->timestamp;
            if (!empty($t->schedule_date)) {
                return ($t->schedule_date instanceof Carbon)
                    ? $t->schedule_date->copy()->timezone($appTz)->timestamp
                    : Carbon::parse((string) $t->schedule_date, $appTz)->timestamp;
            }
            return 0;
        })->values();

        $todayCount = $myDailyTasks->count();

        // =======================
        // CHART 1 BULAN
        // =======================
        $labels      = [];
        $seriesAvg   = [];
        $seriesCount = [];

        $grouped = $tasksMonth->groupBy(function ($t) use ($appTz) {
            if ($t->schedule_date instanceof Carbon) {
                return $t->schedule_date->copy()->timezone($appTz)->format('Y-m-d');
            }
            return substr((string) $t->schedule_date, 0, 10);
        });

        $cursor = $startMonth->copy()->startOfDay();
        while ($cursor <= $endMonth) {
            $ymd   = $cursor->format('Y-m-d');
            $label = $cursor->format('d/m');

            $labels[] = $label;

            $dayTasks = $grouped->get($ymd, collect());
            $cnt      = $dayTasks->count();
            $avg      = $cnt ? round($dayTasks->avg(fn($x) => (int) ($x->progress ?? 0))) : 0;

            $seriesCount[] = (int) $cnt;
            $seriesAvg[]   = (int) $avg;

            $cursor->addDay();
        }

        // =======================
        // DONUT STATUS 1 BULAN
        // =======================
        $statusOrder = ['done', 'in_progress', 'sedang_mengerjakan', 'pending', 'cancelled', 'verification', 'delayed', 'rework', 'to_do'];
        $donut = array_fill_keys($statusOrder, 0);

        foreach ($tasksMonth as $t) {
            $s = strtolower((string) ($t->status ?? ''));
            if (array_key_exists($s, $donut)) {
                $donut[$s]++;
            }
        }

        // =======================
        // DAFTAR TUGAS TERBARU (10 item)
        // =======================
        $latestTasks = $tasksMonth->take(10);

        // =======================
        // ACTIVITY FEED (pakai latestTasks)
        // =======================
        $recentActivities = [];
        foreach ($latestTasks as $t) {
            $when = null;

            // ✅ Prioritas waktu: submitted_at -> created_at -> schedule_date
            if (!empty($t->submitted_at)) {
                try {
                    $when = Carbon::parse($t->submitted_at)->timezone($appTz);
                } catch (\Throwable $e) {
                    $when = null;
                }
            }

            if (!$when && !empty($t->created_at)) {
                $when = $t->created_at->copy()->timezone($appTz);
            }

            if (!$when && !empty($t->schedule_date)) {
                $base = $t->schedule_date instanceof Carbon
                    ? $t->schedule_date->copy()
                    : Carbon::parse((string) $t->schedule_date, $appTz);
                $when = $base->startOfDay()->timezone($appTz);
            }

            $recentActivities[] = [
                'icon'  => 'bi bi-check2-square',
                'title' => (string) ($t->judul ?? 'Aktivitas'),
                'time'  => $when,
                'desc'  => 'Status: ' . ucwords(str_replace('_', ' ', (string) $t->status)) .
                    ' • ' . (int) ($t->progress ?? 0) . '%',
            ];
        }

        $chartPayload = [
            'labels'      => $labels,
            'seriesAvg'   => $seriesAvg,
            'seriesCount' => $seriesCount,
            'donut'       => $donut,
        ];

        return view('user.dashboardUser.dashboardUser', [
            'avgProgress'      => $avgProgressMonth,
            'doneCount'        => $doneCountMonth,
            'todayCount'       => $todayCount,
            'latestTasks'      => $latestTasks,
            'myDailyTasks'     => $myDailyTasks,
            'recentActivities' => $recentActivities,
            'chartPayload'     => $chartPayload,
            'periodLabel'      => $startMonth->format('d/m/Y') . ' - ' . $endMonth->format('d/m/Y'),
        ]);
    }
}
