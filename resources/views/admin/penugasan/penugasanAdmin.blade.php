{{-- resources/views/admin/penugasan/penugasanAdmin.blade.php --}}
@extends('admin.masterAdmin')

@section('title', 'Penugasan')

@php
    // ✅ 1 sumber kebenaran status (whitelist)
    $STATUS_WHITELIST = ['pending', 'in_progress', 'done', 'delayed', 'cancelled', 'blocked'];

    if (!function_exists('badgeStatusAdmin')) {
        function badgeStatusAdmin($s)
        {
            $s = strtolower((string) $s);
            return match ($s) {
                'done' => 'success',
                'in_progress' => 'info',
                'pending' => 'warning text-dark',
                'delayed' => 'secondary',
                'cancelled' => 'dark',
                'blocked' => 'danger',
                default => 'secondary',
            };
        }
    }

    if (!function_exists('statusLabelAdmin')) {
        function statusLabelAdmin($s)
        {
            $s = strtolower((string) $s);
            return match ($s) {
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                'done' => 'Done',
                'delayed' => 'Delayed',
                'cancelled' => 'Cancelled',
                'blocked' => 'Blocked',
                default => ucwords(str_replace('_', ' ', $s)),
            };
        }
    }

    if (!function_exists('progressByStatus')) {
        function progressByStatus($s)
        {
            $s = strtolower((string) $s);
            return match ($s) {
                'done' => 100,
                'in_progress' => 50,
                'delayed' => 50,
                'pending' => 0,
                'cancelled' => 0,
                'blocked' => 0,
                default => 0,
            };
        }
    }

    // ✅ Helper link bukti aman (auto add https:// kalau belum ada)
    if (!function_exists('safeProofLink')) {
        function safeProofLink($url)
        {
            $url = trim((string) $url);
            if ($url === '') {
                return null;
            }

            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://' . $url;
            }

            return $url;
        }
    }

    // ✅ jika controller kirim $statuses, tetap kita filter ke whitelist biar aman
    $statusesForUi = collect($statuses ?? $STATUS_WHITELIST)
        ->map(fn($x) => strtolower((string) $x))
        ->filter(fn($x) => in_array($x, $STATUS_WHITELIST, true))
        ->unique()
        ->values()
        ->all();

    // ✅ filter status dari request juga divalidasi ringan (biar UI tidak kacau)
    $reqStatus = strtolower((string) request('status'));
    if (!in_array($reqStatus, $STATUS_WHITELIST, true)) {
        $reqStatus = '';
    }
@endphp

@section('content')
    <div class="pagetitle">
        <h1>Penugasan</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.admin') }}">Home</a></li>
                <li class="breadcrumb-item">Penjadwalan</li>
                <li class="breadcrumb-item active">Penugasan</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row g-3">

            {{-- Alerts --}}
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- KPI --}}
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card info-card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Diberikan</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-clipboard-check"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6 class="mb-0">{{ $stats['assigned_today'] ?? 0 }}</h6>
                                        <span class="text-muted small">Total penugasan</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card info-card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Pending</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6 class="mb-0">{{ $stats['pending_today'] ?? 0 }}</h6>
                                        <span class="text-muted small">Total belum dikerjakan</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card info-card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Selesai</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6 class="mb-0">{{ $stats['done_today'] ?? 0 }}</h6>
                                        <span class="text-muted small">Total tugas selesai</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FILTER --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-1">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filter</h5>
                        <form method="GET" class="row gy-2 gx-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label small text-muted mb-1">Dari</label>
                                <input type="date" class="form-control" name="from" value="{{ request('from') }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small text-muted mb-1">Sampai</label>
                                <input type="date" class="form-control" name="to" value="{{ request('to') }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small text-muted mb-1">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">Semua</option>
                                    @foreach ($statusesForUi as $st)
                                        <option value="{{ $st }}" @selected($reqStatus === $st)>
                                            {{ statusLabelAdmin($st) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small text-muted mb-1">Teams</label>
                                <select class="form-select" name="employee_id">
                                    <option value="">Semua</option>
                                    @foreach ($employees ?? [] as $emp)
                                        <option value="{{ $emp->id }}" @selected((string) request('employee_id') === (string) $emp->id)>
                                            {{ $emp->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-9 mt-2">
                                <input type="text" class="form-control" name="q"
                                    placeholder="Cari judul / deskripsi..." value="{{ request('q') }}">
                            </div>

                            <div class="col-12 col-md-3 mt-2 d-flex gap-2">
                                <button class="btn btn-primary flex-grow-1">
                                    <i class="bi bi-funnel me-1"></i> Terapkan
                                </button>
                                <a class="btn btn-outline-secondary flex-shrink-0"
                                    href="{{ route('admin.penugasan.index') }}">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- LIST --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">

                        <div class="d-flex justify-content-between flex-wrap align-items-center mb-2 gap-2">
                            <h5 class="card-title mb-0">Daftar Penugasan</h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Penugasan
                            </button>
                        </div>

                        <small class="text-muted">
                            Klik <strong>Edit</strong> untuk mengubah status & laporan.
                        </small>

                        <div class="table-responsive mt-3">
                            <table class="table table-borderless align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th style="min-width:240px;">Pekerjaan</th>
                                        <th style="min-width:150px;">Teams</th>
                                        <th style="width:130px;">Deadline</th>
                                        <th style="width:120px;">Status</th>
                                        <th style="width:200px;">Progress</th>
                                        <th style="width:180px;">Link Bukti</th>
                                        <th class="text-end" style="width:200px;">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($assignments as $i => $as)
                                        @php
                                            $rawSt = strtolower((string) ($as->status ?? 'pending'));
                                            $st = in_array($rawSt, $STATUS_WHITELIST, true) ? $rawSt : 'pending';

                                            $p = progressByStatus($st);
                                            $badge = badgeStatusAdmin($st);

                                            $detailText = $as->detail ?? ($as->description ?? null);

                                            $collapseId = 'as-detail-' . $as->id;
                                            $rowNum = method_exists($assignments, 'firstItem')
                                                ? $assignments->firstItem() + $i
                                                : $i + 1;

                                            $name = $as->employee->name ?? ($as->employee_name ?? '—');

                                            $proofLink = safeProofLink($as->proof_link);
                                        @endphp

                                        <tr>
                                            <td class="text-muted small">{{ $rowNum }}</td>

                                            <td>
                                                <div class="fw-semibold">{{ $as->title }}</div>

                                                <button class="btn btn-sm btn-outline-primary py-0 px-2 mt-2"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $collapseId }}">
                                                    <i class="bi bi-eye me-1"></i> Detail
                                                </button>

                                                <div class="collapse mt-2" id="{{ $collapseId }}">
                                                    <div class="card card-body bg-light border-0 p-2 mb-0 small">
                                                        <div><strong>Hasil:</strong> {{ $as->result ?: '-' }}</div>
                                                        <div><strong>Kendala:</strong> {{ $as->shortcoming ?: '-' }}</div>
                                                        <div><strong>Detail:</strong> {{ $detailText ?: '-' }}</div>
                                                        <div class="mt-1"><strong>Link:</strong>
                                                            @if ($proofLink)
                                                                <a href="{{ $proofLink }}" target="_blank"
                                                                    rel="noopener">
                                                                    {{ $proofLink }}
                                                                </a>
                                                            @else
                                                                -
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-circle text-secondary"></i>
                                                    <span>{{ $name }}</span>
                                                </div>
                                            </td>

                                            <td class="small">
                                                {{ $as->deadline ? \Carbon\Carbon::parse($as->deadline)->format('d M Y') : '-' }}
                                            </td>

                                            <td>
                                                <span class="badge bg-{{ $badge }}">
                                                    {{ statusLabelAdmin($st) }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height:.6rem;">
                                                        <div class="progress-bar" style="width: {{ $p }}%;">
                                                        </div>
                                                    </div>
                                                    <span class="small text-muted">{{ $p }}%</span>
                                                </div>
                                            </td>

                                            <td>
                                                @if ($proofLink)
                                                    <a href="{{ $proofLink }}" target="_blank" rel="noopener"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-link-45deg me-1"></i> Open
                                                    </a>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>

                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalSubmit-{{ $as->id }}">
                                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                                    </button>

                                                    <form method="POST"
                                                        action="{{ route('admin.penugasan.destroy', $as->id) }}"
                                                        onsubmit="return confirm('Yakin hapus penugasan ini?')"
                                                        class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash me-1"></i> Hapus
                                                        </button>
                                                    </form>
                                                </div>

                                                {{-- MODAL SUBMIT --}}
                                                <div class="modal fade text-start" id="modalSubmit-{{ $as->id }}"
                                                    tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <form action="{{ route('admin.penugasan.update', $as->id) }}"
                                                            method="POST" class="modal-content penugasan-form">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Penugasan</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <div class="row g-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Judul</label>
                                                                        <input type="text" class="form-control"
                                                                            name="title" value="{{ $as->title }}"
                                                                            required>
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Deadline</label>
                                                                        <input type="date" class="form-control"
                                                                            name="deadline"
                                                                            value="{{ $as->deadline ? \Carbon\Carbon::parse($as->deadline)->format('Y-m-d') : '' }}"
                                                                            required>
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Teams</label>
                                                                        <select class="form-select" name="employee_id"
                                                                            required>
                                                                            @foreach ($employees ?? [] as $emp)
                                                                                <option value="{{ $emp->id }}"
                                                                                    @selected((int) $as->employee_id === (int) $emp->id)>
                                                                                    {{ $emp->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label required">Status</label>
                                                                        <select name="status"
                                                                            class="form-select js-status" required>
                                                                            @foreach ($STATUS_WHITELIST as $opt)
                                                                                <option value="{{ $opt }}"
                                                                                    @selected($st === $opt)>
                                                                                    {{ statusLabelAdmin($opt) }}
                                                                                    ({{ progressByStatus($opt) }}%)
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                        <div class="form-text">
                                                                            Jika pilih <strong>Done</strong> maka
                                                                            <strong>Link Bukti</strong> wajib.
                                                                        </div>
                                                                    </div>

                                                                    <input type="hidden" name="progress"
                                                                        class="js-progress"
                                                                        value="{{ progressByStatus($st) }}">

                                                                    <div class="col-12">
                                                                        <label class="form-label">Hasil / Result</label>
                                                                        <textarea name="result" class="form-control" rows="2">{{ old('result', $as->result) }}</textarea>
                                                                    </div>

                                                                    <div class="col-12">
                                                                        <label class="form-label">Kendala /
                                                                            Shortcoming</label>
                                                                        <textarea name="shortcoming" class="form-control" rows="2">{{ old('shortcoming', $as->shortcoming) }}</textarea>
                                                                    </div>

                                                                    <div class="col-12">
                                                                        <label class="form-label">Detail</label>
                                                                        <textarea name="detail" class="form-control" rows="2">{{ old('detail', $detailText ?: '') }}</textarea>
                                                                    </div>

                                                                    <div class="col-12">
                                                                        <label class="form-label">Link Bukti</label>
                                                                        <input type="url" name="proof_link"
                                                                            class="form-control js-proof"
                                                                            placeholder="https://..."
                                                                            value="{{ $as->proof_link }}">
                                                                        <div
                                                                            class="small text-danger mt-1 d-none js-proof-hint">
                                                                            <i class="bi bi-exclamation-circle me-1"></i>
                                                                            Link Bukti wajib diisi jika status Done.
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit"
                                                                    class="btn btn-success">Simpan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                                Belum ada penugasan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        @if (method_exists($assignments, 'links'))
                            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                <div class="small text-muted">
                                    Menampilkan
                                    <span>{{ $assignments->total() ? $assignments->firstItem() : 0 }}</span>–<span>{{ $assignments->lastItem() }}</span>
                                    dari <span>{{ $assignments->total() }}</span>
                                </div>
                                {{ $assignments->onEachSide(1)->appends(request()->query())->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ✅ MODAL CREATE (STATUS DIHAPUS) --}}
    <div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('admin.penugasan.store') }}" method="POST" class="modal-content">
                @csrf

                {{-- ✅ DEFAULT STATUS --}}
                <input type="hidden" name="status" value="pending">

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Penugasan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Judul</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Deadline</label>
                            <input type="date" class="form-control" name="deadline" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Teams</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">-- Pilih Teams --</option>
                                @foreach ($employees ?? [] as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Detail</label>
                            <input type="text" class="form-control" name="description"
                                placeholder="Deskripsi singkat...">
                        </div>

                        <div class="col-12">
                            <small class="text-muted">
                                <!-- Status otomatis <strong>Pending</strong> saat penugasan dibuat. -->
                            </small>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- ✅ Validasi Done => proof_link wajib + auto progress --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const mapProgress = (status) => {
                status = (status || '').toLowerCase();
                if (status === 'done') return 100;
                if (status === 'in_progress') return 50;
                if (status === 'delayed') return 50;
                if (status === 'cancelled') return 0;
                if (status === 'blocked') return 0;
                return 0;
            };

            document.querySelectorAll('form.penugasan-form').forEach(form => {
                const st = form.querySelector('.js-status');
                const proof = form.querySelector('.js-proof');
                const hint = form.querySelector('.js-proof-hint');
                const prog = form.querySelector('.js-progress');

                function sync() {
                    const val = (st?.value || '').toLowerCase();
                    const isDone = val === 'done';

                    if (isDone) {
                        proof?.setAttribute('required', 'required');
                        hint?.classList.remove('d-none');
                    } else {
                        proof?.removeAttribute('required');
                        hint?.classList.add('d-none');
                    }

                    if (prog) prog.value = mapProgress(val);
                }

                st?.addEventListener('change', sync);
                sync();
            });
        });
    </script>
@endsection
