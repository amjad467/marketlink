<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('کەشف حیسابی فەرمی') }} — {{ $retailer->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --font-sans: 'Vazirmatn', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --primary: #4f46e5;
            --text-dark: #0f172a;
            --border: #e2e8f0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-sans);
            background: #f8fafc;
            color: var(--text-dark);
            padding: 30px 20px;
            direction: rtl;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            padding: 35px;
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .title h1 { font-size: 24px; font-weight: 800; color: #1e1b4b; }
        .title p { font-size: 13px; color: #64748b; margin-top: 4px; }
        .meta { text-align: left; direction: ltr; font-family: var(--font-mono); font-size: 13px; color: #475569; }

        .company-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .c-box {
            background: #f1f5f9;
            padding: 16px 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .c-box .role-tag { font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .c-box .c-name { font-size: 17px; font-weight: 800; color: #0f172a; margin: 4px 0; }
        .c-box .c-meta { font-size: 13px; color: #64748b; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }
        .stat-card .lbl { font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; }
        .stat-card .val { font-size: 24px; font-weight: 800; font-family: var(--font-mono); }
        .stat-debit .val { color: #dc2626; }
        .stat-credit .val { color: #16a34a; }
        .stat-net .val { color: #2563eb; }

        /* Debt Aging Analysis Card */
        .aging-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 25px;
        }
        .aging-title { font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
        .aging-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .aging-col {
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }
        .aging-col .tag { font-size: 11px; font-weight: 700; margin-bottom: 4px; }
        .aging-col .num { font-size: 16px; font-weight: 800; font-family: var(--font-mono); }
        .aging-green { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .aging-blue { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
        .aging-yellow { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .aging-red { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 13.5px; }
        th, td { padding: 12px 14px; border: 1px solid #e2e8f0; text-align: right; }
        th { background: #f1f5f9; font-weight: 700; color: #334155; }
        .col-mono { font-family: var(--font-mono); font-weight: 600; direction: ltr; }
        .row-total { background: #f8fafc; font-weight: 800; font-size: 14.5px; }

        .btn-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            font-family: inherit;
        }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }

        @media print {
            body { background: #fff; padding: 0; }
            .container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
            .btn-bar, .filter-form { display: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="title">
            <h1>📑 کەشف حیسابی فەرمی کڕیار (Statement of Account)</h1>
            <p>بەرواری دەرچوون: {{ now()->format('Y-m-d H:i') }} | سیستەمی یەکگرتووی دارایی و ژمێریاری MarketLink</p>
        </div>
        <div class="meta">
            <div><strong>Statement Ref:</strong> SOA-{{ $supplier->id }}-{{ $retailer->id }}</div>
            <div><strong>Currency:</strong> USD ($) & IQD</div>
        </div>
    </div>

    <div class="company-cards">
        <div class="c-box">
            <div class="role-tag">🏭 کۆمپانیای دابینکەر (فرۆشیار)</div>
            <div class="c-name">{{ $supplier->name }}</div>
            <div class="c-meta">{{ $supplier->city }} | مۆڵەت: {{ $supplier->trade_license_number ?? '-' }} | پەیوەندی: {{ $supplier->primary_phone }}</div>
        </div>
        <div class="c-box">
            <div class="role-tag">🛒 مارکێت / کڕیار (پارەدەر)</div>
            <div class="c-name">{{ $retailer->name }}</div>
            <div class="c-meta">{{ $retailer->city }} | کەتەگۆری: {{ $retailer->category ?? 'مارکێت' }} | ناونیشان: {{ $retailer->address_line ?? '-' }}</div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="stats-grid">
        <div class="stat-card stat-debit">
            <div class="lbl">کۆی پسوولە و فرۆشراو (مەدین / Debit)</div>
            <div class="val">${{ number_format($total_debit, 2) }}</div>
        </div>
        <div class="stat-card stat-credit">
            <div class="lbl">کۆی پارەی دراو و گەڕاوە (دائین / Credit)</div>
            <div class="val">${{ number_format($total_credit, 2) }}</div>
        </div>
        <div class="stat-card stat-net">
            <div class="lbl">ڕەسیدی ماوەی ئێستا (Net Balance Due)</div>
            <div class="val">${{ number_format($net_balance, 2) }}</div>
        </div>
    </div>

    <!-- Debt Aging Analysis (تەمەنی قەرز) -->
    <div class="aging-box">
        <div class="aging-title">
            <span>⏳ شیکاری تەمەنی قەرز (Debt Aging Breakdown)</span>
            <span style="font-size:12px; font-weight:normal; color:#64748b;">چاودێری قەرزە دواکەوتووەکان بەپێی بەروار</span>
        </div>
        <div class="aging-grid">
            <div class="aging-col aging-green">
                <div class="tag">0 - 30 ڕۆژ (سەلامەت)</div>
                <div class="num">${{ number_format($aging['current_30'], 2) }}</div>
            </div>
            <div class="aging-col aging-blue">
                <div class="tag">31 - 60 ڕۆژ (ئاسایی)</div>
                <div class="num">${{ number_format($aging['overdue_60'], 2) }}</div>
            </div>
            <div class="aging-col aging-yellow">
                <div class="tag">61 - 90 ڕۆژ (دواکەوتوو)</div>
                <div class="num">${{ number_format($aging['overdue_90'], 2) }}</div>
            </div>
            <div class="aging-col aging-red">
                <div class="tag">90+ ڕۆژ (مەترسیدار)</div>
                <div class="num">${{ number_format($aging['overdue_90_plus'], 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Statement Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 110px;">بەروار</th>
                <th style="width: 140px;">جۆری بەڵگەنامە</th>
                <th style="width: 150px;">ژمارەی وەسڵ</th>
                <th>ڕوونکردنەوە و تەفاسیل</th>
                <th style="width: 110px; color:#dc2626;">مەدین (Debit)</th>
                <th style="width: 110px; color:#16a34a;">دائین (Credit)</th>
                <th style="width: 120px;">ڕەسید (Balance)</th>
            </tr>
        </thead>
        <tbody>
            @if($opening_balance > 0)
            <tr style="background: #fdfdfd; font-weight:600;">
                <td colspan="4">ڕەسیدی پێش ئەم بەروارە (Opening Balance)</td>
                <td>-</td>
                <td>-</td>
                <td class="col-mono">${{ number_format($opening_balance, 2) }}</td>
            </tr>
            @endif

            @forelse($entries as $e)
            <tr>
                <td style="font-size:12.5px; color:#64748b;">{{ $e->entry_date->format('Y-m-d') }}</td>
                <td>
                    @if($e->entry_type === 'order_invoice')
                        <span style="color:#4f46e5; font-weight:700;">📦 فەرمانی کۆگا</span>
                    @elseif($e->entry_type === 'cash_van_sale')
                        <span style="color:#d97706; font-weight:700;">🚐 کاش ڤان</span>
                    @elseif($e->entry_type === 'receipt_voucher')
                        <span style="color:#16a34a; font-weight:700;">🧾 سند قبض</span>
                    @elseif($e->entry_type === 'sale_return')
                        <span style="color:#ea580c; font-weight:700;">↩️ گەڕاوەی کاڵا</span>
                    @elseif($e->entry_type === 'opening_balance')
                        <span style="color:#7c3aed; font-weight:700;">⚖️ قەرزی پێش سیستم</span>
                    @else
                        {{ $e->entry_type }}
                    @endif
                </td>
                <td class="col-mono">
                    @if($e->entry_type === 'receipt_voucher' && $e->reference_id)
                        <a href="{{ route('financial.voucher.print', $e->reference_id) }}" target="_blank" style="color:#4f46e5; text-decoration:underline;">
                            {{ $e->reference_number }}
                        </a>
                    @else
                        {{ $e->reference_number }}
                    @endif
                </td>
                <td>{{ $e->description }}</td>
                <td class="col-mono" style="color: {{ $e->debit_amount > 0 ? '#dc2626' : '#94a3b8' }};">
                    {{ $e->debit_amount > 0 ? '$' . number_format($e->debit_amount, 2) : '-' }}
                </td>
                <td class="col-mono" style="color: {{ $e->credit_amount > 0 ? '#16a34a' : '#94a3b8' }};">
                    {{ $e->credit_amount > 0 ? '$' . number_format($e->credit_amount, 2) : '-' }}
                </td>
                <td class="col-mono" style="font-weight:700; color: #0f172a;">
                    ${{ number_format($e->running_balance, 2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #94a3b8; padding: 25px;">
                    هیچ جووڵەیەکی دارایی تۆمارنەکراوە بۆ ئەم کڕیارە لەم ماوەیەدا.
                </td>
            </tr>
            @endforelse

            <tr class="row-total">
                <td colspan="4" style="text-align: right;">کۆی گشتی:</td>
                <td class="col-mono" style="color:#dc2626;">${{ number_format($total_debit, 2) }}</td>
                <td class="col-mono" style="color:#16a34a;">${{ number_format($total_credit, 2) }}</td>
                <td class="col-mono" style="color:#2563eb; font-weight:900;">${{ number_format($net_balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="btn-bar">
        <div>
            <button onclick="window.print()" class="btn btn-primary">🖨️ چاپکردنی کەشف حیساب (Print / PDF)</button>
            <a href="{{ route('reports.statement.export', $retailer->id) }}" class="btn btn-secondary">📥 داگرتنی فایلی Excel (CSV)</a>
        </div>
        <a href="{{ route('marketlink.index') }}" class="btn btn-secondary">← گەڕانەوە بۆ پلاتفۆرم</a>
    </div>
</div>

</body>
</html>
