<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password | Trans Jatim Portal</title>

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
    </style>
</head>

<body>
    <main class="login-card">
        <div class="login-header">
            <div class="login-header-icon">
                <i class="bi bi-key-fill"></i>
            </div>
            <h4 class="fw-bold">Lupa Password?</h4>
            <p class="text-muted small">
                Masukkan email Anda dan kami akan mengirimkan link reset password.
            </p>
        </div>

        <!-- Status Session -->
        <x-auth-session-status class="mb-3 alert alert-success" :status="session('status')" />

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}"
                        required autofocus placeholder="nama@transjatim.id">
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-danger small" />
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">
                    Kirim Link Reset
                </button>
            </div>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Login
                </a>
            </div>
        </form>
    </main>
</body>

</html>
