@extends('admin.masterAdmin')

@section('content')
    <div class="pagetitle">
        <h1>Tambah Jobdesk</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/admin/jobdesk') }}">Rekap Jobdesk</a></li>
                <li class="breadcrumb-item active">Tambah Jobdesk</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-plus-square-dotted me-1"></i> Form Tambah Jobdesk
                        </h5>
                    </div>

                    <div class="card-body">

                        <form id="jobdeskForm" class="row g-3" action="{{ route('admin.tambahJobdesk.store') }}"
                            method="POST">
                            @csrf

                            {{-- Nama Pekerjaan --}}
                            <div class="col-md-6">
                                <label class="form-label small">Nama Pekerjaan</label>
                                <input type="text" name="tasks[0][judul]" class="form-control"
                                    value="{{ old('tasks.0.judul') }}" placeholder="Contoh: Input data tiket..." required>
                            </div>

                            {{-- Tanggal --}}
                            <div class="col-md-3">
                                <label class="form-label small">Tanggal</label>
                                <input type="date" name="tasks[0][schedule_date]" class="form-control"
                                    value="{{ old('tasks.0.schedule_date', now()->toDateString()) }}" required>
                            </div>

                            {{-- Teams / User --}}
                            <div class="col-md-3">
                                <label class="form-label small">Teams</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">-- Pilih User --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Deskripsi --}}
                            <div class="col-12">
                                <label class="form-label small">Deskripsi</label>
                                <textarea name="tasks[0][detail]" class="form-control" rows="3" placeholder="Detail singkat (opsional)">{{ old('tasks.0.detail') }}</textarea>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-save me-1"></i> Simpan Jobdesk
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ================== SCRIPTS ================== --}}
    <script>
        // ====== REAL-TIME CLOCK (WIB) ======
        (function() {
            const clockEl = document.getElementById('clockDisplay');
            const hiddenEl = document.getElementById('submitted_at');
            const form = document.getElementById('jobdeskForm');
            const btnSubmit = document.getElementById('btnSubmit');

            function formatWIB(d) {
                try {
                    const opts = {
                        timeZone: 'Asia/Jakarta',
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    };
                    const parts = new Intl.DateTimeFormat('id-ID', opts).formatToParts(d);
                    const map = Object.fromEntries(parts.map(p => [p.type, p.value]));
                    return `${map.day}/${map.month}/${map.year} ${map.hour}:${map.minute}:${map.second} WIB`;
                } catch (e) {
                    const pad = n => String(n).padStart(2, '0');
                    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())} WIB`;
                }
            }

            function setDisplay() {
                if (clockEl) clockEl.value = formatWIB(new Date());
            }

            setDisplay();
            setInterval(setDisplay, 1000);

            function toISOWithJakartaOffset(d) {
                const utcMs = d.getTime() + (d.getTimezoneOffset() * 60000);
                const jkt = new Date(utcMs + (7 * 60 * 60000));
                const pad = n => String(n).padStart(2, '0');

                const Y = jkt.getFullYear();
                const M = pad(jkt.getMonth() + 1);
                const D = pad(jkt.getDate());
                const h = pad(jkt.getHours());
                const m = pad(jkt.getMinutes());
                const s = pad(jkt.getSeconds());

                return `${Y}-${M}-${D}T${h}:${m}:${s}+07:00`;
            }

            function setSubmittedNow() {
                if (hiddenEl) hiddenEl.value = toISOWithJakartaOffset(new Date());
            }

            form?.addEventListener('submit', setSubmittedNow);
            btnSubmit?.addEventListener('click', setSubmittedNow);
        })();

        // ====== CAMERA ONLY UPLOADER ======
        (function() {
            const video = document.getElementById('camStream');
            const btnStart = document.getElementById('btnStartCam');
            const btnSnap = document.getElementById('btnSnap');
            const btnStop = document.getElementById('btnStopCam');
            const shotsWrap = document.getElementById('shots');
            const shotsInputs = document.getElementById('shotsInputs');
            const form = document.getElementById('jobdeskForm');

            let stream = null;
            let shotCount = 0;

            async function startCam() {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: {
                                ideal: 'environment'
                            },
                            width: {
                                ideal: 1280
                            },
                            height: {
                                ideal: 720
                            }
                        },
                        audio: false
                    });

                    video.srcObject = stream;
                    btnSnap.disabled = false;
                    btnStop.disabled = true;

                    video.onloadedmetadata = () => {
                        btnStop.disabled = false;
                    };
                } catch (err) {
                    alert(
                        'Gagal mengakses kamera. Izinkan akses kamera dan gunakan perangkat yang memiliki kamera.'
                        );
                    console.error(err);
                }
            }

            function stopCam() {
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
                video.srcObject = null;
                btnSnap.disabled = true;
                btnStop.disabled = true;
            }

            function takeShot() {
                if (!video.videoWidth || !video.videoHeight) return;

                const canvas = document.createElement('canvas');
                const maxW = 1280;
                const scale = Math.min(1, maxW / video.videoWidth);

                canvas.width = Math.round(video.videoWidth * scale);
                canvas.height = Math.round(video.videoHeight * scale);

                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const dataURL = canvas.toDataURL('image/jpeg', 0.85);

                const idx = ++shotCount;
                const card = document.createElement('div');
                card.className = 'position-relative';
                card.innerHTML = `
                  <img src="${dataURL}" alt="shot ${idx}"
                       class="rounded border"
                       style="height:96px;width:auto;object-fit:cover;">
                  <button type="button"
                          class="btn btn-sm btn-danger position-absolute top-0 end-0 translate-middle p-1"
                          title="Hapus"
                          data-idx="${idx}"><i class="bi bi-x-lg"></i></button>`;
                shotsWrap.appendChild(card);

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tasks[0][photos_b64][]';
                input.value = dataURL;
                input.dataset.idx = String(idx);
                shotsInputs.appendChild(input);
            }

            shotsWrap.addEventListener('click', function(e) {
                const btn = e.target.closest('button[data-idx]');
                if (!btn) return;

                const idx = btn.getAttribute('data-idx');
                btn.parentElement?.remove();

                [...shotsInputs.querySelectorAll('input[type="hidden"]')].forEach(inp => {
                    if (inp.dataset.idx === idx) inp.remove();
                });
            });

            form?.addEventListener('submit', function(e) {
                const hasShots = shotsInputs.querySelector('input[type="hidden"]');
                if (!hasShots) {
                    e.preventDefault();
                    alert('Harap ambil minimal satu foto dari kamera sebagai bukti.');
                    return false;
                }
            });

            btnStart?.addEventListener('click', startCam);
            btnStop?.addEventListener('click', stopCam);
            btnSnap?.addEventListener('click', takeShot);

            window.addEventListener('pagehide', stopCam);
        })();
    </script>
@endsection
