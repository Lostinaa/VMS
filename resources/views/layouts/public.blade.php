<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Visitor Management System — Ethio Telecom' }}</title>
    <meta name="description" content="Submit a visit request to Ethio Telecom facilities">
    <link rel="icon" href="{{ asset('images/ethiotelecom-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Dark Theme (default) ── */
        :root, [data-theme="dark"] {
            --et-green: #4CAF50;
            --et-green-dark: #1B7D3A;
            --et-green-light: #6AAF35;
            --et-lime: #8BC34A;
            --et-blue: #4EB8D4;
            --et-bg: #0a1628;
            --et-surface: rgba(20, 35, 55, 0.8);
            --et-surface-alt: rgba(255,255,255,0.03);
            --et-border: rgba(76, 175, 80, 0.12);
            --et-text: #e2e8f0;
            --et-text-heading: #f1f5f9;
            --et-muted: #94a3b8;
            --et-input-bg: rgba(10, 22, 40, 0.6);
            --et-input-border: rgba(76, 175, 80, 0.12);
            --et-header-bg: rgba(10, 22, 40, 0.7);
            --et-select-bg: #1e293b;
            --et-placeholder: #475569;
            --et-footer-border: rgba(255,255,255,0.04);
            --et-grad-1: rgba(76, 175, 80, 0.12);
            --et-grad-2: rgba(78, 184, 212, 0.08);
            --et-grad-3: rgba(106, 175, 53, 0.06);
            --et-card-header-bg: linear-gradient(135deg, rgba(76, 175, 80, 0.06), rgba(78, 184, 212, 0.04));
            --et-error: #f87171;
            --et-toggle-bg: rgba(76, 175, 80, 0.1);
            --et-toggle-border: rgba(76, 175, 80, 0.2);
        }

        /* ── Light Theme ── */
        [data-theme="light"] {
            --et-bg: #f5f7fa;
            --et-surface: rgba(255, 255, 255, 0.95);
            --et-surface-alt: rgba(76, 175, 80, 0.04);
            --et-border: rgba(76, 175, 80, 0.15);
            --et-text: #1e293b;
            --et-text-heading: #0f172a;
            --et-muted: #64748b;
            --et-input-bg: #ffffff;
            --et-input-border: #d1d5db;
            --et-header-bg: rgba(255, 255, 255, 0.85);
            --et-select-bg: #ffffff;
            --et-placeholder: #9ca3af;
            --et-footer-border: #e5e7eb;
            --et-grad-1: rgba(76, 175, 80, 0.06);
            --et-grad-2: rgba(78, 184, 212, 0.04);
            --et-grad-3: rgba(106, 175, 53, 0.03);
            --et-card-header-bg: linear-gradient(135deg, rgba(76, 175, 80, 0.08), rgba(78, 184, 212, 0.05));
            --et-error: #dc2626;
            --et-toggle-bg: rgba(76, 175, 80, 0.08);
            --et-toggle-border: rgba(76, 175, 80, 0.2);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--et-bg);
            color: var(--et-text);
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }
        .bg-pattern {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse at 20% 50%, var(--et-grad-1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, var(--et-grad-2) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, var(--et-grad-3) 0%, transparent 50%);
            transition: background 0.3s;
        }
        .page-wrapper { position: relative; z-index: 1; min-height: 100vh; display: flex; flex-direction: column; }

        /* Header */
        .header {
            padding: 1rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--et-border);
            backdrop-filter: blur(16px);
            background: var(--et-header-bg);
            transition: background 0.3s, border-color 0.3s;
        }
        .logo-group { display: flex; align-items: center; gap: 0.85rem; }
        .logo-img { height: 38px; width: auto; object-fit: contain; }
        .logo-divider { width: 1px; height: 32px; background: rgba(76, 175, 80, 0.25); }
        .logo-text h1 { font-size: 1rem; font-weight: 700; color: var(--et-text-heading); }
        .logo-text p {
            font-size: 0.65rem; color: var(--et-green); letter-spacing: 0.06em;
            text-transform: uppercase; font-weight: 600;
        }

        .header-actions { display: flex; align-items: center; gap: 0.75rem; }

        /* Theme Toggle */
        .theme-toggle {
            width: 40px; height: 40px; border-radius: 10px;
            border: 1px solid var(--et-toggle-border);
            background: var(--et-toggle-bg);
            color: var(--et-muted);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.3s; font-size: 1.1rem;
        }
        .theme-toggle:hover {
            background: rgba(76, 175, 80, 0.15);
            color: var(--et-green);
            border-color: rgba(76, 175, 80, 0.4);
        }
        .theme-toggle .icon-sun { display: none; }
        .theme-toggle .icon-moon { display: block; }
        [data-theme="light"] .theme-toggle .icon-sun { display: block; }
        [data-theme="light"] .theme-toggle .icon-moon { display: none; }

        .admin-link {
            font-size: 0.8rem; color: var(--et-muted); text-decoration: none;
            padding: 0.5rem 1rem; border-radius: 8px;
            border: 1px solid rgba(76, 175, 80, 0.15);
            transition: all 0.2s;
        }
        .admin-link:hover {
            color: var(--et-green); border-color: rgba(76, 175, 80, 0.4);
            background: rgba(76, 175, 80, 0.06);
        }

        /* Main */
        .main-content { flex: 1; padding: 2rem; display: flex; justify-content: center; }
        .content-container { width: 100%; max-width: 720px; }

        /* Cards */
        .card {
            background: var(--et-surface);
            border: 1px solid var(--et-border);
            border-radius: 16px;
            backdrop-filter: blur(12px);
            overflow: hidden;
            transition: background 0.3s, border-color 0.3s;
        }
        [data-theme="light"] .card {
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04);
        }
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--et-border);
            background: var(--et-card-header-bg);
        }
        .card-header h2 { font-size: 1.25rem; font-weight: 700; color: var(--et-text-heading); }
        .card-header p { font-size: 0.85rem; color: var(--et-muted); margin-top: 0.25rem; }
        .card-body { padding: 2rem; }

        /* Form */
        .section-title {
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--et-green); margin-bottom: 1rem;
            padding-bottom: 0.5rem; border-bottom: 1px solid rgba(76, 175, 80, 0.15);
            display: flex; align-items: center; justify-content: space-between;
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .form-grid.full { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; gap: 0.35rem; }
        .form-group.span-2 { grid-column: span 2; }
        label { font-size: 0.8rem; font-weight: 500; color: var(--et-muted); }
        input, select, textarea {
            width: 100%; padding: 0.65rem 0.85rem;
            background: var(--et-input-bg);
            border: 1px solid var(--et-input-border);
            border-radius: 10px; color: var(--et-text-heading);
            font-family: 'Inter', sans-serif; font-size: 0.875rem;
            outline: none; transition: all 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--et-green);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.15);
        }
        input::placeholder, textarea::placeholder { color: var(--et-placeholder); }
        select option { background: var(--et-select-bg); color: var(--et-text-heading); }
        textarea { resize: vertical; min-height: 80px; }

        /* Errors */
        .field-error { font-size: 0.75rem; color: var(--et-error); margin-top: 2px; }

        /* Button */
        .btn-submit {
            width: 100%; padding: 0.85rem;
            background: linear-gradient(135deg, var(--et-green), var(--et-green-dark));
            border: none; border-radius: 12px;
            color: white; font-family: 'Inter', sans-serif;
            font-size: 0.95rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s;
            position: relative; overflow: hidden;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3); }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* Success */
        .success-card { text-align: center; padding: 3rem 2rem; }
        .success-icon {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.2), rgba(78, 184, 212, 0.15));
            border: 2px solid rgba(76, 175, 80, 0.3);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .success-icon svg { width: 40px; height: 40px; color: var(--et-green); }
        .success-card h2 { font-size: 1.5rem; font-weight: 700; color: var(--et-text-heading); margin-bottom: 0.5rem; }
        .success-card p { color: var(--et-muted); font-size: 0.9rem; line-height: 1.6; }
        .ref-code {
            display: inline-block; margin: 1.25rem 0;
            padding: 0.75rem 2rem; border-radius: 12px;
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.25);
            font-size: 1.4rem; font-weight: 700;
            color: var(--et-green); letter-spacing: 0.05em;
        }
        .btn-new {
            display: inline-block; margin-top: 1rem;
            padding: 0.7rem 2rem; border-radius: 10px;
            background: rgba(76, 175, 80, 0.08); border: 1px solid rgba(76, 175, 80, 0.2);
            color: var(--et-text); font-size: 0.85rem; font-weight: 500;
            text-decoration: none; cursor: pointer; transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-new:hover { background: rgba(76, 175, 80, 0.15); border-color: rgba(76, 175, 80, 0.35); }

        /* Footer */
        .footer {
            text-align: center; padding: 1.5rem;
            font-size: 0.75rem; color: var(--et-muted);
            border-top: 1px solid var(--et-footer-border);
            transition: border-color 0.3s;
        }

        /* Group visit styles */
        .visitor-card {
            background: var(--et-surface-alt); border: 1px solid var(--et-border);
            border-radius: 12px; padding: 1rem; margin-bottom: 0.75rem;
            transition: background 0.3s, border-color 0.3s;
        }
        .visitor-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 0.75rem; font-size: 0.85rem; font-weight: 600; color: var(--et-muted);
        }
        .btn-add {
            padding: 0.35rem 0.85rem; border-radius: 8px;
            border: 1px solid rgba(76, 175, 80, 0.3);
            background: rgba(76, 175, 80, 0.1); color: var(--et-green); font-size: 0.75rem;
            cursor: pointer; font-family: 'Inter', sans-serif; transition: all 0.2s;
        }
        .btn-add:hover { background: rgba(76, 175, 80, 0.2); }
        .btn-remove {
            width: 28px; height: 28px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 0.85rem;
            cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;
        }
        .btn-remove:hover { background: rgba(239, 68, 68, 0.2); }

        /* Responsive */
        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-group.span-2 { grid-column: span 1; }
            .main-content { padding: 1rem; }
            .card-body { padding: 1.25rem; }
            .header { padding: 0.75rem 1rem; }
            .logo-img { height: 30px; }
        }
    </style>
</head>
<body>
    <div class="bg-pattern"></div>
    <div class="page-wrapper">
        <header class="header">
            <div class="logo-group">
                <img src="{{ asset('images/ethiotelecom-logo.png') }}" alt="Ethio Telecom" class="logo-img">
                <div class="logo-divider"></div>
                <div class="logo-text">
                    <h1>Visitor Management</h1>
                    <p>Ethio Telecom VMS</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle dark/light mode">
                    <span class="icon-moon">🌙</span>
                    <span class="icon-sun">☀️</span>
                </button>
                <a href="/admin" class="admin-link">Staff Login →</a>
            </div>
        </header>

        <main class="main-content">
            {{ $slot }}
        </main>

        <footer class="footer">
            © {{ date('Y') }} Ethio Telecom — Visitor Management System. All rights reserved.
        </footer>
    </div>
    @livewireScripts
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('vms-theme', next);
        }
        // Restore saved preference
        (function() {
            const saved = localStorage.getItem('vms-theme');
            if (saved) {
                document.documentElement.setAttribute('data-theme', saved);
            }
        })();
    </script>
</body>
</html>
