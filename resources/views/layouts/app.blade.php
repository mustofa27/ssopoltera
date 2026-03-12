<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SSO Portal' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .app-shell { min-height: 100vh; }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #111827;
            color: #fff;
            padding: 18px 12px;
            transition: width 0.2s ease;
            overflow: hidden;
            z-index: 1000;
        }
        .sidebar-brand {
            font-weight: 700;
            padding: 8px 10px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 12px;
            white-space: nowrap;
        }
        .sidebar-nav { display: flex; flex-direction: column; gap: 6px; }
        .sidebar-link {
            color: #e5e7eb;
            text-decoration: none;
            padding: 10px;
            border-radius: 8px;
            display: block;
            white-space: nowrap;
        }
        .menu-icon {
            display: inline-block;
            width: 18px;
            text-align: center;
            margin-right: 8px;
        }
        .sidebar-link:hover,
        .sidebar-link.active { background: #1f2937; color: #fff; }
        .main-area {
            margin-left: 250px;
            transition: margin-left 0.2s ease;
            min-height: 100vh;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .icon-btn {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 7px 10px;
            cursor: pointer;
            color: #111827;
        }
        .user-menu { position: relative; }
        .user-menu-button {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 190px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            padding: 8px;
            display: none;
        }
        .user-dropdown.show { display: block; }
        .dropdown-label {
            padding: 8px 10px;
            font-size: 13px;
            color: #6b7280;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 6px;
        }
        .dropdown-item {
            width: 100%;
            text-align: left;
            border: 0;
            background: #fff;
            color: #111827;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
        }
        .dropdown-item:hover { background: #f3f4f6; }
        .app-shell.collapsed .sidebar { width: 76px; }
        .app-shell.collapsed .main-area { margin-left: 76px; }
        .app-shell.collapsed .sidebar-link { text-align: center; }
        .app-shell.collapsed .menu-icon { margin-right: 0; }
        .app-shell.collapsed .menu-label,
        .app-shell.collapsed .sidebar-brand-text { display: none; }
        .container { max-width: 980px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 10px; padding: 18px; box-shadow: 0 4px 18px rgba(0,0,0,.08); }
        .card-soft { background: #f9fafb; padding: 12px; }
        .alert { padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .plain-list { margin: 0; padding-left: 18px; }
        .btn { display: inline-block; background: #2563eb; color: #fff; border: 0; padding: 10px 14px; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .btn-secondary { background: #4b5563; }
        .btn-warning { background: #d97706; }
        .btn-danger { background: #dc2626; }
        .btn-sm { padding: 6px 10px; font-size: 13px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border-bottom: 1px solid #e5e7eb; padding: 10px; text-align: left; }
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        nav[role="navigation"] {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        nav[role="navigation"] a,
        nav[role="navigation"] span.inline-flex {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            text-decoration: none;
            line-height: 1;
            font-size: 13px;
        }
        nav[role="navigation"] a:hover {
            background: #f3f4f6;
        }
        nav[role="navigation"] span[aria-current="page"] > span {
            background: #e5e7eb;
            color: #111827;
            font-weight: 600;
        }
        nav[role="navigation"] span[aria-disabled="true"] > span,
        nav[role="navigation"] span.inline-flex.cursor-not-allowed {
            color: #9ca3af;
            background: #f9fafb;
            cursor: not-allowed;
        }
        nav[role="navigation"] svg {
            width: 16px;
            height: 16px;
            display: inline-block;
            vertical-align: middle;
        }
        .muted { color: #6b7280; }
        .badge { padding: 3px 8px; border-radius: 999px; font-size: 12px; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #e0f2fe; color: #075985; }
        .badge-violet { background: #ede9fe; color: #5b21b6; }
        .badge-indigo { background: #e0e7ff; color: #3730a3; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
        .label { display:block; margin-bottom: 6px; font-weight: 600; }
        .mb-12 { margin-bottom: 12px; }
        .mb-16 { margin-bottom: 16px; }
        .mb-20 { margin-bottom: 20px; }
        .mt-4 { margin-top: 4px; }
        .mt-8 { margin-top: 8px; }
        .mt-14 { margin-top: 14px; }
        .flex { display:flex; gap:8px; align-items:center; }
        .inline-form { display: inline; }
        .row-between { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .heading-reset { margin: 0; }
        .heading-top-reset { margin-top: 0; }
        .form-inline { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .form-inline .input { flex: 1 1 220px; }
        .checkbox-row { display: flex; align-items: center; gap: 8px; margin-top: 28px; }
        .choice-group { display: flex; gap: 12px; flex-wrap: wrap; }
        .choice-item { display: flex; align-items: center; gap: 6px; }
        .text-strong { font-weight: 600; }
        .text-xs { font-size: 12px; }
        .text-sm { font-size: 13px; }
        .text-xl { font-size: 2rem; font-weight: 700; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-center { text-align: center; }
        .p-14 { padding: 14px; }
        .p-20 { padding: 20px; }
        .max-h-220 { max-height: 220px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            background: #111827;
            color: #fff;
            padding: 12px 16px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(12px);
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 9999;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 1024px) {
            .sidebar { width: 220px; }
            .main-area { margin-left: 220px; }
            .app-shell.collapsed .sidebar { width: 72px; }
            .app-shell.collapsed .main-area { margin-left: 72px; }
        }

        @media (max-width: 768px) {
            .grid,
            .grid-3 { grid-template-columns: 1fr; }
            .container { margin: 16px auto; }
            .topbar { padding: 10px 12px; }
            .btn { padding: 9px 12px; }
            .table th,
            .table td { white-space: nowrap; }
            .user-menu-button span:first-child { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        }

        @media (max-width: 640px) {
            .sidebar { width: 72px; }
            .main-area { margin-left: 72px; }
            .sidebar-brand-text,
            .menu-label { display: none; }
            .menu-icon { margin-right: 0; }
            .sidebar-link { text-align: center; }
            .user-menu-button { padding: 7px 9px; }
        }
    </style>
</head>
<body>
    @auth
        <div id="app-shell" class="app-shell">
            <aside class="sidebar">
                <div class="sidebar-brand">
                    <span>🔐</span>
                    <span class="sidebar-brand-text">SSO Portal</span>
                </div>

                <nav class="sidebar-nav">
                    <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="menu-icon">🏠</span>
                        <span class="menu-label">Dashboard</span>
                    </a>
                    @if(auth()->user()->hasPermission('manage_users'))
                        <a class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="menu-icon">👥</span><span class="menu-label">Users</span></a>
                        <a class="sidebar-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}"><span class="menu-icon">🏢</span><span class="menu-label">Departments</span></a>
                        <a class="sidebar-link {{ request()->routeIs('program-studies.*') ? 'active' : '' }}" href="{{ route('program-studies.index') }}"><span class="menu-icon">🎓</span><span class="menu-label">Programs</span></a>
                        <a class="sidebar-link {{ request()->routeIs('support-units.*') ? 'active' : '' }}" href="{{ route('support-units.index') }}"><span class="menu-icon">🧩</span><span class="menu-label">Support Units</span></a>
                        <a class="sidebar-link {{ request()->routeIs('profile-sync.*') ? 'active' : '' }}" href="{{ route('profile-sync.index') }}"><span class="menu-icon">🔄</span><span class="menu-label">Profile Sync</span></a>
                    @endif
                    @if(auth()->user()->hasPermission('manage_roles'))
                        <a class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><span class="menu-icon">🛡️</span><span class="menu-label">Roles</span></a>
                    @endif
                    @if(auth()->user()->hasPermission('manage_applications'))
                        <a class="sidebar-link {{ request()->routeIs('applications.*') ? 'active' : '' }}" href="{{ route('applications.index') }}"><span class="menu-icon">📦</span><span class="menu-label">Applications</span></a>
                    @endif
                    @if(auth()->user()->hasPermission('manage_sessions'))
                        <a class="sidebar-link {{ request()->routeIs('sessions.*') ? 'active' : '' }}" href="{{ route('sessions.index') }}"><span class="menu-icon">🔑</span><span class="menu-label">Sessions</span></a>
                        <a class="sidebar-link {{ request()->routeIs('tokens.*') ? 'active' : '' }}" href="{{ route('tokens.index') }}"><span class="menu-icon">🎫</span><span class="menu-label">Tokens</span></a>
                    @endif
                    @if(auth()->user()->hasPermission('view_audit_logs'))
                        <a class="sidebar-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}"><span class="menu-icon">🧾</span><span class="menu-label">Audit Logs</span></a>
                    @endif
                </nav>
            </aside>

            <div class="main-area">
                <div class="topbar">
                    <button id="sidebar-toggle" class="icon-btn" type="button">☰</button>

                    <div class="user-menu">
                        <button id="user-menu-button" class="user-menu-button" type="button">
                            <span>{{ auth()->user()->name }}</span>
                            <span>▾</span>
                        </button>

                        <div id="user-dropdown" class="user-dropdown">
                            <div class="dropdown-label">{{ auth()->user()->email }}</div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item" type="submit">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="container">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-error">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-error">
                            <ul class="plain-list">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    @else
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <ul class="plain-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    @endauth

    <div id="app-toast" class="toast"></div>

    <script>
        let toastTimeout;

        function showToast(message) {
            const toast = document.getElementById('app-toast');

            if (! toast) {
                return;
            }

            toast.textContent = message;
            toast.classList.add('show');

            clearTimeout(toastTimeout);

            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 1800);
        }

        const appShell = document.getElementById('app-shell');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const userMenuButton = document.getElementById('user-menu-button');
        const userDropdown = document.getElementById('user-dropdown');

        if (sidebarToggle && appShell) {
            sidebarToggle.addEventListener('click', () => {
                appShell.classList.toggle('collapsed');
            });
        }

        if (userMenuButton && userDropdown) {
            userMenuButton.addEventListener('click', () => {
                userDropdown.classList.toggle('show');
            });

            document.addEventListener('click', (event) => {
                const withinMenu = userMenuButton.contains(event.target) || userDropdown.contains(event.target);

                if (! withinMenu) {
                    userDropdown.classList.remove('show');
                }
            });
        }
    </script>
</body>
</html>
