<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\JobdeskTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class DailySummaryFromJobdeskController extends Controller
{
    /**
     * Halaman Daily Summary (sumber data: JobdeskTask & JobdeskTaskPhoto).
     * View: resources/views/user/dailyReportUser/dailyReportUser.blade.php
     * Route name: user.daily
     */
    public function index(Request $request)
    {
        $data = $this->getSummaryData($request);
        return view('user.dailyReportUser.dailyReportUser', $data);
    }

    /** Hitung metrik ringkasan harian dari JobdeskTask (tanpa double-count overlap) */
    private function buildSummary($tasks): array
    {
        // ---------- 1) Rata-rata progress & status ----------
        $total = $tasks->count();
        $avg   = $total ? round($tasks->avg(fn($t) => (int)($t->progress ?? 0)), 2) : 0;

        $byStatus = [
            'done'         => 0,
            'in_progress'  => 0,
            'sedang_mengerjakan' => 0,
            'pending'      => 0,
            'cancelled'    => 0,
            'verification' => 0,
            'delayed'      => 0,
            'rework'       => 0,
            'to_do'        => 0,
        ];
        foreach ($tasks as $t) {
            $s = strtolower((string)($t->status ?? ''));
            if (array_key_exists($s, $byStatus)) $byStatus[$s]++;
        }

        // ---------- 2) Bangun interval menit [start, end] (exclude delayed/rework/verification) ----------
        $intervals = [];
        foreach ($tasks as $t) {
            $status = strtolower((string)($t->status ?? ''));
            if (in_array($status, ['delayed', 'rework', 'verification'], true)) {
                continue; // tidak dihitung ke jam total
            }

            $startRaw = trim((string)($t->start_time ?? ''));
            $endRaw   = trim((string)($t->end_time   ?? ''));
            if ($startRaw === '' || $endRaw === '') continue;

            $startMin = self::parseTimeToMinutes($startRaw);
            $endMin   = self::parseTimeToMinutes($endRaw);
            if ($startMin === null || $endMin === null) continue;

            if ($endMin <= $startMin) $endMin += 1440; // lewat tengah malam

            // Potong ke jendela hari [0,1440]
            $clipStart = max(0, min(1440, $startMin));
            $clipEnd   = max(0, min(1440, $endMin));
            if ($clipEnd > $clipStart) {
                $intervals[] = [$clipStart, $clipEnd];
            }
        }

        // ---------- 3) Merge intervals (union) ----------
        usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($intervals as $intv) {
            if (empty($merged)) {
                $merged[] = $intv;
                continue;
            }
            [$cs, $ce] = $merged[count($merged) - 1];
            [$ns, $ne] = $intv;
            if ($ns <= $ce) {
                $merged[count($merged) - 1][1] = max($ce, $ne);
            } else {
                $merged[] = $intv;
            }
        }

        // ---------- 4) Total menit & jam dari union ----------
        $totalMinutes = 0;
        foreach ($merged as [$s, $e]) {
            $totalMinutes += ($e - $s);
        }
        $totalHours = round($totalMinutes / 60, 1);

        // (Opsional) string hh:mm agar enak dibaca
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;
        $human = $h . ' jam ' . $m . ' menit';

        return [
            'total_tasks'          => $total,
            'avg_progress'         => $avg,
            'done'                 => $byStatus['done'],
            'in_progress'          => $byStatus['in_progress'],
            'sedang_mengerjakan'   => $byStatus['sedang_mengerjakan'],
            'pending'              => $byStatus['pending'],
            'cancelled'            => $byStatus['cancelled'],
            'verification'         => $byStatus['verification'],
            'delayed'              => $byStatus['delayed'],
            'rework'               => $byStatus['rework'],
            'to_do'                => $byStatus['to_do'],
            'total_duration_hours' => $totalHours,
            'human_duration'       => $human,
        ];
    }

    /**
     * Parse "H:i" atau "H:i:s" -> menit sejak 00:00. Return null bila invalid.
     */
    private static function parseTimeToMinutes(string $time): ?int
    {
        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }
        if (!preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            return null;
        }
        [$H, $i, $s] = array_map('intval', explode(':', $time));
        if ($H < 0 || $H > 23 || $i < 0 || $i > 59 || $s < 0 || $s > 59) return null;
        return ($H * 60) + $i; // detik diabaikan
    }

    // (Opsional) Export stub agar route tidak error—abaikan jika tidak dipakai
    public function exportExcel(Request $request)
    {
        return back()->with('success', 'Export Excel: endpoint belum diimplementasikan.');
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getSummaryData($request);
        $data['rangeLabel'] = Carbon::parse($data['pickedYmd'])->translatedFormat('l, d F Y');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('user.dailyReportUser.pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'Laporan-Harian-' . $data['pickedYmd'] . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Shared logic for Index & Export
     */
    private function getSummaryData(Request $request)
    {
        $me    = Auth::user();
        $appTz = config('app.timezone', 'UTC') ?: 'UTC';

        // ====== Ambil parameter tanggal ======
        $qDate = $request->query('date');
        try {
            $picked = $qDate ? Carbon::parse($qDate, $appTz) : Carbon::now($appTz);
        } catch (\Throwable $e) {
            $picked = Carbon::now($appTz);
        }
        $picked->timezone($appTz);
        $pickedYmd = $picked->format('Y-m-d');

        // ====== Otorisasi pilih user (Fix IDOR) ======
        $canPickUser = method_exists($me, 'isAdmin') ? (bool)$me->isAdmin() : false;

        // Default to self
        $selectedId  = $me->id;

        // Only allow override if Admin
        if ($canPickUser && $request->has('user_id')) {
            $selectedId = $request->integer('user_id');
        }

        // ====== Dropdown users (hanya admin) ======
        $users = $canPickUser
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect([$me]);

        // ====== 1) Ambil Jobdesk Tasks ======
        $jobdeskTasks = JobdeskTask::query()
            ->with(['photos', 'jobdesk.user'])
            ->whereHas('jobdesk', fn($q) => $q->where('user_id', (int)$selectedId))
            ->whereDate('schedule_date', $pickedYmd)
            ->get();

        // Map JobdeskTasks common fields
        $jobdeskTasks = $jobdeskTasks->map(function ($t) {
            $t->is_assignment = false;
            $t->project_name  = null;
            $t->progress      = max(1, (int)($t->progress ?? 1));
            // Ensure time fields are strings
            $t->start_time = $t->start_time ? substr($t->start_time, 0, 5) : null;
            $t->end_time   = $t->end_time   ? substr($t->end_time, 0, 5)   : null;
            return $t;
        });

        // ====== 2) Ambil Assignments (Penugasan) ======
        // Asumsi: "Aktivitas" = Assignment yang dibuat ATAU diupdate (submit/acc) pada tanggal ini.
        $assignments = \App\Models\Assignment::query()
            ->forUser((int)$selectedId)
            ->where(function ($q) use ($pickedYmd) {
                // Tampilkan jika dibuat hari ini ATAU diupdate hari ini (misal submisi)
                $q->whereDate('created_at', $pickedYmd)
                    ->orWhereDate('updated_at', $pickedYmd);
            })
            ->with('latestJobdeskWithPhotos') // Eager load jika ada relasi
            ->get();

        // Map Assignments to mimic JobdeskTask structure
        $mappedAssignments = $assignments->map(function ($a) {
            $a->judul         = $a->title;
            $a->status        = $a->computed_status ?? $a->status;
            $a->progress      = $a->computed_progress ?? $a->progress;
            $a->proof_link    = $a->proof_link;
            $a->display_status = $a->status;

            // Mapping fields
            $a->detail        = $a->description;

            // Waktu: Assignments tidak punya jam pasti, kita bisa pakai jam created/updated
            $timeRef = $a->wasChanged() ? $a->updated_at : $a->created_at;
            $a->start_time    = $timeRef ? $timeRef->format('H:i') : '-';
            $a->end_time      = '-'; // Assignment biasanya deadline-based, bukan jam kerja

            $a->project_name  = 'Penugasan';
            $a->is_assignment = true;
            $a->photos        = collect([]); // Penugasan via link jarang pakai foto di list ini

            return $a;
        });

        // ====== 3) Merge & Sort ======
        $tasks = $jobdeskTasks->merge($mappedAssignments);

        // Sort: JobdeskTask yang punya jam duluan, lalu sisanya by created_at
        $tasks = $tasks->sortBy(function ($t) {
            $time = $t->start_time && $t->start_time !== '-' ? $t->start_time : '23:59';
            return $time . '_' . $t->created_at;
        })->values();

        // Ringkasan
        $summary = $this->buildSummary($tasks);

        return [
            'users'      => $users,
            'selectedId' => (int)$selectedId,
            'summary'    => $summary,
            'tasks'      => $tasks,
            'pickedYmd'  => $pickedYmd,
        ];
    }
}
