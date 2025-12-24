<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\JobdeskTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class UserProjectController extends Controller
{
    /**
     * Display merged history of:
     * 1. Jobdesk tasks (User created)
     * 2. Assignments (Admin assigned, completed)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = (int) $request->input('per_page', 10);
        $search = $request->input('search');

        // 1. Get Jobdesk Tasks (Original Logic)
        // Filter: user_id provided by Auth
        // Only jobdesks that are NOT from assignment? Or all? User requested "Assignment if done enters history"
        // Let's get ALL JobdeskTasks for this user that are DONE or ANY status if it's manual jobdesk.
        // Actually, the original history view showed Jobdesks.
        // The requirement: "jika sudah kerjakan (status done) itu akan masuk ke riwayat jobdesk"
        // This likely refers to Assignments (Penugasan) which currently sit in a separate menu.

        // Strategy: We will fetch both and merge them into a standard structure for the view.
        // Or simpler: We pass two collections to the view, or the view iterates a merged paginator.
        // Merging paginators is hard. Let's use two queries and merge collections manually or display in tabs/sections.
        // But "Standard" jobdesk history is already handled by JobdeskController@index?
        // Wait, the user said "Riwayat Jobdesk".
        // Let's check JobdeskUser view again. It iterates `$jobdesks`.
        // If we want Assignments to appear there, we should inject them into that view?
        // OR creates a new page. The user assigned `UserProjectController` which implies distinct page.
        // Let's create a NEW view `user.project.history` or similar if needed.

        // However, checking `web.php` might reveal where `UserProjectController` is pointed.
        // Since I can't check web.php easily right now (blind), I'll assume this controller replaces or augments
        // the History view.

        // Query 1: Assignments (Penugasan) that are DONE
        $assignments = Assignment::query()
            ->forUser($user->id)
            ->where('status', 'done')
            ->orderByDesc('updated_at')
            ->get();

        // Query 2: Jobdesks (Manual / Admin Assigned Jobdesks)
        // Usually these are Jobdesk models.
        $jobdesks = \App\Models\Jobdesk::with(['tasks', 'user'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        // Merge them?
        // The current JobdeskUser view expects `Jobdesk` models.
        // Transforming Assignments into fake Jobdesk objects might be tricky but cleanest for UI.

        $merged = collect();

        foreach ($jobdesks as $j) {
            $merged->push([
                'type' => 'jobdesk',
                'date' => $j->created_at,
                'data' => $j
            ]);
        }

        foreach ($assignments as $a) {
            // Check if this assignment already has a jobdesk linked (to avoid double counting)?
            // The relationship is Jobdesk belongsTo Assignment.
            // If an assignment has a jobdesk, it's already in $jobdesks list IF the jobdesk was created by user.
            // But Assignment-Jobdesk flow often creates a Jobdesk entry.
            // If so, we don't need to double show.
            // Let's check if assignment has jobdesks.
            if ($a->jobdesks()->count() == 0) {
                // Only add if NO jobdesk exists (standalone assignment completion?)
                // or if we just want to show the Assignment entity itself.
                $merged->push([
                    'type' => 'assignment',
                    'date' => $a->updated_at,
                    'data' => $a
                ]);
            }
        }

        // Sort desc
        $merged = $merged->sortByDesc('date');

        // Manual Pagination
        $page = $request->input('page', 1);
        $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // We probably need to render a view that supports this mixed content.
        // Or reuse user.jobdeskUser.jobdeskUser if we pass distinct collections.
        // For now, let's assume we use the existing view but mapped.

        // MAPPING to existing View Structure?
        // The view expects `$jobdesks` which is a Paginator of Jobdesk models.
        // It iterates $jobdesk->tasks.
        // We can wrap Assignment into a pseudo-Jobdesk structure.

        $mappedItems = $slice->map(function ($item) {
            if ($item['type'] === 'jobdesk') {
                return $item['data'];
            } else {
                // Wrap Assignment as Jobdesk
                $a = $item['data'];
                $j = new \App\Models\Jobdesk();
                $j->id = 999999 . $a->id; // Fake ID
                $j->user_id = $a->getOwnerUserIdAttribute();
                $j->division = 'Penugasan';
                $j->created_at = $a->created_at;
                $j->submitted_at = $a->updated_at; // Use update time as submission
                // Fake Relation: user
                $j->setRelation('user', User::find($j->user_id) ?? Auth::user());

                // Fake Relation: tasks
                $t = new JobdeskTask();
                $t->id = 888888 . $a->id;
                $t->judul = $a->title;
                $t->status = 'done';
                $t->progress = 100;
                $t->proof_link = $a->proof_link;
                $t->result = $a->result;
                $t->shortcoming = $a->shortcoming;
                $t->detail = $a->detail;
                $t->schedule_date = $a->deadline;
                // Fake Photos?
                $t->setRelation('photos', collect([])); // No photos for assignment usually?

                $j->setRelation('tasks', collect([$t]));

                return $j;
            }
        });

        // Re-create paginator with mapped items
        $finalPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $mappedItems,
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('user.jobdeskUser.jobdeskUser', [
            'jobdesks' => $finalPaginator,
            'users' => User::orderBy('name')->get(['id', 'name', 'division'])
        ]);
    }
}
