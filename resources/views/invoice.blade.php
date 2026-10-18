<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>پسوولەی بارکردن و فاکتۆری فەرمی — {{ $order->po_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            padding: 2rem;
            direction: rtl;
        }
        .invoice-card {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            padding: 3rem;
            border: 1px solid #e2e8f0;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .brand-title h1 { font-size: 1.7rem; color: #4338ca; }
        .brand-title p { color: #64748b; font-size: 0.85rem; margin-top: 0.2rem; }
        .invoice-meta { text-align: left; }
        .invoice-meta h2 { font-size: 1.3rem; color: #0f172a; }
        .invoice-meta p { font-size: 0.85rem; color: #64748b; margin-top: 0.2rem; }

        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .party-box h3 { font-size: 0.9rem; text-transform: uppercase; color: #6366f1; margin-bottom: 0.5rem; }
        .party-box p { font-size: 0.88rem; color: #334155; line-height: 1.6; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.9rem; }
        th { background: #f1f5f9; padding: 0.8rem 1rem; text-align: right; font-weight: 700; color: #475569; border-bottom: 1px solid #cbd5e1; }
        td { padding: 0.9rem 1rem; border-bottom: 1px solid #e2e8f0; text-align: right; }
        code { font-family: 'JetBrains Mono', monospace; direction: ltr; display: inline-block; }

        .financials-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }
        .inspection-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.2rem;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        .inspection-box strong { color: #0f172a; display: block; margin-bottom: 0.3rem; }
        .totals-table { width: 100%; font-size: 0.9rem; }
        .totals-table td { padding: 0.5rem 0; border: none; }
        .totals-table tr.total-row td {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            padding-top: 0.8rem;
        }
        .debt-alert {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-top: 0.75rem;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .paid-alert {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-top: 0.75rem;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px dashed #cbd5e1;
        }
        .sig-box { text-align: center; }
        .sig-line {
            height: 60px;
            border-bottom: 1px solid #94a3b8;
            margin-bottom: 0.5rem;
        }
        .sig-box span { font-size: 0.85rem; color: #64748b; font-weight: 600; }

        .btn-bar {
            max-width: 900px;
            margin: 0 auto 1.5rem auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #4f46e5;
            color: #fff;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-back {
            color: #475569;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 0; }
            .btn-bar { display: none; }
        }
    </style>
</head>
<body>

<div class="btn-bar">
    <a href="{{ url()->previous() }}" class="btn-back">← گەڕانەوە بۆ پلاتفۆرم</a>
    <button onclick="window.print()" class="btn-print">🖨️ چاپکردنی پسوولە و بەیاننامە</button>
</div>

<div class="invoice-card">
    <div class="invoice-header">
        <div class="brand-title">
            <h1>MarketLink Enterprise B2B</h1>
            <p>پسوولەی فەرمی بارکردن و فرۆشتنی کۆمەڵ (Commercial Waybill & Delivery Invoice)</p>
        </div>
        <div class="invoice-meta">
            <h2>{{ $order->po_number }}</h2>
            <p>ژمارەی پسوولەی بارکردن: <code>{{ $order->waybill_number ?? 'WB-PENDING' }}</code></p>
            <p>بەرواری دەرچوون: {{ $order->created_at->format('Y-m-d') }}</p>
            <p>دۆخ: <strong>{{ $order->status === 'delivered' ? 'گەیەندراوە و وەرگیراوە' : ($order->status === 'dispatched' ? 'لە ڕێگایە' : 'تۆمارکراو') }}</strong></p>
        </div>
    </div>

    <div class="parties-grid">
        <div class="party-box">
            <h3>دابینکەر (فرۆشیاری کۆمەڵ)</h3>
            <p><strong>{{ $order->supplier->name }}</strong></p>
            <p>مۆڵەتی بازرگانی: <code>{{ $order->supplier->trade_license_number }}</code></p>
            <p>شار و ناونیشان: {{ $order->supplier->city }}, {{ $order->supplier->country }}</p>
            <p>تەلەفۆن: {{ $order->supplier->primary_phone }}</p>
            <p>ئیمەیڵ: {{ $order->supplier->primary_email }}</p>
        </div>
        <div class="party-box">
            <h3>کڕیار (مارکێت / فرۆشیاری تاک)</h3>
            <p><strong>{{ $order->retailer->name }}</strong></p>
            <p>مۆڵەتی بازرگانی: <code>{{ $order->retailer->trade_license_number }}</code></p>
            <p>شار و ناونیشان: {{ $order->retailer->city }}, {{ $order->retailer->country }}</p>
            <p>تەلەفۆن: {{ $order->retailer->primary_phone }}</p>
            <p>شوێنی گەیاندن: {{ $order->delivery_address }}</p>
        </div>
    </div>

    @php
        $deliveryProof = \App\Models\DeliveryProof::with('discrepancies.purchaseOrderItem')->where('purchase_order_id', $order->id)->latest()->first();
        $discrepanciesByItem = $deliveryProof ? $deliveryProof->discrepancies->keyBy('purchase_order_item_id') : collect();
        $initialOrderSubtotal = $order->items->sum(fn($it) => (float)$it->quantity_ordered * (float)$it->unit_price);
        $hasDiscrepancies = ((float)$order->damage_amount > 0) || ($deliveryProof && $deliveryProof->discrepancies->count() > 0);
    @endphp

    <table>
        <thead>
            <tr>
                <th>کۆدی کاڵا (SKU)</th>
                <th>ناوی بابەت</th>
                <th>بڕی داواکراو</th>
                @if($hasDiscrepancies)
                    <th>بڕی گەیشتوو</th>
                    <th>کەمی / تەلەف</th>
                @endif
                <th>نرخی یەکە ($)</th>
                <th>کۆی شایستە ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            @php
                $disc = $discrepanciesByItem->get($item->id);
                $deliveredQty = $disc ? (int)$disc->delivered_quantity : ($item->quantity_delivered !== null ? (int)$item->quantity_delivered : (int)$item->quantity_ordered);
                $missingQty = max(0, (int)$item->quantity_ordered - $deliveredQty);
                $actualLineTotal = $deliveredQty * (float)$item->unit_price;
            @endphp
            <tr>
                <td><code>{{ $item->sku }}</code></td>
                <td>
                    <strong>{{ $item->product_title }}</strong>
                    @if($disc && $missingQty > 0)
                        <span style="display:block; font-size:0.75rem; color:#be123c; margin-top:2px;">
                            @if($disc->reason === 'damaged') 💥 تەلەف و شکاو: {{ $missingQty }} دانە
                            @elseif($disc->reason === 'missing') 📦 کەم هاتووە: {{ $missingQty }} دانە
                            @elseif($disc->reason === 'expired') ⏳ ئێکسپایەر و بەسەرچوو: {{ $missingQty }} دانە
                            @elseif($disc->reason === 'rejected_by_store') ↩️ گەڕاوەی مارکێت: {{ $missingQty }} دانە
                            @else {{ $disc->reason }}: {{ $missingQty }} دانە
                            @endif
                        </span>
                    @endif
                </td>
                <td>{{ number_format($item->quantity_ordered) }} دانە</td>
                @if($hasDiscrepancies)
                    <td><strong style="color:{{ $missingQty > 0 ? '#e11d48' : '#059669' }};">{{ number_format($deliveredQty) }} دانە</strong></td>
                    <td>
                        @if($missingQty > 0)
                            <span style="background:#fee2e2; color:#be123c; font-weight:700; font-size:0.75rem; padding:2px 8px; border-radius:4px; display:inline-block;">
                                -{{ $missingQty }} دانە
                            </span>
                        @else
                            <span style="color:#059669; font-size:0.8rem;">بێ کێشە ✓</span>
                        @endif
                    </td>
                @endif
                <td>${{ number_format($item->unit_price, 2) }}</td>
                <td>
                    @if($hasDiscrepancies && $missingQty > 0)
                        <strong style="color:#0f172a; font-family:'JetBrains Mono',monospace;">${{ number_format($actualLineTotal, 2) }}</strong>
                        <div style="font-size:0.72rem; color:#94a3b8; text-decoration:line-through; font-family:'JetBrains Mono',monospace;">${{ number_format($item->quantity_ordered * $item->unit_price, 2) }}</div>
                    @else
                        <strong style="font-family:'JetBrains Mono',monospace;">${{ number_format($item->total_price, 2) }}</strong>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="financials-grid">
        <div class="inspection-box">
            <strong>🚚 زانیاری بارکردن و گەیاندن:</strong>
            <p>شوفێر / گەیەنەر: {{ $order->driver_name ?? 'دیارینەکراوە' }} ({{ $order->driver_phone ?? '-' }})</p>
            @if($order->vehicle_plate)
                <p>ژمارەی تابلۆی ئۆتۆمبێل: <code>{{ $order->vehicle_plate }}</code></p>
            @endif
            <p>بەرواری ناردن: {{ $order->dispatched_at ? $order->dispatched_at->format('Y-m-d H:i') : '-' }}</p>
            @if($order->delivery_otp)
                <p>کۆدی دڵنیایی (OTP): <code>{{ $order->delivery_otp }}</code> <span style="color:#059669; font-weight:700;">(پشتڕاستکراوە ✓)</span></p>
            @endif
            <br>
            <strong>🔍 ئەنجامی وردبینی و پشکنینی مارکێت (PoD):</strong>
            <p>وردبینیکەر لە مارکێت: <strong>{{ $order->inspected_by ?? 'چاوەڕوانی گەیشتنە' }}</strong></p>
            <p>بەرواری وەرگرتن: {{ $order->received_at ? $order->received_at->format('Y-m-d H:i') : '-' }}</p>
            <p>تێبینی پشکنین: {{ $order->inspection_notes ?? 'تێبینی نییە' }}</p>
            @if($deliveryProof && $deliveryProof->delivered_latitude)
                <p style="font-size:0.75rem; color:#64748b; margin-top:0.3rem;">
                    📍 شوێنی جوگرافی (GPS): {{ $deliveryProof->delivered_latitude }}, {{ $deliveryProof->delivered_longitude }}
                </p>
            @endif
        </div>

        <div>
            <table class="totals-table">
                @if($hasDiscrepancies && (float)$order->damage_amount > 0)
                <tr>
                    <td style="color:#64748b;">کۆی داواکراوی سەرەتایی:</td>
                    <td style="text-align:left; font-family:'JetBrains Mono',monospace; color:#64748b;">${{ number_format($initialOrderSubtotal, 2) }}</td>
                </tr>
                <tr style="color:#e11d48; background:#fff1f2; font-weight:700;">
                    <td style="padding:0.4rem 0.5rem; border-radius:4px 0 0 4px;">داشکاندنی کەمی، تەلەف و گەڕاوە:</td>
                    <td style="text-align:left; font-family:'JetBrains Mono',monospace; padding:0.4rem 0.5rem; border-radius:0 4px 4px 0;">-${{ number_format($order->damage_amount, 2) }}</td>
                </tr>
                @endif
                <tr style="border-top:1px solid #cbd5e1;">
                    <td><strong>کۆی گشتی فەرمان (شایستەی پارەدان):</strong></td>
                    <td style="text-align:left; font-family:'JetBrains Mono',monospace;"><strong style="font-size:1.15rem; color:#0f172a;">${{ number_format($order->total_amount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td>بڕی پارەی دراو (کاش/حەواڵە):</td>
                    <td style="text-align:left; color:#047857; font-family:'JetBrains Mono',monospace;"><strong>${{ number_format($order->paid_amount, 2) }}</strong></td>
                </tr>
                <tr class="total-row">
                    <td>قەرزی ماوە بۆ دابینکەر:</td>
                    <td style="text-align:left; color:#b91c1c; font-family:'JetBrains Mono',monospace;">${{ number_format($order->remaining_debt, 2) }}</td>
                </tr>
            </table>

            @if($order->remaining_debt > 0)
                <div class="debt-alert">
                    ⚠️ بڕی قەرزی ماوە: ${{ number_format($order->remaining_debt, 2) }}
                    <div style="font-size:0.75rem; font-weight:normal; margin-top:0.2rem;">مەرجی پارەدان: {{ $order->payment_terms }}</div>
                </div>
            @else
                <div class="paid-alert">
                    ✓ هەموو بڕی پارەکە دراوە و قەرز نەماوە
                </div>
            @endif
        </div>
    </div>

    @if($deliveryProof && $deliveryProof->discrepancies->count() > 0)
    <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:8px; padding:1.2rem; margin-bottom:2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
            <strong style="color:#be123c; font-size:1rem; display:flex; align-items:center; gap:0.4rem;">
                ⚠️ ڕاپۆرتی کەمی، تەلەف، ئێکسپایەر و گەڕاوەی کاڵاکان لە کاتی گەیاندن (Delivery Discrepancies):
            </strong>
            <span style="font-size:0.8rem; background:#ffe4e6; color:#be123c; padding:2px 8px; border-radius:4px; font-weight:700;">
                کۆی کەمکراوە: -${{ number_format($order->damage_amount, 2) }}
            </span>
        </div>
        <table style="margin-bottom:0; background:#fff; border-radius:6px; overflow:hidden;">
            <thead>
                <tr style="background:#ffe4e6;">
                    <th>ناوی کاڵا</th>
                    <th style="text-align:center;">بڕی داواکراو</th>
                    <th style="text-align:center;">بڕی گەیشتوو</th>
                    <th style="text-align:center;">کەم / تەلەف</th>
                    <th style="text-align:center;">هۆکار (Reason)</th>
                    <th style="text-align:left;">بڕی داشکاندن ($)</th>
                    <th>تێبینی تایبەت</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryProof->discrepancies as $disc)
                @php
                    $poItem = $disc->purchaseOrderItem;
                    $unitP = $poItem ? (float)$poItem->unit_price : 0;
                    $diffQty = max(0, (int)$disc->ordered_quantity - (int)$disc->delivered_quantity);
                    $discTotal = $diffQty * $unitP;
                    $reasonInfo = match($disc->reason) {
                        'damaged' => ['label' => '💥 تەلەف و شکاو (Damaged)', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
                        'missing' => ['label' => '📦 کەم هاتووە / ونبوو (Missing)', 'bg' => '#fef3c7', 'color' => '#b45309'],
                        'expired' => ['label' => '⏳ ئێکسپایەر و بەسەرچوو (Expired)', 'bg' => '#ffedd5', 'color' => '#c2410c'],
                        'rejected_by_store' => ['label' => '↩️ گەڕاوەی مارکێت (Returned)', 'bg' => '#e0e7ff', 'color' => '#3730a3'],
                        default => ['label' => $disc->reason, 'bg' => '#f1f5f9', 'color' => '#475569'],
                    };
                @endphp
                <tr>
                    <td><strong>{{ $poItem?->product_title ?? 'کاڵا' }}</strong></td>
                    <td style="text-align:center;">{{ $disc->ordered_quantity }} دانە</td>
                    <td style="text-align:center;"><strong style="color:#059669;">{{ $disc->delivered_quantity }} دانە</strong></td>
                    <td style="text-align:center;"><strong style="color:#e11d48;">-{{ $diffQty }} دانە</strong></td>
                    <td style="text-align:center;">
                        <span style="background:{{ $reasonInfo['bg'] }}; color:{{ $reasonInfo['color'] }}; font-size:0.75rem; font-weight:700; padding:3px 8px; border-radius:4px; display:inline-block;">
                            {{ $reasonInfo['label'] }}
                        </span>
                    </td>
                    <td style="text-align:left; font-family:'JetBrains Mono',monospace; color:#e11d48; font-weight:700;">
                        -${{ number_format($discTotal, 2) }}
                    </td>
                    <td style="font-size:0.8rem; color:#475569;">{{ $disc->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line"></div>
            <span>واژۆ و مۆری دابینکەر (نێرەر)</span>
        </div>
        <div class="sig-box">
            <div class="sig-line" style="display:flex; align-items:center; justify-content:center;">
                @if($deliveryProof && $deliveryProof->signature_image_path)
                    <img src="{{ $deliveryProof->signature_image_path }}" alt="Signature" style="max-height:50px; object-fit:contain;" />
                @elseif($order->delivery_signature)
                    <img src="{{ $order->delivery_signature }}" alt="Signature" style="max-height:50px; object-fit:contain;" />
                @endif
            </div>
            <span>واژۆ و مۆری مارکێت (وەرگر و وردبینیکەر)</span>
        </div>
    </div>
</div>

</body>
</html>
