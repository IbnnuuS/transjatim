<?php

namespace App\Services;

use App\Models\JobdeskTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyRecurringTaskService
{
    /**
     * Generate daily recurring tasks for a specific user (or all users if needed).
     * This logic mimics the "master" task for the current day.
     * 
     * Logic:
     * 1. Find all active "template" tasks (is_template = 1).
     *    - Optionally filter by user_id if provided.
     * 2. For each template:
     *    - Check if a child task exists for today (parent_task_id = template.id, schedule_date = today).
     *    - If not, replicate the template.
     */
    public function generateForUser($userId)
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Find templates belonging to this user's jobdesks
        // Using whereHas jobdesk to filter by user_id
        $templates = JobdeskTask::query()
            ->where('is_template', true)
            ->whereHas('jobdesk', fn($q) => $q->where('user_id', $userId))
            ->get();

        foreach ($templates as $tmpl) {
            // Check if already generated for today
            $exists = JobdeskTask::where('parent_task_id', $tmpl->id)
                ->whereDate('schedule_date', $today)
                ->exists();

            if ($exists) {
                continue;
            }

            // Create new task for today
            JobdeskTask::create([
                'jobdesk_id'      => $tmpl->jobdesk_id,
                'parent_task_id'  => $tmpl->id,
                'judul'           => $tmpl->judul,
                'pic'             => $tmpl->pic,
                'schedule_date'   => $today,
                'start_time'      => $tmpl->start_time,
                'end_time'        => $tmpl->end_time,
                'status'          => 'pending', // Reset status
                'progress'        => 0,       // Reset progress
                'result'          => null,
                'shortcoming'     => null,
                'detail'          => $tmpl->detail, // Keep detail/instruction
                'lat'             => null,
                'lng'             => null,
                'address'         => null,
                'proof_link'      => null,
                'is_template'     => false,
            ]);
        }
    }
}
