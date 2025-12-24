@extends('admin.masterAdmin')

@section('title', 'Dashboard Admin')

@section('content')
@php
    $metrics = $metrics ?? [
        'total_users' => 0,
        'active_projects' => 0,
        'tasks_done_week' => 0,
        'tasks_done_week_pct' => 0,
        'daily_today' => 0,
        'daily_latest_ts' => null,
    ];

    // ✅ DONE: hapus blocked & to_do
    $statusCounts = $statusCounts ?? [
        'done' => 0,
        'in_progress' => 0,
        'pending' => 0,
        'verification' => 0,
        'rework' => 0,
        'delayed' => 0,
        'cancelled' => 0,
    ];

    $adminMonthly = $adminMonthly ?? [
        'avg_progress' => 0,
        'done_count' => 0,
        'today_count' => 0,
        'period' => null,
    ];

    $chartMonthly = $chartMonthly ?? [
        'labels' => [],
        'seriesAvg' => [],
        'seriesCount' => [],
    ];

    // ✅ DONE: hapus blocked & to_do
    $donutMonthly = $donutMonthly ?? [
        'pending' => 0,
        'in_progress' => 0,
        'verification' => 0,
        'rework' => 0,
        'delayed' => 0,
        'cancelled' => 0,
        'done' => 0,
    ];

    $activityFeed = $activityFeed ?? [];
    $latestTasksGlobal = $latestTasksGlobal ?? collect();

    $tz = 'Asia/Jakarta';

    $latestDaily = !empty($metrics['daily_latest_ts'])
        ? \Carbon\Carbon::parse($metrics['daily_latest_ts'], 'UTC')->setTimezone($tz)
        : null;

    $todayLocal = \Carbon\Carbon::now($tz)->toDateString();

    if ($latestDaily && $latestDaily->toDateString() !== $todayLocal) {
        $latestDaily = null;
    }

    $labelStatus = fn($s) => ucwords(str_replace('_',' ', $s));

    // ✅ DONE: hapus blocked & to_do dari legend
    $legendAllOrder = ['done','in_progress','pending','verification','rework','delayed','cancelled'];
    $legendMonthlyOrder = ['done','in_progress','pending','verification','rework','delayed','cancelled'];

    $resetUrl = route('dashboard.admin');
@endphp

<div class="pagetitle">
    <h1>Dashboard Admin</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.admin') }}">Home</a></li>
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row">

        {{-- LEFT --}}
        <div class="col-lg-9">
            <div class="row">

                {{-- KPI --}}
                <div class="col-xxl-4 col-md-6">
                    <div class="card info-card sales-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Teams</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ (int) $metrics['total_users'] }}</h6>
                                    <span class="text-muted small">Manajemen Pengguna</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-4 col-md-6">
                    <div class="card info-card revenue-card">
                        <div class="card-body">
                            <h5 class="card-title">Proyek Aktif</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-kanban"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ (int) $metrics['active_projects'] }}</h6>
                                    <span class="text-muted small">Project Tracker</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-4 col-md-6">
                    <div class="card info-card customers-card">
                        <div class="card-body">
                            <h5 class="card-title">Tugas Selesai <span>| Minggu Ini</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <div class="ps-3 w-100">
                                    @php
                                        $doneWeek = (int) $metrics['tasks_done_week'];
                                        $donePct = min(100, max(0, (int) ($metrics['tasks_done_week_pct'] ?? 0)));
                                    @endphp
                                    <h6>{{ $doneWeek }}</h6>
                                    <div class="progress progress-sm mt-1">
                                        <div class="progress-bar" style="width: {{ $donePct }}%"></div>
                                    </div>
                                    <div class="small text-muted">{{ $donePct }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KPI 1 Bulan --}}
                <div class="col-xxl-6 col-md-6">
                    <div class="card info-card sales-card">
                        <div class="card-body">
                            <h5 class="card-title">Rata-rata Progress <span>| 1 Bulan</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <div class="ps-3 w-100">
                                    @php $avg = (int)($adminMonthly['avg_progress'] ?? 0); @endphp
                                    <h6>{{ $avg }}%</h6>
                                    <div class="progress progress-sm mt-1">
                                        <div class="progress-bar" style="width: {{ max(0, min(100, $avg)) }}%"></div>
                                    </div>
                                    @if (!empty($adminMonthly['period']))
                                        <div class="small text-muted mt-1">Periode: {{ $adminMonthly['period'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-6 col-md-6">
                    <div class="card info-card revenue-card">
                        <div class="card-body">
                            <h5 class="card-title">Tugas Selesai <span>| 1 Bulan</span></h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <div class="ps-3">
                                    <h6>{{ (int) ($adminMonthly['done_count'] ?? 0) }}</h6>
                                    <span class="text-muted small">Akumulasi semua teams</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Grafik Monthly --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Grafik Bulanan (Global) <span>/ 1 Bulan</span></h5>
                            <div id="reportsChartMonthlyAdmin"></div>
                        </div>
                    </div>
                </div>

                {{-- ✅ Tabel Daily + RESET FILTER --}}
                <div class="col-12">
                    <div class="card recent-sales overflow-auto">
                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h5 class="card-title mb-0">
                                    Daily Report Terbaru <span>| Semua Teams</span>
                                </h5>

                                <a href="{{ $resetUrl }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Reset Filter
                                </a>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-borderless align-middle">
                                    <thead>
                                        <tr class="text-nowrap">
                                            <th>#</th>
                                            <th>Waktu</th>
                                            <th>Teams</th>
                                            <th>Divisi</th>
                                            <th>Task</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($latestTasksGlobal as $i => $t)
                                            @php
                                                $no = $i + 1;
                                                $ts = $t->created_at
                                                    ? \Carbon\Carbon::parse($t->created_at, 'UTC')->setTimezone($tz)->format('d/m/Y H:i')
                                                    : '—';

                                                $nama = $t->jobdesk?->user?->name ?? '—';
                                                $divisi =
                                                    $t->jobdesk?->user?->division ??
                                                    ($t->jobdesk?->division ?? '—');

                                                $job = $t->judul ?? '(Tanpa judul)';

                                                $status = strtolower($t->status ?? '');
                                                $badge = match ($status) {
                                                    'done' => 'bg-success',
                                                    'in_progress' => 'bg-primary',
                                                    'pending' => 'bg-warning text-dark',
                                                    'verification' => 'bg-info text-dark',
                                                    'rework' => 'bg-danger',
                                                    'delayed' => 'bg-secondary',
                                                    'cancelled' => 'bg-dark',
                                                    default => 'bg-light text-muted',
                                                };

                                                $displayStatus = $status
                                                    ? ucwords(str_replace('_', ' ', $status))
                                                    : '—';
                                            @endphp
                                            <tr>
                                                <td class="text-muted">{{ $no }}</td>
                                                <td>{{ $ts }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-person-circle text-secondary"></i>
                                                        <span>{{ $nama }}</span>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-info text-dark">{{ $divisi }}</span></td>
                                                <td class="fw-semibold">{{ $job }}</td>
                                                <td>
                                                    <span class="badge {{ $badge }}">
                                                        {{ $displayStatus }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="bi bi-inbox fs-5 d-block mb-2"></i>
                                                    Belum ada laporan terbaru.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- RIGHT --}}
        <div class="col-lg-3">

            {{-- Daily Today --}}
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Daily Report <span>| Hari Ini</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ (int) $metrics['daily_today'] }}</h6>
                            <span class="text-muted small">
                                Update terakhir:
                                {{ $latestDaily ? $latestDaily->format('d M Y, H:i') . ' WIB' : '—' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Donut GLOBAL --}}
            <div class="card">
                <div class="card-body pb-0">
                    <h5 class="card-title">Status Global <span>| Semua Waktu</span></h5>
                    <div id="statusDonutAdmin" style="height: 280px;"></div>

                    <div class="mt-2">
                        @foreach($legendAllOrder as $s)
                            <a href="{{ request()->fullUrlWithQuery(['status' => $s]) }}"
                               class="d-flex justify-content-between align-items-center py-1 text-decoration-none small text-dark">
                                <span>
                                    <span class="legend-dot" data-status="{{ $s }}"></span>
                                    {{ $labelStatus($s) }}
                                </span>
                                <span class="fw-semibold">{{ (int)($statusCounts[$s] ?? 0) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Donut Monthly --}}
            <div class="card">
                <div class="card-body pb-0">
                    <h5 class="card-title">Status Global <span>| 1 Bulan</span></h5>
                    <div id="statusDonutAdminMonthly" style="height: 280px;"></div>

                    <div class="mt-2">
                        @foreach($legendMonthlyOrder as $s)
                            <a href="{{ request()->fullUrlWithQuery(['status' => $s]) }}"
                               class="d-flex justify-content-between align-items-center py-1 text-decoration-none small text-dark">
                                <span>
                                    <span class="legend-dot" data-status="{{ $s }}"></span>
                                    {{ $labelStatus($s) }}
                                </span>
                                <span class="fw-semibold">{{ (int)($donutMonthly[$s] ?? 0) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Aktivitas Feed --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Aktivitas Terbaru</h5>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <ul class="list-group list-group-flush small">
                            @forelse($activityFeed as $act)
                                <li class="list-group-item d-flex align-items-start gap-2">
                                    <div class="text-primary mt-1">
                                        <i class="{{ $act['icon'] ?? 'bi bi-clock' }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ e($act['title'] ?? 'Aktivitas') }}</div>
                                        @if (!empty($act['desc']))
                                            <div class="text-muted">{{ e($act['desc']) }}</div>
                                        @endif
                                        <div class="text-muted">{{ e($act['time_text'] ?? '—') }}</div>
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

    </div>
</section>

<style>
    .legend-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 8px;
        background: #999;
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ==== Chart Monthly ====
    const elMonthly = document.querySelector('#reportsChartMonthlyAdmin');
    if (elMonthly && window.ApexCharts) {
        const LABELS = @json($chartMonthly['labels'] ?? []);
        const SERIES_AVG = @json($chartMonthly['seriesAvg'] ?? []);
        const SERIES_CNT = @json($chartMonthly['seriesCount'] ?? []);

        const opts = {
            chart: { type: 'line', height: 320, toolbar: { show: false } },
            stroke: { width: [3, 0] },
            series: [
                { name: 'Avg Progress', type: 'line', data: SERIES_AVG },
                { name: 'Jumlah Tugas', type: 'column', data: SERIES_CNT },
            ],
            xaxis: { categories: LABELS },
            dataLabels: { enabled: false },
            yaxis: [
                { title: { text: 'Progress (%)' }, max: 100, min: 0 },
                { opposite: true, title: { text: 'Tugas (qty)' } }
            ],
            tooltip: { shared: true, intersect: false },
            legend: { position: 'top' }
        };
        new ApexCharts(elMonthly, opts).render();
    }

    // ✅ Color Map dot legend manual (DONE: hapus blocked & to_do)
    const COLOR_MAP = {
        done: '#198754',
        in_progress: '#0d6efd',
        pending: '#ffc107',
        verification: '#0dcaf0',
        rework: '#fd7e14',
        delayed: '#6c757d',
        cancelled: '#212529',
    };

    document.querySelectorAll('.legend-dot').forEach(dot => {
        const st = dot.getAttribute('data-status');
        dot.style.background = COLOR_MAP[st] || '#999';
    });

    // ==== Donut GLOBAL ====
    const donutEl = document.getElementById('statusDonutAdmin');
    if (donutEl && window.echarts) {
        const chart = echarts.init(donutEl);

        const dataAll = [
            { value: {{ (int) ($statusCounts['done'] ?? 0) }}, raw: 'done', label: 'Done' },
            { value: {{ (int) ($statusCounts['in_progress'] ?? 0) }}, raw: 'in_progress', label: 'In Progress' },
            { value: {{ (int) ($statusCounts['pending'] ?? 0) }}, raw: 'pending', label: 'Pending' },
            { value: {{ (int) ($statusCounts['verification'] ?? 0) }}, raw: 'verification', label: 'Verification' },
            { value: {{ (int) ($statusCounts['rework'] ?? 0) }}, raw: 'rework', label: 'Rework' },
            { value: {{ (int) ($statusCounts['delayed'] ?? 0) }}, raw: 'delayed', label: 'Delayed' },
            { value: {{ (int) ($statusCounts['cancelled'] ?? 0) }}, raw: 'cancelled', label: 'Cancelled' },
        ];

        chart.setOption({
            tooltip: { trigger: 'item', confine: true },
            legend: { show: false },
            series: [{
                name: 'Status',
                type: 'pie',
                radius: ['45%', '70%'],
                center: ['50%', '50%'],
                label: { show: false },
                labelLine: { show: false },
                data: dataAll.map(x => ({ value: x.value, name: x.label })),
            }]
        });

        chart.on('click', function(params) {
            const rawStatus = dataAll.find(d => d.label === params.name)?.raw;
            if (!rawStatus) return;
            const url = new URL(window.location.href);
            url.searchParams.set('status', rawStatus);
            window.location.href = url.toString();
        });

        window.addEventListener('resize', () => chart.resize());
    }

    // ==== Donut Monthly ====
    const elDonutMonthly = document.getElementById('statusDonutAdminMonthly');
    if (elDonutMonthly && window.echarts) {
        const chart = echarts.init(elDonutMonthly);

        const order = ['done','in_progress','pending','verification','rework','delayed','cancelled'];
        const dataMap = @json($donutMonthly);

        const datas = order.map(k => ({
            value: Number(dataMap[k] || 0),
            name: k.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()),
            raw: k
        }));

        chart.setOption({
            tooltip: { trigger: 'item', confine: true },
            legend: { show: false },
            series: [{
                name: 'Status (1 Bulan)',
                type: 'pie',
                radius: ['45%', '70%'],
                center: ['50%', '50%'],
                label: { show: false },
                labelLine: { show: false },
                data: datas.map(x => ({ value: x.value, name: x.name })),
            }]
        });

        chart.on('click', function(params) {
            const rawStatus = datas.find(d => d.name === params.name)?.raw;
            if (!rawStatus) return;
            const url = new URL(window.location.href);
            url.searchParams.set('status', rawStatus);
            window.location.href = url.toString();
        });

        window.addEventListener('resize', () => chart.resize());
    }

});
</script>
@endpush
