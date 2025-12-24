@extends('admin.masterAdmin')

@section('title', 'Rekap Pekerjaan')

@section('content')
    @php
        if (!function_exists('statusBadgeClass')) {
            function statusBadgeClass($s)
            {
                $s = strtolower((string) $s);
                return match ($s) {
                    'pending' => 'bg-warning text-dark',
                    'in_progress' => 'bg-primary',
                    'verification' => 'bg-info text-dark',
                    'rework' => 'bg-danger',
                    'delayed' => 'bg-secondary',
                    'cancelled' => 'bg-dark',
                    'done' => 'bg-success',
                    default => 'bg-light text-dark border',
                };
            }
        }

        // ✅ ambil URL dari text (detail/result/etc)
        if (!function_exists('extractFirstUrl')) {
            function extractFirstUrl(?string $text): ?string
            {
                if (!$text) return null;
                if (preg_match('/https?:\/\/[^\s"]+/i', $text, $m)) {
                    return $m[0];
                }
                return null;
            }
        }
    @endphp

    <div class="pagetitle">
        <h1>Rekap Pekerjaan</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Rekap Pekerjaan</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Filter Rekap Pekerjaan</h5>

                <form method="GET" action="{{ route('admin.laporan-bulanan.index') }}"
                    class="row g-2 mb-3 align-items-end">

                    {{-- ✅ Filter Bulan --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Bulan</label>
                        <select class="form-select" name="month">
                            @php $currM = (int) ($filters['month'] ?? date('n')); @endphp
                            <option value="" disabled {{ empty($filters['month']) ? 'selected' : '' }}>Pilih Bulan</option>
                            @foreach (range(1, 12) as $m)
                                @php
                                    $mName = \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F');
                                @endphp
                                <option value="{{ $m }}" {{ $currM == $m ? 'selected' : '' }}>
                                    {{ $mName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ✅ Filter Tahun (2020 - 2050) --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Tahun</label>
                        <select class="form-select" name="year">
                            @php
                                $currY = (int) ($filters['year'] ?? date('Y'));
                                $startY = 2020;
                                $endY   = 2050;
                            @endphp
                            @foreach (range($endY, $startY) as $y)
                                <option value="{{ $y }}" {{ $currY == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ✅ Filter User --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Teams</label>
                        <select class="form-select" name="user_id">
                            <option value="">Semua Teams</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}"
                                    {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ✅ Filter Divisi --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Divisi</label>
                        <select class="form-select" name="division">
                            <option value="">Semua Divisi</option>
                            <option value="teknik" {{ ($filters['division'] ?? '') == 'teknik' ? 'selected' : '' }}>Teknik</option>
                            <option value="digital" {{ ($filters['division'] ?? '') == 'digital' ? 'selected' : '' }}>Digital</option>
                            <option value="customer service"
                                {{ ($filters['division'] ?? '') == 'customer service' ? 'selected' : '' }}>
                                Customer Service
                            </option>
                        </select>
                    </div>

                    {{-- ✅ Filter Status --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua Status</option>
                            @foreach (['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'] as $s)
                                <option value="{{ $s }}" {{ ($filters['status'] ?? '') == $s ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ✅ Tombol --}}
                    <div class="col-12 d-flex flex-wrap gap-2 mt-3">
                        <button class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Terapkan
                        </button>

                        <a href="{{ route('admin.laporan-bulanan.index') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>

                        @php
                            $q = request()->query();
                            unset($q['page']);
                        @endphp

                        <a class="btn btn-danger" href="{{ route('admin.laporan-bulanan.export.pdf', $q) }}"
                            target="_blank" rel="noopener">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                    </div>
                </form>

                {{-- ✅ TABLE --}}
                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr class="text-nowrap">
                                <th style="width:56px;">#</th>
                                <th style="min-width:130px;">Tanggal</th>
                                <th style="min-width:110px;">Jam</th>
                                <th style="min-width:200px;">Judul Task</th>
                                <th style="min-width:160px;">Nama</th>
                                <th>Divisi</th>
                                <th style="min-width:120px;">Progress</th>
                                <th>Status</th>
                                <th style="min-width:120px;">Laporan</th>
                                <th style="min-width:120px;">Bukti</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($rows as $i => $t)
                                @php
                                    $no = method_exists($rows, 'firstItem') ? $rows->firstItem() + $i : $i + 1;

                                    $tgl = optional($t->schedule_date)->format('d/m/Y') ?? '—';

                                    $jam =
                                        ($t->start_time ?? null) && ($t->end_time ?? null)
                                            ? $t->start_time . ' - ' . $t->end_time
                                            : ($t->start_time ?? '—');

                                    $judul = $t->judul ?? '-';
                                    $nama = $t->jobdesk?->user?->name ?? '—';
                                    $div = $t->jobdesk?->division ?? ($t->jobdesk?->user?->division ?? '—');

                                    $prog = max(0, min(100, (int) ($t->progress ?? 0)));
                                    $st = strtolower($t->status ?? '');

                                    $hasil = $t->result ?: '-';
                                    $kendala = $t->shortcoming ?: '-';
                                    $detail = $t->detail ?: '-';

                                    // ✅ collapse ID unik
                                    $collapseId = 'laporan-bulanan-' . $t->id;

                                    // ✅ bukti link fleksibel
                                    $proofLink =
                                        ($t->proof_link ?? null)
                                        ?? extractFirstUrl($t->result ?? null)
                                        ?? extractFirstUrl($t->detail ?? null)
                                        ?? extractFirstUrl($t->shortcoming ?? null);

                                    if ($proofLink && !preg_match('/^https?:\/\//i', $proofLink)) {
                                        $proofLink = null;
                                    }
                                @endphp

                                <tr>
                                    <td class="text-muted">{{ $no }}</td>
                                    <td>{{ $tgl }}</td>
                                    <td class="text-muted small">{{ $jam }}</td>
                                    <td class="fw-semibold text-wrap">{{ $judul }}</td>
                                    <td>{{ $nama }}</td>

                                    <td>
                                        <span class="badge bg-info text-dark">{{ $div }}</span>
                                    </td>

                                    <td>
                                        <div class="progress progress-sm mb-1" style="height:6px;">
                                            <div class="progress-bar" style="width: {{ $prog }}%"></div>
                                        </div>
                                        <div class="small fw-semibold">{{ $prog }}%</div>
                                    </td>

                                    <td>
                                        <span class="badge {{ statusBadgeClass($st) }}">
                                            {{ ucwords(str_replace('_', ' ', $st)) }}
                                        </span>
                                    </td>

                                    {{-- ✅ LAPORAN HIDDEN --}}
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $collapseId }}">
                                            <i class="bi bi-eye me-1"></i> Lihat
                                        </button>

                                        <div class="collapse mt-2" id="{{ $collapseId }}">
                                            <div class="bg-light border rounded p-2 small">
                                                <div><strong>Hasil:</strong> {{ $hasil }}</div>
                                                <div><strong>Kendala:</strong> {{ $kendala }}</div>
                                                <div><strong>Detail:</strong> {{ $detail }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- ✅ BUKTI --}}
                                    <td>
                                        @if($proofLink)
                                            <a href="{{ $proofLink }}" target="_blank" rel="noopener"
                                                class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-link-45deg me-1"></i> Buka
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                        Tidak ada data.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if (method_exists($rows, 'links'))
                    <div class="mt-3">
                        {{ $rows->onEachSide(1)->links() }}
                    </div>
                @endif

            </div>
        </div>
    </section>
@endsection
