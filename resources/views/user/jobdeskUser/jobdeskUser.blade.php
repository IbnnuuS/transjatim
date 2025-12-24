@extends('user.masterUser')

@section('content')
    <div class="pagetitle">
        <h1>Jobdesk</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Jobdesk</li>
            </ol>
        </nav>
    </div>

    @php
        $isUser = auth()->check() && auth()->user()->role === 'user';
        $tz = 'Asia/Jakarta';
    @endphp

    <section class="section">
        <div class="row">
            <div class="col-12">

                {{-- ====== CARD RIWAYAT + ACTION ====== --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-0">
                                <i class="bi bi-journal-text me-1"></i> Riwayat Jobdesk
                            </h5>
                            <small class="text-muted">Daftar task yang pernah dibuat.</small>
                        </div>

                        {{-- Filter Per Halaman --}}
                        <form action="{{ route('jobdesk.user') }}" method="GET" class="d-flex align-items-center">
                            <label for="per_page" class="me-2 text-muted small mb-0">Show:</label>
                            <select name="per_page" id="per_page" class="form-select form-select-sm"
                                onchange="this.form.submit()" style="width:auto; cursor:pointer;">
                                <option value="10" @selected(request('per_page') == 10)>10</option>
                                <option value="20" @selected(request('per_page') == 20)>20</option>
                                <option value="30" @selected(request('per_page') == 30)>30</option>
                                <option value="50" @selected(request('per_page') == 50)>50</option>
                            </select>
                        </form>
                    </div>

                    <div class="card-body">
                        {{-- ====== RIWAYAT JOBDESK ====== --}}
                        @forelse($jobdesks as $jobdesk)
                            @php
                                $division = $jobdesk->division ?? ($jobdesk->user->division ?? null);
                                $division = $division ? ucwords(strtolower(trim($division))) : null;

                                // Pakai accessor model submitted_at_wib, fallback created_at
                                $madeAtWIB =
                                    $jobdesk->submitted_at_wib ??
                                    optional($jobdesk->created_at)->timezone($tz)->format('d/m/Y H:i') . ' WIB';
                            @endphp

                            <div class="border rounded p-3 mb-3 bg-white">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $jobdesk->user->name ?? '-' }}</strong>
                                        @if ($division)
                                            <span class="text-muted small"> (Divisi: {{ $division }})</span>
                                        @endif
                                        <div class="small text-muted">Dibuat: {{ $madeAtWIB }}</div>
                                    </div>
                                </div>

                                <hr class="my-2">

                                @foreach ($jobdesk->tasks->take(7) as $t)
                                    @php
                                        $status = strtolower($t->status ?? '');
                                        $badgeClass = match ($status) {
                                            'done' => 'bg-success',
                                            'in_progress' => 'bg-info',
                                            'sedang_mengerjakan' => 'bg-primary',
                                            'pending' => 'bg-secondary',
                                            'cancelled' => 'bg-dark',
                                            'verification' => 'bg-primary',
                                            'delayed' => 'bg-warning text-dark',
                                            'rework' => 'bg-danger',
                                            'to_do' => 'bg-light text-dark border',
                                            default => 'bg-secondary',
                                        };

                                        $labelText = match ($status) {
                                            'done' => 'Done',
                                            'in_progress' => 'In Progress',
                                            'sedang_mengerjakan' => 'Sedang Mengerjakan',
                                            'pending' => 'Pending',
                                            'cancelled' => 'Cancelled',
                                            'verification' => 'Verification',
                                            'delayed' => 'Delayed',
                                            'rework' => 'Rework',
                                            'to_do' => 'To Do',
                                            default => ucfirst($status ?: 'Unknown'),
                                        };

                                        $pct = max(1, min(100, (int) ($t->progress ?? 1)));

                                        $photoList = $t->photos->map(fn($p) => $p->url)->values()->all();

                                        $uniqueIdx = ($loop->parent->index ?? 0) . '-' . $loop->index;
                                        $modalId = 'photosModal-' . $uniqueIdx;
                                        $collapseId = 'collapse-' . $uniqueIdx;

                                        // ✅ PER TASK waktu dibuat/diupdate (gunakan submitted_at_wib dari JobdeskTask model)
                                        $taskMadeAt =
                                            $t->submitted_at_wib ??
                                            optional($t->created_at)->timezone($tz)->format('d/m/Y H:i') . ' WIB';

                                        // ✅ penanda "admin" untuk template bullet: prioritas jobdesk assignment_id,
                                        // kalau suatu saat kamu bikin flag di task, bisa ditambah di sini.
                                        $isFromAdmin = $jobdeskFromAdmin ?? false;
                                    @endphp

                                    <div class="border rounded p-2 mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="fw-semibold">{{ $t->judul ?? '-' }}</div>
                                            <span class="badge {{ $badgeClass }}">{{ $labelText }}</span>
                                        </div>

                                        <div class="small text-muted mb-1">
                                            Tanggal: {{ optional($t->schedule_date)->format('Y-m-d') ?? '-' }} |
                                            Waktu: {{ $t->start_time ?? '-' }}–{{ $t->end_time ?? '-' }}
                                        </div>

                                        <div class="mt-1">
                                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                                <span>Persentase:</span>
                                                <span class="fw-semibold">{{ $pct }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: {{ $pct }}%;"
                                                    aria-valuenow="{{ $pct }}" aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end mt-2 gap-2">
                                            @if (in_array($status, ['pending', 'to_do']))
                                                <form action="{{ route('jobdesk.accept', $t->id) }}" method="POST"
                                                    onsubmit="return confirm('Mulai kerjakan task ini?')">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success py-0 px-2" type="submit">
                                                        <i class="bi bi-play-fill me-1"></i> Kerjakan
                                                    </button>
                                                </form>
                                            @endif

                                            @if (in_array($status, ['in_progress', 'sedang_mengerjakan', 'rework', 'verification', 'delayed']))
                                                <button class="btn btn-sm btn-warning py-0 px-2" type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalUpdateTask-{{ $t->id }}">
                                                    <i class="bi bi-pencil-square me-1"></i> Kerjakan
                                                </button>
                                            @endif

                                            <button class="btn btn-sm btn-outline-primary py-0 px-2" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false" aria-controls="{{ $collapseId }}">
                                                <i class="bi bi-eye me-1"></i> Detail
                                            </button>
                                        </div>

                                        @php
                                            // ✅ TEMPLATE DI LIST JIKA KOSONG & DARI ADMIN
                                            $showResult = $t->result;
                                            $showShort = $t->shortcoming;

                                            if ($isFromAdmin && empty(trim((string) $showResult))) {
                                                $showResult = '1. ';
                                            }
                                            if ($isFromAdmin && empty(trim((string) $showShort))) {
                                                $showShort = '1. ';
                                            }
                                        @endphp

                                        <div class="collapse mt-2" id="{{ $collapseId }}">
                                            <div class="card card-body bg-light border-0 p-2 mb-0">
                                                <div class="small">
                                                    <div><strong>Hasil:</strong> {{ $showResult ?? '-' }}</div>
                                                    <div><strong>Kekurangan:</strong> {{ $showShort ?? '-' }}</div>
                                                    <div><strong>Detail:</strong> {{ $t->detail ?? '-' }}</div>
                                                </div>

                                                @if (!empty($photoList))
                                                    @php
                                                        $maxThumb = 6;
                                                        $total = count($photoList);
                                                        $thumbs = array_slice($photoList, 0, $maxThumb);
                                                        $moreCount = max(0, $total - $maxThumb);
                                                    @endphp

                                                    <div class="mt-2">
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @foreach ($thumbs as $i => $src)
                                                                <a href="javascript:void(0)" data-bs-toggle="modal"
                                                                    data-bs-target="#{{ $modalId }}"
                                                                    class="position-relative">
                                                                    <img src="{{ $src }}" alt="bukti"
                                                                        class="rounded border"
                                                                        style="height:72px;width:96px;object-fit:cover">
                                                                    @if ($moreCount > 0 && $loop->last)
                                                                        <span
                                                                            class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 rounded text-white fw-semibold">
                                                                            +{{ $moreCount }}
                                                                        </span>
                                                                    @endif
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <div class="modal fade" id="{{ $modalId }}" tabindex="-1"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered modal-xl">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h6 class="modal-title mb-0">
                                                                        Bukti Foto — {{ $t->judul ?? 'Pekerjaan' }}
                                                                    </h6>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    @php $carouselId = $modalId . '-carousel'; @endphp
                                                                    <div id="{{ $carouselId }}" class="carousel slide"
                                                                        data-bs-ride="carousel">
                                                                        <div class="carousel-inner">
                                                                            @foreach ($photoList as $idx => $url)
                                                                                <div
                                                                                    class="carousel-item @if ($idx === 0) active @endif">
                                                                                    <div
                                                                                        class="d-flex justify-content-center">
                                                                                        <img src="{{ $url }}"
                                                                                            class="d-block rounded"
                                                                                            style="max-height:70vh;max-width:100%;object-fit:contain"
                                                                                            alt="bukti-{{ $idx + 1 }}">
                                                                                    </div>
                                                                                    <div
                                                                                        class="text-center mt-2 small text-muted">
                                                                                        Foto {{ $idx + 1 }} dari
                                                                                        {{ count($photoList) }}
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                        <button class="carousel-control-prev"
                                                                            type="button"
                                                                            data-bs-target="#{{ $carouselId }}"
                                                                            data-bs-slide="prev">
                                                                            <span class="carousel-control-prev-icon"
                                                                                aria-hidden="true"></span>
                                                                            <span class="visually-hidden">Sebelumnya</span>
                                                                        </button>
                                                                        <button class="carousel-control-next"
                                                                            type="button"
                                                                            data-bs-target="#{{ $carouselId }}"
                                                                            data-bs-slide="next">
                                                                            <span class="carousel-control-next-icon"
                                                                                aria-hidden="true"></span>
                                                                            <span class="visually-hidden">Berikutnya</span>
                                                                        </button>

                                                                        <div class="carousel-indicators mt-3">
                                                                            @foreach ($photoList as $idx => $u)
                                                                                <button type="button"
                                                                                    data-bs-target="#{{ $carouselId }}"
                                                                                    data-bs-slide-to="{{ $idx }}"
                                                                                    @if ($idx === 0) class="active" aria-current="true" @endif
                                                                                    aria-label="Slide {{ $idx + 1 }}"></button>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <small class="text-muted me-auto">
                                                                        Klik kanan pada gambar untuk menyimpan.
                                                                    </small>
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">Tutup</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- MODAL UPDATE TASK --}}
                                        <div class="modal fade" id="modalUpdateTask-{{ $t->id }}" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form action="{{ route('jobdesk.submit', $t->id) }}" method="POST"
                                                        class="jobdesk-form">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Pekerjaan</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-select js-status"
                                                                    required>
                                                                    @foreach (['pending', 'in_progress', 'verification', 'rework', 'delayed', 'cancelled', 'done'] as $opt)
                                                                        <option value="{{ $opt }}"
                                                                            @selected($status == $opt)>
                                                                            {{ ucwords(str_replace('_', ' ', $opt)) }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Link Bukti <small
                                                                        class="text-muted">(Wajib jika
                                                                        Done)</small></label>
                                                                <input type="url" name="proof_link"
                                                                    class="form-control js-proof"
                                                                    value="{{ $t->proof_link }}"
                                                                    placeholder="https://...">
                                                                <div class="small text-danger mt-1 d-none js-proof-hint">
                                                                    <i class="bi bi-exclamation-circle me-1"></i> Link
                                                                    Bukti wajib diisi jika status Done.
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Hasil</label>
                                                                <textarea name="result" class="form-control" rows="2">{{ $t->result }}</textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Kekurangan / Kendala</label>
                                                                <textarea name="shortcoming" class="form-control" rows="2">{{ $t->shortcoming }}</textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Detail</label>
                                                                <textarea name="detail" class="form-control" rows="2">{{ $t->detail }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div> {{-- End task item --}}
                                @endforeach
                            </div>
                        @empty
                            <div class="text-muted">Belum ada jobdesk.</div>
                        @endforelse
                    </div>

                    <div class="card-footer bg-white">
                        {{ $jobdesks->links('vendor.pagination.custom-bootstrap-5') }}
                    </div>
                </div>

                <div class="d-grid gap-2 mb-4">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#modalAddJobdesk">
                        <i class="bi bi-plus-lg me-1"></i> Lainnya
                    </button>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form.jobdesk-form').forEach(form => {
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
