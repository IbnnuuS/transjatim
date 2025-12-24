<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Jobdesk;
use App\Models\JobdeskTask;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    /**
     * ✅ 1 sumber kebenaran status (lengkap)
     */
    private function statusWhitelist(): array
    {
        return ['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'];
    }

    /**
     * ✅ progress otomatis dari status (sama dengan user)
     */
    private function progressByStatus(string $status): int
    {
        $status = strtolower($status);

        return match ($status) {
            'pending'      => 0,
            'in_progress'  => 50,
            'verification' => 80,
            'rework'       => 50,
            'delayed'      => 50,
            'cancelled'    => 50,
            'done'         => 100,
            default        => 0,
        };
    }

    public function index(Request $request)
    {
        $whitelist = $this->statusWhitelist();

        $from        = $request->get('from');
        $to          = $request->get('to');
        $status      = strtolower((string)$request->get('status'));
        $employeeId  = $request->get('employee_id');
        $q           = $request->get('q');

        if (!in_array($status, $whitelist, true)) {
            $status = null;
        }

        $query = Assignment::with(['employee'])
            ->when($from && $to, fn($q1) => $q1->whereBetween('deadline', [$from, $to]))
            ->when($status, fn($q1) => $q1->where('status', $status))
            ->when($employeeId, fn($q1) => $q1->where('employee_id', $employeeId))
            ->when($q, function ($q1) use ($q) {
                $q1->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->orderBy('deadline', 'asc')
            ->orderBy('created_at', 'desc');

        $assignments = $query->paginate(15)->appends($request->query());

        $employees = User::query()
            ->where('role', 'user')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $statuses = $whitelist;

        // $today = Carbon::now('Asia/Jakarta')->toDateString();

        // ✅ REVISI: Global Stats (Realtime)
        $stats = [
            'assigned_today' => Assignment::count(),
            'pending_today'  => Assignment::where('status', 'pending')->count(),
            'done_today'     => Assignment::where('status', 'done')->count(),
        ];

        return view('admin.penugasan.penugasanAdmin', compact(
            'assignments',
            'employees',
            'statuses',
            'stats'
        ));
    }

    /**
     * ✅ ADMIN CREATE assignment → default pending
     */
    public function store(Request $request)
    {
        // ✅ Explicit Admin Check
        abort_unless(auth()->user()->isAdmin(), 403);

        $whitelist = $this->statusWhitelist();

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'deadline'    => ['required', 'date'],
            'employee_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'user')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $status = 'pending';
            $progress = $this->progressByStatus($status);

            $assignment = Assignment::create([
                'title'       => $data['title'],
                'deadline'    => $data['deadline'],
                'employee_id' => $data['employee_id'],
                'description' => $data['description'] ?? null,
                'status'      => $status,
                'progress'    => $progress,
            ]);

            // Disable sync on create (wait for done)
            // $this->syncAssignmentToJobdesk($assignment);
        });

        return redirect()
            ->route('admin.penugasan.index')
            ->with('success', 'Penugasan berhasil dibuat & otomatis masuk Jobdesk user.');
    }

    /**
     * ✅ ADMIN UPDATE assignment → otomatis update JobdeskTask juga
     */
    public function update(Request $request, Assignment $assignment)
    {
        // ✅ Explicit Admin Check
        abort_unless(auth()->user()->isAdmin(), 403);

        $whitelist = $this->statusWhitelist();

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'deadline'    => ['required', 'date'],
            'employee_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'user')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'status'      => ['required', Rule::in($whitelist)],

            'result'      => ['nullable', 'string'],
            'shortcoming' => ['nullable', 'string'],
            'detail'      => ['nullable', 'string'],
            'proof_link'  => ['nullable', 'url'],
        ]);

        DB::transaction(function () use ($assignment, $data) {

            $status   = strtolower($data['status']);
            $progress = $this->progressByStatus($status);

            if ($status === 'done' && empty($data['proof_link'])) {
                throw new \Exception('Link bukti wajib diisi jika status Done.');
            }

            $assignment->update([
                'title'       => $data['title'],
                'deadline'    => $data['deadline'],
                'employee_id' => $data['employee_id'],
                'description' => $data['description'] ?? null,
                'status'      => $status,
                'progress'    => $progress,
                'result'      => $data['result'] ?? null,
                'shortcoming' => $data['shortcoming'] ?? null,
                'detail'      => $data['detail'] ?? null,
                'proof_link'  => $data['proof_link'] ?? null,
            ]);

            if ($status === 'done') {
                $this->syncAssignmentToJobdesk($assignment);
            }
        });

        return redirect()
            ->route('admin.penugasan.index')
            ->with('success', 'Penugasan berhasil diperbarui & otomatis sinkron ke Jobdesk user.');
    }

    /**
     * ✅ DELETE assignment + jobdesk/task
     */
    public function destroy(Assignment $assignment)
    {
        // ✅ Explicit Admin Check
        abort_unless(auth()->user()->isAdmin(), 403);

        DB::transaction(function () use ($assignment) {
            Jobdesk::where('assignment_id', $assignment->id)->delete();
            JobdeskTask::where('assignment_id', $assignment->id)->delete();
            $assignment->delete();
        });

        return redirect()
            ->route('admin.penugasan.index')
            ->with('success', 'Penugasan berhasil dihapus + jobdesk/task ikut dihapus.');
    }

    /**
     * ✅ Fix utama: SYNC pakai assignment_id, bukan task terbaru
     */
    private function syncAssignmentToJobdesk(Assignment $assignment): void
    {
        $nowWib = now('Asia/Jakarta');

        $jobdesk = Jobdesk::firstOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id'       => $assignment->employee_id,
            ],
            [
                'division'     => $assignment->employee->division ?? 'Umum',
                'divisi'       => $assignment->employee->division ?? 'Umum',
                'tanggal'      => $nowWib->toDateString(),
                'submitted_at' => $nowWib->copy()->setTimezone('UTC'),
            ]
        );

        $jobdesk->submitted_at = $nowWib->copy()->setTimezone('UTC');
        $jobdesk->save();

        // ✅ FIX: Use jobdesk_id as unique key to prevent duplicates
        // Set is_template = false (0) because assignment is a task, not a template.
        $task = JobdeskTask::updateOrCreate(
            [
                'jobdesk_id'     => $jobdesk->id,
            ],
            [
                'judul'        => $assignment->title,
                'detail'       => $assignment->description ?? null,
                'status'       => $assignment->status,
                'progress'     => (int)$assignment->progress,
                'proof_link'   => $assignment->proof_link,
                'result'       => $assignment->result,
                'shortcoming'  => $assignment->shortcoming,
                'pic'          => $assignment->employee->name ?? '-',
                'schedule_date' => $assignment->deadline,
                'submitted_at' => $nowWib->copy()->setTimezone('UTC'),
                'is_template'  => false,
                // Opsional: simpan assignment_id di task kalau kolomnya ada
                'assignment_id' => $assignment->id,
            ]
        );

        // ✅ auto end_time bila done
        if ((int)$task->progress >= 100) {
            $task->status = 'done';
            if (empty($task->end_time)) {
                $task->end_time = $nowWib->format('H:i');
            }
        }

        $task->save();
    }
}
