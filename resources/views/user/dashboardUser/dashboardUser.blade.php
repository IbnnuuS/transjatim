@extends('user.masterUser')

@section('content')
    @php
        use Illuminate\Support\Carbon;

        // Paksa selalu WIB di tampilan umum
        $appTz = 'Asia/Jakarta';
        $tzLabel = 'WIB';
        $locale = app()->getLocale();
        $me = auth()->user();

        // ====== Tanggal untuk header "Aktivitas pada ..." (pakai WIB)
        $pickedYmd = now($appTz)->toDateString();

        // ===== Activity feed (gabungan: dari controller + last_login_at) =====
        $activityFeed = [];

        if (!empty($recentActivities) && is_iterable($recentActivities)) {
            foreach ($recentActivities as $it) {
                $t = $it['time'] ?? null;
                if ($t instanceof Carbon) {
                    $t = $t->timezone($appTz);
                } elseif (is_string($t) && trim($t) !== '') {
                    try {
                        $t = Carbon::parse($t, $appTz)->timezone($appTz);
                    } catch (\Throwable $e) {
                        $t = null;
                    }
                } else {
                    $t = null;
                }

                $activityFeed[] = [
                    'icon' => $it['icon'] ?? 'bi bi-clock',
                    'title' => $it['title'] ?? 'Aktivitas',
                    'time' => $t,
                    'desc' => $it['desc'] ?? null,
                ];
            }
        }

        usort($activityFeed, function ($a, $b) {
            $ta = $a['time'] ?? null;
            $tb = $b['time'] ?? null;
            if ($ta && $tb) {
                return $tb->timestamp <=> $ta->timestamp;
            }
            if ($ta && !$tb) {
                return -1;
            }
            if (!$ta && $tb) {
                return 1;
            }
            return 0;
        });
        $activityFeed = array_slice($activityFeed, 0, 5);

        $lastLoginText =
            $me && $me->last_login_at
                ? e($me->last_login_at->timezone($appTz)->format('d/m/Y H:i')) . ' ' . e($tzLabel)
                : '—';

        // ===== Helper durasi (menangani lintas tengah malam) =====
        function humanMinutes(?string $start, ?string $end): string
        {
            if (!$start || !$end) {
                return '—';
            }
            try {
                $s = Carbon::createFromFormat('H:i', $start);
                $e = Carbon::createFromFormat('H:i', $end);
            } catch (\Throwable $e) {
                return '—';
            }
            if ($e->lessThan($s)) {
                // lintas tengah malam, tambah 1 hari ke end
                $e->addDay();
            }
            $mins = $s->diffInMinutes($e);
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            if ($h > 0 && $m > 0) {
                return "{$h} jam {$m} menit";
            }
            if ($h > 0) {
                return "{$h} jam";
            }
            return "{$m} menit";
        }
    @endphp

    <div class="pagetitle">
        <h1>Dashboard Teams</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Teams</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="row">

            <!-- Left -->
            <div class="col-12 col-lg-9">
                <div class="row">

                    <!-- Avg Progress -->
                    <div class="col-12 col-md-4">
                        <div class="card info-card sales-card">
                            <div class="card-body">
                                <h5 class="card-title">Rata-rata Progress <span>| 1 Bulan</span></h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6 id="metricAvg">{{ (int) ($avgProgress ?? 0) }}%</h6>
                                        <div class="progress progress-sm mt-1">
                                            <div class="progress-bar" id="barAvg" role="progressbar"
                                                style="width: {{ max(0, min(100, (int) ($avgProgress ?? 0))) }}%"></div>
                                        </div>
                                        <div class="text-muted small mt-1">Login terakhir: {{ $lastLoginText }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Done Count -->
                    <div class="col-12 col-md-4">
                        <div class="card info-card revenue-card">
                            <div class="card-body">
                                <h5 class="card-title">Tugas Selesai <span>| 1 Bulan</span></h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6 id="metricDone">{{ (int) ($doneCount ?? 0) }}</h6>
                                        <span class="text-muted small">Status saya</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Tasks Card -->
                    <div class="col-12 col-md-4">
                        <div class="card info-card customers-card">
                            <div class="card-body">
                                <h5 class="card-title">Tugas Pending <span>| Butuh Action</span></h5>
                                <div class="d-flex align-items-center">
                                    <div
                                        class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-warning text-white">
                                        <i class="bi bi-exclamation-circle"></i>
                                    </div>
                                    <div class="ps-3">
                                        @php
                                            $pendingTasks = \App\Models\JobdeskTask::where('status', 'pending')
                                                ->whereHas('jobdesk', fn($q) => $q->where('user_id', auth()->id()))
                                                ->where(function ($q) {
                                                    $q->where('is_template', 0)->orWhereNull('is_template');
                                                })
                                                ->get();
                                        @endphp
                                        <h6>{{ $pendingTasks->count() }}</h6>
                                        <span class="text-muted small">Menunggu di-ACC</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Tasks List -->
                    @if ($pendingTasks->count() > 0)
                        <div class="col-12">
                            <div class="card border-warning border-2">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0 text-white"><i class="bi bi-exclamation-triangle me-2"></i> Penugasan
                                        Masuk (Harus di-ACC)</h5>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="list-group">
                                        @foreach ($pendingTasks as $pt)
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1 fw-bold">{{ $pt->judul }}</h6>
                                                    <small class="text-muted">
                                                        Deadline: {{ optional($pt->schedule_date)->format('d M Y') }} |
                                                        PIC: {{ $pt->pic }}
                                                    </small>
                                                    @if ($pt->detail)
                                                        <p class="mb-0 small text-muted fst-italic">
                                                            {{ Str::limit($pt->detail, 50) }}</p>
                                                    @endif
                                                </div>
                                                <form action="{{ route('jobdesk.accept', $pt->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-check-circle me-1"></i> Terima / ACC
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Reports Chart -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Grafik Progres Saya <span>/ 1 Bulan</span></h5>
                                <div id="reportsChart"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Laporan Saya (Ringkas) -->
                    <div class="col-12">
                        <div class="card recent-sales overflow-auto">
                            <div class="card-body">
                                <h5 class="card-title">Laporan Saya <span>| Terbaru</span></h5>
                                <div class="table-responsive">
                                    <table class="table table-borderless" id="tableDaily">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Tanggal</th>
                                                <th>Pekerjaan</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(($latestTasks ?? []) as $i => $t)
                                                @php
                                                    $status = strtolower($t->status ?? '');
                                                    $isFromAdmin = !empty(optional($t->jobdesk)->assignment_id);

                                                    // ==== Disable logic "belum waktunya" (copas dari jobdeskUser) ====
                                                    $canWork = true;
                                                    $msgWork = '';

                                                    if (!empty($t->schedule_date) && !empty($t->start_time)) {
                                                        $now = now('Asia/Jakarta');
                                                        try {
                                                            $taskStart = \Illuminate\Support\Carbon::parse(
                                                                $t->schedule_date . ' ' . $t->start_time,
                                                                'Asia/Jakarta',
                                                            );

                                                            if ($taskStart->isFuture()) {
                                                                $canWork = false;
                                                                $msgWork = 'Belum waktunya (' . $t->start_time . ')';
                                                            }
                                                        } catch (\Throwable $e) {
                                                        }
                                                    }

                                                    // ==== Template admin kalau kosong ====
                                                    $defaultResult =
                                                        $isFromAdmin && empty(trim((string) $t->result))
                                                            ? '1. '
                                                            : $t->result ?? '';
                                                    $defaultShort =
                                                        $isFromAdmin && empty(trim((string) $t->shortcoming))
                                                            ? '1. '
                                                            : $t->shortcoming ?? '';
                                                @endphp
                                                <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td class="small text-muted">
                                                        {{ optional($t->schedule_date)->format('Y-m-d') ?? '-' }}
                                                    </td>
                                                    <td class="fw-semibold">
                                                        {{ $t->judul ?? '-' }}
                                                        <br>
                                                        <small class="text-muted">{{ $t->pic }}</small>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge
                                                            @if ($status == 'done') bg-success
                                                            @elseif($status == 'in_progress' || $status == 'sedang_mengerjakan') bg-info
                                                            @elseif($status == 'pending') bg-secondary
                                                            @elseif($status == 'verification') bg-primary
                                                            @elseif($status == 'delayed') bg-warning text-dark
                                                            @elseif($status == 'rework') bg-danger
                                                            @elseif($status == 'cancelled') bg-dark
                                                            @else bg-light text-dark border @endif">
                                                            {{ $status ? ucwords(str_replace('_', ' ', $status)) : '—' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        @if ($canWork)
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modalSubmit-{{ $t->id }}">
                                                                <i class="bi bi-pencil-square me-1"></i> Update
                                                            </button>
                                                        @else
                                                            <button type="button" class="btn btn-sm btn-secondary"
                                                                disabled>
                                                                <i class="bi bi-lock me-1"></i> Update
                                                            </button>
                                                            @if ($msgWork)
                                                                <div class="small text-danger mt-1">
                                                                    <i class="bi bi-clock-history"></i> {{ $msgWork }}
                                                                </div>
                                                            @endif
                                                        @endif

                                                        @if ($t->proof_link)
                                                            <a href="{{ $t->proof_link }}" target="_blank"
                                                                class="btn btn-sm btn-outline-success ms-1">
                                                                <i class="bi bi-link-45deg me-1"></i> Buka Link
                                                            </a>
                                                        @endif

                                                        {{-- ===== MODAL UPDATE (SAMA SEPERTI jobdeskUser) ===== --}}
                                                        <div class="modal fade" id="modalSubmit-{{ $t->id }}"
                                                            tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <form action="{{ route('jobdesk.submit', $t->id) }}"
                                                                    method="POST" class="modal-content">
                                                                    @csrf
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Update Status Jobdesk</h5>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label>Judul Task</label>
                                                                            <input type="text" class="form-control"
                                                                                value="{{ $t->judul }}" disabled>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label
                                                                                class="form-label required">Status</label>
                                                                            <select name="status" class="form-select"
                                                                                required>
                                                                                @php
                                                                                    $currentS = strtolower(
                                                                                        $t->status ?? '',
                                                                                    );
                                                                                    $opts = [
                                                                                        'pending' => 'Pending',
                                                                                        'in_progress' => 'In Progress',
                                                                                        'verification' =>
                                                                                            'Verification',
                                                                                        'rework' => 'Rework',
                                                                                        'done' => 'Done',
                                                                                        'delayed' => 'Delayed',
                                                                                        'cancelled' => 'Cancelled',
                                                                                    ];
                                                                                @endphp
                                                                                @foreach ($opts as $k => $v)
                                                                                    <option value="{{ $k }}"
                                                                                        @selected($currentS === $k)>
                                                                                        {{ $v }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Link Bukti</label>
                                                                            <input type="url" name="proof_link"
                                                                                class="form-control"
                                                                                placeholder="https://..."
                                                                                value="{{ $t->proof_link }}">
                                                                            <div class="form-text">Opsional.</div>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Hasil /
                                                                                Result</label>
                                                                            <textarea name="result" class="form-control" rows="2">{{ old('result', $defaultResult) }}</textarea>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Kendala /
                                                                                Shortcoming</label>
                                                                            <textarea name="shortcoming" class="form-control" rows="2">{{ old('shortcoming', $defaultShort) }}</textarea>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <label class="form-label">Detail</label>
                                                                            <textarea name="detail" class="form-control" rows="2">{{ old('detail', $t->detail) }}</textarea>
                                                                        </div>
                                                                    </div>

                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary"
                                                                            data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" class="btn btn-primary">
                                                                            Simpan
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                        {{-- ===== /MODAL UPDATE ===== --}}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">Belum ada tugas
                                                        aktif.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ====== Laporan Saya (Detail mirip Daily Report Admin) ====== -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <h5 class="card-title mb-0">
                                        Aktivitas pada
                                        {{ \Illuminate\Support\Carbon::parse($pickedYmd, $appTz)->format('d/m/Y') }}
                                    </h5>
                                </div>

                                <div class="table-responsive mt-3" style="max-height:70vh; overflow-y:auto;">
                                    <table class="table align-middle table-striped table-bordered"
                                        style="min-width:1200px;">
                                        <thead class="text-nowrap">
                                            <tr>
                                                <th style="width:42px">#</th>
                                                <th style="min-width:180px;">Waktu</th>
                                                <th style="min-width:260px;">Pekerjaan / Project</th>
                                                <th>Status</th>
                                                <th>Persentase</th>
                                                <th style="min-width:200px;">Hasil</th>
                                                <th style="min-width:200px;">Kekurangan</th>
                                                <th style="min-width:240px;">Detail</th>
                                                <th style="min-width:180px;">Bukti</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                // Sumber data: gunakan $myDailyTasks jika ada; fallback $latestTasks
                                                $detailRows = collect($myDailyTasks ?? ($latestTasks ?? []));
                                            @endphp

                                            @forelse($detailRows as $i => $t)
                                                @php
                                                    // ===== WAKTU (PRIORITAS: JAM KERJA → CREATED_AT WIB) =====
                                                    $s = $t->start_time ?? null;
                                                    $e = $t->end_time ?? null;

                                                    if ($s || $e) {
                                                        $timeLabel = $s && $e ? "{$s}–{$e}" : ($s ?: $e);
                                                        $dur = humanMinutes($s, $e);
                                                    } elseif (!empty($t->created_at)) {
                                                        $createdWib = $t->created_at->timezone($appTz);
                                                        $timeLabel = $createdWib->format('d/m/Y H:i'); // TANPA WIB
                                                        $dur = '';
                                                    } else {
                                                        $timeLabel = '—';
                                                        $dur = '—';
                                                    }

                                                    // Judul + Project
                                                    $judul = trim((string) ($t->judul ?? '-'));
                                                    $project = trim((string) ($t->project_name ?? ''));
                                                    $titleFull = $project
                                                        ? "{$judul} <span class='text-muted small'>({$project})</span>"
                                                        : e($judul);

                                                    // PIC
                                                    $pic = $t->pic ?? null;

                                                    // Status & progress
                                                    $st = strtolower($t->status ?? '');
                                                    $prog = max(0, min(100, (int) ($t->progress ?? 0)));

                                                    // Teks hasil/kekurangan/detail
                                                    $hasil = $t->result ?? '-';
                                                    $kurang = $t->shortcoming ?? '-';
                                                    $detail = $t->detail ?? '-';

                                                    // Foto (gunakan accessor url)
                                                    $photos =
                                                        $t->photos
                                                            ?->map(fn($p) => $p->url)
                                                            ->filter()
                                                            ->values()
                                                            ->all() ?? [];
                                                    $thumbs = array_slice($photos, 0, 2);
                                                    $more = max(0, count($photos) - 2);

                                                    // Proof link
                                                    $proof = trim((string) ($t->proof_link ?? ''));
                                                @endphp
                                                <tr>
                                                    <td class="text-muted">{{ $i + 1 }}</td>
                                                    <td>
                                                        <div>{{ $timeLabel }}</div>
                                                        @if (($t->start_time ?? null) || ($t->end_time ?? null))
                                                            <div class="small text-muted">{{ $dur }}</div>
                                                        @endif
                                                    </td>

                                                    <td class="fw-semibold">
                                                        {!! $titleFull !!}
                                                        @if ($pic)
                                                            <div class="small text-muted">PIC: {{ $pic }}</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge
                                                            @switch($st)
                                                              @case('done') bg-success @break
                                                              @case('in_progress') bg-info @break
                                                              @case('pending') bg-secondary @break
                                                              @case('cancelled') bg-dark @break
                                                              @case('verification') bg-primary @break
                                                              @case('delayed') bg-warning text-dark @break
                                                              @case('rework') bg-danger @break
                                                              @default bg-light text-dark border
                                                            @endswitch
                                                          ">
                                                            {{ $st ? ucwords(str_replace('_', ' ', $st)) : '—' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $prog }}%</td>
                                                    <td class="small">{{ $hasil ?: '-' }}</td>
                                                    <td class="small">{{ $kurang ?: '-' }}</td>
                                                    <td class="small">{{ $detail ?: '-' }}</td>
                                                    <td>
                                                        @if (!empty($proof))
                                                            <a href="{{ $proof }}" target="_blank" rel="noopener"
                                                                class="btn btn-sm btn-light border text-primary">
                                                                <i class="bi bi-link-45deg"></i> Buka Link
                                                            </a>
                                                        @elseif (!empty($photos))
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-primary open-photo-preview"
                                                                title="Lihat foto"
                                                                data-photos='@json($photos)'>
                                                                @foreach ($thumbs as $src)
                                                                    <img src="{{ $src }}"
                                                                        class="rounded border me-1"
                                                                        style="height:34px;width:44px;object-fit:cover">
                                                                @endforeach
                                                                @if ($more > 0)
                                                                    <span
                                                                        class="badge bg-secondary">+{{ $more }}</span>
                                                                @endif
                                                            </button>
                                                        @else
                                                            <span class="text-muted small">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                                        Belum ada aktivitas.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- ====== /Laporan Saya (Detail) ====== -->

                </div>
            </div>

            <!-- Right -->
            <div class="col-12 col-lg-3">

                <!-- Activity -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Aktivitas Terbaru</h5>

                        <ul id="activityList" class="list-group list-group-flush small">
                            @forelse($activityFeed as $act)
                                @php
                                    /** @var \Illuminate\Support\Carbon|null $t */
                                    $t = $act['time'] ?? null;
                                    $timeText = $t
                                        ? e($t->format('d/m/Y H:i')) .
                                            ' ' .
                                            e($tzLabel) .
                                            ' • ' .
                                            e($t->copy()->locale($locale)->diffForHumans())
                                        : '—';
                                @endphp
                                <li class="list-group-item d-flex align-items-start justify-content-between gap-2">
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="text-primary mt-1"><i
                                                class="{{ $act['icon'] ?? 'bi bi-clock' }}"></i></div>
                                        <div>
                                            <div class="fw-semibold">{{ e($act['title'] ?? 'Aktivitas') }}</div>
                                            @if (!empty($act['desc']))
                                                <div class="text-muted">{{ e($act['desc']) }}</div>
                                            @endif
                                            <div class="text-muted">{{ $timeText }}</div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">Belum ada aktivitas.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </section>

    {{-- ===== Charts (ApexCharts) ===== --}}
    @php
        $chart = $chartPayload ?? [
            'labels' => [],
            'seriesAvg' => [],
            'seriesCount' => [],
            'donut' => [],
        ];
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- ===== Modal Preview Foto (dipakai ringkas & detail) ===== --}}
    <div class="modal fade" id="modalPhotoPreview" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title mb-0">Bukti Foto</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="photoPreviewWrap" class="d-flex flex-wrap gap-2 justify-content-center"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const LABELS = @json($chart['labels'] ?? []);
            const SERIES_AVG = @json($chart['seriesAvg'] ?? []);
            const SERIES_CNT = @json($chart['seriesCount'] ?? []);
            const DONUT = @json($chart['donut'] ?? []);

            // ===== Line/Bar Chart 1 Bulan =====
            const elReports = document.querySelector('#reportsChart');
            if (window.ApexCharts && elReports) {
                const options = {
                    chart: {
                        type: 'line',
                        height: 320,
                        toolbar: {
                            show: false
                        }
                    },
                    stroke: {
                        width: [3, 0]
                    },
                    series: [{
                            name: 'Avg Progress',
                            type: 'line',
                            data: SERIES_AVG
                        },
                        {
                            name: 'Jumlah Tugas',
                            type: 'column',
                            data: SERIES_CNT
                        },
                    ],
                    xaxis: {
                        categories: LABELS
                    },
                    dataLabels: {
                        enabled: false
                    },
                    yaxis: [{
                            title: {
                                text: 'Progress (%)'
                            },
                            max: 100,
                            min: 0
                        },
                        {
                            opposite: true,
                            title: {
                                text: 'Tugas (qty)'
                            }
                        }
                    ],
                    tooltip: {
                        shared: true,
                        intersect: false
                    }
                };
                new ApexCharts(elReports, options).render();
            }

            // ====== FOTO PREVIEW: binder tombol ======
            const modalEl = document.getElementById('modalPhotoPreview');
            const wrap = document.getElementById('photoPreviewWrap');

            document.querySelectorAll('.open-photo-preview').forEach(btn => {
                btn.addEventListener('click', () => {
                    let photos = [];
                    try {
                        photos = JSON.parse(btn.getAttribute('data-photos') || '[]') || [];
                    } catch (_) {
                        photos = [];
                    }

                    wrap.innerHTML = '';
                    if (photos.length) {
                        const frag = document.createDocumentFragment();
                        photos.forEach((src, i) => {
                            const a = document.createElement('a');
                            a.href = src;
                            a.target = '_blank';
                            a.className = 'd-inline-block';
                            a.innerHTML = `
                                <img src="${src}" alt="bukti-${i+1}" class="rounded border"
                                     style="max-height:60vh;max-width:100%;object-fit:contain">
                            `;
                            frag.appendChild(a);
                        });
                        wrap.appendChild(frag);
                    } else {
                        wrap.innerHTML = '<div class="text-muted">Tidak ada foto.</div>';
                    }

                    const m = bootstrap?.Modal ? new bootstrap.Modal(modalEl) : null;
                    m?.show();
                });
            });
        });
    </script>
@endsection
