<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Trans Jatim Portal</title>

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

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.1);
            background: #fff;
            padding: 2rem 2.1rem 2.4rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .login-header-icon {
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

        .login-header h4 {
            margin-bottom: 0.25rem;
        }

        .login-header p {
            margin-bottom: 0;
        }

        .input-group-text i {
            font-size: 1rem;
        }

        .btn-toggle-password {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 1.6rem 1.3rem 2rem;
            }
        }
    </style>
</head>

<body>
    <main class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="login-header-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h4 class="fw-bold">Trans Jatim Portal</h4>
            <p class="text-muted small">Silakan masuk ke akun Anda</p>
        </div>

        <!-- Alert Session -->
        @if (session('status'))
            <div class="alert alert-info">{{ session('status') }}</div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" id="email" name="email"
                        class="form-control @error('email') is-invalid @enderror" placeholder="nama@transjatim.id"
                        required autofocus value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" id="password" name="password"
                        class="form-control @error('password') is-invalid @enderror" placeholder="Masukkan password"
                        required>
                    <button type="button" class="btn btn-outline-secondary btn-toggle-password" id="togglePassword"
                        aria-label="Tampilkan/sembunyikan password">
                        <i class="bi bi-eye"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Remember -->
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                <label class="form-check-label" for="remember_me">
                    Ingat saya
                </label>
            </div>

            <!-- Tombol -->
            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-primary" id="btnLogin">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </div>

            @if (Route::has('password.request'))
                {{-- <div class="text-center mb-2">
          <a href="{{ route('password.request') }}" class="small text-decoration-none">
            Lupa password?
          </a>
        </div> --}}
            @endif

            @if (Route::has('register'))
                <div class="text-center mt-3">
                    <span class="small text-muted">Belum punya akun?</span>
                    <a href="{{ route('register') }}" class="small fw-semibold text-decoration-none">
                        Daftar di sini
                    </a>
                </div>
            @endif
        </form>
    </main>

    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        // Toggle password visibility
        (function() {
            const toggle = document.getElementById('togglePassword');
            const pwd = document.getElementById('password');

            if (!toggle || !pwd) return;

            toggle.addEventListener('click', () => {
                const isPassword = pwd.type === 'password';
                pwd.type = isPassword ? 'text' : 'password';

                const icon = toggle.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                }
            });
        })();
    </script>
</body>

</html>
