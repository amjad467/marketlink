<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('پسوولەی وەرگرتنی پارە') }} — {{ $voucher->voucher_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --font-sans: 'Vazirmatn', system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --primary: #4f46e5;
            --text-dark: #0f172a;
            --border-color: #cbd5e1;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-sans);
            background: #f1f5f9;
            color: var(--text-dark);
            padding: 30px 15px;
            direction: rtl;
        }
        .voucher-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .company-title h1 { font-size: 24px; font-weight: 800; color: #1e1b4b; }
        .company-title p { font-size: 13px; color: #64748b; margin-top: 4px; }
        .voucher-meta { text-align: left; direction: ltr; }
        .voucher-badge {
            background: #e0e7ff;
            color: #3730a3;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 15px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .voucher-meta div { font-size: 13px; font-family: var(--font-mono); color: #475569; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #f8fafc;
            padding: 18px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }
        .info-item { display: flex; flex-direction: column; gap: 4px; }
        .info-label { font-size: 12px; font-weight: 600; color: #64748b; }
        .info-val { font-size: 15px; font-weight: 700; color: #0f172a; }

        .amount-highlight-box {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 2px solid #86efac;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        .amount-highlight-box .lbl { font-size: 13px; font-weight: 700; color: #166534; margin-bottom: 6px; }
        .amount-highlight-box .big-amount {
            font-size: 36px;
            font-weight: 900;
            font-family: var(--font-mono);
            color: #15803d;
            direction: ltr;
        }
        .amount-highlight-box .sub-iqd {
            font-size: 15px;
            font-weight: 700;
            color: #166534;
            margin-top: 6px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .details-table th, .details-table td {
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            text-align: right;
            font-size: 14px;
        }
        .details-table th { background: #f1f5f9; font-weight: 700; color: #334155; }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e1;
            text-align: center;
        }
        .sig-box { display: flex; flex-direction: column; align-items: center; }
        .sig-line { width: 180px; height: 1px; background: #94a3b8; margin: 40px 0 8px 0; }
        .sig-title { font-size: 13px; font-weight: 700; color: #475569; }

        .action-bar {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print { background: #4f46e5; color: #fff; }
        .btn-back { background: #e2e8f0; color: #334155; }

        @media print {
            body { background: #fff; padding: 0; }
            .voucher-container { border: none; box-shadow: none; padding: 10px; width: 100%; max-width: 100%; }
            .action-bar { display: none; }
        }
    </style>
</head>
<body>

<div class="voucher-container">
    <div class="header">
        <div class="company-title">
            <h1>{{ $voucher->supplier?->name ?? 'کۆمپانیای دابینکەر' }}</h1>
            <p>{{ $voucher->supplier?->city }} — ژمارەی مۆڵەت: {{ $voucher->supplier?->trade_license_number ?? 'فەرمی' }} | تەلەفۆن: {{ $voucher->supplier?->primary_phone }}</p>
        </div>
        <div class="voucher-meta">
            <span class="voucher-badge">RECEIPT VOUCHER / سند قبض</span>
            <div><strong>No:</strong> {{ $voucher->voucher_number }}</div>
            <div><strong>Date:</strong> {{ $voucher->receipt_date->format('Y-m-d') }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">وەرگیرا لە بەڕێز (پارەدەر / کڕیار):</span>
            <span class="info-val">🛒 {{ $voucher->retailer?->name ?? 'مارکێت' }} ({{ $voucher->retailer?->city }})</span>
        </div>
        <div class="info-item">
            <span class="info-label">شێوازی وەرگرتن (Payment Method):</span>
            <span class="info-val">
                @if($voucher->payment_method === 'cash') 💵 نەقد (Cash)
                @elseif($voucher->payment_method === 'bank_transfer') 🏦 حەواڵەی بانکی
                @elseif($voucher->payment_method === 'fib_fastpay') 📱 پارەدانی ئەلیکترۆنی (FIB / FastPay)
                @elseif($voucher->payment_method === 'cheque') 📑 چەکی بانکی (ژمارە: {{ $voucher->cheque_number }})
                @else {{ $voucher->payment_method }}
                @endif
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">وەرگر (نوێنەر / ژمێریار / شۆفێر):</span>
            <span class="info-val">👤 {{ $voucher->collector?->name ?? 'قاسەی سەرەکی کۆمپانیا' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">دۆخی نەقدینە (Cash Custody):</span>
            <span class="info-val" style="color: {{ $voucher->custody_status === 'deposited_to_safe' ? '#15803d' : '#d97706' }};">
                {{ $voucher->custody_status === 'deposited_to_safe' ? '✓ تەسلیم بە قاسمی سەرەکی کراوە' : '🚚 لای شۆفێر / مەندوبە (لە گەڕاندایە)' }}
            </span>
        </div>
    </div>

    <div class="amount-highlight-box">
        <div class="lbl">کۆی بڕی پارەی وەرگیراو بە دۆلاری ئەمریکی:</div>
        <div class="big-amount">${{ number_format($voucher->amount, 2) }}</div>
        @if($voucher->amount_iqd)
            <div class="sub-iqd">
                بە دیناری عێراقی: {{ number_format($voucher->amount_iqd) }} دینار (نرخی سەرف: {{ number_format($voucher->exchange_rate) }})
            </div>
        @endif
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th>بۆ حیسابی</th>
                <th>ژمارەی بەڵگەنامە</th>
                <th>تێبینی و ڕوونکردنەوە</th>
                <th>دۆخی قەرز پاش دانەوە</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @if($voucher->purchaseOrder)
                        فەرمانی کڕینی کۆگا
                    @elseif($voucher->cashVanSale)
                        فرۆشتنی کاش ڤان
                    @else
                        حیسابی گشتی کڕیار
                    @endif
                </td>
                <td style="font-family:var(--font-mono); font-weight:700;">
                    {{ $voucher->purchaseOrder?->po_number ?? $voucher->cashVanSale?->sale_number ?? 'حیسابی کراوە' }}
                </td>
                <td>{{ $voucher->notes ?? 'دانەوەی قەرز و پاکتاوی دارایی' }}</td>
                <td style="font-weight:700; color:#15803d;">
                    @if($voucher->purchaseOrder)
                        ماوەی ئەم فەرمانە: ${{ number_format($voucher->purchaseOrder->remaining_debt, 2) }}
                    @elseif($voucher->cashVanSale)
                        ماوەی ئەم وەسڵە: ${{ number_format($voucher->cashVanSale->balance_due, 2) }}
                    @else
                        تۆمارکرا لە دەفتەری حیساب
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-title">واژۆی پارەدەر (کڕیار / مارکێت)</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-title">واژۆ و مۆری وەرگر (کۆمپانیای دابینکەر)</div>
        </div>
    </div>
</div>

<div class="action-bar">
    <button onclick="window.print()" class="btn btn-print">🖨️ چاپکردنی پسوولە (Print)</button>
    <a href="javascript:history.back()" class="btn btn-back">← گەڕانەوە</a>
</div>

</body>
</html>
