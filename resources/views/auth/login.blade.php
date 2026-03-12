<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SSO Portal</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, sans-serif; background: linear-gradient(135deg,#1e293b,#0f172a); color:#111827; }
        .card { width: 100%; max-width: 420px; background:#fff; border-radius: 12px; padding: 22px; box-shadow: 0 10px 28px rgba(0,0,0,.25); }
        .title { margin:0 0 6px; }
        .muted { color:#6b7280; margin:0 0 18px; }
        .label { display:block; margin-bottom:6px; font-weight:600; }
        .input { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:10px; }
        .mb-14 { margin-bottom:14px; }
        .btn { width:100%; border:0; border-radius:8px; padding:11px; color:#fff; cursor:pointer; font-weight:600; }
        .btn-ms { background:#2563eb; text-decoration:none; display:block; text-align:center; margin-bottom:14px; }
        .btn-login { background:#111827; }
        .divider { text-align:center; color:#6b7280; font-size:13px; margin:14px 0; }
        .remember { display:flex; align-items:center; gap:8px; margin-bottom:14px; }
        .pw-wrap { position:relative; }
        .eye { position:absolute; right:10px; top:50%; transform:translateY(-50%); border:0; background:transparent; cursor:pointer; color:#111827; }
        .eye svg { width:18px; height:18px; }
        .input.pw { padding-right:38px; }
        .alert { background:#fee2e2; color:#991b1b; border-radius:8px; padding:10px; margin-bottom:12px; }
    </style>
</head>
<body>
    <div class="card">
        <h2 class="title">SSO Portal</h2>
        <p class="muted">Sign in with Microsoft or emergency password</p>

        @if(session('error'))
            <div class="alert">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif

        <a class="btn btn-ms" href="{{ route('auth.microsoft') }}">Sign in with Microsoft 365</a>

        <div class="divider">or use email/password</div>

        <form method="POST" action="{{ route('login.authenticate') }}">
            @csrf
            <div class="mb-14">
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="mb-14">
                <label class="label" for="password">Password</label>
                <div class="pw-wrap">
                    <input class="input pw" id="password" type="password" name="password" required>
                    <button class="eye" type="button" onclick="togglePw()" aria-label="Toggle password visibility">
                        <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="remember">
                <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Remember me</label>
            </div>

            <button class="btn btn-login" type="submit">Login</button>
        </form>
    </div>

    <script>
        function togglePw() {
            const input = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    </script>
</body>
</html>
