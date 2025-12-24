<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

use App\Models\Attendance;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\User;

class ScheduleAdminController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay();

        // ================== FILTER LIST & KPI ==================
        $date  = $request->filled('date')
            ? Carbon::parse($request->date, 'Asia/Jakarta')->startOfDay()
            : $today;

        // ====== HITUNG TOTAL USER (role=user) untuk absent otomatis ======
        $totalUsers = User::query()->where('role', 'user')->count();

        // ====== Ambil attendance hari tersebut ======
        $attTodayQuery = Attendance::whereDate('date', $date->toDateString());

        $presentCount = (clone $attTodayQuery)->where('status', 'present')->count();
        $lateCount    = (clone $attTodayQuery)->where('status', 'late')->count();

        $absentExplicit = (clone $attTodayQuery)->where('status', 'absent')->count();
        $recordedCount  = (clone $attTodayQuery)->distinct('employee_id')->count('employee_id');

        // ✅ tidak absen / lupa absen => dianggap absent
        $absentTotal = $absentExplicit + max(0, ($totalUsers - $recordedCount));

        $stats = [
            'present' => $presentCount + $lateCount,
            'absent'  => $absentTotal,
        ];

        // ================== LIST ATTENDANCE (HANYA YANG ADA RECORD) ==================
        $attendances = Attendance::with('employee')
            ->whereDate('date', $date->toDateString())
            ->orderBy('employee_id')
            ->paginate(10);

        // ================== ASSIGNMENTS LIST (tetap boleh ditampilkan) ==================
        $assignments = Assignment::with('employee')
            ->whereDate('deadline', '>=', $today->toDateString())
            ->latest()
            ->take(20)
            ->get();

        // ================== DATA KALENDER BULANAN ==================
        $month = (int) ($request->query('m', (int) $today->month));
        $year  = (int) ($request->query('y', (int) $today->year));
        if ($month < 1) {
            $month = 12;
            $year -= 1;
        }
        if ($month > 12) {
            $month = 1;
            $year += 1;
        }

        $firstOfMonth = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->startOfDay();
        $lastOfMonth  = $firstOfMonth->copy()->endOfMonth();

        $gridStart = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd   = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        // Count jadwal per tanggal
        $scheduleCounts = Schedule::whereBetween('tanggal', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->selectRaw('tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        // ====== AMBIL ABSEN UNTUK GRID (per tanggal x user) ======
        $attendanceRows = Attendance::whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->get(['employee_id', 'date', 'status', 'in_time', 'out_time', 'note', 'overtime_minutes', 'overtime_reason']);

        $attendanceByDateUser = [];
        foreach ($attendanceRows as $a) {
            $k = Carbon::parse($a->date)->toDateString();
            $attendanceByDateUser[$k][(int) $a->employee_id] = [
                'status'    => strtolower((string) $a->status),
                'in_time'   => $a->in_time,
                'out_time'  => $a->out_time,
                'note'      => $a->note,
                'ot_min'    => $a->overtime_minutes,
                'ot_reason' => $a->overtime_reason,
            ];
        }

        // ====== Jadwal detail per tanggal + status hadir ======
        $defaultStartTime = '08:00:00';

        $scheduleRows = Schedule::with(['user:id,name'])
            ->whereBetween('tanggal', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->get(['id', 'user_id', 'tanggal', 'divisi', 'judul', 'catatan', 'jam_mulai', 'jam_selesai']);

        $scheduleItems = [];
        foreach ($scheduleRows as $r) {
            $dateKey = Carbon::parse($r->tanggal)->toDateString();
            $uid     = (int) $r->user_id;

            $inTime = null;
            $outTime = null;
            $attNote = null;
            $otMin   = null;
            $otReason = null;
            $raw = null;

            $att = $attendanceByDateUser[$dateKey][$uid] ?? null;
            if ($att) {
                $inTime   = $att['in_time'];
                $outTime  = $att['out_time'];
                $attNote  = $att['note'];
                $otMin    = $att['ot_min'];
                $otReason = $att['ot_reason'];
                $raw      = $att['status'];
            }

            // ✅ IZIN DIHAPUS: kalau tidak ada attendance -> absent
            if ($att && $raw) {
                $s = $raw;
            } else {
                if ($inTime) {
                    $s = (strtotime($inTime) > strtotime($defaultStartTime)) ? 'late' : 'present';
                } else {
                    $s = 'absent';
                }
            }

            $labelMap = [
                'present' => 'Hadir',
                'late'    => 'Terlambat',
                'absent'  => 'Tidak Hadir',
            ];
            $badgeMap = [
                'present' => 'success',
                'late'    => 'info',
                'absent'  => 'danger',
            ];

            $scheduleItems[$dateKey][] = [
                'id'         => $r->id,
                'title'      => $r->judul ?: '(Tanpa judul)',
                'division'   => $r->divisi,
                'user'       => $r->user->name ?? null,
                'note'       => $r->catatan,
                'start'      => $r->jam_mulai,
                'end'        => $r->jam_selesai,

                'att_status' => $s,
                'att_label'  => $labelMap[$s] ?? ucfirst($s),
                'att_badge'  => $badgeMap[$s] ?? 'secondary',
                'in_time'    => $inTime,
                'out_time'   => $outTime,
                'att_note'   => $attNote,
                'ot_min'     => $otMin,
                'ot_reason'  => $otReason,
            ];
        }

        $monthLabel = $firstOfMonth->translatedFormat('F Y');
        $prev = $firstOfMonth->copy()->subMonth();
        $next = $firstOfMonth->copy()->addMonth();
        $calendarNav = [
            'prev' => ['m' => (int) $prev->month, 'y' => (int) $prev->year],
            'next' => ['m' => (int) $next->month, 'y' => (int) $next->year],
            'now'  => ['m' => (int) $today->month, 'y' => (int) $today->year],
        ];

        // ================== DATA CHART 7 HARI ==================
        $start = Carbon::now('Asia/Jakarta')->subDays(6)->startOfDay();
        $end   = Carbon::now('Asia/Jakarta')->startOfDay();

        $labels = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $labels[] = $d->format('d M');
        }

        $rows = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(`date`) as d, status, COUNT(*) as c')
            ->groupBy('d', 'status')
            ->orderBy('d')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $key = Carbon::parse($r->d)->format('Y-m-d');
            $st  = strtolower($r->status);
            $map[$key][$st] = ($map[$key][$st] ?? 0) + (int) $r->c;
        }

        $series = [
            'Hadir'        => [],
            'Tidak Hadir'  => [],
            'Terlambat'    => [],
        ];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $k = $d->format('Y-m-d');

            $present = $map[$k]['present'] ?? 0;
            $late    = $map[$k]['late'] ?? 0;
            $absent  = $map[$k]['absent'] ?? 0;

            $series['Hadir'][]       = $present + $late;
            $series['Tidak Hadir'][] = $absent;
            $series['Terlambat'][]   = $late;
        }

        $chart7d = [
            'labels' => $labels,
            'series' => [
                ['name' => 'Hadir',       'data' => $series['Hadir']],
                ['name' => 'Tidak Hadir', 'data' => $series['Tidak Hadir']],
                ['name' => 'Terlambat',   'data' => $series['Terlambat']],
            ],
        ];

        $holidays = $this->getHolidays($year);

        return view('admin.jadwalAdmin.jadwalAdmin', compact(
            'stats',
            'attendances',
            'assignments',
            'chart7d',
            'month',
            'year',
            'monthLabel',
            'gridStart',
            'gridEnd',
            'calendarNav',
            'scheduleCounts',
            'scheduleItems',
            'today',
            'holidays'
        ));
    }

    public function storeAssignment(Request $request)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'deadline'            => 'required|date',
            'employee_id'         => 'required|exists:users,id',
            'division_assignment' => 'required|in:teknik,digital,customer service',
            'description'         => 'nullable|string|max:255',
        ]);

        $payload = [
            'title'       => $data['title'],
            'deadline'    => $data['deadline'],
            'employee_id' => $data['employee_id'],
            'description' => $data['description'] ?? null,
            'status'      => 'in_progress',
            'progress'    => 0,
        ];

        if (Schema::hasColumn('assignments', 'division')) {
            $payload['division'] = $data['division_assignment'];
        }

        Assignment::create($payload);

        return back()->with('success', 'Tugas baru berhasil ditambahkan.');
    }

    private function getHolidays($year)
    {
        if ($year != 2025) return [];

        return [
            '2025-01-01' => 'Tahun Baru Masehi',
            '2025-01-27' => 'Isra Mikraj Nabi Muhammad SAW',
            '2025-01-29' => 'Tahun Baru Imlek 2576 Kongzili',
            '2025-03-29' => 'Hari Suci Nyepi',
            '2025-03-31' => 'Hari Raya Idul Fitri 1446 H',
            '2025-04-01' => 'Cuti Bersama Idul Fitri',
            '2025-04-18' => 'Wafat Isa Al Masih',
            '2025-04-20' => 'Hari Paskah',
            '2025-05-01' => 'Hari Buruh Internasional',
            '2025-05-12' => 'Hari Raya Waisak 2569 BE',
            '2025-05-29' => 'Kenaikan Isa Al Masih',
            '2025-06-01' => 'Hari Lahir Pancasila',
            '2025-06-07' => 'Hari Raya Idul Adha 1446 H',
            '2025-06-27' => 'Tahun Baru Islam 1447 H',
            '2025-08-17' => 'Hari Kemerdekaan RI',
            '2025-09-05' => 'Maulid Nabi Muhammad SAW',
            '2025-12-25' => 'Hari Raya Natal',
        ];
    }
}
