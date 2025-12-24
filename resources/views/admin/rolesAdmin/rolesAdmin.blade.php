@extends('admin.masterAdmin')

@section('title', 'Role & Akses - Admin')

@section('content')
@php
    $appTz = 'Asia/Jakarta';
    $tzLabel = 'WIB';
    $appLocale = app()->getLocale();

    $fmtLastLogin = function ($user) use ($appTz, $tzLabel, $appLocale) {
        $dt = null;

        if (method_exists($user, 'getLastLoginAtLocalAttribute') && !empty($user->last_login_at_local)) {
            $dt =
                $user->last_login_at_local instanceof \Carbon\Carbon
                    ? $user->last_login_at_local->timezone($appTz)
                    : \Carbon\Carbon::parse($user->last_login_at_local, $appTz);
        } elseif (!empty($user->last_login_at)) {
            $dt =
                $user->last_login_at instanceof \Carbon\Carbon
                    ? $user->last_login_at->timezone($appTz)
                    : \Carbon\Carbon::parse($user->last_login_at, $appTz);
        }

        if (!$dt) return '—';

        $human = $dt->copy()->locale($appLocale)->diffForHumans();
        return e($dt->format('d/m/Y H:i')) . ' ' . e($tzLabel) . ' • ' . e($human);
    };
@endphp

<div class="pagetitle">
    <h1>Role & Akses</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">Admin</li>
            <li class="breadcrumb-item active">Role & Akses</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row g-4">

        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Daftar Pengguna</h5>
                        <div class="text-muted small">
                            Total Pengguna:
                            <span id="metricUsersTotal">
                                {{ method_exists($users, 'total') ? $users->total() : count($users) }}
                            </span>
                        </div>
                    </div>

                    <!-- Filter -->
                    <form id="roleAccessFilter" class="row g-2 mt-2" method="GET"
                        action="{{ route('admin.roles.index') }}">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="q" value="{{ request('q') }}"
                                placeholder="Cari nama / email / username">
                        </div>
                        <div class="col-md-5">
                            <select name="role" class="form-select">
                                <option value="">Semua Role</option>
                                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                                <option value="user" @selected(request('role') === 'user')>User</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="table-responsive mt-3">
                        <table class="table table-borderless align-middle" id="tableRoleAccess">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Email / Username</th>
                                    <th>Role</th>
                                    <th>Terakhir Login</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $i => $u)
                                <tr>
                                    <td>{{ method_exists($users, 'firstItem') ? $users->firstItem() + $i : $i + 1 }}</td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $u->avatar_url ?? asset('assets/img/profile-img.jpg') }}"
                                                class="rounded-circle" width="32" height="32" alt="pfp">
                                            <div>
                                                <div class="fw-semibold">{{ $u->name ?? '—' }}</div>
                                                <div class="small text-muted">{{ $u->division ?? '—' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="small">{{ $u->email ?? '—' }}</div>
                                        <div class="text-muted small">{{ $u->username ? '@' . $u->username : '' }}</div>
                                    </td>

                                    <td>
                                        @if (($u->role ?? '') === 'admin')
                                            <span class="badge bg-primary">Admin</span>
                                        @else
                                            <span class="badge bg-secondary">User</span>
                                        @endif
                                    </td>

                                    <td class="small text-muted">{!! $fmtLastLogin($u) !!}</td>

                                    <td class="text-end">
                                        <!-- ✅ Button Edit Role -->
                                        <button class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditRole"
                                            data-user-name="{{ $u->name }}"
                                            data-user-email="{{ $u->email }}"
                                            data-user-role="{{ $u->role }}"
                                            data-action="{{ route('admin.users.update-role', $u->id) }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete -->
                                        <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Hapus user ini? Aksi tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        Belum ada data pengguna.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if (method_exists($users, 'links'))
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="small text-muted">
                                Menampilkan
                                <span>{{ $users->total() ? $users->firstItem() : 0 }}</span>–<span>{{ $users->lastItem() }}</span>
                                dari <span>{{ $users->total() }}</span>
                            </div>
                            {{ $users->onEachSide(1)->appends(request()->query())->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Edit Role -->
<div class="modal fade" id="modalEditRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Edit Role Pengguna</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <form id="formEditRole" class="row g-2" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="col-12">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" id="erName" disabled>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" id="erEmail" disabled>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Role</label>
                        <select id="erRole" name="role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" id="btnSaveRole">Simpan</button>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalEditRole');
    const form  = document.getElementById('formEditRole');

    modal.addEventListener('show.bs.modal', (ev) => {
        const btn   = ev.relatedTarget;
        const name  = btn.getAttribute('data-user-name');
        const email = btn.getAttribute('data-user-email');
        const role  = btn.getAttribute('data-user-role');
        const action= btn.getAttribute('data-action');

        document.getElementById('erName').value = name;
        document.getElementById('erEmail').value = email;
        document.getElementById('erRole').value = role;

        // ✅ FIX: action langsung dari route helper
        form.action = action;
    });

    document.getElementById('btnSaveRole').addEventListener('click', () => {
        form.submit();
    });
});
</script>
@endpush
