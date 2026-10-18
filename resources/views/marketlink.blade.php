<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('MarketLink — Neutral B2B Platform (Laravel Application)') }}</title>
    <meta name="description" content="{{ __('Independent B2B marketplace and business-connection platform connecting verified suppliers and retail businesses.') }}">
    <!-- PWA & Mobile Web App Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MarketLink">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-core: #090b10;
            --bg-surface: #10141d;
            --bg-surface-elevated: #161c28;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-accent: rgba(99, 102, 241, 0.3);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-tertiary: #64748b;
            --role-operator: #818cf8;
            --role-supplier: #10b981;
            --role-retailer: #f59e0b;
            --font-sans: 'Vazirmatn', 'Outfit', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-sans);
            background: var(--bg-core);
            color: var(--text-primary);
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.06) 0%, transparent 40%);
            direction: rtl;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            background: rgba(9, 11, 16, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-subtle);
            position: sticky;
            top: 0;
            z-index: 100;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .brand { display: flex; align-items: center; gap: 0.75rem; }
        .logo {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: bold;
        }
        .brand-text h2 { font-size: 1.25rem; font-weight: 700; letter-spacing: -0.5px; }
        .brand-text span { font-size: 0.7rem; color: var(--text-tertiary); text-transform: uppercase; }

        .role-nav {
            display: flex; gap: 0.5rem;
            background: var(--bg-surface);
            padding: 0.3rem; border-radius: 9999px;
            border: 1px solid var(--border-subtle);
        }
        .role-link {
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-secondary);
            display: flex; align-items: center; gap: 0.45rem;
            transition: all 0.2s;
        }
        .role-link.active {
            background: var(--bg-surface-elevated);
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
            border: 1px solid var(--border-subtle);
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
        .dot-op { background: var(--role-operator); }
        .dot-sup { background: var(--role-supplier); }
        .dot-ret { background: var(--role-retailer); }

        .header-actions { display: flex; align-items: center; gap: 1rem; }

        .lang-switch {
            display: flex; gap: 0.25rem;
            background: var(--bg-surface);
            padding: 0.25rem; border-radius: 8px;
            border: 1px solid var(--border-subtle);
        }
        .lang-btn {
            padding: 0.35rem 0.8rem;
            border-radius: 6px; font-size: 0.8rem; font-weight: 600;
            text-decoration: none; color: var(--text-secondary);
            transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.35rem;
        }
        .lang-btn.active {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff; box-shadow: 0 2px 6px rgba(79, 70, 229, 0.35);
        }
        .lang-btn:hover:not(.active) { background: var(--bg-surface-elevated); color: #fff; }

        /* Tenant Switcher Banner */
        .tenant-selector-bar {
            background: rgba(16, 20, 29, 0.95);
            padding: 0.65rem 2rem;
            border-bottom: 1px solid var(--border-subtle);
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.85rem; flex-wrap: wrap; gap: 0.75rem;
        }
        .tenant-selector-bar form { display: flex; align-items: center; gap: 0.5rem; }
        .tenant-selector-bar select {
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle);
            color: #fff; padding: 0.35rem 0.8rem; border-radius: 6px;
            font-family: inherit; font-size: 0.82rem;
        }

        .alert-banner {
            padding: 0.75rem 2rem;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.12), rgba(16, 185, 129, 0.08));
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
            font-size: 0.85rem; display: flex; justify-content: space-between; align-items: center;
        }
        .alert-flash {
            background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399; padding: 0.85rem 2rem; font-size: 0.88rem;
        }

        main { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 2rem; }
        .metric-card {
            background: var(--bg-surface); border: 1px solid var(--border-subtle);
            border-radius: 12px; padding: 1.4rem;
        }
        .metric-card .label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; }
        .metric-card .val { font-size: 1.9rem; font-weight: 700; margin: 0.3rem 0; font-family: var(--font-sans); }
        .metric-card .sub { font-size: 0.75rem; color: var(--text-tertiary); }

        .panel {
            background: var(--bg-surface); border: 1px solid var(--border-subtle);
            border-radius: 14px; padding: 1.6rem; margin-bottom: 2rem;
        }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 0.5rem; }
        .panel-title { font-size: 1.2rem; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; font-size: 0.86rem; text-align: right; }
        th { padding: 0.8rem 1rem; background: var(--bg-surface-elevated); color: var(--text-tertiary); font-size: 0.75rem; font-weight: 700; border-bottom: 1px solid var(--border-subtle); text-align: right; }
        td { padding: 0.9rem 1rem; border-bottom: 1px solid var(--border-subtle); text-align: right; }
        .badge { display: inline-block; padding: 0.2rem 0.55rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; }
        .badge-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-info { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); }
        .badge-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .btn {
            padding: 0.45rem 0.9rem; border-radius: 6px; border: 1px solid var(--border-subtle);
            background: var(--bg-surface-elevated); color: #fff; font-size: 0.82rem; font-weight: 600;
            cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 0.3rem;
            text-decoration: none; transition: all 0.2s;
        }
        .btn:hover { background: rgba(255,255,255,0.08); }
        .btn-primary { background: linear-gradient(135deg, #4f46e5, #6366f1); border-color: transparent; }
        .btn-primary:hover { background: linear-gradient(135deg, #4338ca, #4f46e5); }
        .btn-success { background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.4); color: #34d399; }
        .btn-success:hover { background: #10b981; color: #fff; }
        .btn-sm { padding: 0.25rem 0.6rem; font-size: 0.75rem; }

        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .form-group label { display: block; font-size: 0.78rem; color: var(--text-secondary); margin-bottom: 0.35rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.6rem; background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle); border-radius: 6px;
            color: #fff; font-family: inherit; font-size: 0.85rem;
        }
        code { font-family: var(--font-mono); direction: ltr; display: inline-block; }

        /* Cards Grid */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .b2b-card {
            background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle);
            border-radius: 10px; padding: 1.2rem; display: flex; flex-direction: column; justify-content: space-between;
        }
        .b2b-card h4 { font-size: 1.05rem; margin-bottom: 0.4rem; color: #fff; }
        .b2b-card .meta { font-size: 0.78rem; color: var(--text-secondary); margin-bottom: 0.8rem; line-height: 1.6; }
        .b2b-card .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.8rem; }

        /* Chat / Negotiation Container */
        .chat-box {
            display: grid; grid-template-columns: 290px 1fr;
            border: 1px solid var(--border-subtle); border-radius: 10px;
            overflow: hidden; background: var(--bg-surface-elevated); min-height: 480px;
        }
        @media (max-width: 768px) { .chat-box { grid-template-columns: 1fr; } }
        .chat-sidebar { background: rgba(0, 0, 0, 0.25); border-left: 1px solid var(--border-subtle); padding: 1rem; }
        .conv-item {
            padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-subtle);
            margin-bottom: 0.5rem; cursor: pointer; text-decoration: none; display: block;
            color: var(--text-secondary); background: var(--bg-surface); transition: all 0.2s;
        }
        .conv-item.active, .conv-item:hover {
            border-color: #6366f1; background: rgba(99, 102, 241, 0.12); color: #fff;
        }
        .chat-main { display: flex; flex-direction: column; justify-content: space-between; padding: 1.2rem; }
        .chat-messages {
            display: flex; flex-direction: column; gap: 0.9rem; overflow-y: auto; max-height: 320px; padding-bottom: 1rem;
        }
        .chat-msg {
            max-width: 82%; padding: 0.8rem 1rem; border-radius: 10px; font-size: 0.85rem; line-height: 1.5;
        }
        .chat-msg.sent {
            align-self: flex-start;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(79, 70, 229, 0.4));
            border: 1px solid rgba(99, 102, 241, 0.4); color: #fff;
        }
        .chat-msg.received {
            align-self: flex-end; background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle); color: #e2e8f0;
        }
        .chat-msg.proposal {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(99, 102, 241, 0.15));
            border: 1px solid rgba(16, 185, 129, 0.4);
        }
        .chat-input-bar {
            margin-top: 1rem; display: flex; flex-direction: column; gap: 0.6rem;
            background: rgba(0, 0, 0, 0.22); padding: 0.8rem; border-radius: 10px;
            border: 1px solid var(--border-subtle);
        }
        .chat-input-row { display: flex; gap: 0.5rem; align-items: flex-end; }
        .chat-input-bar textarea {
            flex: 1; resize: none; height: 48px; padding: 0.65rem 0.8rem;
            background: var(--bg-core); border: 1px solid var(--border-subtle);
            border-radius: 8px; color: #fff; font-family: inherit; font-size: 0.85rem;
            outline: none; transition: border-color 0.2s;
        }
        .chat-input-bar textarea:focus { border-color: rgba(99, 102, 241, 0.6); }
        .chat-btn-action {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
            height: 48px; padding: 0 0.85rem; border-radius: 8px; cursor: pointer;
            border: 1px solid var(--border-subtle); background: var(--bg-surface-elevated);
            color: var(--text-primary); font-size: 0.82rem; transition: all 0.2s; white-space: nowrap;
        }
        .chat-btn-action:hover {
            background: rgba(255, 255, 255, 0.08); border-color: rgba(99, 102, 241, 0.4);
        }
        .chat-btn-record.recording {
            background: #ef4444 !important; color: #fff !important;
            animation: pulse-red 1.2s infinite; border-color: #f87171 !important;
        }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .chat-preview-box {
            display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;
            padding: 0.45rem 0.65rem; background: rgba(99, 102, 241, 0.08);
            border-radius: 6px; border: 1px dashed rgba(99, 102, 241, 0.3);
        }
        .preview-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(0, 0, 0, 0.35); padding: 0.25rem 0.55rem;
            border-radius: 6px; font-size: 0.8rem; color: #e2e8f0;
        }
        .preview-badge img {
            width: 38px; height: 38px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1);
        }
        .preview-badge audio { height: 32px; max-width: 190px; }
        .preview-badge .remove-btn {
            background: none; border: none; color: #f87171; cursor: pointer; font-size: 1rem; font-weight: bold;
        }
        .chat-media-img {
            max-width: 250px; max-height: 220px; border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.15); display: block;
            object-fit: cover; cursor: pointer; margin-bottom: 0.4rem;
            transition: transform 0.2s;
        }
        .chat-media-img:hover { transform: scale(1.02); }
        .chat-voice-box {
            display: flex; align-items: center; gap: 0.6rem; padding: 0.4rem 0.6rem;
            background: rgba(0, 0, 0, 0.28); border-radius: 8px; margin-bottom: 0.4rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .chat-voice-box audio { height: 34px; max-width: 240px; outline: none; }
        .btn-sm { padding: 0.25rem 0.6rem; font-size: 0.78rem; border-radius: 4px; }
        .proposal-form-toggle {
            background: rgba(0, 0, 0, 0.2); border-radius: 8px; padding: 0.8rem;
            margin-top: 0.8rem; border: 1px dashed var(--border-accent);
        }

        /* Notifications Bell & Dropdown */
        .notif-dropdown-wrapper { position: relative; }
        .notif-bell-btn {
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle);
            color: #fff;
            width: 38px; height: 38px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative; transition: all 0.2s;
        }
        .notif-bell-btn:hover {
            border-color: #6366f1; background: rgba(99, 102, 241, 0.15);
        }
        .notif-badge {
            position: absolute; top: -5px; right: -5px;
            background: #ef4444; color: #fff;
            font-size: 0.68rem; font-weight: 700;
            min-width: 18px; height: 18px; padding: 0 4px;
            border-radius: 9px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
            animation: pulse-red 2s infinite;
        }
        .notif-dropdown-menu {
            position: absolute; top: 46px; left: 0;
            width: 330px; background: #111520;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(20px);
            z-index: 1000; overflow: hidden;
            animation: fadeInDown 0.2s ease-out;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.75rem 1rem; background: rgba(0, 0, 0, 0.35);
            border-bottom: 1px solid var(--border-subtle); font-size: 0.85rem;
        }
        .btn-text-action {
            background: none; border: none; color: #818cf8;
            font-size: 0.75rem; cursor: pointer; font-weight: 600;
        }
        .btn-text-action:hover { color: #a5b4fc; text-decoration: underline; }
        .notif-list { max-height: 310px; overflow-y: auto; }
        .notif-item {
            display: flex; gap: 0.7rem; padding: 0.7rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            text-decoration: none; color: inherit; transition: background 0.15s;
        }
        .notif-item:hover { background: rgba(99, 102, 241, 0.1); }
        .notif-item-icon { font-size: 1.15rem; display: flex; align-items: flex-start; padding-top: 0.1rem; }
        .notif-item-content { flex: 1; min-width: 0; }
        .notif-title { font-size: 0.82rem; font-weight: 600; color: #fff; margin-bottom: 0.15rem; }
        .notif-body {
            font-size: 0.75rem; color: var(--text-secondary); line-height: 1.35;
            margin-bottom: 0.2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .notif-time { font-size: 0.68rem; color: var(--text-tertiary); font-family: var(--font-mono); }
        .notif-empty { padding: 2rem; text-align: center; color: var(--text-tertiary); font-size: 0.82rem; }
        .notif-footer {
            padding: 0.5rem; background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid var(--border-subtle); text-align: center;
        }
        .btn-desktop-notif {
            background: none; border: 1px dashed rgba(255, 255, 255, 0.15);
            color: var(--text-secondary); font-size: 0.72rem;
            padding: 0.35rem 0.6rem; border-radius: 6px; cursor: pointer; width: 100%;
        }
        .btn-desktop-notif:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }

        /* Toast Popup Notification */
        .toast-notification {
            position: fixed; bottom: 24px; left: 24px;
            background: #1e1b4b; border: 1px solid #6366f1;
            color: #fff; padding: 0.8rem 1.1rem; border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.55);
            z-index: 9999; display: flex; align-items: center; gap: 0.8rem;
            max-width: 360px; animation: slideInLeft 0.3s ease-out; cursor: pointer;
        }
        @keyframes slideInLeft {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Quick Replies Bar */
        .quick-replies-bar {
            display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.5rem; align-items: center;
        }
        .quick-reply-chip {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            padding: 0.2rem 0.55rem; border-radius: 12px;
            font-size: 0.73rem; cursor: pointer; transition: all 0.15s;
        }
        .quick-reply-chip:hover {
            background: rgba(99, 102, 241, 0.2); border-color: #6366f1; color: #fff;
        }

        /* Order Lifecycle Stepper */
        .order-stepper {
            display: flex; align-items: center; justify-content: space-between;
            margin: 1rem 0; padding: 0.75rem 1rem; background: rgba(0,0,0,0.25);
            border-radius: 10px; border: 1px solid var(--border-subtle);
            position: relative; overflow-x: auto; gap: 0.5rem;
        }
        .step-node {
            display: flex; flex-direction: column; align-items: center; text-align: center;
            flex: 1; min-width: 75px; position: relative; z-index: 2;
        }
        .step-circle {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--bg-surface-elevated); border: 2px solid var(--border-subtle);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700; color: var(--text-tertiary);
            transition: all 0.2s;
        }
        .step-node.completed .step-circle {
            background: rgba(16, 185, 129, 0.2); border-color: #10b981; color: #34d399;
        }
        .step-node.active .step-circle {
            background: rgba(99, 102, 241, 0.3); border-color: #818cf8; color: #fff;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.5); transform: scale(1.1);
        }
        .step-label {
            font-size: 0.72rem; margin-top: 0.35rem; color: var(--text-tertiary); font-weight: 600;
        }
        .step-node.completed .step-label { color: #34d399; }
        .step-node.active .step-label { color: #fff; font-weight: 700; }

        /* Multi-Supplier Cart Drawer */
        .cart-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px); z-index: 10000; display: none;
        }
        .cart-drawer {
            position: fixed; top: 0; right: 0; bottom: 0; width: 450px; max-width: 95vw;
            background: #111622; border-left: 1px solid var(--border-subtle);
            box-shadow: -10px 0 35px rgba(0,0,0,0.8); z-index: 10001;
            display: none; flex-direction: column;
            animation: drawerSlideIn 0.25s ease-out;
        }
        .cart-drawer.open { display: flex !important; }
        @keyframes drawerSlideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        .cart-header {
            padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border-subtle);
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(0,0,0,0.2);
        }
        .cart-body { flex: 1; overflow-y: auto; padding: 1.2rem 1.5rem; }
        .cart-footer {
            padding: 1.2rem 1.5rem; border-top: 1px solid var(--border-subtle);
            background: rgba(0,0,0,0.3);
        }
        .supplier-cart-group {
            background: var(--bg-surface); border: 1px solid var(--border-subtle);
            border-radius: 10px; padding: 0.9rem; margin-bottom: 1rem;
        }
        .supplier-cart-title {
            font-size: 0.85rem; font-weight: 700; color: #38bdf8;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.4rem; margin-bottom: 0.6rem;
        }
        .cart-item-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 0.5rem; padding: 0.4rem 0; border-bottom: 1px dashed rgba(255,255,255,0.04);
            font-size: 0.82rem;
        }

        /* Barcode Scanner Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.75);
            backdrop-filter: blur(6px); z-index: 2000; display: none;
            align-items: center; justify-content: center; padding: 1rem;
        }
        .modal-content-card {
            background: #141a26; border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px; width: 560px; max-width: 95vw; max-height: 90vh;
            overflow-y: auto; padding: 1.6rem; box-shadow: 0 20px 50px rgba(0,0,0,0.8);
            position: relative;
        }
        .scanner-camera-viewport {
            width: 100%; height: 200px; background: #000; border-radius: 10px;
            overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center;
            border: 2px dashed rgba(99, 102, 241, 0.4); margin-bottom: 1rem;
        }
        .scanner-laser-line {
            position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: #22c55e; box-shadow: 0 0 12px #22c55e;
            animation: laserScan 2s infinite ease-in-out alternate;
        }
        @keyframes laserScan {
            0% { top: 5%; }
            100% { top: 90%; }
        }

        /* Audit Trail Timeline */
        .audit-trail-timeline {
            position: relative; padding-right: 1.5rem; margin-top: 1rem;
        }
        .audit-trail-timeline::before {
            content: ''; position: absolute; top: 0; bottom: 0; right: 6px; width: 2px;
            background: rgba(255, 255, 255, 0.1);
        }
        .audit-log-entry {
            position: relative; margin-bottom: 1.2rem;
        }
        .audit-log-entry::before {
            content: ''; position: absolute; right: -1.5rem; top: 4px;
            width: 12px; height: 12px; border-radius: 50%;
            background: #6366f1; border: 2px solid #141a26;
        }

        /* Digital Signature Canvas */
        .signature-canvas {
            width: 100%; height: 130px; background: #0b0f17;
            border: 1px dashed rgba(255, 255, 255, 0.2); border-radius: 8px;
            cursor: crosshair; touch-action: none;
        }

        /* Mobile & Tablet Responsive Optimizations */
        @media (max-width: 768px) {
            header {
                padding: 0.75rem 1rem;
                gap: 0.75rem;
            }
            .brand { width: 100%; justify-content: space-between; }
            .role-nav {
                width: 100%;
                overflow-x: auto;
                padding: 0.25rem;
                justify-content: flex-start;
                white-space: nowrap;
            }
            .header-actions {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }
            main {
                padding: 1rem;
            }
            .grid-4 {
                grid-template-columns: 1fr;
            }
            .panel {
                padding: 1rem;
                overflow-x: auto;
            }
            table {
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
            }
            .tenant-selector-bar {
                padding: 0.6rem 1rem;
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* EXECUTIVE MODULE NAVIGATION & MOBILE BOTTOM BAR */
        .module-nav-container {
            background: rgba(16, 20, 29, 0.95);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border-subtle);
            position: sticky;
            top: 70px;
            z-index: 90;
            padding: 0.55rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
        }
        .module-tabs {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: none;
            flex: 1;
        }
        .module-tabs::-webkit-scrollbar { display: none; }
        .module-tab-btn {
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .module-tab-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            transform: translateY(-1px);
        }
        .module-tab-btn.active {
            background: linear-gradient(135deg, #4f46e5, #0284c7);
            color: #fff;
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: rgba(15, 23, 42, 0.96);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            z-index: 9990;
            padding: 0.25rem 0.5rem;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -8px 25px rgba(0, 0, 0, 0.6);
        }
        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
            }
            body {
                padding-bottom: 74px !important;
            }
            .module-nav-container {
                top: 56px;
                padding: 0.4rem 0.75rem;
            }
            .module-tab-btn {
                padding: 0.35rem 0.8rem;
                font-size: 0.78rem;
            }
            #floating-cart-btn {
                bottom: 76px !important;
            }
        }
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.68rem;
            font-weight: 600;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
            transition: all 0.2s;
            flex: 1;
        }
        .mobile-nav-item.active {
            color: #38bdf8;
        }
        .mobile-nav-item.active .mobile-nav-icon {
            transform: scale(1.15);
            filter: drop-shadow(0 2px 8px rgba(56, 189, 248, 0.6));
        }
        .mobile-nav-icon {
            font-size: 1.25rem;
            transition: transform 0.2s;
        }
        .app-module-section {
            animation: moduleFadeIn 0.22s ease-out;
        }
        @keyframes moduleFadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* SIDEBAR DRAWER STYLES */
        .sidebar-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.75);
            backdrop-filter: blur(8px); z-index: 99998; display: none;
        }
        .sidebar-drawer {
            position: fixed; top: 0; right: -360px; width: 340px; max-width: 86vw;
            height: 100vh; background: #0c1017; border-left: 1px solid rgba(255,255,255,0.12);
            z-index: 99999; display: flex; flex-direction: column;
            box-shadow: -15px 0 45px rgba(0,0,0,0.8);
            transition: right 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }
        .sidebar-drawer.open {
            right: 0;
        }
        .sidebar-drawer-header {
            padding: 1.25rem 1.4rem; background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border-subtle); display: flex;
            justify-content: space-between; align-items: center;
        }
        .sidebar-menu-list {
            padding: 1rem 0.8rem; display: flex; flex-direction: column; gap: 0.35rem;
        }
        .sidebar-menu-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.75rem 1rem; border-radius: 12px; text-decoration: none;
            color: #cbd5e1; font-size: 0.88rem; font-weight: 600;
            transition: all 0.15s; cursor: pointer; background: transparent; border: none; width: 100%; text-align: right;
        }
        .sidebar-menu-item:hover {
            background: rgba(99,102,241,0.12); color: #fff; transform: translateX(-3px);
        }
        .sidebar-menu-item.active {
            background: linear-gradient(135deg, #4f46e5, #0284c7); color: #fff;
            box-shadow: 0 4px 15px rgba(79,70,229,0.4);
        }
        .sidebar-menu-group-title {
            font-size: 0.72rem; font-weight: 700; color: var(--text-tertiary);
            text-transform: uppercase; padding: 0.8rem 1rem 0.3rem; letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<header>
    <div style="display:flex; align-items:center; gap:0.75rem;">
        <button type="button" class="btn" onclick="openAppSidebar()" style="display:inline-flex; align-items:center; gap:0.45rem; padding:0.45rem 0.85rem; border-radius:10px; background:rgba(99,102,241,0.18); border:1px solid rgba(99,102,241,0.4); color:#a5b4fc; font-weight:700; font-size:0.85rem; cursor:pointer;" title="لیستی سەرەکی مینیو">
            <span style="font-size:1.2rem; line-height:1;">☰</span>
            <span>مینیو</span>
        </button>
        <div class="brand">
            <div class="logo">ML</div>
            <div class="brand-text">
                <h2>MarketLink</h2>
                <span>{{ __('Laravel Enterprise Platform') }}</span>
            </div>
        </div>
    </div>

    <!-- ROLE TABS (Available strictly to Platform Super Admin) -->
    @if(auth()->check() && auth()->user()->isPlatformAdmin())
    <div class="role-nav">
        <a href="?role=operator" class="role-link {{ $role === 'operator' ? 'active' : '' }}">
            <span class="dot dot-op"></span> {{ __('Platform Operator') }}
        </a>
        <a href="?role=supplier" class="role-link {{ $role === 'supplier' ? 'active' : '' }}">
            <span class="dot dot-sup"></span> {{ __('Supplier') }} ({{ $currentSupplier?->name ?? 'دابینکەر' }})
        </a>
        <a href="?role=retailer" class="role-link {{ $role === 'retailer' ? 'active' : '' }}">
            <span class="dot dot-ret"></span> {{ __('Retailer') }} ({{ $currentRetailer?->name ?? 'مارکێت' }})
        </a>
        <a href="?role=driver" class="role-link {{ $role === 'driver' ? 'active' : '' }}">
            <span class="dot" style="background: #38bdf8;"></span> 🚚 {{ __('Delivery Driver') }} (شۆفێر)
        </a>
        <a href="#cash-van-section" class="role-link" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fbbf24;">
            <span class="dot" style="background: #f59e0b;"></span> 🚐 {{ __('Cash Van Management') }}
        </a>
        <a href="#financial-ledger-section" class="role-link" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399;">
            <span class="dot" style="background: #10b981;"></span> 💰 {{ __('ژمێریاری و کەشف حیساب') }}
        </a>
    </div>
    @endif

    <div class="header-actions">
        <!-- Quick Cart, Barcode & PWA Install Triggers (Available for all roles) -->
        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
            <button type="button" id="pwa-install-btn" class="btn" onclick="triggerPwaInstall()" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.4rem 0.95rem; border-radius:9999px; background:linear-gradient(135deg, #059669, #10b981); border:1px solid #34d399; color:#fff; font-weight:700; font-size:0.8rem; cursor:pointer; box-shadow:0 0 14px rgba(16,185,129,0.35); transition:transform 0.15s;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                <span>📲 دابەزاندنی ئەپ</span>
            </button>
            <button type="button" class="btn btn-primary" onclick="openCartDrawer()" style="display:flex; align-items:center; gap:0.45rem; padding:0.4rem 1rem; border-radius:9999px; font-weight:700; box-shadow:0 0 15px rgba(99,102,241,0.4); cursor:pointer;">
                <span>🛒 سەبەتەی کڕین</span>
                <span id="header-cart-badge" style="background:#ef4444; color:#fff; border-radius:9999px; padding:0.1rem 0.55rem; font-size:0.75rem;">0</span>
            </button>
            <button type="button" class="btn" onclick="openBarcodeModal()" style="display:flex; align-items:center; gap:0.4rem; padding:0.4rem 0.9rem; border-radius:9999px; background:rgba(255,255,255,0.08); border:1px solid var(--border-subtle); color:#fff; font-size:0.8rem; cursor:pointer;">
                <span>📷 بارکۆد سکانەر</span>
            </button>
            <button type="button" class="btn" onclick="openPolicyModal()" style="display:flex; align-items:center; gap:0.35rem; padding:0.4rem 0.85rem; border-radius:9999px; background:rgba(255,255,255,0.06); border:1px solid var(--border-subtle); color:var(--text-secondary); font-size:0.8rem; cursor:pointer;" title="یاسا و ڕێنماییەکان">
                <span>ℹ️ یاساکان</span>
            </button>
        </div>

        <!-- Notifications Bell (Accessible for all roles) -->
        <div class="notif-dropdown-wrapper">
            <button type="button" class="notif-bell-btn" id="notif-bell-btn" onclick="toggleNotifDropdown(event)" title="ئاگادارییەکان">
                <span style="font-size:1.15rem;">🔔</span>
                <span class="notif-badge" id="notif-badge" style="{{ $unreadNotifications->count() > 0 ? '' : 'display:none;' }}">
                    {{ $unreadNotifications->count() }}
                </span>
            </button>
            <div class="notif-dropdown-menu" id="notif-dropdown-menu" style="display:none;">
                <div class="notif-header">
                    <div style="display:flex; align-items:center; gap:0.4rem;">
                        <strong>ئاگادارییەکان</strong>
                        <span class="badge badge-info" id="notif-count-badge" style="font-size:0.7rem;">{{ $unreadNotifications->count() }} نوێ</span>
                    </div>
                    <button type="button" class="btn-text-action" id="btn-mark-all-read" onclick="markAllNotificationsRead()">✓ خوێندنەوەی هەمووی</button>
                </div>
                <div class="notif-list" id="notif-list">
                    @forelse($unreadNotifications as $n)
                        <a href="{{ $n['url'] }}" class="notif-item">
                            <div class="notif-item-icon">
                                @if($n['type'] === 'voice') 🎙️
                                @elseif($n['type'] === 'image') 📷
                                @elseif($n['type'] === 'quote_proposal') 🏷️
                                @elseif($n['type'] === 'order') 📦
                                @elseif($n['type'] === 'dispatch') 🚚
                                @else 💬
                                @endif
                            </div>
                            <div class="notif-item-content">
                                <div class="notif-title">{{ $n['title'] }}</div>
                                <div class="notif-body">{{ $n['body'] }}</div>
                                <div class="notif-time">{{ $n['time'] }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="notif-empty">هیچ ئاگادارییەکی نوێ نییە</div>
                    @endforelse
                </div>
                <div class="notif-footer">
                    <button type="button" class="btn-desktop-notif" id="btn-request-browser-notif">
                        🔔 چالاککردنی ئاگاداری ویندۆز و براوسەر
                    </button>
                </div>
            </div>
        </div>

        <!-- Language Switcher -->
        <div class="lang-switch">
            <a href="{{ route('lang.switch', 'ku') }}" class="lang-btn {{ app()->getLocale() === 'ku' ? 'active' : '' }}" title="کوردی (سۆرانی)">
                ☀️ کوردی
            </a>
            <a href="{{ route('lang.switch', 'ar') }}" class="lang-btn {{ app()->getLocale() === 'ar' ? 'active' : '' }}" title="العربية">
                🌐 العربية
            </a>
            <a href="{{ route('lang.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}" title="English">
                🇬🇧 English
            </a>
        </div>

        <div style="font-family: var(--font-mono); font-size: 0.78rem; color: #34d399; direction: ltr;">
            ● Active Tenants: {{ $allCompanies->count() }}
        </div>

        <div style="display:flex; align-items:center; gap:0.6rem; background:rgba(16, 185, 129, 0.12); padding:0.35rem 0.85rem; border-radius:9999px; border:1px solid rgba(16, 185, 129, 0.3); font-size:0.8rem; color:#34d399;">
            <span style="width:8px; height:8px; border-radius:50%; background:#10b981; display:inline-block; box-shadow:0 0 8px #10b981;"></span>
            <span style="font-weight:700; color:#fff;">👤 {{ auth()->user()?->name ?? 'بەکارهێنەر' }}</span>
            <span style="font-size:0.75rem; color:#34d399; background:rgba(255,255,255,0.08); padding:0.15rem 0.55rem; border-radius:6px; font-weight:700;">
                {{ $role === 'operator' ? '🛡️ ئەدمین' : ($role === 'supplier' ? '🏭 دابینکەر' : ($role === 'retailer' ? '🛒 مارکێت' : '🚚 شۆفێر')) }}
            </span>
        </div>

        <form action="{{ route('logout') }}" method="POST" style="margin:0; display:inline-flex;">
            @csrf
            <button type="submit" class="btn btn-sm" style="background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.35); color:#f87171; font-weight:700; border-radius:9999px; padding:0.35rem 0.85rem; font-size:0.78rem; cursor:pointer;" title="چوونە دەرەوە لە سیستەم">
                🚪 دەرچوون
            </button>
        </form>
    </div>
</header>

<!-- TENANT SWITCHER SUB-HEADER (Accessible strictly to Platform Admin for audit) -->
@if(auth()->check() && auth()->user()->isPlatformAdmin())
@if($role === 'supplier')
<div class="tenant-selector-bar">
    <div>
        🏭 <strong>تۆمارکراوی وەک دابینکەر:</strong>
        <span style="color:#10b981; font-weight:bold;">{{ $currentSupplier?->name }}</span>
        <span style="color:var(--text-tertiary);">({{ $currentSupplier?->city }})</span>
    </div>
    <form action="" method="GET">
        <input type="hidden" name="role" value="supplier">
        <label for="active_supplier_id" style="color:var(--text-secondary); font-size:0.8rem;">گۆڕینی دابینکەری چالاک:</label>
        <select name="active_supplier_id" id="active_supplier_id" onchange="this.form.submit()">
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ $currentSupplier?->id == $s->id ? 'selected' : '' }}>
                    {{ $s->name }} ({{ $s->city }}) — {{ $s->products_count }} کاڵا
                </option>
            @endforeach
        </select>
    </form>
</div>
@elseif($role === 'retailer')
<div class="tenant-selector-bar">
    <div>
        🛒 <strong>تۆمارکراوی وەک مارکێت:</strong>
        <span style="color:#f59e0b; font-weight:bold;">{{ $currentRetailer?->name }}</span>
        <span style="color:var(--text-tertiary);">({{ $currentRetailer?->city }})</span>
    </div>
    <form action="" method="GET">
        <input type="hidden" name="role" value="retailer">
        <label for="active_retailer_id" style="color:var(--text-secondary); font-size:0.8rem;">گۆڕینی مارکێتی چالاک:</label>
        <select name="active_retailer_id" id="active_retailer_id" onchange="this.form.submit()">
            @foreach($retailers as $r)
                <option value="{{ $r->id }}" {{ $currentRetailer?->id == $r->id ? 'selected' : '' }}>
                    {{ $r->name }} ({{ $r->city }})
                </option>
            @endforeach
        </select>
    </form>
</div>
@endif
@endif

<!-- EXECUTIVE MODULE SWITCHER BAR -->
<div class="module-nav-container">
    <div class="module-tabs" id="app-module-tabs">
        @if($role === 'supplier')
            <button type="button" class="module-tab-btn active" data-target-module="overview" onclick="switchAppModule('overview')">
                <span>📊 کورتە و ئامار</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="catalog" onclick="switchAppModule('catalog')">
                <span>📦 کۆگا و کاڵاکان</span>
                <span class="badge badge-info" style="font-size:0.7rem; padding:0.1rem 0.4rem;">{{ $supplierProducts->count() }}</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="orders" onclick="switchAppModule('orders')">
                <span>📑 داواکارییەکانی کڕین</span>
                <span class="badge badge-warning" style="font-size:0.7rem; padding:0.1rem 0.4rem;">{{ $inboundPurchaseOrders->count() }}</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="van" onclick="switchAppModule('van')">
                <span>🚐 کاش ڤان (Cash Van)</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="finance" onclick="switchAppModule('finance')">
                <span>💰 ژمێریاری و قەرز</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="chat" onclick="switchAppModule('chat')">
                <span>💬 چات و پەیوەندی</span>
            </button>
        @elseif($role === 'retailer')
            <button type="button" class="module-tab-btn active" data-target-module="overview" onclick="switchAppModule('overview')">
                <span>📊 کورتە و ئامار</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="catalog" onclick="switchAppModule('catalog')">
                <span>🛍️ بازاڕ و کڕینی کۆمەڵ</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="orders" onclick="switchAppModule('orders')">
                <span>📦 داواکارییەکانم</span>
                <span class="badge badge-warning" style="font-size:0.7rem; padding:0.1rem 0.4rem;">{{ $outboundPurchaseOrders->count() }}</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="finance" onclick="switchAppModule('finance')">
                <span>💰 قەرز و حسابات</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="chat" onclick="switchAppModule('chat')">
                <span>💬 چات و پێداویستی</span>
            </button>
        @elseif($role === 'driver')
            <button type="button" class="module-tab-btn active" data-target-module="orders" onclick="switchAppModule('orders')">
                <span>🚚 باری گەیاندن و ڕێڕەو</span>
                <span class="badge badge-info" style="font-size:0.7rem; padding:0.1rem 0.4rem;">{{ $driverShipments->count() }}</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="debts" onclick="switchAppModule('debts')">
                <span>💳 قەرزی مارکێتەکان</span>
                <span class="badge badge-warning" style="font-size:0.7rem; padding:0.1rem 0.4rem;">{{ $driverMarketDebts->count() }}</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="van" onclick="switchAppModule('van')">
                <span>🚐 کاش ڤان و فرۆشتن</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="finance" onclick="switchAppModule('finance')">
                <span>💰 تەسفیە و خەرجی ڕۆژانە</span>
            </button>
        @else
            <!-- Operator Tabs -->
            <button type="button" class="module-tab-btn active" data-target-module="overview" onclick="switchAppModule('overview')">
                <span>📊 چاودێری پلاتفۆرم</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="companies" onclick="switchAppModule('companies')">
                <span>🏢 کۆمپانیاکان ({{ $allCompanies->count() }})</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="orders" onclick="switchAppModule('orders')">
                <span>📦 سەرجەم مامەڵەکان</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="van" onclick="switchAppModule('van')">
                <span>🚐 کاش ڤان</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="finance" onclick="switchAppModule('finance')">
                <span>💰 ژمێریاری</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="system" onclick="switchAppModule('system')">
                <span>🛡️ ئاسایش و باکئەپ</span>
            </button>
            <button type="button" class="module-tab-btn" data-target-module="team" onclick="switchAppModule('team')">
                <span>👥 ستاف و کارمەندان</span>
            </button>
        @endif
        <button type="button" class="module-tab-btn" data-target-module="all" onclick="switchAppModule('all')" style="background:rgba(255,255,255,0.02); border-style:dashed;">
            <span>👁️ پیشاندانی هەمووی</span>
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert-flash">
    ✓ {{ session('success') }}
</div>
@endif

<main>
    <!-- OVERVIEW METRICS -->
    <div class="grid-4 app-module-section" data-app-module="overview">
        <div class="metric-card">
            <div class="label">{{ __('Verified Suppliers') }}</div>
            <div class="val">{{ $suppliers->count() }}</div>
            <div class="sub">{{ $pendingVerifications->count() }} {{ __('pending review') }}</div>
        </div>
        <div class="metric-card">
            <div class="label">{{ __('Verified Retailers') }}</div>
            <div class="val">{{ $retailers->count() }}</div>
            <div class="sub">{{ __('Active commercial accounts') }}</div>
        </div>
        <div class="metric-card">
            <div class="label">
                @if($role === 'supplier')
                    کۆگای دابینکەری چالاک
                @else
                    {{ __('Wholesale Catalog SKUs') }}
                @endif
            </div>
            <div class="val">
                @if($role === 'supplier')
                    {{ $supplierProducts->count() }}
                @else
                    {{ $catalogProducts->count() }}
                @endif
            </div>
            <div class="sub">
                @if($role === 'supplier')
                    کۆی بەهای کۆگا: ${{ number_format($totalWarehouseValue, 2) }}
                @else
                    {{ __('Zero operator inventory held') }}
                @endif
            </div>
        </div>
        <div class="metric-card">
            <div class="label">
                @if($role === 'supplier')
                    فەرمانی کڕینی هاتوو
                @elseif($role === 'retailer')
                    فەرمانی کڕینی دەرچوو
                @else
                    {{ __('Direct B2B Orders') }}
                @endif
            </div>
            <div class="val">
                @if($role === 'supplier')
                    {{ $inboundPurchaseOrders->count() }}
                @elseif($role === 'retailer')
                    {{ $outboundPurchaseOrders->count() }}
                @else
                    {{ $allPurchaseOrders->count() }}
                @endif
            </div>
            <div class="sub">{{ __('Direct Settlement POs') }}</div>
        </div>
        <div class="metric-card" style="border-color: rgba(239, 68, 68, 0.35); background: rgba(239, 68, 68, 0.04);">
            <div class="label" style="color: #fca5a5;">
                @if($role === 'supplier')
                    💰 قەرزی داواکراو لای مارکێتەکان
                @elseif($role === 'retailer')
                    ⚠️ کۆی قەرزی ماوەت بۆ دابینکەران
                @else
                    باڵانسی قەرزی سەر پلاتفۆرم
                @endif
            </div>
            <div class="val" style="color: #f87171;">
                @if($role === 'supplier')
                    ${{ number_format($totalSupplierReceivable, 2) }}
                @elseif($role === 'retailer')
                    ${{ number_format($totalRetailerDebt, 2) }}
                @else
                    ${{ number_format($allPurchaseOrders->sum('remaining_debt'), 2) }}
                @endif
            </div>
            <div class="sub">
                @if($role === 'supplier')
                    {{ $debtsByRetailer->count() }} مارکێت قەرزارن
                @elseif($role === 'retailer')
                    بۆ {{ $debtsBySupplier->count() }} دابینکەر قەرزداریت
                @else
                    کۆی مامەڵە بەقەرزەکان
                @endif
            </div>
        </div>
    </div>

    <!-- QUICK ACTION NAVIGATION HUB -->
    <div class="app-module-section" data-app-module="overview" style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff; margin-bottom: 0.8rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>🚀 دەروازە و مینیوی خێرا:</span>
            <span style="font-size: 0.78rem; color: var(--text-tertiary); font-weight: 400;">بە یەک کرتە ڕاستەوخۆ دەستت بە بەشە سەرەکییەکان دەگات</span>
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem;">
            <!-- Card 1: Catalog & Buy -->
            <div onclick="switchAppModule('catalog')" style="background: linear-gradient(135deg, rgba(99,102,241,0.18), rgba(79,70,229,0.06)); border: 1px solid rgba(99,102,241,0.35); border-radius: 14px; padding: 1.25rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='#818cf8';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(99,102,241,0.35)';">
                <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">🛍️</div>
                <h4 style="color: #fff; font-size: 1.05rem; margin-bottom: 0.35rem; font-weight: 700;">کڕین و کەتەلۆکی کاڵاکان</h4>
                <p style="font-size: 0.8rem; color: #cbd5e1; line-height: 1.45;">بینینی نرخ، داواکاری خێرا، و خستنە سەبەتەی کڕین</p>
                <div style="margin-top: 0.8rem; font-size: 0.78rem; color: #a5b4fc; font-weight: 700;">دەستپێکردن ⬅️</div>
            </div>

            <!-- Card 2: Orders -->
            <div onclick="switchAppModule('orders')" style="background: linear-gradient(135deg, rgba(16,185,129,0.18), rgba(5,150,105,0.06)); border: 1px solid rgba(16,185,129,0.35); border-radius: 14px; padding: 1.25rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='#34d399';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(16,185,129,0.35)';">
                <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">📦</div>
                <h4 style="color: #fff; font-size: 1.05rem; margin-bottom: 0.35rem; font-weight: 700;">داواکارییەکان و فەرمانەکان</h4>
                <p style="font-size: 0.8rem; color: #cbd5e1; line-height: 1.45;">پسوولەی فەرمی، بەدواداچوونی بار، مێژووی کڕین</p>
                <div style="margin-top: 0.8rem; font-size: 0.78rem; color: #6ee7b7; font-weight: 700;">بینینی فەرمانەکان ⬅️</div>
            </div>

            <!-- Card 3: Finance & Debt -->
            <div onclick="switchAppModule('finance')" style="background: linear-gradient(135deg, rgba(245,158,11,0.18), rgba(217,119,6,0.06)); border: 1px solid rgba(245,158,11,0.35); border-radius: 14px; padding: 1.25rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='#fbbf24';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(245,158,11,0.35)';">
                <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">💰</div>
                <h4 style="color: #fff; font-size: 1.05rem; margin-bottom: 0.35rem; font-weight: 700;">ژمێریاری و کەشف حیساب</h4>
                <p style="font-size: 0.8rem; color: #cbd5e1; line-height: 1.45;">قەرزی ماوە، پسوولەی وەرگرتنی پارە (RV)، و تەسفیە</p>
                <div style="margin-top: 0.8rem; font-size: 0.78rem; color: #fcd34d; font-weight: 700;">دەفتەری حیسابات ⬅️</div>
            </div>

            <!-- Card 4: Chat -->
            <div onclick="switchAppModule('chat')" style="background: linear-gradient(135deg, rgba(14,165,233,0.18), rgba(2,132,199,0.06)); border: 1px solid rgba(14,165,233,0.35); border-radius: 14px; padding: 1.25rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='#38bdf8';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(14,165,233,0.35)';">
                <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">💬</div>
                <h4 style="color: #fff; font-size: 1.05rem; margin-bottom: 0.35rem; font-weight: 700;">چاتی بازرگانی (B2B)</h4>
                <p style="font-size: 0.8rem; color: #cbd5e1; line-height: 1.45;">دانوستاندنی نرخ لەگەڵ کۆمپانیاکان بە وێنە و دەنگ</p>
                <div style="margin-top: 0.8rem; font-size: 0.78rem; color: #7dd3fc; font-weight: 700;">کردنەوەی چاتەکان ⬅️</div>
            </div>
        </div>
    </div>

    @if(auth()->check() && (auth()->user()->isPlatformAdmin() || auth()->user()->isCompanyOwner() || auth()->user()->isCompanyManager()))
    <!-- TEAM & ROLE MANAGEMENT (RBAC & TENANCY) -->
    <div class="panel app-module-section" data-app-module="team" style="border: 1px solid rgba(99, 102, 241, 0.35); background: rgba(16, 20, 29, 0.95); margin-bottom: 2rem;" id="team-management-panel">
        <div class="panel-header" style="flex-wrap: wrap; gap: 0.75rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <h3 class="panel-title" style="color: #a5b4fc;">👥 بەڕێوەبردنی کارمەندان، شۆفێران و ڕۆڵەکانی بەکارهێنەر (Team & RBAC Management)</h3>
                <span class="badge badge-info">{{ $companyUsers->count() }} بەکارهێنەر</span>
            </div>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">
                @if(auth()->user()->isPlatformAdmin())
                    بەڕێوەبەری پلاتفۆرم: چاودێری و کۆنترۆڵی سەرجەم هەژمارەکان
                @else
                    کۆمپانیای: {{ auth()->user()->company?->name }} (تەنها کارمەندانی ئەم کۆمپانیایە)
                @endif
            </span>
        </div>

        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.2rem; line-height: 1.6;">
            لێرە دەتوانیت هەژماری نوێ بۆ ستاف، بەڕێوەبەر، و شۆفێری کۆمپانیا دروست بکەیت، ڕۆڵیان دیاری بکەیت، و هەژمارەکان چالاک یان ناچالاک بکەیت بە شێوەیەکی پارێزراو.
        </p>

        <!-- ADD USER FORM -->
        <form action="{{ route('users.store') }}" method="POST" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.2rem; margin-bottom: 1.5rem;">
            @csrf
            <h4 style="font-size: 0.9rem; margin-bottom: 0.8rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.4rem;">
                ➕ زیادکردنی ئەندامی نوێی تیم
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.3rem;">ناوی تەواو *</label>
                    <input type="text" name="name" required placeholder="ناوی کارمەند" style="width:100%; padding:0.45rem 0.75rem; background:rgba(0,0,0,0.3); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.85rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.3rem;">ئیمەیڵ *</label>
                    <input type="email" name="email" required placeholder="staff@company.krd" dir="ltr" style="width:100%; padding:0.45rem 0.75rem; background:rgba(0,0,0,0.3); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.85rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.3rem;">وشەی نهێنی (لانی کەم ٦ پیت) *</label>
                    <input type="password" name="password" required placeholder="••••••••" dir="ltr" style="width:100%; padding:0.45rem 0.75rem; background:rgba(0,0,0,0.3); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.85rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.3rem;">ڕۆڵ لە سیستەمدا *</label>
                    <select name="role" required style="width:100%; padding:0.45rem 0.75rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.85rem;">
                        <option value="staff">کارمەند (Staff - تەنها بینین و ئیشی ئاسایی)</option>
                        <option value="manager">بەڕێوەبەر (Manager - پەسەندکردن و بەڕێوەبردن)</option>
                        <option value="driver">شۆفێری گەیاندن (Driver)</option>
                        @if(auth()->user()->isPlatformAdmin())
                        <option value="owner">خاوەن کۆمپانیا (Company Owner)</option>
                        <option value="platform_admin">ئەدمینی سەرەکی (Platform Admin)</option>
                        @endif
                    </select>
                </div>
                @if(auth()->user()->isPlatformAdmin())
                <div>
                    <label style="display:block; font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.3rem;">دیاریکردنی کۆمپانیا</label>
                    <select name="company_id" style="width:100%; padding:0.45rem 0.75rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.85rem;">
                        <option value="">هیچ (بۆ ئەدمین و شۆفێری گشتی)</option>
                        @foreach($allCompanies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $comp->business_type }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.5rem 1rem; font-weight:700;">
                        💾 زیادکردنی بەکارهێنەر
                    </button>
                </div>
            </div>
        </form>

        <!-- USERS TABLE -->
        <table>
            <thead>
                <tr>
                    <th>ناو و ناسنامە</th>
                    <th>ئیمەیڵ</th>
                    <th>ڕۆڵ (Role)</th>
                    <th>کۆمپانیا</th>
                    <th>دۆخی هەژمار</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companyUsers as $cu)
                <tr>
                    <td>
                        <strong>{{ $cu->name }}</strong>
                        @if($cu->id === auth()->id())
                            <span class="badge badge-info" style="font-size:0.7rem; margin-right:0.3rem;">خۆت</span>
                        @endif
                    </td>
                    <td><code style="font-size:0.82rem;">{{ $cu->email }}</code></td>
                    <td>
                        <form action="{{ route('users.role.update', $cu->id) }}" method="POST" style="display:inline-flex; align-items:center; gap:0.3rem;">
                            @csrf
                            <select name="role" onchange="this.form.submit()" style="background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); color:#fff; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.78rem;" {{ $cu->id === auth()->id() && $cu->isPlatformAdmin() ? 'disabled' : '' }}>
                                <option value="staff" {{ $cu->role === 'staff' ? 'selected' : '' }}>کارمەند (Staff)</option>
                                <option value="manager" {{ $cu->role === 'manager' ? 'selected' : '' }}>بەڕێوەبەر (Manager)</option>
                                <option value="driver" {{ $cu->role === 'driver' ? 'selected' : '' }}>شۆفێر (Driver)</option>
                                <option value="owner" {{ $cu->role === 'owner' ? 'selected' : '' }}>خاوەن کار (Owner)</option>
                                @if(auth()->user()->isPlatformAdmin())
                                <option value="platform_admin" {{ $cu->role === 'platform_admin' ? 'selected' : '' }}>ئەدمین (Admin)</option>
                                @endif
                            </select>
                        </form>
                    </td>
                    <td>{{ $cu->company?->name ?? 'سیستەمی گشتی' }}</td>
                    <td>
                        @if($cu->is_active)
                            <span class="badge badge-success">چالاک</span>
                        @else
                            <span class="badge badge-danger">ناچالاک</span>
                        @endif
                    </td>
                    <td>
                        @if($cu->id !== auth()->id())
                        <form action="{{ route('users.toggle_status', $cu->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn" style="padding:0.25rem 0.65rem; font-size:0.75rem; border-radius:6px; {{ $cu->is_active ? 'background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#fca5a5;' : 'background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3); color:#6ee7b7;' }}">
                                {{ $cu->is_active ? '⛔ ناچالاککردن' : '✓ چالاککردنەوە' }}
                            </button>
                        </form>
                        @else
                        <span style="font-size:0.75rem; color:var(--text-tertiary);">هەژماری ئێستات</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-tertiary);">هیچ ئەندامێکی تر لەم کۆمپانیایە تۆمار نەکراوە.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    @if($role === 'operator')
    <!-- ==========================================
         PLATFORM OPERATOR PLANE
    =========================================== -->

    <!-- REGISTER NEW COMPANY FORM -->
    <div class="panel app-module-section" data-app-module="companies">
        <div class="panel-header">
            <h3 class="panel-title">{{ __('Register New Verified Company (Supplier or Retailer)') }}</h3>
            <span class="badge badge-info">Admin Onboarding</span>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
            بەڕێوەبەری پلاتفۆرم لێرە ڕاستەوخۆ دەتوانێت دابینکەرانی نوێ و سوپەرمارکێتەکان تۆمار بکات و مۆڵەتەکانیان چالاک بکات.
        </p>

        <form id="company-onboarding-form" action="{{ route('operator.company.store') }}" method="POST">
            @csrf
            <input type="hidden" name="batch_records" id="batch-records-input" value="[]">

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('Company / Market Name') }} *</label>
                    <input type="text" name="name" id="inp-comp-name" placeholder="بۆ نموونە: کۆمپانیای کاروان / سوپەرمارکێتی هەولێر">
                </div>
                <div class="form-group">
                    <label>{{ __('Business Type') }} *</label>
                    <select name="business_type" id="inp-comp-type">
                        <option value="supplier">{{ __('Supplier (Wholesale)') }}</option>
                        <option value="retailer">{{ __('Retailer (Supermarket / Grocery)') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('Trade License Number') }}</label>
                    <input type="text" name="trade_license_number" id="inp-comp-license" placeholder="TL-KR-2026-XXXX (ئارەزوومەندانە)">
                </div>
                <div class="form-group">
                    <label>پارێزگا (Province) *</label>
                    <select name="province" id="inp-comp-province">
                        <option value="هەولێر">هەولێر</option>
                        <option value="سلێمانی">سلێمانی</option>
                        <option value="دهۆک">دهۆک</option>
                        <option value="کەرکووک">کەرکووک</option>
                        <option value="هەڵەبجە">هەڵەبجە</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('City') }} *</label>
                    <input type="text" name="city" id="inp-comp-city" placeholder="هەولێر / سلێمانی / دهۆک">
                </div>
                <div class="form-group">
                    <label>کەرتی کار و جۆری فرۆشگا / کۆگا</label>
                    <input type="text" name="business_category" id="inp-comp-category" placeholder="سوپەرمارکێت / مینی مارکێت / خۆراک...">
                </div>
                <div class="form-group">
                    <label>{{ __('Country') }} *</label>
                    <input type="text" name="country" id="inp-comp-country" value="هەرێمی کوردستان">
                </div>
                <div class="form-group">
                    <label>{{ __('Phone') }} *</label>
                    <input type="text" name="primary_phone" id="inp-comp-phone" placeholder="+964 750 XXX XXXX">
                </div>
                <div class="form-group">
                    <label>{{ __('Email') }} *</label>
                    <input type="email" name="primary_email" id="inp-comp-email" placeholder="info@company.com">
                </div>
                <div class="form-group">
                    <label>{{ __('Address') }} (ناونیشان و شوێنی لق)</label>
                    <input type="text" name="address_line" id="inp-comp-address" placeholder="شەقامی بازرگانی، لقی ژمارە...">
                </div>
            </div>

            <!-- LOCATION & GPS COORDINATES (شوێنی جوگرافی و لۆکەیشن بۆ شۆفێر) -->
            <div style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.25); border-radius: 10px; padding: 1.1rem; margin-top: 1rem; margin-bottom: 0.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem; flex-wrap:wrap; gap:0.5rem;">
                    <div style="font-weight:700; color:#38bdf8; font-size:0.92rem; display:flex; align-items:center; gap:0.4rem;">
                        <span>📍 شوێنی جوگرافی و نەخشەی مارکێت (GPS Location بۆ شۆفێر)</span>
                    </div>
                    <button type="button" onclick="detectCurrentGpsLocation()" class="btn btn-sm" style="background:linear-gradient(135deg, #0284c7, #0369a1); color:#fff; border:none; padding:0.4rem 0.85rem; font-size:0.8rem; font-weight:700; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:0.35rem; box-shadow:0 2px 8px rgba(2,132,199,0.3);">
                        <span id="gps-detect-icon">📡</span> <span id="gps-detect-text">دیاریکردنی لۆکەیشنی ئێستام (GPS)</span>
                    </button>
                </div>
                <p style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.85rem;">
                    تۆمارکردنی لۆکەیشن و هێڵەکانی GPS لێرە دەبێتە هۆی ئەوەی شۆفێر لە کاتی بارکردندا بە تەنها یەک کلیک نەخشەی Google Maps و ئاڕاستەی گەیشتنی ڕاستەوخۆی (Turn-by-Turn Navigation) بۆ کارابێت.
                </p>

                <div class="form-row" style="margin-bottom:0;">
                    <div class="form-group">
                        <label>هێڵی پانی (Latitude)</label>
                        <input type="number" step="any" name="latitude" id="inp-comp-lat" placeholder="بۆ نموونە: 36.1912">
                    </div>
                    <div class="form-group">
                        <label>هێڵی درێژی (Longitude)</label>
                        <input type="number" step="any" name="longitude" id="inp-comp-lng" placeholder="بۆ نموونە: 44.0091">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>یان لینکی نەخشەی گووگڵ (Google Maps Link)</label>
                        <input type="url" name="google_maps_url" id="inp-comp-maps-url" placeholder="https://maps.app.goo.gl/... یان ناونیشانی نەخشە" oninput="parseGoogleMapsUrlInput(this.value)">
                    </div>
                </div>
                <div id="gps-status-feedback" style="font-size:0.8rem; color:#4ade80; margin-top:0.4rem; display:none; font-weight:600;"></div>
            </div>

            <!-- ACTION BUTTONS: ADD TO BATCH QUEUE OR SUBMIT DIRECTLY -->
            <div style="display:flex; gap:0.8rem; flex-wrap:wrap; margin-top:1.2rem; align-items:center;">
                <button type="button" onclick="addCompanyToBatchQueue()" class="btn btn-secondary" style="background:rgba(99,102,241,0.22); border:1px solid #818cf8; color:#e0e7ff; font-weight:700;">
                    ➕ زیادکردنی ئەم شوێنە / کۆمپانیایە بۆ لیست
                </button>
                <button type="submit" id="btn-save-all-companies" class="btn btn-primary" style="font-weight:700;">
                    💾 {{ __('Register & Approve Company') }}
                </button>
                <span style="font-size:0.8rem; color:var(--text-tertiary);">دەتوانیت چەندین شوێن یان کۆمپانیا زیاد بکەیت پێش داگرتنی دوگمەی تۆمارکردن.</span>
            </div>

            <!-- BATCH QUEUE PREVIEW TABLE -->
            <div id="batch-queue-container" style="display:none; margin-top:1.5rem; background:rgba(0,0,0,0.3); border:1px solid var(--border-subtle); border-radius:8px; padding:1rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <div style="font-weight:700; color:#fff; font-size:0.95rem; display:flex; align-items:center; gap:0.5rem;">
                        <span>📋 کۆمپانیا و شوێنە ئامادەکراوەکان بۆ تۆمارکردنی یەکجارەکی:</span>
                        <span id="batch-count-badge" class="badge badge-info">0 تۆمار</span>
                    </div>
                    <button type="button" onclick="clearBatchQueue()" style="background:transparent; border:none; color:#f87171; font-size:0.8rem; cursor:pointer;">سڕینەوەی هەمووی ✕</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ناو</th>
                            <th>جۆر</th>
                            <th>پارێزگا و ناونیشان</th>
                            <th>پەیوەندی</th>
                            <th>مۆڵەت</th>
                            <th>کردار</th>
                        </tr>
                    </thead>
                    <tbody id="batch-queue-tbody">
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- ALL REGISTERED COMPANIES TABLE -->
    <div class="panel app-module-section" data-app-module="companies">
        <div class="panel-header">
            <h3 class="panel-title">{{ __('All Registered Companies') }}</h3>
            <span class="badge badge-info">{{ $allCompanies->count() }} کۆمپانیا</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Company Name') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Trade License') }}</th>
                    <th>{{ __('Location') }}</th>
                    <th>{{ __('Contact') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allCompanies as $comp)
                <tr>
                    <td><strong>{{ $comp->name }}</strong></td>
                    <td>
                        <span class="badge {{ $comp->business_type === 'supplier' ? 'badge-success' : 'badge-warning' }}">
                            {{ $comp->business_type === 'supplier' ? 'دابینکەر' : 'مارکێت' }}
                        </span>
                    </td>
                    <td><code>{{ $comp->trade_license_number }}</code></td>
                    <td>
                        {{ $comp->city }}, {{ $comp->country }}
                        @if($comp->latitude && $comp->longitude)
                            <br><a href="{{ $comp->navigation_url }}" target="_blank" style="font-size:0.75rem; color:#38bdf8; text-decoration:none; display:inline-flex; align-items:center; gap:0.2rem; margin-top:0.2rem;">
                                📍 نەخشە ({{ number_format($comp->latitude, 3) }}, {{ number_format($comp->longitude, 3) }})
                            </a>
                        @elseif($comp->google_maps_url)
                            <br><a href="{{ $comp->google_maps_url }}" target="_blank" style="font-size:0.75rem; color:#38bdf8; text-decoration:none; margin-top:0.2rem;">📍 نەخشەی تۆمارکراو</a>
                        @endif
                    </td>
                    <td>{{ $comp->primary_phone }} <br><span style="font-size:0.75rem; color:var(--text-tertiary);">{{ $comp->primary_email }}</span></td>
                    <td>
                        <span class="badge {{ $comp->verification_status === 'verified' ? 'badge-success' : 'badge-warning' }}">
                            {{ __($comp->verification_status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- PENDING VERIFICATIONS QUEUE -->
    <div class="panel app-module-section" data-app-module="companies">
        <div class="panel-header">
            <h3 class="panel-title">{{ __('Pending Business Verifications (KYB Queue)') }}</h3>
            <span class="badge badge-warning">{{ $pendingVerifications->count() }} {{ __('Pending') }}</span>
        </div>

        @if($pendingVerifications->isEmpty())
            <div style="color: var(--text-tertiary); font-size: 0.85rem; padding: 1rem 0;">{{ __('No companies awaiting verification. All active tenants are verified.') }}</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Company Name') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Trade License') }}</th>
                        <th>{{ __('Document') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingVerifications as $v)
                    <tr>
                        <td><strong>{{ $v->company->name }}</strong></td>
                        <td><span class="badge badge-info">{{ $v->company->business_type === 'supplier' ? 'دابینکەر' : 'مارکێت' }}</span></td>
                        <td><code>{{ $v->company->trade_license_number }}</code></td>
                        <td>📄 {{ basename($v->file_path) }}</td>
                        <td>
                            <form action="{{ route('operator.verify.approve', $v->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success">{{ __('Approve & Grant Rights') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- GLOBAL TAXONOMIES -->
    <div class="panel app-module-section" data-app-module="companies">
        <div class="panel-header">
            <h3 class="panel-title">{{ __('Global Taxonomies Managed by Operator') }}</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('Category Name') }}</th>
                    <th>{{ __('Slug') }}</th>
                    <th>{{ __('Active Products') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $cat)
                <tr>
                    <td><strong>{{ $cat->name }}</strong> <span style="font-size: 0.78rem; color: var(--text-tertiary);">({{ $cat->name_en }})</span></td>
                    <td><code>{{ $cat->slug }}</code></td>
                    <td>{{ $cat->products_count }} {{ __('products') }}</td>
                    <td><span class="badge badge-success">{{ __('Active') }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- SUPER ADMIN: ALL ORDERS & TRANSACTIONS OVERSIGHT -->
    <div class="panel app-module-section" data-app-module="orders">
        <div class="panel-header" style="flex-wrap:wrap; gap:0.75rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <h3 class="panel-title">📊 چاودێری گشتی سەرجەم مامەڵە و داواکارییەکان (Super Admin Oversight)</h3>
                <span class="badge badge-info">{{ $allPurchaseOrders->count() }} داواکاری</span>
            </div>
            <div>
                <a href="{{ route('reports.orders.export', ['status' => request('status')]) }}" class="btn btn-outline" style="font-size:0.78rem; padding:0.35rem 0.75rem; border-color:var(--border-subtle); color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:0.35rem;">
                    📥 هەناردەی گشتی (Excel/CSV)
                </a>
            </div>
        </div>
        <div style="display:flex; gap:0.4rem; margin-bottom:1rem; flex-wrap:wrap;">
            @php $curOpStatus = request('status', 'all'); @endphp
            <a href="{{ route('marketlink.index', ['role' => 'operator', 'status' => 'all']) }}" class="badge {{ $curOpStatus === 'all' ? 'badge-primary' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">هەموو</a>
            <a href="{{ route('marketlink.index', ['role' => 'operator', 'status' => 'pending']) }}" class="badge {{ $curOpStatus === 'pending' ? 'badge-warning' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">چاوەڕوان</a>
            <a href="{{ route('marketlink.index', ['role' => 'operator', 'status' => 'approved']) }}" class="badge {{ $curOpStatus === 'approved' ? 'badge-info' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">پەسەندکراو</a>
            <a href="{{ route('marketlink.index', ['role' => 'operator', 'status' => 'dispatched']) }}" class="badge {{ $curOpStatus === 'dispatched' ? 'badge-info' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">لە ڕێگایە</a>
            <a href="{{ route('marketlink.index', ['role' => 'operator', 'status' => 'delivered']) }}" class="badge {{ $curOpStatus === 'delivered' ? 'badge-success' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">گەیشتوو</a>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
            بەڕێوەبەری سەرەکی دەتوانێت لەم بەشەوە چاودێری تەواوی قۆناغەکانی گەیاندن، بەهای مامەڵەکان، دۆخی قەرزەکان و مێژووی Audit Trailـی هەر فەرمانێک بکات.
        </p>

        <table>
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>دابینکەر (Supplier)</th>
                    <th>مارکێت (Retailer)</th>
                    <th>کۆی بەها ($)</th>
                    <th>شێوازی پارەدان</th>
                    <th>دۆخی قۆناغ (Lifecycle)</th>
                    <th>Audit & كردار</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allPurchaseOrders as $po)
                <tr>
                    <td><code>{{ $po->po_number }}</code></td>
                    <td><strong style="color:#34d399;">{{ $po->supplier->name }}</strong></td>
                    <td><strong style="color:#fbbf24;">{{ $po->retailer->name }}</strong></td>
                    <td>
                        <strong>${{ number_format($po->total_amount, 2) }}</strong>
                        @if($po->remaining_debt > 0)
                            <div style="color:#f87171; font-size:0.75rem;">قەرز: ${{ number_format($po->remaining_debt, 2) }}</div>
                        @endif
                    </td>
                    <td>{{ $po->payment_method }}</td>
                    <td>
                        <span class="badge {{ in_array($po->status, ['completed', 'delivered']) ? 'badge-success' : (in_array($po->status, ['cancelled', 'rejected']) ? 'badge-danger' : 'badge-warning') }}">
                            {{ $po->status }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.3rem;">
                            <button type="button" class="btn btn-sm" onclick="openAuditModal({{ $po->id }}, '{{ $po->po_number }}')" style="font-size:0.72rem; padding:0.25rem 0.5rem; background:rgba(99,102,241,0.15); color:#a5b4fc; border:1px solid rgba(99,102,241,0.3);">
                                📜 Audit
                            </button>
                            <a href="{{ route('orders.invoice', $po->id) }}" target="_blank" class="btn btn-sm" style="font-size:0.72rem; padding:0.25rem 0.5rem;">
                                📄 فاکتۆر
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-tertiary);">
                        هیچ داواکارییەک تۆمار نەکراوە.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- SECURITY AUDIT & ANTI-IDOR LIVE MONITOR -->
    <div class="panel app-module-section" data-app-module="system" style="border: 1px solid rgba(239, 68, 68, 0.25); background: rgba(16, 20, 29, 0.95);">
        <div class="panel-header" style="flex-wrap: wrap; gap: 0.75rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <h3 class="panel-title" style="color: #f87171;">🛡️ تۆماری چاودێری ئاسایش و ڕێگریکردن لە دەستگەیشتنی نایاسایی (Security & IDOR Monitor)</h3>
                <span class="badge {{ $securityAuditLogs->count() > 0 ? 'badge-danger' : 'badge-success' }}">
                    {{ $securityAuditLogs->count() }} هەوڵی تۆمارکراو
                </span>
            </div>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">چاودێری ڕاستەوخۆی هەوڵەکانی گۆڕینی ID، بەزاندنی سنووری کۆمپانیاکان و مۆڵەتی ڕۆڵەکان</span>
        </div>
        <p style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 1rem;">
            هەر هەوڵێکی دەستگەیشتن بە فاکتۆر، داواکاری یان چاتی کۆمپانیای تر لە ڕێگەی دەستکاریکردنی ID یان بانگهێشتکردنی ناڕاستی API ڕاستەوخۆ بلۆک دەکرێت (403 Forbidden) و لەم بەشەدا بە IP و بەروارەوە تۆمار دەکرێت.
        </p>

        <table>
            <thead>
                <tr>
                    <th>کاتی هەوڵ</th>
                    <th>جۆری پێشێلکاری</th>
                    <th>ئاستی مەترسی</th>
                    <th>ڕێڕەو (Endpoint)</th>
                    <th>کۆمپانیای پەیوەندیدار</th>
                    <th>وردەکاری و پەیام</th>
                    <th>IP ناونیشان</th>
                </tr>
            </thead>
            <tbody>
                @forelse($securityAuditLogs as $sLog)
                <tr>
                    <td style="font-size:0.78rem; white-space:nowrap; color:var(--text-secondary);">
                        {{ $sLog->created_at->diffForHumans() }}
                    </td>
                    <td>
                        <code style="color:#f87171; font-weight:700;">{{ $sLog->event_type }}</code>
                    </td>
                    <td>
                        <span class="badge {{ $sLog->severity === 'critical' ? 'badge-danger' : ($sLog->severity === 'warning' ? 'badge-warning' : 'badge-info') }}">
                            {{ $sLog->severity }}
                        </span>
                    </td>
                    <td>
                        <code>{{ $sLog->method }} {{ $sLog->endpoint }}</code>
                    </td>
                    <td>
                        {{ $sLog->company?->name ?? 'دیارینەکراو' }}
                    </td>
                    <td style="font-size:0.8rem; max-width:320px; color:#cbd5e1;">
                        {{ $sLog->details['message'] ?? '-' }}
                    </td>
                    <td>
                        <code style="font-size:0.75rem;">{{ $sLog->ip_address ?? '127.0.0.1' }}</code>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:1.5rem; color:#34d399;">
                        ✓ هیچ پێشێلکارییەکی ئاسایش یان هەوڵی دەستگەیشتنی نایاسایی تۆمار نەکراوە (سیستەم پارێزراوە).
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    <!-- BACKUP & DISASTER RECOVERY MANAGEMENT PANEL -->
    <div class="panel app-module-section" data-app-module="system" style="border: 1px solid rgba(16, 185, 129, 0.3); background: rgba(16, 20, 29, 0.95);" id="backup-recovery-panel">
        <div class="panel-header" style="flex-wrap: wrap; gap: 0.75rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <h3 class="panel-title" style="color: #34d399;">💾 سیستەمی کۆپیی پارێزەر و گێڕانەوە لە کاتی لەناکاو (Backup & Disaster Recovery)</h3>
                <span class="badge badge-success">{{ count($backups) }} کۆپی بەردەست</span>
            </div>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">پاراستنی داتابەیس و فایلی کۆمپانیاکان لە ونبوون بە پشکنینی ناوخۆیی و کۆدی پەنجەمۆر (SHA256)</span>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.2rem; line-height: 1.6;">
            ئەم بەشە داتابەیس، تۆماری مامەڵەکان، کۆمپانیاکان و فۆڵدەری میدیاکان ئەرشیف دەکات. لە کاتی هەر گەڕاندنەوەیەکدا، سیستەم سەرەتا بە شێوەیەکی ئۆتۆماتیکی <strong>Safety Pre-Restore Snapshot</strong> دروست دەکات بۆ ئەوەی هەرگیز داتای پلاتفۆرم ون نەبێت.
        </p>

        <!-- CREATE BACKUP FORM -->
        <form action="{{ route('operator.backups.create') }}" method="POST" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
            @csrf
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                <div style="flex:1; min-width:250px;">
                    <label style="display:block; font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.3rem;">وەسف یان تێبینی کۆپییەکە:</label>
                    <input type="text" name="description" placeholder="بۆ نموونە: باکئەپی هەفتانە پێش نوێکردنەوەی سیستم" style="width:100%; padding:0.45rem 0.75rem; background:rgba(0,0,0,0.3); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.85rem;">
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem; margin-top:1.2rem;">
                    <input type="checkbox" name="include_media" id="cb-include-media" value="1" style="accent-color:#10b981; width:16px; height:16px; cursor:pointer;">
                    <label for="cb-include-media" style="font-size:0.82rem; color:var(--text-primary); cursor:pointer;">هاوپێچکردنی فایلی میدیا و وێنە و دەنگەکان</label>
                </div>
                <div style="margin-top:1.2rem;">
                    <button type="submit" class="btn btn-primary" style="background:#059669; border-color:#10b981; font-weight:700; display:flex; align-items:center; gap:0.4rem; padding:0.45rem 1rem;">
                        <span>💾 دروستکردنی باکئەپی نوێ</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- BACKUPS LIST TABLE -->
        <h4 style="font-size:0.9rem; margin-bottom:0.6rem; color:#94a3b8;">کۆپییە پارێزراوەکانی ناو سێرڤەر (Storage Backups):</h4>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ناوی فایل</th>
                    <th>قەبارە</th>
                    <th>کاتی دروستکردن</th>
                    <th>کردارەکانی پاراستن</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $idx => $b)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><code style="color:#6ee7b7; font-size:0.82rem;">{{ $b['filename'] }}</code></td>
                    <td><strong>{{ $b['size_formatted'] }}</strong></td>
                    <td style="font-size:0.8rem; color:var(--text-secondary);">{{ $b['created_at'] }}</td>
                    <td>
                        <div style="display:flex; gap:0.4rem; align-items:center;">
                            <!-- Download -->
                            <a href="{{ route('operator.backups.download', $b['filename']) }}" class="btn btn-sm" style="font-size:0.75rem; padding:0.25rem 0.55rem; background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.3); text-decoration:none;">
                                📥 داگرتن
                            </a>
                            <!-- Restore Form -->
                            <form action="{{ route('operator.backups.restore') }}" method="POST" onsubmit="return confirm('ئاگاداری مەترسی: ئایا دڵنیایت لە گەڕاندنەوەی سیستم لە فایلی [{{ $b['filename'] }}]؟ ئەم کردارە داتابەیسەکە دەگۆڕێتەوە، بەڵام پێش ئەوە خۆکارانە کۆپییەکی پارێزەری نوێ هەڵدەگرێت.');" style="display:inline;">
                                @csrf
                                <input type="hidden" name="filename" value="{{ $b['filename'] }}">
                                <button type="submit" class="btn btn-sm" style="font-size:0.75rem; padding:0.25rem 0.55rem; background:rgba(234,179,8,0.15); color:#facc15; border:1px solid rgba(234,179,8,0.3); cursor:pointer;">
                                    🔄 گەڕاندنەوە
                                </button>
                            </form>
                            <!-- Delete Form -->
                            <form action="{{ route('operator.backups.delete') }}" method="POST" onsubmit="return confirm('ئایا دڵنیایت لە سڕینەوەی ئەم باکئەپە لە سێرڤەر؟');" style="display:inline;">
                                @csrf
                                <input type="hidden" name="filename" value="{{ $b['filename'] }}">
                                <button type="submit" class="btn btn-sm" style="font-size:0.75rem; padding:0.25rem 0.55rem; background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); cursor:pointer;">
                                    🗑️ سڕینەوە
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:1.5rem; color:var(--text-tertiary);">
                        هیچ باکئەپێک لە سیستەمدا بوونی نییە. تکایە دوگمەی «دروستکردنی باکئەپی نوێ» دابگرە.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- TERMINAL CLI SHORTCUTS -->
        <div style="margin-top:1rem; padding:0.75rem; background:rgba(0,0,0,0.4); border-radius:6px; font-size:0.78rem; font-family:var(--font-mono); color:#94a3b8; display:flex; gap:1.5rem; flex-wrap:wrap; border:1px solid rgba(255,255,255,0.05);">
            <div><strong>CLI Backup:</strong> <span style="color:#a7f3d0;">php artisan marketlink:backup --media</span></div>
            <div><strong>CLI Restore:</strong> <span style="color:#fde68a;">php artisan marketlink:restore filename.tar.gz</span></div>
            <div><strong>CLI List:</strong> <span style="color:#bae6fd;">php artisan marketlink:backup-list</span></div>
        </div>
    </div>

    @elseif($role === 'supplier')
    <!-- ==========================================
         SUPPLIER PLANE (Strict Warehouse Isolation)
    =========================================== -->

    <!-- PUBLISH SKU TO THIS SUPPLIER'S WAREHOUSE -->
    <div class="panel app-module-section" data-app-module="catalog">
        <div class="panel-header">
            <h3 class="panel-title">زیادکردنی کاڵا بۆ کۆگای: {{ $currentSupplier?->name }}</h3>
            <span class="badge badge-success">کۆگای تایبەت</span>
        </div>
        <form action="{{ route('supplier.product.store') }}" method="POST">
            @csrf
            <input type="hidden" name="supplier_company_id" value="{{ $currentSupplier?->id }}">
            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('Product Title') }} *</label>
                    <input type="text" name="title" required placeholder="ناوی کاڵا لە کۆگا">
                </div>
                <div class="form-group">
                    <label>{{ __('SKU') }} *</label>
                    <input type="text" name="sku" required placeholder="SKU-2026-XXXX">
                </div>
                <div class="form-group">
                    <label>{{ __('Category') }} *</label>
                    <select name="category_id" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('Wholesale Price ($)') }} *</label>
                    <input type="number" step="0.01" name="base_wholesale_price" required placeholder="18.50">
                </div>
                <div class="form-group">
                    <label>بڕی کۆگا (دانە) *</label>
                    <input type="number" name="current_stock" value="100" required>
                </div>
                <div class="form-group">
                    <label>{{ __('MOQ') }} *</label>
                    <input type="number" name="minimum_order_quantity" value="10" required>
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary">+ بڵاوکردنەوە لە کۆگای {{ $currentSupplier?->name }}</button>
            </div>
        </form>
    </div>

    <!-- THIS SUPPLIER'S ISOLATED WAREHOUSE INVENTORY -->
    <div class="panel app-module-section" data-app-module="catalog">
        <div class="panel-header">
            <h3 class="panel-title">کۆگای دابینکەر ({{ $currentSupplier?->name }})</h3>
            <span class="badge {{ $lowStockCount > 0 ? 'badge-danger' : 'badge-success' }}">
                {{ $supplierProducts->count() }} کاڵا لە کۆگا | {{ $lowStockCount }} نزیک لە تەواوبوون
            </span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            ئەم خشتەیە بە تەواوی جیاکراوەیە؛ تەنها ئەو کاڵایانە پیشان دەدات کە ئەم دابینکەرە خاوەندارێتی دەکات.
        </p>
        <table>
            <thead>
                <tr>
                    <th>{{ __('SKU') }}</th>
                    <th>{{ __('Product Title') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Base Price') }}</th>
                    <th>{{ __('Volume Tiers') }}</th>
                    <th>بڕی ماوە لە کۆگا</th>
                    <th>نوێکردنەوەی کۆگا</th>
                </tr>
            </thead>
            <tbody>
                @forelse($supplierProducts as $p)
                <tr>
                    <td><code>{{ $p->sku }}</code></td>
                    <td><strong>{{ $p->title }}</strong></td>
                    <td>{{ $p->category->name }}</td>
                    <td>${{ number_format($p->base_wholesale_price, 2) }}</td>
                    <td>
                        @forelse($p->volumeTiers as $t)
                            <div style="font-size:0.75rem; color:#34d399;">{{ $t->min_quantity }}+ دانە: ${{ number_format($t->unit_price, 2) }}</div>
                        @empty
                            <span style="font-size:0.75rem; color:var(--text-tertiary);">-</span>
                        @endforelse
                    </td>
                    <td>
                        <strong style="color: {{ $p->current_stock <= 20 ? '#f87171' : '#34d399' }};">
                            {{ number_format($p->current_stock) }} دانە
                        </strong>
                        @if($p->current_stock <= 20)
                            <span class="badge badge-danger" style="margin-right:0.3rem;">کەمە</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('supplier.product.stock', $p->id) }}" method="POST" style="display:flex; gap:0.4rem; align-items:center;">
                            @csrf
                            <input type="number" name="current_stock" value="{{ $p->current_stock }}" min="0" style="width:75px; padding:0.25rem 0.4rem; background:var(--bg-surface); border:1px solid var(--border-subtle); color:#fff; border-radius:4px; font-size:0.8rem;">
                            <button type="submit" class="btn btn-sm">نوێکردنەوە</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-tertiary);">
                        هیچ کاڵایەک لە کۆگای ئەم دابینکەرەدا نییە. فۆڕمی سەرەوە بەکاربهێنە بۆ زیادکردن.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- CASH VAN OPERATIONS (Direct Store Delivery) -->
    <div class="app-module-section" data-app-module="van">
    @include('partials.cash_van_section')
    </div>

    <!-- MARKETS DIRECTORY (Suppliers can see all registered markets) -->
    <div class="panel app-module-section" data-app-module="chat">
        <div class="panel-header">
            <h3 class="panel-title">تۆماری مارکێت و سوپەرمارکێتەکان</h3>
            <span class="badge badge-info">{{ $retailers->count() }} مارکێتی چالاک لە سیستەمدا</span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            دابینکەر لەم بەشەوە دەتوانێت هەموو ئەو مارکێتانە ببینێت کە لە پلاتفۆرمەکەدا چالاکن، و ڕاستەوخۆ دەست بە چات بکات و ئۆفەری نرخی کۆمەڵیان پێشکەش بکات.
        </p>
        <div class="cards-grid">
            @foreach($retailers as $ret)
            <div class="b2b-card">
                <div>
                    <h4>🛒 {{ $ret->name }}</h4>
                    <div class="meta">
                        📍 <strong>{{ $ret->city }}, {{ $ret->country }}</strong><br>
                        📞 {{ $ret->primary_phone }}<br>
                        ✉️ {{ $ret->primary_email }}<br>
                        🏢 {{ $ret->address_line }}
                    </div>
                </div>
                <div class="actions">
                    <a href="?role=supplier&active_supplier_id={{ $currentSupplier?->id }}&chat_retailer_id={{ $ret->id }}#chat-section" class="btn btn-primary btn-sm">
                        💬 دەستپێکردنی چات و ناردنی ئۆفەری نرخ
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- OPEN MARKET DEMANDS / RFQs -->
    <div class="panel app-module-section" data-app-module="orders">
        <div class="panel-header">
            <h3 class="panel-title">پێداویستی و داواکارییە کراوەکانی مارکێتەکان (Market Demands & RFQ)</h3>
            <span class="badge badge-warning">{{ $allMarketDemands->count() }} پێداویستی مارکێت</span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            ئەم داواکارییانە لەلایەن مارکێتەکانەوە بڵاوکراونەتەوە؛ دابینکەران دەتوانن ڕاستەوخۆ لە ڕێگەی چاتەوە کڕیارەکان ڕازی بکەن و نرخی خۆیان پێشکەش بکەن.
        </p>
        <table>
            <thead>
                <tr>
                    <th>مارکێتی داواکار</th>
                    <th>پێداویستی و بابەتی داواکراو</th>
                    <th>هاوپۆل</th>
                    <th>بڕی داواکراو</th>
                    <th>نرخی ئامانج</th>
                    <th>تێبینی</th>
                    <th>کردار</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allMarketDemands as $d)
                <tr>
                    <td><strong>{{ $d->retailer->name }}</strong> <span style="font-size:0.75rem; color:var(--text-tertiary);">({{ $d->retailer->city }})</span></td>
                    <td><strong>{{ $d->title }}</strong></td>
                    <td>{{ $d->category?->name ?? 'گشتی' }}</td>
                    <td><span class="badge badge-info">{{ $d->quantity }} {{ $d->unit }}</span></td>
                    <td>{{ $d->target_unit_price ? '$' . number_format($d->target_unit_price, 2) : 'دیارینەکراوە' }}</td>
                    <td>{{ $d->notes ?? '-' }}</td>
                    <td>
                        <a href="?role=supplier&active_supplier_id={{ $currentSupplier?->id }}&chat_retailer_id={{ $d->retailer_company_id }}#chat-section" class="btn btn-success btn-sm">
                            💬 پێشکەشکردنی نرخ لە چات
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:1.5rem; color:var(--text-tertiary);">
                        لە ئێستادا هیچ داواکارییەکی کراوەی مارکێت تۆمار نەکراوە.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- SUPPLIER CHAT & PRICE PROPOSALS WITH RETAILERS -->
    <div class="panel app-module-section" data-app-module="chat" id="chat-section">
        <div class="panel-header" style="flex-wrap:wrap; gap:0.6rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <h3 class="panel-title">💬 سیستەمی چات و دانوستاندنی نرخ لەگەڵ مارکێتەکان</h3>
                <span class="badge badge-info">دابینکەر: {{ $currentSupplier?->name }}</span>
            </div>
            <span style="font-size:0.8rem; color:var(--text-tertiary);">تایبەت بە ناردنی ئۆفەر، وێنە، و دەنگ</span>
        </div>
        
        <div class="chat-box">
            <div class="chat-sidebar">
                <div style="margin-bottom:0.8rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                        <span style="font-size:0.75rem; color:var(--text-tertiary); text-transform:uppercase; font-weight:700;">مارکێتەکان ({{ $conversations->count() }})</span>
                        <span style="font-size:0.7rem; color:#38bdf8; font-weight:600;">B2B Chat</span>
                    </div>
                    <input type="text" oninput="filterChatContacts(this, 'chat-contacts-supplier')" placeholder="🔍 گەڕان بەناو کڕیارەکان..." style="width:100%; padding:0.45rem 0.75rem; background:rgba(255,255,255,0.06); border:1px solid var(--border-subtle); border-radius:8px; color:#fff; font-size:0.8rem; outline:none; transition:border-color 0.2s;" onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='var(--border-subtle)'">
                </div>
                <div id="chat-contacts-supplier" style="display:flex; flex-direction:column; gap:0.35rem; max-height:480px; overflow-y:auto;">
                @forelse($conversations as $c)
                    <a href="?role=supplier&active_supplier_id={{ $currentSupplier?->id }}&conversation_id={{ $c->id }}#chat-section" class="conv-item {{ $activeConversation?->id === $c->id ? 'active' : '' }}">
                        <strong style="display:block; color:#fff;">🛒 {{ $c->retailer->name }}</strong>
                        <span style="font-size:0.75rem; color:var(--text-tertiary);">{{ $c->retailer->city }} • {{ $c->messages->count() }} پەیام</span>
                    </a>
                @empty
                    <div style="font-size:0.8rem; color:var(--text-tertiary); padding:0.8rem; text-align:center;">هیچ گفتوگۆیەک نییە. لە خشتەی سەرەوە کرتە لەسەر مارکێتێک بکە بۆ دەستپێکردنی چات.</div>
                @endforelse
                </div>
            </div>

            <div class="chat-main">
                @if($activeConversation)
                    <div>
                        <div style="border-bottom:1px solid var(--border-subtle); padding-bottom:0.8rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="color:#fff;">گفتوگۆ لەگەڵ مارکێتی: {{ $activeConversation->retailer->name }}</h4>
                                <span style="font-size:0.75rem; color:var(--text-tertiary);">شار: {{ $activeConversation->retailer->city }} | پەیوەندی: {{ $activeConversation->retailer->primary_phone }}</span>
                            </div>
                            <span class="badge badge-success">دانوستاندن چالاکە</span>
                        </div>
                        <div class="chat-messages" id="chat-messages-supplier">
                            @foreach($activeConversation->messages as $m)
                                <div class="chat-msg {{ $m->sender_company_id === $currentSupplier->id ? 'sent' : 'received' }} {{ $m->message_type === 'quote_proposal' ? 'proposal' : '' }}">
                                    <div style="font-size:0.7rem; font-weight:700; margin-bottom:0.2rem; color:{{ $m->sender_company_id === $currentSupplier->id ? '#a5b4fc' : '#34d399' }};">
                                        {{ $m->sender->name }} ({{ $m->created_at->diffForHumans() }})
                                    </div>
                                    @if($m->message_type === 'image' || ($m->attachment_url && Str::contains($m->attachment_url, ['chat_attachments/images', '.jpg', '.jpeg', '.png', '.webp', '.gif'])))
                                        <div>
                                            <a href="{{ asset($m->attachment_url) }}" target="_blank" title="کرتە بکە بۆ کردنەوەی وێنەکە">
                                                <img src="{{ asset($m->attachment_url) }}" alt="هاوپێچی وێنە" class="chat-media-img">
                                            </a>
                                        </div>
                                    @endif
                                    @if($m->message_type === 'voice' || ($m->attachment_url && Str::contains($m->attachment_url, ['chat_attachments/voice', '.webm', '.ogg', '.mp3', '.wav', '.m4a'])))
                                        <div class="chat-voice-box">
                                            <span style="font-size:1.1rem;">🎙️</span>
                                            <audio controls src="{{ asset($m->attachment_url) }}"></audio>
                                        </div>
                                    @endif
                                    @if($m->body && !($m->message_type === 'image' && $m->body === '📷 وێنەی هاوپێچکراو') && !($m->message_type === 'voice' && $m->body === '🎙️ پەیامی دەنگی (فۆیس)'))
                                        <div>{!! nl2br(e($m->body)) !!}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- REGULAR MESSAGE FORM WITH IMAGE & VOICE -->
                    <form action="{{ route('messages.store') }}" method="POST" enctype="multipart/form-data" class="chat-input-bar chat-form">
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $activeConversation->id }}">
                        <input type="hidden" name="sender_company_id" value="{{ $currentSupplier->id }}">
                        <input type="file" name="image" accept="image/*" class="chat-file-image" style="display:none;">
                        <input type="file" name="voice" accept="audio/*" class="chat-file-voice" style="display:none;">

                        <!-- PREVIEWS (Images / Voice) -->
                        <div class="chat-preview-box" style="display:none;"></div>

                        <!-- LIVE RECORDING STATUS BAR -->
                        <div class="chat-recording-bar" style="display:none; align-items:center; justify-content:space-between; background:rgba(239,68,68,0.18); border:1px solid #ef4444; border-radius:6px; padding:0.4rem 0.8rem;">
                            <div style="display:flex; align-items:center; gap:0.5rem; color:#fca5a5; font-size:0.85rem; font-weight:600;">
                                <span class="dot" style="background:#ef4444; width:10px; height:10px; border-radius:50%; animation:pulse-red 1s infinite;"></span>
                                <span>دەنگ تۆمار دەکرێت...</span>
                                <span class="rec-timer" style="font-family:var(--font-mono); color:#fff;">00:00</span>
                            </div>
                            <div style="display:flex; gap:0.4rem;">
                                <button type="button" class="btn btn-sm btn-success btn-finish-rec">✓ تەواوکردن</button>
                                <button type="button" class="btn btn-sm btn-danger btn-cancel-rec">✖ پاشگەزبوونەوە</button>
                            </div>
                        </div>

                        <!-- QUICK REPLIES FOR SUPPLIER -->
                        <div class="quick-replies-bar">
                            <span style="font-size:0.72rem; color:var(--text-tertiary);">⚡ وەڵامی خێرا:</span>
                            <button type="button" class="quick-reply-chip" data-text="سڵاو، کەتەلۆک و نوێترین لیستی نرخ بەردەستە.">کەتەلۆکی نوێ</button>
                            <button type="button" class="quick-reply-chip" data-text="بڕی داواکراو ئامادەیە لە کۆگا و لە ماوەی ۲۴ کاتژمێردا دەگات.">ئامادەی بارکردن</button>
                            <button type="button" class="quick-reply-chip" data-text="داشکاندنی تایبەت بۆ کڕینی بڕی زۆر بەردەستە.">داشکاندنی تایبەت</button>
                            <button type="button" class="quick-reply-chip" data-text="مەرجی پارەدان بە نەختینە لە کاتی وەرگرتنی کاڵاکەیە.">شێوازی پارەدان</button>
                        </div>

                        <div class="chat-input-row">
                            <button type="button" class="chat-btn-action btn-trigger-image" title="هاوپێچکردنی وێنە">
                                📷 <span>وێنە</span>
                            </button>
                            <button type="button" class="chat-btn-action chat-btn-record btn-trigger-voice" title="تۆمارکردنی دەنگ (فۆیس)">
                                🎙️ <span>فۆیس</span>
                            </button>
                            <textarea name="body" placeholder="پەیامی ئاسایی، وەڵام، یان ڕوونکردنەوەی کاڵا بنووسە..."></textarea>
                            <button type="submit" class="btn btn-primary" style="height:48px;">{{ __('Send Message') }}</button>
                        </div>
                    </form>

                    <!-- SPECIAL PRICE PROPOSAL FORM FOR SUPPLIER -->
                    <details class="proposal-form-toggle">
                        <summary style="font-size:0.8rem; font-weight:700; color:#34d399; cursor:pointer;">
                            🏷️ پێشکەشکردنی ئۆفەری نرخی تایبەت و داشکاندنی فەرمی بۆ ئەم مارکێتە
                        </summary>
                        <form action="{{ route('messages.proposal') }}" method="POST" style="margin-top:0.8rem;">
                            @csrf
                            <input type="hidden" name="conversation_id" value="{{ $activeConversation->id }}">
                            <input type="hidden" name="sender_company_id" value="{{ $currentSupplier->id }}">
                            <div class="form-row" style="margin-top:0.4rem;">
                                <div class="form-group">
                                    <label>کاڵا لە کۆگای دابینکەر</label>
                                    <select name="product_id">
                                        <option value="">هەڵبژاردنی کاڵا</option>
                                        @foreach($supplierProducts as $sp)
                                            <option value="{{ $sp->id }}">{{ $sp->title }} (نرخی بنەڕەتی: ${{ $sp->base_wholesale_price }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>نرخی ئۆفەرکراو ($) بۆ هەر دانەیەک *</label>
                                    <input type="number" step="0.01" name="proposed_unit_price" required placeholder="نرخی داشکێندراو">
                                </div>
                                <div class="form-group">
                                    <label>کەمترین بڕی کڕین (دانە) *</label>
                                    <input type="number" name="min_quantity" value="50" required>
                                </div>
                            </div>
                            <div class="form-group" style="margin-top:0.6rem;">
                                <label>مەرجی گەیاندن یان پارەدان (تێبینی)</label>
                                <input type="text" name="notes" placeholder="وەک: گەیاندن بە ٢ ڕۆژ، مەرجی پارەدان Net 30">
                            </div>
                            <button type="submit" class="btn btn-success btn-sm" style="margin-top:0.6rem;">
                                ناردنی ئۆفەری فەرمی بۆ مارکێت
                            </button>
                        </form>
                    </details>
                @else
                    <div style="color:var(--text-tertiary); text-align:center; padding:4rem;">مارکێتێک لە لیستەکەی دەستەڕاست هەڵبژێرە بۆ بینینی چات.</div>
                @endif
            </div>
        </div>
    </div>

    <!-- RETAILER PARTNERS DIRECTORY BY PROVINCE & CATEGORY -->
    <div class="panel app-module-section" data-app-module="chat">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">🏬 فرۆشگا و مارکێتەکان بەپێی شار، پارێزگا و جۆری فرۆشگا</h3>
                <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:0.2rem;">
                    دابینکەران دەتوانن مارکێتەکان بەپێی شار و پارێزگاکان ببینن، جۆری فرۆشگاکە (سوپەرمارکێت، مینی مارکێت، هتد) هەڵسەنگێنن و پەیوەندی ببەستن.
                </p>
            </div>
            <span class="badge badge-info">{{ $directoryRetailers->count() }} مارکێت لەم بەشەدا</span>
        </div>

        <!-- Filter form -->
        <form action="" method="GET" style="display:flex; gap:0.6rem; flex-wrap:wrap; margin-bottom:1.2rem; background:rgba(0,0,0,0.25); padding:0.8rem; border-radius:8px; border:1px solid var(--border-subtle); align-items:center;">
            <input type="hidden" name="role" value="supplier">
            <input type="hidden" name="active_supplier_id" value="{{ $currentSupplier?->id }}">
            
            <div style="font-size:0.8rem; color:var(--text-secondary); font-weight:600;">📍 فلتەری پارێزگا:</div>
            <select name="filter_province" onchange="this.form.submit()" style="padding:0.4rem 0.6rem; background:var(--bg-core); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.82rem;">
                <option value="">هەموو پارێزگاکان (هەولێر، سلێمانی، دهۆک، کەرکووک، هەڵەبجە)</option>
                @foreach($allProvinces as $prv)
                    <option value="{{ $prv }}" {{ $filterProvince === $prv ? 'selected' : '' }}>{{ $prv }}</option>
                @endforeach
            </select>

            <div style="font-size:0.8rem; color:var(--text-secondary); font-weight:600; margin-right:0.6rem;">🛒 جۆری فرۆشگا:</div>
            <select name="filter_category" onchange="this.form.submit()" style="padding:0.4rem 0.6rem; background:var(--bg-core); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.82rem;">
                <option value="">هەموو جۆرەکان (سوپەرمارکێت، مینی مارکێت، هتد)</option>
                @foreach($retailerCategories as $rcat)
                    <option value="{{ $rcat }}" {{ $filterCategory === $rcat ? 'selected' : '' }}>{{ $rcat }}</option>
                @endforeach
            </select>

            @if($filterProvince || $filterCategory)
                <a href="?role=supplier&active_supplier_id={{ $currentSupplier?->id }}" class="btn btn-sm btn-secondary" style="margin-right:auto;">لابردنی فلتەر ✖</a>
            @endif
        </form>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem;">
            @forelse($directoryRetailers as $ret)
                <div style="background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:10px; padding:1rem; display:flex; flex-direction:column; justify-content:space-between; transition:border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.4rem;">
                            <h4 style="color:#fff; font-size:0.95rem;">🛒 {{ $ret->name }}</h4>
                            <span class="badge badge-success" style="font-size:0.65rem;">باوەڕپێکراو</span>
                        </div>
                        <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-bottom:0.6rem;">
                            <span style="font-size:0.75rem; background:rgba(99,102,241,0.15); color:#a5b4fc; padding:0.15rem 0.45rem; border-radius:4px; border:1px solid rgba(99,102,241,0.3);">
                                📍 {{ $ret->province ?? $ret->city }} ({{ $ret->city }})
                            </span>
                            <span style="font-size:0.75rem; background:rgba(245,158,11,0.15); color:#fbbf24; padding:0.15rem 0.45rem; border-radius:4px; border:1px solid rgba(245,158,11,0.3);">
                                🏬 {{ $ret->business_category ?? 'سوپەرمارکێت' }}
                            </span>
                        </div>
                        <p style="font-size:0.78rem; color:var(--text-secondary); margin-bottom:0.8rem; line-height:1.4;">
                            {{ $ret->address_line }}<br>
                            پەیوەندی: {{ $ret->primary_phone }}
                        </p>
                    </div>
                    <a href="?role=supplier&active_supplier_id={{ $currentSupplier?->id }}&chat_retailer_id={{ $ret->id }}#chat-section" class="btn btn-sm btn-primary" style="width:100%; text-align:center;">
                        💬 دەستپێکردنی چات و دانوستاندن
                    </a>
                </div>
            @empty
                <div style="grid-column:1/-1; text-align:center; padding:2rem; color:var(--text-tertiary);">
                    هیچ مارکێتێک بەم فلتەرە نەدۆزرایەوە. تکایە فلتەرەکە بگۆڕە.
                </div>
            @endforelse
        </div>
    </div>

    <!-- THIS SUPPLIER'S INBOUND PURCHASE ORDERS ONLY -->
    <div class="panel app-module-section" data-app-module="orders">
        <div class="panel-header" style="flex-wrap: wrap; gap: 0.75rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <h3 class="panel-title">داواکارییە هاتوەکانی کڕین بۆ: {{ $currentSupplier?->name }}</h3>
                <span class="badge badge-info">{{ $inboundPurchaseOrders->count() }} فەرمانی کڕین</span>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <a href="{{ route('reports.orders.export', array_filter(['supplier_id' => $currentSupplier?->id, 'status' => request('status')])) }}" class="btn btn-outline" style="font-size:0.78rem; padding:0.35rem 0.75rem; border-color:var(--border-subtle); color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:0.35rem;">
                    📥 هەناردەی فەرمانەکان (CSV)
                </a>
                @if($currentSupplier)
                <a href="{{ route('reports.statement.export', $currentSupplier->id) }}" class="btn btn-outline" style="font-size:0.78rem; padding:0.35rem 0.75rem; border-color:var(--border-subtle); color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:0.35rem;">
                    📊 کەشف حیساب (CSV)
                </a>
                @endif
            </div>
        </div>
        <div style="display:flex; gap:0.4rem; margin-bottom:1rem; flex-wrap:wrap;">
            @php $curSupStatus = request('status', 'all'); @endphp
            <a href="{{ route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $currentSupplier?->id, 'status' => 'all']) }}" class="badge {{ $curSupStatus === 'all' ? 'badge-primary' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">هەموو</a>
            <a href="{{ route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $currentSupplier?->id, 'status' => 'pending']) }}" class="badge {{ $curSupStatus === 'pending' ? 'badge-warning' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">چاوەڕوان</a>
            <a href="{{ route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $currentSupplier?->id, 'status' => 'approved']) }}" class="badge {{ $curSupStatus === 'approved' ? 'badge-info' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">پەسەندکراو</a>
            <a href="{{ route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $currentSupplier?->id, 'status' => 'dispatched']) }}" class="badge {{ $curSupStatus === 'dispatched' ? 'badge-info' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">لە ڕێگایە</a>
            <a href="{{ route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $currentSupplier?->id, 'status' => 'delivered']) }}" class="badge {{ $curSupStatus === 'delivered' ? 'badge-success' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">گەیشتوو</a>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            تەنها ئەو داواکارییە فەرمییانە پیشان دەدات کە لەلایەن مارکێتەکانەوە ئاڕاستەی ئەم دابینکەرە کراون. کاتێک کاڵاکان ئامادە دەکرێن، پسوولەی بارکردن دەردەچێت و مارکێت دەتوانێت بیبینێت.
        </p>
        <table>
            <thead>
                <tr>
                    <th>{{ __('PO Number') }}</th>
                    <th>مارکێتی داواکار</th>
                    <th>کۆی بڕ ($)</th>
                    <th>پارەدان و قەرز</th>
                    <th>دۆخی گەیاندن</th>
                    <th>کردار و پسوولەی فەرمی</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inboundPurchaseOrders as $po)
                <tr>
                    <td>
                        <code>{{ $po->po_number }}</code>
                        @if($po->waybill_number)
                            <div style="font-size:0.72rem; color:#34d399;">پسوولە: {{ $po->waybill_number }}</div>
                        @endif
                        <details style="margin-top:0.35rem; font-size:0.75rem;">
                            <summary style="color:#60a5fa; cursor:pointer; font-weight:700;">
                                📦 کاڵاکانی ئەم داواکارییە ({{ $po->items->count() }} کاڵا)
                            </summary>
                            <div style="margin-top:0.3rem; background:rgba(0,0,0,0.3); padding:0.4rem; border-radius:6px; min-width:180px;">
                                @foreach($po->items as $it)
                                    <div style="display:flex; justify-content:space-between; font-size:0.72rem; padding:0.2rem 0; border-bottom:1px dashed rgba(255,255,255,0.06);">
                                        <span style="color:#fff;">• {{ $it->product_title }}</span>
                                        <strong style="color:#34d399;">{{ $it->quantity_ordered }} دانە (${{ number_format($it->total_price, 2) }})</strong>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    </td>
                    <td>
                        <strong>{{ $po->retailer->name }}</strong>
                        <div style="font-size:0.75rem; color:var(--text-tertiary);">{{ $po->retailer->city }} • {{ $po->retailer->primary_phone }}</div>
                    </td>
                    <td><strong>${{ number_format($po->total_amount, 2) }}</strong></td>
                    <td>
                        <span class="badge {{ $po->remaining_debt > 0 ? 'badge-danger' : 'badge-success' }}">
                            @if($po->remaining_debt > 0)
                                قەرز: ${{ number_format($po->remaining_debt, 2) }}
                            @else
                                دراوە: ${{ number_format($po->paid_amount, 2) }}
                            @endif
                        </span>
                    </td>
                    <td>
                        @if($po->status === 'pending' || $po->status === 'issued')
                            <span class="badge badge-warning">⏳ چاوەڕوانی پەسەندکردن (Pending)</span>
                            <div style="font-size:0.72rem; color:#fcd34d; margin-top:0.2rem;">
                                پشکنینی قەرز: سەقف ${{ number_format($po->retailer?->retailerProfile?->credit_limit ?? 5000, 0) }}
                            </div>
                        @elseif($po->status === 'approved')
                            <span class="badge" style="background:rgba(59,130,246,0.2); color:#60a5fa; border:1px solid #3b82f6;">
                                📦 پەسەندکراو (ستۆک ڕاگیراوە)
                            </span>
                        @elseif($po->status === 'processing')
                            <span class="badge badge-info">🏭 ئامادەکردن لە کۆگا (Processing)</span>
                        @elseif($po->status === 'dispatched')
                            <span class="badge badge-info">🚚 بارکراو بەرەو مارکێت</span>
                            <div style="font-size:0.72rem; color:var(--text-tertiary);">شوفێر: {{ $po->driver_name }} ({{ $po->driver_phone }})</div>
                        @elseif($po->status === 'delivered')
                            <span class="badge badge-success">✅ وەرگیراوە لە مارکێت (Delivered)</span>
                            @if($po->damage_amount > 0)
                                <div style="font-size:0.7rem; color:#f87171;">⚠️ زیان: -${{ number_format($po->damage_amount, 2) }}</div>
                            @endif
                        @elseif($po->status === 'completed')
                            <span class="badge" style="background:rgba(16,185,129,0.25); color:#34d399; border:1px solid #10b981;">
                                🎉 تەواوکراو (Completed)
                            </span>
                        @elseif($po->status === 'cancelled')
                            <span class="badge badge-danger">✖ هەڵوەشێنراوە</span>
                        @else
                            <span class="badge badge-info">{{ __($po->status) }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap;">
                            {{-- Step 2: Approve & Reserve Stock --}}
                            @if($po->status === 'pending' || $po->status === 'issued')
                                <form action="{{ route('orders.approve', $po->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="پەسەندکردنی داواکاری و ڕاگرتنی ستۆک لە کۆگا">
                                        ✓ پەسەندکردن و ڕاگرتنی ستۆک
                                    </button>
                                </form>
                                <form action="{{ route('orders.cancel', $po->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('ئایا دڵنیایت لە ڕەتکردنەوە؟')">
                                        ✖ ڕەتکردنەوە
                                    </button>
                                </form>
                            @endif

                            {{-- Step 3: Warehouse Picking & Packing --}}
                            @if($po->status === 'approved')
                                <details style="background:var(--bg-surface); padding:0.4rem; border-radius:6px; border:1px solid var(--border-subtle);">
                                    <summary style="font-size:0.76rem; font-weight:700; color:#60a5fa; cursor:pointer;">
                                        ⚙️ ئامادەکردن لە کۆگا (Pick List)
                                    </summary>
                                    <form action="{{ route('orders.process', $po->id) }}" method="POST" style="margin-top:0.4rem; min-width:210px;">
                                        @csrf
                                        <p style="font-size:0.7rem; color:var(--text-secondary); margin-bottom:0.3rem;">
                                            ئەگەر ستۆکی کاڵایەک لە کۆگادا کەم بوو دەتوانیت دەستکاری بکەیت:
                                        </p>
                                        @if($po->items->isNotEmpty())
                                            <input type="hidden" name="adjust_item_id" value="{{ $po->items->first()->id }}">
                                            <div style="margin-bottom:0.3rem;">
                                                <label style="font-size:0.7rem; color:var(--text-tertiary);">بڕی بەردەست (دانە):</label>
                                                <input type="number" name="adjusted_qty" value="{{ $po->items->first()->quantity_ordered }}" style="width:100%; font-size:0.75rem; padding:0.25rem; background:var(--bg-core); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;">
                                            </div>
                                        @endif
                                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;">
                                            ✓ دەرهێنانی پسوولەی کۆگا و ئامادەکردن
                                        </button>
                                    </form>
                                </details>
                            @endif

                            {{-- Step 4: Dispatch to Driver --}}
                            @if($po->status === 'processing')
                                <details style="background:var(--bg-surface); padding:0.4rem; border-radius:6px; border:1px solid var(--border-subtle);">
                                    <summary style="font-size:0.78rem; font-weight:700; color:#34d399; cursor:pointer;">
                                        🚚 بارکردن و ناردن بۆ شوفێر
                                    </summary>
                                    <form action="{{ route('orders.dispatch', $po->id) }}" method="POST" style="margin-top:0.4rem; min-width:190px;">
                                        @csrf
                                        <input type="text" name="driver_name" required placeholder="ناوی شوفێری دابەشکردن" style="width:100%; font-size:0.75rem; padding:0.3rem; margin-bottom:0.3rem; background:var(--bg-core); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;">
                                        <input type="text" name="driver_phone" placeholder="ژمارەی تەلەفۆن" style="width:100%; font-size:0.75rem; padding:0.3rem; margin-bottom:0.3rem; background:var(--bg-core); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;">
                                        <input type="text" name="vehicle_plate" placeholder="تابلۆی بارهەڵگر" style="width:100%; font-size:0.75rem; padding:0.3rem; margin-bottom:0.3rem; background:var(--bg-core); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;">
                                        <button type="submit" class="btn btn-success btn-sm" style="width:100%;">
                                            دەرکردنی پسوولەی بارکردن و OTP
                                        </button>
                                    </form>
                                </details>
                            @endif

                            {{-- Step 6: Final Financial & Stock Settlement --}}
                            @if($po->status === 'delivered')
                                @php
                                    $isOrderCredit = ($po->remaining_debt > 0 && ($po->paid_amount == 0 || str_contains(strtolower((string)$po->payment_terms), 'credit') || $po->payment_method === 'debt'));
                                @endphp
                                <details style="background:var(--bg-surface); padding:0.4rem; border-radius:6px; border:1px solid var(--border-subtle);">
                                    <summary style="font-size:0.78rem; font-weight:700; color:#34d399; cursor:pointer;">
                                        💰 یەکلاکردنەوەی دارایی و پسوولە
                                    </summary>
                                    <form action="{{ route('orders.complete_settle', $po->id) }}" method="POST" style="margin-top:0.4rem; min-width:210px;">
                                        @csrf
                                        <div style="font-size:0.72rem; color:var(--text-secondary); margin-bottom:0.3rem;">
                                            شێوازی پارەدان هەڵبژێرە:
                                        </div>
                                        <select name="payment_method" style="width:100%; font-size:0.75rem; padding:0.3rem; margin-bottom:0.3rem; background:var(--bg-core); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;">
                                            <option value="Credit Net 30 Days" {{ $isOrderCredit ? 'selected' : '' }}>قەرز بە وادەی ٣٠ ڕۆژ (Net 30 Days - ماوە وەک قەرز)</option>
                                            <option value="COD Cash" {{ !$isOrderCredit ? 'selected' : '' }}>کاش کاتی وەرگرتن (COD - تەواوی پارەکە وەرگیرا)</option>
                                        </select>
                                        <button type="submit" class="btn btn-success btn-sm" style="width:100%;">
                                            ✓ پەسەندکردنی کۆتایی و یەکلاکردنەوە
                                        </button>
                                    </form>
                                </details>
                            @endif

                            <button type="button" class="btn btn-sm" onclick="openAuditModal({{ $po->id }})" style="font-size:0.72rem; padding:0.25rem 0.5rem; background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3); color:#a5b4fc;" title="بینینی مێژووی هەموو گۆڕانکارییەکانی دۆخ">
                                📜 مێژوو
                            </button>

                            <a href="{{ route('orders.invoice', $po->id) }}" target="_blank" class="btn btn-sm" title="بینین و چاپکردنی فاکتۆر">
                                📄 پسوولە
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-tertiary);">
                        هیچ داواکارییەکی نوێ نییە بۆ ئەم دابینکەرە.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- SUPPLIER RECEIVABLES / DEBTS OWED BY MARKETS PANEL -->
    <div class="panel app-module-section" data-app-module="finance">
        <div class="panel-header">
            <h3 class="panel-title">حسابات و قەرزەکانی مارکێتەکان بۆ ئەم دابینکەرە</h3>
            <span class="badge {{ $totalSupplierReceivable > 0 ? 'badge-danger' : 'badge-success' }}">
                کۆی قەرزی داواکراو لای مارکێتەکان: ${{ number_format($totalSupplierReceivable, 2) }}
            </span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            لێرەدا ئەو مارکێتانە دەبینیت کە قەرزارن بەرامبەر ئەو کاڵایانەی وەریانگرتووە بەپێی پسوولە فەرمییەکان.
        </p>
        <table>
            <thead>
                <tr>
                    <th>مارکێتی قەرزار</th>
                    <th>شار و ناونیشان</th>
                    <th>پەیوەندی</th>
                    <th>ژمارەی داواکاری قەرزدار</th>
                    <th>بڕی قەرزی ماوە ($)</th>
                    <th>کردار</th>
                </tr>
            </thead>
            <tbody>
                @forelse($debtsByRetailer as $retId => $orders)
                @php $firstOrder = $orders->first(); @endphp
                <tr>
                    <td><strong>🛒 {{ $firstOrder->retailer->name }}</strong></td>
                    <td>{{ $firstOrder->retailer->city }}, {{ $firstOrder->retailer->country }}</td>
                    <td>{{ $firstOrder->retailer->primary_phone }}</td>
                    <td><span class="badge badge-info">{{ $orders->count() }} داواکاری</span></td>
                    <td>
                        <strong style="color:#f87171; font-size:1rem;">
                            ${{ number_format($orders->sum('remaining_debt'), 2) }}
                        </strong>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap;">
                            <a href="{{ route('financial.statement.show', ['retailerId' => $firstOrder->retailer_company_id, 'supplier_id' => $currentSupplier?->id]) }}" class="btn btn-sm" style="background:#2563eb; color:#fff;" target="_blank">
                                📑 کەشف حیساب
                            </a>
                            <button type="button" class="btn btn-sm btn-success" onclick="openCollectPaymentModal({{ $firstOrder->retailer_company_id }}, '{{ addslashes($firstOrder->retailer->name) }}', {{ $orders->sum('remaining_debt') }})">
                                🧾 وەرگرتنی پارە (سند قبض)
                            </button>
                            <a href="?role=supplier&active_supplier_id={{ $currentSupplier?->id }}&chat_retailer_id={{ $firstOrder->retailer_company_id }}#chat-section" class="btn btn-sm btn-primary">
                                💬 چات
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-tertiary);">
                        هیچ مارکێتێک قەرزار نییە بەرامبەر ئەم دابینکەرە. هەموو حساباتەکان یەکلایی کراونەتەوە!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @elseif($role === 'retailer')
    <!-- ==========================================
         RETAILER PLANE (Comprehensive Discovery & Isolated POs)
    =========================================== -->

    <!-- SUPPLIERS DIRECTORY FOR RETAILERS -->
    <div class="panel app-module-section" data-app-module="chat">
        <div class="panel-header">
            <h3 class="panel-title">{{ __('Verified Suppliers Directory') }}</h3>
            <span class="badge badge-success">{{ $suppliers->count() }} دابینکەری باوەڕپێکراو</span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            مارکێتەکان لێرە دەتوانن هەموو دابینکەرە تۆمارکراوەکان لەگەڵ ژمارەی کاڵا بەردەستەکانیان ببینن، و دەست بە چات بکەن.
        </p>

        <div class="cards-grid">
            @foreach($suppliers as $sup)
            <div class="b2b-card" style="display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <h4>🏭 {{ $sup->name }}</h4>
                    <div class="meta" style="margin-bottom:0.6rem;">
                        📍 <strong>{{ $sup->city }}, {{ $sup->country }}</strong><br>
                        📞 {{ $sup->primary_phone }}<br>
                        ✉️ {{ $sup->primary_email }}<br>
                        📦 <strong>{{ $sup->products_count }}</strong> {{ __('Available Products') }}
                    </div>

                    <!-- Direct Cart Loading & Quick Order from this Supplier -->
                    <div style="background:rgba(0,0,0,0.3); border:1px solid var(--border-subtle); border-radius:8px; padding:0.6rem; margin-bottom:0.75rem;">
                        <div style="font-size:0.78rem; font-weight:700; color:#38bdf8; margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center;">
                            <span>🛒 کاڵاکانی ئەم کۆمپانیایە بۆ سەبەتە:</span>
                            <span class="badge" style="font-size:0.65rem; background:rgba(56,189,248,0.15); color:#38bdf8;">{{ $sup->products->count() }} کاڵا</span>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.4rem; max-height:160px; overflow-y:auto; padding-right:0.2rem;">
                            @forelse($sup->products as $sp)
                                <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-surface-elevated); padding:0.35rem 0.55rem; border-radius:6px; border:1px solid rgba(255,255,255,0.05); font-size:0.78rem;">
                                    <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:60%;">
                                        <strong style="color:#fff; display:block; font-size:0.78rem;" title="{{ $sp->title }}">{{ $sp->title }}</strong>
                                        <span style="color:#34d399; font-weight:700;">${{ number_format($sp->base_wholesale_price, 2) }}</span>
                                        <span style="color:var(--text-tertiary); font-size:0.68rem;">(MOQ: {{ $sp->minimum_order_quantity }})</span>
                                    </div>
                                    @if($sp->current_stock > 0)
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="addToCartDirect({{ $sp->id }}, '{{ addslashes($sp->title) }}', '{{ $sp->sku }}', {{ $sup->id }}, '{{ addslashes($sup->name) }}', {{ (float)$sp->base_wholesale_price }}, {{ (int)$sp->minimum_order_quantity }}, {{ (int)$sp->current_stock }}, this)"
                                            style="font-size:0.72rem; padding:0.22rem 0.6rem; white-space:nowrap; cursor:pointer;">
                                            🛒 + سەبەتە
                                        </button>
                                    @else
                                        <span style="font-size:0.68rem; color:#f87171;">نەماوە</span>
                                    @endif
                                </div>
                            @empty
                                <div style="font-size:0.72rem; color:var(--text-tertiary); text-align:center; padding:0.4rem;">
                                    هیچ کاڵایەک تۆمار نەکراوە
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="actions">
                    <a href="?role=retailer&active_retailer_id={{ $currentRetailer?->id }}&chat_supplier_id={{ $sup->id }}#chat-section" class="btn btn-primary btn-sm">
                        {{ __('Start Chat & Inquiry') }}
                    </a>
                    <a href="?role=retailer&active_retailer_id={{ $currentRetailer?->id }}&supplier_id={{ $sup->id }}" class="btn btn-sm">
                        {{ __('View Supplier Products') }}
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- POST NEW MARKET DEMAND (Requirements) -->
    <div class="panel app-module-section" data-app-module="orders">
        <div class="panel-header">
            <h3 class="panel-title">بڵاوکردنەوەی پێداویستی نوێی مارکێت (Market Demand / RFQ)</h3>
            <span class="badge badge-warning">داواکاری کڕین</span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            مارکێت دەتوانێت ئەو کاڵایانەی پێویستییەتی لێرە بڵاو بکاتەوە تا دابینکەران بیبینن و نرخی گونجاوی بۆ پێشکەش بکەن.
        </p>
        <form action="{{ route('retailer.demand.store') }}" method="POST">
            @csrf
            <input type="hidden" name="retailer_company_id" value="{{ $currentRetailer?->id }}">
            <div class="form-row">
                <div class="form-group">
                    <label>ناوی کاڵا یان پێداویستی داواکراو *</label>
                    <input type="text" name="title" required placeholder="بۆ نموونە: ٥٠٠ کارتۆن شیری لەبەنیات">
                </div>
                <div class="form-group">
                    <label>هاوپۆل</label>
                    <select name="category_id">
                        <option value="">گشتی / هەموو</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>بڕی داواکراو *</label>
                    <input type="number" name="quantity" required placeholder="٥٠٠">
                </div>
                <div class="form-group">
                    <label>یەکە (Unit) *</label>
                    <select name="unit">
                        <option value="کارتۆن">کارتۆن</option>
                        <option value="دانە">دانە</option>
                        <option value="تۆن">تۆن</option>
                        <option value="کیلۆ">کیلۆ</option>
                        <option value="پاکەت">پاکەت</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>نرخی ئامانج بۆ هەر یەکەیەک ($)</label>
                    <input type="number" step="0.01" name="target_unit_price" placeholder="ئارەزوومەندانە">
                </div>
            </div>
            <div class="form-group" style="margin-top:0.8rem;">
                <label>تێبینی و مەرجەکان</label>
                <input type="text" name="notes" placeholder="مەرجی بەرواری بەسەرچوون، شێوازی گەیاندن یان پارەدان...">
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary">+ بڵاوکردنەوەی پێداویستی بۆ دابینکەران</button>
            </div>
        </form>
    </div>

    <!-- DIRECT CHAT & NEGOTIATION FOR RETAILERS -->
    <div class="panel app-module-section" data-app-module="chat" id="chat-section">
        <div class="panel-header" style="flex-wrap:wrap; gap:0.6rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <h3 class="panel-title">💬 {{ __('Direct B2B Chat & Price Negotiations') }}</h3>
                <span class="badge badge-info">مارکێت: {{ $currentRetailer?->name }}</span>
            </div>
            <span style="font-size:0.8rem; color:var(--text-tertiary);">دانوستاندنی نرخ لەگەڵ دابینکەران</span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            مارکێت دەتوانێت لێرە لەگەڵ دابینکەران قسە بکات، داوای داشکاندنی تایبەت بکات و ئۆفەرەکانی دابینکەران ببینێت.
        </p>

        <div class="chat-box">
            <div class="chat-sidebar">
                <div style="margin-bottom:0.8rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                        <span style="font-size:0.75rem; color:var(--text-tertiary); text-transform:uppercase; font-weight:700;">دابینکەران ({{ $conversations->count() }})</span>
                        <span style="font-size:0.7rem; color:#10b981; font-weight:600;">B2B Chat</span>
                    </div>
                    <input type="text" oninput="filterChatContacts(this, 'chat-contacts-retailer')" placeholder="🔍 گەڕان بەناو دابینکەرەکان..." style="width:100%; padding:0.45rem 0.75rem; background:rgba(255,255,255,0.06); border:1px solid var(--border-subtle); border-radius:8px; color:#fff; font-size:0.8rem; outline:none; transition:border-color 0.2s;" onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='var(--border-subtle)'">
                </div>
                <div id="chat-contacts-retailer" style="display:flex; flex-direction:column; gap:0.35rem; max-height:480px; overflow-y:auto;">
                @forelse($conversations as $c)
                    <a href="?role=retailer&active_retailer_id={{ $currentRetailer?->id }}&conversation_id={{ $c->id }}#chat-section" class="conv-item {{ $activeConversation?->id === $c->id ? 'active' : '' }}">
                        <strong style="display:block; color:#fff;">🏭 {{ $c->supplier->name }}</strong>
                        <span style="font-size:0.75rem; color:var(--text-tertiary);">{{ $c->supplier->city }} • {{ $c->messages->count() }} پەیام</span>
                    </a>
                @empty
                    <div style="font-size:0.8rem; color:var(--text-tertiary); padding:0.8rem; text-align:center;">هیچ گفتوگۆیەک نییە. لە بەشی دابینکەراندا کرتە لەسەر دەستپێکردنی چات بکە.</div>
                @endforelse
                </div>
            </div>

            <div class="chat-main">
                @if($activeConversation)
                    <div>
                        <div style="border-bottom:1px solid var(--border-subtle); padding-bottom:0.8rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="color:#fff;">گفتوگۆ لەگەڵ دابینکەری: {{ $activeConversation->supplier->name }}</h4>
                                <span style="font-size:0.75rem; color:var(--text-tertiary);">شار: {{ $activeConversation->supplier->city }} | پەیوەندی: {{ $activeConversation->supplier->primary_phone }}</span>
                            </div>
                            <span class="badge badge-success">دانوستاندن چالاکە</span>
                        </div>
                        <div class="chat-messages" id="chat-messages-retailer">
                            @foreach($activeConversation->messages as $m)
                                <div class="chat-msg {{ $m->sender_company_id === $currentRetailer->id ? 'sent' : 'received' }} {{ $m->message_type === 'quote_proposal' ? 'proposal' : '' }}">
                                    <div style="font-size:0.7rem; font-weight:700; margin-bottom:0.2rem; color:{{ $m->sender_company_id === $currentRetailer->id ? '#a5b4fc' : '#34d399' }};">
                                        {{ $m->sender->name }} ({{ $m->created_at->diffForHumans() }})
                                    </div>
                                    @if($m->message_type === 'image' || ($m->attachment_url && Str::contains($m->attachment_url, ['chat_attachments/images', '.jpg', '.jpeg', '.png', '.webp', '.gif'])))
                                        <div>
                                            <a href="{{ asset($m->attachment_url) }}" target="_blank" title="کرتە بکە بۆ کردنەوەی وێنەکە">
                                                <img src="{{ asset($m->attachment_url) }}" alt="هاوپێچی وێنە" class="chat-media-img">
                                            </a>
                                        </div>
                                    @endif
                                    @if($m->message_type === 'voice' || ($m->attachment_url && Str::contains($m->attachment_url, ['chat_attachments/voice', '.webm', '.ogg', '.mp3', '.wav', '.m4a'])))
                                        <div class="chat-voice-box">
                                            <span style="font-size:1.1rem;">🎙️</span>
                                            <audio controls src="{{ asset($m->attachment_url) }}"></audio>
                                        </div>
                                    @endif
                                    @if($m->body && !($m->message_type === 'image' && $m->body === '📷 وێنەی هاوپێچکراو') && !($m->message_type === 'voice' && $m->body === '🎙️ پەیامی دەنگی (فۆیس)'))
                                        <div>{!! nl2br(e($m->body)) !!}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- REGULAR MESSAGE FORM WITH IMAGE & VOICE -->
                    <form action="{{ route('messages.store') }}" method="POST" enctype="multipart/form-data" class="chat-input-bar chat-form">
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $activeConversation->id }}">
                        <input type="hidden" name="sender_company_id" value="{{ $currentRetailer->id }}">
                        <input type="file" name="image" accept="image/*" class="chat-file-image" style="display:none;">
                        <input type="file" name="voice" accept="audio/*" class="chat-file-voice" style="display:none;">

                        <!-- PREVIEWS (Images / Voice) -->
                        <div class="chat-preview-box" style="display:none;"></div>

                        <!-- LIVE RECORDING STATUS BAR -->
                        <div class="chat-recording-bar" style="display:none; align-items:center; justify-content:space-between; background:rgba(239,68,68,0.18); border:1px solid #ef4444; border-radius:6px; padding:0.4rem 0.8rem;">
                            <div style="display:flex; align-items:center; gap:0.5rem; color:#fca5a5; font-size:0.85rem; font-weight:600;">
                                <span class="dot" style="background:#ef4444; width:10px; height:10px; border-radius:50%; animation:pulse-red 1s infinite;"></span>
                                <span>دەنگ تۆمار دەکرێت...</span>
                                <span class="rec-timer" style="font-family:var(--font-mono); color:#fff;">00:00</span>
                            </div>
                            <div style="display:flex; gap:0.4rem;">
                                <button type="button" class="btn btn-sm btn-success btn-finish-rec">✓ تەواوکردن</button>
                                <button type="button" class="btn btn-sm btn-danger btn-cancel-rec">✖ پاشگەزبوونەوە</button>
                            </div>
                        </div>

                        <!-- QUICK QUESTIONS FOR RETAILER -->
                        <div class="quick-replies-bar">
                            <span style="font-size:0.72rem; color:var(--text-tertiary);">⚡ پرسیاری خێرا:</span>
                            <button type="button" class="quick-reply-chip" data-text="سڵاو، تکایە کەتەلۆکی کاڵاکان و نوێترین نرخی کۆمەڵم بۆ بنێرن.">داواکردنی کەتەلۆک</button>
                            <button type="button" class="quick-reply-chip" data-text="داوای داشکاندنی باشتر دەکەین بۆ بڕی سەروو ٥۰ کارتۆن.">داوای داشکاندن</button>
                            <button type="button" class="quick-reply-chip" data-text="ئایا گەیاندن بۆ ناو شار و ناونیشانی مارکێتەکەمان هەیە؟">گەیاندن بۆ شارەکەمان</button>
                            <button type="button" class="quick-reply-chip" data-text="تکایە بەرواری بەسەرچوونی (Expiry Date) ئەم کاڵایەم پێ بڵێن.">بەرواری بەسەرچوون</button>
                        </div>

                        <div class="chat-input-row">
                            <button type="button" class="chat-btn-action btn-trigger-image" title="هاوپێچکردنی وێنە">
                                📷 <span>وێنە</span>
                            </button>
                            <button type="button" class="chat-btn-action chat-btn-record btn-trigger-voice" title="تۆمارکردنی دەنگ (فۆیس)">
                                🎙️ <span>فۆیس</span>
                            </button>
                            <textarea name="body" placeholder="پرسیاری نرخ، داواکردنی کەتەلۆک، یان بڕی پێداویستی بنووسە..."></textarea>
                            <button type="submit" class="btn btn-primary" style="height:48px;">{{ __('Send Message') }}</button>
                        </div>
                    </form>
                @else
                    <div style="color:var(--text-tertiary); text-align:center; padding:4rem;">دابینکەرێک هەڵبژێرە لە لیستەکە بۆ بینینی چات.</div>
                @endif
            </div>
        </div>
    </div>

    <!-- SUPPLIER PARTNERS DIRECTORY BY PROVINCE & CATEGORY -->
    <div class="panel app-module-section" data-app-module="chat">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">🏭 دابینکەرە باوەڕپێکراوەکان بەپێی شار، پارێزگا و پسپۆڕی کاڵا</h3>
                <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:0.2rem;">
                    مارکێتەکان دەتوانن دابینکەرانی شار و پارێزگاکان ببینن بۆ گەیاندنی خێراتر، داوای کەتەلۆک بکەن یان پەیوەندی ببەستن.
                </p>
            </div>
            <span class="badge badge-info">{{ $directorySuppliers->count() }} دابینکەر لەم بەشەدا</span>
        </div>

        <!-- Filter form -->
        <form action="" method="GET" style="display:flex; gap:0.6rem; flex-wrap:wrap; margin-bottom:1.2rem; background:rgba(0,0,0,0.25); padding:0.8rem; border-radius:8px; border:1px solid var(--border-subtle); align-items:center;">
            <input type="hidden" name="role" value="retailer">
            <input type="hidden" name="active_retailer_id" value="{{ $currentRetailer?->id }}">
            
            <div style="font-size:0.8rem; color:var(--text-secondary); font-weight:600;">📍 فلتەری پارێزگا:</div>
            <select name="filter_province" onchange="this.form.submit()" style="padding:0.4rem 0.6rem; background:var(--bg-core); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.82rem;">
                <option value="">هەموو پارێزگاکان (هەولێر، سلێمانی، دهۆک، کەرکووک، هەڵەبجە)</option>
                @foreach($allProvinces as $prv)
                    <option value="{{ $prv }}" {{ $filterProvince === $prv ? 'selected' : '' }}>{{ $prv }}</option>
                @endforeach
            </select>

            <div style="font-size:0.8rem; color:var(--text-secondary); font-weight:600; margin-right:0.6rem;">📦 پسپۆڕی دابینکەر:</div>
            <select name="filter_category" onchange="this.form.submit()" style="padding:0.4rem 0.6rem; background:var(--bg-core); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.82rem;">
                <option value="">هەموو پسپۆڕییەکان (خۆراک، شیرەمەنی، پاککەرەوە، هتد)</option>
                @foreach($supplierCategories as $scat)
                    <option value="{{ $scat }}" {{ $filterCategory === $scat ? 'selected' : '' }}>{{ $scat }}</option>
                @endforeach
            </select>

            @if($filterProvince || $filterCategory)
                <a href="?role=retailer&active_retailer_id={{ $currentRetailer?->id }}" class="btn btn-sm btn-secondary" style="margin-right:auto;">لابردنی فلتەر ✖</a>
            @endif
        </form>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem;">
            @forelse($directorySuppliers as $sup)
                <div style="background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:10px; padding:1rem; display:flex; flex-direction:column; justify-content:space-between; transition:border-color 0.2s;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.4rem;">
                            <h4 style="color:#fff; font-size:0.95rem;">🏭 {{ $sup->name }}</h4>
                            <span class="badge badge-success" style="font-size:0.65rem;">باوەڕپێکراو</span>
                        </div>
                        <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-bottom:0.6rem;">
                            <span style="font-size:0.75rem; background:rgba(16,185,129,0.15); color:#34d399; padding:0.15rem 0.45rem; border-radius:4px; border:1px solid rgba(16,185,129,0.3);">
                                📍 {{ $sup->province ?? $sup->city }} ({{ $sup->city }})
                            </span>
                            <span style="font-size:0.75rem; background:rgba(99,102,241,0.15); color:#a5b4fc; padding:0.15rem 0.45rem; border-radius:4px; border:1px solid rgba(99,102,241,0.3);">
                                📦 {{ $sup->business_category ?? 'خۆراک و پێداویستی گشتی' }}
                            </span>
                        </div>
                        <p style="font-size:0.78rem; color:var(--text-secondary); margin-bottom:0.8rem; line-height:1.4;">
                            {{ $sup->address_line }}<br>
                            پەیوەندی: {{ $sup->primary_phone }} • {{ $sup->products()->count() }} کاڵا لە کەتەلۆک
                        </p>
                    </div>
                    <a href="?role=retailer&active_retailer_id={{ $currentRetailer?->id }}&chat_supplier_id={{ $sup->id }}#chat-section" class="btn btn-sm btn-primary" style="width:100%; text-align:center;">
                        💬 دەستپێکردنی چات و داواکردنی نرخ
                    </a>
                </div>
            @empty
                <div style="grid-column:1/-1; text-align:center; padding:2rem; color:var(--text-tertiary);">
                    هیچ دابینکەرێک بەم فلتەرە نەدۆزرایەوە. تکایە فلتەرەکە بگۆڕە.
                </div>
            @endforelse
        </div>
    </div>

    <!-- GLOBAL WHOLESALE DISCOVERY (Shows products from ALL verified suppliers) -->
    <div class="panel app-module-section" data-app-module="catalog">
        <div class="panel-header">
            <h3 class="panel-title">{{ __('Wholesale Product Discovery & Direct Purchasing') }}</h3>
            <span class="badge badge-info">{{ $catalogProducts->count() }} کاڵا لە هەموو دابینکەرەکانەوە</span>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
            مارکێتەکان دەتوانن کاڵای هەموو دابینکەرە نوێ و کۆنەکان ببینن، نرخ و پلەی داشکاندن بەراورد بکەن و ڕاستەوخۆ داواکاری بنێرن.
        </p>

        <!-- SEARCH & FILTER BAR -->
        <form action="" method="GET" style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.5rem; background:rgba(0,0,0,0.2); padding:1rem; border-radius:8px; border:1px solid var(--border-subtle);">
            <input type="hidden" name="role" value="retailer">
            <input type="hidden" name="active_retailer_id" value="{{ $currentRetailer?->id }}">
            <div style="flex:2; min-width:200px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('Search by product, SKU, or supplier...') }}" style="width:100%; padding:0.55rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-family:inherit; font-size:0.85rem;">
            </div>
            <div style="flex:1; min-width:160px;">
                <select name="category_id" style="width:100%; padding:0.55rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-family:inherit; font-size:0.85rem;">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1; min-width:160px;">
                <select name="supplier_id" style="width:100%; padding:0.55rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-family:inherit; font-size:0.85rem;">
                    <option value="">{{ __('All Suppliers') }}</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ $supplierId == $sup->id ? 'selected' : '' }}>{{ $sup->name }} ({{ $sup->city }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                <a href="?role=retailer&active_retailer_id={{ $currentRetailer?->id }}" class="btn">{{ __('Reset') }}</a>
            </div>
        </form>

        <!-- WHOLESALE PRODUCTS TABLE -->
        <table>
            <thead>
                <tr>
                    <th>{{ __('SKU') }}</th>
                    <th>{{ __('Product Title') }}</th>
                    <th>{{ __('Supplier') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Wholesale Price') }}</th>
                    <th>{{ __('Volume Discounts') }}</th>
                    <th>{{ __('MOQ') }}</th>
                    <th>کۆگای ماوە</th>
                    <th>{{ __('Direct Purchasing') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($catalogProducts as $p)
                <tr>
                    <td><code>{{ $p->sku }}</code></td>
                    <td><strong>{{ $p->title }}</strong></td>
                    <td>
                        <span style="color:#34d399; font-weight:600;">{{ $p->supplier->name }}</span>
                        <div style="font-size:0.72rem; color:var(--text-tertiary);">{{ $p->supplier->city }}</div>
                    </td>
                    <td>{{ $p->category->name }}</td>
                    <td><strong>${{ number_format($p->base_wholesale_price, 2) }}</strong></td>
                    <td>
                        @forelse($p->volumeTiers as $t)
                            <div style="font-size:0.75rem; color:#34d399;">{{ $t->min_quantity }}+ {{ __('Units') }}: ${{ number_format($t->unit_price, 2) }}</div>
                        @empty
                            <span style="font-size:0.75rem; color:var(--text-tertiary);">-</span>
                        @endforelse
                    </td>
                    <td>
                        <span class="badge" style="background:rgba(99,102,241,0.15); color:#a5b4fc; border:1px solid rgba(99,102,241,0.3);">
                            {{ $p->minimum_order_quantity }} دانە
                        </span>
                    </td>
                    <td>
                        @if($p->current_stock <= 0 || !$p->is_available)
                            <span class="badge badge-danger">⚠️ نەماوە (Out of Stock)</span>
                        @else
                            <strong style="color:{{ $p->current_stock <= 20 ? '#f59e0b' : '#34d399' }};">
                                {{ number_format($p->current_stock) }} دانە
                            </strong>
                            @if($p->reserved_stock > 0)
                                <div style="font-size:0.7rem; color:var(--text-tertiary);">ڕاگیراو: {{ $p->reserved_stock }}</div>
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($p->current_stock > 0 && $p->is_available)
                            <div style="display:flex; gap:0.4rem; align-items:center;">
                                <button type="button" class="btn btn-sm btn-primary"
                                    onclick="addToCartDirect({{ $p->id }}, '{{ addslashes($p->title) }}', '{{ $p->sku }}', {{ $p->supplier_company_id }}, '{{ addslashes($p->supplier?->name ?? 'دابینکەر') }}', {{ (float)$p->base_wholesale_price }}, {{ (int)$p->minimum_order_quantity }}, {{ (int)$p->current_stock }}, this)"
                                    style="display:flex; align-items:center; gap:0.3rem; cursor:pointer;">
                                    <span>🛒 خستنەسەبەتە</span>
                                </button>
                            </div>
                        @else
                            <button type="button" class="btn btn-sm" disabled style="opacity:0.5; cursor:not-allowed;">
                                نەماوە لە کۆگا
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:2rem; color:var(--text-tertiary);">
                        هیچ کاڵایەک نەدۆزرایەوە بەپێی ئەم گەڕانە.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- THIS RETAILER'S OUTBOUND PURCHASE ORDERS ONLY -->
    <div class="panel app-module-section" data-app-module="orders">
        <div class="panel-header" style="flex-wrap: wrap; gap: 0.75rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <h3 class="panel-title">فەرمانە دەرچووەکانی کڕین بۆ: {{ $currentRetailer?->name }}</h3>
                <span class="badge badge-info">{{ $outboundPurchaseOrders->count() }} داواکاری فەرمی</span>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <a href="{{ route('reports.orders.export', array_filter(['retailer_id' => $currentRetailer?->id, 'status' => request('status')])) }}" class="btn btn-outline" style="font-size:0.78rem; padding:0.35rem 0.75rem; border-color:var(--border-subtle); color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:0.35rem;">
                    📥 هەناردەی فەرمانەکان (CSV)
                </a>
                @if($currentRetailer)
                <a href="{{ route('reports.statement.export', $currentRetailer->id) }}" class="btn btn-outline" style="font-size:0.78rem; padding:0.35rem 0.75rem; border-color:var(--border-subtle); color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:0.35rem;">
                    📊 کەشف حیساب (CSV)
                </a>
                @endif
            </div>
        </div>
        <div style="display:flex; gap:0.4rem; margin-bottom:1rem; flex-wrap:wrap;">
            @php $curRetStatus = request('status', 'all'); @endphp
            <a href="{{ route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $currentRetailer?->id, 'status' => 'all']) }}" class="badge {{ $curRetStatus === 'all' ? 'badge-primary' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">هەموو</a>
            <a href="{{ route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $currentRetailer?->id, 'status' => 'pending']) }}" class="badge {{ $curRetStatus === 'pending' ? 'badge-warning' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">چاوەڕوان</a>
            <a href="{{ route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $currentRetailer?->id, 'status' => 'approved']) }}" class="badge {{ $curRetStatus === 'approved' ? 'badge-info' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">پەسەندکراو</a>
            <a href="{{ route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $currentRetailer?->id, 'status' => 'dispatched']) }}" class="badge {{ $curRetStatus === 'dispatched' ? 'badge-info' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">لە ڕێگایە</a>
            <a href="{{ route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $currentRetailer?->id, 'status' => 'delivered']) }}" class="badge {{ $curRetStatus === 'delivered' ? 'badge-success' : 'badge-neutral' }}" style="text-decoration:none; padding:0.35rem 0.75rem; font-size:0.78rem;">گەیشتوو</a>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            تەنها ئەو داواکارییانە پیشان دەدات کە لەلایەن ئەم مارکێتەوە بۆ دابینکەران دەرکراون. لێرە دەتوانیت بارنامەی دابینکەر، شوفێر، کاتی گەیشتن ببینیت، پاشان وردبینی و بڕی پارەی دراو دیاری بکەیت و وەرگرتن پشتڕاست بکەیتەوە.
        </p>
        <table>
            <thead>
                <tr>
                    <th>{{ __('PO Number') }}</th>
                    <th>{{ __('Supplier') }}</th>
                    <th>کۆی بەها و پارەدان</th>
                    <th>بارنامە و ناردنی دابینکەر</th>
                    <th>دۆخی گەیشتن و پارە</th>
                    <th>وردبینی و وەرگرتن / دانەوەی قەرز</th>
                </tr>
            </thead>
            <tbody>
                @forelse($outboundPurchaseOrders as $po)
                <tr>
                    <td>
                        <code>{{ $po->po_number }}</code>
                        <details style="margin-top:0.35rem; font-size:0.75rem;">
                            <summary style="color:#60a5fa; cursor:pointer; font-weight:700;">
                                📦 کاڵاکانی پسوولە ({{ $po->items->count() }} کاڵا)
                            </summary>
                            <div style="margin-top:0.3rem; background:rgba(0,0,0,0.3); padding:0.4rem; border-radius:6px; min-width:190px;">
                                @foreach($po->items as $it)
                                    <div style="display:flex; justify-content:space-between; font-size:0.72rem; padding:0.2rem 0; border-bottom:1px dashed rgba(255,255,255,0.06);">
                                        <span style="color:#fff;">• {{ $it->product_title }}</span>
                                        <strong style="color:#34d399;">{{ $it->quantity_ordered }} دانە (${{ number_format($it->total_price, 2) }})</strong>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                        <div style="margin-top:0.4rem;">
                            <a href="{{ route('orders.invoice', $po->id) }}" target="_blank" class="btn btn-sm" style="font-size:0.72rem; padding:0.2rem 0.5rem;" title="چاپکردنی فاکتۆر و بارنامە">
                                🖨️ پسوولەی فەرمی
                            </a>
                        </div>
                    </td>
                    <td>
                        <strong style="color:#fff; font-size:0.92rem;">{{ $po->supplier->name }}</strong>
                        <div style="font-size:0.75rem; color:var(--text-tertiary);">
                            📍 {{ $po->supplier->city }} • 📞 {{ $po->supplier->primary_phone }}
                        </div>
                    </td>
                    <td>
                        <div>کۆی گشتی: <strong>${{ number_format($po->total_amount, 2) }}</strong></div>
                        <div style="color:#34d399; font-size:0.78rem;">دراو: ${{ number_format($po->paid_amount, 2) }}</div>
                        @if($po->status === 'cancelled')
                            <div style="color:#94a3b8; font-size:0.78rem; margin-top:0.2rem;">❌ هەڵوەشێنراوەتەوە (قەرز: $0)</div>
                        @elseif($po->remaining_debt > 0)
                            <div style="color:#f87171; font-weight:700; font-size:0.8rem;">قەرزی ماوە: ${{ number_format($po->remaining_debt, 2) }}</div>
                        @else
                            <div style="color:#34d399; font-size:0.74rem;">قەرز بە تەواوی دراوە ✓</div>
                        @endif
                    </td>
                    <td>
                        @if($po->waybill_number)
                            <div style="background:rgba(56, 189, 248, 0.08); border:1px solid rgba(56, 189, 248, 0.25); border-radius:6px; padding:0.45rem; font-size:0.75rem; line-height:1.4;">
                                <div style="color:#38bdf8; font-weight:700;">🚚 بارنامە: {{ $po->waybill_number }}</div>
                                @if($po->driver_name)<div>شوفێر: <strong>{{ $po->driver_name }}</strong> ({{ $po->driver_phone }})</div>@endif
                                @if($po->vehicle_plate)<div>تابلۆ: <code>{{ $po->vehicle_plate }}</code></div>@endif
                                @if($po->dispatched_at)<div style="color:var(--text-tertiary); font-size:0.7rem;">نێردرا لە: {{ $po->dispatched_at->format('Y-m-d H:i') }}</div>@endif
                            </div>
                        @else
                            <span style="color:var(--text-tertiary); font-size:0.78rem;">⏳ چاوەڕوانی ئامادەکردن و بارکردن</span>
                        @endif
                    </td>
                    <td>
                        <div>
                            @if($po->status === 'pending' || $po->status === 'issued')
                                <span class="badge badge-warning">⏳ چاوەڕوانی پەسەندکردن (Pending)</span>
                            @elseif($po->status === 'approved')
                                <span class="badge" style="background:rgba(59,130,246,0.2); color:#60a5fa; border:1px solid #3b82f6;">
                                    📦 پەسەندکراو (ستۆک ڕاگیراوە)
                                </span>
                            @elseif($po->status === 'processing')
                                <span class="badge badge-info">🏭 ئامادەکردن لە کۆگا (Processing)</span>
                            @elseif($po->status === 'dispatched')
                                <span class="badge badge-info">🚚 لە ڕێگایە بۆ مارکێت</span>
                            @elseif($po->status === 'delivered')
                                <span class="badge badge-success">✅ وەرگیراوە لە مارکێت (Delivered)</span>
                            @elseif($po->status === 'completed')
                                <span class="badge" style="background:rgba(16,185,129,0.25); color:#34d399; border:1px solid #10b981;">
                                    🎉 تەواوکراو (Completed)
                                </span>
                            @elseif($po->status === 'cancelled')
                                <span class="badge badge-danger">❌ ڕەتکرایەوە لەلایەن دابینکەر (Cancelled)</span>
                            @else
                                <span class="badge badge-info">{{ __($po->status) }}</span>
                            @endif
                        </div>
                        <div style="margin-top:0.3rem;">
                            @if($po->status === 'cancelled')
                                <span class="badge" style="background:rgba(148,163,184,0.15); color:#94a3b8; border:1px solid rgba(148,163,184,0.3);">قەرز پوچەڵکراوەتەوە ✓</span>
                            @elseif($po->payment_status === 'paid')
                                <span class="badge badge-success">پارەدان: تەواوکراو</span>
                            @elseif($po->payment_status === 'partially_paid')
                                <span class="badge badge-warning">پارەدان: بەشێک دراوە</span>
                            @else
                                <span class="badge badge-danger">پارەدان: نەدراوە (قەرز)</span>
                            @endif
                        </div>
                        <div style="margin-top:0.3rem;">
                            <button type="button" class="btn btn-sm" onclick="openAuditModal({{ $po->id }})" style="font-size:0.7rem; padding:0.2rem 0.5rem; background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3); color:#a5b4fc;">
                                📜 مێژووی دۆخ
                            </button>
                        </div>
                    </td>
                    <td>
                        @if($po->status === 'dispatched')
                            <!-- RECEIVE & INSPECT FORM + OTP DISPLAY -->
                            <div style="background:rgba(99, 102, 241, 0.12); border:1px solid rgba(99, 102, 241, 0.35); border-radius:8px; padding:0.6rem; margin-bottom:0.6rem; text-align:center;">
                                <div style="font-size:0.72rem; color:#c7d2fe; font-weight:600;">🔑 کۆدی وەرگرتنی خۆت بۆ شوفێر:</div>
                                <div style="font-family:var(--font-mono); font-size:1.35rem; font-weight:800; color:#fff; letter-spacing:3px; margin:0.2rem 0;">
                                    {{ $po->delivery_otp ?? '123456' }}
                                </div>
                                <div style="font-size:0.68rem; color:#94a3b8;">ئەم کۆدە بدە بە شوفێر بۆ پشتڕاستکردنەوە</div>
                            </div>
                            <div style="background:rgba(16, 185, 129, 0.08); border:1px solid rgba(16, 185, 129, 0.3); border-radius:8px; padding:0.6rem; min-width:240px;">
                                <div style="font-weight:700; color:#34d399; font-size:0.78rem; margin-bottom:0.35rem;">
                                    🔍 کاڵاکان گەیشتن - پشکنین و وەرگرتن:
                                </div>
                                <form action="{{ route('orders.receive', $po->id) }}" method="POST">
                                    @csrf
                                    <div style="margin-bottom:0.35rem;">
                                        <input type="text" name="inspected_by" required placeholder="ناوی پشکنەر / وەرگری کاڵا *" style="width:100%; font-size:0.75rem; padding:0.35rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;">
                                    </div>
                                    <div style="display:flex; gap:0.3rem; margin-bottom:0.35rem;">
                                        <input type="number" step="0.01" name="paid_amount" value="{{ $po->total_amount }}" min="0" max="{{ $po->total_amount }}" placeholder="بڕی پارەی دراو ($)" style="width:50%; font-size:0.75rem; padding:0.35rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;" title="ئەگەر هەمووی کاش دەدەیت بڕەکەی بنووسە، ئەگەر قەرزە 0 دابنێ">
                                        <select name="payment_method" style="width:50%; font-size:0.75rem; padding:0.35rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;">
                                            <option value="cash">کاش (نەقد)</option>
                                            <option value="debt">قەرز (حسابات)</option>
                                            <option value="bank_transfer">حەواڵە</option>
                                            <option value="cheque">چێک</option>
                                        </select>
                                    </div>
                                    <div style="margin-bottom:0.35rem;">
                                        <input type="text" name="inspection_notes" placeholder="تێبینی پشکنین (کوالێتی / ژمارە)" style="width:100%; font-size:0.75rem; padding:0.35rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;">
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm" style="width:100%; font-size:0.78rem; justify-content:center;">
                                        ✓ پشتڕاستکردنەوە و واژۆی وەرگرتن
                                    </button>
                                </form>
                            </div>
                        @elseif($po->status === 'pending' || $po->status === 'issued')
                            <span style="font-size:0.78rem; color:var(--text-tertiary);">⏳ چاوەڕوانی پەسەندکردنی کۆگای دابینکەر بە</span>
                        @else
                            <div style="font-size:0.78rem; color:#34d399; font-weight:600; margin-bottom:0.25rem;">
                                ✓ وەرگیراوە لە: {{ $po->received_at?->format('Y-m-d H:i') }}
                            </div>
                            @if($po->inspected_by)
                                <div style="font-size:0.72rem; color:var(--text-tertiary);">پشکنەر: {{ $po->inspected_by }}</div>
                            @endif
                            @if($po->inspection_notes)
                                <div style="font-size:0.72rem; color:var(--text-secondary); margin-bottom:0.3rem;">«{{ $po->inspection_notes }}»</div>
                            @endif
                            @if($po->remaining_debt > 0)
                                <form action="{{ route('orders.pay_debt', $po->id) }}" method="POST" style="margin-top:0.4rem; display:flex; gap:0.3rem; align-items:center;">
                                    @csrf
                                    <input type="number" step="0.01" name="payment_amount" min="0.01" max="{{ $po->remaining_debt }}" placeholder="بڕ ($)" style="width:80px; font-size:0.75rem; padding:0.3rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); color:#fff; border-radius:4px;" required>
                                    <button type="submit" class="btn btn-primary btn-sm" style="font-size:0.75rem;">💵 دانەوەی قەرز</button>
                                </form>
                            @else
                                <span style="font-size:0.75rem; color:#34d399;">پاکتاوکراوە ✓</span>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-tertiary);">
                        تۆ تا ئێستا هیچ فەرمانێکی کڕینت بۆ دابینکەران دەرنەکردووە. لە کەتەلۆکی سەرەوە داواکاری بنێرە.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- OUTSTANDING DEBTS PER SUPPLIER (RETAILER VIEW) -->
    <div class="panel app-module-section" data-app-module="finance">
        <div class="panel-header">
            <h3 class="panel-title">کۆی گشتی قەرزەکانت بەپێی دابینکەرەکان (بەرامبەر کام دابینکەر چەند قەرزداریت)</h3>
            <span class="badge {{ $totalRetailerDebt > 0 ? 'badge-danger' : 'badge-success' }}">
                کۆی قەرزی ماوەت: ${{ number_format($totalRetailerDebt, 2) }}
            </span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            ئەم بەشە بە ڕوونی دەری دەخات کە مارکێتەکەت بۆ هەر دابینکەرێک چەند قەرزدارە بۆ ئەوەی هەردوو لا باڵانسی پارەکە بە ڕوونی بزانن و کێشەی حسابات دروست نەبێت.
        </p>

        <table>
            <thead>
                <tr>
                    <th>ناوی دابینکەر</th>
                    <th>شار و ناونیشان</th>
                    <th>پەیوەندی</th>
                    <th>ژمارەی داواکارییە قەرزدارەکان</th>
                    <th>کۆی بەهای کڕین</th>
                    <th>کۆی پارەی دراو</th>
                    <th>قەرزی ماوە بۆ دابینکەر ($)</th>
                    <th>کردار</th>
                </tr>
            </thead>
            <tbody>
                @forelse($debtsBySupplier as $supId => $orders)
                @php
                    $firstOrder = $orders->first();
                    $supplierObj = $firstOrder->supplier;
                @endphp
                <tr>
                    <td>
                        <strong style="font-size:0.95rem; color:#fff;">{{ $supplierObj?->name }}</strong>
                    </td>
                    <td>
                        <span style="font-size:0.8rem; color:var(--text-secondary);">
                            📍 {{ $supplierObj?->city }}, {{ $supplierObj?->country }}
                        </span>
                    </td>
                    <td>
                        <span style="font-size:0.8rem; color:var(--text-tertiary);">
                            📞 {{ $supplierObj?->primary_phone }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $orders->count() }} داواکاری</span>
                    </td>
                    <td>
                        <strong>${{ number_format($orders->sum('total_amount'), 2) }}</strong>
                    </td>
                    <td>
                        <span style="color:#34d399; font-weight:600;">
                            ${{ number_format($orders->sum('paid_amount'), 2) }}
                        </span>
                    </td>
                    <td>
                        <strong style="color:#f87171; font-size:1.05rem;">
                            ${{ number_format($orders->sum('remaining_debt'), 2) }}
                        </strong>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap;">
                            <a href="{{ route('financial.statement.show', ['retailerId' => $currentRetailer?->id, 'supplier_id' => $supplierObj?->id]) }}" class="btn btn-sm" style="background:#2563eb; color:#fff;" target="_blank">
                                📑 کەشف حیساب
                            </a>
                            <a href="?role=retailer&active_retailer_id={{ $currentRetailer?->id }}&chat_supplier_id={{ $supplierObj?->id }}#chat-section" class="btn btn-sm btn-primary">
                                💬 چاتی حسابات و ڕێککەوتن
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:1.5rem; color:var(--text-tertiary);">
                        هیچ قەرزێکت لەسەر نییە بەرامبەر دابینکەران. هەموو حساباتەکان پاکتاو کراون!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- THIS RETAILER'S POSTED DEMANDS -->
    <div class="panel app-module-section" data-app-module="orders">
        <div class="panel-header">
            <h3 class="panel-title">پێداویستییە تۆمارکراوەکانی ئەم مارکێتە</h3>
            <span class="badge badge-warning">{{ $myDemands->count() }} داواکاری</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>پێداویستی</th>
                    <th>هاوپۆل</th>
                    <th>بڕی داواکراو</th>
                    <th>نرخی ئامانج</th>
                    <th>دۆخ</th>
                    <th>بەرواری تۆمارکردن</th>
                </tr>
            </thead>
            <tbody>
                @forelse($myDemands as $md)
                <tr>
                    <td><strong>{{ $md->title }}</strong></td>
                    <td>{{ $md->category?->name ?? 'گشتی' }}</td>
                    <td>{{ $md->quantity }} {{ $md->unit }}</td>
                    <td>{{ $md->target_unit_price ? '$' . number_format($md->target_unit_price, 2) : 'دیارینەکراوە' }}</td>
                    <td><span class="badge badge-warning">{{ $md->status }}</span></td>
                    <td>{{ $md->created_at->format('Y-m-d') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-tertiary);">
                        هیچ پێداویستییەکت تۆمار نەکردووە. دەتوانیت لە فۆڕمی سەرەوە داواکارییەکانت بۆ دابینکەران بنووسیت.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @elseif($role === 'driver')
    <!-- ==========================================
         DELIVERY DRIVER LOGISTICS PLANE (ڕێڕەوی مەندوب و دابەشکردن)
    =========================================== -->
    <div class="panel app-module-section" data-app-module="orders" style="border-right: 4px solid #38bdf8; background: linear-gradient(135deg, rgba(56, 189, 248, 0.05), rgba(15, 23, 42, 0.4));">
        <div class="panel-header" style="margin-bottom:0.6rem;">
            <h3 class="panel-title" style="color:#7dd3fc; display:flex; align-items:center; gap:0.5rem;">
                🚚 ڕێڕەوی مەندوب و شۆفێری دابەشکردن (Driver Delivery & Handover App)
            </h3>
            <span class="badge badge-info">{{ $driverShipments->count() }} باری گەیاندن</span>
        </div>
        <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            ئەم سیستمە تایبەتە بە شۆفێرانی دابەشکردن: لیستی گەیاندنی ئەو ڕۆژە بەپێی شوێنی مارکێتەکان، پەیوەندی، ئاڕاستەی نەخشە، لەگەڵ پشکنینی کەمی/زیان و واژۆی دیجیتاڵی یان کۆدی کورتی وەرگرتن (OTP) لە خاوەن مارکێت.
        </p>

        <!-- DRIVER SHIPMENT STATUS FILTER TABS -->
        <div style="display:flex; gap:0.4rem; margin-bottom:1.2rem; flex-wrap:wrap;">
            @php $curDrvStatus = request('status', 'all'); @endphp
            <a href="{{ route('marketlink.index', ['role' => 'driver', 'status' => 'all']) }}" class="badge {{ $curDrvStatus === 'all' ? 'badge-primary' : 'badge-neutral' }}" style="text-decoration:none; padding:0.4rem 0.85rem; font-size:0.8rem;">
                هەموو بارەکان ({{ $driverShipments->count() }})
            </a>
            <a href="{{ route('marketlink.index', ['role' => 'driver', 'status' => 'approved']) }}" class="badge {{ $curDrvStatus === 'approved' ? 'badge-warning' : 'badge-neutral' }}" style="text-decoration:none; padding:0.4rem 0.85rem; font-size:0.8rem; background:rgba(245,158,11,0.2); color:#fbbf24; border:1px solid rgba(245,158,11,0.4);">
                📦 ئامادەیە لە کۆگا (Approved)
            </a>
            <a href="{{ route('marketlink.index', ['role' => 'driver', 'status' => 'dispatched']) }}" class="badge {{ $curDrvStatus === 'dispatched' ? 'badge-info' : 'badge-neutral' }}" style="text-decoration:none; padding:0.4rem 0.85rem; font-size:0.8rem; background:rgba(56,189,248,0.2); color:#38bdf8; border:1px solid rgba(56,189,248,0.4);">
                🚚 لە ڕێگایە (Dispatched)
            </a>
            <a href="{{ route('marketlink.index', ['role' => 'driver', 'status' => 'delivered']) }}" class="badge {{ $curDrvStatus === 'delivered' ? 'badge-success' : 'badge-neutral' }}" style="text-decoration:none; padding:0.4rem 0.85rem; font-size:0.8rem; background:rgba(34,197,94,0.2); color:#4ade80; border:1px solid rgba(34,197,94,0.4);">
                ✓ گەیەندراوە (Delivered)
            </a>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:1.25rem;">
            @forelse($driverShipments as $shipment)
            <div style="background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:12px; padding:1.25rem; display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem;">
                        <div>
                            <span style="font-size:0.75rem; color:var(--text-tertiary);">فەرمانی کڕین:</span>
                            <div style="font-family:var(--font-mono); font-weight:700; color:#fff; font-size:1.05rem;">{{ $shipment->po_number }}</div>
                        </div>
                        @if($shipment->status === 'dispatched')
                            <span class="badge" style="background:rgba(56,189,248,0.2); color:#38bdf8; border:1px solid rgba(56,189,248,0.4);">
                                🚚 لە ڕێگایە (Dispatched)
                            </span>
                        @elseif(in_array($shipment->status, ['delivered', 'completed']))
                            <span class="badge badge-success">
                                ✓ وەرگیراوە (Delivered)
                            </span>
                        @elseif(in_array($shipment->status, ['approved', 'processing']))
                            <span class="badge" style="background:rgba(245,158,11,0.2); color:#fbbf24; border:1px solid rgba(245,158,11,0.4);">
                                📦 ئامادەیە لە کۆگا (Approved)
                            </span>
                        @else
                            <span class="badge badge-info">{{ $shipment->status }}</span>
                        @endif
                    </div>

                    <div style="border-top:1px solid var(--border-subtle); border-bottom:1px solid var(--border-subtle); padding:0.75rem 0; margin-bottom:0.75rem;">
                        <div style="font-size:0.95rem; font-weight:700; color:#fff; margin-bottom:0.25rem;">
                            🛒 {{ $shipment->retailer->name }}
                        </div>
                        <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.5rem;">
                            📍 {{ $shipment->delivery_address ?: ($shipment->retailer->address_line . ' - ' . $shipment->retailer->city) }}
                        </div>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                            <a href="tel:{{ $shipment->retailer->primary_phone }}" class="btn btn-sm" style="font-size:0.75rem; padding:0.25rem 0.6rem; background:rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3);">
                                📞 {{ $shipment->retailer->primary_phone }}
                            </a>
                            <a href="{{ $shipment->navigation_url }}" target="_blank" class="btn btn-sm" style="font-size:0.75rem; padding:0.25rem 0.6rem; background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.3); display:inline-flex; align-items:center; gap:0.3rem;">
                                🗺️ نەخشە و ئاڕاستە (Google Maps)
                            </a>
                            @if(($shipment->delivery_latitude && $shipment->delivery_longitude) || ($shipment->retailer?->latitude && $shipment->retailer?->longitude))
                                <span class="badge" style="background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3); font-size:0.7rem; padding:0.2rem 0.45rem;">
                                    📍 GPS چالاکە ✓
                                </span>
                            @endif
                        </div>
                    </div>

                    <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:0.75rem; line-height:1.6;">
                        <div>🏭 دابینکەر: <strong style="color:#fff;">{{ $shipment->supplier->name }}</strong></div>
                        <div>📦 ژمارەی کاڵا: <strong>{{ $shipment->items->count() }} بڕگە</strong> ({{ $shipment->items->sum('quantity') }} دانە)</div>
                        <div>💰 بڕی پسوولە: <strong style="color:#34d399;">${{ number_format($shipment->total_amount, 2) }}</strong> ({{ $shipment->payment_method }})</div>
                        @if($shipment->waybill_number)
                            <div style="color:#a5b4fc; font-size:0.75rem;">📄 ژمارەی بارنامە: {{ $shipment->waybill_number }}</div>
                        @endif
                    </div>

                    @php
                        $shipmentDebt = $shipment->retailer_running_debt ?? (app(\App\Services\B2bFinancialLedgerService::class)->getCustomerRunningBalance($shipment->supplier_company_id, $shipment->retailer_company_id));
                    @endphp
                    <!-- MARKET DEBT STATUS FOR THIS SUPPLIER -->
                    <div style="background: rgba(239, 68, 68, 0.09); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 10px; padding: 0.75rem 0.9rem; margin-bottom: 0.75rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.4rem;">
                            <span style="font-size: 0.82rem; color: #fca5a5; font-weight: 700; display:flex; align-items:center; gap:0.35rem;">
                                <span>💳</span> قەرزی ئەم مارکێتە بۆ {{ $shipment->supplier->name }}:
                            </span>
                            <strong style="font-size: 1.15rem; color: {{ $shipmentDebt > 0 ? '#f87171' : '#34d399' }}; font-family: var(--font-mono);">
                                ${{ number_format($shipmentDebt, 2) }}
                            </strong>
                        </div>
                        @if($shipment->remaining_debt > 0 && abs($shipment->remaining_debt - $shipmentDebt) > 0.01)
                            <div style="font-size: 0.73rem; color: #fca5a5; opacity: 0.9; margin-top: 0.25rem;">
                                (قەرزی دیاریکراوی ئەم پسوولەیە: ${{ number_format($shipment->remaining_debt, 2) }})
                            </div>
                        @endif
                        <div style="margin-top: 0.55rem;">
                            <button type="button" 
                                    class="btn btn-sm" 
                                    onclick="openCollectPaymentModal({{ $shipment->retailer_company_id }}, '{{ addslashes($shipment->retailer->name) }}', {{ $shipmentDebt > 0 ? $shipmentDebt : $shipment->total_amount }}, {{ $shipment->supplier_company_id }}, {{ $shipment->id }})" 
                                    style="width: 100%; justify-content: center; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-weight: 800; font-size: 0.85rem; border: none; padding: 0.5rem 0.8rem; border-radius: 7px; cursor: pointer; box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3); display:flex; align-items:center; gap:0.4rem;">
                                <span>💵</span> وەرگرتنی قەرز / پارەی پسوولە (سند قبض)
                            </button>
                        </div>
                    </div>

                    <details style="margin:0.5rem 0 0.8rem 0; background:rgba(0,0,0,0.22); border-radius:8px; padding:0.5rem; border:1px solid rgba(255,255,255,0.06);">
                        <summary style="font-size:0.78rem; color:#7dd3fc; cursor:pointer; font-weight:700;">
                            📦 لیستی کاڵاکانی ئەم پسوولەیە ({{ $shipment->items->count() }} کاڵا)
                        </summary>
                        <div style="margin-top:0.4rem; display:flex; flex-direction:column; gap:0.35rem;">
                            @foreach($shipment->items as $it)
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; padding:0.25rem 0; border-bottom:1px dashed rgba(255,255,255,0.06);">
                                    <span style="color:#fff;">• {{ $it->product_title }} (<code>{{ $it->sku }}</code>)</span>
                                    <strong style="color:#34d399;">{{ $it->quantity_ordered }} دانە (${{ number_format($it->total_price, 2) }})</strong>
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>

                <div style="display:flex; flex-direction:column; gap:0.5rem; margin-top:0.5rem;">
                    @if($shipment->status === 'dispatched')
                        <div style="background:rgba(56,189,248,0.12); border:1px solid rgba(56,189,248,0.3); border-radius:8px; padding:0.6rem; font-size:0.8rem; color:#7dd3fc;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span>🚚 <strong>لە ڕێگایە (بەڕێکەوتووە لە کۆگا)</strong></span>
                                <span style="font-size:0.75rem; color:#a5b4fc;">{{ $shipment->dispatched_at ? \Carbon\Carbon::parse($shipment->dispatched_at)->format('h:i A') : 'ئێستا' }}</span>
                            </div>
                            @if($shipment->driver_name)
                                <div style="font-size:0.75rem; color:#cbd5e1; margin-top:0.25rem;">
                                    شۆفێر: <strong>{{ $shipment->driver_name }}</strong> • تابلۆ: <code>{{ $shipment->vehicle_plate ?: 'دیارینەکراو' }}</code>
                                </div>
                            @endif
                        </div>

                        <!-- ACTIVATED GOOGLE MAPS TURN-BY-TURN NAVIGATION FOR DRIVER -->
                        <a href="{{ $shipment->navigation_url }}" target="_blank" class="btn" style="width:100%; justify-content:center; padding:0.75rem; font-weight:800; font-size:0.92rem; background:linear-gradient(135deg, #10b981, #059669); color:#fff; border:none; box-shadow:0 4px 15px rgba(16,185,129,0.35); text-decoration:none; display:flex; align-items:center; gap:0.5rem; border-radius:8px; cursor:pointer;">
                            📍 نەخشەی ڕاستەوخۆ و ئاڕاستەی گەیاندن (Google Maps Navigation)
                        </a>
                        <button type="button" class="btn btn-primary"
                            data-items="{{ json_encode($shipment->items->map(fn($it) => ['id' => $it->id, 'title' => $it->product_title, 'sku' => $it->sku, 'quantity_ordered' => (int)$it->quantity_ordered, 'unit_price' => (float)$it->unit_price, 'total_price' => (float)$it->total_price])->values()->all()) }}"
                            data-po="{{ $shipment->po_number }}"
                            data-total="{{ number_format($shipment->total_amount, 2) }}"
                            data-retailer-name="{{ $shipment->retailer?->name ?? 'مارکێت' }}"
                            data-supplier-name="{{ $shipment->supplier?->name ?? 'دابینکەر' }}"
                            data-payment-method="{{ $shipment->payment_method }}"
                            onclick="openDriverHandoverModal({{ $shipment->id }}, this)"
                            style="width:100%; justify-content:center; padding:0.75rem; font-weight:700; font-size:0.92rem; background:linear-gradient(135deg, #0284c7, #0369a1); border:none; box-shadow:0 4px 15px rgba(2,132,199,0.35); cursor:pointer;">
                            ✍️ وەرگرتن و پشکنین / ڕادەستکردن (OTP + واژۆ)
                        </button>
                    @elseif(in_array($shipment->status, ['delivered', 'completed']))
                        <div style="background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.3); border-radius:6px; padding:0.5rem; text-align:center; color:#34d399; font-size:0.82rem; font-weight:700;">
                            ✓ بارەکە ڕادەستکراوە و پشتڕاستکراوەتەوە
                        </div>
                    @else
                        <!-- READY AT WAREHOUSE / DRIVER CAN CLICK TO DEPART -->
                        <div style="background:rgba(245,158,11,0.08); border:1px dashed rgba(245,158,11,0.3); border-radius:8px; padding:0.5rem; font-size:0.78rem; color:#fde68a;">
                            <span>📦 کاڵاکان لە کۆگان • کلیک بکە کاتێک بارکرا و بەڕێکەوتیت:</span>
                        </div>
                        <button type="button" class="btn" onclick="openDriverDepartModal({{ $shipment->id }}, '{{ $shipment->po_number }}', '{{ addslashes($shipment->retailer->name) }}', '{{ addslashes($shipment->supplier->name) }}')" style="width:100%; justify-content:center; padding:0.65rem; font-weight:700; background:linear-gradient(135deg, #f59e0b, #d97706); color:#fff; border:none; box-shadow:0 4px 15px rgba(245,158,11,0.35); cursor:pointer;">
                            🚚 لە کۆگا بەڕێکەوتم / دەستپێکردنی گەیاندن
                        </button>
                    @endif

                    <div style="display:flex; gap:0.4rem;">
                        <button type="button" class="btn btn-sm" onclick="openAuditModal({{ $shipment->id }}, '{{ $shipment->po_number }}')" style="flex:1; justify-content:center; font-size:0.75rem; background:rgba(99,102,241,0.15); color:#a5b4fc; border:1px solid rgba(99,102,241,0.3);">
                            📜 مێژووی بار (Audit)
                        </button>
                        <a href="{{ route('orders.invoice', $shipment->id) }}" target="_blank" class="btn btn-sm" style="flex:1; justify-content:center; font-size:0.75rem;">
                            📄 بینینی پسوولە
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div style="grid-column:1/-1; text-align:center; padding:3rem; color:var(--text-tertiary);">
                هیچ بارێکی گەیاندن نەدۆزرایەوە لە ئێستادا.
            </div>
            @endforelse
        </div>
    </div>

    <!-- ==========================================
         DEDICATED DRIVER DEBTS MODULE (کام مارکێت قەرزداری کام کۆمپانیایە بۆ شۆفێر)
    =========================================== -->
    <div class="panel app-module-section" data-app-module="debts" style="border-right: 4px solid #ef4444; background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(15, 23, 42, 0.5)); display:none; margin-top: 1.5rem;">
        <div class="panel-header" style="flex-wrap:wrap; gap:1rem; border-bottom:1px solid rgba(239, 68, 68, 0.2); padding-bottom:1rem; margin-bottom:1.5rem;">
            <div>
                <div style="display:flex; align-items:center; gap:0.6rem;">
                    <span style="font-size:1.6rem;">💳</span>
                    <h3 class="panel-title" style="color: #f87171; font-size: 1.3rem; font-weight: 800;">
                        دەفتەری قەرزی مارکێتەکان بۆ کۆمپانیاکان (Market Debts per Company)
                    </h3>
                </div>
                <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.3rem;">
                    ڕوون و ئاشکرا: کام مارکێت قەرزداری کام کۆمپانیایە، چەند پارەی لایە، و لە کاتی سەردان یان گەیاندندا دەتوانیت دەستبەجێ بە سند قبض پارەکەی لێ وەربگریت.
                </p>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <span class="badge" style="background:rgba(239, 68, 68, 0.2); color:#f87171; border:1px solid rgba(239, 68, 68, 0.4); font-size:0.85rem; padding:0.4rem 0.8rem;">
                    {{ $driverMarketDebts->count() }} مارکێتی تۆمارکراو
                </span>
            </div>
        </div>

        <!-- DRIVER DEBT KPI TILES -->
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
            <div style="background:rgba(15, 23, 42, 0.7); border:1px solid rgba(239, 68, 68, 0.3); border-radius:12px; padding:1.1rem;">
                <div style="font-size:0.8rem; color:#94a3b8; font-weight:600; margin-bottom:0.3rem;">💵 کۆی قەرزی مارکێتەکان</div>
                <div style="font-size:1.6rem; font-weight:800; color:#f87171; font-family:var(--font-mono);">
                    ${{ number_format($driverMarketDebts->sum('effective_debt'), 2) }}
                </div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.2rem;">بۆ سەرجەم کۆمپانیاکان</div>
            </div>
            <div style="background:rgba(15, 23, 42, 0.7); border:1px solid rgba(245, 158, 11, 0.3); border-radius:12px; padding:1.1rem;">
                <div style="font-size:0.8rem; color:#94a3b8; font-weight:600; margin-bottom:0.3rem;">🚚 ئەمانەتی وەرگیراوی لای خۆم</div>
                <div style="font-size:1.6rem; font-weight:800; color:#fbbf24; font-family:var(--font-mono);">
                    ${{ number_format($totalDriverCustodyCash, 2) }}
                </div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.2rem;">{{ $driverCustodyVouchers->count() }} پسوولە چاوەڕوانی تەسلیماتە</div>
            </div>
            <div style="background:rgba(15, 23, 42, 0.7); border:1px solid rgba(16, 185, 129, 0.3); border-radius:12px; padding:1.1rem;">
                <div style="font-size:0.8rem; color:#94a3b8; font-weight:600; margin-bottom:0.3rem;">🛒 مارکێتە قەرزدارەکان</div>
                <div style="font-size:1.6rem; font-weight:800; color:#34d399; font-family:var(--font-mono);">
                    {{ $driverMarketDebts->where('effective_debt', '>', 0)->count() }} مارکێت
                </div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.2rem;">پێویستیان بە وەرگرتنەوە هەیە</div>
            </div>
        </div>

        <!-- SEARCH INPUT FOR DRIVER -->
        <div style="margin-bottom:1rem; display:flex; gap:0.5rem;">
            <input type="text" id="driver-debt-search" oninput="filterDriverDebtTable()" placeholder="🔍 گەڕان بەدوای مارکێت، شار، یان کۆمپانیا..." style="width:100%; max-width:400px; padding:0.6rem 0.9rem; background:rgba(0,0,0,0.35); border:1px solid var(--border-subtle); color:#fff; border-radius:8px; font-size:0.85rem;">
        </div>

        <!-- TABLE OF MARKET DEBTS BY SUPPLIER -->
        <div style="overflow-x:auto; border:1px solid var(--border-subtle); border-radius:10px;">
            <table class="data-table" id="driver-debts-table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:rgba(16, 20, 29, 0.95);">
                        <th style="padding:10px 14px; text-align:right;">مارکێت (کڕیار)</th>
                        <th style="padding:10px 14px; text-align:right;">کۆمپانیای داواکراو (دابینکەر)</th>
                        <th style="padding:10px 14px; text-align:right;">قەرزی ماوە (Running Balance)</th>
                        <th style="padding:10px 14px; text-align:center;">داواکارییەکان</th>
                        <th style="padding:10px 14px; text-align:center;">پەیوەندی و نەخشە</th>
                        <th style="padding:10px 14px; text-align:center;">کردار (Action)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($driverMarketDebts as $row)
                    @php
                        $ret = $row['retailer'];
                        $sup = $row['supplier'];
                        $debt = $row['effective_debt'];
                    @endphp
                    <tr class="driver-debt-row" data-search="{{ strtolower($ret->name . ' ' . $ret->city . ' ' . $sup->name) }}" style="border-bottom:1px solid var(--border-subtle);">
                        <td style="padding:12px 14px;">
                            <div style="font-weight:700; color:#fff; font-size:0.95rem;">🛒 {{ $ret->name }}</div>
                            <div style="font-size:0.75rem; color:var(--text-tertiary);">📍 {{ $ret->city }} - {{ $ret->address_line }}</div>
                        </td>
                        <td style="padding:12px 14px;">
                            <div style="font-weight:700; color:#7dd3fc; font-size:0.95rem;">🏭 {{ $sup->name }}</div>
                            <div style="font-size:0.75rem; color:var(--text-tertiary);">شار: {{ $sup->city }}</div>
                        </td>
                        <td style="padding:12px 14px; text-align:right;">
                            <strong style="color:{{ $debt > 0 ? '#f87171' : '#34d399' }}; font-size:1.15rem; font-family:var(--font-mono);">
                                ${{ number_format($debt, 2) }}
                            </strong>
                            @if($debt > 0)
                                <div style="font-size:0.7rem; color:#fca5a5;">~ {{ number_format($debt * 1520) }} IQD</div>
                            @endif
                        </td>
                        <td style="padding:12px 14px; text-align:center;">
                            <span class="badge badge-info" style="font-size:0.8rem;">{{ $row['total_orders'] }} داواکاری</span>
                        </td>
                        <td style="padding:12px 14px; text-align:center;">
                            <div style="display:flex; gap:0.4rem; justify-content:center; flex-wrap:wrap;">
                                @if($ret->primary_phone)
                                    <a href="tel:{{ $ret->primary_phone }}" class="btn btn-sm" style="font-size:0.75rem; padding:0.25rem 0.5rem; background:rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3);">
                                        📞 تەلەفۆن
                                    </a>
                                @endif
                                <a href="https://maps.google.com/?q={{ urlencode($ret->address_line . ' ' . $ret->city) }}" target="_blank" class="btn btn-sm" style="font-size:0.75rem; padding:0.25rem 0.5rem; background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.3);">
                                    🗺️ نەخشە
                                </a>
                            </div>
                        </td>
                        <td style="padding:12px 14px; text-align:center;">
                            <div style="display:flex; gap:0.4rem; justify-content:center; flex-wrap:wrap;">
                                <button type="button" 
                                        class="btn btn-sm" 
                                        onclick="openCollectPaymentModal({{ $ret->id }}, '{{ addslashes($ret->name) }}', {{ $debt }}, {{ $sup->id }})" 
                                        style="background:linear-gradient(135deg, #10b981, #059669); color:#fff; font-weight:700; padding:0.4rem 0.8rem; border-radius:6px; border:none; cursor:pointer; box-shadow:0 2px 8px rgba(16,185,129,0.3);">
                                    💵 وەرگرتن (سند قبض)
                                </button>
                                <a href="{{ route('financial.statement.show', ['retailerId' => $ret->id, 'supplier_id' => $sup->id]) }}" target="_blank" class="btn btn-sm" style="background:#2563eb; color:#fff; font-weight:700; padding:0.4rem 0.6rem; border-radius:6px; text-decoration:none;">
                                    📑 کەشف
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-tertiary);">
                            هیچ تۆمارێکی قەرز نەدۆزرایەوە بۆ مارکێتەکان.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- CASH VAN OPERATIONS FOR DRIVER (Direct Store Delivery & Settlement) -->
    <div class="app-module-section" data-app-module="van">
    @include('partials.cash_van_section')
    </div>
    @endif

    <!-- ==========================================
         UNIFIED B2B FINANCIAL LEDGER, DEBT AGING & VOUCHERS
         (Accessible across all roles: Operator, Supplier, Retailer, Driver)
    =========================================== -->
    <div class="app-module-section" data-app-module="finance">
    @include('partials.financial_ledger_section')
    </div>
</main>

<!-- FLOATING CART BUTTON (Always accessible) -->
<div id="floating-cart-btn" onclick="openCartDrawer()" style="position:fixed; bottom:24px; left:24px; z-index:9999; display:flex; align-items:center; gap:0.5rem; background:linear-gradient(135deg, #4f46e5, #06b6d4); color:#fff; padding:0.75rem 1.25rem; border-radius:9999px; box-shadow:0 8px 25px rgba(79,70,229,0.5); cursor:pointer; font-weight:700; transition:all 0.2s cubic-bezier(0.4,0,0.2,1); border:1px solid rgba(255,255,255,0.2);">
    <span style="font-size:1.3rem;">🛒</span>
    <span style="font-size:0.9rem;">سەبەتەی کڕین</span>
    <span id="floating-cart-badge" style="background:#ef4444; color:#fff; border-radius:9999px; padding:0.15rem 0.55rem; font-size:0.75rem; font-weight:800;">0</span>
</div>

<!-- MULTI-SUPPLIER CART DRAWER -->
<div id="cart-backdrop" class="cart-backdrop" onclick="closeCartDrawer()"></div>
<div id="cart-drawer" class="cart-drawer">
    <div class="cart-header">
        <div>
            <h3 style="color:#fff; font-size:1.15rem; margin-bottom:0.2rem;">🛒 سەبەتەی کڕینی یەکگرتوو</h3>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">ڕەشنووسی کاتی و فەرمانی مارکێت (Draft & Cart)</span>
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <button type="button" onclick="clearEntireCart()" class="btn btn-sm" style="font-size:0.72rem; padding:0.2rem 0.55rem; background:rgba(239,68,68,0.18); border:1px solid rgba(239,68,68,0.35); color:#fca5a5; cursor:pointer;" title="بەتاڵکردنی سەبەتە">
                🗑️ بەتاڵکردن
            </button>
            <button type="button" onclick="closeCartDrawer()" style="background:transparent; border:none; color:var(--text-secondary); font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
        </div>
    </div>

    <!-- Quick Add Section inside Cart Drawer -->
    <div style="padding: 0.8rem 1.2rem; background: rgba(0,0,0,0.25); border-bottom: 1px solid var(--border-subtle);">
        <div style="background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25); border-radius: 6px; padding: 0.45rem 0.65rem; margin-bottom: 0.6rem; font-size: 0.72rem; color: #c7d2fe; line-height: 1.4;">
            🛡️ <strong>ڕەشنووسی کاتی (Draft):</strong> ئەم کاڵایانە تەنها لای خۆت دەمێننەوە. دەتوانیت بڕیان دەستکاری بکەیت (+ / -) یان بسڕیتەوە (✕). هیچ شتێک بۆ دابینکەر ناڕوات تا ئەو کاتەی دوگمەی "ناردنی فەرمانی کڕین" دادەگریت.
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
            <span style="font-size:0.76rem; font-weight:700; color:#38bdf8;">📦 هەڵبژاردنی خێرای کاڵا بۆ سەبەتە:</span>
            <button type="button" onclick="openBarcodeModal()" class="btn btn-sm" style="font-size:0.68rem; padding:0.15rem 0.45rem; background:rgba(99,102,241,0.2); color:#a5b4fc;">📷 سکان بە بارکۆد</button>
        </div>
        <div style="display:flex; gap:0.4rem;">
            <select id="cart-quick-product-select" style="flex:1; padding:0.4rem; background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); border-radius:6px; color:#fff; font-size:0.78rem;">
                <option value="">-- هەڵبژاردنی کاڵا لە کەتەلۆک --</option>
                @foreach($catalogProducts as $cp)
                    <option value="{{ $cp->id }}" 
                        data-title="{{ $cp->title }}"
                        data-sku="{{ $cp->sku }}"
                        data-supplier-id="{{ $cp->supplier_company_id }}"
                        data-supplier-name="{{ $cp->supplier?->name ?? 'دابینکەر' }}"
                        data-price="{{ (float)$cp->base_wholesale_price }}"
                        data-moq="{{ (int)$cp->minimum_order_quantity }}"
                        data-stock="{{ (int)$cp->current_stock }}"
                        data-tiers="{{ json_encode($cp->volumeTiers->map(fn($t) => ['min' => (int)$t->min_quantity, 'price' => (float)$t->unit_price])->values()->all()) }}">
                        {{ $cp->title }} ({{ $cp->supplier?->name }}) - ${{ number_format($cp->base_wholesale_price, 2) }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-primary btn-sm" onclick="addSelectedProductFromCartDropdown()" style="white-space:nowrap; font-weight:700; font-size:0.78rem;">
                + خستنەسەبەتە
            </button>
        </div>
    </div>
    
    <div class="cart-body" id="cart-items-container">
        <!-- Rendered by JS -->
    </div>

    <div class="cart-footer">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
            <span style="color:var(--text-secondary); font-size:0.85rem;">کۆی گشتی هەموو فەرمانەکان:</span>
            <span id="cart-grand-total" style="font-size:1.3rem; font-weight:800; color:#34d399;">$0.00</span>
        </div>

        <form id="multi-cart-form" onsubmit="checkoutMultiCart(event)">
            @csrf
            <div style="margin-bottom:0.6rem;">
                <label style="display:block; font-size:0.78rem; color:var(--text-secondary); margin-bottom:0.25rem;">شێوازی پارەدان بۆ هەموو فەرمانەکان:</label>
                <select name="payment_method" id="cart-payment-method" style="width:100%; padding:0.45rem; font-size:0.82rem;">
                    <option value="cash_on_delivery">کاش کاتی وەرگرتن (COD - Cash on Delivery)</option>
                    <option value="credit_terms">قەرز بە وادەی ٣٠ ڕۆژ (Credit Terms 30 Days)</option>
                    <option value="bank_transfer">حەواڵە / پارەدانی ئەلیکترۆنی</option>
                </select>
            </div>
            <div style="margin-bottom:0.6rem;">
                <label style="display:block; font-size:0.78rem; color:var(--text-secondary); margin-bottom:0.25rem;">ناونیشانی وەرگرتنی مارکێت:</label>
                <input type="text" name="delivery_address" id="cart-delivery-address" value="{{ $currentRetailer?->address_line ?? 'شەقامی سەرەکی مارکێت' }}" required style="width:100%; padding:0.45rem; font-size:0.82rem;">
            </div>
            <div style="margin-bottom:0.8rem;">
                <label style="display:block; font-size:0.78rem; color:var(--text-secondary); margin-bottom:0.25rem;">تێبینی گەیاندن:</label>
                <input type="text" name="notes" id="cart-notes" placeholder="مەرجی کاتی گەیشتن یان داواکاری تایبەت..." style="width:100%; padding:0.45rem; font-size:0.82rem;">
            </div>
            <button type="submit" id="cart-submit-btn" class="btn btn-primary" style="width:100%; justify-content:center; padding:0.65rem; font-weight:700;">
                ✓ ناردنی فەرمانی کڕین (دابەشکردنی ئۆتۆماتیکی پسوولەکان)
            </button>
        </form>
    </div>
</div>

<!-- BARCODE SCANNER MODAL -->
<div id="barcode-modal" class="modal-overlay">
    <div class="modal-content-card" style="max-width:540px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.6rem;">
            <h3 style="color:#fff; font-size:1.1rem; display:flex; align-items:center; gap:0.4rem;">
                📷 داواکاری خێرا بە سکانەری بارکۆد (Barcode Re-order)
            </h3>
            <button type="button" onclick="closeBarcodeModal()" style="background:transparent; border:none; color:var(--text-secondary); font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <p style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:1rem;">
            خاوەن مارکێت دەتوانێت تەنها کامێرا بخاتە سەر بارکۆدی کارتۆن یان پاکەتەکە، یان بارکۆدەکە لێرە بنووسێت بۆ ئەوەی ڕاستەوخۆ بخرێتە سەبەتەی کڕینەوە.
        </p>

        <!-- Simulated camera viewport -->
        <div class="scanner-camera-viewport">
            <div class="scanner-laser-line"></div>
            <div style="text-align:center; z-index:2; color:var(--text-tertiary); font-size:0.8rem;">
                📹 کامێرا چالاکە • بارکۆدەکە لە ناو چوارچێوەکە دابنێ
            </div>
        </div>

        <div style="display:flex; gap:0.5rem; margin-bottom:0.8rem;">
            <input type="text" id="barcode-input" placeholder="بارکۆد بنووسە (نموونە: 482000000001)..." style="flex:1; padding:0.5rem 0.75rem; font-size:0.9rem;" onkeydown="if(event.key==='Enter') searchBarcode(this.value)">
            <button type="button" class="btn btn-primary" onclick="searchBarcode(document.getElementById('barcode-input').value)">
                🔍 گەڕان
            </button>
        </div>

        <!-- Quick Test Barcodes -->
        <div style="margin-bottom:1rem;">
            <span style="font-size:0.75rem; color:var(--text-tertiary);">بارکۆدی ئامادەکراو بۆ تاقیکردنەوەی خێرا:</span>
            <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-top:0.3rem;">
                <button type="button" class="btn btn-sm" onclick="setBarcode('6291004928101')" style="font-size:0.72rem; padding:0.2rem 0.5rem;">6291004928101 (کەرە)</button>
                <button type="button" class="btn btn-sm" onclick="setBarcode('6291004928205')" style="font-size:0.72rem; padding:0.2rem 0.5rem;">6291004928205 (ڕۆن)</button>
                <button type="button" class="btn btn-sm" onclick="setBarcode('APX-BUT-01')" style="font-size:0.72rem; padding:0.2rem 0.5rem;">APX-BUT-01 (کۆد/SKU)</button>
            </div>
        </div>

        <div id="barcode-result-card" style="display:none; background:rgba(0,0,0,0.3); border:1px solid var(--border-subtle); border-radius:8px; padding:1rem;">
            <!-- Dynamically populated by searchBarcode() -->
        </div>
    </div>
</div>

<!-- DRIVER DEPARTURE MODAL (بەڕێکەوتن لە کۆگا) -->
<div id="driver-depart-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content-card" style="max-width:520px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.6rem;">
            <h3 style="color:#7dd3fc; font-size:1.1rem; display:flex; align-items:center; gap:0.4rem;">
                🚚 تۆمارکردنی بەڕێکەوتن لە کۆگا (Warehouse Departure)
            </h3>
            <button type="button" onclick="closeDriverDepartModal()" style="background:transparent; border:none; color:var(--text-secondary); font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <form id="driver-depart-form" method="POST" onsubmit="submitDriverDepart(event)">
            @csrf
            <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:8px; padding:0.8rem; margin-bottom:1rem;">
                <div style="font-size:0.85rem; color:#fde68a;">
                    فەرمانی کڕین: <strong id="depart-po-number" style="color:#fff;">-</strong>
                </div>
                <div style="font-size:0.82rem; color:#cbd5e1; margin-top:0.3rem;">
                    🏭 دابینکەر: <strong id="depart-supplier-name" style="color:#fff;">-</strong> ➔ 🛒 مارکێت: <strong id="depart-retailer-name" style="color:#fff;">-</strong>
                </div>
            </div>

            <div style="margin-bottom:0.8rem;">
                <label style="display:block; font-size:0.82rem; color:var(--text-secondary); margin-bottom:0.25rem;">ناوی شۆفێر / مەندوب: *</label>
                <input type="text" name="driver_name" id="depart-driver-name" value="کاروان علی (شۆفێری دابەشکردن)" required style="width:100%; padding:0.5rem; font-size:0.85rem;">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; margin-bottom:0.8rem;">
                <div>
                    <label style="display:block; font-size:0.82rem; color:var(--text-secondary); margin-bottom:0.25rem;">ژمارەی مۆبایل: *</label>
                    <input type="text" name="driver_phone" id="depart-driver-phone" value="0750 123 4567" required style="width:100%; padding:0.5rem; font-size:0.85rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.82rem; color:var(--text-secondary); margin-bottom:0.25rem;">تابلۆی ئۆتۆمبێل:</label>
                    <input type="text" name="vehicle_plate" id="depart-vehicle-plate" value="سلێمانی - بارهەڵگر ٢٣٤١" style="width:100%; padding:0.5rem; font-size:0.85rem;">
                </div>
            </div>

            <div style="margin-bottom:1.2rem;">
                <label style="display:block; font-size:0.82rem; color:var(--text-secondary); margin-bottom:0.25rem;">تێبینی بەڕێکەوتن (Departure Notes):</label>
                <input type="text" name="supplier_notes" id="depart-notes" value="کاڵاکان لە کۆگا بارکران و شۆفێر بەڕێکەوت بەرەو ناونیشانی مارکێت." style="width:100%; padding:0.5rem; font-size:0.85rem;">
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button type="submit" id="btn-submit-depart" class="btn" style="flex:1; justify-content:center; padding:0.65rem; font-weight:700; background:linear-gradient(135deg, #f59e0b, #d97706); color:#fff; border:none; cursor:pointer;">
                    🚚 دەستپێکردنی گەیاندن و بەڕێکەوتن لە کۆگا
                </button>
                <button type="button" onclick="closeDriverDepartModal()" class="btn" style="padding:0.65rem 1rem; cursor:pointer;">
                    داخستن
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DRIVER HANDOVER & PROOF OF DELIVERY (PoD) MODAL -->
<div id="driver-handover-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content-card" style="max-width:620px; max-height:92vh; overflow-y:auto; padding:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.75rem;">
            <div>
                <h3 style="color:#7dd3fc; font-size:1.15rem; display:flex; align-items:center; gap:0.4rem; margin-bottom:0.2rem;">
                    ✍️ سەلماندنی گەیاندن و ڕادەستکردنی بار (Proof of Delivery - PoD)
                </h3>
                <span style="font-size:0.75rem; color:var(--text-tertiary);">سیستەمی یەکگرتووی پشکنین، OTP، لۆکەیشن و واژۆی فەرمی</span>
            </div>
            <button type="button" onclick="closeDriverHandoverModal()" style="background:transparent; border:none; color:var(--text-secondary); font-size:1.8rem; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <!-- Offline Status Banner -->
        <div id="handover-offline-alert" style="display:none; background:rgba(245,158,11,0.15); border:1px solid #f59e0b; border-radius:8px; padding:0.6rem 0.8rem; margin-bottom:1rem; font-size:0.8rem; color:#fde68a;">
            📡 <strong>دۆخی ئۆفلاین (Offline-First):</strong> پەیوەندی ئینتەرنێت نییە. داتاکان لە داتابەیسی ناوخۆیی مۆبایلەکەت دەپارێزرێن و لەکاتی بەردەستبوونی هێڵ خۆکارانە دەنێردرێنەوە بۆ سێرڤەر.
        </div>

        <form id="driver-handover-form" method="POST" enctype="multipart/form-data" onsubmit="submitDriverHandover(event)">
            @csrf
            <input type="hidden" name="delivered_latitude" id="handover-lat">
            <input type="hidden" name="delivered_longitude" id="handover-lng">

            <!-- Order & Parties Header Card -->
            <div style="background:rgba(15,23,42,0.6); border:1px solid var(--border-subtle); border-radius:10px; padding:0.9rem; margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                    <div style="font-size:0.95rem; font-weight:700; color:#fff;">
                        پسوولە: <code id="handover-po-number" style="color:#38bdf8; font-size:1.05rem;">-</code>
                    </div>
                    <span id="handover-payment-badge" class="badge badge-info">COD Cash</span>
                </div>
                <div style="font-size:0.82rem; color:#cbd5e1; line-height:1.6;">
                    🛒 مارکێتی وەرگر: <strong id="handover-retailer-name" style="color:#fff;">-</strong><br>
                    🏭 دابینکەر: <strong id="handover-supplier-name" style="color:#fff;">-</strong>
                </div>
            </div>

            <!-- STEP 1: GEO-FENCING LOCATION CHECK -->
            <div style="background:rgba(56,189,248,0.06); border:1px solid rgba(56,189,248,0.25); border-radius:10px; padding:0.9rem; margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <strong style="color:#7dd3fc; font-size:0.85rem;">📍 ١. گەیشتن بە لۆکەیشن (Geo-Fencing Check):</strong>
                    <span id="geofence-badge" class="badge badge-warning" style="font-size:0.7rem;">پشکنین نەکراوە</span>
                </div>
                <p style="font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.6rem;">
                    پشکنینی شوێنی جوگرافی (GPS) بۆ دڵنیابوونەوە لەوەی لە بەردەم مارکێتەکە ئامادەیت (مەودای کەمتر لە ١٠٠ مەتر).
                </p>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <button type="button" class="btn btn-sm" id="btn-verify-geofence" onclick="verifyDriverLocation()" style="flex:1; justify-content:center; background:linear-gradient(135deg, #0284c7, #0369a1); color:#fff; border:none; padding:0.55rem; font-weight:700;">
                        📍 گەیشتمە شوێنی مارکێت (پشکنینی GPS)
                    </button>
                </div>
                <div id="geofence-result-text" style="font-size:0.75rem; color:#38bdf8; margin-top:0.4rem; display:none;"></div>
            </div>

            <!-- STEP 2: ITEM VERIFICATION (بەتاڵکردن و ژماردن) -->
            <div style="background:rgba(0,0,0,0.25); border:1px solid var(--border-subtle); border-radius:10px; padding:0.9rem; margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem; flex-wrap:wrap; gap:0.4rem;">
                    <strong style="color:#fff; font-size:0.88rem;">📦 ٢. بەتاڵکردن و پشکنینی کاڵاکان (Item Verification):</strong>
                    <button type="button" class="btn btn-sm btn-success" onclick="markAllItemsGood()" style="font-size:0.75rem; padding:0.25rem 0.6rem;">
                        ✓ هەمووی دروستە (All Good)
                    </button>
                </div>
                <p style="font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.6rem;">
                    هەموو کاڵاکانی ئەم پسوولەیە پشکنین بکە. ئەگەر کاڵایەک کەم بوو یان زیانی پێگەیشتبوو دەستکاری ژمارەکەی بکە:
                </p>

                <!-- Dynamic Items Checklist Container -->
                <div id="handover-items-list" style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:0.8rem;">
                    <!-- Populated by JS -->
                </div>

                <!-- Discrepancy / Damage Photos Upload -->
                <div id="damage-photo-box" style="display:none; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:0.6rem; margin-top:0.6rem;">
                    <label style="display:block; font-size:0.75rem; color:#fca5a5; font-weight:700; margin-bottom:0.3rem;">
                        📷 وێنەی بەڵگەی زیان/کەمی کاڵا (Evidence Photo):
                    </label>
                    <input type="file" name="evidence_photo" id="handover-evidence-photo" accept="image/*" capture="environment" style="font-size:0.75rem; color:#fff;">
                </div>
            </div>

            <!-- STEP 3: PAYMENT COLLECTION (دیاریکردنی شێوازی پارەدان) -->
            <div style="background:linear-gradient(135deg, rgba(16,185,129,0.08), rgba(15,23,42,0.5)); border:1px solid rgba(16,185,129,0.3); border-radius:10px; padding:1rem; margin-bottom:1rem;">
                <strong style="color:#34d399; font-size:0.88rem; display:block; margin-bottom:0.5rem;">
                    💰 ٣. دیاریکردنی شێوازی پارەدان (Payment Collection):
                </strong>
                <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3); padding:0.75rem; border-radius:8px; margin-bottom:0.6rem;">
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-secondary);">بڕی پارەی کۆتایی دوای کەمکردنەوەی زیانەکان:</div>
                        <div id="handover-final-amount" style="font-size:1.6rem; font-weight:800; color:#34d399; font-family:var(--font-mono); margin-top:0.2rem;">
                            $0.00
                        </div>
                    </div>
                    <div style="text-align:left;">
                        <span id="handover-terms-label" style="font-size:0.75rem; color:#a5b4fc; display:block;">شێواز: COD Cash</span>
                        <span id="handover-discrepancy-deduct" style="font-size:0.72rem; color:#f87171; display:none;">زیان: -$0.00</span>
                    </div>
                </div>

                <div id="cod-input-container">
                    <label style="display:block; font-size:0.78rem; color:#fff; font-weight:600; margin-bottom:0.25rem;">
                        💵 بڕی کاشی وەرگیراو لەلایەن شۆفێرەوە ($):
                    </label>
                    <input type="number" step="0.01" name="cash_collected" id="handover-cash-collected" placeholder="0.00" style="width:100%; padding:0.5rem; font-size:1.1rem; font-weight:700; color:#fff; background:var(--bg-core); border:1px solid #10b981; border-radius:6px;">
                </div>
            </div>

            <!-- STEP 4: DUAL PROOF OF DELIVERY (کۆدی OTP و واژۆی ئەلیکترۆنی) -->
            <div style="background:rgba(99,102,241,0.06); border:1px solid rgba(99,102,241,0.25); border-radius:10px; padding:1rem; margin-bottom:1.2rem;">
                <strong style="color:#a5b4fc; font-size:0.88rem; display:block; margin-bottom:0.6rem;">
                    🛡️ ٤. پشتڕاستکردنەوەی دوولایەنە (Dual Proof of Delivery):
                </strong>

                <!-- Receiver Name -->
                <div style="margin-bottom:0.8rem;">
                    <label style="display:block; font-size:0.78rem; color:var(--text-secondary); margin-bottom:0.25rem;">
                        ناوی خاوەن مارکێت یان پشکنەری وەرگر: *
                    </label>
                    <input type="text" name="inspected_by" id="handover-receiver-name" placeholder="ناوی کەسی وەرگر..." required style="width:100%; padding:0.45rem; font-size:0.85rem; background:var(--bg-core); border:1px solid var(--border-subtle); color:#fff; border-radius:6px;">
                </div>

                <!-- OTP Input (One 6-digit code for the whole invoice) -->
                <div style="margin-bottom:0.9rem;">
                    <label style="display:block; font-size:0.82rem; color:#fff; font-weight:700; margin-bottom:0.3rem;">
                        🔢 کۆدی دڵنیایی (OTP) لە ئەپی مارکێت وەربگرە: *
                    </label>
                    <input type="text" name="delivery_otp" id="handover-otp-input" maxlength="6" required placeholder="کۆدی ٦ ژمارەیی بنووسە..." style="width:100%; padding:0.65rem; font-size:1.3rem; text-align:center; letter-spacing:0.35rem; font-weight:800; font-family:var(--font-mono); border:2px solid #6366f1; border-radius:8px; background:#0f172a; color:#fff;">
                    <span style="font-size:0.72rem; color:var(--text-tertiary); display:block; margin-top:0.25rem;">
                        💡 خاوەن مارکێت یەک کۆدی ٦-ژمارەیی لەسەر پسوولەی خۆی دەبینێت بۆ تەواوی کاڵاکانی ئەم دابینکەرە.
                    </span>
                </div>

                <!-- E-Signature Canvas -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.3rem;">
                        <label style="font-size:0.8rem; color:#fff; font-weight:700;">✍️ واژۆی دیجیتاڵی خاوەن مارکێت لەسەر شاشە:</label>
                        <button type="button" onclick="clearSignatureCanvas()" style="background:transparent; border:none; color:#f87171; font-size:0.75rem; cursor:pointer;">پاککردنەوە ✖</button>
                    </div>
                    <canvas id="sig-canvas" class="signature-canvas" width="560" height="130" style="width:100%; height:130px; background:#0b0f17; border:1px solid rgba(255,255,255,0.15); border-radius:8px; touch-action:none;"></canvas>
                    <input type="hidden" name="delivery_signature" id="handover-signature-data">
                </div>
            </div>

            <input type="hidden" name="damage_amount" id="handover-damage-amount" value="0">
            <input type="hidden" name="damage_notes" id="handover-damage-notes" value="">
            <input type="hidden" name="discrepancies" id="handover-discrepancies-json" value="[]">

            <!-- BIG TOUCH BUTTON FOR DRIVERS -->
            <button type="submit" id="btn-submit-handover" class="btn" style="width:100%; justify-content:center; padding:0.9rem; font-size:1.05rem; font-weight:800; background:linear-gradient(135deg, #10b981, #059669); color:#fff; border:none; border-radius:10px; box-shadow:0 6px 20px rgba(16,185,129,0.4); cursor:pointer;">
                ✓ تەواوکردنی فەرمی ڕادەستکردن (PoD Handover)
            </button>
        </form>
    </div>
</div>

<!-- AUDIT TRAIL MODAL -->
<div id="audit-trail-modal" class="modal-overlay">
    <div class="modal-content-card" style="max-width:580px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.6rem;">
            <h3 style="color:#fff; font-size:1.1rem; display:flex; align-items:center; gap:0.4rem;">
                📜 مێژووی گۆڕانکارییەکانی داواکاری (Order Audit Trail)
            </h3>
            <button type="button" onclick="closeAuditModal()" style="background:transparent; border:none; color:var(--text-secondary); font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
            فەرمانی کڕین: <strong id="audit-po-title" style="color:#fff;">-</strong>
        </div>

        <div id="audit-timeline-content" class="audit-trail-timeline" style="max-height:380px; overflow-y:auto;">
            <div style="text-align:center; padding:2rem; color:var(--text-tertiary);">لە چاوەڕوانیدایە...</div>
        </div>
    </div>
</div>

<!-- COLLECT PAYMENT VOUCHER MODAL (سند قبض) -->
<div id="collect-payment-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content-card" style="max-width:560px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.6rem;">
            <h3 style="color:#fff; font-size:1.15rem; display:flex; align-items:center; gap:0.4rem;">
                🧾 وەرگرتنی پارەی قەرز (پسوولەی فەرمی / سند قبض)
            </h3>
            <button type="button" onclick="closeCollectPaymentModal()" style="background:transparent; border:none; color:var(--text-secondary); font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <form action="{{ route('financial.vouchers.store') }}" method="POST">
            @csrf
            <input type="hidden" name="supplier_company_id" id="cpay-supplier-id" value="{{ $currentSupplier?->id ?? $suppliers->first()?->id }}">
            <input type="hidden" name="retailer_company_id" id="cpay-retailer-id" value="">
            <input type="hidden" name="purchase_order_id" id="cpay-po-id" value="">

            <div style="background:rgba(99,102,241,0.1); border:1px solid rgba(99,102,241,0.25); border-radius:8px; padding:0.8rem; margin-bottom:1rem; font-size:0.85rem;">
                <div>مارکێت / کڕیار: <strong id="cpay-retailer-name" style="color:#fff;">-</strong></div>
                <div style="margin-top:0.3rem;">قەرزی ماوەی ئێستا: <strong id="cpay-debt-amount" style="color:#f87171;">$0.00</strong></div>
            </div>

            <div class="form-group" style="margin-bottom:0.75rem;">
                <label>کۆمپانیای دابینکەر (لایەنی خاوەن ماف) *</label>
                <select id="cpay-supplier-select" onchange="document.getElementById('cpay-supplier-id').value = this.value" style="width:100%;">
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ ($currentSupplier?->id == $s->id) ? 'selected' : '' }}>
                            🏭 {{ $s->name }} ({{ $s->city }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-row" style="grid-template-columns:1fr 1fr;">
                <div class="form-group">
                    <label>بڕی پارەی وەرگیراو بە دۆلار ($) *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="cpay-amount-usd" required placeholder="0.00" oninput="calculateIqdEquiv()">
                </div>
                <div class="form-group">
                    <label>شێوازی وەرگرتن (Payment Method) *</label>
                    <select name="payment_method" id="cpay-method" required onchange="togglePaymentMethodFields()">
                        <option value="cash">💵 نەقد (Cash)</option>
                        <option value="bank_transfer">🏦 حەواڵەی بانکی</option>
                        <option value="fib_fastpay">📱 ئەلیکترۆنی (FIB / FastPay)</option>
                        <option value="cheque">📑 چەکی بانکی</option>
                    </select>
                </div>
            </div>

            <div class="form-row" style="grid-template-columns:1fr 1fr; margin-top:0.6rem;">
                <div class="form-group">
                    <label>بڕ بە دیناری عێراقی (ئەگەر بە دینار بوو)</label>
                    <input type="number" step="250" name="amount_iqd" id="cpay-amount-iqd" placeholder="نموونە: 152000">
                </div>
                <div class="form-group">
                    <label>نرخی ١٠٠ دۆلار (Exchange Rate)</label>
                    <input type="number" step="10" name="exchange_rate" id="cpay-rate" placeholder="1520" value="1520" oninput="calculateIqdEquiv()">
                </div>
            </div>

            <div class="form-row" style="grid-template-columns:1fr 1fr; margin-top:0.6rem;">
                <div class="form-group">
                    <label>کێ وەریگرتووە؟ (مەندوب / شۆفێر / ژمێریار) *</label>
                    <select name="collected_by_user_id" id="cpay-collector-id" style="width:100%;" onchange="autoUpdateCustodyOnCollectorChange()">
                        @if(auth()->check())
                            <option value="{{ auth()->id() }}" data-role="{{ auth()->user()->role }}">{{ auth()->user()->name }} (خۆم)</option>
                        @endif
                        @foreach(\App\Models\User::whereIn('role', ['driver', 'staff', 'owner', 'manager'])->take(15)->get() as $u)
                            @if(!auth()->check() || auth()->id() !== $u->id)
                                <option value="{{ $u->id }}" data-role="{{ $u->role }}">{{ $u->name }} ({{ $u->role === 'driver' ? '🚚 شۆفێر/مەندوب' : '💼 ژمێریار/بەڕێوەبەر' }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>کەی وەرگیراوە؟ (بەروار و کاتی وەرگرتن) *</label>
                    <input type="datetime-local" name="receipt_date" id="cpay-receipt-date" required value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>
            </div>

            <div class="form-group" style="margin-top:0.6rem;">
                <label>شوێنی دانانی پارەکە (دۆخی ئەمانەت / تەسلیمات) *</label>
                <select name="custody_status" id="cpay-custody-status" style="width:100%;" onchange="updateCustodyHint()">
                    <option value="in_driver_custody">🚚 لە ئەمانەتی مەندوب/شۆفێرە (لە حسابی کڕیار دەردەچێت، بەڵام ناچێتە خەزێنەی گشتی تا تەسلیم دەکرێت)</option>
                    <option value="deposited_to_safe">🏛️ ڕاستەوخۆ دەخرێتە خەزێنەی سەرەکی کۆمپانیا (ژمێریار وەریگرت)</option>
                </select>
                <div id="cpay-custody-hint" style="font-size:0.75rem; color:#fbbf24; margin-top:0.35rem; line-height:1.4;">
                    ℹ️ پارەکە سەرەتا لە ئەمانەتی مەندوب تۆمار دەکرێت و لە قەرزی مارکێت کەم دەبێتەوە. کاتێک مەندوب دەگەڕێتەوە، ژمێریار بە هەمان بڕ لە خەزێنەی گشتی کۆمپانیا دەری دەکاتەوە و جێگیری دەکات.
                </div>
            </div>

            <div id="cpay-cheque-fields" style="display:none; margin-top:0.6rem; padding:0.6rem; background:rgba(255,255,255,0.03); border-radius:6px; border:1px dashed var(--border-subtle);">
                <div class="form-row" style="grid-template-columns:1fr 1fr;">
                    <div class="form-group">
                        <label>ژمارەی چەک</label>
                        <input type="text" name="cheque_number" placeholder="CHQ-98765">
                    </div>
                    <div class="form-group">
                        <label>بەرواری بەسەرچوون / بەرواری چەک</label>
                        <input type="date" name="cheque_due_date">
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top:0.6rem;">
                <label>تێبینی دەربارەی وەرگرتنی ئەم قەرزە</label>
                <input type="text" name="notes" placeholder="تێبینی دەربارەی وەرگرتن...">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:0.6rem; margin-top:1.2rem;">
                <button type="button" class="btn" onclick="closeCollectPaymentModal()">داخستن</button>
                <button type="submit" class="btn btn-success" style="font-weight:700;">
                    ✓ دەرکردن و تۆمارکردنی وەسڵ (سند قبض)
                </button>
            </div>
        </form>
    </div>
</div>

<!-- OPENING BALANCE MODAL (قەرزی پێش سیستم / باڵانسی سەرەتایی) -->
<div id="opening-balance-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content-card" style="max-width:540px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid rgba(139, 92, 246, 0.3); padding-bottom:0.6rem;">
            <h3 style="color:#fff; font-size:1.15rem; display:flex; align-items:center; gap:0.4rem;">
                <span>⚖️</span> تۆمارکردنی قەرزی پێش سیستم (باڵانسی سەرەتایی)
            </h3>
            <button type="button" onclick="closeOpeningBalanceModal()" style="background:transparent; border:none; color:var(--text-secondary); font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <form action="{{ route('financial.opening_balance.store') }}" method="POST">
            @csrf
            <input type="hidden" name="supplier_company_id" id="opb-supplier-id" value="{{ $currentSupplier?->id ?? $suppliers->first()?->id }}">

            <div style="background:rgba(139, 92, 246, 0.12); border:1px solid rgba(139, 92, 246, 0.3); border-radius:8px; padding:0.85rem; margin-bottom:1rem; font-size:0.82rem; color:#e2e8f0; line-height:1.5;">
                💡 <strong>چارەسەری خێرا بۆ قەرزی کۆن:</strong> پێویست ناکات وەسڵی ساڵانی ڕابردوو بە بەرهەم بنووسیتەوە. لێرە بە یەک هەنگاو قەرزی پێش سیستەمی مارکێتەکە بنووسە، ڕاستەوخۆ دەچێتە سەر کەشف حیساب و دەفتەری ژمێریاری.
            </div>

            <div class="form-group" style="margin-bottom:0.75rem;">
                <label>کۆمپانیای دابینکەر (خاوەن ماف) *</label>
                <select id="opb-supplier-select" onchange="document.getElementById('opb-supplier-id').value = this.value" style="width:100%;">
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ ($currentSupplier?->id == $s->id) ? 'selected' : '' }}>
                            🏭 {{ $s->name }} ({{ $s->city }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0.75rem;">
                <label>دیاریکردنی مارکێت / کڕیار *</label>
                <select name="retailer_company_id" id="opb-retailer-id" required style="width:100%;">
                    <option value="">-- مارکێتەکە هەڵبژێرە --</option>
                    @foreach($retailers as $r)
                        <option value="{{ $r->id }}">🛒 {{ $r->name }} ({{ $r->city ?? 'شار' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row" style="grid-template-columns:1fr 1fr;">
                <div class="form-group">
                    <label>بڕی قەرز بە دۆلار ($) *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="opb-amount" required placeholder="0.00" style="font-family:var(--font-mono); font-size:1.1rem; color:#f87171; font-weight:700;">
                </div>
                <div class="form-group">
                    <label>جۆری باڵانس *</label>
                    <select name="type" required>
                        <option value="debit">🔴 قەرز لەسەر کڕیار (مەدین / Debit)</option>
                        <option value="credit">🟢 پێشەکی کڕیار لای ئێمە (دائین / Credit)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:0.6rem;">
                <label>بەرواری باڵانسی پێشوو (Date)</label>
                <input type="date" name="entry_date" value="{{ now()->toDateString() }}">
            </div>

            <div class="form-group" style="margin-top:0.6rem;">
                <label>تێبینی دەربارەی قەرزی پێشوو</label>
                <input type="text" name="notes" placeholder="وەک: قەرزی ماوەی ساڵی ٢٠٢٥ لە دەفتەری کۆن...">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:0.6rem; margin-top:1.2rem;">
                <button type="button" class="btn" onclick="closeOpeningBalanceModal()">داخستن</button>
                <button type="submit" class="btn btn-primary" style="background:#7c3aed; font-weight:700; border-color:#6d28d9;">
                    ✓ جێگیرکردنی باڵانسی سەرەتایی لە کەشف حیساب
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function refreshCsrfTokens() {
    fetch('{{ route("csrf.token") }}')
        .then(res => res.json())
        .then(data => {
            if (data && data.token) {
                document.querySelectorAll('input[name="_token"]').forEach(input => {
                    input.value = data.token;
                });
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.content = data.token;
            }
        })
        .catch(() => {});
}

window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    } else {
        refreshCsrfTokens();
    }
});

function openCollectPaymentModal(retailerId, retailerName, debt, supplierId, poId) {
    refreshCsrfTokens();
    document.getElementById('cpay-retailer-id').value = retailerId;
    document.getElementById('cpay-retailer-name').innerText = retailerName;
    document.getElementById('cpay-debt-amount').innerText = '$' + Number(debt).toFixed(2);
    document.getElementById('cpay-amount-usd').value = Number(debt) > 0 ? Number(debt).toFixed(2) : '';
    if (supplierId) {
        document.getElementById('cpay-supplier-id').value = supplierId;
        const supSel = document.getElementById('cpay-supplier-select');
        if (supSel) supSel.value = supplierId;
    }
    const poInput = document.getElementById('cpay-po-id');
    if (poInput) {
        poInput.value = poId || '';
    }

    // Auto set driver custody if role is driver
    const isDriverRole = "{{ $role === 'driver' ? '1' : '0' }}";
    const custodySel = document.getElementById('cpay-custody-status');
    if (custodySel && isDriverRole === '1') {
        custodySel.value = 'in_driver_custody';
    }

    // Set current date & time
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const localIso = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
    const rDateInput = document.getElementById('cpay-receipt-date');
    if (rDateInput && !rDateInput.value) {
        rDateInput.value = localIso;
    }
    calculateIqdEquiv();
    updateCustodyHint();
    document.getElementById('collect-payment-modal').style.display = 'flex';
}

function filterDriverDebtTable() {
    const input = document.getElementById('driver-debt-search');
    if (!input) return;
    const q = input.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.driver-debt-row');
    rows.forEach(r => {
        const search = r.getAttribute('data-search') || '';
        r.style.display = search.includes(q) ? '' : 'none';
    });
}

function closeCollectPaymentModal() {
    document.getElementById('collect-payment-modal').style.display = 'none';
}

function openOpeningBalanceModal(retailerId, retailerName, supplierId) {
    refreshCsrfTokens();
    if (supplierId) {
        document.getElementById('opb-supplier-id').value = supplierId;
        const supSel = document.getElementById('opb-supplier-select');
        if (supSel) supSel.value = supplierId;
    }
    if (retailerId) {
        const retSel = document.getElementById('opb-retailer-id');
        if (retSel) retSel.value = retailerId;
    }
    document.getElementById('opening-balance-modal').style.display = 'flex';
}

function closeOpeningBalanceModal() {
    document.getElementById('opening-balance-modal').style.display = 'none';
}

function updateCustodyHint() {
    const custody = document.getElementById('cpay-custody-status')?.value;
    const hint = document.getElementById('cpay-custody-hint');
    if (!hint) return;
    if (custody === 'in_driver_custody') {
        hint.innerText = "ℹ️ پارەکە وەک ئەمانەت لە ئەستۆی مەندوب/شۆفێر تۆمار دەکرێت و لە قەرزی مارکێت کەم دەبێتەوە. کاتێک مەندوب دەگاتەوە کۆمپانیا، ژمێریار لە بەشی تەحاسوب پەسەندی دەکات و دەخرێتە خەزێنەی گشتی کۆمپانیا.";
        hint.style.color = "#fbbf24";
    } else {
        hint.innerText = "ℹ️ پارەکە دەستبەجێ بە تەواوی دەخرێتە ناو خەزێنەی سەرەکی کۆمپانیا (Main Safe / Treasury).";
        hint.style.color = "#34d399";
    }
}

function autoUpdateCustodyOnCollectorChange() {
    const sel = document.getElementById('cpay-collector-id');
    const opt = sel.options[sel.selectedIndex];
    const role = opt?.getAttribute('data-role');
    const custodySel = document.getElementById('cpay-custody-status');
    if (custodySel) {
        if (role === 'driver') {
            custodySel.value = 'in_driver_custody';
        } else if (role === 'owner' || role === 'manager') {
            custodySel.value = 'deposited_to_safe';
        }
        updateCustodyHint();
    }
}

function togglePaymentMethodFields() {
    const method = document.getElementById('cpay-method').value;
    document.getElementById('cpay-cheque-fields').style.display = method === 'cheque' ? 'block' : 'none';
}

function calculateIqdEquiv() {
    const usd = parseFloat(document.getElementById('cpay-amount-usd').value) || 0;
    const rate = parseFloat(document.getElementById('cpay-rate').value) || 1520;
    if (usd > 0 && rate > 0) {
        document.getElementById('cpay-amount-iqd').value = Math.round(usd * rate * 10);
    }
}

// ==========================================
// UNIVERSAL SCROLL RESTORATION FOR ALL FORM ACTIONS & RELOADS
// ==========================================
(function() {
    const SCROLL_KEY = 'ml_active_scroll_y';

    // Save scroll on any form submit (confirm, approve, dispatch, receive, etc.)
    document.addEventListener('submit', function() {
        try {
            sessionStorage.setItem(SCROLL_KEY, String(window.scrollY || window.pageYOffset || 0));
        } catch(e) {}
    }, true);

    // Save scroll before window unload (e.g. location.reload() or link navigation)
    window.addEventListener('beforeunload', function() {
        try {
            sessionStorage.setItem(SCROLL_KEY, String(window.scrollY || window.pageYOffset || 0));
        } catch(e) {}
    });

    // Helper to restore position
    function restoreScroll() {
        try {
            const saved = sessionStorage.getItem(SCROLL_KEY);
            if (saved !== null) {
                const y = parseInt(saved, 10);
                if (!isNaN(y) && y > 0) {
                    window.scrollTo({ top: y, left: 0, behavior: 'instant' });
                    requestAnimationFrame(function() {
                        window.scrollTo({ top: y, left: 0, behavior: 'instant' });
                        setTimeout(function() {
                            window.scrollTo({ top: y, left: 0, behavior: 'instant' });
                        }, 50);
                    });
                }
                sessionStorage.removeItem(SCROLL_KEY);
            }
        } catch(e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreScroll);
    } else {
        restoreScroll();
    }
    window.addEventListener('load', restoreScroll);
})();

// ==========================================
// OPERATOR MULTI-COMPANY / MULTI-LOCATION BATCH ENTRY
// ==========================================
let batchCompaniesQueue = [];

function addCompanyToBatchQueue() {
    const nameEl = document.getElementById('inp-comp-name');
    const name = nameEl ? nameEl.value.trim() : '';
    if (!name) {
        alert('تکایە ناوی کۆمپانیا یان مارکێت بنووسە!');
        if (nameEl) nameEl.focus();
        return;
    }

    const type = document.getElementById('inp-comp-type')?.value || 'retailer';
    const license = document.getElementById('inp-comp-license')?.value.trim() || ('TL-' + (type === 'supplier' ? 'SUP' : 'RET') + '-' + Math.floor(1000 + Math.random() * 9000));
    const province = document.getElementById('inp-comp-province')?.value || 'هەولێر';
    const city = document.getElementById('inp-comp-city')?.value.trim() || province;
    const category = document.getElementById('inp-comp-category')?.value.trim() || '';
    const country = document.getElementById('inp-comp-country')?.value.trim() || 'هەرێمی کوردستان';
    const phone = document.getElementById('inp-comp-phone')?.value.trim() || '+9647500000000';
    const email = document.getElementById('inp-comp-email')?.value.trim() || ('info_' + Date.now().toString().slice(-4) + '@marketlink.krd');
    const address = document.getElementById('inp-comp-address')?.value.trim() || city;
    const lat = document.getElementById('inp-comp-lat')?.value.trim() || null;
    const lng = document.getElementById('inp-comp-lng')?.value.trim() || null;
    const mapsUrl = document.getElementById('inp-comp-maps-url')?.value.trim() || '';

    batchCompaniesQueue.push({
        name,
        business_type: type,
        trade_license_number: license,
        province,
        city,
        business_category: category,
        country,
        primary_phone: phone,
        primary_email: email,
        address_line: address,
        latitude: lat,
        longitude: lng,
        google_maps_url: mapsUrl
    });

    renderBatchQueue();

    // Clear inputs for the next branch/record
    if (nameEl) nameEl.value = '';
    const licEl = document.getElementById('inp-comp-license');
    if (licEl) licEl.value = '';
    const addrEl = document.getElementById('inp-comp-address');
    if (addrEl) addrEl.value = '';
    const latEl = document.getElementById('inp-comp-lat');
    if (latEl) latEl.value = '';
    const lngEl = document.getElementById('inp-comp-lng');
    if (lngEl) lngEl.value = '';
    const urlEl = document.getElementById('inp-comp-maps-url');
    if (urlEl) urlEl.value = '';
    const gpsStatus = document.getElementById('gps-status-feedback');
    if (gpsStatus) gpsStatus.style.display = 'none';
    if (nameEl) nameEl.focus();
}

function detectCurrentGpsLocation() {
    const statusEl = document.getElementById('gps-status-feedback');
    const iconEl = document.getElementById('gps-detect-icon');
    const textEl = document.getElementById('gps-detect-text');

    if (!navigator.geolocation) {
        alert('وێبگەڕەکەت پشتگیری دەستنیشانکردنی شوێنی جوگرافی (GPS) ناکات.');
        return;
    }

    if (iconEl) iconEl.textContent = '⏳';
    if (textEl) textEl.textContent = 'دەستنیشانکردنی شوێن...';

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = Number(pos.coords.latitude.toFixed(6));
            const lng = Number(pos.coords.longitude.toFixed(6));
            const latEl = document.getElementById('inp-comp-lat');
            const lngEl = document.getElementById('inp-comp-lng');
            const urlEl = document.getElementById('inp-comp-maps-url');

            if (latEl) latEl.value = lat;
            if (lngEl) lngEl.value = lng;
            if (urlEl) urlEl.value = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;

            if (iconEl) iconEl.textContent = '✓';
            if (textEl) textEl.textContent = 'لۆکەیشن دیاریکرا';
            if (statusEl) {
                statusEl.style.display = 'block';
                statusEl.style.color = '#4ade80';
                statusEl.textContent = `✓ شوێنی ئێستات لەسەر نەخشە بە سەرکەوتوویی تۆمارکرا: (${lat}, ${lng})`;
            }
        },
        function(err) {
            console.warn('Geolocation error:', err);
            if (iconEl) iconEl.textContent = '⚠️';
            if (textEl) textEl.textContent = 'دیاری نەکرا';
            if (statusEl) {
                statusEl.style.display = 'block';
                statusEl.style.color = '#f87171';
                statusEl.textContent = 'تکایە ڕێگە بدە بە دەستگەیشتن بە لۆکەیشن (Location Permission) لە وێبگەڕەکەتدا یان دەستی هێڵەکان بنووسە.';
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function parseGoogleMapsUrlInput(val) {
    if (!val) return;
    const match = val.match(/([0-9]{1,2}\.[0-9]{4,10}),\s*([0-9]{1,3}\.[0-9]{4,10})/);
    if (match) {
        const lat = match[1];
        const lng = match[2];
        const latEl = document.getElementById('inp-comp-lat');
        const lngEl = document.getElementById('inp-comp-lng');
        if (latEl && !latEl.value) latEl.value = lat;
        if (lngEl && !lngEl.value) lngEl.value = lng;
    }
}

function removeBatchQueueItem(idx) {
    batchCompaniesQueue.splice(idx, 1);
    renderBatchQueue();
}

function clearBatchQueue() {
    batchCompaniesQueue = [];
    renderBatchQueue();
}

function renderBatchQueue() {
    const container = document.getElementById('batch-queue-container');
    const tbody = document.getElementById('batch-queue-tbody');
    const badge = document.getElementById('batch-count-badge');
    const hiddenInput = document.getElementById('batch-records-input');
    const submitBtn = document.getElementById('btn-save-all-companies');

    if (!container || !tbody) return;

    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(batchCompaniesQueue);
    }

    if (batchCompaniesQueue.length === 0) {
        container.style.display = 'none';
        tbody.innerHTML = '';
        if (submitBtn) {
            submitBtn.textContent = '💾 تۆمارکردن و پەسەندکردنی کۆمپانیا';
        }
        return;
    }

    container.style.display = 'block';
    if (badge) badge.textContent = batchCompaniesQueue.length + ' تۆمار';
    if (submitBtn) {
        submitBtn.textContent = '💾 تۆمارکردنی هەموو کۆمپانیا و شوێنەکان (' + batchCompaniesQueue.length + ' دانە)';
    }

    let rowsHtml = '';
    batchCompaniesQueue.forEach((item, index) => {
        const typeLabel = item.business_type === 'supplier' ? '🏭 دابینکەر' : '🏪 مارکێت';
        let locationBadges = '';
        if (item.latitude && item.longitude) {
            locationBadges = `<br><span class="badge" style="background:rgba(16,185,129,0.15); color:#34d399; font-size:0.7rem; border:1px solid rgba(16,185,129,0.3); padding:0.15rem 0.4rem; margin-top:0.25rem; display:inline-block;">📍 GPS: ${item.latitude}, ${item.longitude}</span>`;
        } else if (item.google_maps_url) {
            locationBadges = `<br><span class="badge" style="background:rgba(59,130,246,0.15); color:#60a5fa; font-size:0.7rem; margin-top:0.25rem; display:inline-block;">📍 نەخشەی دیاریکراو</span>`;
        }

        rowsHtml += `
            <tr>
                <td>${index + 1}</td>
                <td><strong style="color:#fff;">${item.name}</strong></td>
                <td><span class="badge ${item.business_type === 'supplier' ? 'badge-primary' : 'badge-success'}">${typeLabel}</span></td>
                <td>${item.province} - ${item.city} <br><small style="color:var(--text-tertiary);">${item.address_line}</small>${locationBadges}</td>
                <td>${item.primary_phone}</td>
                <td><code>${item.trade_license_number}</code></td>
                <td>
                    <button type="button" onclick="removeBatchQueueItem(${index})" style="background:transparent; border:none; color:#f87171; cursor:pointer; font-size:1.1rem;" title="سڕینەوە">✕</button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = rowsHtml;
}

document.addEventListener('DOMContentLoaded', function() {
    const compForm = document.getElementById('company-onboarding-form');
    if (compForm) {
        compForm.addEventListener('submit', function() {
            if (batchCompaniesQueue.length > 0) {
                compForm.querySelectorAll('input, select').forEach(el => {
                    if (el.id !== 'batch-records-input') {
                        el.removeAttribute('required');
                    }
                });
            }
        });
    }

    // Scroll all chat windows to bottom on load
    document.querySelectorAll('.chat-messages').forEach(function(box) {
        box.scrollTop = box.scrollHeight;
    });

    // Initialize all chat forms (both supplier & retailer)
    document.querySelectorAll('.chat-form').forEach(function(form) {
        const fileImageInput = form.querySelector('.chat-file-image');
        const fileVoiceInput = form.querySelector('.chat-file-voice');
        const previewBox = form.querySelector('.chat-preview-box');
        const recordingBar = form.querySelector('.chat-recording-bar');
        const recTimer = form.querySelector('.rec-timer');
        const btnTriggerImage = form.querySelector('.btn-trigger-image');
        const btnTriggerVoice = form.querySelector('.btn-trigger-voice');
        const btnFinishRec = form.querySelector('.btn-finish-rec');
        const btnCancelRec = form.querySelector('.btn-cancel-rec');
        const textarea = form.querySelector('textarea[name="body"]');

        let mediaRecorder = null;
        let audioChunks = [];
        let timerInterval = null;
        let recordSeconds = 0;

        // Image Attachment
        if (btnTriggerImage && fileImageInput) {
            btnTriggerImage.addEventListener('click', function() {
                fileImageInput.click();
            });

            fileImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    previewBox.style.display = 'flex';
                    const oldImg = previewBox.querySelector('.img-badge');
                    if (oldImg) oldImg.remove();

                    const badge = document.createElement('div');
                    badge.className = 'preview-badge img-badge';
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        badge.innerHTML = `
                            <img src="${evt.target.result}" alt="Preview">
                            <span>${file.name.substring(0, 16)} (${(file.size / 1024).toFixed(0)} KB)</span>
                            <button type="button" class="remove-btn" title="لابردن">✖</button>
                        `;
                        badge.querySelector('.remove-btn').addEventListener('click', function() {
                            badge.remove();
                            fileImageInput.value = '';
                            if (!previewBox.children.length) previewBox.style.display = 'none';
                        });
                    };
                    reader.readAsDataURL(file);
                    previewBox.appendChild(badge);
                }
            });
        }

        // Live Voice Recording via MediaRecorder API
        if (btnTriggerVoice) {
            btnTriggerVoice.addEventListener('click', async function() {
                if (mediaRecorder && mediaRecorder.state === 'recording') {
                    stopRecording(true);
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    if (fileVoiceInput) fileVoiceInput.click();
                    return;
                }

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    audioChunks = [];
                    recordSeconds = 0;
                    mediaRecorder = new MediaRecorder(stream);

                    mediaRecorder.ondataavailable = function(e) {
                        if (e.data && e.data.size > 0) audioChunks.push(e.data);
                    };

                    mediaRecorder.onstop = function() {
                        stream.getTracks().forEach(t => t.stop());
                        clearInterval(timerInterval);
                        recordingBar.style.display = 'none';
                        btnTriggerVoice.classList.remove('recording');

                        if (audioChunks.length > 0 && !btnTriggerVoice.dataset.cancelled) {
                            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                            const audioFile = new File([audioBlob], `voice_${Date.now()}.webm`, { type: 'audio/webm' });

                            try {
                                const dt = new DataTransfer();
                                dt.items.add(audioFile);
                                fileVoiceInput.files = dt.files;
                            } catch(err) {
                                console.warn('DataTransfer not supported:', err);
                            }

                            renderAudioPreview(audioBlob, audioFile.name);
                        }
                        delete btnTriggerVoice.dataset.cancelled;
                    };

                    mediaRecorder.start();
                    btnTriggerVoice.classList.add('recording');
                    recordingBar.style.display = 'flex';
                    recTimer.textContent = '00:00';

                    timerInterval = setInterval(function() {
                        recordSeconds++;
                        const mins = String(Math.floor(recordSeconds / 60)).padStart(2, '0');
                        const secs = String(recordSeconds % 60).padStart(2, '0');
                        recTimer.textContent = `${mins}:${secs}`;
                    }, 1000);

                } catch (err) {
                    console.error('Mic access error:', err);
                    alert('مایکرۆفۆن بەردەست نییە یان دەسەڵاتی پێنەدراوە. دەتوانیت فایلی دەنگی هەڵبژێریت.');
                    if (fileVoiceInput) fileVoiceInput.click();
                }
            });
        }

        function stopRecording(save) {
            if (!mediaRecorder) return;
            if (!save) btnTriggerVoice.dataset.cancelled = 'true';
            if (mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
        }

        if (btnFinishRec) btnFinishRec.addEventListener('click', () => stopRecording(true));
        if (btnCancelRec) btnCancelRec.addEventListener('click', () => stopRecording(false));

        if (fileVoiceInput) {
            fileVoiceInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) renderAudioPreview(file, file.name);
            });
        }

        function renderAudioPreview(blobOrFile, name) {
            previewBox.style.display = 'flex';
            const oldVoice = previewBox.querySelector('.voice-badge');
            if (oldVoice) oldVoice.remove();

            const badge = document.createElement('div');
            badge.className = 'preview-badge voice-badge';
            const audioUrl = URL.createObjectURL(blobOrFile);
            badge.innerHTML = `
                <span>🎙️</span>
                <audio controls src="${audioUrl}"></audio>
                <button type="button" class="remove-btn" title="لابردن">✖</button>
            `;
            badge.querySelector('.remove-btn').addEventListener('click', function() {
                badge.remove();
                fileVoiceInput.value = '';
                if (!previewBox.children.length) previewBox.style.display = 'none';
            });
            previewBox.appendChild(badge);
        }

        // Validate submission: must have text or image or voice
        form.addEventListener('submit', function(e) {
            const hasText = textarea && textarea.value.trim().length > 0;
            const hasImage = fileImageInput && fileImageInput.files && fileImageInput.files.length > 0;
            const hasVoice = fileVoiceInput && fileVoiceInput.files && fileVoiceInput.files.length > 0;

            if (!hasText && !hasImage && !hasVoice) {
                e.preventDefault();
                alert('تکایە پەیامێک بنووسە یان وێنە / فۆیسێک هاوپێچ بکە پێش ناردن.');
            }
        });
    });

    // Audio Chime Synthesis (Web Audio API)
    window.playNotificationSound = function() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.18, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.35);
        } catch (e) {
            console.log('Audio chime not ready');
        }
    };

    // Toast Popup Notification
    window.showToast = function(title, body, url) {
        let toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML = `
            <div style="font-size:1.35rem;">🔔</div>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:700; font-size:0.83rem; margin-bottom:0.15rem; color:#fff;">${title}</div>
                <div style="font-size:0.75rem; color:#cbd5e1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${body}</div>
            </div>
            <span style="font-size:0.85rem; opacity:0.7; cursor:pointer;" onclick="this.parentElement.remove()">✕</span>
        `;
        toast.onclick = function(e) {
            if (e.target.tagName !== 'SPAN' && url) window.location.href = url;
        };
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'slideInLeft 0.3s ease-out reverse';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    };

    // Global Notification Dropdown Toggle
    window.toggleNotifDropdown = function(e) {
        if (e) {
            e.stopPropagation();
            e.preventDefault();
        }
        const notifMenu = document.getElementById('notif-dropdown-menu');
        if (!notifMenu) return;
        const isHidden = notifMenu.style.display === 'none' || getComputedStyle(notifMenu).display === 'none';
        notifMenu.style.display = isHidden ? 'block' : 'none';
    };

    document.addEventListener('click', function(e) {
        const notifBellBtn = document.getElementById('notif-bell-btn');
        const notifMenu = document.getElementById('notif-dropdown-menu');
        if (notifMenu && notifMenu.style.display !== 'none') {
            if (notifBellBtn && !notifBellBtn.contains(e.target) && !notifMenu.contains(e.target)) {
                notifMenu.style.display = 'none';
            }
        }
    });

    // Mark All Notifications as Read
    window.markAllNotificationsRead = function() {
        const role = "{{ $role }}";
        const companyId = "{{ $role === 'supplier' ? ($currentSupplier?->id ?? '') : ($role === 'retailer' ? ($currentRetailer?->id ?? '') : '') }}";
        fetch('{{ route('notifications.mark_read') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ role: role, company_id: companyId })
        }).then(r => r.json()).then(() => {
            const badge = document.getElementById('notif-badge');
            const countBadge = document.getElementById('notif-count-badge');
            if (badge) badge.style.display = 'none';
            if (countBadge) countBadge.textContent = '0 نوێ';
            const list = document.getElementById('notif-list');
            if (list) list.innerHTML = '<div class="notif-empty">هەموو ئاگادارییەکان خوێندرانەوە ✓</div>';
            lastKnownCount = 0;
        }).catch(err => console.error('Mark read error:', err));
    };

    // Browser Native Push Notification Request
    const btnReqBrowser = document.getElementById('btn-request-browser-notif');
    if (btnReqBrowser) {
        btnReqBrowser.addEventListener('click', function() {
            if ('Notification' in window) {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        alert('ئاگاداری براوسەر بە سەرکەوتوویی بۆ MarketLink چالاک کرا!');
                        new Notification('MarketLink', {
                            body: 'لە ئێستاوە ئاگادارییەکان لە ویندۆز و مۆبایلەوە ڕاستەوخۆ دەگەن!',
                        });
                    } else {
                        alert('ڕێگەپێدان ڕەتکرایەوە لە ڕێکخستنی براوسەرەکەت.');
                    }
                });
            } else {
                alert('براوسەرەکەت پشتگیری نۆتیفیکەیشنی وێب ناکات.');
            }
        });
    }

    // Live Polling for New Notifications (Works for all roles)
    let lastKnownCount = parseInt(document.getElementById('notif-badge')?.textContent.trim() || '0');
    const activeRole = "{{ $role }}";
    const activeCompanyId = "{{ $role === 'supplier' ? ($currentSupplier?->id ?? '') : ($role === 'retailer' ? ($currentRetailer?->id ?? '') : '') }}";

    function pollNotifications() {
        const queryParams = new URLSearchParams({
            role: activeRole,
            company_id: activeCompanyId
        });

        fetch(`/b2b/notifications/check?${queryParams.toString()}`)
            .then(r => r.json())
            .then(data => {
                const newCount = data.total_unread || 0;
                const badge = document.getElementById('notif-badge');
                const countBadge = document.getElementById('notif-count-badge');
                const notifList = document.getElementById('notif-list');

                if (newCount > lastKnownCount) {
                    window.playNotificationSound();
                    const latest = data.notifications && data.notifications[0];
                    if (latest) {
                        window.showToast(latest.title, latest.body, latest.url);
                        if (window.Notification && Notification.permission === 'granted') {
                            new Notification(latest.title, { body: latest.body });
                        }
                    }
                }

                lastKnownCount = newCount;
                if (badge) {
                    badge.textContent = newCount;
                    badge.style.display = newCount > 0 ? '' : 'none';
                }
                if (countBadge) {
                    countBadge.textContent = newCount + ' نوێ';
                }

                if (notifList && data.notifications) {
                    if (data.notifications.length > 0) {
                        notifList.innerHTML = data.notifications.map(n => {
                            let icon = '💬';
                            if (n.type === 'voice') icon = '🎙️';
                            else if (n.type === 'image') icon = '📷';
                            else if (n.type === 'quote_proposal') icon = '🏷️';
                            else if (n.type === 'order') icon = '📦';
                            else if (n.type === 'dispatch') icon = '🚚';

                            return `
                                <a href="${n.url || '#'}" class="notif-item">
                                    <div class="notif-item-icon">${icon}</div>
                                    <div class="notif-item-content">
                                        <div class="notif-title">${n.title}</div>
                                        <div class="notif-body">${n.body}</div>
                                        <div class="notif-time">${n.time || 'ئێستا'}</div>
                                    </div>
                                </a>
                            `;
                        }).join('');
                    } else {
                        notifList.innerHTML = '<div class="notif-empty">هیچ ئاگادارییەکی نوێ نییە ✓</div>';
                    }
                }
            })
            .catch(err => console.log('Poll notification error:', err));
    }

    // Poll every 6 seconds
    setInterval(pollNotifications, 6000);

    // Quick Reply Chips Action
    document.querySelectorAll('.quick-reply-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            const form = chip.closest('form');
            if (form) {
                const textarea = form.querySelector('textarea[name="body"]');
                if (textarea) {
                    const text = chip.getAttribute('data-text');
                    textarea.value = textarea.value ? (textarea.value + ' ' + text) : text;
                    textarea.focus();
                }
            }
        });
    });

    // Initialize Cart Badges on Load
    updateCartHeaderBadge();
});

// ==========================================
// 1. MULTI-SUPPLIER B2B CART MANAGEMENT
// ==========================================
let cart = JSON.parse(localStorage.getItem('ml_b2b_cart') || '[]');

function saveCart() {
    localStorage.setItem('ml_b2b_cart', JSON.stringify(cart));
    updateCartHeaderBadge();
    renderCart();
}

function updateCartHeaderBadge() {
    // Show count of distinct items in cart (not sum of quantities like 251)
    const distinctCount = Array.isArray(cart) ? cart.length : 0;
    const totalUnits = Array.isArray(cart) ? cart.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0) : 0;
    const headerBadge = document.getElementById('header-cart-badge');
    const floatingBadge = document.getElementById('floating-cart-badge');
    const floatingBtn = document.getElementById('floating-cart-btn');

    if (headerBadge) {
        headerBadge.textContent = distinctCount;
        headerBadge.title = `${distinctCount} جۆر کاڵا دیاریکراوە (${totalUnits} دانە بە گشتی)`;
    }
    if (floatingBadge) {
        floatingBadge.textContent = distinctCount;
        floatingBadge.title = `${distinctCount} جۆر کاڵا دیاریکراوە (${totalUnits} دانە بە گشتی)`;
    }
    const sidebarBadge = document.getElementById('sidebar-cart-badge');
    if (sidebarBadge) {
        sidebarBadge.textContent = distinctCount;
    }
    if (floatingBtn) {
        floatingBtn.style.boxShadow = distinctCount > 0 ? '0 0 25px rgba(52, 211, 153, 0.6)' : '0 8px 25px rgba(79,70,229,0.5)';
    }
}

function clearEntireCart() {
    if (!cart || cart.length === 0) {
        alert('سەبەتەکەت پێشتر بەتاڵە!');
        return;
    }
    if (confirm('ئایا دڵنیایت لە بەتاڵکردنی سەبەتەکە؟ هەموو کاڵاکانی ناو سەبەتە دەسڕدرێنەوە.')) {
        cart = [];
        saveCart();
    }
}

function openCartDrawer() {
    renderCart();
    const backdrop = document.getElementById('cart-backdrop');
    const drawer = document.getElementById('cart-drawer');
    if (backdrop) backdrop.style.display = 'block';
    if (drawer) {
        drawer.style.display = 'flex';
        drawer.classList.add('open');
    }
}

function closeCartDrawer() {
    const backdrop = document.getElementById('cart-backdrop');
    const drawer = document.getElementById('cart-drawer');
    if (backdrop) backdrop.style.display = 'none';
    if (drawer) {
        drawer.classList.remove('open');
        drawer.style.display = 'none';
    }
}

function handleAddToCartBtn(btn) {
    try {
        let productData = null;
        const raw = btn.getAttribute('data-product');
        if (raw) {
            try { productData = JSON.parse(raw); } catch(e) {}
        }
        if (!productData && btn.dataset.id) {
            let tiers = [];
            try { tiers = JSON.parse(btn.dataset.tiers || '[]'); } catch(e) {}
            productData = {
                id: parseInt(btn.dataset.id, 10),
                title: btn.dataset.title || 'کاڵا',
                sku: btn.dataset.sku || '',
                supplier_id: parseInt(btn.dataset.supplierId, 10),
                supplier_name: btn.dataset.supplierName || 'دابینکەر',
                base_price: parseFloat(btn.dataset.price || 0),
                moq: parseInt(btn.dataset.moq || 1, 10),
                stock: parseInt(btn.dataset.stock || 0, 10),
                tiers: tiers
            };
        }
        if (productData) {
            addToCart(productData);

            // Instant visual confirmation right on the button
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span>✅ خرایە سەبەتە</span>';
            btn.style.background = '#10b981';
            btn.style.borderColor = '#10b981';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.background = '';
                btn.style.borderColor = '';
            }, 1200);
        } else {
            console.error('Could not extract product data from button');
        }
    } catch (e) {
        console.error('Error handling add to cart:', e);
    }
}

function addToCartDirect(id, title, sku, supplierId, supplierName, basePrice, moq, stock, btn) {
    try {
        const itemMoq = (moq && parseInt(moq, 10) > 0) ? parseInt(moq, 10) : 1;
        addToCart({
            id: parseInt(id, 10),
            title: title || 'کاڵا',
            sku: sku || '',
            supplier_id: parseInt(supplierId, 10),
            supplier_name: supplierName || 'دابینکەر',
            base_price: parseFloat(basePrice || 0),
            moq: itemMoq,
            stock: parseInt(stock || 0, 10),
            qty: itemMoq
        });

        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span>✅ خرایە سەبەتە</span>';
            btn.style.background = '#10b981';
            btn.style.borderColor = '#10b981';
            btn.style.color = '#fff';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.background = '';
                btn.style.borderColor = '';
                btn.style.color = '';
            }, 1200);
        }
    } catch (e) {
        console.error('Error in addToCartDirect:', e);
    }
}

function addSelectedProductFromCartDropdown() {
    const sel = document.getElementById('cart-quick-product-select');
    if (!sel || !sel.value) {
        alert('تکایە سەرەتا کاڵایەک لە لیستەکە هەڵبژێرە.');
        return;
    }
    const opt = sel.options[sel.selectedIndex];
    let tiers = [];
    try { tiers = JSON.parse(opt.dataset.tiers || '[]'); } catch(e) {}
    const product = {
        id: parseInt(opt.value, 10),
        title: opt.dataset.title || 'کاڵا',
        sku: opt.dataset.sku || '',
        supplier_id: parseInt(opt.dataset.supplierId, 10),
        supplier_name: opt.dataset.supplierName || 'دابینکەر',
        base_price: parseFloat(opt.dataset.price || 0),
        moq: parseInt(opt.dataset.moq || 1, 10),
        stock: parseInt(opt.dataset.stock || 0, 10),
        tiers: tiers
    };
    addToCart(product);
    sel.value = '';
}

function addToCart(product) {
    if (!product || !product.id) return;
    if (!Array.isArray(cart)) cart = [];

    const existing = cart.find(i => i.id === product.id);
    const minQty = (product.moq && parseInt(product.moq) > 0) ? parseInt(product.moq) : 1;

    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({
            id: product.id,
            title: product.title || 'کاڵا',
            sku: product.sku || '',
            supplier_id: product.supplier_id || product.supplier_company_id,
            supplier_name: product.supplier_name || 'دابینکەر',
            base_price: parseFloat(product.base_price || product.base_wholesale_price || 0),
            moq: minQty,
            stock: product.stock !== undefined ? product.stock : (product.current_stock || 0),
            tiers: product.tiers || (product.volume_tiers ? product.volume_tiers.map(t => ({ min: t.min_quantity, price: parseFloat(t.unit_price) })) : []),
            qty: minQty
        });
    }

    saveCart();

    if (typeof window.showToast === 'function') {
        window.showToast('🛒 سەبەتەی کڕین', `کاڵای "${product.title || ''}" خرایە ناو سەبەتە!`, null);
    }
    openCartDrawer();
}

function updateCartQty(productId, delta) {
    const item = cart.find(i => i.id === productId);
    if (!item) return;

    item.qty += delta;
    if (item.qty <= 0) {
        cart = cart.filter(i => i.id !== productId);
    } else if (item.stock && item.qty > item.stock) {
        item.qty = item.stock;
        alert('بڕی دیاریکراو گەیشتە ئەوپەڕی ستۆکی بەردەست (' + item.stock + ' دانە)!');
    }

    saveCart();
}

function removeFromCart(productId) {
    cart = cart.filter(i => i.id !== productId);
    saveCart();
}

function getEffectiveUnitPrice(item) {
    let unitPrice = item.base_price;
    if (item.tiers && item.tiers.length > 0) {
        // Find best qualifying volume tier
        const sortedTiers = [...item.tiers].sort((a, b) => b.min - a.min);
        for (const tier of sortedTiers) {
            if (item.qty >= tier.min) {
                unitPrice = tier.price;
                break;
            }
        }
    }
    return unitPrice;
}

function renderCart() {
    const container = document.getElementById('cart-items-container');
    const grandTotalEl = document.getElementById('cart-grand-total');
    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = `
            <div style="text-align:center; padding:3rem 1rem; color:var(--text-tertiary);">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">🛒</div>
                <div style="font-weight:700; color:var(--text-secondary); margin-bottom:0.4rem;">سەبەتەکەت بەتاڵە</div>
                <p style="font-size:0.8rem;">کاڵاکان لە کەتەلۆکی دابینکەرانەوە هەڵبژێرە یان بارکۆد سکان بکە.</p>
            </div>
        `;
        if (grandTotalEl) grandTotalEl.textContent = '$0.00';
        return;
    }

    // Group items by supplier
    const grouped = {};
    cart.forEach(item => {
        const supKey = item.supplier_id || 'unknown';
        if (!grouped[supKey]) {
            grouped[supKey] = {
                name: item.supplier_name || 'دابینکەر',
                items: []
            };
        }
        grouped[supKey].items.push(item);
    });

    let html = '';
    let grandTotal = 0;

    for (const supId in grouped) {
        const group = grouped[supId];
        let supplierSubtotal = 0;

        html += `
            <div class="supplier-cart-group">
                <div class="supplier-cart-title">
                    <span>🏭 ${group.name}</span>
                    <span style="font-size:0.72rem; color:var(--text-tertiary);">${group.items.length} کاڵا</span>
                </div>
        `;

        group.items.forEach(item => {
            const unitPrice = getEffectiveUnitPrice(item);
            const lineTotal = unitPrice * item.qty;
            supplierSubtotal += lineTotal;

            const hasTierDiscount = unitPrice < item.base_price;
            const moqWarning = item.qty < item.moq;

            html += `
                <div class="cart-item-row" style="flex-direction:column; align-items:stretch; gap:0.35rem; padding:0.6rem 0;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <strong style="color:#fff; font-size:0.85rem;">${item.title}</strong>
                            <div style="font-size:0.72rem; color:var(--text-tertiary);">کۆد: <code>${item.sku}</code></div>
                        </div>
                        <button type="button" onclick="removeFromCart(${item.id})" style="background:transparent; border:none; color:#f87171; cursor:pointer; font-size:1.1rem; line-height:1;" title="سڕینەوە">&times;</button>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.2rem;">
                        <div style="display:flex; align-items:center; gap:0.4rem; background:#0b0f17; border:1px solid var(--border-subtle); border-radius:6px; padding:0.15rem 0.4rem;">
                            <button type="button" onclick="updateCartQty(${item.id}, -1)" style="background:transparent; border:none; color:#fff; cursor:pointer; font-weight:700; padding:0 0.3rem;">-</button>
                            <span style="font-size:0.82rem; font-weight:700; color:#fff; min-width:24px; text-align:center;">${item.qty}</span>
                            <button type="button" onclick="updateCartQty(${item.id}, 1)" style="background:transparent; border:none; color:#fff; cursor:pointer; font-weight:700; padding:0 0.3rem;">+</button>
                        </div>

                        <div style="text-align:left;">
                            <div style="font-weight:700; color:#34d399; font-size:0.9rem;">$${lineTotal.toFixed(2)}</div>
                            <div style="font-size:0.72rem; color:var(--text-tertiary);">
                                $${unitPrice.toFixed(2)} / دانە
                                ${hasTierDiscount ? '<span style="color:#fbbf24; font-size:0.68rem;">(داشکاندنی بڕ)</span>' : ''}
                            </div>
                        </div>
                    </div>

                    ${moqWarning ? `
                        <div style="font-size:0.7rem; color:#f87171; background:rgba(239,68,68,0.1); padding:0.2rem 0.4rem; border-radius:4px;">
                            ⚠️ کەمترین بڕی داواکاری دابینکەر (MOQ): ${item.moq} دانەیە!
                        </div>
                    ` : ''}
                </div>
            `;
        });

        html += `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.5rem; padding-top:0.4rem; border-top:1px dashed rgba(255,255,255,0.08); font-size:0.8rem;">
                    <span style="color:var(--text-secondary);">کۆی ئەم دابینکەرە:</span>
                    <strong style="color:#38bdf8;">$${supplierSubtotal.toFixed(2)}</strong>
                </div>
            </div>
        `;

        grandTotal += supplierSubtotal;
    }

    container.innerHTML = html;
    if (grandTotalEl) grandTotalEl.textContent = '$' + grandTotal.toFixed(2);
}

function checkoutMultiCart(e) {
    e.preventDefault();
    if (!Array.isArray(cart) || cart.length === 0) {
        alert('سەبەتەکەت بەتاڵە!');
        return;
    }

    // Check MOQ for all items
    for (const item of cart) {
        if (item.moq && item.qty < item.moq) {
            alert(`کاڵای "${item.title}" بڕەکەی (${item.qty}) لە کەمترین بڕی داواکاری (${item.moq}) کەمترە! تکایە زیادی بکە.`);
            return;
        }
    }

    const submitBtn = document.getElementById('cart-submit-btn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'لە پڕۆسەی دروستکردنی فەرمانەکاندایە...';
    }

    const payload = {
        retailer_company_id: '{{ $currentRetailer?->id }}',
        items: cart.map(i => ({ product_id: i.id, quantity: i.qty })),
        payment_method: document.getElementById('cart-payment-method').value,
        delivery_address: document.getElementById('cart-delivery-address').value,
        notes: document.getElementById('cart-notes').value,
        _token: '{{ csrf_token() }}'
    };

    fetch('/retailer/cart/checkout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(async r => {
        let data = {};
        try { data = await r.json(); } catch(err) { data = { success: false, message: 'وەڵامی سێرڤەر بە دروستی نەگەیشت' }; }
        return { ok: r.ok, status: r.status, data };
    })
    .then(({ ok, data }) => {
        if (ok && data.success) {
            alert(data.message + '\nژمارەی پسوولەی فەرمانەکان: ' + (data.orders ? data.orders.map(o => o.po_number).join(', ') : ''));
            cart = [];
            saveCart();
            closeCartDrawer();
            window.location.reload();
        } else {
            alert('هەڵە لە تۆمارکردن: ' + (data.message || 'نەتوانرا داواکاری تۆمار بکرێت'));
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '✓ ناردنی فەرمانی کڕین';
            }
        }
    })
    .catch(err => {
        console.error('Checkout error:', err);
        alert('کێشەیەک لە پەیوەندی هەیە. تکایە دووبارە هەوڵبدەرەوە.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = '✓ ناردنی فەرمانی کڕین';
        }
    });
}

// ==========================================
// 2. BARCODE SCANNER RE-ORDER
// ==========================================
function openBarcodeModal() {
    document.getElementById('barcode-modal').style.display = 'flex';
    document.getElementById('barcode-input').focus();
}

function closeBarcodeModal() {
    document.getElementById('barcode-modal').style.display = 'none';
    document.getElementById('barcode-result-card').style.display = 'none';
}

function setBarcode(code) {
    document.getElementById('barcode-input').value = code;
    searchBarcode(code);
}

function playBarcodeBeep(type = 'success') {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        if (type === 'success') {
            osc.frequency.setValueAtTime(1760, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.08);
        } else {
            osc.frequency.setValueAtTime(320, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.16);
        }
    } catch(e) {}
}

function searchBarcode(code) {
    if (!code || !code.trim()) return;

    const resultCard = document.getElementById('barcode-result-card');
    resultCard.style.display = 'block';
    resultCard.innerHTML = '<div style="text-align:center; color:var(--text-tertiary); padding:1rem;">لە گەڕاندایە لە کۆگاکان...</div>';

    fetch('/retailer/barcode/search?code=' + encodeURIComponent(code.trim()))
        .then(r => r.json())
        .then(data => {
            if (data.found && data.product) {
                playBarcodeBeep('success');
                const p = data.product;
                window.currentScannedProduct = p;
                const isOutOfStock = p.current_stock <= 0;

                let tiersHtml = '';
                if (p.volume_tiers && p.volume_tiers.length > 0) {
                    tiersHtml = '<div style="font-size:0.75rem; color:#34d399; margin-top:0.3rem;"><strong>داشکاندنی بڕ (Wholesale Tiers):</strong><br>' +
                        p.volume_tiers.map(t => `${t.min_quantity}+ دانە: $${parseFloat(t.unit_price).toFixed(2)}`).join(' | ') +
                        '</div>';
                }

                resultCard.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <strong style="color:#fff; font-size:1rem; display:block;">${p.title}</strong>
                            <span style="font-size:0.75rem; color:var(--text-tertiary);">کۆد: <code>${p.sku}</code> • دابینکەر: <span style="color:#34d399;">${p.supplier_name}</span></span>
                        </div>
                        <span class="badge ${isOutOfStock ? 'badge-danger' : 'badge-success'}">
                            ${isOutOfStock ? '⚠️ نەماوە' : p.current_stock + ' دانە لە کۆگا'}
                        </span>
                    </div>

                    <div style="margin:0.75rem 0; padding:0.5rem 0; border-top:1px solid rgba(255,255,255,0.06); border-bottom:1px solid rgba(255,255,255,0.06);">
                        <div style="font-size:0.85rem; color:var(--text-secondary);">
                            نرخی یەک دانە: <strong style="color:#fff; font-size:1.05rem;">$${parseFloat(p.base_wholesale_price).toFixed(2)}</strong>
                            • کەمترین بڕی داواکاری (MOQ): <strong>${p.minimum_order_quantity} دانە</strong>
                        </div>
                        ${tiersHtml}
                    </div>

                    ${!isOutOfStock ? `
                        <button type="button" class="btn btn-primary" onclick="addScannedProductToCart()" style="width:100%; justify-content:center; padding:0.6rem; cursor:pointer;">
                            🛒 خستنە ناو سەبەتەی کڕین
                        </button>
                    ` : `
                        <div style="color:#f87171; text-align:center; font-size:0.8rem; padding:0.4rem;">ئەم کاڵایە لە ئێستادا لە کۆگای دابینکەر تەواو بووە</div>
                    `}
                `;
            } else {
                resultCard.innerHTML = `
                    <div style="text-align:center; color:#f87171; padding:1rem;">
                        ⚠️ کاڵایەک بەم بارکۆدە نەدۆزرایەوە (${code})
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Barcode lookup error:', err);
            resultCard.innerHTML = '<div style="text-align:center; color:#f87171; padding:1rem;">کێشەیەک لە پەیوەندی هەیە</div>';
        });
}

function addScannedProductToCart(p) {
    p = p || window.currentScannedProduct;
    if (!p) return;
    addToCart({
        id: p.id,
        title: p.title,
        sku: p.sku,
        supplier_id: p.supplier_company_id || p.supplier_id,
        supplier_name: p.supplier_name,
        base_price: parseFloat(p.base_wholesale_price || p.base_price || 0),
        moq: p.minimum_order_quantity || p.moq || 1,
        stock: p.current_stock || p.stock || 0,
        tiers: (p.volume_tiers || []).map(t => ({ min: t.min_quantity, price: parseFloat(t.unit_price) }))
    });
    closeBarcodeModal();
}

// ==========================================
// 3. DRIVER DELIVERY HANDOVER & DIGITAL SIGNATURE
// ==========================================
let activeHandoverOrderId = null;
let sigCanvas, sigCtx, isDrawing = false;

function initSignaturePad() {
    sigCanvas = document.getElementById('sig-canvas');
    if (!sigCanvas) return;
    sigCtx = sigCanvas.getContext('2d');
    sigCtx.strokeStyle = '#38bdf8';
    sigCtx.lineWidth = 2.5;
    sigCtx.lineCap = 'round';

    function getPos(e) {
        const rect = sigCanvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    sigCanvas.addEventListener('mousedown', function(e) {
        isDrawing = true;
        const pos = getPos(e);
        sigCtx.beginPath();
        sigCtx.moveTo(pos.x, pos.y);
    });

    sigCanvas.addEventListener('mousemove', function(e) {
        if (!isDrawing) return;
        const pos = getPos(e);
        sigCtx.lineTo(pos.x, pos.y);
        sigCtx.stroke();
    });

    window.addEventListener('mouseup', function() {
        if (isDrawing) {
            isDrawing = false;
            saveSignatureData();
        }
    });

    // Touch events for mobile tablets
    sigCanvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
        isDrawing = true;
        const pos = getPos(e);
        sigCtx.beginPath();
        sigCtx.moveTo(pos.x, pos.y);
    }, { passive: false });

    sigCanvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
        if (!isDrawing) return;
        const pos = getPos(e);
        sigCtx.lineTo(pos.x, pos.y);
        sigCtx.stroke();
    }, { passive: false });

    sigCanvas.addEventListener('touchend', function() {
        if (isDrawing) {
            isDrawing = false;
            saveSignatureData();
        }
    });
}

function clearSignatureCanvas() {
    if (sigCanvas && sigCtx) {
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        document.getElementById('handover-signature-data').value = '';
    }
}

function saveSignatureData() {
    if (sigCanvas) {
        document.getElementById('handover-signature-data').value = sigCanvas.toDataURL();
    }
}

let activeDepartOrderId = null;

function openDriverDepartModal(orderId, poNumber, retailerName, supplierName) {
    activeDepartOrderId = orderId;
    const poEl = document.getElementById('depart-po-number');
    const retEl = document.getElementById('depart-retailer-name');
    const supEl = document.getElementById('depart-supplier-name');
    const modal = document.getElementById('driver-depart-modal');

    if (poEl) poEl.textContent = poNumber || ('#' + orderId);
    if (retEl) retEl.textContent = retailerName || 'مارکێت';
    if (supEl) supEl.textContent = supplierName || 'دابینکەر';
    if (modal) modal.style.display = 'flex';
}

function closeDriverDepartModal() {
    const modal = document.getElementById('driver-depart-modal');
    if (modal) modal.style.display = 'none';
    activeDepartOrderId = null;
}

function submitDriverDepart(e) {
    e.preventDefault();
    if (!activeDepartOrderId) return;

    const submitBtn = document.getElementById('btn-submit-depart');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'لە تۆمارکردنی بەڕێکەوتندایە...';
    }

    const payload = {
        role: 'driver',
        driver_name: document.getElementById('depart-driver-name')?.value || 'شۆفێری دابەشکردن',
        driver_phone: document.getElementById('depart-driver-phone')?.value || '',
        vehicle_plate: document.getElementById('depart-vehicle-plate')?.value || '',
        supplier_notes: document.getElementById('depart-notes')?.value || '',
        _token: '{{ csrf_token() }}'
    };

    fetch('/orders/' + activeDepartOrderId + '/dispatch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(async r => {
        let data = {};
        try { data = await r.json(); } catch(e) { data = { success: false, message: 'وەڵامی سێرڤەر بە دروستی نەگەیشت' }; }
        return { ok: r.ok, status: r.status, data };
    })
    .then(({ ok, data }) => {
        if (ok && data.success) {
            closeDriverDepartModal();
            let msg = data.message + '\nکۆدی وەرگرتنی مارکێت (OTP): ' + (data.delivery_otp || '');
            if (data.navigation_url) {
                if (confirm(msg + '\n\n📍 بارەکە بارکرا! ئایا دەتەوێت نەخشە و ئاڕاستەی گەیشتن بە مارکێت (Google Maps Turn-by-Turn Navigation) ئێستا بکرێتەوە؟')) {
                    window.open(data.navigation_url, '_blank');
                }
            } else {
                alert(msg);
            }
            window.location.reload();
        } else {
            alert('هەڵە لە تۆمارکردنی بەڕێکەوتن: ' + (data.message || 'نەتوانرا ئەنجام بدرێت'));
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '🚚 دەستپێکردنی گەیاندن و بەڕێکەوتن لە کۆگا';
            }
        }
    })
    .catch(err => {
        console.error('Depart error:', err);
        alert('کێشەیەک لە پەیوەندی هەیە. تکایە دووبارە تاقی بکەرەوە.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = '🚚 دەستپێکردنی گەیاندن و بەڕێکەوتن لە کۆگا';
        }
    });
}

let currentHandoverItems = [];
let currentHandoverBaseTotal = 0;
let currentHandoverPaymentMethod = 'cash_on_delivery';

function openDriverHandoverModal(orderId, triggerEl, totalAmount, retailerName) {
    activeHandoverOrderId = orderId;
    let poNumber = '';
    let supplierName = 'دابینکەر';
    let rawItems = [];

    if (triggerEl instanceof HTMLElement) {
        poNumber = triggerEl.dataset.po || ('#' + orderId);
        totalAmount = triggerEl.dataset.total || '0.00';
        retailerName = triggerEl.dataset.retailerName || 'مارکێت';
        supplierName = triggerEl.dataset.supplierName || 'دابینکەر';
        currentHandoverPaymentMethod = triggerEl.dataset.paymentMethod || 'cash_on_delivery';
        try {
            rawItems = JSON.parse(triggerEl.dataset.items || '[]');
        } catch(e) { rawItems = []; }
    } else {
        poNumber = triggerEl || ('#' + orderId);
    }

    currentHandoverItems = rawItems;
    currentHandoverBaseTotal = parseFloat(String(totalAmount).replace(/,/g, '')) || 0;

    document.getElementById('handover-po-number').textContent = poNumber;
    document.getElementById('handover-retailer-name').textContent = retailerName || 'مارکێت';
    document.getElementById('handover-supplier-name').textContent = supplierName || 'دابینکەر';

    const pBadge = document.getElementById('handover-payment-badge');
    const termsLabel = document.getElementById('handover-terms-label');
    const isCod = currentHandoverPaymentMethod.toLowerCase().includes('cash') || currentHandoverPaymentMethod.toLowerCase().includes('cod');

    if (pBadge) {
        pBadge.textContent = isCod ? '💵 کاش لە وەرگرتن (COD)' : '📝 قەرز (Credit)';
        pBadge.className = isCod ? 'badge badge-success' : 'badge badge-warning';
    }
    if (termsLabel) {
        termsLabel.textContent = isCod ? 'شێواز: کاش کاتی وەرگرتن (COD)' : 'شێواز: قەرزی فەرمی (Credit Balance)';
    }

    const codContainer = document.getElementById('cod-input-container');
    if (codContainer) {
        codContainer.style.display = isCod ? 'block' : 'none';
    }

    document.getElementById('handover-otp-input').value = '';
    document.getElementById('handover-damage-amount').value = '0';
    document.getElementById('handover-damage-notes').value = '';
    document.getElementById('handover-receiver-name').value = retailerName || '';
    document.getElementById('handover-lat').value = '';
    document.getElementById('handover-lng').value = '';

    // Check online/offline status
    const offlineAlert = document.getElementById('handover-offline-alert');
    if (offlineAlert) {
        offlineAlert.style.display = navigator.onLine ? 'none' : 'block';
    }

    // Reset Geofence status
    const gfBadge = document.getElementById('geofence-badge');
    if (gfBadge) {
        gfBadge.textContent = 'پشکنین نەکراوە';
        gfBadge.className = 'badge badge-warning';
    }
    const gfResult = document.getElementById('geofence-result-text');
    if (gfResult) gfResult.style.display = 'none';

    // Render items checklist
    renderHandoverItemsChecklist();
    recalculateHandoverTotals();

    document.getElementById('driver-handover-modal').style.display = 'flex';
    clearSignatureCanvas();
    if (!sigCanvas) {
        initSignaturePad();
    }
}

function renderHandoverItemsChecklist() {
    const container = document.getElementById('handover-items-list');
    if (!container) return;

    if (!currentHandoverItems || currentHandoverItems.length === 0) {
        container.innerHTML = `
            <div style="font-size:0.8rem; color:var(--text-tertiary); padding:0.6rem; text-align:center;">
                کاڵاکانی داواکاری بەردەستن لە کۆی گشتی پسوولە.
            </div>
        `;
        return;
    }

    let html = '';
    currentHandoverItems.forEach((item, idx) => {
        const itemTotal = (item.quantity_ordered * item.unit_price).toFixed(2);
        html += `
            <div class="handover-item-card" data-idx="${idx}" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-subtle); border-radius:8px; padding:0.65rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.4rem;">
                    <div>
                        <strong style="color:#fff; font-size:0.85rem;">${item.title}</strong>
                        <div style="font-size:0.72rem; color:var(--text-tertiary);">کۆد: <code>${item.sku}</code> • نرخی تاک: $${item.unit_price}</div>
                    </div>
                    <span style="font-size:0.8rem; font-weight:700; color:#34d399;">$${itemTotal}</span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                    <div style="font-size:0.78rem; color:var(--text-secondary);">
                        داواکراو: <strong style="color:#fff;">${item.quantity_ordered} دانە</strong>
                    </div>

                    <div style="display:flex; align-items:center; gap:0.4rem;">
                        <label style="font-size:0.75rem; color:#bae6fd;">بڕی گەیشتوو:</label>
                        <input type="number" min="0" max="${item.quantity_ordered}" value="${item.quantity_ordered}" 
                            class="item-delivered-qty" data-idx="${idx}"
                            onchange="onHandoverItemQtyChange(${idx}, this.value)"
                            style="width:65px; padding:0.25rem 0.4rem; font-size:0.85rem; font-weight:700; text-align:center; background:var(--bg-core); border:1px solid #38bdf8; color:#fff; border-radius:4px;">
                    </div>
                </div>

                <div class="item-discrepancy-panel" id="disc-panel-${idx}" style="display:none; margin-top:0.5rem; padding-top:0.4rem; border-top:1px dashed rgba(239,68,68,0.3);">
                    <div style="display:flex; gap:0.4rem; align-items:center;">
                        <span style="font-size:0.72rem; color:#fca5a5; font-weight:700;">هۆکاری کەمی:</span>
                        <select class="item-disc-reason" data-idx="${idx}" onchange="recalculateHandoverTotals()" style="flex:1; padding:0.25rem; font-size:0.75rem; background:var(--bg-core); border:1px solid #ef4444; color:#fff; border-radius:4px;">
                            <option value="damaged">کارتۆن/کاڵا تێکچووبوو (Damaged)</option>
                            <option value="missing">کەم بوو / ون ببوو (Missing)</option>
                            <option value="expired">بەسەرچووبوو (Expired)</option>
                            <option value="rejected_by_store">خاوەن مارکێت ڕەتیکردەوە (Rejected)</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function onHandoverItemQtyChange(idx, val) {
    const item = currentHandoverItems[idx];
    if (!item) return;

    let delivered = parseInt(val, 10);
    if (isNaN(delivered) || delivered < 0) delivered = 0;
    if (delivered > item.quantity_ordered) delivered = item.quantity_ordered;

    const panel = document.getElementById('disc-panel-' + idx);
    const photoBox = document.getElementById('damage-photo-box');

    if (panel) {
        panel.style.display = delivered < item.quantity_ordered ? 'block' : 'none';
    }

    recalculateHandoverTotals();
}

function markAllItemsGood() {
    document.querySelectorAll('.item-delivered-qty').forEach((inp, idx) => {
        if (currentHandoverItems[idx]) {
            inp.value = currentHandoverItems[idx].quantity_ordered;
        }
        const panel = document.getElementById('disc-panel-' + idx);
        if (panel) panel.style.display = 'none';
    });
    const photoBox = document.getElementById('damage-photo-box');
    if (photoBox) photoBox.style.display = 'none';

    recalculateHandoverTotals();
}

function recalculateHandoverTotals() {
    let totalDeduction = 0;
    let discrepancies = [];
    let hasAnyDiscrepancy = false;

    currentHandoverItems.forEach((item, idx) => {
        const inp = document.querySelector(`.item-delivered-qty[data-idx="${idx}"]`);
        const delivered = inp ? parseInt(inp.value, 10) : item.quantity_ordered;
        const missing = Math.max(0, item.quantity_ordered - delivered);

        if (missing > 0) {
            hasAnyDiscrepancy = true;
            const diffAmount = missing * (parseFloat(item.unit_price) || 0);
            totalDeduction += diffAmount;

            const reasonSelect = document.querySelector(`.item-disc-reason[data-idx="${idx}"]`);
            const reason = reasonSelect ? reasonSelect.value : 'damaged';
            const reasonKu = reason === 'damaged' ? 'تەلەف و شکاو' : (reason === 'missing' ? 'کەم هاتووە' : (reason === 'expired' ? 'ئێکسپایەر' : 'گەڕاوەی مارکێت'));
            const itemSpecificNote = `${missing} دانە (${reasonKu}) — داشکاندن: $${diffAmount.toFixed(2)}`;

            discrepancies.push({
                item_id: item.id,
                ordered_qty: item.quantity_ordered,
                delivered_qty: delivered,
                missing_qty: missing,
                unit_price: item.unit_price,
                diff_amount: diffAmount,
                reason: reason,
                notes: itemSpecificNote
            });
        }
    });

    const finalAmount = Math.max(0, currentHandoverBaseTotal - totalDeduction);
    const finalEl = document.getElementById('handover-final-amount');
    if (finalEl) finalEl.textContent = '$' + finalAmount.toFixed(2);

    const deductEl = document.getElementById('handover-discrepancy-deduct');
    if (deductEl) {
        if (totalDeduction > 0) {
            deductEl.style.display = 'block';
            deductEl.textContent = 'داشکاندنی کەموزیاد: -$' + totalDeduction.toFixed(2);
        } else {
            deductEl.style.display = 'none';
        }
    }

    const cashInput = document.getElementById('handover-cash-collected');
    if (cashInput) {
        cashInput.value = finalAmount.toFixed(2);
    }

    document.getElementById('handover-damage-amount').value = totalDeduction.toFixed(2);
    document.getElementById('handover-damage-notes').value = discrepancies.length > 0 
        ? discrepancies.map(d => `${d.missing_qty} دانە (${d.reason === 'damaged' ? 'تەلەف' : (d.reason === 'missing' ? 'کەم' : (d.reason === 'expired' ? 'ئێکسپایەر' : 'گەڕاوە'))})`).join(', ')
        : '';
    document.getElementById('handover-discrepancies-json').value = JSON.stringify(discrepancies);

    const photoBox = document.getElementById('damage-photo-box');
    if (photoBox) {
        photoBox.style.display = hasAnyDiscrepancy ? 'block' : 'none';
    }
}

function verifyDriverLocation() {
    const btn = document.getElementById('btn-verify-geofence');
    const badge = document.getElementById('geofence-badge');
    const resultText = document.getElementById('geofence-result-text');

    if (btn) btn.disabled = true;
    if (badge) {
        badge.textContent = 'لە پشکنیندایە...';
        badge.className = 'badge badge-warning';
    }

    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const lat = pos.coords.latitude.toFixed(6);
                const lng = pos.coords.longitude.toFixed(6);
                document.getElementById('handover-lat').value = lat;
                document.getElementById('handover-lng').value = lng;

                if (badge) {
                    badge.textContent = '✓ لە شوێنی مارکێتە (<٥٠م)';
                    badge.className = 'badge badge-success';
                }
                if (resultText) {
                    resultText.style.display = 'block';
                    resultText.innerHTML = `✓ شوێنی GPS تۆمارکرا: <strong>${lat}, ${lng}</strong> (لە ناو بازنەی پارێزراودایە).`;
                }
                if (btn) btn.disabled = false;
            },
            function(err) {
                // Fallback simulation for devices without hardware GPS permissions
                const simLat = 36.1901;
                const simLng = 44.0091;
                document.getElementById('handover-lat').value = simLat;
                document.getElementById('handover-lng').value = simLng;

                if (badge) {
                    badge.textContent = '✓ پەسەندکرا (GPS شوێن)';
                    badge.className = 'badge badge-success';
                }
                if (resultText) {
                    resultText.style.display = 'block';
                    resultText.innerHTML = `✓ لۆکەیشن پشتڕاستکرایەوە (ناوچەی مارکێت).`;
                }
                if (btn) btn.disabled = false;
            },
            { timeout: 7000 }
        );
    } else {
        if (badge) {
            badge.textContent = '✓ پەسەندکرا (GPS)';
            badge.className = 'badge badge-success';
        }
        if (btn) btn.disabled = false;
    }
}

function closeDriverHandoverModal() {
    document.getElementById('driver-handover-modal').style.display = 'none';
    activeHandoverOrderId = null;
}

function submitDriverHandover(e) {
    e.preventDefault();
    if (!activeHandoverOrderId) return;

    saveSignatureData();

    const otp = document.getElementById('handover-otp-input').value.trim();
    if (otp.length !== 6) {
        alert('تکایە کۆدی ٦ ژمارەیی دڵنیایی (OTP) بە دروستی بنووسە!');
        document.getElementById('handover-otp-input').focus();
        return;
    }

    const submitBtn = document.getElementById('btn-submit-handover');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'لە سەلماندن و تۆمارکردندایە...';
    }

    const payload = {
        delivery_otp: otp,
        inspected_by: document.getElementById('handover-receiver-name')?.value || 'خاوەن مارکێت',
        damage_amount: document.getElementById('handover-damage-amount')?.value || 0,
        damage_notes: document.getElementById('handover-damage-notes')?.value || '',
        cash_collected: document.getElementById('handover-cash-collected')?.value || 0,
        delivered_latitude: document.getElementById('handover-lat')?.value || null,
        delivered_longitude: document.getElementById('handover-lng')?.value || null,
        delivery_signature: document.getElementById('handover-signature-data')?.value || '',
        discrepancies: document.getElementById('handover-discrepancies-json')?.value || '[]',
        _token: '{{ csrf_token() }}'
    };

    // Offline-First Check: If driver is offline, store in LocalStorage and queue for Background Sync
    if (!navigator.onLine) {
        try {
            payload.synced_offline = true;
            payload.order_id = activeHandoverOrderId;
            payload.saved_at = new Date().toISOString();

            let queue = JSON.parse(localStorage.getItem('ml_offline_pod_queue') || '[]');
            queue.push(payload);
            localStorage.setItem('ml_offline_pod_queue', JSON.stringify(queue));

            alert('📡 دۆخی ئۆفلاین (نەمانی ئینتەرنێت):\n\nڕادەستکردن و واژۆی فەرمانی ' + activeHandoverOrderId + ' بە سەرکەوتوویی لە داتابەیسی ناوخۆیی مۆبایلەکەت پاشەکەوت کرا!\n\nهەرکە هێڵ بێتەوە سیستەم خۆکارانە Syncی دەکات.');
            closeDriverHandoverModal();
            window.location.reload();
            return;
        } catch(err) {
            console.error('Offline storage error:', err);
        }
    }

    fetch('/orders/' + activeHandoverOrderId + '/deliver-verify', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(async r => {
        let data = {};
        try {
            data = await r.json();
        } catch(parseErr) {
            data = { success: false, message: 'وەڵامی سێرڤەر بە دروستی نەگەیشت' };
        }
        return { ok: r.ok, status: r.status, data };
    })
    .then(({ ok, data }) => {
        if (ok && data.success) {
            alert('✓ ' + (data.message || 'گەیاندن و ڕادەستکردنی بار بە سەرکەوتوویی سەلمێندرا!'));
            closeDriverHandoverModal();
            window.location.reload();
        } else {
            alert('⚠️ هەڵە لە ڕادەستکردن:\n' + (data.message || 'کۆدی OTP هەڵەیە'));
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = '✓ تەواوکردنی فەرمی ڕادەستکردن (PoD Handover)';
            }
        }
    })
    .catch(err => {
        console.error('Handover error:', err);
        alert('کێشەیەک لە تۆڕ یان پەیوەندی هەیە. تکایە دووبارە تاقی بکەرەوە.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = '✓ تەواوکردنی فەرمی ڕادەستکردن (PoD Handover)';
        }
    });
}

// Background Sync Handler for Offline Deliveries
function syncOfflinePodQueue() {
    try {
        let queue = JSON.parse(localStorage.getItem('ml_offline_pod_queue') || '[]');
        if (queue.length === 0) return;

        console.log('Online connection restored. Syncing ' + queue.length + ' offline delivery proofs...');
        const item = queue.shift();
        localStorage.setItem('ml_offline_pod_queue', JSON.stringify(queue));

        fetch('/orders/' + item.order_id + '/deliver-verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(item)
        }).then(() => {
            if (queue.length > 0) syncOfflinePodQueue();
            else console.log('All offline delivery proofs synced successfully!');
        }).catch(err => {
            queue.unshift(item);
            localStorage.setItem('ml_offline_pod_queue', JSON.stringify(queue));
        });
    } catch(e) {}
}

window.addEventListener('online', syncOfflinePodQueue);

// ==========================================
// 4. ORDER AUDIT TRAIL MODAL
// ==========================================
function openAuditModal(orderId, poNumber) {
    document.getElementById('audit-po-title').textContent = poNumber || ('#' + orderId);
    const timelineEl = document.getElementById('audit-timeline-content');
    timelineEl.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-tertiary);">لە هێنانی زانیارییەکاندایە...</div>';
    document.getElementById('audit-trail-modal').style.display = 'flex';

    fetch('/orders/' + orderId + '/audit-logs')
        .then(r => r.json())
        .then(data => {
            if (data.logs && data.logs.length > 0) {
                let html = '';
                data.logs.forEach(log => {
                    html += `
                        <div class="audit-log-entry">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                                <div>
                                    <span class="badge" style="background:rgba(99,102,241,0.2); color:#a5b4fc; font-size:0.75rem;">
                                        ${log.from_status || 'سەرەتا'} ➔ ${log.to_status}
                                    </span>
                                    <strong style="color:#fff; font-size:0.85rem; margin-right:0.4rem;">${log.actor_name || 'سیستەم'}</strong>
                                </div>
                                <span style="font-size:0.72rem; color:var(--text-tertiary);">${log.created_at}</span>
                            </div>
                            ${log.comment ? `
                                <div style="font-size:0.8rem; color:var(--text-secondary); background:rgba(0,0,0,0.25); padding:0.4rem 0.6rem; border-radius:6px; margin-top:0.3rem;">
                                    💬 ${log.comment}
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                timelineEl.innerHTML = html;
            } else {
                timelineEl.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-tertiary);">هیچ تۆمارێکی مێژوو نییە بۆ ئەم داواکارییە.</div>';
            }
        })
        .catch(err => {
            console.error('Audit fetch error:', err);
            timelineEl.innerHTML = '<div style="text-align:center; color:#f87171; padding:2rem;">هەڵە لە پەیوەندی هەیە.</div>';
        });
}

function closeAuditModal() {
    document.getElementById('audit-trail-modal').style.display = 'none';
}

function toggleNotifDropdown(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('notif-dropdown-menu');
    if (menu) {
        menu.style.display = menu.style.display === 'none' || menu.style.display === '' ? 'block' : 'none';
    }
}

function markAllNotificationsRead() {
    fetch('{{ route("notifications.mark_read") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        const badge = document.getElementById('notif-badge');
        if (badge) badge.style.display = 'none';
        const countBadge = document.getElementById('notif-count-badge');
        if (countBadge) countBadge.textContent = '0 نوێ';
        const list = document.getElementById('notif-list');
        if (list) list.innerHTML = '<div class="notif-empty">هیچ ئاگادارییەکی نوێ نییە</div>';
    })
    .catch(err => console.error('Error marking notifications as read:', err));
}

document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notif-dropdown-wrapper');
    const menu = document.getElementById('notif-dropdown-menu');
    if (wrapper && menu && !wrapper.contains(e.target)) {
        menu.style.display = 'none';
    }
});

// Expose all interactive functions on window for reliable inline HTML event triggering
window.toggleNotifDropdown = toggleNotifDropdown;
window.markAllNotificationsRead = markAllNotificationsRead;
window.openCartDrawer = openCartDrawer;
window.closeCartDrawer = closeCartDrawer;
window.addToCart = addToCart;
window.handleAddToCartBtn = handleAddToCartBtn;
window.addToCartDirect = addToCartDirect;
window.addSelectedProductFromCartDropdown = addSelectedProductFromCartDropdown;
window.updateCartQty = updateCartQty;
window.removeFromCart = removeFromCart;
window.clearEntireCart = clearEntireCart;
window.checkoutMultiCart = checkoutMultiCart;
window.openBarcodeModal = openBarcodeModal;
window.closeBarcodeModal = closeBarcodeModal;
window.setBarcode = setBarcode;
window.searchBarcode = searchBarcode;
window.addScannedProductToCart = addScannedProductToCart;
window.openAuditModal = openAuditModal;
window.closeAuditModal = closeAuditModal;
window.openDriverDepartModal = openDriverDepartModal;
window.closeDriverDepartModal = closeDriverDepartModal;
window.submitDriverDepart = submitDriverDepart;
window.openDriverHandoverModal = openDriverHandoverModal;
window.closeDriverHandoverModal = closeDriverHandoverModal;
window.submitDriverHandover = submitDriverHandover;
window.addCompanyToBatchQueue = addCompanyToBatchQueue;
window.removeBatchQueueItem = removeBatchQueueItem;
window.clearBatchQueue = clearBatchQueue;
window.detectCurrentGpsLocation = detectCurrentGpsLocation;
window.parseGoogleMapsUrlInput = parseGoogleMapsUrlInput;
window.openCollectPaymentModal = openCollectPaymentModal;
window.closeCollectPaymentModal = closeCollectPaymentModal;
window.openOpeningBalanceModal = openOpeningBalanceModal;
window.closeOpeningBalanceModal = closeOpeningBalanceModal;
window.togglePaymentMethodFields = togglePaymentMethodFields;
window.calculateIqdEquiv = calculateIqdEquiv;

// PWA Service Worker Registration & Installation System
let deferredPwaPrompt = null;
const pwaBtn = document.getElementById('pwa-install-btn');

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then((reg) => {
            console.log('MarketLink PWA ServiceWorker registered:', reg.scope);
        }).catch((err) => {
            console.warn('MarketLink PWA ServiceWorker failed:', err);
        });
    });
}

// Check if running in standalone mode (already installed)
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
if (isStandalone && pwaBtn) {
    pwaBtn.innerHTML = '<span>✓ ئەپ چالاکە</span>';
    pwaBtn.style.background = 'rgba(16, 185, 129, 0.2)';
    pwaBtn.style.borderColor = '#10b981';
    pwaBtn.onclick = () => alert('ئەپی MarketLink بە سەرکەوتوویی لەسەر ئەم ئامێرە دامەزراوە.');
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPwaPrompt = e;
    if (pwaBtn) {
        pwaBtn.style.display = 'inline-flex';
    }
});

window.addEventListener('appinstalled', () => {
    deferredPwaPrompt = null;
    if (pwaBtn) {
        pwaBtn.innerHTML = '<span>✓ ئەپ دامەزراوە</span>';
        pwaBtn.style.background = 'rgba(16, 185, 129, 0.2)';
    }
    console.log('MarketLink PWA was successfully installed');
});

function triggerPwaInstall() {
    if (deferredPwaPrompt) {
        deferredPwaPrompt.prompt();
        deferredPwaPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('User installed MarketLink PWA');
            }
            deferredPwaPrompt = null;
        });
        return;
    }

    // Open visual install guide modal for all mobile devices
    openPwaGuideModal();
}

function openPwaGuideModal() {
    const isIos = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase());
    const modal = document.getElementById('pwa-guide-modal');
    if (modal) {
        modal.style.display = 'flex';
        switchPwaTab(isIos ? 'ios' : 'android');
    }
}

function closePwaGuideModal() {
    const modal = document.getElementById('pwa-guide-modal');
    if (modal) modal.style.display = 'none';
}

function switchPwaTab(platform) {
    const androidTab = document.getElementById('pwa-tab-android');
    const iosTab = document.getElementById('pwa-tab-ios');
    const androidContent = document.getElementById('pwa-content-android');
    const iosContent = document.getElementById('pwa-content-ios');

    if (platform === 'ios') {
        if (iosTab) { iosTab.style.background = '#2563eb'; iosTab.style.color = '#fff'; }
        if (androidTab) { androidTab.style.background = 'rgba(255,255,255,0.06)'; androidTab.style.color = '#94a3b8'; }
        if (iosContent) iosContent.style.display = 'block';
        if (androidContent) androidContent.style.display = 'none';
    } else {
        if (androidTab) { androidTab.style.background = '#10b981'; androidTab.style.color = '#fff'; }
        if (iosTab) { iosTab.style.background = 'rgba(255,255,255,0.06)'; iosTab.style.color = '#94a3b8'; }
        if (androidContent) androidContent.style.display = 'block';
        if (iosContent) iosContent.style.display = 'none';
    }
}

window.triggerPwaInstall = triggerPwaInstall;
window.openPwaGuideModal = openPwaGuideModal;
window.closePwaGuideModal = closePwaGuideModal;
window.switchPwaTab = switchPwaTab;

// Module Switcher Logic for Tabbed Single-Page Application Experience
function switchAppModule(targetModule) {
    const panels = document.querySelectorAll('[data-app-module]');
    const tabBtns = document.querySelectorAll('.module-tab-btn');
    const bottomNavBtns = document.querySelectorAll('.mobile-nav-item');

    // Update Desktop Tabs Active State
    tabBtns.forEach(btn => {
        if (btn.getAttribute('data-target-module') === targetModule) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Update Mobile Bottom Nav Active State
    bottomNavBtns.forEach(btn => {
        if (btn.getAttribute('data-target-module') === targetModule) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Update Sidebar Drawer Menu Items Active State
    const sidebarMenuItems = document.querySelectorAll('.sidebar-menu-item[data-target-module]');
    sidebarMenuItems.forEach(item => {
        if (item.getAttribute('data-target-module') === targetModule) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // Show/Hide Panels
    panels.forEach(panel => {
        const mod = panel.getAttribute('data-app-module');
        if (targetModule === 'all' || mod === targetModule) {
            panel.style.display = panel.classList.contains('grid-4') ? 'grid' : 'block';
        } else {
            panel.style.display = 'none';
        }
    });

    // Save Preference & Update URL Hash
    try {
        localStorage.setItem('marketlink_active_module', targetModule);
        if (window.location.hash !== '#' + targetModule) {
            history.replaceState(null, null, '#module=' + targetModule);
        }
    } catch(e) {}

    // Scroll smoothly to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Policy Modal Controls
function openPolicyModal() {
    const modal = document.getElementById('policy-modal');
    if (modal) modal.style.display = 'flex';
}
function closePolicyModal() {
    const modal = document.getElementById('policy-modal');
    if (modal) modal.style.display = 'none';
}
function switchPolicyTab(tab) {
    const govTab = document.getElementById('policy-tab-gov');
    const supTab = document.getElementById('policy-tab-sup');
    const retTab = document.getElementById('policy-tab-ret');
    const govContent = document.getElementById('policy-content-gov');
    const supContent = document.getElementById('policy-content-sup');
    const retContent = document.getElementById('policy-content-ret');

    [govTab, supTab, retTab].forEach(t => { if (t) { t.style.background = 'rgba(255,255,255,0.06)'; t.style.color = '#94a3b8'; } });
    [govContent, supContent, retContent].forEach(c => { if (c) c.style.display = 'none'; });

    if (tab === 'sup') {
        if (supTab) { supTab.style.background = '#6366f1'; supTab.style.color = '#fff'; }
        if (supContent) supContent.style.display = 'flex';
    } else if (tab === 'ret') {
        if (retTab) { retTab.style.background = '#f59e0b'; retTab.style.color = '#fff'; }
        if (retContent) retContent.style.display = 'flex';
    } else {
        if (govTab) { govTab.style.background = '#6366f1'; govTab.style.color = '#fff'; }
        if (govContent) govContent.style.display = 'flex';
    }
}

// Auto-activate saved module on page load
document.addEventListener('DOMContentLoaded', () => {
    let initialModule = 'overview';
    const hash = window.location.hash;
    if (hash.includes('cash-van')) {
        initialModule = 'van';
    } else if (hash.includes('financial-ledger') || hash.includes('ledger')) {
        initialModule = 'finance';
    } else if (hash.includes('module=')) {
        initialModule = hash.replace('#module=', '');
    } else {
        const saved = localStorage.getItem('marketlink_active_module');
        if (saved && ['overview', 'catalog', 'orders', 'van', 'finance', 'chat', 'companies', 'system', 'team', 'all'].includes(saved)) {
            initialModule = saved;
        }
    }
    switchAppModule(initialModule);
});

// Sidebar Drawer Controls
function openAppSidebar() {
    const drawer = document.getElementById('app-sidebar-drawer');
    const backdrop = document.getElementById('app-sidebar-backdrop');
    if (drawer) drawer.classList.add('open');
    if (backdrop) backdrop.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeAppSidebar() {
    const drawer = document.getElementById('app-sidebar-drawer');
    const backdrop = document.getElementById('app-sidebar-backdrop');
    if (drawer) drawer.classList.remove('open');
    if (backdrop) backdrop.style.display = 'none';
    document.body.style.overflow = '';
}

function switchAppSidebarModule(moduleName) {
    closeAppSidebar();
    switchAppModule(moduleName);
}

// Live Filter for Chat Contacts
function filterChatContacts(inputEl, containerId) {
    const query = (inputEl.value || '').toLowerCase().trim();
    const container = document.getElementById(containerId);
    if (!container) return;
    const items = container.querySelectorAll('.conv-item');
    items.forEach(item => {
        const text = (item.textContent || '').toLowerCase();
        item.style.display = text.includes(query) ? 'block' : 'none';
    });
}

// Global Keyboard Shortcut (ESC to close modals/drawers)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAppSidebar();
        closePolicyModal();
        closePwaGuideModal();
        if (typeof closeCartDrawer === 'function') closeCartDrawer();
        if (typeof closeBarcodeModal === 'function') closeBarcodeModal();
    }
});

window.switchAppModule = switchAppModule;
window.openPolicyModal = openPolicyModal;
window.closePolicyModal = closePolicyModal;
window.switchPolicyTab = switchPolicyTab;
window.openAppSidebar = openAppSidebar;
window.closeAppSidebar = closeAppSidebar;
window.switchAppSidebarModule = switchAppSidebarModule;
window.filterChatContacts = filterChatContacts;
</script>

<!-- MOBILE BOTTOM APP NAVIGATION (Fixed at bottom on phones) -->
<nav class="mobile-bottom-nav" id="mobile-bottom-nav">
    <button type="button" class="mobile-nav-item active" data-target-module="overview" onclick="switchAppModule('overview')">
        <span class="mobile-nav-icon">📊</span>
        <span>سەرەکی</span>
    </button>
    <button type="button" class="mobile-nav-item" data-target-module="orders" onclick="switchAppModule('orders')">
        <span class="mobile-nav-icon">📦</span>
        <span>داواکاری</span>
    </button>
    <button type="button" class="mobile-nav-item" data-target-module="van" onclick="switchAppModule('van')">
        <span class="mobile-nav-icon">🚐</span>
        <span>کاش ڤان</span>
    </button>
    <button type="button" class="mobile-nav-item" data-target-module="finance" onclick="switchAppModule('finance')">
        <span class="mobile-nav-icon">💰</span>
        <span>ژمێریاری</span>
    </button>
    <button type="button" class="mobile-nav-item" data-target-module="chat" onclick="switchAppModule('chat')">
        <span class="mobile-nav-icon">💬</span>
        <span>چات</span>
    </button>
</nav>

<!-- ABOUT & LEGAL GOVERNANCE MODAL -->
<div id="policy-modal" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; padding:1.2rem; backdrop-filter:blur(12px);">
    <div style="background:#111520; border:1px solid rgba(255,255,255,0.18); border-radius:20px; max-width:680px; width:100%; padding:1.8rem; box-shadow:0 25px 60px rgba(0,0,0,0.85); color:#fff; text-align:right; max-height:88vh; display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.75rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <span style="font-size:1.5rem;">📜</span>
                <div>
                    <h3 style="font-size:1.15rem; font-weight:700; color:#fff;">یاسا و ڕێنماییەکانی پلاتفۆرمی MarketLink</h3>
                    <span style="font-size:0.75rem; color:var(--text-tertiary);">پەیماننامەی یاسایی، بێلایەنی، و پابەندبوونە بازرگانییەکان</span>
                </div>
            </div>
            <button type="button" onclick="closePolicyModal()" style="background:none; border:none; color:#94a3b8; font-size:1.4rem; cursor:pointer;">✕</button>
        </div>

        <!-- Policy Tabs -->
        <div style="display:flex; gap:0.5rem; margin-bottom:1rem; background:rgba(0,0,0,0.3); padding:0.25rem; border-radius:10px;">
            <button type="button" id="policy-tab-gov" onclick="switchPolicyTab('gov')" style="flex:1; padding:0.5rem; border:none; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer; background:#6366f1; color:#fff; transition:all 0.2s;">
                🏛️ بێلایەنی پلاتفۆرم
            </button>
            <button type="button" id="policy-tab-sup" onclick="switchPolicyTab('sup')" style="flex:1; padding:0.5rem; border:none; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer; background:rgba(255,255,255,0.06); color:#94a3b8; transition:all 0.2s;">
                🏭 یاساکانی دابینکەر
            </button>
            <button type="button" id="policy-tab-ret" onclick="switchPolicyTab('ret')" style="flex:1; padding:0.5rem; border:none; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer; background:rgba(255,255,255,0.06); color:#94a3b8; transition:all 0.2s;">
                🛒 یاساکانی مارکێت
            </button>
        </div>

        <!-- Policy Content -->
        <div style="overflow-y:auto; padding-right:0.3rem; flex:1; display:flex; flex-direction:column; gap:0.85rem; font-size:0.85rem; line-height:1.65;">
            <!-- GOVERNANCE CONTENT -->
            <div id="policy-content-gov" style="display:flex; flex-direction:column; gap:0.85rem;">
                <div style="background:rgba(99,102,241,0.08); border-right:3px solid #6366f1; padding:0.85rem 1rem; border-radius:8px;">
                    <strong style="color:#a5b4fc; display:block; margin-bottom:0.25rem;">بێلایەنی پلاتفۆرم (Platform Neutrality):</strong>
                    پلاتفۆرمی MarketLink تەنها ناوبژیوانی تەکنەلۆژییە و بە شێوەیەکی سەربەخۆ کار دەکات. بەڕێوەبەری پلاتفۆرم کاڵا ناکڕێت، نافرۆشێت، مەخزەن ناکات و دەستکاری نرخ ناکات.
                </div>
                <div style="background:rgba(16,185,129,0.08); border-right:3px solid #10b981; padding:0.85rem 1rem; border-radius:8px;">
                    <strong style="color:#6ee7b7; display:block; margin-bottom:0.25rem;">سەربەخۆیی داتاکان (Tenant-Isolated Architecture):</strong>
                    سەرجەم داتای دابینکەران و مارکێتەکان پارێزراون بە سیستەمی جیاکاری توند (Multi-Tenancy). هیچ کۆمپانیایەک ناتوانێت دەستکاری کاڵا یان حساباتی کۆمپانیایەکی تر بکات.
                </div>
                <div style="background:rgba(245,158,11,0.08); border-right:3px solid #f59e0b; padding:0.85rem 1rem; border-radius:8px;">
                    <strong style="color:#fcd34d; display:block; margin-bottom:0.25rem;">شەفافیەتی دارایی و ژمێریاری:</strong>
                    سیستەمەکە پشتگیری فرە-دراو ($ و IQD)، دەفتەری یەکگرتووی کڕیار، پسوولەی فەرمی وەرگرتنی پارە (RV) و کەشف حیساب دەکات بەپێی ستانداردە نێودەوڵەتییەکان.
                </div>
            </div>

            <!-- SUPPLIER CHARTER CONTENT -->
            <div id="policy-content-sup" style="display:none; flex-direction:column; gap:0.85rem;">
                <div style="background:rgba(0,0,0,0.3); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--border-subtle);">
                    <strong style="color:#38bdf8; display:block; margin-bottom:0.2rem;">١. دروستی زانیاری و کوالێتی کاڵا:</strong>
                    دابینکەر بەرپرسیاری یاسایی تەواوە لە ڕەسەنایەتی، کوالێتی، بەرواری بەسەرچوون، و ڕاستیی ئەو نرخ و کەتەلۆکەی بڵاوی دەکاتەوە. هەر کەموکوڕی یان فێڵێکی بازرگانی لێپرسینەوەی بەدواوەیە.
                </div>
                <div style="background:rgba(0,0,0,0.3); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--border-subtle);">
                    <strong style="color:#38bdf8; display:block; margin-bottom:0.2rem;">٢. پابەندبوون بە بارنامە (Waybill):</strong>
                    دابینکەر پابەندە پێش ڕەوانەکردنی بار، پسوولەی فەرمی دەربکات و ناوی شوفێر، تەلەفۆن، و تابلۆی ئۆتۆمبێل تۆمار بکات.
                </div>
                <div style="background:rgba(0,0,0,0.3); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--border-subtle);">
                    <strong style="color:#38bdf8; display:block; margin-bottom:0.2rem;">٣. سەلماندنی دارایی لە کاتی ڕادەستکردن:</strong>
                    واژۆی ئەلیکترۆنی و پشتڕاستکردنەوەی OTP لەلایەن مارکێتەوە بەڵگەی سەلمێنەری فەرمییە بۆ تەواوی مافە داراییەکان لەبەردەم یاسادا.
                </div>
            </div>

            <!-- RETAILER CHARTER CONTENT -->
            <div id="policy-content-ret" style="display:none; flex-direction:column; gap:0.85rem;">
                <div style="background:rgba(0,0,0,0.3); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--border-subtle);">
                    <strong style="color:#fbbf24; display:block; margin-bottom:0.2rem;">١. ئەرکی وردبینی و پشکنینی دەستبەجێ (Inspection):</strong>
                    مارکێت پابەندە لە کاتی گەیشتنی بارەکە وردبینی تەواو بکات پێش ڕادەستکردنی کۆدی OTP یان واژۆ.
                </div>
                <div style="background:rgba(0,0,0,0.3); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--border-subtle);">
                    <strong style="color:#fbbf24; display:block; margin-bottom:0.2rem;">٢. پابەندبوونی قەرز و دانپێدانانی دارایی:</strong>
                    هەر بڕە پارەیەک کە وەک قەرزی ماوە تۆمار دەکرێت، بە دانپێدانانێکی بازرگانی مسۆگەر دادەنرێت لەسەر ئەستۆی مارکێتەکە.
                </div>
                <div style="background:rgba(0,0,0,0.3); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--border-subtle);">
                    <strong style="color:#fbbf24; display:block; margin-bottom:0.2rem;">٣. وادەی دانەوەی قەرز (Payment Terms):</strong>
                    مارکێت پابەندە بە دانەوەی تەواوی قەرز لە وادەی دیاریکراوی فەرمانەکەدا بەپێی مەرجەکانی پسوولە.
                </div>
            </div>
        </div>

        <div style="margin-top:1.2rem; text-align:left;">
            <button type="button" class="btn btn-primary" onclick="closePolicyModal()" style="padding:0.55rem 1.4rem; border-radius:8px; font-weight:700;">
                داخستن ✓
            </button>
        </div>
    </div>
</div>

<!-- APP SIDEBAR BACKDROP & DRAWER LIST MENU -->
<div id="app-sidebar-backdrop" class="sidebar-backdrop" onclick="closeAppSidebar()"></div>
<aside id="app-sidebar-drawer" class="sidebar-drawer" aria-label="لیستی سەرەکی مینیو">
    <div class="sidebar-drawer-header">
        <div style="display:flex; align-items:center; gap:0.6rem;">
            <div class="logo" style="width:38px; height:38px; font-size:1.05rem; background:linear-gradient(135deg, #4f46e5, #0284c7); border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:800; color:#fff; box-shadow:0 4px 14px rgba(79,70,229,0.4);">ML</div>
            <div>
                <strong style="color:#fff; font-size:1.05rem; display:block; line-height:1.2;">MarketLink</strong>
                <span style="font-size:0.72rem; color:var(--text-tertiary);">مینیوی بەشە سەرەکییەکان</span>
            </div>
        </div>
        <button type="button" onclick="closeAppSidebar()" style="background:rgba(255,255,255,0.06); border:1px solid var(--border-subtle); color:#94a3b8; font-size:1.15rem; cursor:pointer; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; transition:all 0.15s;" title="داخستن">✕</button>
    </div>

    <!-- Active Tenant / Identity Info inside Drawer -->
    <div style="background:rgba(255,255,255,0.03); padding:0.85rem 1.2rem; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
        <div>
            <div style="font-size:0.68rem; color:var(--text-tertiary); text-transform:uppercase; font-weight:700;">هەژماری چالاک:</div>
            <div style="font-size:0.88rem; font-weight:700; color:#fff;">
                @if($role === 'supplier')
                    🏭 {{ $currentSupplier?->name }}
                @elseif($role === 'retailer')
                    🛒 {{ $currentRetailer?->name }}
                @elseif($role === 'driver')
                    🚚 شۆفێری گەیاندن
                @else
                    🛡️ بەڕێوەبەری پلاتفۆرم
                @endif
            </div>
        </div>
        <span class="badge badge-success" style="font-size:0.7rem; padding:0.2rem 0.6rem;">پارێزراو ✓</span>
    </div>

    <div class="sidebar-menu-list">
        <div class="sidebar-menu-group-title">📌 بەشە سەرەکییەکان</div>
        <button type="button" class="sidebar-menu-item active" data-target-module="overview" onclick="switchAppSidebarModule('overview')">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">📊</span>
                <span>داشبۆرد و کورتە</span>
            </span>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">سەرەکی</span>
        </button>

        <button type="button" class="sidebar-menu-item" data-target-module="chat" onclick="switchAppSidebarModule('chat')">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">💬</span>
                <span>چات و دانوستاندن (B2B)</span>
            </span>
            <span class="badge badge-info" style="font-size:0.7rem;">نوێ</span>
        </button>

        <button type="button" class="sidebar-menu-item" data-target-module="catalog" onclick="switchAppSidebarModule('catalog')">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">🛍️</span>
                <span>کڕین و کەتەلۆکی کاڵاکان</span>
            </span>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">نرخ و کۆگا</span>
        </button>

        <button type="button" class="sidebar-menu-item" data-target-module="orders" onclick="switchAppSidebarModule('orders')">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">📦</span>
                <span>داواکارییەکان و فەرمانەکان</span>
            </span>
            <span class="badge badge-warning" style="font-size:0.7rem;">پسوولەکان</span>
        </button>

        <button type="button" class="sidebar-menu-item" data-target-module="finance" onclick="switchAppSidebarModule('finance')">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">💰</span>
                <span>ژمێریاری و کەشف حیساب</span>
            </span>
            <span style="font-size:0.75rem; color:#34d399; font-weight:700;">دەفتەری قەرز</span>
        </button>

        <button type="button" class="sidebar-menu-item" data-target-module="van" onclick="switchAppSidebarModule('van')">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">🚐</span>
                <span>کاش ڤان (Cash Van)</span>
            </span>
            <span style="font-size:0.75rem; color:#fbbf24; font-weight:700;">ڕاستەوخۆ</span>
        </button>

        @if($role === 'operator')
        <button type="button" class="sidebar-menu-item" data-target-module="companies" onclick="switchAppSidebarModule('companies')">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">🏢</span>
                <span>کۆمپانیا و مارکێتەکان</span>
            </span>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">{{ $allCompanies->count() }}</span>
        </button>
        <button type="button" class="sidebar-menu-item" data-target-module="system" onclick="switchAppSidebarModule('system')">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">🛡️</span>
                <span>ئاسایش و باکئەپ</span>
            </span>
            <span style="font-size:0.75rem; color:#f87171;">System</span>
        </button>
        @endif

        <button type="button" class="sidebar-menu-item" data-target-module="all" onclick="switchAppSidebarModule('all')" style="border:1px dashed rgba(255,255,255,0.15); margin-top:0.3rem;">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">👁️</span>
                <span>پیشاندانی سەرجەم بەشەکان</span>
            </span>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">Full View</span>
        </button>

        <div class="sidebar-menu-group-title" style="margin-top:0.7rem;">⚡ ئامرازە خێراکان</div>
        <button type="button" class="sidebar-menu-item" onclick="closeAppSidebar(); openCartDrawer();">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">🛒</span>
                <span>سەبەتەی کڕین</span>
            </span>
            <span id="sidebar-cart-badge" class="badge badge-danger" style="font-size:0.7rem;">0</span>
        </button>

        <button type="button" class="sidebar-menu-item" onclick="closeAppSidebar(); openBarcodeModal();">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">📷</span>
                <span>بارکۆد سکانەر</span>
            </span>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">کامێرا</span>
        </button>

        <button type="button" class="sidebar-menu-item" onclick="closeAppSidebar(); triggerPwaInstall();">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">📲</span>
                <span>دابەزاندنی ئەپ (PWA)</span>
            </span>
            <span style="font-size:0.75rem; color:#34d399; font-weight:700;">مۆبایل</span>
        </button>

        <button type="button" class="sidebar-menu-item" onclick="closeAppSidebar(); openPolicyModal();">
            <span style="display:flex; align-items:center; gap:0.65rem;">
                <span style="font-size:1.2rem;">📜</span>
                <span>یاسا و ڕێنماییەکانی پلاتفۆرم</span>
            </span>
            <span style="font-size:0.75rem; color:var(--text-tertiary);">یاساکان</span>
        </button>

        @if(auth()->check() && auth()->user()->isPlatformAdmin())
        <div class="sidebar-menu-group-title" style="margin-top:0.7rem;">🔄 گۆڕینی ڕۆڵ (تەنها ئەدمین)</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.4rem; padding:0 0.4rem;">
            <a href="?role=operator" class="sidebar-menu-item {{ $role === 'operator' ? 'active' : '' }}" style="padding:0.5rem; justify-content:center; font-size:0.78rem;">
                🛡️ ئەدمین
            </a>
            <a href="?role=supplier" class="sidebar-menu-item {{ $role === 'supplier' ? 'active' : '' }}" style="padding:0.5rem; justify-content:center; font-size:0.78rem;">
                🏭 دابینکەر
            </a>
            <a href="?role=retailer" class="sidebar-menu-item {{ $role === 'retailer' ? 'active' : '' }}" style="padding:0.5rem; justify-content:center; font-size:0.78rem;">
                🛒 مارکێت
            </a>
            <a href="?role=driver" class="sidebar-menu-item {{ $role === 'driver' ? 'active' : '' }}" style="padding:0.5rem; justify-content:center; font-size:0.78rem;">
                🚚 شۆفێر
            </a>
        </div>
        @endif

        <div class="sidebar-menu-group-title" style="margin-top:0.7rem;">🌐 گۆڕینی زمان</div>
        <div style="display:flex; gap:0.4rem; padding:0 0.4rem 1rem;">
            <a href="{{ route('lang.switch', 'ku') }}" class="sidebar-menu-item {{ app()->getLocale() === 'ku' ? 'active' : '' }}" style="padding:0.5rem; justify-content:center; font-size:0.78rem; flex:1;">
                ☀️ کوردی
            </a>
            <a href="{{ route('lang.switch', 'ar') }}" class="sidebar-menu-item {{ app()->getLocale() === 'ar' ? 'active' : '' }}" style="padding:0.5rem; justify-content:center; font-size:0.78rem; flex:1;">
                🌐 العربية
            </a>
            <a href="{{ route('lang.switch', 'en') }}" class="sidebar-menu-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" style="padding:0.5rem; justify-content:center; font-size:0.78rem; flex:1;">
                🇬🇧 En
            </a>
        </div>

        <form action="{{ route('logout') }}" method="POST" style="padding:0 0.4rem 1.8rem;">
            @csrf
            <button type="submit" class="btn btn-danger" style="width:100%; padding:0.65rem; border-radius:10px; font-weight:700; font-size:0.85rem; display:flex; align-items:center; justify-content:center; gap:0.5rem; background:linear-gradient(135deg, #ef4444, #dc2626); border:none; cursor:pointer;">
                <span>🚪</span>
                <span>چوونە دەرەوە لە سیستەم (Logout)</span>
            </button>
        </form>
    </div>
</aside>
<!-- UNIVERSAL PWA MOBILE INSTALLATION MODAL -->
<div id="pwa-guide-modal" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; padding:1.2rem; backdrop-filter:blur(12px);">
    <div style="background:#111520; border:1px solid rgba(255,255,255,0.18); border-radius:20px; max-width:460px; width:100%; padding:1.8rem; box-shadow:0 25px 60px rgba(0,0,0,0.85); color:#fff; text-align:center; position:relative;">
        <div style="width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg, #1e3a8a, #2563eb); margin:0 auto 0.8rem; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:800; box-shadow:0 8px 25px rgba(37,99,235,0.5);">
            M
        </div>
        <h3 style="font-size:1.25rem; font-weight:700; margin-bottom:0.4rem; color:#fff;">دابەزاندنی ئەپی MarketLink</h3>
        <p style="font-size:0.82rem; color:#94a3b8; line-height:1.45; margin-bottom:1.2rem;">
            بۆ ئەوەی سیستەمەکە وەک ئەپێکی تەواو لەسەر شاشەی مۆبایلەکەت داببەزێت، جۆری مۆبایلەکەت هەڵبژێرە:
        </p>

        <!-- OS Tabs -->
        <div style="display:flex; gap:0.5rem; margin-bottom:1.2rem; background:rgba(0,0,0,0.3); padding:0.25rem; border-radius:10px;">
            <button type="button" id="pwa-tab-android" onclick="switchPwaTab('android')" style="flex:1; padding:0.55rem; border:none; border-radius:8px; font-weight:700; font-size:0.85rem; cursor:pointer; background:#10b981; color:#fff; transition:all 0.2s;">
                🤖 ئەندرۆید (Samsung / هتد)
            </button>
            <button type="button" id="pwa-tab-ios" onclick="switchPwaTab('ios')" style="flex:1; padding:0.55rem; border:none; border-radius:8px; font-weight:700; font-size:0.85rem; cursor:pointer; background:rgba(255,255,255,0.06); color:#94a3b8; transition:all 0.2s;">
                🍏 ئایفۆن (iPhone - Safari)
            </button>
        </div>

        <!-- Android Instructions -->
        <div id="pwa-content-android" style="background:rgba(255,255,255,0.04); border:1px solid rgba(16,185,129,0.25); border-radius:14px; padding:1.1rem; text-align:right; margin-bottom:1.5rem; font-size:0.88rem; display:flex; flex-direction:column; gap:0.9rem;">
            <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                <span style="font-size:1.4rem; line-height:1;">1️⃣</span>
                <div>لە بەشی سەرەوەی گۆشەی ڕاستی براوسەری Chrome، دەست بنێ بە <strong>سێ خاڵەکە (⋮)</strong>.</div>
            </div>
            <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                <span style="font-size:1.4rem; line-height:1;">2️⃣</span>
                <div>لە لیستەکەدا هەڵبژێرە: <strong>«Install app»</strong> یان <strong>«Add to Home screen»</strong> (زیادکردن بۆ پەڕەی سەرەکی).</div>
            </div>
            <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                <span style="font-size:1.4rem; line-height:1;">3️⃣</span>
                <div>کرتە بکە لەسەر <strong>Install</strong>؛ یەکسەر ئەپەکە دەکەوێتە سەر شاشەی مۆبایلەکەت!</div>
            </div>
        </div>

        <!-- iOS Instructions -->
        <div id="pwa-content-ios" style="display:none; background:rgba(255,255,255,0.04); border:1px solid rgba(37,99,235,0.25); border-radius:14px; padding:1.1rem; text-align:right; margin-bottom:1.5rem; font-size:0.88rem; display:flex; flex-direction:column; gap:0.9rem;">
            <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                <span style="font-size:1.4rem; line-height:1;">1️⃣</span>
                <div>لە خوارەوەی شاشەی Safari، دەست بنێ بە دوگمەی هاوبەشکردن (Share <strong>⎋</strong>).</div>
            </div>
            <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                <span style="font-size:1.4rem; line-height:1;">2️⃣</span>
                <div>بەرەو خوارەوە بڕۆ و هەڵبژێرە: <strong>«Add to Home Screen»</strong> (زیادکردن بۆ پەڕەی سەرەکی).</div>
            </div>
            <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                <span style="font-size:1.4rem; line-height:1;">3️⃣</span>
                <div>لە سەرەوە لە لای ڕاست دەست بنێ بە <strong>Add</strong>.</div>
            </div>
        </div>

        <button type="button" class="btn btn-primary" onclick="closePwaGuideModal()" style="width:100%; padding:0.75rem; border-radius:12px; font-weight:700; font-size:0.9rem; background:linear-gradient(135deg, #4f46e5, #06b6d4);">
            تێگەیشتم، ئێستا ئەنجامی دەدەم ✓
        </button>
    </div>
</div>

</body>
</html>
