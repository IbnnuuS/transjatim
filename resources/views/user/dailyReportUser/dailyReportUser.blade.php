@extends('user.masterUser')

@section('title', 'Daily Summary')

@section('content')
    @php
        // ====== Fallback date & timezone ======
        $appTz = config('app.timezone', 'UTC') ?: 'UTC';
        if (!isset($pickedYmd) || empty($pickedYmd)) {
            try {
                $pickedYmd = \Illuminate\Support\Carbon::now($appTz)->format('Y-m-d');
            } catch (\Throwable $e) {
                $pickedYmd = date('Y-m-d');
            }
        }

        // ====== Aman-kan $tasks agar tidak "Undefined variable $tasks" ======
        $tasksCol = collect();
        if (isset($tasks)) {
            $tasksCol = $tasks instanceof \Illuminate\Support\Collection ? $tasks : collect($tasks);
        }

        // ====== Helpers kecil (guard agar tak re-declare) ======
        if (!function_exists('badgeStatus')) {
            function badgeStatus($s)
            {
                $s = strtolower((string) $s);
                return match ($s) {
                    'done' => 'bg-success',
                    'in_progress' => 'bg-info',
                    'pending' => 'bg-secondary',
                    'cancelled' => 'bg-dark',
                    'verification' => 'bg-primary',
                    'delayed' => 'bg-warning text-dark',
                    'rework' => 'bg-danger',
                    'to_do' => 'bg-light text-dark border',
                    default => 'bg-secondary',
                };
            }
        }

        if (!function_exists('fmtTime')) {
            function fmtTime($t)
            {
                if (!$t) {
                    return '';
                }
                try {
                    return \Illuminate\Support\Str::of($t)->substr(0, 5);
                } catch (\Throwable $e) {
                    return $t;
                }
            }
        }

        if (!function_exists('emptyState')) {
            function emptyState($text = 'Belum ada aktivitas pada tanggal ini.')
            {
                return '<div class="text-center text-muted py-5">
                <i class="bi bi-cloud fs-1 d-block mb-2"></i>
                <div>' .
                    $text .
                    '</div>
              </div>';
            }
        }

        if (!function_exists('minutesToHuman')) {
            function minutesToHuman($m)
            {
                $m = abs((int) $m);
                $h = intdiv($m, 60);
                $mm = $m % 60;
                return $h . ' jam ' . $mm . ' menit';
            }
        }

        if (!function_exists('diffMinutes')) {
            function diffMinutes($start, $end)
            {
                try {
                    if (empty($start) || empty($end)) {
                        return 0;
                    }
                    $startC = \Illuminate\Support\Carbon::createFromFormat('H:i:s', $start);
                    $endC = \Illuminate\Support\Carbon::createFromFormat('H:i:s', $end);
                    if ($endC->lessThan($startC)) {
                        $endC->addDay();
                    }
                    return $endC->diffInMinutes($startC);
                } catch (\Throwable $e) {
                    return 0;
                }
            }
        }

        // ✅ Helper ambil bukti link (fleksibel)
        if (!function_exists('resolveProofLink')) {
            function resolveProofLink($task)
            {
                $candidates = [
                    $task->proof_link ?? null,
                    $task->bukti_link ?? null,
                    $task->bukti ?? null,
                    $task->link ?? null,
                    $task->url ?? null,
                    $task->photo_url ?? null,
                    $task->attachment ?? null,
                    $task->evidence ?? null,
                ];

                $raw = null;
                foreach ($candidates as $v) {
                    if (!empty($v)) {
                        $raw = trim((string) $v);
                        break;
                    }
                }

                if (!$raw) {
                    return null;
                }

                // Jika sudah URL lengkap
                if (preg_match('~^https?://~i', $raw)) {
                    return $raw;
                }

                // Jika path file storage
                return asset('storage/' . ltrim($raw, '/'));
            }
        }
    @endphp

    <div class="pagetitle">
        <h1>Laporan Harian</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.user') }}">Home</a></li>
                <li class="breadcrumb-item active">Laporan Harian</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        {{-- =================== FILTER BAR =================== --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('user.daily') }}" class="row g-2 align-items-end mt-2">
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date" class="form-control" value="{{ $pickedYmd }}" required>
                    </div>

                    @php
                        $hasUserPicker =
                            isset($users) &&
                            $users instanceof \Illuminate\Support\Collection &&
                            $users->count() > 0 &&
                            isset($selectedId);
                    @endphp

                    @if ($hasUserPicker)
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">Teams</label>
                            <select name="user_id" class="form-select">
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected((int) ($selectedId ?? 0) === (int) $u->id)>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-sm-12 col-md-5 d-flex gap-2">
                        <a class="btn btn-outline-secondary mt-3 mt-md-0" href="{{ route('user.daily') }}">
                            <i class="bi bi-trash3 me-1"></i> Reset
                        </a>

                        <button class="btn btn-primary mt-3 mt-md-0">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>

                        @php
                            $q = request()->query();
                            unset($q['page']);
                        @endphp

                        <a href="{{ route('user.daily.export.pdf', $q) }}" target="_blank"
                            class="btn btn-danger mt-3 mt-md-0">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- =================== KPI CARDS =================== --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="card info-card">
                    <div class="card-body">
                        <h6 class="card-title">Total Tugas</h6>
                        <div class="fs-3 fw-semibold">{{ (int) ($summary['total_tasks'] ?? $tasksCol->count()) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card info-card">
                    <div class="card-body">
                        <h6 class="card-title">Rata-rata Progress</h6>
                        <div class="fs-3 fw-semibold">
                            @php
                                $avgProg =
                                    $summary['avg_progress'] ??
                                    round($tasksCol->avg(fn($t) => (int) ($t->progress ?? 0)));
                            @endphp
                            {{ (int) ($avgProg ?: 0) }}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card info-card">
                    <div class="card-body">
                        <h6 class="card-title">Selesai (Done)</h6>
                        <div class="fs-3 fw-semibold">
                            {{ (int) ($summary['done'] ?? $tasksCol->filter(fn($t) => strtolower($t->status ?? '') === 'done')->count()) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =================== TABEL AKTIVITAS =================== --}}
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    Aktivitas pada {{ \Illuminate\Support\Carbon::parse($pickedYmd)->format('d/m/Y') }}
                    ({{ $appTz }})
                </h5>

                @if ($tasksCol->isEmpty())
                    {!! emptyState('Belum ada aktivitas pada tanggal ini.') !!}
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:56px">#</th>
                                    <th style="width:140px">Waktu</th>
                                    <th>Pekerjaan</th>
                                    <th style="width:120px">Status</th>
                                    <th style="width:110px">Persentase</th>
                                    <th>Bukti</th>
                                    <th>Hasil</th>
                                    <th>Kekurangan</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tasksCol as $i => $t)
                                    @php
                                        $st = strtolower($t->status ?? '');
                                        $pct = max(1, min(100, (int) ($t->progress ?? 1)));
                                        $rowMinutes = diffMinutes($t->start_time ?? null, $t->end_time ?? null);
                                        $proofLink = resolveProofLink($t);
                                    @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>

                                        <td>
                                            <div class="small">
                                                {{ fmtTime($t->start_time) }}@if (!empty($t->start_time) && !empty($t->end_time))
                                                    –
                                                @endif{{ fmtTime($t->end_time) }}
                                            </div>

                                            @if (!empty($t->start_time) && !empty($t->end_time) && $rowMinutes > 0)
                                                <div class="xsmall text-muted">{{ minutesToHuman($rowMinutes) }}</div>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="fw-semibold">{{ $t->judul ?? '-' }}</div>
                                            @if (!empty($t->project_name))
                                                <div class="small text-muted">Project: {{ $t->project_name }}</div>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="badge {{ badgeStatus($st) }}">
                                                {{ ucwords(str_replace('_', ' ', $st ?: 'Unknown')) }}
                                            </span>
                                        </td>

                                        <td>
                                            <div class="d-flex justify-content-between small">
                                                <span></span><span>{{ $pct }}%</span>
                                            </div>
                                            <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                                aria-valuenow="{{ $pct }}">
                                                <div class="progress-bar" style="width: {{ $pct }}%"></div>
                                            </div>
                                        </td>

                                        {{-- ✅ Kolom Bukti Link --}}
                                        <td>
                                            @if ($proofLink)
                                                <a href="{{ $proofLink }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-link-45deg"></i> Lihat
                                                </a>
                                            @else
                                                <span class="text-muted small">Tidak Ada</span>
                                            @endif
                                        </td>

                                        <td style="white-space:pre-wrap">{{ $t->result ?? '-' }}</td>
                                        <td style="white-space:pre-wrap">{{ $t->shortcoming ?? '-' }}</td>
                                        <td style="white-space:pre-wrap">{{ $t->detail ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
