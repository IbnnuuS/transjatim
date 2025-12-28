<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun | Trans Jatim Portal</title>

    <!-- Bootstrap & Icons -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
            position: relative;
        }

        /* ✅ 2-Color Blur Background */
        .blur-blob {
            position: fixed;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            z-index: -1;
            animation: moveBlob 20s infinite alternate;
        }

        .blob-1 {
            top: -10%;
            left: -10%;
            background: #4cc9f0;
        }

        .blob-2 {
            bottom: -10%;
            right: -10%;
            background: #4361ee;
        }

        @keyframes moveBlob {
            0% {
                transform: translate(0, 0) scale(1);
            }

            100% {
                transform: translate(50px, 50px) scale(1.1);
            }
        }

        /* ✅ Glassmorphism Card */
        .register-card {
            width: 100%;
            max-width: 480px;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
            padding: 2.5rem 2.5rem 3rem;
            position: relative;
            z-index: 10;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-logo {
            width: 100px;
            height: auto;
            margin: 0 auto 1rem;
            background: transparent;
            box-shadow: none;
            border-radius: 0;
        }

        .register-logo img {
            width: 100%;
            height: auto;
            display: block;
        }

        .register-header h4 {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .register-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }

        /* Form Elements */
        .form-label {
            font-weight: 500;
            color: #343a40;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #6c757d;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
            padding-left: 0.5rem;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
            background: #fff;
            color: var(--primary-color);
        }

        .btn-toggle-password {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-left: none;
            border-radius: 0 12px 12px 0 !important;
            color: #6c757d;
            transition: all 0.3s;
        }

        .input-group .form-control-password {
            border-radius: 0 !important;
        }

        .btn-toggle-password:hover {
            color: var(--primary-color);
            background: #fff;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4361ee, #3a56d4);
            border: none;
            border-radius: 12px;
            padding: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.25);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.35);
        }

        .link-login {
            color: var(--primary-color);
            font-weight: 600;
            transition: color 0.3s;
        }

        .link-login:hover {
            color: #2b45c2;
        }

        .alert {
            border-radius: 12px;
            border: none;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: rgba(255, 87, 87, 0.1);
            color: #cc2929;
        }

        .form-text {
            font-size: 0.8rem;
            color: #8898aa;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>

    <!-- Background Blobs -->
    <div class="blur-blob blob-1"></div>
    <div class="blur-blob blob-2"></div>

    <main class="register-card">

        <!-- Header -->
        <div class="register-header">
            <div class="register-logo">
                <img src="{{ asset('assets/img/logo ori.png') }}" alt="Trans Jatim">
            </div>
            <h4>Create New Account</h4>
            <p>Register to access the dashboard</p>
        </div>

        {{-- Alert global --}}
        @if ($errors->any())
            <div class="alert alert-danger py-2">
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
                <label for="name" class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>
                    <input id="name" type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required
                        autofocus autocomplete="name" placeholder="Type your full name">
                </div>
                @error('name')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Email --}}
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input id="email" type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required
                        autocomplete="username" placeholder="name@transjatim.id">
                </div>
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Password --}}
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input id="password" type="password" name="password"
                        class="form-control form-control-password @error('password') is-invalid @enderror" required
                        autocomplete="new-password" placeholder="Create a password">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-password" id="togglePwd"
                        aria-label="Toggle password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Minimum 8 characters, letters & numbers recommended.
                </div>
            </div>

            {{-- Confirm Password --}}
            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-shield-check"></i>
                    </span>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                        class="form-control form-control-password @error('password_confirmation') is-invalid @enderror"
                        required autocomplete="new-password" placeholder="Repeat password">
                    <button type="button" class="btn btn-outline-secondary btn-toggle-password" id="togglePwd2"
                        aria-label="Toggle confirm password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password_confirmation')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-primary btn-lg" id="btnRegister">
                    Register <i class="bi bi-person-check ms-2"></i>
                </button>
            </div>

            {{-- Link ke Login --}}
            <div class="text-center">
                <span class="small text-muted">Already have an account?</span>
                <a href="{{ route('login') }}" class="link-login small text-decoration-none ms-1">Sign In</a>
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

            btn.addEventListener('click', function() {
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
