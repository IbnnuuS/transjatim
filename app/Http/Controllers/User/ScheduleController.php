<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();
        $now   = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        // ================== DATA KALENDER BULANAN (Logic dari Admin) ==================
        $month = (int)($request->query('m', (int)$now->month));
        $year  = (int)($request->query('y', (int)$now->year));

        // Simple validasi navigasi
        if ($month < 1) {
            $month = 12;
            $year--;
        }
        if ($month > 12) {
            $month = 1;
            $year++;
        }

        $dateFocus = Carbon::createFromDate($year, $month, 1);
        $monthLabel = $dateFocus->translatedFormat('F Y');

        // Navigasi prev/next
        $prevM = $month - 1;
        $prevY = $year;
        if ($prevM < 1) {
            $prevM = 12;
            $prevY--;
        }
        $nextM = $month + 1;
        $nextY = $year;
        if ($nextM > 12) {
            $nextM = 1;
            $nextY++;
        }

        $calendarNav = [
            'prev' => ['m' => $prevM, 'y' => $prevY],
            'next' => ['m' => $nextM, 'y' => $nextY],
            'now'  => ['m' => $now->month, 'y' => $now->year],
        ];

        // Grid Start/End
        $firstOfMonth = $dateFocus->copy()->startOfDay();
        $lastOfMonth  = $firstOfMonth->copy()->endOfMonth();
        $gridStart    = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd      = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        // Count jadwal per tanggal (milik user ini)
        $scheduleCounts = Schedule::where('user_id', $user->id)
            ->whereBetween('tanggal', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->selectRaw('tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        // Data Absensi untuk Grid (status, jam)
        $attendanceRows = Attendance::where('employee_id', $user->id)
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->get(['date', 'status', 'in_time', 'out_time', 'note']);

        $attendanceByDate = [];
        foreach ($attendanceRows as $a) {
            $k = Carbon::parse($a->date)->toDateString();
            $attendanceByDate[$k] = [
                'status'   => strtolower($a->status ?? ''),
                'in_time'  => $a->in_time,
                'out_time' => $a->out_time,
                'note'     => $a->note,
            ];
        }

        // Detail jadwal untuk Modal (JS consumption)
        $scheduleRows = Schedule::where('user_id', $user->id)
            ->whereBetween('tanggal', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->get(['tanggal', 'divisi', 'judul', 'catatan', 'jam_mulai', 'jam_selesai']);

        $scheduleItems = [];
        foreach ($scheduleRows as $r) {
            $k = Carbon::parse($r->tanggal)->toDateString();
            $scheduleItems[$k][] = [
                'divisi'  => $r->divisi,
                'judul'   => $r->judul,
                'title'   => $r->judul, // alias
                'catatan' => $r->catatan,
                'note'    => $r->catatan, // alias
            ];
        }

        // ================== EXISTING LOGIC ==================

        // Jadwal user (list, paginate) - Default urut tanggal desc
        $schedules = Schedule::where('user_id', $user->id)
            ->orderBy('tanggal', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Kehadiran hari ini
        $todayAttendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->latest('id')
            ->first();

        // Jadwal hari ini (dari admin) - asumsi masih dipakai logic lama
        $todaySchedule = Schedule::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->first();

        // Riwayat kehadiran bulan ini (buat tabel riwayat)
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd   = $now->copy()->endOfMonth()->toDateString();
        $attendanceThisMonth = Attendance::where('employee_id', $user->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->orderBy('date', 'desc')
            ->get();

        // ================== HOLIDAYS (Hardcoded 2025) ==================
        $holidays = $this->getHolidays($year);

        return view('user.jadwalUser.jadwalUser', compact(
            'user',
            'schedules',
            'todayAttendance',
            'attendanceThisMonth',
            'todaySchedule',
            'month',
            'year',
            'monthLabel',
            'calendarNav',
            'gridStart',
            'gridEnd',
            'scheduleCounts',
            'attendanceByDate',
            'scheduleItems',
            'today',
            'holidays'
        ));
    }

    private function getHolidays($year)
    {
        // Daftar Libur Nasional Indonesia (Estimasi 2025)
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

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'divisi'  => 'nullable|string|max:100',
            'judul'   => 'required|string|max:200',
            'catatan' => 'nullable|string',
        ]);

        Schedule::create([
            'user_id' => Auth::id(),
            'tanggal' => $request->tanggal,
            'divisi'  => $request->divisi,
            'judul'   => $request->judul,
            'catatan' => $request->catatan,
        ]);

        return back()->with('success_schedule', 'Jadwal berhasil ditambahkan.');
    }
}
