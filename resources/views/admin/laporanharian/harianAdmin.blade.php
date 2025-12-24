@extends('admin.masterAdmin')

@section('title', 'Laporan Harian')

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

        $statusOptions = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'verification' => 'Verification',
            'rework' => 'Rework',
            'delayed' => 'Delayed',
            'cancelled' => 'Cancelled',
            'done' => 'Done',
        ];
    @endphp

    <div class="pagetitle">
        <h1 class="mb-1">Laporan Harian</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item">Admin</li>
                <li class="breadcrumb-item active" aria-current="page">Laporan Harian</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <h5 class="card-title mb-0">Data Jobdesk (Harian)</h5>
                            <div class="text-muted small">
                                Total: <strong>{{ number_format($summary['total'] ?? 0) }}</strong>
                            </div>
                        </div>

                        {{-- Filter --}}
                        <form method="GET" action="{{ route('admin.laporan-harian.index') }}" class="mt-1">
                            <div class="row g-2 g-md-3 align-items-end">
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">Cari</label>
                                    <input type="text" name="q" class="form-control"
                                        placeholder="Judul / nama / divisi" value="{{ $filters['q'] ?? '' }}">
                                </div>

                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">Teams</label>
                                    @php $uid = (string)($filters['user_id'] ?? ''); @endphp
                                    <select name="user_id" class="form-select">
                                        <option value="">Semua</option>
                                        @foreach ($users as $u)
                                            <option value="{{ $u->id }}" @selected($uid === (string) $u->id)>
                                                {{ $u->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">Divisi</label>
                                    @php $div = $filters['division'] ?? ''; @endphp
                                    <select name="division" class="form-select">
                                        <option value="">Semua</option>
                                        <option value="Teknik" @selected($div === 'Teknik')>Teknik</option>
                                        <option value="Digital" @selected($div === 'Digital')>Digital</option>
                                        <option value="Customer Service" @selected($div === 'Customer Service')>
                                            Customer Service
                                        </option>
                                    </select>
                                </div>

                                {{-- ✅ STATUS LENGKAP --}}
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">Status</label>
                                    @php $st = $filters['status'] ?? ''; @endphp
                                    <select name="status" class="form-select">
                                        <option value="">Semua</option>
                                        @foreach ($statusOptions as $val => $label)
                                            <option value="{{ $val }}" @selected($st === $val)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">Dari</label>
                                    <input type="date" name="date_from" class="form-control"
                                        value="{{ $filters['date_from'] ?? '' }}">
                                </div>

                                <div class="col-12 col-sm-6 col-md-3">
                                    <label class="form-label small">Sampai</label>
                                    <input type="date" name="date_to" class="form-control"
                                        value="{{ $filters['date_to'] ?? '' }}">
                                </div>

                                <div class="col-12 col-md-6 d-flex flex-wrap gap-2 mt-1">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="bi bi-funnel me-1"></i> Terapkan
                                    </button>

                                    <a href="{{ route('admin.laporan-harian.index') }}"
                                        class="btn btn-outline-secondary flex-grow-1 flex-md-grow-0">
                                        Reset
                                    </a>

                                    @php
                                        $q = request()->query();
                                        unset($q['page']);
                                    @endphp

                                    <a class="btn btn-danger flex-grow-1 flex-md-grow-0"
                                        href="{{ route('admin.laporan-harian.export.pdf', $q) }}" target="_blank"
                                        rel="noopener">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                                    </a>
                                </div>
                            </div>
                        </form>

                        {{-- ✅ TABLE --}}
                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle">
                                <thead class="position-sticky top-0 bg-white border-bottom" style="z-index:1;">
                                    <tr class="text-nowrap">
                                        <th style="width:56px">#</th>
                                        <th style="min-width:140px;">Tanggal & Jam</th>
                                        <th style="min-width:200px;">Judul Task</th>
                                        <th style="min-width:160px;">Nama</th>
                                        <th>Divisi</th>
                                        <th style="min-width:170px;">Progress</th>
                                        <th>Status</th>

                                        {{-- ✅ NEW --}}
                                        <th style="min-width:220px;">Laporan</th>
                                        <th style="min-width:120px;">Bukti</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($rows as $i => $t)
                                        @php
                                            $no = method_exists($rows, 'firstItem') ? $rows->firstItem() + $i : $i + 1;
                                            $tgl = optional($t->schedule_date)->format('d/m/Y') ?? '—';
                                            $time =
                                                ($t->start_time ?? null) && ($t->end_time ?? null)
                                                    ? $t->start_time . ' - ' . $t->end_time
                                                    : $t->start_time ?? '—';

                                            $judul = $t->judul ?? '(Tanpa judul)';
                                            $nama = $t->jobdesk?->user?->name ?? '—';
                                            $divisi = $t->jobdesk?->division ?? ($t->jobdesk?->user?->division ?? '—');
                                            $prog = max(0, min(100, (int) ($t->progress ?? 0)));
                                            $stRow = strtolower($t->status ?? '');

                                            $collapseId = 'laporan-' . $t->id;

                                            // ✅ bukti link fleksibel
                                            $proofLink =
                                                ($t->proof_link ?? null)
                                                ?? extractFirstUrl($t->result ?? null)
                                                ?? extractFirstUrl($t->detail ?? null)
                                                ?? extractFirstUrl($t->shortcoming ?? null);

                                            if ($proofLink && !preg_match('/^https?:\/\//i', $proofLink)) {
                                                $proofLink = null;
                                            }

                                            $hasil = $t->result ?: '-';
                                            $kendala = $t->shortcoming ?: '-';
                                            $detail = $t->detail ?: '-';
                                        @endphp

                                        <tr>
                                            <td class="text-muted">{{ $no }}</td>

                                            <td>
                                                <div class="small">{{ $tgl }}</div>
                                                <div class="text-muted small">{{ $time }}</div>
                                            </td>

                                            <td class="fw-semibold text-wrap">{{ $judul }}</td>

                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-circle fs-5 text-secondary"></i>
                                                    <span class="text-wrap">{{ $nama }}</span>
                                                </div>
                                            </td>

                                            <td>
                                                <span class="badge bg-info text-dark text-wrap">{{ $divisi }}</span>
                                            </td>

                                            <td>
                                                <div class="progress progress-sm mb-1" style="height:6px;">
                                                    <div class="progress-bar" style="width: {{ $prog }}%"></div>
                                                </div>
                                                <div class="small fw-semibold">{{ $prog }}%</div>
                                            </td>

                                            <td>
                                                <span class="badge {{ statusBadgeClass($stRow) }}">
                                                    {{ ucwords(str_replace('_',' ', $stRow)) }}
                                                </span>
                                            </td>

                                            {{-- ✅ LAPORAN (HASIL / KENDALA / DETAIL) --}}
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

                                            {{-- ✅ BUKTI LINK --}}
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
                                            <td colspan="9" class="text-center text-muted py-4">
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
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                <div class="small text-muted">
                                    Menampilkan
                                    <span>{{ $rows->total() ? $rows->firstItem() : 0 }}</span>–<span>{{ $rows->lastItem() }}</span>
                                    dari <span>{{ $rows->total() }}</span>
                                </div>
                                {{ $rows->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
