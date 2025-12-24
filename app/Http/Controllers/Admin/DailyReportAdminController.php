<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\JobdeskTask;
use Carbon\Carbon;

class DailyReportAdminController extends Controller
{
    public function index(Request $request)
    {
        // --- TANGGAL DIPILIH ---
        $date = $request->query('date');
        try {
            $picked = $date ? Carbon::parse($date) : Carbon::now('Asia/Jakarta');
        } catch (\Throwable $e) {
            $picked = Carbon::now('Asia/Jakarta');
        }
        $picked->timezone('Asia/Jakarta');
        $pickedYmd = $picked->format('Y-m-d');

        // --- USER DIPILIH ---
        $users = User::orderBy('name')->get(['id','name','division']);
        $selectedId = $request->query('user_id') ?? ($users->first()->id ?? null);

        // --- AMBIL TASKS HARI INI DARI USER DIPILIH ---
        $tasks = JobdeskTask::with([
                'photos:id,task_id,path',
                'jobdesk.user:id,name,division',
                'jobdesk:id,user_id,division',
            ])
            ->whereDate('schedule_date', $pickedYmd)
            ->when($selectedId, fn($q) => $q->whereHas('jobdesk', fn($qq) => $qq->where('user_id', $selectedId)))
            ->orderBy('start_time')
            ->get();

        // --- RINGKASAN ---
        $summary = [
            'total_tasks'  => $tasks->count(),
            'avg_progress' => round((float)($tasks->avg('progress') ?? 0), 1),
            'done'         => $tasks->where('status', 'done')->count(),
            'delayed'      => $tasks->where('status', 'delayed')->count(),
            'rework'       => $tasks->where('status', 'rework')->count(),
            'verification' => $tasks->where('status', 'verification')->count(),
        ];

        // --- RENDER VIEW ---
        return view('admin.dailyReportAdmin.dailyreportAdmin', [
            'tasks'      => $tasks,
            'summary'    => $summary,
            'users'      => $users,
            'selectedId' => (int)$selectedId,
            'pickedYmd'  => $pickedYmd,
        ]);
    }
}
