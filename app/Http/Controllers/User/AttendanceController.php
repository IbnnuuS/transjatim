<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * POST /attendance/punch-in
     * route name: attendance.punch_in
     */
    public function punchIn(Request $request)
    {
        $user = Auth::user();
        abort_unless((bool)$user, 401);

        // Default zona waktu Asia/Jakarta
        $nowJkt = Carbon::now('Asia/Jakarta');
        $today  = $nowJkt->toDateString(); // YYYY-MM-DD

        // Validasi input
        $validated = $request->validate([
            'note'  => ['nullable', 'string', 'max:255'],
        ]);

        try {
            // Transaksikan agar konsisten
            DB::transaction(function () use ($user, $today, $nowJkt, $validated) {
                // Ambil atau buat record kehadiran hari ini
                $attendance = Attendance::firstOrCreate(
                    ['employee_id' => $user->id, 'date' => $today],
                    // jika baru dibuat, set status default present
                    ['status' => 'present']
                );

                // Cegah double punch-in
                if (!empty($attendance->in_time)) {
                    throw new \RuntimeException('Anda sudah melakukan absen masuk hari ini.');
                }

                $status = $attendance->status ?: 'present';

                // LOGIKA SEDERHANA TANPA SHIFT:
                // Cek jam masuk > 08:15 (misalnya) -> late.
                // Jika ingin dinamis, bisa ambil dari Settings. Ini contoh hardcode 08:00
                $baseline = '08:00';
                $lateThreshold = Carbon::createFromFormat('H:i', $baseline, 'Asia/Jakarta')->addMinutes(15);

                if ($nowJkt->format('H:i') > $lateThreshold->format('H:i')) {
                    $status = 'late';
                } else {
                    $status = 'present';
                }

                // Set field kehadiran
                $attendance->status = $status;

                // Jika Anda menyimpan divisi di Attendance (opsional)
                if (property_exists($attendance, 'division')) {
                    $attendance->division = $attendance->division ?: ($user->division ?? null);
                }

                $attendance->in_time  = $nowJkt->format('H:i'); // simpan HH:MM
                $attendance->note     = $this->concatNote($attendance->note ?? null, $validated['note'] ?? null);

                $attendance->save();

                /**
                 * ==========================================
                 *  AUTO-BUAT JADWAL DARI ABSENSI (USER ABSEN SENDIRI)
                 * ==========================================
                 * Jika hari ini belum ada Schedule untuk user ini,
                 * kita buat jadwal otomatis supaya muncul di:
                 * - Jadwal Saya
                 * - Kalender user
                 */
                $hasScheduleToday = Schedule::where('user_id', $user->id)
                    ->whereDate('tanggal', $today)
                    ->exists();

                if (!$hasScheduleToday) {
                    $autoTitle = 'Hadir';

                    Schedule::create([
                        'user_id'     => $user->id,
                        'tanggal'     => $today,
                        'divisi'      => $user->division ?? 'teknik',      // sesuaikan kebutuhan / default
                        'judul'       => $autoTitle,
                        'catatan'     => 'Jadwal otomatis dari absensi mandiri.',
                        'jam_mulai'   => null,
                        'jam_selesai' => null,
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['attendance' => $e->getMessage()]);
        }

        return back()->with('success', 'Absen masuk berhasil dicatat.');
    }

    /**
     * POST /attendance/punch-out
     * route name: attendance.punch_out
     */
    public function punchOut(Request $request)
    {
        $user = Auth::user();
        abort_unless((bool)$user, 401);

        $nowJkt = Carbon::now('Asia/Jakarta');
        $today  = $nowJkt->toDateString();

        $validated = $request->validate([
            'note'            => ['nullable', 'string', 'max:255'],
            'overtime_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($user, $today, $nowJkt, $validated) {
                // ... (finding attendance logic remains same, implicit here or handled below)
                $attendance = Attendance::where('employee_id', $user->id)
                    ->where('date', $today)
                    ->lockForUpdate()
                    ->first();

                if (!$attendance) {
                    $attendance = Attendance::create([
                        'employee_id' => $user->id,
                        'date'        => $today,
                        'status'      => 'present',
                        'in_time'     => $nowJkt->format('H:i'),
                    ]);
                }

                if (empty($attendance->in_time)) {
                    $attendance->in_time = $nowJkt->format('H:i');
                }

                if (!empty($attendance->out_time)) {
                    throw new \RuntimeException('Anda sudah melakukan absen pulang hari ini.');
                }

                $attendance->out_time = $nowJkt->format('H:i');

                // Hitung Overtime jika pulang > 16:00
                $threshold = Carbon::createFromFormat('Y-m-d H:i', $today . ' 16:00', 'Asia/Jakarta');
                if ($nowJkt->greaterThan($threshold)) {
                    $diffMinutes = (int) $threshold->diffInMinutes($nowJkt);
                    $attendance->overtime_minutes = $diffMinutes;

                    // Gunakan input overtime_reason jika ada, fallback ke note, fallback ke '-'
                    $reason = $validated['overtime_reason'] ?? ($validated['note'] ?? '-');
                    $attendance->overtime_reason = $reason;
                } else {
                    $attendance->overtime_minutes = 0;
                    $attendance->overtime_reason  = null;
                }


                // Append overtime reason to note as well if requested "masuk kolom catatan"
                // But avoid duplication if note == overtime_reason
                $finalNote = $validated['note'] ?? null;
                if ($attendance->overtime_minutes > 0 && !empty($validated['overtime_reason'])) {
                    $finalNote = $this->concatNote($finalNote, 'Overtime: ' . $validated['overtime_reason']);
                }
                $attendance->note = $this->concatNote($attendance->note ?? null, $finalNote);

                // Normalisasi status akhir:
                // - Biarkan "present", "late" apa adanya
                if (!in_array($attendance->status, ['present', 'late'])) {
                    $attendance->status = 'present';
                }

                $attendance->save();
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['attendance' => $e->getMessage()]);
        }

        return back()->with('success', 'Absen pulang berhasil dicatat.');
    }

    /**
     * Gabungkan catatan lama & baru secara aman.
     */
    private function concatNote(?string $old, ?string $new): ?string
    {
        $old = trim((string)$old);
        $new = trim((string)$new);
        if ($old === '' && $new === '') return null;
        if ($old === '') return $new;
        if ($new === '') return $old;
        return $old . ' | ' . $new;
    }
}
