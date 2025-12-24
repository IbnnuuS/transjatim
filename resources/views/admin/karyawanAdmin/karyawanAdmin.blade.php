@extends('admin.masterAdmin')

@section('content')
    <div class="pagetitle">
        <h1>Monitoring Kehadiran &amp; Teams</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item">Admin</li>
                <li class="breadcrumb-item active">Teams</li>
            </ol>
        </nav>
        <!-- <div class="small text-muted">Seluruh waktu ditampilkan dalam WIB.</div> -->
    </div>

    <section class="section dashboard">
        <div class="row g-4">
            <div class="col-12">
                <div class="row g-4">

                    {{-- ===== Kehadiran Harian ===== --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">

                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="card-title mb-0">Kehadiran Harian</h5>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary" id="clockBadge">--:--:-- WIB</span>
                                    </div>
                                </div>

                                {{-- FILTER ATTENDANCE --}}
                                <form class="row g-2 mt-2" method="GET" action="{{ url()->current() }}">
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" class="form-control" name="att_date"
                                            value="{{ request('att_date', now('Asia/Jakarta')->toDateString()) }}">
                                    </div>

                                    {{-- ✅ FILTER DIVISI --}}
                                    <div class="col-md-3">
                                        <label class="form-label">Divisi</label>
                                        <select name="division" class="form-select">
                                            <option value="">Semua Divisi</option>
                                            @foreach (($divisionOptions ?? ['Teknik','Digital','Customer Service']) as $d)
                                                <option value="{{ $d }}" @selected(request('division') === (string) $d)>
                                                    {{ $d }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- SEARCH --}}
                                    <div class="col-md-3">
                                        <label class="form-label">Cari Nama</label>
                                        <div class="d-flex gap-2">
                                            <input type="text" class="form-control" name="q"
                                                placeholder="Nama / Nama lengkap" value="{{ request('q') }}">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-filter"></i>
                                            </button>
                                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary"
                                                title="Reset">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive mt-3">
                                    <table class="table table-borderless align-middle" id="tableAttendance">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nama</th>
                                                <th>Divisi</th>
                                                <th>Jam Masuk</th>
                                                <th>Jam Pulang</th>
                                                <th>Status</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $attMap = $attendanceMap ?? []; @endphp

                                            @forelse($employees as $idx => $emp)
                                                @php
                                                    $avatarRaw = $emp->avatar ?? null;
                                                    $avatarUrl = $avatarRaw
                                                        ? asset('storage/' . ltrim($avatarRaw, '/'))
                                                        : asset('assets/img/profile-img.jpg');

                                                    $divisi = $emp->divisi ?? ($emp->division ?? null);

                                                    $att = $attMap[$emp->id] ?? null;
                                                    $jamIn = $att['in'] ?? null;
                                                    $jamOut = $att['out'] ?? null;
                                                    $st = strtolower((string) ($att['status'] ?? 'Belum Tersedia'));

                                                    $badge = match ($st) {
                                                        'hadir' => 'bg-success',
                                                        'terlambat' => 'bg-warning text-dark',
                                                        'izin' => 'bg-info text-dark',
                                                        'wfh' => 'bg-primary',
                                                        'dinas' => 'bg-secondary',
                                                        'pulang belum terekam' => 'bg-light text-dark border',
                                                        'tidak hadir' => 'bg-danger',
                                                        default => 'bg-light text-muted',
                                                    };
                                                @endphp
                                                <tr>
                                                    <td>{{ $idx + 1 }}</td>
                                                    <td class="d-flex align-items-center gap-2">
                                                        <img src="{{ $avatarUrl }}" class="rounded js-zoom-avatar"
                                                            style="width:32px;height:32px;object-fit:cover;border:1px solid #e9ecef;cursor:zoom-in;"
                                                            alt="avatar" data-avatar="{{ $avatarUrl }}">
                                                        <div>
                                                            <div class="fw-semibold">{{ $emp->full_name ?? $emp->name }}</div>
                                                        </div>
                                                    </td>
                                                    <td>{{ $divisi ?? '-' }}</td>
                                                    <td>{{ $jamIn ?: '—' }}</td>
                                                    <td>{{ $jamOut ?: '—' }}</td>
                                                    <td><span class="badge {{ $badge }}">{{ ucwords($st) }}</span></td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary btn-detail"
                                                            data-bs-toggle="modal" data-bs-target="#modalDetailTeams"
                                                            data-id="{{ $emp->id }}"
                                                            data-name="{{ $emp->name }}"
                                                            data-full-name="{{ $emp->full_name }}"
                                                            data-birth-date="{{ $emp->birth_date }}"
                                                            data-division="{{ $divisi ?? '-' }}"
                                                            data-avatar="{{ $avatarUrl }}">
                                                            <i class="bi bi-info-circle"></i> Detail
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">Belum ada data teams.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- ===== Kalender Bulanan ===== --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-0">Kalender Kehadiran Bulanan</h5>

                                <form class="row g-2 mt-2" method="GET" action="{{ url()->current() }}">
                                    <input type="hidden" name="att_date" value="{{ request('att_date', now('Asia/Jakarta')->toDateString()) }}">
                                    <input type="hidden" name="division" value="{{ request('division', '') }}">
                                    <input type="hidden" name="q" value="{{ request('q', '') }}">

                                    <div class="col-md-4">
                                        <label class="form-label">Teams</label>
                                        <select class="form-select" name="cal_employee_id">
                                            @forelse(($employeeList ?? collect()) as $opt)
                                                <option value="{{ $opt->id }}"
                                                    @selected((int) request('cal_employee_id', (int) ($employeeList->first()->id ?? 0)) === (int) $opt->id)>
                                                    {{ $opt->name }}
                                                </option>
                                            @empty
                                                <option value="">(Tidak ada teams)</option>
                                            @endforelse
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Bulan</label>
                                        <input type="month" class="form-control" name="cal_month"
                                            value="{{ request('cal_month', $calMonthStr ?? now('Asia/Jakarta')->format('Y-m')) }}">
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-calendar3"></i> Tampilkan
                                        </button>
                                    </div>

                                    <div class="col-md-2 d-flex align-items-end">
                                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary w-100">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </a>
                                    </div>
                                </form>

                                <div class="table-responsive mt-3">
                                    <table class="table table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:110px;">Tanggal</th>
                                                <th style="width:140px;">Hari</th>
                                                <th style="width:100px;">Masuk</th>
                                                <th style="width:100px;">Pulang</th>
                                                <th style="width:180px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(($calDays ?? []) as $d)
                                                @php
                                                    $st = strtolower($d['status'] ?? '');
                                                    $badge = match ($st) {
                                                        'hadir' => 'bg-success',
                                                        'terlambat' => 'bg-warning text-dark',
                                                        'izin' => 'bg-info text-dark',
                                                        'wfh' => 'bg-primary',
                                                        'dinas' => 'bg-secondary',
                                                        'pulang belum terekam' => 'bg-light text-dark border',
                                                        'tidak hadir' => 'bg-danger',
                                                        default => 'bg-light text-muted',
                                                    };
                                                @endphp
                                                <tr>
                                                    <td class="small">{{ \Carbon\Carbon::parse($d['date'])->format('d/m/Y') }}</td>
                                                    <td class="small">{{ $d['dow'] ?? '-' }}</td>
                                                    <td class="small">{{ $d['in'] ?? '—' }}</td>
                                                    <td class="small">{{ $d['out'] ?? '—' }}</td>
                                                    <td class="small">
                                                        <span class="badge {{ $badge }}">{{ ucwords($st ?: 'Belum tersedia') }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        Tidak ada data untuk bulan ini.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- ===== Daftar Teams ===== --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-0">Daftar Nama Teams</h5>

                                <div class="table-responsive mt-3">
                                    <table class="table table-borderless align-middle" id="tableEmployees">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Profil</th>
                                                <th>Nama</th>
                                                <th>Divisi</th>
                                                <th>Tanggal Lahir</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($employees as $i => $e)
                                                @php
                                                    $avatarRaw = $e->avatar ?? null;
                                                    $avatarUrl = $avatarRaw
                                                        ? asset('storage/' . ltrim($avatarRaw, '/'))
                                                        : asset('assets/img/profile-img.jpg');

                                                    $divisi = $e->divisi ?? ($e->division ?? null);

                                                    $birth = $e->birth_date
                                                        ? \Carbon\Carbon::parse($e->birth_date)->format('d/m/Y')
                                                        : '—';
                                                @endphp
                                                <tr>
                                                    <td class="text-muted">{{ $i + 1 }}</td>
                                                    <td>
                                                        <img src="{{ $avatarUrl }}" class="rounded js-zoom-avatar"
                                                            style="width:36px;height:36px;object-fit:cover;border:1px solid #e9ecef;cursor:zoom-in;"
                                                            alt="avatar" data-avatar="{{ $avatarUrl }}">
                                                    </td>
                                                    <td>{{ $e->name ?? '—' }}</td>
                                                    <td>
                                                        @if ($divisi)
                                                            <span class="badge bg-info text-dark">{{ $divisi }}</span>
                                                        @else
                                                            <span class="badge bg-light text-muted border">Belum Memilih Divisi</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $birth }}</td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-detail"
                                                            data-bs-toggle="modal" data-bs-target="#modalDetailTeams"
                                                            data-name="{{ $e->name }}"
                                                            data-full-name="{{ $e->full_name }}"
                                                            data-birth-date="{{ $e->birth_date }}"
                                                            data-division="{{ $divisi ?? '-' }}"
                                                            data-avatar="{{ $avatarUrl }}">
                                                            <i class="bi bi-info-circle"></i>
                                                        </button>

                                                        <button type="button" class="btn btn-sm btn-outline-success btn-edit"
                                                            data-bs-toggle="modal" data-bs-target="#modalEditTeams"
                                                            data-id="{{ $e->id }}"
                                                            data-name="{{ $e->name }}"
                                                            data-full-name="{{ $e->full_name }}"
                                                            data-division="{{ $e->division ?? ($e->divisi ?? '') }}"
                                                            data-birth-date="{{ $e->birth_date }}">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        Tidak ada teams untuk filter saat ini.
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
        </div>
    </section>

    {{-- ================= MODALS ================= --}}

    {{-- Detail Teams --}}
    <div class="modal fade" id="modalDetailTeams" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Detail Teams</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-3 text-center">
                            <img id="detAvatar" src="" alt="Avatar" class="rounded js-zoom-avatar"
                                style="width:120px;height:120px;object-fit:cover;border:1px solid #e9ecef;cursor:zoom-in;"
                                data-avatar="">
                        </div>
                        <div class="col-sm-9">
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Nama Akun</div>
                                <div class="col-sm-8 fw-semibold" id="detName">-</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Nama Lengkap</div>
                                <div class="col-sm-8 fw-semibold" id="detFullName">-</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Tanggal Lahir</div>
                                <div class="col-sm-8 fw-semibold" id="detBirth">-</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 text-muted">Divisi</div>
                                <div class="col-sm-8 fw-semibold" id="detDivision">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Zoom Avatar --}}
    <div class="modal fade" id="modalZoomAvatar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content" style="background: rgba(0,0,0,.9);">
                <div class="modal-body p-0 d-flex justify-content-center align-items-center" style="min-height:70vh;">
                    <img id="zoomAvatarImg" src="" alt="Avatar Zoom"
                        style="max-width:100%;max-height:80vh;object-fit:contain;">
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit Teams --}}
    <div class="modal fade" id="modalEditTeams" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formEditTeams" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h6 class="modal-title">Edit Data Teams</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row g-3">
                        {{-- ✅ Nama Akun --}}
                        <div class="col-12">
                            <label class="form-label small">Nama (Akun)</label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Nama Lengkap</label>
                            <input type="text" class="form-control" name="full_name" id="editFullName">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Divisi</label>
                            <select class="form-select" name="division" id="editDivision">
                                <option value="">- Pilih -</option>
                                <option value="Teknik">Teknik</option>
                                <option value="Digital">Digital</option>
                                <option value="Customer Service">Customer Service</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="birth_date" id="editBirthDate">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Clock WIB
    (function runClock() {
        const el = document.getElementById('clockBadge');
        if (!el) return;
        const tick = () => {
            const d = new Date();
            const pad = n => String(n).padStart(2, '0');
            const utc = d.getTime() + d.getTimezoneOffset() * 60000;
            const jkt = new Date(utc + 7 * 60 * 60000);
            el.textContent = `${pad(jkt.getHours())}:${pad(jkt.getMinutes())}:${pad(jkt.getSeconds())} WIB`;
        };
        tick();
        setInterval(tick, 1000);
    })();

    // Detail Modal
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-detail');
        if (!btn) return;

        const name = btn.getAttribute('data-name') || '-';
        const fullName = btn.getAttribute('data-full-name') || '-';
        const birthRaw = btn.getAttribute('data-birth-date') || '';
        const division = btn.getAttribute('data-division') || '-';
        const avatar = btn.getAttribute('data-avatar') || '';

        document.getElementById('detName').textContent = name;
        document.getElementById('detFullName').textContent = fullName;

        const fmtBirth = (() => {
            if (!birthRaw) return '-';
            const t = Date.parse(birthRaw);
            if (!isNaN(t)) {
                const d = new Date(t);
                const p = n => String(n).padStart(2, '0');
                return `${p(d.getDate())}/${p(d.getMonth()+1)}/${d.getFullYear()}`;
            }
            return birthRaw;
        })();

        document.getElementById('detBirth').textContent = fmtBirth;
        document.getElementById('detDivision').textContent = division;

        const detAvatar = document.getElementById('detAvatar');
        detAvatar.src = avatar;
        detAvatar.dataset.avatar = avatar;
    });

    // Zoom avatar
    document.addEventListener('click', function(e) {
        const img = e.target.closest('.js-zoom-avatar');
        if (!img) return;
        const src = img.dataset.avatar || img.getAttribute('src');
        document.getElementById('zoomAvatarImg').src = src;
        const modal = new bootstrap.Modal(document.getElementById('modalZoomAvatar'));
        modal.show();
    });

    // Edit Modal
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name') || '';
        const fullName = btn.getAttribute('data-full-name') || '';
        const division = btn.getAttribute('data-division') || '';
        const birthRaw = btn.getAttribute('data-birth-date') || '';

        const form = document.getElementById('formEditTeams');
        form.action = "{{ url('/admin/karyawan') }}/" + id;

        document.getElementById('editName').value = name;
        document.getElementById('editFullName').value = fullName;
        document.getElementById('editDivision').value = division;

        let dateVal = '';
        if (birthRaw) {
            const t = Date.parse(birthRaw);
            if (!isNaN(t)) {
                const d = new Date(t);
                const p = n => String(n).padStart(2, '0');
                dateVal = `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`;
            } else {
                dateVal = birthRaw;
            }
        }
        document.getElementById('editBirthDate').value = dateVal;
    });
</script>
@endpush
