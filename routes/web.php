<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// =======================
// Controller Imports
// =======================
use App\Http\Controllers\JobdeskController;

// ==== USER Controllers ====
use App\Http\Controllers\User\ProfileController as UserProfileController;
use App\Http\Controllers\User\ScheduleController;
use App\Http\Controllers\User\DailyReportUserController as UserDR;
use App\Http\Controllers\User\UserProjectController;
use App\Http\Controllers\User\DailySummaryFromJobdeskController;
use App\Http\Controllers\User\MonthlyRecapController;
use App\Http\Controllers\User\DashboardUserController;
use App\Http\Controllers\User\PenugasanController;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;

// ==== ADMIN Controllers ====
use App\Http\Controllers\Admin\ScheduleAdminController;
use App\Http\Controllers\Admin\DailyReportAdminController;
use App\Http\Controllers\Admin\RoleAccessController;
use App\Http\Controllers\Admin\JobdeskAdminController;
use App\Http\Controllers\Admin\LaporanHarianAdminController;
use App\Http\Controllers\Admin\LaporanBulananAdminController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\TambahJobdeskController;

// ===== Fallback file server (BARU) =====
use App\Http\Controllers\PublicFileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root -> /login
Route::get('/', fn() => redirect('/login'));

// Smart router: /dashboard -> admin / user
Route::get('/dashboard', function () {
    $user = Auth::user();
    if (!$user) return redirect('/login');

    if (method_exists($user, 'hasRole')) {
        return $user->hasRole('admin')
            ? redirect()->route('dashboard.admin')
            : redirect()->route('dashboard.user');
    }

    // Fallback jika tanpa Spatie Role
    return (strtolower($user->role ?? '') === 'admin')
        ? redirect()->route('dashboard.admin')
        : redirect()->route('dashboard.user');
})->middleware(['auth', 'verified'])->name('dashboard');


// =====================================================================
// Semua route butuh login
// =====================================================================
Route::middleware('auth')->group(function () {

    // -------------------------
    // PROFIL (SEMUA USER)
    // -------------------------
    Route::get('/profile',  [UserProfileController::class, 'index'])->name('profile.user');
    Route::post('/profile', [UserProfileController::class, 'update'])->name('profile.update.user');

    // Alias Breeze/Jetstream
    Route::get('/profile/edit', [UserProfileController::class, 'index'])->name('profile.edit');
    Route::patch('/profile',    [UserProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',   [UserProfileController::class, 'destroy'])->name('profile.destroy');

    // -------------------------
    // ROUTE BERSAMA (ADMIN/USER)
    // -------------------------
    Route::patch('/jobdesks/{jobdesk}/status', [JobdeskController::class, 'updateStatus'])
        ->name('jobdesks.update-status');

    // =================================================================
    // DASHBOARD USER
    // =================================================================
    Route::middleware(['verified', 'role:user'])->group(function () {

        Route::get('/dashboard-user', [DashboardUserController::class, 'index'])
            ->name('dashboard.user');

        Route::get('/jadwal-user',  [ScheduleController::class, 'index'])->name('jadwal.user');
        Route::post('/jadwal-user', [ScheduleController::class, 'store'])->name('jadwal.store');

        // ===== Kehadiran User =====
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::post('/punch-in',  [UserAttendanceController::class, 'punchIn'])->name('punch_in');
            Route::post('/punch-out', [UserAttendanceController::class, 'punchOut'])->name('punch_out');
            Route::get('/today',      [UserAttendanceController::class, 'today'])->name('today');
        });

        Route::get('/jobdesk-user',  [JobdeskController::class, 'index'])->name('jobdesk.user');
        Route::post('/jobdesk-user', [JobdeskController::class, 'store'])->name('jobdesk.store');

        Route::post('/jobdesk-user/{id}/accept', [JobdeskController::class, 'accept'])->name('jobdesk.accept');
        Route::post('/jobdesk-user/{id}/submit', [JobdeskController::class, 'submit'])->name('jobdesk.submit');

        Route::get('/my/projects',      [UserProjectController::class, 'index'])->name('user.projects');
        Route::get('/jobdesks/history', [UserProjectController::class, 'index'])->name('jobdesks.history');

        Route::get('/dailyreport-user',  [UserDR::class, 'index'])->name('dailyreport.user');
        Route::post('/dailyreport-user', [UserDR::class, 'store'])->name('dailyreport.user.store');

        Route::get('/proyek-user', fn() => view('user.proyekUser.proyekUser'))->name('proyek.user');

        Route::get('/rekapBulanan-user', [MonthlyRecapController::class, 'index'])->name('rekapBulanan.user');

        Route::get('/daily-reports',  [UserDR::class, 'index'])->name('user.daily-reports.index');
        Route::post('/daily-reports', [UserDR::class, 'store'])->name('user.daily-reports.store');

        Route::get('/daily', [DailySummaryFromJobdeskController::class, 'index'])
            ->name('user.daily');

        Route::get('/daily/export/excel', function (\Illuminate\Http\Request $r) {
            return back()->with('warning', 'Fitur export Excel belum diimplementasikan.');
        })->name('user.daily.export.excel');

        Route::get('/daily/export/pdf', [DailySummaryFromJobdeskController::class, 'exportPdf'])
            ->name('user.daily.export.pdf');

        Route::get('/rekapBulanan-user/export/pdf', [MonthlyRecapController::class, 'exportPdf'])
            ->name('user.rekapBulanan.export.pdf');

        // ================= Penugasan User =================
        Route::get('/penugasan-user', [PenugasanController::class, 'index'])
            ->name('penugasan.user');

        Route::patch('/penugasan-user/{assignment}/accept', [PenugasanController::class, 'accept'])
            ->name('penugasan.user.accept');

        Route::patch('/penugasan-user/{assignment}/meta', [PenugasanController::class, 'updateMeta'])
            ->name('penugasan.user.updateMeta');

        Route::post('/penugasan-user/{assignment}/submit', [PenugasanController::class, 'submit'])
            ->name('penugasan.user.submit');
    });

    // =================================================================
    // DASHBOARD ADMIN
    // =================================================================
    Route::middleware(['verified', 'role:admin'])->group(function () {

        Route::get('/dashboard-admin', [DashboardAdminController::class, 'index'])
            ->name('dashboard.admin');

        // Jadwal (Admin)
        Route::get('/admin/jadwal', [ScheduleAdminController::class, 'index'])->name('admin.jadwal');
        Route::post('/admin/assignment', [ScheduleAdminController::class, 'storeAssignment'])
            ->name('admin.assignment.store');

        // Penugasan (Admin)
        Route::prefix('admin/penugasan')->name('admin.penugasan.')->group(function () {
            Route::get('/',                [AssignmentController::class, 'index'])->name('index');
            Route::post('/',               [AssignmentController::class, 'store'])->name('store');
            Route::put('/{assignment}',    [AssignmentController::class, 'update'])->name('update');
            Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('destroy');
        });

        // Manajemen Karyawan
        Route::get('/admin/karyawan', [KaryawanController::class, 'index'])->name('admin.karyawan');
        Route::put('/admin/karyawan/{user}', [KaryawanController::class, 'update'])->name('admin.karyawan.update');

        // ===================== Role & Akses (CRUD User) =====================
        Route::get('/admin/roles',  [RoleAccessController::class, 'index'])->name('admin.roles.index');
        Route::post('/admin/users', [RoleAccessController::class, 'store'])->name('admin.users.store');

        // ✅ FIX ROUTE UPDATE ROLE (AMAN, TIDAK BENTROK)
        Route::patch('/admin/users/{user}/update-role', [RoleAccessController::class, 'updateRole'])
            ->name('admin.users.update-role');

        Route::delete('/admin/users/{user}', [RoleAccessController::class, 'destroy'])
            ->name('admin.users.destroy');

        // Tambah Jobdesk (Admin)
        Route::get('/admin/tambahJobdesk', [TambahJobdeskController::class, 'create'])
            ->name('admin.tambahJobdesk.create');

        Route::post('/admin/tambahJobdesk/store', [TambahJobdeskController::class, 'store'])
            ->name('admin.tambahJobdesk.store');

        // Jobdesk Admin
        Route::prefix('admin/jobdesk')->name('admin.jobdesk.')->group(function () {
            Route::post('/bulk-action',        [JobdeskAdminController::class, 'bulk'])->name('bulk');
            Route::get('/',                    [JobdeskAdminController::class, 'index'])->name('index');

            Route::post('/{id}/status',        [JobdeskAdminController::class, 'updateStatus'])
                ->whereNumber('id')->name('updateStatus');

            Route::post('/{id}/submit-like-user', [JobdeskAdminController::class, 'submitLikeUser'])
                ->whereNumber('id')->name('submitLikeUser');

            Route::get('/{id}/edit',           [JobdeskAdminController::class, 'edit'])
                ->whereNumber('id')->name('edit');

            Route::post('/{id}/update-inline', [JobdeskAdminController::class, 'updateInline'])
                ->whereNumber('id')->name('updateInline');

            Route::delete('/{id}',             [JobdeskAdminController::class, 'destroy'])
                ->whereNumber('id')->name('destroy');
        });

        // Daily Report Admin
        Route::get('/admin/daily-report', [DailyReportAdminController::class, 'index'])->name('admin.dailyreport');

        // Laporan Harian Admin
        Route::prefix('admin/laporan-harian')->name('admin.laporan-harian.')->group(function () {
            Route::get('/', [LaporanHarianAdminController::class, 'index'])->name('index');
            Route::get('/export/excel', [LaporanHarianAdminController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export/pdf',   [LaporanHarianAdminController::class, 'exportPdf'])->name('export.pdf');
        });

        Route::get('/admin/laporan/harian', [LaporanHarianAdminController::class, 'index'])
            ->name('admin.laporanharian');

        // Laporan Bulanan Admin
        Route::prefix('admin/laporan-bulanan')->name('admin.laporan-bulanan.')->group(function () {
            Route::get('/', [LaporanBulananAdminController::class, 'index'])->name('index');
            Route::get('/export/excel', [LaporanBulananAdminController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export/pdf',   [LaporanBulananAdminController::class, 'exportPdf'])->name('export.pdf');
        });

        Route::get('/admin/laporan/bulanan', function () {
            return redirect()->route('admin.laporan-bulanan.index');
        })->name('admin.laporanbulanan');

        Route::get('/admin/assignment/{id}/edit', function ($id) {
            return "Halaman edit tugas ID: {$id}";
        })->name('admin.assignment.edit');

        Route::delete('/admin/assignment/{id}', function ($id) {
            return back()->with('success', "Tugas ID {$id} berhasil dihapus.");
        })->name('admin.assignment.destroy');
    });
});

// =====================================================================
// Fallback file server (BARU) — untuk gambar jika /storage tidak terbaca
// =====================================================================
Route::get('/files/{path}', [PublicFileController::class, 'show'])
    ->where('path', '.*')
    ->name('files.show');

// Debug recurring
Route::get('/debug-recurring', function () {
    $userId = Illuminate\Support\Facades\Auth::id();
    if (!$userId) return "Please login first.";

    $today = Illuminate\Support\Carbon::now('Asia/Jakarta')->toDateString();

    echo "<h1>Debug Recurring Task for User ID: $userId</h1>";
    echo "Today: $today<br>";

    $templates = App\Models\JobdeskTask::query()
        ->where('is_template', true)
        ->whereHas('jobdesk', fn($q) => $q->where('user_id', $userId))
        ->get();

    echo "Found " . $templates->count() . " templates.<br><hr>";

    foreach ($templates as $tmpl) {
        echo "Template ID: {$tmpl->id} - Title: {$tmpl->judul}<br>";

        $exists = App\Models\JobdeskTask::where('parent_task_id', $tmpl->id)
            ->whereDate('schedule_date', $today)
            ->first();

        if ($exists) {
            echo "-> Skill exists for today! Task ID: {$exists->id} (Status: {$exists->status})<br>";
            echo "-> Condition: Skipping.<br>";
        } else {
            echo "-> Task DOES NOT exist for today.<br>";
            echo "-> Condition: Will Create.<br>";

            try {
                $newTask = App\Models\JobdeskTask::create([
                    'jobdesk_id'      => $tmpl->jobdesk_id,
                    'parent_task_id'  => $tmpl->id,
                    'judul'           => $tmpl->judul,
                    'pic'             => $tmpl->pic,
                    'schedule_date'   => $today,
                    'start_time'      => $tmpl->start_time,
                    'end_time'        => $tmpl->end_time,
                    'status'          => 'to_do',
                    'progress'        => 0,
                    'detail'          => $tmpl->detail,
                    'is_template'     => false,
                ]);
                echo "-> CREATED SUCCESS. New Task ID: {$newTask->id}<br>";
            } catch (\Exception $e) {
                echo "-> CREATE FAILED: " . $e->getMessage() . "<br>";
            }
        }
        echo "<hr>";
    }
    return "Done.";
});

require __DIR__ . '/auth.php';
