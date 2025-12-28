<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JobdeskTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MonthlyRecapController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->getMonthlyData($request);
        return view('user.rekapBulananUser.rekapBulananUser', $data);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getMonthlyData($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('user.rekapBulananUser.pdf', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'Rekap-Bulanan-' . $data['monthParam'] . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Shared logic for Index & Export
     */
    private function getMonthlyData(Request $request)
    {
        $me    = Auth::user();
        $appTz = config('app.timezone', 'UTC') ?: 'UTC';

        // ===== Ambil parameter bulan =====
        $qMonth = $request->query('month'); // format 'YYYY-MM'
        try {
            if ($qMonth && preg_match('/^\d{4}-\d{2}$/', $qMonth)) {
                $from = Carbon::createFromFormat('Y-m', $qMonth, $appTz)->startOfMonth();
            } else {
                $from = Carbon::now($appTz)->startOfMonth();
            }
        } catch (\Throwable $e) {
            $from = Carbon::now($appTz)->startOfMonth();
        }
        $to = $from->clone()->endOfMonth();

        $monthLabel = $from->translatedFormat('F Y');
        $monthParam = $from->format('Y-m');

        // ===== Pilih user =====
        // ===== Pilih user (Fix IDOR) =====
        $canPickUser = method_exists($me, 'isAdmin') ? (bool) $me->isAdmin() : false;
        $selectedId  = $me->id ?? null;

        if ($canPickUser && $request->has('user_id')) {
            $selectedId = $request->integer('user_id');
        }

        $users = $canPickUser
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect([$me]);

        if ($canPickUser && $users->isNotEmpty() && !$users->pluck('id')->contains($selectedId)) {
            $selectedId = $me->id ?? null;
        }

        // ===== Ambil tasks =====
        $tasks = JobdeskTask::query()
            ->with(['photos', 'jobdesk.user:id,name,division'])
            ->whereHas('jobdesk', fn($q) => $q->where('user_id', (int)$selectedId))
            ->whereDate('schedule_date', '>=', $from->toDateString())
            ->whereDate('schedule_date', '<=', $to->toDateString())
            ->where(function ($q) {
                $q->where('is_template', 0)->orWhereNull('is_template');
            })
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->orderBy('created_at')
            ->get();

        // ===== Group per hari =====
        $byDay = $tasks->groupBy(function ($t) {
            try {
                return \Illuminate\Support\Carbon::parse($t->schedule_date)->toDateString();
            } catch (\Throwable $e) {
                return (string) $t->schedule_date;
            }
        });

        // ===== Ringkasan status total =====
        $monthStatus = [
            'done' => 0,
            'in_progress' => 0,
            'sedang_mengerjakan' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'verification' => 0,
            'delayed' => 0,
            'rework' => 0,
            'to_do' => 0,
        ];
        foreach ($tasks as $t) {
            $s = strtolower((string)($t->status ?? ''));
            if (array_key_exists($s, $monthStatus)) $monthStatus[$s]++;
        }

        // ===== Perhitungan menit =====
        // 1) Per-hari: union & sum (sum = seperti Daily Summary)
        $minutesUnionByDay = [];
        $minutesSumByDay   = [];
        foreach ($byDay as $ymd => $list) {
            $minutesUnionByDay[$ymd] = $this->unionMinutesForDay($list);
            $minutesSumByDay[$ymd]   = $this->sumMinutesLikeDaily($list);
        }

        // 2) Bulanan: jumlahkan per-hari
        $monthTotals = [
            'days'          => $byDay->count(),
            'tasks'         => $tasks->count(),
            'avg_progress'  => $tasks->count() ? round($tasks->avg(fn($t) => (int)($t->progress ?? 0))) : 0,
            'status'        => $monthStatus,
            'minutes_union' => array_sum($minutesUnionByDay), // tetap ada
            'minutes_sum'   => array_sum($minutesSumByDay),   // << ini yang seperti Daily
        ];

        // ===== Data harian untuk Blade =====
        $days = $byDay->map(function ($list, $ymd) use ($minutesUnionByDay, $minutesSumByDay) {
            $count   = $list->count();
            $avgProg = $count ? round($list->avg(fn($t) => (int)($t->progress ?? 0))) : 0;
            $status  = [
                'done' => 0,
                'in_progress' => 0,
                'sedang_mengerjakan' => 0,
                'pending' => 0,
                'cancelled' => 0,
                'verification' => 0,
                'delayed' => 0,
                'rework' => 0,
                'to_do' => 0,
            ];
            foreach ($list as $t) {
                $s = strtolower((string)($t->status ?? ''));
                if (isset($status[$s])) $status[$s]++;
            }
            return [
                'date'           => $ymd,
                'tasks'          => $list,
                'count'          => $count,
                'avg_progress'   => $avgProg,
                'status'         => $status,
                'minutes_union'  => (int)($minutesUnionByDay[$ymd] ?? 0),
                'minutes_sum'    => (int)($minutesSumByDay[$ymd] ?? 0), // << baru
            ];
        })->sortKeys();

        return [
            'users'       => $users,
            'canPickUser' => $canPickUser,
            'selectedId'  => (int)$selectedId,
            'monthLabel'  => $monthLabel,
            'monthParam'  => $monthParam,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'days'        => $days,
            'monthTotals' => $monthTotals,
        ];
    }

    /**
     * SUM seperti Daily Summary:
     * - Exclude delayed/rework/verification
     * - Tiap task dihitung diffMinutes(start,end) (lintas tengah malam didukung)
     * - Lalu dijumlahkan (tanpa merge/union)
     */
    protected function sumMinutesLikeDaily(Collection $rows): int
    {
        $excluded = ['delayed', 'rework', 'verification'];
        $sum = 0;
        foreach ($rows as $r) {
            $status = strtolower($r->status ?? '');
            if (in_array($status, $excluded, true)) continue;

            $sum += $this->diffMinutes($r->start_time ?? null, $r->end_time ?? null);
        }
        return $sum;
    }

    /**
     * UNION per hari (tetap dipertahankan kalau butuh).
     */
    protected function unionMinutesForDay(Collection $rows): int
    {
        $excluded  = ['delayed', 'rework', 'verification'];
        $intervals = [];

        foreach ($rows as $r) {
            $status = strtolower($r->status ?? '');
            if (in_array($status, $excluded, true)) continue;

            $st = $this->hhmmToMinutes($r->start_time ?? null);
            $en = $this->hhmmToMinutes($r->end_time ?? null);
            if ($st === null || $en === null) continue;

            if ($en < $st) {
                // Hitung bagian hari ini saja
                $intervals[] = [$st, 1440];
                // Jika ingin tarik penuh ke hari mulai, tambahkan: $intervals[] = [0, $en];
            } else {
                if ($st < $en) $intervals[] = [$st, $en];
            }
        }

        if (!$intervals) return 0;

        usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        [$cs, $ce] = $intervals[0];
        for ($i = 1; $i < count($intervals); $i++) {
            [$s, $e] = $intervals[$i];
            if ($s <= $ce) {
                $ce = max($ce, $e);
            } else {
                $merged[] = [$cs, $ce];
                [$cs, $ce] = [$s, $e];
            }
        }
        $merged[] = [$cs, $ce];

        $total = 0;
        foreach ($merged as [$s, $e]) $total += max(0, $e - $s);
        return $total;
    }

    protected function hhmmToMinutes($t): ?int
    {
        if (empty($t)) return null;
        $t = trim((string)$t);
        if ($t === '') return null;
        if (preg_match('/^\d{1,2}:\d{2}$/', $t)) $t .= ':00';
        if (!preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $t)) return null;
        [$H, $i, $s] = array_map('intval', explode(':', $t));
        if ($H < 0 || $H > 23 || $i < 0 || $i > 59 || $s < 0 || $s > 59) return null;
        return $H * 60 + $i;
    }

    /**
     * diffMinutes ala Daily Summary:
     * - return 0 jika start/end kosong
     * - jika end < start, tambahkan 1 hari ke end (melewati tengah malam)
     */
    protected function diffMinutes($start, $end): int
    {
        try {
            if (empty($start) || empty($end)) return 0;
            $startC = Carbon::createFromFormat('H:i:s', $start);
            $endC   = Carbon::createFromFormat('H:i:s', $end);
            if ($endC->lessThan($startC)) $endC->addDay();
            return $endC->diffInMinutes($startC);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
