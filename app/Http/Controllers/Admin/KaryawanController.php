<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

final class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        $appTz = config('app.timezone', 'Asia/Jakarta') ?: 'Asia/Jakarta';
        Carbon::setLocale('id');

        // PARAM FILTER
        $dateStr = (string) $request->query('att_date', Carbon::now($appTz)->toDateString());
        $shift   = trim((string) $request->query('att_shift', ''));
        $q        = trim((string) $request->query('q', ''));
        $division = trim((string) $request->query('division', ''));

        // ✅ hanya 3 divisi
        $divisionOptions = ['Teknik','Digital','Customer Service'];

        // Dropdown karyawan kalender
        $employeeList = User::query()
            ->where(fn($qq) => $qq->where('role','user')->orWhereNull('role'))
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $calEmployeeId = (int) $request->query('cal_employee_id', (int) ($employeeList->first()->id ?? 0));
        $calMonthStr   = trim((string) $request->query('cal_month', ''));

        if (!preg_match('~^\d{4}-\d{2}$~', $calMonthStr)) {
            $calMonthStr = Carbon::now($appTz)->format('Y-m');
        }

        [$monthY, $monthM] = array_map('intval', explode('-', $calMonthStr));
        $firstDay  = Carbon::createFromDate($monthY, $monthM, 1, $appTz);
        $lastDay   = $firstDay->copy()->endOfMonth();
        $daysCount = $firstDay->daysInMonth;

        // SELECT kolom users
        $cols = ['id','name'];
        if (Schema::hasColumn('users','full_name')) $cols[] = 'full_name';
        if (Schema::hasColumn('users','email')) $cols[] = 'email';
        if (Schema::hasColumn('users','avatar')) $cols[] = 'avatar';
        if (Schema::hasColumn('users','birth_date')) $cols[] = 'birth_date';
        if (Schema::hasColumn('users','divisi')) $cols[] = 'divisi';
        if (Schema::hasColumn('users','division')) $cols[] = 'division';

        $orderExpr = Schema::hasColumn('users','full_name')
            ? 'COALESCE(full_name, name)'
            : 'name';

        // QUERY USERS + FILTER DIVISI & SEARCH
        $employees = User::query()
            ->where(fn($q1) => $q1->where('role','user')->orWhereNull('role'))
            ->when($division !== '', function($q2) use ($division) {
                $q2->where(function($w) use ($division) {
                    if (Schema::hasColumn('users','division')) $w->orWhere('division', $division);
                    if (Schema::hasColumn('users','divisi')) $w->orWhere('divisi', $division);
                });
            })
            ->when($q !== '', function($q3) use ($q) {
                $q3->where(function($w) use ($q) {
                    $like = '%'.$q.'%';
                    if (Schema::hasColumn('users','full_name')) {
                        $w->where('full_name','like',$like);
                        if (Schema::hasColumn('users','name')) $w->orWhere('name','like',$like);
                    } else {
                        $w->where('name','like',$like);
                    }
                });
            })
            ->select($cols)
            ->orderByRaw($orderExpr)
            ->get();

        // ABSENSI HARIAN
        $attendanceMap = $this->buildAttendanceMapForDay($dateStr, $shift, $appTz);

        // BULANAN
        $scheduleMapMonth = ($calEmployeeId > 0)
            ? $this->buildScheduleMapForMonth($calEmployeeId, $firstDay, $lastDay)
            : [];

        $attendanceMapMonth = ($calEmployeeId > 0)
            ? $this->buildAttendanceMapForMonth($calEmployeeId, $firstDay, $lastDay, $appTz)
            : [];

        $calDays = [];
        for ($d=1; $d<=$daysCount; $d++) {
            $date = Carbon::createFromDate($monthY, $monthM, $d, $appTz);
            $key  = $date->toDateString();

            $att = $attendanceMapMonth[$key] ?? null;

            $calDays[] = [
                'date' => $key,
                'dow' => ucfirst($date->locale('id')->isoFormat('dddd')),
                'in' => $att['in'] ?? '—',
                'out' => $att['out'] ?? '—',
                'status' => $att['status'] ?? 'Belum Tersedia',
            ];
        }

        return view('admin.karyawanAdmin.karyawanAdmin', [
            'employees' => $employees,
            'attendanceMap' => $attendanceMap,
            'divisionOptions' => $divisionOptions,
            'employeeList' => $employeeList,
            'calEmployeeId' => $calEmployeeId,
            'calMonthStr' => $calMonthStr,
            'calDays' => $calDays,
            'daysInMonth' => $daysCount,
            'appTz' => $appTz,
        ]);
    }

    /* ===================== ABSENSI HELPERS ===================== */

    private function buildAttendanceMapForDay(string $dateYmd, string $shift, string $appTz): array
    {
        $table = $this->firstExistingTable(['attendances','attendance','absensi','presences','kehadirans']);
        if (!$table) return [];

        $colUser   = $this->firstExistingColumn($table, ['user_id','employee_id','karyawan_id','uid']);
        $colDate   = $this->firstExistingColumn($table, ['date','attendance_date','tanggal','work_date','tgl']);
        $colIn     = $this->firstExistingColumn($table, ['in_time','time_in','check_in','checkin','clock_in','jam_masuk','jam_in']);
        $colOut    = $this->firstExistingColumn($table, ['out_time','time_out','check_out','checkout','clock_out','jam_pulang','jam_out']);
        $colStatus = $this->firstExistingColumn($table, ['status','state','kehadiran_status','attendance_status','absen_status']);
        $colShift  = $this->firstExistingColumn($table, ['shift','work_shift','jadwal_shift']);

        if (!$colUser || !$colDate) return [];

        $selectCols = [$colUser.' as _uid', $colDate.' as _date'];
        if ($colIn) $selectCols[] = $colIn.' as _in';
        if ($colOut) $selectCols[] = $colOut.' as _out';
        if ($colStatus) $selectCols[] = $colStatus.' as _st';
        if ($colShift) $selectCols[] = $colShift.' as _sh';

        $q = DB::table($table)->select($selectCols)->whereDate($colDate, $dateYmd);
        if ($colShift && $shift !== '') $q->where($colShift, $shift);

        $rows = $q->get();
        $map = [];

        foreach ($rows as $r) {
            $uid = (int) ($r->_uid ?? 0);
            if ($uid <= 0) continue;

            $tin = $this->fmtTimeWib($r->_in ?? null, $appTz);
            $tout = $this->fmtTimeWib($r->_out ?? null, $appTz);

            $label = $this->normalizeStatus((string)($r->_st ?? ''), $tin, $tout);
            $map[$uid] = ['in'=>$tin, 'out'=>$tout, 'status'=>$label];
        }

        return $map;
    }

    private function buildAttendanceMapForMonth(int $userId, Carbon $start, Carbon $end, string $appTz): array
    {
        $table = $this->firstExistingTable(['attendances','attendance','absensi','presences','kehadirans']);
        if (!$table) return [];

        $colUser   = $this->firstExistingColumn($table, ['user_id','employee_id','karyawan_id','uid']);
        $colDate   = $this->firstExistingColumn($table, ['date','attendance_date','tanggal','work_date','tgl']);
        $colIn     = $this->firstExistingColumn($table, ['in_time','time_in','check_in','checkin','clock_in','jam_masuk','jam_in']);
        $colOut    = $this->firstExistingColumn($table, ['out_time','time_out','check_out','checkout','clock_out','jam_pulang','jam_out']);
        $colStatus = $this->firstExistingColumn($table, ['status','state','kehadiran_status','attendance_status','absen_status']);

        if (!$colUser || !$colDate) return [];

        $rows = DB::table($table)
            ->where($colUser, $userId)
            ->whereBetween($colDate, [$start->toDateString(), $end->toDateString()])
            ->orderBy($colDate)
            ->get();

        $byDate = [];
        foreach ($rows as $r) {
            $key = (string)$r->$colDate;
            $tin = $this->fmtTimeWib($r->$colIn ?? null, $appTz);
            $tout = $this->fmtTimeWib($r->$colOut ?? null, $appTz);
            $label = $this->normalizeStatus((string)($r->$colStatus ?? ''), $tin, $tout);

            $byDate[$key] = ['in'=>$tin, 'out'=>$tout, 'status'=>$label];
        }

        return $byDate;
    }

    private function buildScheduleMapForMonth(int $userId, Carbon $start, Carbon $end): array
    {
        return [];
    }

    private function firstExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $t) if (Schema::hasTable($t)) return $t;
        return null;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) if (Schema::hasColumn($table, $c)) return $c;
        return null;
    }

    private function fmtTimeWib($val, string $appTz): ?string
    {
        if (!$val) return null;
        try {
            return Carbon::parse($val)->timezone($appTz)->format('H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeStatus(string $raw, ?string $tin, ?string $tout): string
    {
        $r = strtolower(trim($raw));
        if ($r !== '') {
            return match ($r) {
                'hadir','present' => 'Hadir',
                'tidak hadir','absent' => 'Tidak Hadir',
                'terlambat','late' => 'Terlambat',
                'izin','leave' => 'Izin',
                'wfh' => 'WFH',
                'dinas','on_duty' => 'Dinas',
                'pulang belum terekam' => 'Pulang Belum Terekam',
                default => ucwords($r),
            };
        }

        if ($tin && $tout) return 'Hadir';
        if ($tin && !$tout) return 'Pulang Belum Terekam';
        return 'Belum Tersedia';
    }

    /* ===================== UPDATE TEAMS ===================== */

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'full_name'  => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'division'   => 'nullable|string|max:50',
        ]);

        $user->name = $validated['name'];
        $user->full_name = $validated['full_name'];
        $user->birth_date = $validated['birth_date'];

        if (Schema::hasColumn('users','division')) {
            $user->division = $validated['division'];
        } elseif (Schema::hasColumn('users','divisi')) {
            $user->divisi = $validated['division'];
        }

        $user->save();

        return back()->with('success', "Data {$user->name} berhasil diperbarui.");
    }
}
