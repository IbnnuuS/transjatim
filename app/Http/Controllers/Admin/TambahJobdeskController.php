<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Jobdesk;
use App\Models\JobdeskTask;
use App\Models\JobdeskTaskPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Services\DailyRecurringTaskService;

class TambahJobdeskController extends Controller
{
    /**
     * Tampilkan halaman form tambah jobdesk (admin).
     */
    public function create()
    {
        // ✅ Explicit Admin Check
        abort_unless(auth()->user()->isAdmin(), 403);

        // ✅ hanya tampilkan user (bukan admin)
        $users = User::query()
            ->where('role', 'user')
            ->orderBy('name')
            ->get(['id', 'name', 'division', 'role']);

        return view('admin.tambahJobdesk.tambahJobdesk', compact('users'));
    }

    /**
     * Simpan jobdesk baru dari admin.
     */
    public function store(Request $request)
    {
        // ✅ Explicit Admin Check
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            // ✅ pastikan yang dipilih benar-benar role user
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'user')),
            ],

            'submitted_at' => ['nullable', 'string'],

            'tasks.0.judul'         => ['required', 'string', 'max:255'],
            'tasks.0.schedule_date' => ['required', 'date'],
            'tasks.0.start_time'    => ['nullable'],
            'tasks.0.end_time'      => ['nullable'],
            'tasks.0.result'        => ['nullable', 'string'],
            'tasks.0.shortcoming'   => ['nullable', 'string'],
            'tasks.0.detail'        => ['nullable', 'string'],
            'tasks.0.photos_b64'    => ['nullable', 'array'],
            'tasks.0.photos_b64.*'  => ['string'],
            'is_recurring'          => ['nullable', 'boolean'],
        ]);

        $t0 = $request->input('tasks.0');
        $targetUser = User::where('role', 'user')->findOrFail($request->user_id);

        try {
            $submittedAt = $request->submitted_at
                ? Carbon::parse($request->submitted_at)
                : now('Asia/Jakarta');
        } catch (\Throwable $e) {
            $submittedAt = now('Asia/Jakarta');
        }

        DB::beginTransaction();

        try {
            $jobdesk = Jobdesk::create([
                'user_id'      => (int) $request->user_id,
                'division'     => $request->division ?? ($targetUser->division ?? 'Umum'),
                'submitted_at' => $submittedAt,
                'is_recurring' => $request->boolean('is_recurring'),
            ]);

            $task = JobdeskTask::create([
                'jobdesk_id'    => $jobdesk->id,
                'judul'         => $t0['judul'],
                'pic'           => $t0['pic'] ?? $targetUser->name,
                'schedule_date' => $t0['schedule_date'],
                'start_time'    => $t0['start_time'] ?? null,
                'end_time'      => $t0['end_time'] ?? null,
                'status'        => 'pending',
                'progress'      => 0,
                'result'        => $t0['result']      ?? null,
                'shortcoming'   => $t0['shortcoming'] ?? null,
                'detail'        => $t0['detail']      ?? null,
                'is_template'   => 1,
            ]);

            foreach ((array) ($t0['photos_b64'] ?? []) as $b64) {
                if (!is_string($b64) || !str_starts_with($b64, 'data:image')) continue;

                [$meta, $data] = explode(',', $b64, 2) + [null, null];
                if (!$meta || !$data) continue;

                $bin = base64_decode($data, true);
                if ($bin === false) continue;

                $ext = 'jpg';
                if (preg_match('#^data:image/(\w+);base64$#', strtolower($meta), $m)) {
                    $ext = strtolower($m[1]);
                    if ($ext === 'jpeg') $ext = 'jpg';
                }

                $dir  = 'jobdesk/' . now('Asia/Jakarta')->format('Y/m/d');
                $name = 'jb_' . Str::random(16) . '.' . $ext;
                $path = $dir . '/' . $name;

                Storage::disk('public')->put($path, $bin);

                JobdeskTaskPhoto::create([
                    'task_id' => $task->id,
                    'path'    => $path,
                ]);
            }

            DB::commit();

            // Trigger generation for today immediately
            (new DailyRecurringTaskService)->generateForUser($request->user_id);

            return redirect()
                ->route('admin.tambahJobdesk.create')
                ->with('success', 'Jobdesk berhasil ditambahkan (Status: Pending)!');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['msg' => 'Gagal menyimpan: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
