@extends('user.masterUser')

@section('content')

<div class="pagetitle">
    <h1>Profil Saya</h1>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
<style>
    .img-container { max-height: 500px; display:block; }
    .img-container img { max-width:100%; display:block; }
</style>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body pt-4">

                <h5 class="card-title">Data Profil</h5>

                <form action="{{ route('profile.update.user') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-4 col-form-label">Foto Profil</label>
                        <div class="col-sm-8">
                            <div class="d-flex align-items-center gap-3">
                                <img id="avatarPreview"
                                    src="{{ $user->avatar_display }}"
                                    alt="Avatar"
                                    style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #e9ecef;">

                                <div class="flex-grow-1">
                                    <input type="file" name="avatar" id="avatarInput" class="form-control" accept="image/*">
                                    <small class="text-muted d-block mt-1">Format: JPG/PNG/WEBP, maks 2MB.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label">Nama Akun</label>
                        <div class="col-sm-8">
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $user->name ?? '') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label">Email</label>
                        <div class="col-sm-8">
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $user->email ?? '') }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label">Nama Lengkap</label>
                        <div class="col-sm-8">
                            <input type="text" name="full_name" class="form-control"
                                value="{{ old('full_name', $user->full_name ?? '') }}">
                        </div>
                    </div>

                   <div class="row mb-3">
    <label class="col-sm-4 col-form-label">Tanggal Lahir</label>
    <div class="col-sm-8">
        <input type="date" name="birth_date" class="form-control"
            value="{{ old('birth_date', $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('Y-m-d') : '') }}">
    </div>
</div>


                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label">Divisi</label>
                        <div class="col-sm-8">
                            <select name="division" class="form-control">
                                <option value="">-- Pilih Divisi --</option>
                                @php
                                    $div = old('division', $user->division ?? ($user->divisi ?? ''));
                                @endphp
                                <option value="Teknik" {{ $div === 'Teknik' ? 'selected' : '' }}>Teknik</option>
                                <option value="Digital" {{ $div === 'Digital' ? 'selected' : '' }}>Digital</option>
                                <option value="Customer Service" {{ $div === 'Customer Service' ? 'selected' : '' }}>Customer Service</option>
                            </select>
                        </div>
                    </div>

                    <div class="text-end">
                        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                    </div>
                </form>

                <hr class="my-4">

                <h5 class="card-title">Ganti Password</h5>

                <form action="{{ route('profile.update.user') }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="change_password">

                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label">Password Sekarang</label>
                        <div class="col-sm-8">
                            <input type="password" name="current_password" class="form-control"
                                autocomplete="current-password" placeholder="Masukkan password saat ini">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label">Password Baru</label>
                        <div class="col-sm-8">
                            <input type="password" name="password" class="form-control"
                                autocomplete="new-password" placeholder="Minimal 8 karakter">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-4 col-form-label">Konfirmasi Password</label>
                        <div class="col-sm-8">
                            <input type="password" name="password_confirmation" class="form-control"
                                autocomplete="new-password" placeholder="Ulangi password baru">
                        </div>
                    </div>

                    <div class="text-end">
                        <button class="btn btn-warning" type="submit">Ganti Password</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{{-- Crop Modal --}}
<div class="modal fade" id="modalCrop" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Potong Gambar (1:1)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="img-container">
                    <img id="imageToCrop" src="" alt="Picture">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnCropApply">Potong & Gunakan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const modalCropEl = document.getElementById('modalCrop');
    const modalCrop = new bootstrap.Modal(modalCropEl);
    const imageToCrop = document.getElementById('imageToCrop');
    const btnCropApply = document.getElementById('btnCropApply');
    let cropper = null;

    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = (evt) => {
            imageToCrop.src = evt.target.result;
            modalCrop.show();
        };
        reader.readAsDataURL(file);
    });

    modalCropEl.addEventListener('shown.bs.modal', () => {
        if (cropper) cropper.destroy();
        cropper = new Cropper(imageToCrop, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
        });
    });

    modalCropEl.addEventListener('hidden.bs.modal', () => {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    });

    btnCropApply.addEventListener('click', () => {
        if (!cropper) return;

        cropper.getCroppedCanvas({ width: 300, height: 300 }).toBlob((blob) => {
            if (!blob) return;

            avatarPreview.src = URL.createObjectURL(blob);

            const newFile = new File([blob], "avatar_cropped.jpg", { type: "image/jpeg" });
            const dt = new DataTransfer();
            dt.items.add(newFile);
            avatarInput.files = dt.files;

            modalCrop.hide();
        }, 'image/jpeg');
    });
</script>
@endpush

@endsection
