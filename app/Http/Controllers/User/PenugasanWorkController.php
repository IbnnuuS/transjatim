<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Jobdesk;
use App\Models\JobdeskTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class PenugasanWorkController extends Controller
{
    public function store(Request $request, Assignment $assignment)
    {
        $ownerId = method_exists($assignment, 'getOwnerUserIdAttribute')
            ? ($assignment->owner_user_id ?? $assignment->employee_id ?? $assignment->user_id ?? null)
            : ($assignment->employee_id ?? $assignment->user_id ?? null);

        abort_unless((int) $ownerId === (int) Auth::id(), 403);

        $request->validate([
            'user_id'               => ['required', 'in:' . Auth::id()],
            'division'              => ['required', 'string', 'max:100'],
            'submitted_at'          => ['nullable', 'string'],

            'tasks.0.judul'         => ['required', 'string', 'max:255'],
            'tasks.0.pic'           => ['required', 'string', 'max:255'],
            'tasks.0.schedule_date' => ['required', 'date'],
            'tasks.0.start_time'    => ['nullable', 'date_format:H:i'],
            'tasks.0.end_time'      => ['nullable', 'date_format:H:i'],
            'tasks.0.status'        => ['required', Rule::in(JobdeskTask::STATUSES)],

            'tasks.0.progress'      => ['nullable', 'integer', 'between:0,100'],
            'tasks.0.result'        => ['nullable', 'string'],
            'tasks.0.shortcoming'   => ['nullable', 'string'],
            'tasks.0.detail'        => ['nullable', 'string'],

            'tasks.0.lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'tasks.0.lng'           => ['nullable', 'numeric', 'between:-180,180'],
            'tasks.0.address'       => ['nullable', 'string', 'max:255'],

            'tasks.0.photos_b64'    => ['required', 'array', 'min:1'],
            'tasks.0.photos_b64.*'  => ['required', 'string'],
        ], [
            'tasks.0.photos_b64.required' => 'Harap ambil minimal satu foto dari kamera.',
            'tasks.0.photos_b64.min'      => 'Harap ambil minimal satu foto dari kamera.',
        ]);

        $t0 = $request->input('tasks.0');
        $incomingDivision = $request->input('division') ?? (Auth::user()->division ?? 'Umum');

        $status = strtolower((string) ($t0['status'] ?? 'pending'));

        $mappedProgress = match ($status) {
            'pending'      => 0,
            'in_progress'  => 50,
            'verification' => 80,
            'rework'       => 50,
            'delayed'      => 50,
            'cancelled'    => 50,
            'done'         => 100,
            default        => 50,
        };

        $incomingProgress = isset($t0['progress']) ? (int) $t0['progress'] : null;
        $progress = ($incomingProgress !== null)
            ? max(0, min(100, $incomingProgress))
            : $mappedProgress;

        $nowWib = now('Asia/Jakarta');

        // submitted_at (UTC) kalau kolom ada
        $submittedAtUtc = null;
        if (Schema::hasColumn('jobdesks', 'submitted_at')) {
            $raw = $request->input('submitted_at');
            try {
                $submittedAtUtc = $raw ? Carbon::parse($raw)->setTimezone('UTC') : $nowWib->copy()->setTimezone('UTC');
            } catch (\Throwable $e) {
                $submittedAtUtc = $nowWib->copy()->setTimezone('UTC');
            }
        }

        DB::beginTransaction();
        try {
            // ✅ upsert jobdesk by assignment_id (biar 1 assignment = 1 jobdesk header)
            $jobdesk = Jobdesk::firstOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'user_id'       => Auth::id(),
                ],
                [
                    'division'     => $incomingDivision,
                    'divisi'       => $incomingDivision,
                    'tanggal'      => now(),
                    'submitted_at' => $submittedAtUtc,
                ]
            );

            if (Schema::hasColumn('jobdesks', 'submitted_at')) {
                $jobdesk->submitted_at = $submittedAtUtc;
                $jobdesk->save();
            }

            // ✅ upsert task utama (judul = assignment title / judul laporan)
            $task = JobdeskTask::updateOrCreate(
                [
                    'jobdesk_id'  => $jobdesk->id,
                    'judul'       => $t0['judul'],
                    'is_template' => false,
                ],
                [
                    'pic'           => $t0['pic'],
                    'schedule_date' => $t0['schedule_date'],
                    'start_time'    => $t0['start_time'] ?? null,
                    'end_time'      => $t0['end_time']   ?? null,

                    'status'        => $status,
                    'progress'      => $progress,

                    'result'        => $t0['result']      ?? null,
                    'shortcoming'   => $t0['shortcoming'] ?? null,
                    'detail'        => $t0['detail']      ?? null,

                    'lat'           => $t0['lat']         ?? null,
                    'lng'           => $t0['lng']         ?? null,
                    'address'       => $t0['address']     ?? null,

                    'submitted_at'  => $nowWib->copy()->setTimezone('UTC'),
                ]
            );

            // simpan foto
            foreach ((array) ($t0['photos_b64'] ?? []) as $dataUrl) {
                $storedPath = $this->storeDataUrlImage($dataUrl);
                if ($storedPath) {
                    $task->photos()->create(['path' => $storedPath]);
                }
            }

            // ✅ UPDATE assignment juga (supaya penugasan page + stats konsisten)
            $assignment->update([
                'status'      => $status,
                'progress'    => $progress,
                'result'      => $t0['result'] ?? null,
                'shortcoming' => $t0['shortcoming'] ?? null,
                'description' => $t0['detail'] ?? $assignment->description,
                // proof_link kalau kamu kirim dari form lain, bisa ditambahkan di request
                // 'proof_link'  => $request->input('proof_link'),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['general' => 'Terjadi kesalahan saat menyimpan data.'])
                ->withInput();
        }

        return back()->with('success', 'Laporan tugas tersimpan & Assignment ikut tersinkron!');
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
        if ($bin === false) return null;

        $dir  = 'jobdesk/' . now()->format('Y/m/d');
        $name = uniqid('jb_', true) . '.' . $ext;
        $path = $dir . '/' . $name;

        Storage::disk('public')->put($path, $bin);

        return $path;
    }
}
