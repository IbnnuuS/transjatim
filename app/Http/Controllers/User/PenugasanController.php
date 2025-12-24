<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Jobdesk;
use App\Models\JobdeskTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenugasanController extends Controller
{
    /**
     * Halaman daftar penugasan user + ringkasan stats hari ini.
     */
    public function index()
    {
        $userId = auth()->id();

        $assignments = Assignment::query()
            ->forUser($userId)
            ->when(request('status'), fn($q, $st) => $q->where('status', $st))
            ->when(request('q'), function ($q, $s) {
                $q->where(function ($qq) use ($s) {
                    $qq->where('title', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->when(request('from'), fn($q, $from) => $q->whereDate('deadline', '>=', $from))
            ->when(request('to'),   fn($q, $to)   => $q->whereDate('deadline', '<=', $to))
            ->orderBy('deadline', 'asc')
            ->paginate(10)
            ->withQueryString();

        // $today = now('Asia/Jakarta')->toDateString();

        // ✅ REVISI: Hitungan Global (Realtime All Time)
        $totalAll     = Assignment::forUser($userId)->count();
        $pendingAll   = Assignment::forUser($userId)->where(fn($q) => $q->where('status', 'pending')->orWhere('status', 'in_progress')->orWhere('status', 'verification')->orWhere('status', 'rework')->orWhere('status', 'delayed'))->count();
        // Atau simpelnya: bukan done dan cancelled?
        // User minta "Pending", biasanya yg belum selesai. Mari kita ambil yg benar-benar "Belum Selesai".
        // Definisi user: "Pending" di card.
        // Mari kita ambil: Pending + In Progress + dll (Active) vs Done.
        // Tapi card labelnya "Pending", mungkin maksudnya status 'pending'?
        // "Total Deadline", "Pending", "Selesai".
        // Asumsi:
        // Total Deadline -> Total Penugasan (Semua)
        // Pending -> Total status Pending (atau active tasks?) -> Request user: "Pending" card. Biasa user anggap pending = belum kelar.
        // Selesai -> Total done.

        // Opsional: Jika user maksud "Pending" strict status pending:
        // $pendingAll = Assignment::forUser($userId)->where('status', 'pending')->count();

        // Namun "Pending" di dashboard biasanya "Outstanding".
        // Saya akan gunakan pure 'pending' status count agar sesuai label,
        // tapi jika user komplain "kok in_progress ga masuk", nanti kita ubah.
        // Sesuai request sebelumnya: "total deadline, pending, dan card selesai".
        // Mari stick to literal status for now, or 'active'.
        // Let's count 'Pending' specifically as requested, or maybe 'Belum Selesai' is better context.
        // I will use 'Pending' status strictly to be safe, or maybe 'Not Done'?
        // Let's use 'Pending' status strictly first.
        $pendingAll = Assignment::forUser($userId)->where('status', 'pending')->count();
        $doneAll    = Assignment::forUser($userId)->where('status', 'done')->count();

        $stats = [
            'today_total'   => $totalAll,    // Reuse key biar blade ga error dulu
            'today_pending' => $pendingAll,
            'today_done'    => $doneAll,
        ];

        return view('user.penugasanUser.penugasanUser', [
            'assignments' => $assignments,
            'stats'       => $stats,
            'statuses'    => ['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'],
        ]);
    }

    public function accept(Assignment $assignment)
    {
        if ($assignment->employee_id && (int)$assignment->employee_id !== (int)auth()->id()) {
            abort(403);
        }

        if (strtolower((string)$assignment->status) !== 'pending') {
            return back()->with('warning', 'Tugas sudah diproses atau selesai.');
        }

        DB::transaction(function () use ($assignment) {
            $assignment->update([
                'status'   => 'in_progress',
                'progress' => 50,
            ]);

            // ✅ SYNC ke jobdesk/task DIBATALKAN saat accept (hanya saat DONE)
            // $this->syncAssignmentToJobdesk($assignment, [
            //     'status'      => 'in_progress',
            //     'progress'    => 50,
            //     'detail'      => $assignment->description,
            //     'result'      => $assignment->result ?? null,
            //     'shortcoming' => $assignment->shortcoming ?? null,
            //     'proof_link'  => $assignment->proof_link ?? null,
            // ]);
        });

        return back()->with('success', 'Tugas diterima! Silakan kerjakan.');
    }

    /**
     * ✅ Update penugasan: status + detail + proof_link/result/shortcoming
     * ✅ AUTO SYNC ke JobdeskTask (tidak hanya saat done)
     */
    public function submit(Assignment $assignment, Request $request)
    {
        if ($assignment->employee_id && (int)$assignment->employee_id !== (int)auth()->id()) {
            abort(403, 'Unauthorized access to this assignment.');
        }

        $request->validate([
            'status'      => ['required', 'in:pending,in_progress,verification,rework,delayed,cancelled,done'],
            'detail'      => ['nullable', 'string'],
            'proof_link'  => ['nullable', 'url', 'max:2056'],
            'result'      => ['nullable', 'string'],
            'shortcoming' => ['nullable', 'string'],
        ]);

        $newStatus = strtolower($request->input('status'));

        // proof_link wajib jika done
        if ($newStatus === 'done') {
            $request->validate([
                'proof_link' => ['required', 'url', 'max:2056'],
            ]);
        }

        $newProgress = match ($newStatus) {
            'pending'      => 0,
            'in_progress'  => 50,
            'verification' => 80,
            'rework'       => 50,
            'delayed'      => 50,
            'cancelled'    => 50,
            'done'         => 100,
            default        => (int)($assignment->progress ?? 0),
        };

        $detailValue = $request->input('detail');

        DB::transaction(function () use ($assignment, $request, $newStatus, $newProgress, $detailValue) {
            // 1) update assignment
            $payload = [
                'status'      => $newStatus,
                'progress'    => $newProgress,
                'proof_link'  => $request->input('proof_link'),
                'result'      => $request->input('result'),
                'shortcoming' => $request->input('shortcoming'),
            ];

            if ($detailValue !== null) {
                $payload['description'] = $detailValue;
            }

            $assignment->update($payload);

            // 2) ✅ SYNC ke jobdesk/task (UPSERT) HANYA jika DONE
            if ($newStatus === 'done') {
                $this->syncAssignmentToJobdesk($assignment, [
                    'status'      => $newStatus,
                    'progress'    => $newProgress,
                    'detail'      => $detailValue ?? $assignment->description,
                    'result'      => $request->input('result'),
                    'shortcoming' => $request->input('shortcoming'),
                    'proof_link'  => $request->input('proof_link'),
                ]);
            }
        });

        return redirect()->route('penugasan.user')->with('success', 'Penugasan berhasil diperbarui & tersinkron ke Jobdesk!');
    }

    /**
     * ✅ Optional meta update: juga auto sync ke jobdesk
     */
    public function updateMeta(Assignment $assignment, Request $request)
    {
        if ($assignment->employee_id && (int)$assignment->employee_id !== (int)auth()->id()) {
            abort(403);
        }

        $request->validate([
            'status' => ['nullable', 'in:pending,in_progress,verification,rework,delayed,cancelled,done'],
            'detail' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($assignment, $request) {
            $payload = [];

            $st = null;
            $progress = null;

            if ($request->filled('status')) {
                $st = strtolower($request->input('status'));
                $progress = match ($st) {
                    'pending'      => 0,
                    'in_progress'  => 50,
                    'verification' => 80,
                    'rework'       => 50,
                    'delayed'      => 50,
                    'cancelled'    => 50,
                    'done'         => 100,
                    default        => (int)($assignment->progress ?? 0),
                };

                $payload['status'] = $st;
                $payload['progress'] = $progress;
            }

            if ($request->has('detail')) {
                $payload['description'] = $request->input('detail');
            }

            if (!empty($payload)) {
                $assignment->update($payload);
            }

            // ✅ sync juga HANYA JIKA status == done
            $currentStatus = $st ?? strtolower((string)$assignment->status);
            if ($currentStatus === 'done') {
                $this->syncAssignmentToJobdesk($assignment, [
                    'status'      => $currentStatus,
                    'progress'    => $progress ?? (int)($assignment->progress ?? 0),
                    'detail'      => $request->input('detail', $assignment->description),
                    'result'      => $assignment->result ?? null,
                    'shortcoming' => $assignment->shortcoming ?? null,
                    'proof_link'  => $assignment->proof_link ?? null,
                ]);
            }
        });

        return back()->with('success', 'Status / detail berhasil diperbarui & tersinkron ke Jobdesk!');
    }

    /**
     * ✅ INTI: sinkron Assignment -> Jobdesk + JobdeskTask
     * - jobdesk dibuat kalau belum ada (assignment_id)
     * - task di-upsert (1 task utama per assignment) supaya status/progress/result/dll selalu sama
     */
    private function syncAssignmentToJobdesk(Assignment $assignment, array $data): void
    {
        $userId = auth()->id();
        $nowWib = now('Asia/Jakarta');

        // 1) upsert jobdesk header
        $jobdesk = Jobdesk::firstOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id'       => $userId,
            ],
            [
                'division'     => auth()->user()->division ?? 'Umum',
                'divisi'       => auth()->user()->division ?? 'Umum',
                'tanggal'      => now(),
                'submitted_at' => $nowWib->copy()->setTimezone('UTC'),
            ]
        );

        // update header timestamp (opsional tapi membantu sorting)
        if (isset($jobdesk->submitted_at)) {
            $jobdesk->submitted_at = $nowWib->copy()->setTimezone('UTC');
            $jobdesk->save();
        }

        // 2) upsert task utama (gunakan kombinasi jobdesk_id + judul)
        //    Kalau kamu punya kolom assignment_id di jobdesk_tasks akan lebih bagus,
        //    tapi kita pakai judul sebagai kunci aman.
        $task = JobdeskTask::updateOrCreate(
            [
                'jobdesk_id'  => $jobdesk->id,
            ],
            [
                'judul'         => $assignment->title,
                'is_template'   => false,
                'pic'           => auth()->user()->name,
                'schedule_date' => $assignment->deadline
                    ? $assignment->deadline->toDateString()
                    : now('Asia/Jakarta')->toDateString(),

                'status'        => $data['status'] ?? 'pending',
                'progress'      => (int)($data['progress'] ?? 0),

                'result'        => $data['result'] ?? null,
                'shortcoming'   => $data['shortcoming'] ?? null,
                'detail'        => $data['detail'] ?? null,
                'proof_link'    => $data['proof_link'] ?? null,

                // ✅ timestamp activity (biar jobdesk list & dashboard terbaru)
                'submitted_at'  => $nowWib->copy()->setTimezone('UTC'),
                // Opsional: simpan assignment_id di task kalau kolomnya ada
                'assignment_id' => $assignment->id,
            ]
        );

        // 3) start/end time logic (mirip JobdeskController@submit)
        $st = strtolower((string)($data['status'] ?? 'pending'));
        $nowWibTime = $nowWib->format('H:i');

        $startStatuses = ['in_progress', 'sedang_mengerjakan', 'verification', 'rework'];
        $endStatuses   = ['done', 'cancelled', 'delayed'];

        if (in_array($st, $startStatuses, true) && empty($task->start_time)) {
            $task->start_time = $nowWibTime;
        }

        if (in_array($st, $endStatuses, true)) {
            if (empty($task->start_time)) {
                $task->start_time = $nowWibTime;
            }
            $task->end_time = $nowWibTime;
        }

        $task->save();
    }
}
