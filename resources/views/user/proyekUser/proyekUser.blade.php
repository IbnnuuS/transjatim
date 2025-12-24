@extends('user.masterUser')

@section('content')

@php
// Helper badge status
if (!function_exists('statusBadge')) {
    function statusBadge($s) {
        $s = strtolower($s ?? 'on_progress');
        return match($s) {
            'done' => '<span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>Done</span>',
            'pending' => '<span class="badge bg-warning text-dark"><i class="bi bi-pause-circle me-1"></i>Pending</span>',
            default => '<span class="badge bg-primary"><i class="bi bi-hourglass-split me-1"></i>On Progress</span>',
        };
    }
}
@endphp

<!-- ================== PROYEK & JOBDESK AKTIF ================== -->
<div class="card mt-4" id="projects">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="card-title mb-0">Proyek & Jobdesk Saya</h5>
            @isset($myActiveProjectsCount)
                <span class="text-muted small">Total proyek aktif: <strong>{{ $myActiveProjectsCount }}</strong></span>
            @endisset
        </div>

        @php
        $collection = collect($jobdesks ?? []);
        $byStatus = $collection->groupBy(fn($j) => strtolower($j->status ?? 'on_progress'));
        $cols = [
            'on_progress' => ['title' => 'On Progress', 'icon' => 'bi-hourglass-split'],
            'pending' => ['title' => 'Pending', 'icon' => 'bi-pause-circle'],
            'done' => ['title' => 'Done', 'icon' => 'bi-check2-circle'],
        ];
        @endphp

        <div class="kanban row">
            @foreach ($cols as $key => $meta)
                <div class="col-md-4 mb-3">
                    <h6 class="mb-2"><i class="bi {{ $meta['icon'] }} me-1"></i> {{ $meta['title'] }}</h6>
                    <div class="vstack gap-2">

                        @forelse (($byStatus[$key] ?? collect()) as $jd)
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="me-2">
                                            <div class="small text-muted">{{ $jd->project->name ?? '— Proyek' }}</div>
                                            <div class="fw-semibold">{{ $jd->title ?? '— Judul Jobdesk' }}</div>
                                        </div>
                                        <div class="ms-2">{!! statusBadge($jd->status ?? 'on_progress') !!}</div>
                                    </div>

                                    <div class="mt-2 row g-2">
                                        <div class="col-6 small text-muted">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            Deadline:
                                            <span class="fw-medium">
                                                {{ optional($jd->due_date)->format('d M Y') ?? '—' }}
                                            </span>
                                        </div>
                                        <div class="col-6 small text-muted text-end">
                                            <i class="bi bi-clock-history me-1"></i>
                                            Update:
                                            <span class="fw-medium">
                                                {{ optional($jd->updated_at)->diffForHumans() ?? '—' }}
                                            </span>
                                        </div>
                                    </div>

                                    @php
                                        $p = (int)($jd->progress ?? 0);
                                        $p = max(0, min(100, $p)); // jaga-jaga agar tidak lebih dari 100
                                    @endphp

                                    <div class="progress mt-2" style="height:6px;">
                                        <div class="progress-bar" role="progressbar"
                                            style="width: {{ $p }}%"
                                            aria-valuenow="{{ $p }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-3">
                                        @if (Route::has('jobdesks.show') && isset($jd->id))
                                            <a href="{{ route('jobdesks.show', $jd->id) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye me-1"></i> Detail
                                            </a>
                                        @endif

                                        @if (Route::has('jobdesks.update-status') && isset($jd->id))
                                            <div class="dropup ms-auto">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Ubah Status
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form method="POST" action="{{ route('jobdesks.update-status', $jd->id) }}">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="status" value="on_progress">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-hourglass-split me-1"></i> On Progress
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('jobdesks.update-status', $jd->id) }}">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="status" value="pending">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-pause-circle me-1"></i> Pending
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('jobdesks.update-status', $jd->id) }}">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="status" value="done">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-check2-circle me-1"></i> Done
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted small py-3 border rounded-3 bg-light">
                                Belum ada item di status ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-muted small mt-2">
            Anda dapat mengubah status jobdesk melalui tombol <em>Ubah Status</em>.
        </div>
    </div>
</div>

<!-- ================== RIWAYAT JOBDESK ================== -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">Riwayat Jobdesk</h5>

        <form method="GET" class="row g-2 mb-3" action="{{ route('jobdesks.history') }}">
            <div class="col-sm-12 col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari judul / proyek / catatan">
            </div>
            <div class="col-6 col-md-3">
                <input type="date" name="start" value="{{ request('start') }}" class="form-control">
            </div>
            <div class="col-6 col-md-3">
                <input type="date" name="end" value="{{ request('end') }}" class="form-control">
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-borderless align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Proyek</th>
                        <th>Jobdesk</th>
                        <th>Status</th>
                        <th>Catatan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history ?? [] as $idx => $row)
                        <tr>
                            <td>{{ method_exists($history,'firstItem') ? $history->firstItem() + $idx : $idx + 1 }}</td>
                            <td><div class="small text-muted">{{ optional($row->created_at)->format('d/m/Y H:i') ?? '—' }}</div></td>
                            <td>{{ $row->project->name ?? '—' }}</td>
                            <td class="fw-semibold">{{ $row->title ?? '—' }}</td>
                            <td>{!! statusBadge($row->status ?? 'on_progress') !!}</td>
                            <td class="text-truncate" style="max-width:300px;">{{ $row->notes ?? '—' }}</td>
                            <td class="text-end">
                                @if (Route::has('jobdesks.show') && isset($row->id))
                                    <a href="{{ route('jobdesks.show', $row->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Belum ada riwayat jobdesk.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (isset($history) && method_exists($history, 'links'))
            <div class="mt-2">
                {{ $history->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
