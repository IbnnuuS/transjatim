@extends('user.masterUser')

@section('content')
    @php
        /**
         * ✅ STATUS FULL (SAMA DENGAN CONTROLLER USER)
         */
        $STATUS_WHITELIST = ['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'];

        if (!function_exists('badgeStatusUser')) {
            function badgeStatusUser($s)
            {
                $s = strtolower((string) $s);
                return match ($s) {
                    'done' => 'success',
                    'in_progress' => 'info',
                    'pending' => 'warning text-dark',
                    'verification' => 'primary',
                    'rework' => 'danger',
                    'delayed' => 'secondary',
                    'cancelled' => 'dark',
                    default => 'secondary',
                };
            }
        }

        if (!function_exists('statusLabelUser')) {
            function statusLabelUser($s)
            {
                $s = strtolower((string) $s);
                return match ($s) {
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'verification' => 'Verification',
                    'rework' => 'Rework',
                    'delayed' => 'Delayed',
                    'cancelled' => 'Cancelled',
                    'done' => 'Done',
                    default => ucwords(str_replace('_', ' ', $s)),
                };
            }
        }

        /**
         * ✅ Progress berdasarkan status (SAMA LOGIC DENGAN CONTROLLER)
         */
        if (!function_exists('progressByStatusUser')) {
            function progressByStatusUser($s)
            {
                $s = strtolower((string) $s);
                return match ($s) {
                    'pending' => 0,
                    'in_progress' => 50,
                    'verification' => 80,
                    'rework' => 50,
                    'delayed' => 50,
                    'cancelled' => 50,
                    'done' => 100,
                    default => 0,
                };
            }
        }

        $me = Auth::user();
        $defaultDivision = ucwords(strtolower($me->division ?? ''));

        // ✅ filter status dari request harus valid
        $reqStatus = strtolower((string) request('status'));
        if (!in_array($reqStatus, $STATUS_WHITELIST, true)) {
            $reqStatus = '';
        }
    @endphp

    <div class="pagetitle">
        <h1>Penugasan Saya</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.user') }}">Home</a></li>
                <li class="breadcrumb-item">Teams</li>
                <li class="breadcrumb-item active">Penugasan</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row g-3">

            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-1"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>

            {{-- ===== STAT ===== --}}
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card info-card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Total Penugasan</h5>
                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="bi bi-clipboard-check"></i>
                                    </div>
                                    <div class="ps-3">
                                        <h6 class="mb-0">{{ $stats['today_total'] ?? 0 }}</h6>
                                        <span class="text-muted small">Total semua tugas</span>
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
                                        <h6 class="mb-0">{{ $stats['today_pending'] ?? 0 }}</h6>
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
                                        <h6 class="mb-0">{{ $stats['today_done'] ?? 0 }}</h6>
                                        <span class="text-muted small">Total tugas selesai</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== FILTER ===== --}}
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
                                    @foreach ($STATUS_WHITELIST as $st)
                                        <option value="{{ $st }}" @selected($reqStatus === $st)>
                                            {{ statusLabelUser($st) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small text-muted mb-1">Cari</label>
                                <input type="text" class="form-control" name="q" placeholder="Judul / deskripsi..."
                                    value="{{ request('q') }}">
                            </div>
                            <div class="col-12 mt-3 d-flex flex-wrap gap-2">
                                <button class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i> Terapkan
                                </button>
                                <a class="btn btn-outline-secondary" href="{{ route('penugasan.user') }}">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ===== LIST ===== --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between flex-wrap align-items-center mb-2">
                            <h5 class="card-title mb-0">Daftar Penugasan</h5>
                            <small class="text-muted">Klik <strong>Edit / Kerjakan</strong> untuk mengubah status &
                                laporan.</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th style="min-width:240px;">Pekerjaan</th>
                                        <th style="width:130px;">Deadline</th>
                                        <th style="width:120px;">Status</th>
                                        <th style="width:200px;">Progress</th>
                                        <th class="text-end" style="width:170px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignments as $i => $as)
                                        @php
                                            $st = strtolower((string) ($as->status ?? 'pending'));
                                            if (!in_array($st, $STATUS_WHITELIST, true)) {
                                                $st = 'pending';
                                            }

                                            $p = progressByStatusUser($st);
                                            $badge = badgeStatusUser($st);

                                            $detailText = $as->detail ?? ($as->description ?? null);
                                            $collapseId = 'as-detail-' . $as->id;
                                        @endphp

                                        <tr>
                                            <td class="text-muted small">
                                                {{ method_exists($assignments, 'firstItem') ? $assignments->firstItem() + $i : $i + 1 }}
                                            </td>

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
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="small">
                                                {{ $as->deadline ? $as->deadline->format('d M Y') : '-' }}
                                            </td>

                                            <td>
                                                <span class="badge bg-{{ $badge }}">
                                                    {{ statusLabelUser($st) }}
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

                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalSubmit-{{ $as->id }}">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit / Kerjakan
                                                </button>

                                                {{-- ✅ MODAL FULL STATUS --}}
                                                <div class="modal fade text-start" id="modalSubmit-{{ $as->id }}"
                                                    tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <form action="{{ route('penugasan.user.submit', $as->id) }}"
                                                            method="POST" class="modal-content penugasan-form">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Penugasan</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label>Judul</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $as->title }}" disabled>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label required">Status</label>
                                                                    <select name="status" class="form-select js-status"
                                                                        required>
                                                                        @foreach ($STATUS_WHITELIST as $opt)
                                                                            <option value="{{ $opt }}"
                                                                                @selected($st === $opt)>
                                                                                {{ statusLabelUser($opt) }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <div class="form-text">
                                                                        Jika pilih <strong>Done</strong> maka <strong>Link
                                                                            Bukti</strong> wajib.
                                                                    </div>
                                                                </div>

                                                                <div class="mb-3">
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

                                                                <div class="mb-3">
                                                                    <label class="form-label">Hasil / Result</label>
                                                                    <textarea name="result" class="form-control" rows="2">{{ old('result', $as->result) }}</textarea>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Kendala / Shortcoming</label>
                                                                    <textarea name="shortcoming" class="form-control" rows="2">{{ old('shortcoming', $as->shortcoming) }}</textarea>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Detail</label>
                                                                    <textarea name="detail" class="form-control" rows="2">{{ old('detail', $detailText ?: '') }}</textarea>
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
                                                {{-- /MODAL --}}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                                Belum ada penugasan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form.penugasan-form').forEach(form => {
                const st = form.querySelector('.js-status');
                const proof = form.querySelector('.js-proof');
                const hint = form.querySelector('.js-proof-hint');

                function sync() {
                    const isDone = (st?.value || '').toLowerCase() === 'done';
                    if (isDone) {
                        proof?.setAttribute('required', 'required');
                        hint?.classList.remove('d-none');
                    } else {
                        proof?.removeAttribute('required');
                        hint?.classList.add('d-none');
                    }
                }

                st?.addEventListener('change', sync);
                sync();
            });
        });
    </script>
@endsection
