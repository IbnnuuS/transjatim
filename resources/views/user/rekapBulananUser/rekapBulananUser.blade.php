@extends('user.masterUser')

@section('title', 'Rekap Bulanan')

@section('content')
    @php
        // ================= Helpers =================
        if (!function_exists('statusBadgeClass')) {
            function statusBadgeClass($s)
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
                    // ✅ To Do tidak dipakai
                    default => 'bg-secondary',
                };
            }
        }

        // Hapus minus pada semua tampilan waktu & menit (selalu positif)
        if (!function_exists('minutesToHuman')) {
            function minutesToHuman($m)
            {
                $m = abs((int) $m);
                $h = intdiv($m, 60);
                $mm = $m % 60;
                return sprintf('%d jam %d menit', $h, $mm);
            }
        }

        if (!function_exists('fmtMinutes')) {
            function fmtMinutes($m)
            {
                return abs((int) $m) . ' menit';
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

                if (!$raw) return null;

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
        <h1>Rekap Bulanan</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.user') }}">Home</a></li>
                <li class="breadcrumb-item active">Rekap Bulanan</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        {{-- ============ FILTER BAR ============ --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title mb-3">Filter Rekap</h5>
                <form method="GET" action="{{ route('rekapBulanan.user') }}" class="row gy-2 gx-2 align-items-end">

                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label">Bulan</label>
                        <input type="month" name="month" class="form-control" value="{{ $monthParam }}" required>
                        <div class="form-text">
                            Periode: {{ $from }} s/d {{ $to }}
                        </div>
                    </div>

                    @if ($canPickUser)
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label">Teams</label>
                            <select name="user_id" class="form-select">
                                @foreach ($users ?? [] as $u)
                                    <option value="{{ $u->id }}" @selected($selectedId === $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-12 col-md-5 d-flex flex-wrap gap-2 mt-2 mt-md-0">
                        <button class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>
                        @php
                            $q = request()->query();
                            unset($q['page']);
                        @endphp
                        <a href="{{ route('user.rekapBulanan.export.pdf', $q) }}" target="_blank" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- ============ KPI BULANAN ============ --}}
        <div class="row g-3 mb-3">

            <div class="col-6 col-md-4">
                <div class="card info-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title">Bulan</h6>
                        <div class="fs-5 fw-semibold">{{ $monthLabel }}</div>
                        <div class="small text-muted">Hari aktif: {{ $monthTotals['days'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="card info-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Tugas</h6>
                        <div class="fs-5 fw-semibold">{{ $monthTotals['tasks'] }}</div>
                        <div class="small text-muted">
                            Progress rata-rata: {{ number_format($monthTotals['avg_progress'], 0) }}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card info-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title">Status (bulan)</h6>
                        <div class="small d-flex flex-wrap gap-1">
                            <span class="badge bg-success">Done: {{ $monthTotals['status']['done'] }}</span>
                            <span class="badge bg-info">In Progress: {{ $monthTotals['status']['in_progress'] }}</span>
                            <span class="badge bg-secondary">Pending: {{ $monthTotals['status']['pending'] }}</span>
                            <span class="badge bg-dark">Cancelled: {{ $monthTotals['status']['cancelled'] }}</span>
                            <span class="badge bg-primary">Verification: {{ $monthTotals['status']['verification'] }}</span>
                            <span class="badge bg-warning text-dark">Delayed: {{ $monthTotals['status']['delayed'] }}</span>
                            <span class="badge bg-danger">Rework: {{ $monthTotals['status']['rework'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ============ TABEL REKAP PER HARI + DETAIL ============ --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap align-items-center mb-2">
                    <h5 class="card-title mb-0">Rekap Harian — {{ $monthLabel }}</h5>
                    <small class="text-muted">Klik <strong>Lihat Detail</strong> untuk membuka ringkasan tugas per hari.</small>
                </div>

                @if (($days ?? collect())->isEmpty())
                    <div class="text-muted py-3">
                        Belum ada data pada bulan ini.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="tableRekap">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:56px">#</th>
                                    <th style="min-width:110px;">Tanggal</th>
                                    <th style="min-width:110px;">Total Tugas</th>
                                    <th style="min-width:130px;">Progress (avg)</th>
                                    <th style="min-width:220px;">Status</th>
                                    <th style="width:120px">Detail</th>
                                </tr>
                            </thead>

                            <tbody>
                                @php $row=1; @endphp
                                @foreach ($days as $ymd => $d)
                                    @php
                                        $dateLabel = \Illuminate\Support\Carbon::parse($ymd)->format('d/m/Y');
                                        $key = 'dayRow-' . $ymd;
                                    @endphp

                                    <tr>
                                        <td>{{ $row++ }}</td>
                                        <td class="fw-semibold">{{ $dateLabel }}</td>
                                        <td>{{ $d['count'] }}</td>
                                        <td>{{ number_format($d['avg_progress'], 0) }}%</td>

                                        <td class="small">
                                            <div class="d-flex flex-wrap gap-1">
                                                <span class="badge bg-success">Done: {{ $d['status']['done'] }}</span>
                                                <span class="badge bg-info">IP: {{ $d['status']['in_progress'] }}</span>
                                                <span class="badge bg-secondary">Pend: {{ $d['status']['pending'] }}</span>
                                                <span class="badge bg-dark">Canc: {{ $d['status']['cancelled'] }}</span>
                                                <span class="badge bg-primary">Ver: {{ $d['status']['verification'] }}</span>
                                                <span class="badge bg-warning text-dark">Delay: {{ $d['status']['delayed'] }}</span>
                                                <span class="badge bg-danger">Rew: {{ $d['status']['rework'] }}</span>
                                            </div>
                                        </td>

                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#{{ $key }}"
                                                aria-expanded="false" aria-controls="{{ $key }}">
                                                Lihat Detail
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Detail harian (collapse row) --}}
                                    <tr class="collapse bg-light" id="{{ $key }}">
                                        <td colspan="6">
                                            <div class="p-2 p-md-3 border rounded">
                                                @foreach ($d['tasks'] as $i => $t)
                                                    @php
                                                        $status = strtolower($t->status ?? '');
                                                        if ($status === 'to_do') continue;

                                                        $badge = statusBadgeClass($status);
                                                        $pct = max(1, min(100, (int) ($t->progress ?? 1)));
                                                        $proof = resolveProofLink($t);
                                                    @endphp

                                                    <div class="mb-3 pb-3 border-bottom">
                                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                            <div class="fw-semibold">{{ $t->judul ?? '-' }}</div>
                                                            <span class="badge {{ $badge }}">
                                                                {{ $t->status_label ?? ucwords(str_replace('_', ' ', $status)) }}
                                                            </span>
                                                        </div>

                                                        <div class="small text-muted mt-1">
                                                            Tanggal:
                                                            {{ optional($t->schedule_date)->format('Y-m-d') ?? '-' }}
                                                            <span class="mx-1">|</span>
                                                            Waktu:
                                                            {{ $t->start_time ?? '-' }}–{{ $t->end_time ?? '-' }}
                                                            <span class="mx-1">|</span>
                                                            Progress: {{ $pct }}%
                                                        </div>

                                                        {{-- ✅ Bukti hanya di detail --}}
                                                        <div class="mt-2">
                                                            <strong>Bukti:</strong>
                                                            @if ($proof)
                                                                <a href="{{ $proof }}" target="_blank">{{ $proof }}</a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada</span>
                                                            @endif
                                                        </div>

                                                        <div class="mt-2"><strong>Hasil:</strong> {{ $t->result ?? '-' }}</div>
                                                        <div class="mt-1"><strong>Kekurangan:</strong> {{ $t->shortcoming ?? '-' }}</div>
                                                        <div class="mt-1"><strong>Detail:</strong> {{ $t->detail ?? '-' }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
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
