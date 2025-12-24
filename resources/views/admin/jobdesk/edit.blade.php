@extends('admin.masterAdmin')

@section('title', 'Edit Jobdesk Task')

@section('content')
<div class="pagetitle">
    <h1 class="mb-1">Edit Jobdesk Task</h1>
    <nav>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.jobdesk.index') }}">Jobdesk</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Form Edit Task</h5>

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

            <form action="{{ route('admin.jobdesk.submitLikeUser', $task->id) }}" method="POST">
                @csrf

                {{-- ✅ Judul editable --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Judul Pekerjaan</label>
                    <input type="text" name="judul" class="form-control"
                        value="{{ old('judul', $task->judul) }}" required>
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach ($adminStatuses as $opt)
                            <option value="{{ $opt }}" @selected(strtolower($task->status) === strtolower($opt))>
                                {{ ucwords(str_replace('_', ' ', $opt)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Proof Link --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Link Bukti (Wajib jika Done)</label>
                    <input type="url" name="proof_link" class="form-control"
                        placeholder="https://..."
                        value="{{ old('proof_link', $task->proof_link) }}">
                </div>

                {{-- Result --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Hasil Pekerjaan</label>
                    <textarea name="result" class="form-control" rows="2">{{ old('result', $task->result) }}</textarea>
                </div>

                {{-- Shortcoming --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kekurangan / Kendala</label>
                    <textarea name="shortcoming" class="form-control" rows="2">{{ old('shortcoming', $task->shortcoming) }}</textarea>
                </div>

                {{-- Detail --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Detail</label>
                    <textarea name="detail" class="form-control" rows="3">{{ old('detail', $task->detail) }}</textarea>
                </div>

                {{-- Progress --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Progress (Auto)</label>
                    <input type="number" class="form-control" value="{{ $task->progress ?? 0 }}" disabled>
                </div>

                {{-- Foto --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Bukti Foto</label>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @forelse ($task->photos as $p)
                            <a href="{{ asset('storage/'.$p->path) }}" target="_blank">
                                <img src="{{ asset('storage/'.$p->path) }}"
                                    style="height:90px;width:auto;object-fit:cover;"
                                    class="rounded border">
                            </a>
                        @empty
                            <div class="text-muted">Tidak ada foto bukti.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                    <a href="{{ route('admin.jobdesk.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
