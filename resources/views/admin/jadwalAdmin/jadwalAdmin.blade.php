@extends('admin.masterAdmin')

@section('content')
    <div class="pagetitle">
        <h1>Jadwal & Absensi</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.admin') }}">Home</a></li>
                <li class="breadcrumb-item">Admin</li>
                <li class="breadcrumb-item active">Jadwal & Absensi</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="row">
            <!-- KPI Cards -->
            <div class="col-xxl-6 col-md-6">
                <div class="card info-card sales-card">
                    <div class="card-body">
                        <h5 class="card-title">Hadir <span>| Hari Ini</span></h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-person-check"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $stats['present'] ?? 0 }}</h6>
                                <span class="text-muted small">Total hadir</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6 col-md-6">
                <div class="card info-card customers-card">
                    <div class="card-body">
                        <h5 class="card-title">Tidak Hadir <span>| Hari Ini</span></h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-person-x"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $stats['absent'] ?? 0 }}</h6>
                                <span class="text-muted small">Alpha / no show</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Left column -->
            <div class="col-lg-8">
                <div class="row">

                    <!-- Daftar Kehadiran (tabel) -->
                    <div class="col-12">
                        <div class="card recent-sales overflow-auto">
                            <div class="card-body">
                                <h5 class="card-title">Daftar Kehadiran <span>| Hari Ini</span></h5>

                                <form class="row g-2 align-items-end mb-3" method="GET"
                                    action="{{ route('admin.jadwal') }}">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Tanggal</label>
                                        <input type="date" class="form-control" name="date"
                                            value="{{ request('date') }}">
                                    </div>
                                    <div class="col-md-3"></div>
                                    <div class="col-md-6 d-flex gap-2 flex-wrap">
                                        <button class="btn btn-primary flex-fill" type="submit">
                                            <i class="bi bi-funnel me-1"></i> Terapkan Filter
                                        </button>
                                        <a class="btn btn-outline-secondary" href="{{ route('admin.jadwal') }}">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                                        </a>
                                        <div class="form-check ms-0 ms-md-2 d-flex align-items-center">
                                            <input class="form-check-input" type="checkbox" id="autoRefresh"
                                                {{ request('auto') ? 'checked' : '' }}>
                                            <label class="form-check-label small ms-1" for="autoRefresh">Auto</label>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle">
                                        <thead>
                                            <tr class="text-nowrap">
                                                <th>#</th>
                                                <th>Nama</th>
                                                <th>Divisi</th>

                                                <th>Status</th>
                                                <th>Masuk</th>
                                                <th>Pulang</th>
                                                <th>Overtime</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($attendances as $idx => $row)
                                                <tr>
                                                    <td class="text-muted">
                                                        {{ method_exists($attendances, 'firstItem') ? $attendances->firstItem() + $idx : $idx + 1 }}
                                                    </td>
                                                    <td class="fw-semibold">{{ $row->employee->name ?? '-' }}</td>
                                                    <td>{{ $row->division ?? '-' }}</td>

                                                    <td>
                                                        @php
                                                            $s = strtolower($row->status ?? '');
                                                            $badge =
                                                                [
                                                                    'present' => 'success',
                                                                    'absent' => 'danger',
                                                                    'late' => 'info',
                                                                ][$s] ?? 'secondary';
                                                            $label =
                                                                [
                                                                    'present' => 'Hadir',
                                                                    'absent' => 'Tidak Hadir',
                                                                    'late' => 'Terlambat',
                                                                ][$s] ?? ucfirst($s ?: 'Belum');
                                                        @endphp
                                                        <span class="badge bg-{{ $badge }}">{{ $label }}</span>
                                                    </td>
                                                    <td>
                                                        @if ($row->in_time)
                                                            <i class="bi bi-box-arrow-in-right me-1"></i>{{ \Illuminate\Support\Str::of($row->in_time)->substr(0, 5) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($row->out_time)
                                                            <i class="bi bi-box-arrow-right me-1"></i>{{ \Illuminate\Support\Str::of($row->out_time)->substr(0, 5) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if (($row->overtime_minutes ?? 0) > 0)
                                                            <span class="badge bg-warning text-dark">{{ $row->overtime_minutes }}m</span>
                                                            @if ($row->overtime_reason)
                                                                <br><small class="text-muted fst-italic"
                                                                    style="font-size: 0.75rem;">
                                                                    {{ \Illuminate\Support\Str::limit($row->overtime_reason, 20) }}
                                                                </small>
                                                            @endif
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small">{{ $row->note ?? '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-4">
                                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                                        Tidak ada data kehadiran untuk filter saat ini.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @if (method_exists($attendances, 'links'))
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="small text-muted">
                                            Menampilkan
                                            <span>{{ $attendances->total() ? $attendances->firstItem() : 0 }}</span>–<span>{{ $attendances->lastItem() }}</span>
                                            dari <span>{{ $attendances->total() }}</span>
                                        </div>
                                        {{ $attendances->onEachSide(1)->appends(request()->query())->links() }}
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Right: Kalender Bulanan (klik-able) -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Kalender Jadwal <span class="text-muted">/ Bulanan</span></h5>
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-outline-secondary"
                                    href="{{ route('admin.jadwal', ['m' => $calendarNav['prev']['m'], 'y' => $calendarNav['prev']['y']]) }}"
                                    title="Bulan sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                                <a class="btn btn-outline-secondary"
                                    href="{{ route('admin.jadwal', ['m' => $calendarNav['now']['m'], 'y' => $calendarNav['now']['y']]) }}"
                                    id="goToday" title="Bulan ini">Hari ini</a>
                                <a class="btn btn-outline-secondary"
                                    href="{{ route('admin.jadwal', ['m' => $calendarNav['next']['m'], 'y' => $calendarNav['next']['y']]) }}"
                                    title="Bulan berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="mt-1 text-muted small">{{ $monthLabel }}</div>

                        <div class="table-responsive mt-3">
                            <table class="table table-bordered small mb-2">
                                <thead>
                                    <tr class="table-light text-center align-middle">
                                        <th>Sen</th>
                                        <th>Sel</th>
                                        <th>Rab</th>
                                        <th>Kam</th>
                                        <th>Jum</th>
                                        <th>Sab</th>
                                        <th>Min</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        use Carbon\Carbon;
                                        $d = $gridStart->copy();
                                    @endphp

                                    @while ($d->lte($gridEnd))
                                        <tr>
                                            @for ($i = 0; $i < 7; $i++)
                                                @php
                                                    $isOtherMonth = $d->month !== $month;
                                                    $isToday = $d->isSameDay($today);
                                                    $dateKey = $d->toDateString();
                                                    $count = (int) ($scheduleCounts[$dateKey] ?? 0);
                                                    $holidayName = $holidays[$dateKey] ?? null;
                                                    $isHoliday = !empty($holidayName);
                                                @endphp
                                                <td class="align-top p-2 {{ $isOtherMonth ? 'bg-light' : '' }} {{ $isHoliday ? 'bg-danger-subtle' : '' }} cal-cell"
                                                    data-date="{{ $dateKey }}" role="button"
                                                    title="Lihat jadwal tanggal {{ $d->format('d/m/Y') }}">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-semibold {{ $isOtherMonth ? 'text-muted' : '' }}">
                                                            {{ $d->format('j') }}
                                                        </span>
                                                        @if ($isToday)
                                                            <span class="badge bg-primary"
                                                                style="font-size: 0.65rem;">Hari ini</span>
                                                        @elseif($isHoliday)
                                                            <span class="badge bg-danger"
                                                                style="font-size: 0.65rem;">Libur</span>
                                                        @endif
                                                    </div>

                                                    @if ($isHoliday)
                                                        <div class="mb-1">
                                                            <small class="text-danger fw-bold d-block lh-1"
                                                                style="font-size: 0.7rem;">
                                                                {{ \Illuminate\Support\Str::limit($holidayName, 20) }}
                                                            </small>
                                                        </div>
                                                    @endif

                                                    @if ($count > 0)
                                                        <div class="mt-1">
                                                            <span class="badge bg-secondary">Jadwal:
                                                                {{ $count }}</span>
                                                        </div>
                                                    @else
                                                        <div class="text-muted small mt-1">—</div>
                                                    @endif
                                                </td>
                                                @php $d->addDay(); @endphp
                                            @endfor
                                        </tr>
                                    @endwhile
                                </tbody>
                            </table>
                        </div>
                        <div class="text-muted small">
                            Klik tanggal untuk melihat detail jadwal & status hadir-nya.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Modal: Jadwal Harian ===== --}}
    <div class="modal fade" id="modalDaySchedules" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        Jadwal — <span id="mdDate">-</span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="mdCounter" class="mb-2 text-muted small">—</div>
                    <div id="mdList"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const SCHEDULE_ITEMS = @json($scheduleItems ?? []);
        const HOLIDAYS = @json($holidays ?? []);

        const fmtDateID = (iso) => {
            try {
                const d = new Date(iso + 'T00:00:00');
                return new Intl.DateTimeFormat('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                }).format(d);
            } catch {
                return iso;
            }
        };

        const time5 = (t) => t ? String(t).slice(0, 5) : '';

        document.addEventListener('click', (e) => {
            const cell = e.target.closest('.cal-cell');
            if (!cell) return;

            const date = cell.getAttribute('data-date');
            const list = SCHEDULE_ITEMS[date] || [];
            const holiday = HOLIDAYS[date] || null;

            const md = document.getElementById('modalDaySchedules');
            md.querySelector('#mdDate').textContent = fmtDateID(date);

            const counter = md.querySelector('#mdCounter');
            counter.textContent = list.length ? `Total jadwal: ${list.length}` : (holiday ? 'Hari Libur Nasional' :
                'Tidak ada jadwal.');

            const wrap = md.querySelector('#mdList');
            wrap.innerHTML = '';

            if (holiday) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger d-flex align-items-center mb-3';
                alertDiv.innerHTML = `
                    <i class="bi bi-calendar-event me-2 fs-4"></i>
                    <div>
                        <div class="fw-bold">HARI LIBUR NASIONAL</div>
                        <div>${String(holiday).replace(/</g,'&lt;')}</div>
                    </div>
                `;
                wrap.appendChild(alertDiv);
            }

            if (list.length) {
                const frag = document.createDocumentFragment();
                list.forEach((it) => {
                    const div = document.createElement('div');
                    div.className = 'border rounded p-2 mb-2';
                    div.innerHTML = `
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold">${it.title ?? '(Tanpa judul)'}</div>
                          <div class="small text-muted">
                            ${it.user ? 'PIC: ' + it.user + ' • ' : ''}Divisi: ${it.division ?? '-'}
                          </div>
                        </div>
                        <div class="text-end">
                          ${
                            it.att_label
                              ? `<span class="badge bg-${(it.att_badge||'secondary')}">${it.att_label}</span>`
                              : ''
                          }
                        </div>
                      </div>
                      <div class="small mt-1 text-muted">
                        ${it.start ? 'Mulai: ' + time5(it.start) : ''}
                        ${it.end ? ' • Selesai: ' + time5(it.end) : ''}
                      </div>
                      ${
                        (it.in_time || it.out_time || it.att_note || it.ot_min > 0)
                          ? `<div class="small mt-1">
                               ${it.in_time  ? '<i class="bi bi-box-arrow-in-right me-1"></i>' + time5(it.in_time) : ''}
                               ${it.out_time ? ' • <i class="bi bi-box-arrow-right me-1"></i>' + time5(it.out_time) : ''}
                               ${it.ot_min > 0 ? '<br><span class="badge bg-warning text-dark mt-1">Overtime: ' + it.ot_min + 'm</span>' + (it.ot_reason ? ' <span class="small text-muted fst-italic">(' + it.ot_reason + ')</span>' : '') : ''}
                               ${it.att_note ? '<br><span class="text-muted">Catatan absen:</span> ' + String(it.att_note).replace(/</g,'&lt;') : ''}
                             </div>`
                          : ''
                      }
                    `;
                    frag.appendChild(div);
                });
                wrap.appendChild(frag);
            } else if (!holiday) {
                wrap.innerHTML =
                    '<div class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-1"></i>Belum ada jadwal.</div>';
            }

            const modal = bootstrap?.Modal ? new bootstrap.Modal(md) : null;
            modal && modal.show();
        });

        (function() {
            const auto = document.getElementById('autoRefresh');
            if (!auto) return;
            let timer = null;
            const setupAuto = () => {
                if (auto.checked) {
                    timer = setInterval(() => window.location.reload(), 60000);
                } else if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            };
            auto.addEventListener('change', setupAuto);
            setupAuto();
        })();
    </script>
@endsection
