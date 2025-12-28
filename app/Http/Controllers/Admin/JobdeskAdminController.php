<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobdeskTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class JobdeskAdminController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->input('q', ''));
        $userId   = $request->input('user_id');
        $divisi   = trim((string) $request->input('division', ''));
        $status   = trim((string) $request->input('status', ''));
        $date     = $request->input('date');

        $buildQuery = function () use ($q, $userId, $divisi, $date) {
            return JobdeskTask::query()
                ->where(function ($w) {
                    $w->where('is_template', 0)->orWhereNull('is_template');
                })
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->where(function ($w) use ($q) {
                        $w->where('judul', 'like', "%{$q}%")
                            ->orWhere('pic', 'like', "%{$q}%")
                            ->orWhereHas('jobdesk.user', fn($wu) => $wu->where('name', 'like', "%{$q}%"))
                            ->orWhereHas('jobdesk', fn($wj) => $wj->where('division', 'like', "%{$q}%"));
                    });
                })
                ->when($userId, function ($qq) use ($userId) {
                    $safeUser = User::where('id', $userId)
                        ->where(function ($q) {
                            $q->whereNull('role')->orWhere('role', 'user');
                        })
                        ->exists();

                    if ($safeUser) {
                        $qq->whereHas('jobdesk', fn($w) => $w->where('user_id', $userId));
                    }
                })
                ->when($divisi !== '', function ($qq) use ($divisi) {
                    $qq->where(function ($sub) use ($divisi) {
                        $sub->whereHas('jobdesk', fn($w) => $w->where('division', $divisi))
                            ->orWhereHas('jobdesk.user', fn($wu) => $wu->where('division', $divisi));
                    });
                })
                ->when($date, fn($qq) => $qq->whereDate('schedule_date', '=', $date));
        };

        $rows = $buildQuery()
            ->with([
                'jobdesk:id,user_id,division',
                'jobdesk.user:id,name,division',
                'photos:id,task_id,path',
            ])
            ->when($status !== '', fn($qq) => $qq->where('status', $status))
            ->orderByDesc('schedule_date')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $sumByStatus = $buildQuery()
            ->select('status', DB::raw('COUNT(*) as qty'))
            ->groupBy('status')
            ->pluck('qty', 'status')
            ->toArray();

        $summary = [
            'total'        => $rows->total(),
            'to_do'        => (int)($sumByStatus['to_do'] ?? 0),
            'pending'      => (int)($sumByStatus['pending'] ?? 0),
            'in_progress'  => (int)($sumByStatus['in_progress'] ?? 0),
            'verification' => (int)($sumByStatus['verification'] ?? 0),
            'rework'       => (int)($sumByStatus['rework'] ?? 0),
            'delayed'      => (int)($sumByStatus['delayed'] ?? 0),
            'cancelled'    => (int)($sumByStatus['cancelled'] ?? 0),
            'done'         => (int)($sumByStatus['done'] ?? 0),
        ];

        $kanbanStatuses = ['to_do', 'pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'];

        $users = User::query()
            ->where(function ($q) {
                $q->whereNull('role')->orWhere('role', 'user');
            })
            ->select('id', 'name', 'division')
            ->orderBy('name')
            ->get();

        return view('admin.jobdesk.jobdeskAdmin', [
            'rows'    => $rows,
            'users'   => $users,
            'filters' => [
                'q'        => $q,
                'user_id'  => $userId,
                'division' => $divisi,
                'status'   => $status,
                'date'     => $date,
            ],
            'summary' => $summary,
        ]);
    }

    protected function normalizeStatus(?string $raw): ?string
    {
        if (!$raw) return null;

        $s = strtolower(trim($raw));
        $s = str_replace([' ', '-', '__'], '_', $s);

        if (in_array($s, ['todo', 'to-do', 'to__do'])) $s = 'to_do';
        if (in_array($s, ['inprogress', 'in__progress', 'in_progess', 'in_proggress'])) $s = 'in_progress';

        $allowed = ['to_do', 'pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'];

        return in_array($s, $allowed, true) ? $s : null;
    }

    public function edit($id)
    {
        $task = JobdeskTask::with(['photos', 'jobdesk.user'])->findOrFail($id);
        return view('admin.jobdesk.edit', compact('task'));
    }

    public function submitLikeUser(Request $request, $id)
    {
        $task = JobdeskTask::with('jobdesk')->findOrFail($id);

        $request->validate([
            'judul'       => ['required', 'string', 'max:255'],
            'proof_link'  => ['nullable', 'url', 'max:2056'],
            'status'      => ['required', Rule::in(JobdeskTask::STATUSES)],
            'result'      => ['nullable', 'string'],
            'shortcoming' => ['nullable', 'string'],
            'detail'      => ['nullable', 'string'],
        ]);

        $newStatus = strtolower((string) $request->input('status', 'pending'));

        if ($newStatus === 'done') {
            $request->validate([
                'proof_link' => ['required', 'url', 'max:2056'],
            ]);
        }

        $newProgress = match ($newStatus) {
            'done'         => 100,
            'pending'      => 0,
            'in_progress'  => 50,
            'verification' => 80,
            'rework'       => 50,
            'delayed'      => 50,
            'cancelled'    => 50,
            default        => 50,
        };

        $nowWib = now('Asia/Jakarta');
        $nowWibTime = $nowWib->format('H:i');

        $startStatuses = ['in_progress', 'verification', 'rework'];
        $endStatuses   = ['done', 'cancelled', 'delayed'];

        DB::transaction(function () use ($task, $request, $newStatus, $newProgress, $nowWib, $nowWibTime, $startStatuses, $endStatuses) {

            if (in_array($newStatus, $startStatuses, true) && empty($task->start_time)) {
                $task->start_time = $nowWibTime;
            }

            if (in_array($newStatus, $endStatuses, true)) {
                if (empty($task->start_time)) {
                    $task->start_time = $nowWibTime;
                }
                $task->end_time = $nowWibTime;
            }

            $task->update([
                'judul'        => $request->judul,
                'status'       => $newStatus,
                'progress'     => $newProgress,
                'proof_link'   => $request->proof_link,
                'result'       => $request->result,
                'shortcoming'  => $request->shortcoming,
                'detail'       => $request->detail,
                'start_time'   => $task->start_time,
                'end_time'     => $task->end_time,
                'submitted_at' => $nowWib->copy()->setTimezone('UTC'),
            ]);
        });

        return back()->with('success', 'Task updated successfully (Admin).');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string|max:50']);

        $task = JobdeskTask::findOrFail($id);
        $normalized = $this->normalizeStatus($request->input('status'));

        if (!$normalized) {
            return back()->with('warning', 'Status tidak dikenali / tidak diizinkan.');
        }

        $task->status = $normalized;
        $task->progress = match ($normalized) {
            'done' => 100,
            'pending', 'to_do', 'cancelled' => 0,
            default => 50
        };

        $task->save();

        return back()->with('success', 'Status berhasil diperbarui.');
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'min:1'],
            'action' => ['required', 'string'],
        ]);

        $ids    = collect($request->ids)->map(fn($v) => (int)$v)->unique()->values();
        $action = $request->action;

        if ($action === 'delete') {
            $tasks = JobdeskTask::with('photos')->whereIn('id', $ids)->get();
            foreach ($tasks as $t) {
                foreach ($t->photos as $p) {
                    $path = ltrim((string)$p->path, '/');
                    $path = preg_replace('~^public/~', '', $path);
                    if ($path) Storage::disk('public')->delete($path);
                    $p->delete();
                }
                $t->delete();
            }
            return back()->with('success', 'Item terpilih berhasil dihapus.');
        }

        if (str_starts_with($action, 'status:')) {
            $raw = substr($action, 7);
            $normalized = $this->normalizeStatus($raw);

            if (!$normalized) {
                return back()->with('warning', 'Status bulk tidak dikenali / tidak diizinkan.');
            }

            $newProgress = match ($normalized) {
                'done' => 100,
                'pending', 'to_do', 'cancelled' => 0,
                default => 50
            };

            JobdeskTask::whereIn('id', $ids)->update([
                'status'   => $normalized,
                'progress' => $newProgress
            ]);

            return back()->with('success', 'Status item terpilih diperbarui.');
        }

        return back()->with('warning', 'Aksi tidak dikenali.');
    }

    public function destroy($id)
    {
        $task = JobdeskTask::with('photos')->findOrFail($id);

        foreach ($task->photos as $p) {
            $path = ltrim((string)$p->path, '/');
            $path = preg_replace('~^public/~', '', $path);
            if ($path) Storage::disk('public')->delete($path);
            $p->delete();
        }

        $task->delete();
        return back()->with('success', 'Item berhasil dihapus.');
    }
}
