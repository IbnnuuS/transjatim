@extends('admin.masterAdmin')

@section('title','Daily Report - Admin')

@section('content')
@php
  function statusBadgeClass($s) {
    $s = strtolower((string)$s);
    return match($s){
      'to_do'        => 'bg-light text-dark border',
      'pending'      => 'bg-warning text-dark',
      'in_progress'  => 'bg-primary',
      'verification' => 'bg-info text-dark',
      'rework'       => 'bg-danger',
      'delayed'      => 'bg-secondary',
      'cancelled'    => 'bg-dark',
      'done'         => 'bg-success',
      default        => 'bg-light text-dark border',
    };
  }

  $countPending     = ($tasks ?? collect())->where('status', 'pending')->count();
  $countInProgress  = ($tasks ?? collect())->where('status', 'in_progress')->count();
  $countDone        = ($tasks ?? collect())->where('status', 'done')->count();
  $countCancelled   = ($tasks ?? collect())->where('status', 'cancelled')->count();
@endphp

<div class="pagetitle">
  <h1>Daily Report</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
      <li class="breadcrumb-item">Admin</li>
      <li class="breadcrumb-item active">Daily Report</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row g-4">

    {{-- ================= LEFT ================= --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">

          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Daftar Daily Report</h5>
            <div class="text-muted small">
              Total: <span id="metricDailyTotal">{{ ($tasks ?? collect())->count() }}</span>
            </div>
          </div>

          <form class="row g-2 mt-2" method="GET" action="{{ route('admin.dailyreport') }}">
            <div class="col-md-3">
              <input type="date" name="date" class="form-control" value="{{ $pickedYmd ?? now()->toDateString() }}" required>
            </div>
            <div class="col-md-5">
              <select name="user_id" class="form-select">
                @foreach(($users ?? []) as $u)
                  <option value="{{ $u->id }}" @selected((int)$selectedId === (int)$u->id)>{{ $u->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 d-grid">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel"></i> Filter
              </button>
            </div>
            <div class="col-md-2 d-grid">
              <a href="{{ route('admin.dailyreport') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
          </form>

          <div class="table-responsive mt-3">
            <table class="table table-borderless align-middle" id="tableDailyAdmin">
              <thead>
                <tr class="text-nowrap">
                  <th style="width:42px">#</th>
                  <th style="min-width:180px;">Nama</th>
                  <th style="min-width:240px;">Judul Pekerjaan</th>
                  <th style="min-width:140px;">Tanggal & Jam</th>
                  <th>Divisi</th>
                  <th>PIC</th>
                  <th style="min-width:160px;">Progress</th>
                  <th class="text-end" style="min-width:140px;">Bukti </th>
                </tr>
              </thead>
              <tbody>
                @forelse($tasks as $i => $t)
                  @php
                    $no   = $i + 1;
                    $nama = $t->jobdesk?->user?->name ?? '—';
                    $div  = $t->jobdesk?->division ?? ($t->jobdesk?->user?->division ?? '—');

                    $tgl  = optional($t->schedule_date)->format('d/m/Y') ?? '—';
                    $jam  = trim(($t->start_time ?: '—') . (($t->end_time) ? ' - '.$t->end_time : ''));

                    $judul = $t->judul ?? '(Tanpa judul)';
                    $pic   = $t->pic   ?? '—';
                    $prog  = max(0, min(100, (int)($t->progress ?? 0)));
                    $st    = strtolower($t->status ?? '');

                    // gunakan accessor url -> $p->url
                    $photos = ($t->photos ?? collect())
                              ->map(fn($p) => $p->url)
                              ->values()
                              ->all();
                    $photoFirst = $photos[0] ?? asset('assets/sample/proof-placeholder.jpg');

                    $btnId = 'btnProof-'.$t->id;
                  @endphp

                  <tr>
                    <td class="text-muted">{{ $no }}</td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle fs-5 text-secondary"></i>
                        <span>{{ $nama }}</span>
                      </div>
                    </td>
                    <td class="fw-semibold">{{ $judul }}</td>
                    <td>
                      <div class="small">{{ $tgl }}</div>
                      <div class="text-muted small">{{ $jam }}</div>
                    </td>
                    <td><span class="badge bg-info text-dark">{{ $div }}</span></td>
                    <td><span class="badge bg-secondary">{{ $pic }}</span></td>
                    <td>
                      <div class="progress progress-sm">
                        <div class="progress-bar" style="width: {{ $prog }}%"></div>
                      </div>
                      <div class="small mt-1 d-flex align-items-center gap-2">
                        <span class="text-muted">{{ $prog }}%</span>
                        <span class="badge {{ statusBadgeClass($st) }}">{{ $st ?: '—' }}</span>
                      </div>
                    </td>
                    <td class="text-end">
                      <button
                        id="{{ $btnId }}"
                        class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#modalProof"
                        data-id="{{ $t->id }}"
                        data-name="{{ $nama }}"
                        data-title="{{ $judul }}"
                        data-date="{{ optional($t->schedule_date)->format('Y-m-d') ?? '' }}"
                        data-time="{{ $t->start_time ?? '' }}"
                        data-division="{{ $div }}"
                        data-pic="{{ $pic }}"
                        data-progress="{{ $prog }}"
                        data-status="{{ $st }}"
                        data-photo="{{ $photoFirst }}"
                        data-photos='@json($photos)'
                      >
                        <i class="bi bi-image"></i> Lihat
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                      <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                      Belum ada data pada tanggal ini.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="small text-muted">
              Menampilkan <span id="showFrom">{{ ($tasks->count() ? 1 : 0) }}</span>–<span id="showTo">{{ $tasks->count() }}</span>
              dari <span id="showTotal">{{ $tasks->count() }}</span>
            </div>
          </div>

        </div>
      </div>
    </div>
    {{-- ================= END LEFT ================= --}}

    {{-- ================= RIGHT ================= --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Ringkasan Status</h5>
          <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-warning text-dark">Pending: <span id="sumPending">{{ $countPending }}</span></span>
            <span class="badge bg-primary">In Progress: <span id="sumInProgress">{{ $countInProgress }}</span></span>
            <span class="badge bg-success">Done: <span id="sumDone">{{ $countDone }}</span></span>
            <span class="badge bg-dark">Cancelled: <span id="sumCancelled">{{ $countCancelled }}</span></span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Ringkasan Lain</h5>
          <ul class="mb-0 small">
            <li>Total tugas: <strong>{{ (int)($summary['total_tasks'] ?? $tasks->count()) }}</strong></li>
            <li>Rata-rata progress: <strong>{{ number_format((float)($summary['avg_progress'] ?? 0), 0) }}%</strong></li>
            <li>Done: <strong>{{ (int)($summary['done'] ?? 0) }}</strong> · Delayed: <strong>{{ (int)($summary['delayed'] ?? 0) }}</strong></li>
            <li>Rework: <strong>{{ (int)($summary['rework'] ?? 0) }}</strong> · Verification: <strong>{{ (int)($summary['verification'] ?? 0) }}</strong></li>
          </ul>
        </div>
      </div>

    </div>
    {{-- ================= END RIGHT ================= --}}

  </div>
</section>

{{-- ===================== MODAL: Bukti Foto  ===================== --}}
<div class="modal fade" id="modalProof" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">
          Bukti — <span id="mName">—</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-xl-6">
            <div class="card h-100">
              <div class="card-body">
                <h6 class="card-title">Bukti Foto</h6>
                <div id="mPhotoWrap">
                  <img id="mPhoto" src="{{ asset('assets/sample/proof-placeholder.jpg') }}" class="img-fluid rounded border" alt="Bukti Foto">
                </div>
                <div class="small text-muted mt-2">
                  Tanggal: <span id="mDate">—</span>, Jam: <span id="mTime">—</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <h6 class="card-title">Rincian</h6>
                <div class="row g-2">
                  <div class="col-md-4">
                    <div class="fw-semibold">Judul Pekerjaan</div>
                    <div id="mTitle">—</div>
                  </div>
                  <div class="col-md-3">
                    <div class="fw-semibold">Divisi</div>
                    <div id="mDivision">—</div>
                  </div>
                  <div class="col-md-3">
                    <div class="fw-semibold">PIC</div>
                    <div id="mPIC">—</div>
                  </div>
                  <div class="col-md-2">
                    <div class="fw-semibold">Progress</div>
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress progress-sm flex-grow-1" style="max-width:120px;">
                        <div class="progress-bar" id="mProgressBar" style="width:0%"></div>
                      </div>
                      <span id="mProgressText" class="small text-muted">0%</span>
                    </div>
                  </div>
                </div>
                <div class="mt-2 small text-muted">
                  Status: <span id="mStatus">—</span>
                </div>
              </div>
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

<script>
document.addEventListener('DOMContentLoaded', function(){
  const modal = document.getElementById('modalProof');
  modal?.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget; if (!btn) return;

    const name   = btn.getAttribute('data-name') || '—';
    const title  = btn.getAttribute('data-title') || '—';
    const date   = btn.getAttribute('data-date') || '—';
    const time   = btn.getAttribute('data-time') || '—';
    const divisi = btn.getAttribute('data-division') || '—';
    const pic    = btn.getAttribute('data-pic') || '—';
    const prog   = parseInt(btn.getAttribute('data-progress') || '0') || 0;
    const status = (btn.getAttribute('data-status') || '—').replace('_',' ');
    const photo  = btn.getAttribute('data-photo') || '';

    let photos = [];
    try { photos = JSON.parse(btn.getAttribute('data-photos') || '[]') || []; } catch(_) { photos = []; }

    modal.querySelector('#mName').textContent = name;
    modal.querySelector('#mTitle').textContent = title;
    modal.querySelector('#mDate').textContent = date;
    modal.querySelector('#mTime').textContent = time;
    modal.querySelector('#mDivision').textContent = divisi;
    modal.querySelector('#mPIC').textContent = pic;
    modal.querySelector('#mProgressText').textContent = Math.max(0,Math.min(100,prog)) + '%';
    modal.querySelector('#mProgressBar').style.width = Math.max(0,Math.min(100,prog)) + '%';
    modal.querySelector('#mStatus').textContent = status;

    const wrap = modal.querySelector('#mPhotoWrap');
    wrap.innerHTML = '';
    if (photos.length > 1) {
      const grid = document.createElement('div');
      grid.className = 'd-flex flex-wrap gap-2';
      photos.forEach((src) => {
        const img = document.createElement('img');
        img.src = src;
        img.className = 'rounded border';
        img.style.maxHeight = '44vh';
        img.style.maxWidth  = '100%';
        img.style.objectFit = 'contain';
        grid.appendChild(img);
      });
      wrap.appendChild(grid);
    } else {
      const img = document.createElement('img');
      img.id = 'mPhoto';
      img.src = (photos[0] || photo || '');
      img.className = 'img-fluid rounded border';
      img.alt = 'Bukti Foto';
      wrap.appendChild(img);
    }
  });
});
</script>
@endsection
