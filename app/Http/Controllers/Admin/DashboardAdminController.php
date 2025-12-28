<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Jobdesk;
use App\Models\JobdeskTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardAdminController extends Controller
{
    public function index(Request $req)
    {
        $appTz      = config('app.timezone', 'Asia/Jakarta') ?: 'Asia/Jakarta';
        $now        = Carbon::now($appTz);
        $weekStart  = $now->copy()->startOfWeek();
        $startMonth = $now->copy()->startOfMonth()->startOfDay();
        $endMonth   = $now->copy()->endOfMonth()->endOfDay();

        $fQ      = trim((string) $req->get('q', ''));
        $fDiv    = $req->get('divisi');
        $fStatus = $req->get('status');

        $applyDivision = function ($q) use ($fDiv) {
            if (!$fDiv) return;

            $q->where(function ($qDiv) use ($fDiv) {
                $qDiv->whereHas('jobdesk', function ($j) use ($fDiv) {
                    $jobdesksTable = (new \App\Models\Jobdesk())->getTable();
                    $hasDivisionJD = Schema::hasColumn($jobdesksTable, 'division');
                    $hasDivisiJD   = Schema::hasColumn($jobdesksTable, 'divisi');

                    $j->where(function ($wj) use ($fDiv, $jobdesksTable, $hasDivisionJD, $hasDivisiJD) {
                        if ($hasDivisionJD && $hasDivisiJD) {
                            $wj->where($jobdesksTable . '.division', $fDiv)
                                ->orWhere($jobdesksTable . '.divisi', $fDiv);
                        } elseif ($hasDivisionJD) {
                            $wj->where($jobdesksTable . '.division', $fDiv);
                        } elseif ($hasDivisiJD) {
                            $wj->where($jobdesksTable . '.divisi', $fDiv);
                        } else {
                            $wj->whereRaw('1=0');
                        }
                    });
                })
                    ->orWhereHas('jobdesk.user', function ($u) use ($fDiv) {
                        $usersTable  = (new \App\Models\User())->getTable();
                        $hasDivision = Schema::hasColumn($usersTable, 'division');
                        $hasDivisi   = Schema::hasColumn($usersTable, 'divisi');

                        if ($hasDivision && $hasDivisi) {
                            $u->where(function ($wu) use ($usersTable, $fDiv) {
                                $wu->where($usersTable . '.division', $fDiv)
                                    ->orWhere($usersTable . '.divisi', $fDiv);
                            });
                        } elseif ($hasDivision) {
                            $u->where($usersTable . '.division', $fDiv);
                        } elseif ($hasDivisi) {
                            $u->where($usersTable . '.divisi', $fDiv);
                        } else {
                            $u->whereRaw('1=0');
                        }
                    });
            });
        };

        $applyStatus = function ($q) use ($fStatus) {
            if (!$fStatus) return;
            $q->where('status', $fStatus);
        };

        $applySearch = function ($q) use ($fQ) {
            if ($fQ === '') return;
            $like = '%' . $fQ . '%';
            $q->where(function ($w) use ($like) {
                $w->where('judul', 'like', $like)
                    ->orWhere('result', 'like', $like)
                    ->orWhere('detail', 'like', $like)
                    ->orWhere('shortcoming', 'like', $like)
                    ->orWhereHas('jobdesk.user', function ($u) use ($like) {
                        $u->where('name', 'like', $like);
                    });
            });
        };

        $totalUsers     = User::count();
        $activeProjects = Jobdesk::count();

        // ===== Weekly Done =====
        $tasksDoneWeekQuery = JobdeskTask::query()
            ->where(function ($q) use ($weekStart) {
                $q->whereDate('schedule_date', '>=', $weekStart->toDateString())
                    ->orWhere(function ($qq) use ($weekStart) {
                        $qq->whereNull('schedule_date')
                            ->where('created_at', '>=', $weekStart->copy()->startOfDay());
                    });
            })
            ->where(function ($q) {
                $q->where('is_template', 0)->orWhereNull('is_template');
            });

        $applyDivision($tasksDoneWeekQuery);
        $applySearch($tasksDoneWeekQuery);
        $applyStatus($tasksDoneWeekQuery);

        $tasksDoneWeek = ($fStatus && $fStatus !== 'done')
            ? 0
            : (clone $tasksDoneWeekQuery)->where('status', 'done')->count();

        $totalWeekTasksQuery = JobdeskTask::query()
            ->where(function ($q) use ($weekStart) {
                $q->whereDate('schedule_date', '>=', $weekStart->toDateString())
                    ->orWhere(function ($qq) use ($weekStart) {
                        $qq->whereNull('schedule_date')
                            ->where('created_at', '>=', $weekStart->copy()->startOfDay());
                    });
            })
            ->where(function ($q) {
                $q->where('is_template', 0)->orWhereNull('is_template');
            });

        $applyDivision($totalWeekTasksQuery);
        $applySearch($totalWeekTasksQuery);
        $applyStatus($totalWeekTasksQuery);

        $totalWeekTasks = $totalWeekTasksQuery->count();
        $tasksDoneWeekPct = $totalWeekTasks > 0 ? (int) round(($tasksDoneWeek / $totalWeekTasks) * 100) : 0;

        // ===== Daily Today =====
        $startDayWib = $now->copy()->startOfDay();
        $endDayWib   = $now->copy()->endOfDay();
        $startDayUtc = $startDayWib->copy()->timezone('UTC');
        $endDayUtc   = $endDayWib->copy()->timezone('UTC');

        $dailyTodayBase = JobdeskTask::query()
            ->whereBetween('created_at', [$startDayUtc, $endDayUtc])
            ->where(function ($q) {
                $q->where('is_template', 0)->orWhereNull('is_template');
            });
        $applyDivision($dailyTodayBase);
        $applySearch($dailyTodayBase);
        $applyStatus($dailyTodayBase);

        $dailyToday = (clone $dailyTodayBase)->count();
        $latestTodayRow = (clone $dailyTodayBase)->select(['created_at', 'updated_at'])
            ->orderByRaw('COALESCE(updated_at, created_at) DESC')->first();

        $dailyLatestTs = $latestTodayRow ? ($latestTodayRow->updated_at ?? $latestTodayRow->created_at) : null;

        $metrics = [
            'total_users'         => $totalUsers,
            'active_projects'     => $activeProjects,
            'tasks_done_week'     => $tasksDoneWeek,
            'tasks_done_week_pct' => $tasksDoneWeekPct,
            'daily_today'         => $dailyToday,
            'daily_latest_ts'     => $dailyLatestTs,
        ];

        // ✅ Donut GLOBAL all-time (DONE: hapus blocked & to_do)
        $allStatusKeys = ['done', 'in_progress', 'pending', 'verification', 'rework', 'delayed', 'cancelled'];
        $statusCounts = [];
        foreach ($allStatusKeys as $sk) {
            $q = JobdeskTask::query()->where('status', $sk)
                ->where(function ($q) {
                    $q->where('is_template', 0)->orWhereNull('is_template');
                });
            $applyDivision($q);
            $applySearch($q);
            $statusCounts[$sk] = $q->count();
        }

        // ===== Bulanan (global) =====
        $userCols = ['id', 'name'];
        if (Schema::hasColumn('users', 'division')) $userCols[] = 'division';
        if (Schema::hasColumn('users', 'divisi'))   $userCols[] = 'divisi';

        $tasksMonth = JobdeskTask::query()
            ->with([
                'photos',
                'jobdesk:id,user_id,divisi,division',
                'jobdesk.user' => function ($q) use ($userCols) {
                    $q->select($userCols);
                }
            ])
            ->where(function ($q) use ($startMonth, $endMonth) {
                $q->whereBetween(DB::raw('DATE(schedule_date)'), [
                    $startMonth->toDateString(),
                    $endMonth->toDateString(),
                ])
                    ->orWhere(function ($qq) use ($startMonth, $endMonth) {
                        $qq->whereNull('schedule_date')
                            ->whereBetween('created_at', [$startMonth, $endMonth]);
                    });
            })
            ->where(function ($q) {
                $q->where('is_template', 0)->orWhereNull('is_template');
            });

        $applyDivision($tasksMonth);
        $applySearch($tasksMonth);
        $applyStatus($tasksMonth);

        $tasksMonth = $tasksMonth->get();

        $avgProgressMonthAll = $tasksMonth->count()
            ? (int) round($tasksMonth->avg(fn($x) => (int)($x->progress ?? 0)))
            : 0;

        $doneCountMonthAll = $tasksMonth->where('status', 'done')->count();

        // Chart monthly
        $labels = [];
        $seriesAvg = [];
        $seriesCount = [];

        $grouped = $tasksMonth->groupBy(function ($t) use ($appTz) {
            if (!empty($t->schedule_date)) {
                return ($t->schedule_date instanceof Carbon)
                    ? $t->schedule_date->format('Y-m-d')
                    : Carbon::parse((string)$t->schedule_date, $appTz)->format('Y-m-d');
            }
            return $t->created_at
                ? $t->created_at->copy()->timezone($appTz)->format('Y-m-d')
                : Carbon::now($appTz)->format('Y-m-d');
        });

        $cursor = $startMonth->copy()->startOfDay();
        while ($cursor <= $endMonth) {
            $ymd = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d/m');

            $dayTasks = $grouped->get($ymd, collect());
            $cnt = $dayTasks->count();
            $avg = $cnt ? round($dayTasks->avg(fn($x) => (int)($x->progress ?? 0))) : 0;

            $seriesCount[] = (int)$cnt;
            $seriesAvg[]   = (int)$avg;

            $cursor->addDay();
        }

        $chartMonthly = [
            'labels'      => $labels,
            'seriesAvg'   => $seriesAvg,
            'seriesCount' => $seriesCount,
        ];

        // ✅ Donut monthly 1 bulan (DONE: hapus blocked & to_do)
        $statusOrder  = ['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'];
        $donutMonthly = array_fill_keys($statusOrder, 0);
        foreach ($tasksMonth as $t) {
            $s = strtolower((string)($t->status ?? ''));
            if (isset($donutMonthly[$s])) $donutMonthly[$s]++;
        }

        // Activity feed
        $latestForFeed = JobdeskTask::query()
            ->with(['jobdesk.user' => function ($q) use ($userCols) {
                $q->select($userCols);
            }])
            ->where(function ($q) use ($startMonth, $endMonth) {
                $q->whereBetween(DB::raw('DATE(schedule_date)'), [
                    $startMonth->toDateString(),
                    $endMonth->toDateString(),
                ])
                    ->orWhere(function ($qq) use ($startMonth, $endMonth) {
                        $qq->whereNull('schedule_date')
                            ->whereBetween('created_at', [$startMonth, $endMonth]);
                    });
            })
            ->where(function ($q) {
                $q->where('is_template', 0)->orWhereNull('is_template');
            });

        $applyDivision($latestForFeed);
        $applySearch($latestForFeed);
        $applyStatus($latestForFeed);

        $latestForFeed = $latestForFeed->orderByDesc('created_at')->limit(10)->get();

        $activityFeed = [];
        foreach ($latestForFeed as $t) {
            $title = (string)($t->judul ?? 'Aktivitas');
            $owner = optional(optional($t->jobdesk)->user)->name;
            if ($owner) $title = $owner . ' — ' . $title;

            $when = $t->created_at ? $t->created_at->copy()->timezone($appTz) : null;
            $timeText = $when ? $when->format('d/m/Y H:i') . ' WIB • ' . $when->copy()->locale(app()->getLocale())->diffForHumans() : '—';

            $activityFeed[] = [
                'icon'      => 'bi bi-check2-square',
                'title'     => $title,
                'desc'      => 'Status: ' . ucwords(str_replace('_', ' ', (string)$t->status)) . ' • ' . (int)($t->progress ?? 0) . '%',
                'time_text' => $timeText,
            ];
        }

        // Latest tasks global
        $latestTasksGlobal = JobdeskTask::query()
            ->with(['photos', 'jobdesk.user' => function ($q) use ($userCols) {
                $q->select($userCols);
            }])
            ->where(function ($q) {
                $q->where('is_template', 0)->orWhereNull('is_template');
            });

        $applyDivision($latestTasksGlobal);
        $applySearch($latestTasksGlobal);
        $applyStatus($latestTasksGlobal);

        $latestTasksGlobal = $latestTasksGlobal->orderByDesc('created_at')->limit(10)->get();

        $adminMonthly = [
            'avg_progress' => $avgProgressMonthAll,
            'done_count'   => $doneCountMonthAll,
            'today_count'  => $metrics['daily_today'],
            'period'       => $startMonth->format('d/m/Y') . ' - ' . $endMonth->format('d/m/Y'),
        ];

        return view('admin.dashboard.dashboardAdmin', compact(
            'metrics',
            'statusCounts',
            'adminMonthly',
            'chartMonthly',
            'donutMonthly',
            'activityFeed',
            'latestTasksGlobal'
        ));
    }
}
