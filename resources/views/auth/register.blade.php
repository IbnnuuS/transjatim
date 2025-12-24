<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Akun | Trans Jatim Portal</title>

  <!-- Bootstrap & Icons -->
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">

  <style>
    body {
      background: #f5f6fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .register-card {
      width: 100%;
      max-width: 460px;
      background: #fff;
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 18px rgba(0, 0, 0, .08);
      padding: 2rem 2.2rem 2.4rem;
    }

    .register-header {
      text-align: center;
      margin-bottom: 1.8rem;
    }

    .register-header-icon {
      width: 70px;
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
      border-radius: 50%;
      background: #e8f1ff;
      color: #0d6efd;
      font-size: 34px;
    }

    .register-header h4 {
      margin-bottom: 0.25rem;
    }

    .register-header p {
      margin-bottom: 0;
    }

    .input-group-text i {
      font-size: 1rem;
    }

    .input-group .btn-toggle-password {
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
    }

    .form-text {
      font-size: 0.8rem;
    }

    @media (max-width: 576px) {
      .register-card {
        padding: 1.5rem 1.3rem 1.8rem;
      }
    }
  </style>
</head>
<body>

  <main class="register-card">

    <!-- Header -->
    <div class="register-header">
      <div class="register-header-icon">
        <i class="bi bi-person-plus-fill"></i>
      </div>
      <h4 class="fw-bold">Buat Akun Baru</h4>
      <p class="text-muted small">Daftar untuk mengakses dashboard</p>
    </div>

    {{-- Alert global --}}
    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('register') }}" novalidate>
      @csrf

      {{-- Name --}}
      <div class="mb-3">
        <label for="name" class="form-label">Nama</label>
        <div class="input-group">
          <span class="input-group-text">
            <i class="bi bi-person"></i>
          </span>
          <input
            id="name"
            type="text"
            name="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name') }}"
            required
            autofocus
            autocomplete="name"
            placeholder="Nama lengkap">
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      {{-- Email --}}
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <div class="input-group">
          <span class="input-group-text">
            <i class="bi bi-envelope"></i>
          </span>
          <input
            id="email"
            type="email"
            name="email"
            class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email') }}"
            required
            autocomplete="username"
            placeholder="nama@transjatim.id">
          @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      {{-- Password --}}
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text">
            <i class="bi bi-lock"></i>
          </span>
          <input
            id="password"
            type="password"
            name="password"
            class="form-control @error('password') is-invalid @enderror"
            required
            autocomplete="new-password"
            placeholder="Masukkan password">
          <button
            type="button"
            class="btn btn-outline-secondary btn-toggle-password"
            id="togglePwd"
            aria-label="Tampilkan/sembunyikan password">
            <i class="bi bi-eye"></i>
          </button>
          @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="form-text">
          Minimal 8 karakter, kombinasi huruf &amp; angka disarankan.
        </div>
      </div>

      {{-- Confirm Password --}}
      <div class="mb-4">
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
        <div class="input-group">
          <span class="input-group-text">
            <i class="bi bi-shield-check"></i>
          </span>
          <input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            class="form-control @error('password_confirmation') is-invalid @enderror"
            required
            autocomplete="new-password"
            placeholder="Ulangi password">
          <button
            type="button"
            class="btn btn-outline-secondary btn-toggle-password"
            id="togglePwd2"
            aria-label="Tampilkan/sembunyikan konfirmasi password">
            <i class="bi bi-eye"></i>
          </button>
          @error('password_confirmation')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      {{-- Submit --}}
      <div class="d-grid mb-2">
        <button type="submit" class="btn btn-primary" id="btnRegister">
          <i class="bi bi-person-check me-1"></i> Daftar
        </button>
      </div>

      {{-- Link ke Login --}}
      <div class="text-center">
        <span class="small text-muted">Sudah punya akun?</span>
        <a href="{{ route('login') }}" class="small text-decoration-none">Masuk</a>
      </div>
    </form>
  </main>

  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script>
    // Fungsi toggle show/hide password
    function setupPasswordToggle(buttonId, inputId) {
      const btn = document.getElementById(buttonId);
      const input = document.getElementById(inputId);

      if (!btn || !input) return;

      btn.addEventListener('click', function () {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.toggle('bi-eye');
          icon.classList.toggle('bi-eye-slash');
        }
      });
    }

    setupPasswordToggle('togglePwd', 'password');
    setupPasswordToggle('togglePwd2', 'password_confirmation');
  </script>
</body>
</html>
