<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DailyReportUserController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Ambil daftar laporan milik user login
        $reports = DailyReport::where('user_id', $user->id)
            ->orderBy('tanggal_laporan', 'desc')
            ->paginate(8)
            ->withQueryString();

        // ⬇️ PENTING: sesuaikan dengan path view yang ADA
        // File kamu: resources/views/user/dailyReportUser/dailyReportUser.blade.php
        return view('user.dailyReportUser.dailyReportUser', [
            'user'    => $user,
            'reports' => $reports,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Validasi input
        $validated = $request->validate([
            'tasks'     => ['required', 'string', 'min:3'], // "Pekerjaan yang dilakukan"
            'note'      => ['nullable', 'string', 'max:1000'],
            'photo'     => ['nullable','file','image','max:4096'], // 4MB
            'lat'       => ['nullable','numeric','between:-90,90'],
            'lng'       => ['nullable','numeric','between:-180,180'],
            'status'    => ['nullable', Rule::in(['pending','in_progress','done','blocked'])],
            'progress'  => ['nullable','integer','min:0','max:100'],
            'title'     => ['nullable','string','max:255'],
        ]);

        // Siapkan judul otomatis dari tasks (ambil fragmen pertama)
        $title = $validated['title']
            ?? Str::limit(trim(explode(',', $validated['tasks'])[0] ?? 'Daily Report'), 80);

        // Simpan foto (opsional) ke storage/app/public/daily_reports
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('daily_reports', 'public');
            // Nanti tampilkan di Blade dengan asset('storage/'.$photoPath)
        }

        // Simpan ke DB
        DailyReport::create([
            'user_id'         => $user->id,
            'nama'            => $user->name ?? 'User',
            'divisi'          => $user->division ?? ($user->divisi ?? 'Umum'), // sesuaikan dg kolom users kamu
            'tanggal_laporan' => now(),
            'pekerjaan'       => $validated['tasks'],
            'title'           => $title,
            'pic'             => $user->name ?? 'User',
            'progress'        => (int)($validated['progress'] ?? 0),
            'latitude'        => $validated['lat'] ?? null,
            'longitude'       => $validated['lng'] ?? null,
            'photo_url'       => $photoPath,        // path relatif di disk 'public'
            'catatan'         => $validated['note'] ?? null, // kolom DB = catatan (bukan note)
            'status'          => $validated['status'] ?? 'in_progress',
        ]);

        return redirect()
            ->route('user.daily-reports.index')
            ->with('success', 'Laporan harian berhasil disimpan.');
    }
}
