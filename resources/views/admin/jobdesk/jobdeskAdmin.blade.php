@extends('admin.masterAdmin')

@section('title', 'Monitoring Proyek - Admin')

@section('content')
@php
    if (!function_exists('statusBadgeClass')) {
        function statusBadgeClass($s)
        {
            $s = strtolower((string) $s);
            return match ($s) {
                'to_do' => 'bg-light text-dark border',
                'pending' => 'bg-warning text-dark',
                'in_progress' => 'bg-primary',
                'verification' => 'bg-info',
                'rework' => 'bg-danger',
                'delayed' => 'bg-secondary',
                'cancelled' => 'bg-dark',
                'done' => 'bg-success',
                default => 'bg-light text-dark border',
            };
        }
    }
@endphp

<style>
    /* ✅ table responsive rapih tidak melebar */
    .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    #tableJobdeskAdmin {
        width: 100% !important;
        table-layout: fixed;
    }

    #tableJobdeskAdmin th,
    #tableJobdeskAdmin td {
        vertical-align: middle;
        word-break: break-word;
    }

    /* ✅ text panjang tidak bikin tabel melebar */
    .text-ellipsis {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        width: 100%;
    }

    .col-judul { max-width: 200px; }
    .col-nama { max-width: 140px; }

    /* ✅ dropdown selalu tampil di atas table */
    .dropdown-menu {
        z-index: 99999 !important;
    }
</style>

@php
    $adminStatuses = [
        'pending',
        'in_progress',
        'verification',
        'rework',
        'delayed',
        'cancelled',
        'done',
    ];
@endphp

<div class="pagetitle">
    <h1 class="mb-1">Monitoring Proyek</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item active" aria-current="page">Monitoring Proyek</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <h5 class="card-title mb-0">Daftar Jobdesk</h5>
                        <div class="text-muted small">
                            Total: <span id="metricJobsTotal">{{ number_format($summary['total'] ?? 0) }}</span>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <form id="jobdeskFilter" method="GET" action="{{ route('admin.jobdesk.index') }}" class="mt-2">
                        <div class="row g-2 g-md-3 align-items-end">
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small text-muted">Cari</label>
                                <input type="text" name="q" class="form-control" placeholder="Judul / divisi"
                                    value="{{ $filters['q'] ?? '' }}" />
                            </div>

                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small text-muted">Teams</label>
                                @php $uid = $filters['user_id'] ?? ''; @endphp
                                <select name="user_id" class="form-select">
                                    <option value="">Semua</option>
                                    @foreach ($users ?? [] as $u)
                                        <option value="{{ $u->id }}" @selected((string)$uid === (string)$u->id)>
                                            {{ $u->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small text-muted">Divisi</label>
                                @php $div = $filters['division'] ?? ''; @endphp
                                <select name="division" class="form-select">
                                    <option value="">Semua</option>
                                    <option value="Teknik" @selected($div === 'Teknik')>Teknik</option>
                                    <option value="Digital" @selected($div === 'Digital')>Digital</option>
                                    <option value="Customer Service" @selected($div === 'Customer Service')>Customer Service</option>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small text-muted">Status</label>
                                @php $st = $filters['status'] ?? ''; @endphp
                                <select name="status" class="form-select">
                                    <option value="">Semua</option>
                                    @foreach($adminStatuses as $opt)
                                        <option value="{{ $opt }}" @selected($st === $opt)>
                                            {{ ucwords(str_replace('_',' ',$opt)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small text-muted">Tanggal</label>
                                <input type="date" name="date" class="form-control" value="{{ $filters['date'] ?? '' }}">
                            </div>

                            <div class="col-12 col-md-9 d-flex flex-wrap gap-2 mt-1">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i> Terapkan
                                </button>
                                <a href="{{ route('admin.jobdesk.index') }}" class="btn btn-outline-secondary">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Bulk --}}
                    <form id="bulkForm" action="{{ route('admin.jobdesk.bulk') }}" method="POST" class="mt-3">
                        @csrf

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="form-check me-2">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                                <label for="checkAll" class="form-check-label small">Pilih semua</label>
                            </div>

                            <select name="action" class="form-select form-select-sm" style="max-width:220px;" required>
                                <option value="" selected>Pilih aksi…</option>
                                @foreach($adminStatuses as $opt)
                                    <option value="status:{{ $opt }}">Ubah ke {{ ucwords(str_replace('_',' ',$opt)) }}</option>
                                @endforeach
                                <option value="delete">Hapus</option>
                            </select>

                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-bolt me-1"></i> Jalankan
                            </button>
                        </div>

                        {{-- Table --}}
                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle" id="tableJobdeskAdmin">
                                <thead class="position-sticky top-0 bg-white border-bottom" style="z-index:1;">
                                    <tr class="text-nowrap">
                                        <th style="width:36px;"></th>
                                        <th style="width:50px;">#</th>
                                        <th style="width:140px;">Tanggal & Jam</th>
                                        <th style="width:200px;">Judul</th>
                                        <th style="width:140px;">Nama</th>
                                        <th style="width:110px;">Divisi</th>
                                        <th style="width:260px;">Progress</th>
                                        <th class="text-end" style="width:140px;">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($rows as $i => $t)
                                        @php
                                            $no = method_exists($rows, 'firstItem') ? $rows->firstItem() + $i : $i+1;
                                            $tgl = optional($t->schedule_date)->format('d/m/Y') ?? '—';
                                            $time = (($t->start_time ?? null) && ($t->end_time ?? null))
                                                ? ($t->start_time.' - '.$t->end_time)
                                                : '—';
                                            $judul = $t->judul ?? '(Tanpa judul)';
                                            $nama = $t->jobdesk?->user?->name ?? '—';
                                            $div = $t->jobdesk?->division ?? ($t->jobdesk?->user?->division ?? '—');
                                            $prog = max(0, min(100, (int)($t->progress ?? 0)));
                                            $stRow = strtolower($t->status ?? '');
                                        @endphp

                                        <tr>
                                            <td>
                                                <input class="form-check-input row-check" type="checkbox" name="ids[]" value="{{ $t->id }}">
                                            </td>
                                            <td class="text-muted">{{ $no }}</td>

                                            <td>
                                                <div class="small">{{ $tgl }}</div>
                                                <div class="text-muted small">{{ $time }}</div>
                                            </td>

                                            <td class="fw-semibold col-judul">
                                                <span class="text-ellipsis">{{ $judul }}</span>
                                            </td>

                                            <td class="col-nama">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-person-circle fs-5 text-secondary"></i>
                                                    <span class="text-ellipsis">{{ $nama }}</span>
                                                </div>
                                            </td>

                                            <td>
                                                <span class="badge bg-info text-dark text-wrap">{{ $div }}</span>
                                            </td>

                                            <td>
                                                <div class="progress progress-sm mb-1" style="height:6px;">
                                                    <div class="progress-bar" style="width: {{ $prog }}%"></div>
                                                </div>

                                                <div class="d-flex flex-wrap align-items-center gap-2 small">
                                                    <span class="fw-semibold">{{ $prog }}%</span>
                                                    <span class="badge {{ statusBadgeClass($stRow) }}">
                                                        {{ $stRow ?: '—' }}
                                                    </span>

                                                    {{-- ✅ DROPUP STATUS (VISIBLE OUT TABLE) --}}
                                                    <div class="dropup">
                                                        <button type="button"
                                                            class="btn btn-light btn-sm dropdown-toggle btn-status-toggle"
                                                            data-bs-toggle="dropdown"
                                                            data-bs-display="static"
                                                            data-bs-boundary="viewport"
                                                            aria-expanded="false">
                                                            Ubah
                                                        </button>

                                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0"
                                                            style="min-width:170px;">
                                                            @foreach($adminStatuses as $opt)
                                                                <li>
                                                                    <button type="button"
                                                                        onclick="submitSingleStatus({{ $t->id }}, '{{ $opt }}')"
                                                                        class="dropdown-item py-2">
                                                                        {{ ucwords(str_replace('_', ' ', $opt)) }}
                                                                    </button>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="text-end">
                                                <div class="btn-group">

                                                    {{-- ✅ LIHAT DETAIL --}}
                                                    <button type="button" class="btn btn-sm btn-outline-primary open-detail"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalDetailJobdesk"
                                                        data-title="{{ $judul }}"
                                                        data-date="{{ $tgl }}"
                                                        data-time="{{ $time }}"
                                                        data-nama="{{ $nama }}"
                                                        data-divisi="{{ $div }}"
                                                        data-progress="{{ $prog }}"
                                                        data-status="{{ $stRow }}"
                                                        data-detail="{{ $t->detail ?? '-' }}"
                                                        data-proof_link="{{ $t->proof_link ?? '-' }}">
                                                        <i class="bi bi-eye"></i>
                                                    </button>

                                                    {{-- ✅ EDIT --}}
                                                    <button type="button" class="btn btn-sm btn-outline-secondary open-edit"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditTask"
                                                        data-id="{{ $t->id }}"
                                                        data-judul="{{ $t->judul }}"
                                                        data-status="{{ $stRow }}"
                                                        data-proof_link="{{ $t->proof_link ?? '' }}"
                                                        data-result="{{ $t->result ?? '' }}"
                                                        data-shortcoming="{{ $t->shortcoming ?? '' }}"
                                                        data-detail="{{ $t->detail ?? '' }}">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>

                                                    {{-- ✅ DELETE --}}
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="submitDelete({{ $t->id }})">
                                                        <i class="bi bi-trash"></i>
                                                    </button>

                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                Belum ada data jobdesk.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                    @if(method_exists($rows,'links'))
                        <div class="mt-2">
                            {{ $rows->onEachSide(1)->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

{{-- ✅ MODAL DETAIL JOBDESK --}}
<div class="modal fade" id="modalDetailJobdesk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Detail Jobdesk</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <strong>Judul:</strong>
                    <div id="dTitle">-</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Tanggal:</strong>
                        <div id="dDate">-</div>
                    </div>
                    <div class="col-md-6">
                        <strong>Jam:</strong>
                        <div id="dTime">-</div>
                    </div>
                    <div class="col-md-6">
                        <strong>Nama:</strong>
                        <div id="dNama">-</div>
                    </div>
                    <div class="col-md-6">
                        <strong>Divisi:</strong>
                        <div id="dDivisi">-</div>
                    </div>
                </div>

                <hr>

                <div class="mb-2">
                    <strong>Progress:</strong>
                    <div id="dProgress">-</div>
                </div>

                <div class="mb-2">
                    <strong>Status:</strong>
                    <div id="dStatus">-</div>
                </div>

                <div class="mb-2">
                    <strong>Detail:</strong>
                    <div id="dDetail">-</div>
                </div>

                <div class="mb-2">
                    <strong>Link Bukti:</strong>
                    <div id="dProofLink">-</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEditTask" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" id="formEditTask" method="POST" action="#">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title mb-0">Edit Task</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">

                    <div class="col-12">
                        <label class="form-label">Judul Pekerjaan</label>
                        <input type="text" name="judul" id="eJudul" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" id="eStatus" class="form-select" required>
                            @foreach($adminStatuses as $opt)
                                <option value="{{ $opt }}">{{ ucwords(str_replace('_', ' ', $opt)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Link Bukti (Wajib jika Done)</label>
                        <input type="url" name="proof_link" id="eProofLink" class="form-control" placeholder="https://...">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Hasil Pekerjaan</label>
                        <textarea name="result" id="eResult" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Kekurangan / Kendala</label>
                        <textarea name="shortcoming" id="eShortcoming" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Detail</label>
                        <textarea name="detail" id="eDetail" class="form-control" rows="3"></textarea>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- Hidden forms --}}
<form id="singleDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<form id="singleStatusForm" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="status" id="singleStatusInput">
</form>

<script>
    function submitDelete(id) {
        if (!confirm('Hapus item ini?')) return;
        const form = document.getElementById('singleDeleteForm');
        const baseUrl = "{{ route('admin.jobdesk.destroy', ':id') }}";
        form.action = baseUrl.replace(':id', id);
        form.submit();
    }

    function submitSingleStatus(id, status) {
        const form = document.getElementById('singleStatusForm');
        const baseUrl = "{{ route('admin.jobdesk.updateStatus', ':id') }}";
        form.action = baseUrl.replace(':id', id);
        document.getElementById('singleStatusInput').value = status;
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {

        // ✅ CHECK ALL
        const bulkForm = document.getElementById('bulkForm');
        const checkAll = document.getElementById('checkAll');

        function getRowChecks() {
            return bulkForm ? bulkForm.querySelectorAll('.row-check') : document.querySelectorAll('.row-check');
        }

        checkAll?.addEventListener('change', function() {
            getRowChecks().forEach(ch => ch.checked = checkAll.checked);
        });

        // ✅ MODAL DETAIL
        const modalDetail = document.getElementById('modalDetailJobdesk');
        modalDetail?.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            if (!btn) return;

            document.getElementById('dTitle').innerText = btn.getAttribute('data-title') || '-';
            document.getElementById('dDate').innerText = btn.getAttribute('data-date') || '-';
            document.getElementById('dTime').innerText = btn.getAttribute('data-time') || '-';
            document.getElementById('dNama').innerText = btn.getAttribute('data-nama') || '-';
            document.getElementById('dDivisi').innerText = btn.getAttribute('data-divisi') || '-';
            document.getElementById('dProgress').innerText = (btn.getAttribute('data-progress') || '0') + '%';
            document.getElementById('dStatus').innerText = btn.getAttribute('data-status') || '-';
            document.getElementById('dDetail').innerText = btn.getAttribute('data-detail') || '-';

            let proof = btn.getAttribute('data-proof_link') || '-';
            document.getElementById('dProofLink').innerHTML =
                (proof && proof !== '-' && proof.startsWith('http'))
                    ? `<a href="${proof}" target="_blank">${proof}</a>`
                    : proof;
        });

        // ✅ MODAL EDIT
        const modalEdit = document.getElementById('modalEditTask');
        modalEdit?.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            if (!btn) return;

            const id = btn.getAttribute('data-id');
            const judul = btn.getAttribute('data-judul') || '';
            const status = (btn.getAttribute('data-status') || '').toLowerCase();
            const proofLink = btn.getAttribute('data-proof_link') || '';
            const result = btn.getAttribute('data-result') || '';
            const shortc = btn.getAttribute('data-shortcoming') || '';
            const detail = btn.getAttribute('data-detail') || '';

            const form = modalEdit.querySelector('#formEditTask');
            form.setAttribute('action',
                "{{ route('admin.jobdesk.submitLikeUser', ':id') }}".replace(':id', id)
            );

            modalEdit.querySelector('#eJudul').value = judul;
            modalEdit.querySelector('#eStatus').value = status || 'pending';
            modalEdit.querySelector('#eProofLink').value = proofLink;
            modalEdit.querySelector('#eResult').value = result;
            modalEdit.querySelector('#eShortcoming').value = shortc;
            modalEdit.querySelector('#eDetail').value = detail;
        });

        /**
         * ✅ DROPDOWN STATUS VISIBLE OUT TABLE
         * dropdown-menu dipindahkan ke body saat dibuka
         */
        document.addEventListener('shown.bs.dropdown', function(event) {
            const parent = event.target.closest('.dropup, .dropdown');
            if (!parent) return;

            const menu = parent.querySelector('.dropdown-menu');
            if (!menu) return;

            // Simpan referensi parent asli
            menu.dataset.parentId = parent.dataset.ddid || Math.random().toString(36).substr(2, 9);
            parent.dataset.ddid = menu.dataset.parentId;

            // Simpan posisi tombol
            const rect = event.target.getBoundingClientRect();

            document.body.appendChild(menu);

            // Set posisi absolute/fixed agar tepat di tombol
            menu.style.position = 'fixed';
            menu.style.top = (rect.top - menu.offsetHeight) + 'px'; // dropup
            menu.style.left = (rect.left) + 'px';
            menu.style.zIndex = 999999;
        });

        document.addEventListener('hidden.bs.dropdown', function(event) {
            const parent = event.target.closest('.dropup, .dropdown');
            if (!parent) return;

            const menus = document.querySelectorAll('.dropdown-menu');
            menus.forEach(menu => {
                if (menu.dataset.parentId && parent.dataset.ddid === menu.dataset.parentId) {
                    parent.appendChild(menu);
                    menu.style.position = '';
                    menu.style.top = '';
                    menu.style.left = '';
                    menu.style.zIndex = '';
                }
            });
        });

    });
</script>
@endsection
