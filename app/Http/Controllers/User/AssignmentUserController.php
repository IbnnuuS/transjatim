<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

use App\Models\Assignment;
use App\Models\User;
use App\Models\Jobdesk;
use App\Models\JobdeskTask;
use App\Models\JobdeskTaskPhoto;

class AssignmentUserController extends Controller
{
    // GET /penugasan-user
    public function index(Request $request)
    {
        $user = Auth::user();

        $assignments = Assignment::query()
            ->with('employee:id,name')
            ->where('employee_id', $user->id)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function($q) use($request) {
                $q->where(function($qq) use($request){
                    $qq->where('title','like','%'.$request->q.'%')
                       ->orWhere('description','like','%'.$request->q.'%');
                });
            })
            ->when($request->filled('from'), fn($q) => $q->whereDate('deadline','>=',$request->date('from')))
            ->when($request->filled('to'), fn($q) => $q->whereDate('deadline','<=',$request->date('to')))
            ->orderBy('deadline')
            ->paginate(10)
            ->withQueryString();

        // Fleksibel cari blade di beberapa path umum
        $views = [
            'user.penugasanUser',                   // resources/views/user/penugasanUser.blade.php
            'user.penugasanUser.penugasanUser',     // resources/views/user/penugasanUser/penugasanUser.blade.php
            'user.penugasanuser',                   // resources/views/user/penugasanuser.blade.php
            'penugasanUser',                        // resources/views/penugasanUser.blade.php
        ];

        return view()->first($views, compact('assignments'));
    }

    // GET /penugasan-user/{assignment}/kerjakan
    public function work(Assignment $assignment)
    {
        $user = Auth::user();
        abort_unless((int)$assignment->employee_id === (int)$user->id, 403, 'Penugasan bukan milik Anda');

        $users = User::select('id','name')->where('id', $user->id)->get();

        $views = [
            'user.penugasanUser.kerjakan',          // resources/views/user/penugasanUser/kerjakan.blade.php
            'user.penugasanuser.kerjakan',          // resources/views/user/penugasanuser/kerjakan.blade.php
            'user.kerjakan',                        // resources/views/user/kerjakan.blade.php
        ];

        return view()->first($views, [
            'assignment' => $assignment,
            'users'      => $users,
        ]);
    }

    // POST /penugasan-user/{assignment}/kerjakan
    public function storeWork(Request $request, Assignment $assignment)
    {
        $user = Auth::user();
        abort_unless((int)$assignment->employee_id === (int)$user->id, 403, 'Penugasan bukan milik Anda');

        // Validasi form (1 task)
        $validated = $request->validate([
            'user_id'                 => ['required', Rule::in([$user->id])],
            'division'                => ['required','string','max:100'],
            'submitted_at'            => ['nullable','string','max:25'], // "YYYY-mm-dd HH:ii:ss" WIB
            'tasks'                   => ['required','array','size:1'],
            'tasks.0.judul'           => ['required','string','max:255'],
            'tasks.0.pic'             => ['required','string','max:255'],
            'tasks.0.schedule_date'   => ['required','date'],
            'tasks.0.start_time'      => ['nullable','date_format:H:i'],
            'tasks.0.end_time'        => ['nullable','date_format:H:i'],
            'tasks.0.status'          => ['required', Rule::in(['done','in_progress','pending'])],
            // 'tasks.0.progress'        => ['required','integer','min:1','max:100'], // Removed manual input
            'tasks.0.result'          => ['nullable','string'],
            'tasks.0.shortcoming'     => ['nullable','string'],
            'tasks.0.detail'          => ['nullable','string'],
            'tasks.0.photos_b64'      => ['required','array','min:1'],
            'tasks.0.photos_b64.*'    => ['required','string'],
        ],[
            'tasks.0.photos_b64.required' => 'Harap ambil minimal 1 foto bukti',
            'tasks.0.photos_b64.min'      => 'Harap ambil minimal 1 foto bukti',
        ]);

        // 1) Simpan Jobdesk (TANPA kolom "tasks" & "note")
        $jobdesk = new Jobdesk();
        $jobdesk->user_id      = $user->id;
        $jobdesk->divisi       = $validated['division'];
        $jobdesk->submitted_at = $validated['submitted_at'] ?: now('Asia/Jakarta')->format('Y-m-d H:i:s');
        $jobdesk->save();

        // 2) Simpan Task detail ke jobdesk_tasks
        $t = $validated['tasks'][0];
        $task = new JobdeskTask();
        $task->jobdesk_id    = $jobdesk->id;
        $task->judul         = $t['judul'];
        $task->pic           = $t['pic'];
        $task->schedule_date = $t['schedule_date'];
        $task->start_time    = $t['start_time'] ?? null;
        $task->end_time      = $t['end_time'] ?? null;
        $task->status        = $t['status'];
        // Auto-calculate progress
        $task->progress = match($t['status']) {
            'done' => 100,
            'in_progress' => 50,
            default => 0
        };
        $task->result        = $t['result'] ?? null;
        $task->shortcoming   = $t['shortcoming'] ?? null;
        $task->detail        = $t['detail'] ?? null;
        $task->save();

        // 3) Simpan Foto base64 ke storage + jobdesk_task_photos
        $today   = now('Asia/Jakarta');
        $baseDir = 'public/jobdesk/'.$today->format('Y/m/d');

        foreach ($t['photos_b64'] as $idx => $dataUrl) {
            if (preg_match('#^data:image/(\w+);base64,#i', $dataUrl, $m)) {
                $ext  = strtolower($m[1]);
                $ext  = in_array($ext, ['jpg','jpeg','png','webp']) ? $ext : 'jpg';
                $data = base64_decode(substr($dataUrl, strpos($dataUrl, ',')+1));
                $filename = $task->id.'_'.$idx.'_'.uniqid().'.'.$ext;
                $path = $baseDir.'/'.$filename;
                Storage::put($path, $data);

                $photo = new JobdeskTaskPhoto();
                $photo->task_id = $task->id;
                $photo->path    = 'jobdesk/'.$today->format('Y/m/d').'/'.$filename; // tanpa "public/"
                $photo->save();
            }
        }

        // 4) Update Assignment progress/status
        $newStatus = $t['status'];
        
        // Auto-calculate progress for assignment as well
        $newProgress = match($newStatus) {
            'done' => 100,
            'in_progress' => 50,
            default => 0
        };

        if ($newStatus === 'done' && $newProgress === 100) {
            $assignment->status   = 'done';
            $assignment->progress = 100;
        } else {
            $assignment->status   = 'in_progress'; // Keep assignment in progress if not done? Or follow status?
            // If user marks as pending, assignment might stay assigned/pending.
            // If user marks as In Progress, assignment becomes In Progress.
            // However, logic says if jobdesk task is pending, maybe assignment is still pending?
            // Current logic forces 'in_progress' unless done. I will keep it simple and follow task status mostly, 
            // but the original logic was: "If not done, force in_progress and max progress".
            // Since we simplified, let's just sync status and progress.
            $assignment->status   = $newStatus;
            $assignment->progress = $newProgress;
        }
        $assignment->save();

        return redirect()
            ->route('penugasan.user')
            ->with('success', 'Laporan pekerjaan untuk penugasan berhasil disimpan.');
    }
}
