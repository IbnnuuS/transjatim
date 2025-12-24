@extends('user.masterUser')

@section('content')
    @php
        use Carbon\Carbon;

        // ==============================
        // Util kecil
        // ==============================
        if (!function_exists('strsafe')) {
            function strsafe($v, $fallback = '—')
            {
                $v = trim((string) ($v ?? ''));
                return $v === '' ? $fallback : $v;
            }
        }

        // ==============================
        // Peta jadwal per tanggal (untuk kalender)
        // ==============================
        $taskMap = [];
        foreach ($schedules ?? [] as $sch) {
            $tgl = optional($sch->tanggal);
            $key = $tgl ? $tgl->format('Y-m-d') : null;
            if ($key) {
                $taskMap[$key][] = [
                    'date' => $key,
                    'title' => $sch->judul ?? '(Tanpa judul)',
                    'divisi' => $sch->divisi ?? '—',
                    'note' => $sch->catatan ?? '—',
                ];
            }
        }

        // ==============================
        // Peta kehadiran per tanggal (untuk widget, jadwal, kalender)
        // ==============================
        $statusBadgeMap = [
            'present' => 'success',
            'izin' => 'warning text-dark',
            'leave' => 'warning text-dark',
            'absent' => 'danger',
            'late' => 'info',
        ];
        $statusLabelMap = [
            'present' => 'Hadir',
            'izin' => 'Izin',
            'leave' => 'Izin',
            'absent' => 'Tidak Hadir',
            'late' => 'Terlambat',
        ];

        // attendanceThisMonth dikirim dari controller
        $attendanceMap = [];
        if (isset($attendanceThisMonth)) {
            foreach ($attendanceThisMonth as $row) {
                if (!$row->date) {
                    continue;
                }
                $key = Carbon::parse($row->date)->format('Y-m-d');
                $attendanceMap[$key] = $row; // jika dobel per hari, yang terakhir overwrite (biasanya oke)
            }
        }

        // Data untuk JS (kalender) – ringkas per tanggal
        $attendanceJs = [];
        foreach ($attendanceMap as $key => $row) {
            $s = strtolower($row->status ?? '');
            $attendanceJs[$key] = [
                'status' => $s,
                'label' => $statusLabelMap[$s] ?? ucfirst($s ?: 'Belum'),
                'badge' => $statusBadgeMap[$s] ?? 'secondary',
                'in' => $row->in_time ? \Illuminate\Support\Str::of($row->in_time)->substr(0, 5) : null,
                'out' => $row->out_time ? \Illuminate\Support\Str::of($row->out_time)->substr(0, 5) : null,
                'note' => $row->note,
            ];
        }

        // ==============================
        // Data kehadiran hari ini (untuk widget)
        // ==============================
        $att = $todayAttendance ?? null;

        // Fallback jika $todayAttendance belum dikirim
        if (!$att ?? false) {
            try {
                $todayKey = Carbon::now('Asia/Jakarta')->toDateString();
            } catch (\Throwable $e) {
                $todayKey = now()->toDateString();
            }

            if (!empty($attendanceMap[$todayKey])) {
                $att = $attendanceMap[$todayKey];
            }
        }

        $statusToday = strtolower($att->status ?? '');
        $inTime = $att?->in_time;
        $outTime = $att?->out_time;

        // Logic tombol
        $canPunchIn = !$inTime; // belum absen masuk
        $canPunchOut = $inTime && !$outTime; // sudah masuk, belum pulang

        // Saran shift (opsional dari controller), fallback Pagi
        $todaySchedule = $todaySchedule ?? null; // jadwal hari ini dari admin (bisa null)
    @endphp

    <div class="pagetitle">
        <h1>Jadwal Teams</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.user') }}">Home</a></li>
                <li class="breadcrumb-item active">Jadwal</li>
            </ol>
        </nav>
    </div>

    <section class="section schedule">
        <div class="row g-3">

            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="card-body p-0">
                        {{-- Header Section: Tanggal & Jam --}}
                        <div class="p-4 bg-primary text-white d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="fw-bold mb-1 text-white">
                                    <i class="bi bi-calendar-check me-2"></i>Kehadiran & Absensi
                                </h5>
                                <div class="small opacity-75">
                                </div>
                            </div>
                            <div class="text-end">
                                <h2 class="mb-0 fw-bold display-6" id="wibNow">--:--</h2>
                                <div class="small opacity-75">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            {{-- Status Bar (Status Hari Ini) --}}
                            @php
                                // Re-calculate status logic for display (ensure vars exist)
                                // $statusToday, $badge, $label must be derived from $att if not available in scope
                                $curStatus = $statusToday ?? 'absent';
                                $curBadge = $statusBadgeMap[$curStatus] ?? 'secondary';
                                $curLabel = $statusLabelMap[$curStatus] ?? 'Belum Absen';

                                // Override label logic similar to original:
                                if (!$att && !$inTime) {
                                    $curLabel = 'Belum Absen';
                                    $curBadge = 'secondary';
                                } elseif ($inTime && !$outTime) {
                                    $curLabel = 'Hadir (Masuk)';
                                    $curBadge = 'success'; // or primary
                                } elseif ($outTime) {
                                    $curLabel = 'Sudah Pulang';
                                    $curBadge = 'success';
                                }
                            @endphp

                            <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3 bg-light border">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $curBadge }} text-white shadow-sm"
                                    style="width:50px;height:50px;min-width:50px;">
                                    <i class="bi bi-person-badge fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted text-uppercase fw-bold">Status Hari Ini</div>
                                    <div class="fw-bold fs-5 text-{{ $curBadge }}">{{ $curLabel }}</div>
                                </div>
                            </div>

                            <div class="row g-3">
                                {{-- Card Masuk --}}
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm"
                                        style="background: linear-gradient(to right bottom, #f0fdf4, #ffffff); border-left: 5px solid #198754 !important;">
                                        <div class="card-body p-4 text-center">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white mb-3 shadow"
                                                style="width:64px;height:64px;">
                                                <i class="bi bi-box-arrow-in-right fs-2"></i>
                                            </div>
                                            <h5 class="fw-bold text-success mb-1">Absen Masuk</h5>
                                            <div class="text-muted small mb-3">
                                                @if ($inTime)
                                                    <span
                                                        class="fs-4 fw-bold text-dark">{{ \Illuminate\Support\Str::substr($inTime, 0, 5) }}</span>
                                                @else
                                                    <span class="fst-italic">--:--</span>
                                                @endif
                                            </div>

                                            <form method="POST" action="{{ route('attendance.punch_in') }}">
                                                @csrf
                                                <div class="mb-3">
                                                    <input type="text" class="form-control text-center bg-white"
                                                        name="note" placeholder="Catatan Masuk (opsional)"
                                                        {{ $canPunchIn ? '' : 'disabled' }}>
                                                </div>
                                                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm"
                                                    {{ $canPunchIn ? '' : 'disabled' }}>
                                                    <i class="bi bi-check-lg me-1"></i> ABSEN MASUK
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card Pulang --}}
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm"
                                        style="background: linear-gradient(to right bottom, #fff5f5, #ffffff); border-left: 5px solid #dc3545 !important;">
                                        <div class="card-body p-4 text-center">
                                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger text-white mb-3 shadow"
                                                style="width:64px;height:64px;">
                                                <i class="bi bi-box-arrow-right fs-2"></i>
                                            </div>
                                            <h5 class="fw-bold text-danger mb-1">Absen Pulang</h5>
                                            <div class="text-muted small mb-3">
                                                @if ($outTime)
                                                    <span
                                                        class="fs-4 fw-bold text-dark">{{ \Illuminate\Support\Str::substr($outTime, 0, 5) }}</span>
                                                @else
                                                    <span class="fst-italic">--:--</span>
                                                @endif
                                            </div>

                                            <form method="POST" action="{{ route('attendance.punch_out') }}"
                                                id="formPunchOut">
                                                @csrf
                                                <input type="hidden" name="overtime_reason" id="hiddenOvertimeReason">
                                                <div class="mb-3">
                                                    <input type="text" class="form-control text-center bg-white"
                                                        name="note" placeholder="Catatan Pulang (opsional)"
                                                        {{ $canPunchOut ? '' : 'disabled' }}>
                                                </div>
                                                <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm"
                                                    {{ $canPunchOut ? '' : 'disabled' }}>
                                                    <i class="bi bi-door-open me-1"></i> ABSEN PULANG
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Notifikasi --}}
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show mt-4 shadow-sm" role="alert">
                                    <i class="bi bi-check-circle-fill me-2"></i> <strong>Berhasil!</strong>
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger mt-4 shadow-sm">
                                    <h6 class="alert-heading fw-bold"><i
                                            class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi Kesalahan</h6>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            {{-- =========================
         RIWAYAT KEHADIRAN BULAN INI
         ========================= --}}
            @if (isset($attendanceThisMonth))
                @php
                    $sumPresent = $attendanceThisMonth->where('status', 'present')->count();
                    $sumLate = $attendanceThisMonth->where('status', 'late')->count();
                    $sumIzin = $attendanceThisMonth->whereIn('status', ['izin', 'leave'])->count();
                    $sumAbsent = $attendanceThisMonth->whereIn('status', ['absent'])->count();
                @endphp

                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-clock-history me-1"></i>
                                    Riwayat Kehadiran Bulan Ini
                                </h5>
                                <div class="d-flex gap-2 small">
                                    <span class="badge bg-success">Hadir: {{ $sumPresent }}</span>
                                    <span class="badge bg-info text-dark">Terlambat: {{ $sumLate }}</span>
                                    <span class="badge bg-warning text-dark">Izin/Leave: {{ $sumIzin }}</span>
                                    <span class="badge bg-danger">Tidak Hadir: {{ $sumAbsent }}</span>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:110px;">Tanggal</th>

                                            <th style="width:140px;">Status</th>
                                            <th style="width:100px;">Masuk</th>
                                            <th style="width:100px;">Pulang</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($attendanceThisMonth as $row)
                                            @php
                                                $in = $row->in_time
                                                    ? \Illuminate\Support\Str::of($row->in_time)->substr(0, 5)
                                                    : '—';
                                                $out = $row->out_time
                                                    ? \Illuminate\Support\Str::of($row->out_time)->substr(0, 5)
                                                    : '—';
                                                $st = strtolower($row->status ?? '');
                                                $badgeRow = $statusBadgeMap[$st] ?? 'secondary';
                                                $labelRow = $statusLabelMap[$st] ?? '—';

                                                // Overtime Logic
                                                $ovtMins = (int) ($row->overtime_minutes ?? 0);
                                                $isOvertime = $ovtMins > 0;
                                                $ovtDuration = '';
                                                if ($isOvertime) {
                                                    $h = floor($ovtMins / 60);
                                                    $m = $ovtMins % 60;
                                                    $parts = [];
                                                    if ($h > 0) {
                                                        $parts[] = "$h jam";
                                                    }
                                                    if ($m > 0) {
                                                        $parts[] = "$m menit";
                                                    }
                                                    $ovtDuration = implode(' ', $parts);
                                                }
                                            @endphp
                                            <tr>
                                                <td class="small">
                                                    {{ $row->date ? \Carbon\Carbon::parse($row->date)->format('d/m/Y') : '—' }}
                                                </td>

                                                <td><span class="badge bg-{{ $badgeRow }}">{{ $labelRow }}</span>
                                                </td>
                                                <td class="small">{{ $in }}</td>
                                                <td class="small">
                                                    <div>{{ $out }}</div>
                                                    @if ($isOvertime)
                                                        <div class="mt-1">
                                                            <span class="badge bg-purple"
                                                                style="background-color: #6f42c1; color: white; font-size: 0.7em;">Overtime</span>
                                                        </div>
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            {{ $ovtDuration }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="small text-muted">
                                                    {{ $row->note ?? '—' }}
                                                    @if ($isOvertime && !empty($row->overtime_reason) && $row->overtime_reason !== '-')
                                                        <div class="mt-1 text-primary" style="font-size: 0.75rem;">
                                                            <strong>Alasan:</strong> {{ $row->overtime_reason }}
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="bi bi-inbox me-1"></i> Belum ada data kehadiran bulan ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ===== Kalender Bulanan (Server-Side Logic) ===== -->
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calendar3 me-1"></i> Kalender Jadwal
                            </h5>

                            <!-- Navigasi bulan -->
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary"
                                    href="{{ route('jadwal.user', ['m' => $calendarNav['prev']['m'], 'y' => $calendarNav['prev']['y']]) }}"
                                    title="Bulan sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                                <a class="btn btn-outline-secondary"
                                    href="{{ route('jadwal.user', ['m' => $calendarNav['now']['m'], 'y' => $calendarNav['now']['y']]) }}">Hari
                                    ini</a>
                                <a class="btn btn-outline-secondary"
                                    href="{{ route('jadwal.user', ['m' => $calendarNav['next']['m'], 'y' => $calendarNav['next']['y']]) }}"
                                    title="Bulan berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="alert alert-light border text-center py-2 mb-3">
                            <span class="fw-bold fs-5 text-primary">{{ $monthLabel }}</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center align-middle mb-0 calendar-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sen</th>
                                        <th>Sel</th>
                                        <th>Rab</th>
                                        <th>Kam</th>
                                        <th>Jum</th>
                                        <th>Sab</th>
                                        <th>Min</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // $gridStart & $gridEnd passed from Controller
                                        $d = $gridStart->copy();
                                    @endphp
                                    @while ($d->lte($gridEnd))
                                        <tr>
                                            @for ($i = 0; $i < 7; $i++)
                                                @php
                                                    $isOtherMonth = $d->month !== $month;
                                                    $isToday = $d->isSameDay($today);
                                                    $dateKey = $d->toDateString();
                                                    $count = (int) ($scheduleCounts[$dateKey] ?? 0);
                                                    $attStatus = $attendanceByDate[$dateKey]['status'] ?? null;

                                                    // Holiday Check
                                                    $holidayName = $holidays[$dateKey] ?? null;
                                                    $isHoliday = !empty($holidayName);
                                                @endphp
                                                <td class="align-top p-2 cal-cell {{ $isOtherMonth ? 'bg-light text-muted' : '' }} {{ $isToday ? 'table-primary' : '' }} {{ $isHoliday ? 'bg-danger-subtle' : '' }}"
                                                    style="height: 100px; width: 14.28%; cursor: pointer;"
                                                    data-date="{{ $dateKey }}">

                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span
                                                            class="fw-bold {{ $isOtherMonth ? 'opacity-50' : '' }}">{{ $d->day }}</span>
                                                        @if ($isToday)
                                                            <span class="badge bg-primary rounded-pill"
                                                                style="font-size: 0.65rem;">Hari Ini</span>
                                                        @elseif($isHoliday)
                                                            <span class="badge bg-danger rounded-pill"
                                                                style="font-size: 0.65rem;">Libur</span>
                                                        @endif
                                                    </div>

                                                    {{-- Indikator Libur --}}
                                                    @if ($isHoliday)
                                                        <div class="mb-1">
                                                            <small class="text-danger fw-bold d-block lh-1"
                                                                style="font-size: 0.7rem;">
                                                                {{ \Illuminate\Support\Str::limit($holidayName, 20) }}
                                                            </small>
                                                        </div>
                                                    @endif

                                                    {{-- Indikator Jadwal --}}
                                                    @if ($count > 0)
                                                        <div class="mb-1">
                                                            <span class="badge bg-info text-dark d-block text-truncate"
                                                                style="max-width: 100%;">
                                                                {{ $count }} Jadwal
                                                            </span>
                                                        </div>
                                                    @endif

                                                    {{-- Indikator Kehadiran --}}
                                                    @if ($attStatus)
                                                        @php
                                                            $badgeColor = match ($attStatus) {
                                                                'present' => 'success',
                                                                'late' => 'warning text-dark',
                                                                'absent' => 'danger',
                                                                'leave', 'izin' => 'secondary',
                                                                default => 'secondary',
                                                            };
                                                            $statusLabel = match ($attStatus) {
                                                                'present' => 'Hadir',
                                                                'late' => 'Telat',
                                                                'absent' => 'Alpa',
                                                                'leave' => 'Cuti',
                                                                'izin' => 'Izin',
                                                                default => ucfirst($attStatus),
                                                            };
                                                        @endphp
                                                        <div>
                                                            <span
                                                                class="badge bg-{{ $badgeColor }} d-block text-truncate"
                                                                style="max-width: 100%;">
                                                                {{ $statusLabel }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </td>
                                                @php $d->addDay(); @endphp
                                            @endfor
                                        </tr>
                                    @endwhile
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-info-circle me-1"></i> Klik tanggal untuk melihat detail jadwal.
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .calendar-container {
                    text-align: center;
                }

                .calendar-header {
                    font-size: 1.25rem;
                    font-weight: bold;
                    margin-bottom: 10px;
                    color: #0d6efd;
                    letter-spacing: 1px;
                }

                .calendar-table th {
                    background: #f8f9fa;
                    font-weight: 600;
                }

                .calendar-table td {
                    width: 120px;
                    min-width: 110px;
                    height: 100px;
                    vertical-align: top;
                    padding: .5rem;
                    position: relative;
                    cursor: pointer;
                }

                .calendar-table td.empty {
                    background: #fafafa;
                    cursor: default;
                }

                .calendar-table td:hover:not(.empty) {
                    background: #f7fbff;
                }

                .cal-date-num {
                    font-weight: 700;
                }

                .calendar-table .today .cal-date-num {
                    background: #0d6efd;
                    color: #fff;
                    border-radius: 50%;
                    width: 28px;
                    height: 28px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    animation: pulseCal 1s ease-in-out infinite alternate;
                }

                @keyframes pulseCal {
                    from {
                        box-shadow: 0 0 0px #0d6efd;
                    }

                    to {
                        box-shadow: 0 0 8px #0d6efd;
                    }
                }

                .task-list {
                    margin: .35rem 0 0;
                    display: grid;
                    gap: .25rem;
                }

                .task-pill {
                    display: inline-flex;
                    width: 100%;
                    background: #f6f8fc;
                    border: 1px solid #e5eaf2;
                    border-radius: 8px;
                    padding: .25rem .4rem;
                    font-size: .78rem;
                    line-height: 1.2;
                    text-align: left;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .task-pill .dot {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    display: inline-block;
                    margin-right: .35rem;
                    margin-top: .25rem;
                    flex: 0 0 6px;
                }

                .task-pill .txt {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .task-more {
                    font-size: .75rem;
                    color: #6c757d;
                }

                @media (max-width: 991px) {
                    .calendar-table td {
                        width: 100px;
                        min-width: 95px;
                        height: 90px;
                    }
                }

                @media (max-width: 575px) {
                    .calendar-table td {
                        width: 80px;
                        min-width: 72px;
                        height: 84px;
                    }
                }
            </style>

            <!-- ===== Tabel Jadwal List ===== -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Jadwal Saya</h5>

                        @if (session('success_schedule'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success_schedule') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-borderless align-middle" id="tableSchedule">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>

                                        <th>Divisi</th>
                                        <th>Judul</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($schedules as $i => $sch)
                                        @php
                                            $tglObj = optional($sch->tanggal);
                                            $tglKey = $tglObj ? $tglObj->format('Y-m-d') : null;
                                            $attRow =
                                                $tglKey && isset($attendanceMap[$tglKey])
                                                    ? $attendanceMap[$tglKey]
                                                    : null;

                                            $attStatus = $attRow ? strtolower($attRow->status ?? '') : null;
                                            $attBadge = $attStatus ? $statusBadgeMap[$attStatus] ?? 'secondary' : null;
                                            $attLabel = $attStatus
                                                ? $statusLabelMap[$attStatus] ?? ucfirst($attStatus)
                                                : null;
                                            $attIn =
                                                $attRow && $attRow->in_time
                                                    ? \Illuminate\Support\Str::of($attRow->in_time)->substr(0, 5)
                                                    : null;
                                            $attOut =
                                                $attRow && $attRow->out_time
                                                    ? \Illuminate\Support\Str::of($attRow->out_time)->substr(0, 5)
                                                    : null;
                                            $attNote = $attRow->note ?? null;
                                        @endphp
                                        <tr>
                                            <td>{{ method_exists($schedules, 'firstItem') ? $schedules->firstItem() + $i : $i + 1 }}
                                            </td>
                                            <td>
                                                <div class="small">
                                                    {{ $tglObj ? $tglObj->format('d/m/Y') : '—' }}
                                                </div>
                                            </td>

                                            <td>{{ strsafe($sch->divisi) }}</td>
                                            <td class="fw-semibold">{{ $sch->judul ?? '(Tanpa judul)' }}</td>
                                            <td>
                                                {{ $sch->catatan ?? '—' }}
                                                @if ($attRow)
                                                    <div class="small mt-1">
                                                        <span class="text-muted">Kehadiran:</span>
                                                        <span
                                                            class="badge bg-{{ $attBadge }}">{{ $attLabel }}</span>
                                                        <div class="text-muted">
                                                            Masuk: {{ $attIn ?? '—' }} | Pulang: {{ $attOut ?? '—' }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                                Belum ada jadwal.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (method_exists($schedules, 'links'))
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="small text-muted">
                                    Menampilkan
                                    <span>{{ $schedules->total() ? $schedules->firstItem() : 0 }}</span>–<span>{{ $schedules->lastItem() }}</span>
                                    dari <span>{{ $schedules->total() }}</span>
                                </div>
                                {{ $schedules->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ===== Modal Detail Jadwal per Tanggal ===== --}}
    <div class="modal fade" id="scheduleDetailModal" tabindex="-1" aria-labelledby="scheduleDetailLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleDetailLabel">
                        <i class="bi bi-info-circle me-1"></i> Detail Jadwal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 fw-semibold" id="detailDate">Tanggal: —</div>
                    <div id="detailList"><!-- tabel detail di-render via JS --></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal Alasan Overtime ===== --}}
    <div class="modal fade" id="modalOvertimeReason" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-clock-history me-2"></i>Konfirmasi Overtime
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Anda melakukan absen pulang di atas jam 16:00. Mohon isi alasan lembur.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Overtime</label>
                        <textarea class="form-control" id="otReasonInput" rows="3"
                            placeholder="Contoh: Menyelesaikan revisi jobdesk A..."></textarea>
                        <div class="form-text text-danger d-none" id="otReasonError">Wajib diisi!</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSubmitOvertime">
                        <i class="bi bi-send me-1"></i> Kirim Absen Pulang
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Script Kalender + Modal + Jam WIB ===== --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // === Jam real-time WIB (untuk widget kehadiran) ===
            (function() {
                const el = document.getElementById('wibNow');
                if (!el) return;
                const pad = n => String(n).padStart(2, '0');

                function tick() {
                    try {
                        const d = new Date();
                        const utc = d.getTime() + d.getTimezoneOffset() * 60000;
                        const jkt = new Date(utc + 7 * 60 * 60000);
                        el.textContent =
                            `${pad(jkt.getHours())}:${pad(jkt.getMinutes())}`;
                    } catch {
                        const d = new Date();
                        el.textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}`;
                    }
                }
                tick();
                setInterval(tick, 1000);
            })();

            // ==== Data jadwal & kehadiran dari Laravel untuk Modal Detail ====
            const SCHEDULE_ITEMS = @json($scheduleItems ?? [], JSON_UNESCAPED_UNICODE);
            const ATTENDANCE_ITEMS = @json($attendanceByDate ?? [], JSON_UNESCAPED_UNICODE);
            const HOLIDAYS = @json($holidays ?? [], JSON_UNESCAPED_UNICODE);

            // ===== Modal Detail =====
            const modalEl = document.getElementById('scheduleDetailModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
            const detailDateEl = document.getElementById('detailDate');
            const detailListEl = document.getElementById('detailList');

            // Global click lister untuk sel kalender
            document.addEventListener('click', function(e) {
                const cell = e.target.closest('.cal-cell');
                if (!cell || !modal) return;

                const dateKey = cell.dataset.date;
                if (!dateKey) return;

                openDetailFor(dateKey);
            });

            function escapeHtml(text) {
                if (!text) return text;
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function openDetailFor(dateKey) {
                const items = SCHEDULE_ITEMS[dateKey] || [];
                const att = ATTENDANCE_ITEMS[dateKey] || null;
                const holiday = HOLIDAYS[dateKey] || null;

                // header tanggal
                try {
                    const [y, m, d] = dateKey.split('-').map((n) => parseInt(n, 10));
                    const label = `${String(d).padStart(2,'0')}/${String(m).padStart(2,'0')}/${y}`;
                    detailDateEl.textContent = `Tanggal: ${label}`;
                } catch {
                    detailDateEl.textContent = `Tanggal: ${dateKey}`;
                }

                if (!items.length && !att && !holiday) {
                    detailListEl.innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                            <p class="mb-0">Tidak ada jadwal, kehadiran, atau hari libur.</p>
                        </div>`;
                    modal.show();
                    return;
                }

                // Bangun HTML detail
                let htmlContent = '';

                // 0. Info Libur
                if (holiday) {
                    htmlContent += `
                        <div class="alert alert-danger d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-event me-2 fs-4"></i>
                            <div>
                                <div class="fw-bold">HARI LIBUR NASIONAL</div>
                                <div>${escapeHtml(holiday)}</div>
                            </div>
                        </div>
                    `;
                }

                // 1. Info Kehadiran
                if (att) {
                    const st = att.status;
                    let badgeClass = 'secondary';
                    let label = st;

                    if (st === 'present') {
                        badgeClass = 'success';
                        label = 'Hadir';
                    } else if (st === 'late') {
                        badgeClass = 'warning text-dark';
                        label = 'Telat';
                    } else if (st === 'absent') {
                        badgeClass = 'danger';
                        label = 'Alpa';
                    } else if (st === 'izin') {
                        badgeClass = 'secondary';
                        label = 'Izin';
                    } else if (st === 'leave') {
                        badgeClass = 'secondary';
                        label = 'Cuti';
                    }

                    const inTime = att.in_time ? att.in_time.substr(0, 5) : '—';
                    const outTime = att.out_time ? att.out_time.substr(0, 5) : '—';

                    htmlContent += `
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body p-3">
                                <h6 class="card-title fw-bold mb-2 text-primary">
                                    <i class="bi bi-person-badge me-1"></i> Data Kehadiran
                                </h6>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="badge bg-${badgeClass} fs-6">${label}</span>
                                    <div class="small text-muted border-start ps-3">
                                        Masuk: <strong>${escapeHtml(inTime)}</strong> &nbsp;&bull;&nbsp; 
                                        Pulang: <strong>${escapeHtml(outTime)}</strong>
                                        ${
                                            (att.out_time && att.out_time.substring(0,5) > '16:00') 
                                            ? '<span class="badge ms-1" style="background-color: #6f42c1;">Overtime</span>' 
                                            : ''
                                        }
                                    </div>
                                </div>
                                ${att.note ? `<div class="small text-muted fst-italic mt-1"><i class="bi bi-sticky me-1"></i>Catatan: ${escapeHtml(att.note)}</div>` : ''}
                            </div>
                        </div>
                    `;
                }

                // 2. Info Jadwal
                if (items.length > 0) {
                    htmlContent += `
                        <h6 class="fw-bold mb-2"><i class="bi bi-list-task me-1"></i> Daftar Jadwal (${items.length})</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Divisi</th>
                                        <th>Judul</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map(item => `
                                                                                                                <tr>
                                                                                                                    <td class="small text-nowrap">${escapeHtml(item.divisi || '—')}</td>
                                                                                                                    <td class="small fw-semibold text-primary">${escapeHtml(item.judul)}</td>
                                                                                                                    <td class="small text-muted">${escapeHtml(item.catatan || '—')}</td>
                                                                                                                </tr>
                                                                                                            `).join('')}
                                </tbody>
                            </table>
                        </div>
                     `;
                } else if (!items.length && att) {
                    htmlContent +=
                        `<div class="text-muted small fst-italic mt-2">Tidak ada jadwal tugas dari admin.</div>`;
                }

                detailListEl.innerHTML = htmlContent;
                modal.show();
            }
        });

        // === OVERTIME CHECK LOGIC ===
        document.addEventListener('DOMContentLoaded', function() {
            const formOut = document.getElementById('formPunchOut');
            if (formOut) {
                formOut.addEventListener('submit', function(e) {
                    const now = new Date();
                    // Cek jika jam >= 16:00
                    if (now.getHours() >= 16) {
                        e.preventDefault();
                        const modal = new bootstrap.Modal(document.getElementById('modalOvertimeReason'));
                        modal.show();
                    }
                    // Jika < 16:00, submit seperti biasa
                });
            }

            const btnSubmitOt = document.getElementById('btnSubmitOvertime');
            if (btnSubmitOt) {
                btnSubmitOt.addEventListener('click', function() {
                    const reason = document.getElementById('otReasonInput').value.trim();
                    const err = document.getElementById('otReasonError');

                    if (!reason) {
                        err.classList.remove('d-none');
                        return;
                    }
                    err.classList.add('d-none');

                    // Isi hidden input dan submit
                    document.getElementById('hiddenOvertimeReason').value = reason;

                    // Hide modal
                    const modalEl = document.getElementById('modalOvertimeReason');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    // Submit form
                    formOut.submit();
                });
            }
        });
    </script>
@endsection
