<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('چوونەژوورەوەی پارێزراو — MarketLink B2B') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-core: #090b10;
            --bg-surface: #10141d;
            --bg-surface-elevated: #161c28;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-accent: rgba(99, 102, 241, 0.4);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --danger: #ef4444;
            --success: #10b981;
            --font-sans: 'Vazirmatn', 'Outfit', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-sans);
            background: var(--bg-core);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.08) 0%, transparent 45%);
            direction: rtl;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(16px);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            justify-content: center;
        }
        .brand-badge {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #6366f1, #10b981);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
        }
        .brand-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .login-desc {
            font-size: 14px;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 13px 20px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .security-badge svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-size: 13px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="brand-badge">M</div>
            <div class="brand-title">MarketLink B2B</div>
        </div>
        <p class="login-desc">{{ __('چوونەژوورەوەی پارێزراو بە سیستەمی بازرگانی دەستەجەمعی و فرە-کۆمپانیا') }}</p>

        <div style="margin-bottom: 20px;">
            <a href="{{ route('login.demo') }}" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:14px; background:linear-gradient(135deg, #10b981, #059669); color:#fff; font-weight:700; border-radius:12px; text-decoration:none; box-shadow:0 6px 20px rgba(16,185,129,0.35); font-size:14px;">
                <span>🚀</span>
                <span>{{ __('چوونەژوورەوەی ڕاستەوخۆ بەبێ وشەی نهێنی (1-Click Login)') }}</span>
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>⚠️ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">{{ __('ئیمەیڵی بەکارهێنەر') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="form-input" placeholder="user@company.krd" dir="ltr">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">{{ __('وشەی نهێنی') }}</label>
                <input type="password" id="password" name="password" required class="form-input" placeholder="••••••••" dir="ltr">
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary);">
                <input type="checkbox" name="remember" id="remember" style="accent-color: var(--primary);">
                <label for="remember">{{ __('بمپارێزە لەم ئامێرەدا (Remember Me)') }}</label>
            </div>

            <button type="submit" class="btn-submit">
                🔒 {{ __('چوونەژوورەوە بە سیستەم') }}
            </button>
        </form>

        <!-- DEMO ACCOUNTS QUICK-FILL -->
        <div style="margin-top: 20px; padding: 14px; background: rgba(255,255,255,0.03); border: 1px dashed var(--border-subtle); border-radius: 12px;">
            <div style="font-size: 12px; font-weight: 600; color: #a5b4fc; margin-bottom: 8px;">🔑 هەژمارە ئامادەکراوەکانی سیستەم (پاسۆرد: password):</div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <button type="button" onclick="fillLogin('admin@marketlink.krd', 'password')" style="background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: #c7d2fe; padding: 6px 10px; border-radius: 6px; font-size: 11.5px; cursor: pointer; text-align: right; display: flex; justify-content: space-between; align-items: center;">
                    <span>🛡️ بەڕێوەبەری پلاتفۆرم (Operator Admin)</span>
                    <code style="font-size: 10.5px; color: #818cf8;">admin@marketlink.krd</code>
                </button>
                <button type="button" onclick="fillLogin('supplier_1@marketlink.krd', 'password')" style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #a7f3d0; padding: 6px 10px; border-radius: 6px; font-size: 11.5px; cursor: pointer; text-align: right; display: flex; justify-content: space-between; align-items: center;">
                    <span>🏭 دابینکەر (Apex Wholesale Ltd)</span>
                    <code style="font-size: 10.5px; color: #34d399;">supplier_1@marketlink.krd</code>
                </button>
                <button type="button" onclick="fillLogin('retailer_2@marketlink.krd', 'password')" style="background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.3); color: #fbcfe8; padding: 6px 10px; border-radius: 6px; font-size: 11.5px; cursor: pointer; text-align: right; display: flex; justify-content: space-between; align-items: center;">
                    <span>🛒 مارکێت (Metro Supermarkets)</span>
                    <code style="font-size: 10.5px; color: #f472b6;">retailer_2@marketlink.krd</code>
                </button>
                <button type="button" onclick="fillLogin('driver@marketlink.krd', 'password')" style="background: rgba(56,189,248,0.15); border: 1px solid rgba(56,189,248,0.3); color: #bae6fd; padding: 6px 10px; border-radius: 6px; font-size: 11.5px; cursor: pointer; text-align: right; display: flex; justify-content: space-between; align-items: center;">
                    <span>🚚 شۆفێر / مەندوب (Delivery Driver)</span>
                    <code style="font-size: 10.5px; color: #38bdf8;">driver@marketlink.krd</code>
                </button>
            </div>
        </div>

        <a href="{{ route('marketlink.index') }}" class="back-link">
            ← {{ __('گەڕانەوە بۆ پەڕەی سەرەکی') }}
        </a>

        <div class="security-badge">
            <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
            <span>{{ __('سیستەمی پارێزراوی تەواو بە چاودێری و لۆگکردنی هەوڵەکان (Rate-Limited)') }}</span>
        </div>
    </div>

    <script>
        function fillLogin(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
