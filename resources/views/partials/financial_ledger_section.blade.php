<div id="financial-ledger-section" class="panel" style="border-right: 4px solid #10b981; background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(15, 23, 42, 0.5)); margin-top: 2rem; scroll-margin-top: 80px;">
    <!-- SECTION HEADER -->
    <div class="panel-header" style="flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid rgba(16, 185, 129, 0.2); padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <div>
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <span style="font-size:1.6rem;">💰</span>
                <h3 class="panel-title" style="color: #34d399; font-size: 1.35rem; font-weight: 800;">
                    سیستەمی یەکگرتووی ژمێریاری، کەشف حیساب و وەرگرتنی پارە (سند قبض)
                </h3>
            </div>
            <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.3rem;">
                دەفتەری حیسابی یەکگرتوو (Unified Customer Ledger)، شیکاری تەمەنی قەرزەکان (Aging Analysis)، وەسڵی وەرگرتنی فەرمی بە دۆلار و دینار، و تەحاسوبی نەقدینەی شۆفێر.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
            <button type="button" 
                    class="btn btn-sm" 
                    style="background: linear-gradient(135deg, #7c3aed, #4f46e5); color:#fff; font-weight:700; border-radius:8px; padding:0.45rem 0.9rem; display:inline-flex; align-items:center; gap:0.4rem; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);"
                    onclick="openOpeningBalanceModal(null, '', {{ $currentSupplier?->id ?? 'null' }})"
                    title="تۆمارکردنی قەرزی کۆنی کڕیار پێش بەکارهێنانی ئەم سیستمە">
                <span>⚖️</span> زیادکردنی قەرزی پێشوو (باڵانسی سەرەتایی)
            </button>
            <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                ● ژمێریاری فەرمی چالاکە
            </span>
            @if($currentSupplier)
            <span class="badge badge-info" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                🏭 کۆمپانیا: {{ $currentSupplier->name }}
            </span>
            @endif
        </div>
    </div>

    <!-- FINANCIAL METRICS TILES -->
    @php
        $totalCustomerDebt = $customerFinancialSummaries->sum('total_debt');
        $totalCollectedVouchers = $recentReceiptVouchers->sum('amount');
        $overLimitCount = $customerFinancialSummaries->where('is_over_limit', true)->count();
    @endphp
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 1.1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600; margin-bottom: 0.3rem;">💵 کۆی گشتی قەرزەکان (Receivables)</div>
            <div style="font-size: 1.6rem; font-weight: 800; color: #f87171; font-family: var(--font-mono);">
                ${{ number_format($totalCustomerDebt, 2) }}
            </div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.2rem;">قەرزی کۆگای سەرەکی + کاش ڤان</div>
        </div>

        <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 1.1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600; margin-bottom: 0.3rem;">🧾 کۆی وەرگیراو بە پسوولە (Vouchers)</div>
            <div style="font-size: 1.6rem; font-weight: 800; color: #34d399; font-family: var(--font-mono);">
                ${{ number_format($totalCollectedVouchers, 2) }}
            </div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.2rem;">{{ $recentReceiptVouchers->count() }} پسوولەی فەرمی دەرکراو</div>
        </div>

        <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 1.1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600; margin-bottom: 0.3rem;">🚚 نەقدینەی ئەمانەتی شۆفێر (In Custody)</div>
            <div style="font-size: 1.6rem; font-weight: 800; color: #fbbf24; font-family: var(--font-mono);">
                ${{ number_format($totalDriverCustodyCash, 2) }}
            </div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.2rem;">{{ $driverCustodyVouchers->count() }} پسوولە چاوەڕوانی تەسلیماتن</div>
        </div>

        <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 12px; padding: 1.1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600; margin-bottom: 0.3rem;">⚠️ چاودێری سەقفی متمانە (Credit Limits)</div>
            <div style="font-size: 1.6rem; font-weight: 800; color: {{ $overLimitCount > 0 ? '#ef4444' : '#818cf8' }}; font-family: var(--font-mono);">
                {{ $overLimitCount }} کڕیار
            </div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.2rem;">تێپەڕاندنی سەقفی قەرزی ڕێگەپێدراو</div>
        </div>
    </div>

    <!-- TAB NAVIGATION FOR FINANCIAL SECTION -->
    <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; border-bottom:1px solid var(--border-subtle); padding-bottom:0.75rem; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" id="btn-tab-fin-customers" onclick="switchFinancialTab('customers')" style="font-weight:700; border-radius:8px;">
            👥 دەفتەری حیسابی کڕیاران و قەرزەکان ({{ $customerFinancialSummaries->count() }})
        </button>
        <button type="button" class="btn" id="btn-tab-fin-matrix" onclick="switchFinancialTab('matrix')" style="background:rgba(255,255,255,0.06); color:#fff; font-weight:700; border-radius:8px;">
            🏢 کام مارکێت قەرزداری کام کۆمپانیایە ({{ $marketCompanyDebts->count() }})
        </button>
        <button type="button" class="btn" id="btn-tab-fin-vouchers" onclick="switchFinancialTab('vouchers')" style="background:rgba(255,255,255,0.06); color:#fff; font-weight:700; border-radius:8px;">
            🧾 دوایین پسوولەکانی وەرگرتن / سند قبض ({{ $recentReceiptVouchers->count() }})
        </button>
        <button type="button" class="btn" id="btn-tab-fin-clearance" onclick="switchFinancialTab('clearance')" style="background:rgba(255,255,255,0.06); color:#fff; font-weight:700; border-radius:8px;">
            🚚 پاکتاوی نەقدینەی شۆفێران / تەسلیمات ({{ $driverCustodyVouchers->count() }})
        </button>
    </div>

    <!-- TAB 1: CUSTOMER LEDGER & DEBTS TABLE -->
    <div id="fin-tab-content-customers" style="display: block;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
            <h4 style="color:#f8fafc; font-size:1.05rem; display:flex; align-items:center; gap:0.4rem;">
                <span>📊</span> خشتەی قەرز، دەفتەری حیساب و سەقفی متمانەی کڕیاران
            </h4>
            <span style="font-size:0.8rem; color:var(--text-tertiary);">
                کلیک لەسەر «📑 کەشف حیساب» بکە بۆ ڕاپۆرتی تەمەنی قەرز و جووڵەکان یان «🧾 وەرگرتنی پارە» بۆ دەرکردنی پسوولەی فەرمی.
            </span>
        </div>

        <div style="overflow-x: auto; border: 1px solid var(--border-subtle); border-radius: 10px;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(16, 20, 29, 0.95);">
                        <th style="padding: 10px 14px; text-align: right;">مارکێت / کڕیار</th>
                        <th style="padding: 10px 14px; text-align: right;">شار و پەیوەندی</th>
                        <th style="padding: 10px 14px; text-align: center;">داواکارییەکان</th>
                        <th style="padding: 10px 14px; text-align: right;">سەقفی متمانە (Limit)</th>
                        <th style="padding: 10px 14px; text-align: right;">قەرزی ماوە (Running Balance)</th>
                        <th style="padding: 10px 14px; text-align: center;">دۆخی مەترسی</th>
                        <th style="padding: 10px 14px; text-align: center;">کردارەکان (Actions)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customerFinancialSummaries as $item)
                    @php
                        $ret = $item['retailer'];
                        $debt = $item['total_debt'];
                        $limit = $item['credit_limit'];
                        $supId = $item['supplier_id'];
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-subtle); transition: background 0.15s;">
                        <td style="padding: 12px 14px;">
                            <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">🛒 {{ $ret->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-tertiary);">کۆدی کڕیار: #RET-{{ str_pad($ret->id, 4, '0', STR_PAD_LEFT) }}</div>
                        </td>
                        <td style="padding: 12px 14px;">
                            <div style="color: var(--text-secondary); font-size: 0.85rem;">{{ $ret->city }}</div>
                            <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--text-tertiary);">{{ $ret->primary_phone }}</div>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <span class="badge badge-info" style="font-size: 0.8rem;">{{ $item['total_orders_count'] }} فەرمان</span>
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-family: var(--font-mono); color: #94a3b8; font-weight: 600;">
                            ${{ number_format($limit, 2) }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right;">
                            <strong style="color: {{ $debt > 0 ? '#f87171' : '#34d399' }}; font-size: 1.05rem; font-family: var(--font-mono);">
                                ${{ number_format($debt, 2) }}
                            </strong>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            @if($debt <= 0)
                                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.75rem;">سەلامەت (0 قەرز)</span>
                            @elseif($item['is_over_limit'])
                                <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; font-size: 0.75rem; font-weight:700;">⚠️ تێپەڕاندنی سەقف!</span>
                            @elseif($debt > ($limit * 0.75))
                                <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; font-size: 0.75rem;">نزیک سەقف</span>
                            @else
                                <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; font-size: 0.75rem;">قەرزی ئاسایی</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <div style="display: flex; gap: 0.4rem; justify-content: center; align-items: center; flex-wrap: wrap;">
                                <!-- STATEMENT OF ACCOUNT BUTTON -->
                                <a href="{{ route('financial.statement.show', ['retailerId' => $ret->id, 'supplier_id' => $supId]) }}" 
                                   class="btn btn-sm" 
                                   style="background: #2563eb; color: #fff; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"
                                   target="_blank" 
                                   title="کردنەوەی کەشف حیسابی تەواو لەگەڵ شیکاری تەمەنی قەرز">
                                    <span>📑</span> کەشف حیساب
                                </a>

                                <!-- ISSUE RECEIPT VOUCHER BUTTON -->
                                <button type="button" 
                                        class="btn btn-sm btn-success" 
                                        style="font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;"
                                        onclick="openCollectPaymentModal({{ $ret->id }}, '{{ addslashes($ret->name) }}', {{ $debt }}, {{ $supId ?? 'null' }})"
                                        title="تۆمارکردنی پارەی دراو و دەرکردنی پسوولەی فەرمی سند قبض">
                                    <span>🧾</span> وەرگرتنی پارە (سند قبض)
                                </button>

                                <!-- OPENING BALANCE / PREVIOUS DEBT BUTTON -->
                                <button type="button" 
                                        class="btn btn-sm" 
                                        style="background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.4); color: #c084fc; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;"
                                        onclick="openOpeningBalanceModal({{ $ret->id }}, '{{ addslashes($ret->name) }}', {{ $supId ?? 'null' }})"
                                        title="تۆمارکردنی باڵانسی سەرەتایی یان قەرزی پێش سیستم">
                                    <span>⚖️</span> قەرزی پێشوو
                                </button>

                                <!-- DIRECT CHAT BUTTON -->
                                <a href="?role=supplier&active_supplier_id={{ $supId }}&chat_retailer_id={{ $ret->id }}#chat-section" 
                                   class="btn btn-sm" 
                                   style="background: rgba(255,255,255,0.08); color: #fff; padding: 0.35rem 0.65rem; border-radius: 6px; text-decoration: none;"
                                   title="چات لەگەڵ ئەم مارکێتە">
                                    💬
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-tertiary);">
                            هیچ تۆمارێکی قەرز یان کڕیارێک نەدۆزرایەوە. هەموو حساباتەکان خاوێنن!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: RECENT PAYMENT VOUCHERS (سند قبض) -->
    <div id="fin-tab-content-vouchers" style="display: none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
            <h4 style="color:#f8fafc; font-size:1.05rem; display:flex; align-items:center; gap:0.4rem;">
                <span>🧾</span> تۆماری فەرمی پسوولەکانی وەرگرتنی پارە (Payment Receipt Vouchers)
            </h4>
            <span style="font-size:0.8rem; color:var(--text-tertiary);">
                هەموو پسوولەیەک بە فۆرماتی ژمێریاری ستاندارد (A4 یان چاپکەری حەراری Thermal 80mm) بەردەستە.
            </span>
        </div>

        <div style="overflow-x: auto; border: 1px solid var(--border-subtle); border-radius: 10px;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(16, 20, 29, 0.95);">
                        <th style="padding: 10px 14px; text-align: right;">ژمارەی پسوولە (Ref)</th>
                        <th style="padding: 10px 14px; text-align: right;">بەروار</th>
                        <th style="padding: 10px 14px; text-align: right;">مارکێت (پارەدەر)</th>
                        <th style="padding: 10px 14px; text-align: right;">دابینکەر (وەرگر)</th>
                        <th style="padding: 10px 14px; text-align: right;">بڕی پارە ($ و IQD)</th>
                        <th style="padding: 10px 14px; text-align: center;">شێوازی پارەدان</th>
                        <th style="padding: 10px 14px; text-align: center;">دۆخی ئەمانەت</th>
                        <th style="padding: 10px 14px; text-align: center;">کرداری چاپ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentReceiptVouchers as $voc)
                    <tr style="border-bottom: 1px solid var(--border-subtle);">
                        <td style="padding: 12px 14px;">
                            <strong style="color: #60a5fa; font-family: var(--font-mono); font-size: 0.9rem;">
                                {{ $voc->voucher_number }}
                            </strong>
                            @if($voc->purchaseOrder)
                                <div style="font-size: 0.72rem; color: var(--text-tertiary);">بەستراوە بە: {{ $voc->purchaseOrder->po_number }}</div>
                            @elseif($voc->cashVanSale)
                                <div style="font-size: 0.72rem; color: #fbbf24;">کاش ڤان: {{ $voc->cashVanSale->sale_number }}</div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; font-family: var(--font-mono); font-size: 0.82rem; color: var(--text-secondary);">
                            {{ $voc->created_at ? $voc->created_at->format('Y-m-d H:i') : $voc->receipt_date }}
                        </td>
                        <td style="padding: 12px 14px;">
                            <div style="font-weight: 700; color: #fff;">{{ $voc->retailer?->name ?? 'مارکێت' }}</div>
                        </td>
                        <td style="padding: 12px 14px;">
                            <div style="color: var(--text-secondary); font-size: 0.85rem;">{{ $voc->supplier?->name ?? 'دابینکەر' }}</div>
                        </td>
                        <td style="padding: 12px 14px; text-align: right;">
                            <strong style="color: #34d399; font-size: 1.05rem; font-family: var(--font-mono);">
                                ${{ number_format($voc->amount, 2) }}
                            </strong>
                            @if($voc->amount_iqd)
                                <div style="font-size: 0.75rem; color: #94a3b8; font-family: var(--font-mono);">
                                    {{ number_format($voc->amount_iqd) }} IQD
                                </div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            @php
                                $methodBadge = match($voc->payment_method) {
                                    'cash' => ['label' => '💵 نەقد (Cash)', 'bg' => 'rgba(16, 185, 129, 0.2)', 'color' => '#34d399'],
                                    'bank_transfer' => ['label' => '🏦 حەواڵەی بانکی', 'bg' => 'rgba(59, 130, 246, 0.2)', 'color' => '#60a5fa'],
                                    'fib_fastpay' => ['label' => '📱 FIB / FastPay', 'bg' => 'rgba(168, 85, 247, 0.2)', 'color' => '#c084fc'],
                                    'cheque' => ['label' => '📑 چەکی بانکی', 'bg' => 'rgba(245, 158, 11, 0.2)', 'color' => '#fbbf24'],
                                    default => ['label' => $voc->payment_method, 'bg' => 'rgba(255,255,255,0.1)', 'color' => '#fff'],
                                };
                            @endphp
                            <span class="badge" style="background: {{ $methodBadge['bg'] }}; color: {{ $methodBadge['color'] }}; font-size: 0.75rem;">
                                {{ $methodBadge['label'] }}
                            </span>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            @if($voc->custody_status === 'in_driver_custody')
                                <div style="display:flex; flex-direction:column; gap:0.35rem; align-items:center;">
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; font-size: 0.75rem;">
                                        🚚 لای وەرگر ({{ $voc->collector?->name ?? 'مەندوب/شۆفێر' }})
                                    </span>
                                    @if(in_array($role, ['supplier', 'operator']))
                                        <form action="{{ route('financial.voucher.verify', $voc->id) }}" method="POST" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" style="font-size:0.72rem; padding:0.25rem 0.6rem; border-radius:5px;" title="پەسەندکردنی گەیشتنی پارە بە خەزێنەی سەرەکی کۆمپانیا">
                                                ✓ وەرگرتن لە خەزێنە
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.75rem;">
                                    🏛️ لە خەزێنەی سەرەکی
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <a href="{{ route('financial.voucher.print', $voc->id) }}" 
                               class="btn btn-sm" 
                               style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"
                               target="_blank" 
                               title="کردنەوە و چاپی پسوولە (Thermal یان A4)">
                                <span>🖨️</span> چاپی پسوولە
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-tertiary);">
                            هیچ پسوولەیەکی وەرگرتنی پارە تۆمار نەکراوە تا ئێستا.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: DRIVER CASH CLEARANCE SETTLEMENTS -->
    <div id="fin-tab-content-clearance" style="display: none;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;" class="fin-clearance-grid">
            <!-- PENDING DRIVER CASH IN CUSTODY -->
            <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 1.25rem;">
                <h4 style="color: #fbbf24; font-size: 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
                    <span>🚚</span> نەقدینەی لای شۆفێر لە گەڕاندا (In Driver Custody)
                </h4>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    ئەم پارانە لەلایەن شۆفێرەوە لە مارکێت وەرگیراون بە کاش و پێویستە لە کۆتایی ڕۆژدا تەسلیمی قاسمی سەرەکی کۆمپانیا بکرێن.
                </p>

                <div style="max-height: 250px; overflow-y: auto;">
                    <table class="data-table" style="width: 100%; font-size: 0.82rem;">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.3);">
                                <th>ژمارەی وەسڵ</th>
                                <th>وەرگر / شۆفێر</th>
                                <th>مارکێت</th>
                                <th style="text-align: left;">بڕ ($)</th>
                                <th style="text-align: center;">کردار</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($driverCustodyVouchers as $dvoc)
                            <tr>
                                <td style="font-family: var(--font-mono); color: #60a5fa;">{{ $dvoc->voucher_number }}</td>
                                <td>{{ $dvoc->collector?->name ?? 'شۆفێر' }}</td>
                                <td>{{ $dvoc->retailer?->name ?? 'مارکێت' }}</td>
                                <td style="text-align: left; font-family: var(--font-mono); color: #fbbf24; font-weight: 700;">
                                    ${{ number_format($dvoc->amount, 2) }}
                                </td>
                                <td style="text-align: center;">
                                    @if(in_array($role, ['supplier', 'operator']))
                                        <form action="{{ route('financial.voucher.verify', $dvoc->id) }}" method="POST" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" style="font-size:0.72rem; padding:0.25rem 0.5rem;" title="تەسلیمکردنی ئەم وەسڵە بە تەنیا لە خەزێنە">
                                                ✓ تەسلیم
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 1.5rem; color: var(--text-tertiary);">
                                    هیچ نەقدینەیەک لە لای شۆفێر نەماوە. هەمووی پاکتاو کراوە!
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SETTLEMENT CLEARANCE FORM -->
            <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 10px; padding: 1.25rem;">
                <h4 style="color: #34d399; font-size: 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
                    <span>🏛️</span> فۆڕمی پاکتاوکردن و ڕادەستکردنی قاسەی سەرەکی
                </h4>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    ژمێریاری سەرەکی بڕی پارەی ڕاستەقینەی وەرگیراو دەنووسێت بۆ پشتڕاستکردنەوە و دەرکردنی تەسلیماتنامەی فەرمی.
                </p>

                <form action="{{ route('financial.clearance.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="supplier_company_id" value="{{ $currentSupplier?->id ?? $suppliers->first()?->id }}">

                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.8rem;">دیاریکردنی شۆفێر بۆ پاکتاو *</label>
                        <select name="driver_user_id" required style="width: 100%;">
                            @php
                                $uniqueDrivers = $driverCustodyVouchers->pluck('collector')->filter()->unique('id');
                            @endphp
                            @forelse($uniqueDrivers as $drv)
                                <option value="{{ $drv->id }}">🚚 {{ $drv->name }} ({{ $drv->email }})</option>
                            @empty
                                @if(auth()->user() && auth()->user()->role === 'driver')
                                    <option value="{{ auth()->id() }}">🚚 {{ auth()->user()->name }} (خۆم)</option>
                                @else
                                    <option value="{{ \App\Models\User::where('role', 'driver')->value('id') ?? auth()->id() }}">
                                        {{ \App\Models\User::where('role', 'driver')->value('name') ?? 'شۆفێری کۆمپانیا' }}
                                    </option>
                                @endif
                            @endforelse
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.8rem;">بڕی پارەی ڕاستەقینەی ڕادەستکراو بە دۆلار ($) *</label>
                        <input type="number" step="0.01" min="0" name="actual_cash_deposited" required placeholder="0.00" value="{{ $totalDriverCustodyCash > 0 ? $totalDriverCustodyCash : '' }}" style="width: 100%; font-family: var(--font-mono); font-size: 1.1rem; color: #34d399; font-weight: 700;">
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="font-size: 0.8rem;">تێبینی پاکتاو یان هۆکاری کەم/زیادی</label>
                        <input type="text" name="notes" placeholder="پاکتاوی کۆتایی ڕۆژ..." style="width: 100%;">
                    </div>

                    <button type="submit" class="btn btn-success" style="width: 100%; font-weight: 800; padding: 0.6rem; justify-content: center;">
                        ✓ پشتڕاستکردنەوە و وەرگرتن لە قاسمی سەرەکی
                    </button>
                </form>
            </div>
        </div>

        <!-- RECENT SETTLEMENTS LIST -->
        <div>
            <h4 style="color: #f8fafc; font-size: 0.95rem; margin-bottom: 0.5rem;">📜 مێژووی پاکتاوی کۆتایی ڕۆژ (Cash Clearance History)</h4>
            <div style="overflow-x: auto; border: 1px solid var(--border-subtle); border-radius: 8px;">
                <table class="data-table" style="width: 100%; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: rgba(16, 20, 29, 0.95);">
                            <th>ژمارەی پاکتاو</th>
                            <th>بەروار</th>
                            <th>شۆفێر</th>
                            <th>پشتڕاستکەرەوە</th>
                            <th style="text-align: right;">کۆی لای شۆفێر</th>
                            <th style="text-align: right;">ڕادەستکراو</th>
                            <th style="text-align: right;">جیاوازی (Discrepancy)</th>
                            <th style="text-align: center;">دۆخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentClearanceSettlements as $cs)
                        <tr>
                            <td style="font-family: var(--font-mono); color: #60a5fa; font-weight: 700;">{{ $cs->settlement_number }}</td>
                            <td>{{ $cs->settlement_date }}</td>
                            <td>{{ $cs->driver?->name ?? 'شۆفێر' }}</td>
                            <td>{{ $cs->verifier?->name ?? 'ژمێریار' }}</td>
                            <td style="text-align: right; font-family: var(--font-mono);">${{ number_format($cs->total_cash_collected, 2) }}</td>
                            <td style="text-align: right; font-family: var(--font-mono); color: #34d399; font-weight: 700;">${{ number_format($cs->actual_cash_deposited, 2) }}</td>
                            <td style="text-align: right; font-family: var(--font-mono); color: {{ $cs->discrepancy_amount != 0 ? '#ef4444' : '#94a3b8' }};">
                                ${{ number_format($cs->discrepancy_amount, 2) }}
                            </td>
                            <td style="text-align: center;">
                                @if($cs->status === 'verified')
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.75rem;">پەسەندکراو</span>
                                @else
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; font-size: 0.75rem;">کێشە لە بڕ</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 1.5rem; color: var(--text-tertiary);">
                                هیچ پاکتاوێکی پێشوو تۆمار نەکراوە.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: MARKET-TO-COMPANY DEBT MATRIX (کام مارکێت قەرزداری کام کۆمپانیایە) -->
    <div id="fin-tab-content-matrix" style="display: none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:0.6rem;">
            <div>
                <h4 style="color:#f8fafc; font-size:1.1rem; display:flex; align-items:center; gap:0.4rem;">
                    <span>🏢</span> خشتەی یەکلاکەرەوەی قەرزەکان: کام مارکێت قەرزداری کام کۆمپانیایە؟
                </h4>
                <span style="font-size:0.8rem; color:var(--text-tertiary);">
                    شیکاری وردی پەیوەندی نێوان مارکێتەکان و کۆمپانیاکان: بڕی قەرزی هەر مارکێتێک بەرامبەر هەر کۆمپانیایەک بە جیا.
                </span>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                <!-- Filter by Supplier -->
                <select id="matrix-supplier-filter" onchange="filterMatrixTable()" style="background:var(--bg-surface-elevated); border:1px solid var(--border-subtle); color:#fff; padding:0.45rem 0.8rem; border-radius:8px; font-size:0.85rem;">
                    <option value="">🏢 هەموو کۆمپانیاکان (All Companies)</option>
                    @foreach($suppliers as $s)
                        <option value="{{ strtolower($s->name) }}">{{ $s->name }}</option>
                    @endforeach
                </select>
                <!-- Search by Market/City -->
                <input type="text" id="matrix-search-input" oninput="filterMatrixTable()" placeholder="🔍 گەڕان بەپێی مارکێت، شار یان کۆمپانیا..." style="background:rgba(0,0,0,0.3); border:1px solid var(--border-subtle); color:#fff; padding:0.45rem 0.8rem; border-radius:8px; font-size:0.85rem; min-width:240px;">
            </div>
        </div>

        <div style="overflow-x: auto; border: 1px solid var(--border-subtle); border-radius: 10px;">
            <table class="data-table" id="fin-matrix-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(16, 20, 29, 0.95);">
                        <th style="padding: 10px 14px; text-align: right;">مارکێت (کڕیار)</th>
                        <th style="padding: 10px 14px; text-align: right;">کۆمپانیای خاوەن ماف (دابینکەر)</th>
                        <th style="padding: 10px 14px; text-align: center;">داواکارییەکان</th>
                        <th style="padding: 10px 14px; text-align: right;">سەقفی متمانە</th>
                        <th style="padding: 10px 14px; text-align: right;">قەرزی ماوە (Running Balance)</th>
                        <th style="padding: 10px 14px; text-align: center;">دۆخی مەترسی</th>
                        <th style="padding: 10px 14px; text-align: center;">کردارەکان (Actions)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($marketCompanyDebts as $row)
                    @php
                        $ret = $row['retailer'];
                        $sup = $row['supplier'];
                        $debt = $row['effective_debt'];
                        $limit = $row['credit_limit'];
                    @endphp
                    <tr class="matrix-table-row" data-supplier="{{ strtolower($sup->name) }}" data-search="{{ strtolower($ret->name . ' ' . $ret->city . ' ' . $sup->name) }}" style="border-bottom: 1px solid var(--border-subtle); transition: background 0.15s;">
                        <td style="padding: 12px 14px;">
                            <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">🛒 {{ $ret->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-tertiary);">شار: {{ $ret->city }} • {{ $ret->primary_phone }}</div>
                        </td>
                        <td style="padding: 12px 14px;">
                            <div style="font-weight: 700; color: #7dd3fc; font-size: 0.95rem;">🏭 {{ $sup->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-tertiary);">کۆدی دابینکەر: #SUP-{{ str_pad($sup->id, 4, '0', STR_PAD_LEFT) }}</div>
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <span class="badge badge-info" style="font-size: 0.8rem;">{{ $row['total_orders'] }} فەرمان</span>
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-family: var(--font-mono); color: #94a3b8; font-weight: 600;">
                            ${{ number_format($limit, 2) }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right;">
                            <strong style="color: {{ $debt > 0 ? '#f87171' : '#34d399' }}; font-size: 1.15rem; font-family: var(--font-mono);">
                                ${{ number_format($debt, 2) }}
                            </strong>
                            @if($debt > 0)
                                <div style="font-size: 0.7rem; color: #fca5a5;">~ {{ number_format($debt * 1520) }} IQD</div>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            @if($debt <= 0)
                                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.75rem;">سەلامەت (0 قەرز)</span>
                            @elseif($row['is_over_limit'])
                                <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; font-size: 0.75rem; font-weight:700;">⚠️ تێپەڕاندنی سەقف!</span>
                            @elseif($debt > ($limit * 0.75))
                                <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; font-size: 0.75rem;">نزیک سەقف</span>
                            @else
                                <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; font-size: 0.75rem;">قەرزی ئاسایی</span>
                            @endif
                        </td>
                        <td style="padding: 12px 14px; text-align: center;">
                            <div style="display: flex; gap: 0.4rem; justify-content: center; align-items: center; flex-wrap: wrap;">
                                <a href="{{ route('financial.statement.show', ['retailerId' => $ret->id, 'supplier_id' => $sup->id]) }}" 
                                   class="btn btn-sm" 
                                   style="background: #2563eb; color: #fff; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"
                                   title="بینینی کەشف حیسابی تەواو">
                                    <span>📑</span> کەشف حیساب
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-success" 
                                        style="font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.3rem;"
                                        onclick="openCollectPaymentModal({{ $ret->id }}, '{{ addslashes($ret->name) }}', {{ $debt }}, {{ $sup->id }})">
                                    <span>🧾</span> وەرگرتنی پارە
                                </button>
                                <button type="button" 
                                        class="btn btn-sm" 
                                        style="background: rgba(124, 58, 237, 0.15); color: #c4b5fd; border: 1px solid rgba(124, 58, 237, 0.3); font-weight: 600; padding: 0.35rem 0.6rem; border-radius: 6px;"
                                        onclick="openOpeningBalanceModal({{ $ret->id }}, '{{ addslashes($ret->name) }}', {{ $sup->id }})"
                                        title="تۆمارکردنی قەرزی کۆن پێش بەکارهێنانی سیستم">
                                    <span>⚖️</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-tertiary);">
                            هیچ پەیوەندی و قەرزێکی تۆمارکراو نەدۆزرایەوە.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function switchFinancialTab(tab) {
    const tabs = ['customers', 'matrix', 'vouchers', 'clearance'];
    tabs.forEach(t => {
        const content = document.getElementById('fin-tab-content-' + t);
        const btn = document.getElementById('btn-tab-fin-' + t);
        if (content) {
            content.style.display = (t === tab) ? 'block' : 'none';
        }
        if (btn) {
            if (t === tab) {
                btn.className = 'btn btn-primary';
                btn.style.background = '#4f46e5';
            } else {
                btn.className = 'btn';
                btn.style.background = 'rgba(255,255,255,0.06)';
            }
        }
    });
}

function filterMatrixTable() {
    const supFilter = document.getElementById('matrix-supplier-filter')?.value.toLowerCase().trim() || '';
    const q = document.getElementById('matrix-search-input')?.value.toLowerCase().trim() || '';
    const rows = document.querySelectorAll('.matrix-table-row');
    rows.forEach(r => {
        const rowSup = r.getAttribute('data-supplier') || '';
        const search = r.getAttribute('data-search') || '';
        const matchesSup = !supFilter || rowSup.includes(supFilter);
        const matchesText = !q || search.includes(q);
        r.style.display = (matchesSup && matchesText) ? '' : 'none';
    });
}
</script>
