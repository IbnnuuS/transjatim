<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Jobdesk;
use App\Models\JobdeskTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\DailyRecurringTaskService;
use Illuminate\Support\Carbon;

class JobdeskController extends Controller
{
    public function index(Request $request)
    {
        (new DailyRecurringTaskService)->generateForUser(Auth::id());

        $users = User::orderBy('name')->get(['id', 'name', 'division']);

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 30, 50], true)) {
            $perPage = 10;
        }

        $jobdesks = Jobdesk::with([
            'user:id,name,division',
            'tasks' => fn($q) => $q
                ->where(fn($sq) => $sq->where('is_template', 0)->orWhere('is_template', false)->orWhereNull('is_template'))
                // ✅ urutkan berdasarkan submitted_at task (biar task terbaru yg dikerjakan muncul dulu)
                ->orderByDesc('submitted_at')
                ->orderByDesc('schedule_date')
                ->orderByDesc('created_at'),
        ])
            ->where('user_id', Auth::id())
            // ✅ PENTING: HAPUS filter lama yang menyembunyikan assignment sebelum done
            // ->where(function ($q) { ... })
            ->recent()
            ->paginate($perPage)
            ->withQueryString();

        return view('user.jobdeskUser.jobdeskUser', compact('users', 'jobdesks'));
    }

    public function accept(Request $request, $taskId)
    {
        $task = JobdeskTask::with('jobdesk')
            ->whereHas('jobdesk', fn($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($taskId);

        if (strtolower((string) $task->status) !== 'pending') {
            return back()->with('warning', 'Task cannot be accepted (current status: ' . $task->status . ')');
        }

        $nowWib = now('Asia/Jakarta');
        $nowWibTime = $nowWib->format('H:i');

        DB::transaction(function () use ($task, $nowWib, $nowWibTime) {
            // Saat user mulai kerja (accept), set start_time jika kosong
            if (empty($task->start_time)) {
                $task->start_time = $nowWibTime;
            }

            $task->status = 'in_progress';
            $task->progress = 50; // lebih konsisten dengan mapping (bukan 0)

            // ✅ timestamp PER TASK
            $task->submitted_at = $nowWib->copy()->setTimezone('UTC');
            $task->save();

            // (Opsional) header jobdesk ikut berubah untuk sorting header
            if ($task->jobdesk && Schema::hasColumn('jobdesks', 'submitted_at')) {
                $task->jobdesk->submitted_at = $nowWib->copy()->setTimezone('UTC');
                $task->jobdesk->save();
            }

            // ✅ jika task berasal dari penugasan (assignment_id ada) => update assignments juga
            $this->syncTaskToAssignmentIfLinked($task);
        });

        return back()->with('success', 'Task accepted! You can now work on it.');
    }

    public function submit(Request $request, $taskId)
    {
        $task = JobdeskTask::with('jobdesk')
            ->whereHas('jobdesk', fn($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($taskId);

        $request->validate([
            'proof_link'  => ['nullable', 'url', 'max:2056'],
            'status'      => ['required', Rule::in(JobdeskTask::STATUSES)],
            'result'      => ['nullable', 'string'],
            'shortcoming' => ['nullable', 'string'],
            'detail'      => ['nullable', 'string'],
        ]);

        $newStatus = strtolower((string) $request->input('status', 'done'));

        // ✅ proof_link wajib jika done
        if ($newStatus === 'done') {
            $request->validate([
                'proof_link' => ['required', 'url', 'max:2056'],
            ]);
        }

        $newProgress = match ($newStatus) {
            'done'               => 100,
            'pending'            => 0,
            'in_progress'        => 50,
            'sedang_mengerjakan' => 50,
            'verification'       => 80,
            'rework'             => 50,
            'delayed'            => 50,
            'cancelled'          => 50,
            default              => 50,
        };

        $nowWib = now('Asia/Jakarta');
        $nowWibTime = $nowWib->format('H:i');

        $startStatuses = ['in_progress', 'sedang_mengerjakan', 'verification', 'rework'];
        $endStatuses   = ['done', 'cancelled', 'delayed'];

        DB::transaction(function () use ($task, $request, $newStatus, $newProgress, $nowWib, $nowWibTime, $startStatuses, $endStatuses) {
            // Mulai kerja: set start_time jika kosong
            if (in_array($newStatus, $startStatuses, true) && empty($task->start_time)) {
                $task->start_time = $nowWibTime;
            }

            // Selesai: set end_time sekarang, pastikan start_time ada
            if (in_array($newStatus, $endStatuses, true)) {
                if (empty($task->start_time)) {
                    $task->start_time = $nowWibTime;
                }
                $task->end_time = $nowWibTime;
            }

            $task->update([
                'status'       => $newStatus,
                'progress'     => $newProgress,
                'proof_link'   => $request->proof_link,
                'result'       => $request->result,
                'shortcoming'  => $request->shortcoming,
                'detail'       => $request->detail,
                'start_time'   => $task->start_time,
                'end_time'     => $task->end_time,
                'submitted_at' => $nowWib->copy()->setTimezone('UTC'), // ✅ timestamp PER TASK
            ]);

            // (Opsional) header jobdesk ikut update
            if ($task->jobdesk && Schema::hasColumn('jobdesks', 'submitted_at')) {
                $task->jobdesk->submitted_at = $nowWib->copy()->setTimezone('UTC');
                $task->jobdesk->save();
            }

            // ✅ jika task berasal dari penugasan => update assignments
            $this->syncTaskToAssignmentIfLinked($task);
        });

        return back()->with('success', 'Task updated successfully!');
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'               => ['required', 'exists:users,id'],
            'division'              => ['required', 'string', 'max:100'],
            'submitted_at'          => ['nullable', 'string'],

            'tasks.0.judul'         => ['required', 'string', 'max:255'],
            'tasks.0.schedule_date' => ['required', 'date'],
            'tasks.0.status'        => ['required', Rule::in(JobdeskTask::STATUSES)],
            'tasks.0.result'        => ['nullable', 'string'],
            'tasks.0.shortcoming'   => ['nullable', 'string'],
            'tasks.0.detail'        => ['nullable', 'string'],
            'tasks.0.lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'tasks.0.lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'tasks.0.address'       => ['nullable', 'string', 'max:255'],
            'tasks.0.proof_link'    => ['nullable', 'url', 'max:2056'],

            // optional foto base64
            'tasks.0.photos_b64'    => ['nullable', 'array'],
            'tasks.0.photos_b64.*'  => ['nullable', 'string'],
        ]);

        $t0 = $request->input('tasks.0');
        $incomingDivision = $request->input('division') ?? (Auth::user()->division ?? 'Umum');

        $raw = $request->input('submitted_at');
        try {
            $submittedAtUtc = $raw ? Carbon::parse($raw)->setTimezone('UTC') : now();
        } catch (\Throwable $e) {
            $submittedAtUtc = now();
        }

        // ✅ SECURITY FIX: Enforce user_id matches Auth::id() unless Admin
        if (!method_exists(Auth::user(), 'isAdmin') || !Auth::user()->isAdmin()) {
            $request->merge(['user_id' => Auth::id()]);
        }

        $jobdeskData = [
            'user_id'      => $request->user_id,
            'division'     => $incomingDivision,
            'divisi'       => $incomingDivision,
            'submitted_at' => $submittedAtUtc,
            'tanggal'      => now(),
        ];

        DB::beginTransaction();
        try {
            $jobdesk = Jobdesk::create($jobdeskData);

            $statusLower = strtolower((string) ($t0['status'] ?? 'pending'));

            $task = $jobdesk->tasks()->create([
                'judul'         => $t0['judul'],
                'pic'           => '-',
                'schedule_date' => $t0['schedule_date'],
                'start_time'    => null,
                'end_time'      => null,

                'status'        => $statusLower,
                'progress'      => match ($statusLower) {
                    'done'               => 100,
                    'pending'            => 0,
                    'in_progress'        => 50,
                    'sedang_mengerjakan' => 50,
                    'verification'       => 80,
                    'rework'             => 50,
                    'delayed'            => 50,
                    'cancelled'          => 50,
                    default              => 50,
                },

                'result'       => $t0['result']      ?? null,
                'shortcoming'  => $t0['shortcoming'] ?? null,
                'detail'       => $t0['detail']      ?? null,
                'lat'          => $t0['lat']         ?? null,
                'lng'          => $t0['lng']         ?? null,
                'address'      => $t0['address']     ?? null,
                'proof_link'   => $t0['proof_link']  ?? null,

                // ✅ timestamp PER TASK saat dibuat
                'submitted_at' => now('Asia/Jakarta')->setTimezone('UTC'),
                'is_template'  => false,
            ]);

            foreach ((array) ($t0['photos_b64'] ?? []) as $dataUrl) {
                $storedPath = $this->storeDataUrlImage($dataUrl);
                if ($storedPath) {
                    $task->photos()->create(['path' => $storedPath]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['general' => 'Terjadi kesalahan saat menyimpan data.'])
                ->withInput();
        }

        return back()->with('success', 'Jobdesk berhasil disimpan!');
    }

    /**
     * ✅ Jika task terhubung ke assignment (jobdesk.assignment_id ada),
     * maka update table assignments agar sinkron dgn JobdeskTask.
     */
    private function syncTaskToAssignmentIfLinked(JobdeskTask $task): void
    {
        $jobdesk = $task->jobdesk;
        if (!$jobdesk || empty($jobdesk->assignment_id)) {
            return;
        }

        $assignment = Assignment::find($jobdesk->assignment_id);
        if (!$assignment) {
            return;
        }

        $status = strtolower((string) ($task->status ?? 'pending'));
        $progress = (int) ($task->progress ?? 0);

        // ✅ detail jobdesk => assignment.description
        $desc = $task->detail;

        $assignment->update([
            'status'      => $status,
            'progress'    => $progress,
            'proof_link'  => $task->proof_link,
            'result'      => $task->result,
            'shortcoming' => $task->shortcoming,
            'description' => $desc ?? $assignment->description,
        ]);
    }

    private function storeDataUrlImage(string $dataUrl): ?string
    {
        if (!str_starts_with($dataUrl, 'data:image') || !str_contains($dataUrl, ',')) {
            return null;
        }

        [$meta, $base64] = explode(',', $dataUrl, 2);
        $meta = strtolower($meta);

        $ext = 'jpg';
        if (str_contains($meta, 'image/png'))  $ext = 'png';
        if (str_contains($meta, 'image/webp')) $ext = 'webp';
        if (str_contains($meta, 'image/jpeg') || str_contains($meta, 'image/jpg')) $ext = 'jpg';

        $bin = base64_decode($base64, true);
        if ($bin === false) {
            return null;
        }

        $dir  = 'jobdesk/' . now()->format('Y/m/d');
        $name = uniqid('jb_', true) . '.' . $ext;
        $path = $dir . '/' . $name;

        Storage::disk('public')->put($path, $bin);

        return $path;
    }
}
