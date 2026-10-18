<!-- ========================================================
     CASH VAN MANAGEMENT MODULE (DSD / DIRECT STORE DELIVERY)
========================================================= -->
<div id="cash-van-section" class="panel" style="border-right: 4px solid #f59e0b; background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(15, 23, 42, 0.45)); margin-top: 2rem;">
    <div class="panel-header" style="margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 class="panel-title" style="color: #fbbf24; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                🚐 {{ __('Cash Van Management') }}
            </h3>
            <p style="font-size: 0.84rem; color: var(--text-secondary); margin-top: 0.25rem;">
                سیستەمی گەڕۆک بۆ بەڕێوەبردنی سەیارەکان، کۆگای ناو ڤان، فرۆشتنی خێرا (Mobile POS)، هێڵی گەڕان و تەحاسوبی ئێواران.
            </p>
        </div>
        @if($role !== 'driver')
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button type="button" class="btn btn-warning" onclick="toggleCashVanModal('modal-add-van')" style="background: #f59e0b; color: #000; font-weight: 700;">
                + {{ __('Register New Van') }}
            </button>
        </div>
        @endif
    </div>

    <!-- CASH VAN FLEET METRICS -->
    <div class="grid-4" style="margin-bottom: 1.5rem;">
        <div class="metric-card" style="border-left: 3px solid #f59e0b;">
            <div class="label">{{ __('Vehicle Fleet') }}</div>
            <div class="val" style="color: #fbbf24;">{{ $cashVans->count() }}</div>
            <div class="sub">سەیارەی چالاکی دابەشکردن</div>
        </div>
        <div class="metric-card" style="border-left: 3px solid #10b981;">
            <div class="label">فرۆشتنی کاش ڤانی ئەمڕۆ</div>
            <div class="val" style="color: #34d399;">
                ${{ number_format($cashVanSales->where('created_at', '>=', now()->startOfDay())->sum('total_amount'), 2) }}
            </div>
            <div class="sub">{{ $cashVanSales->where('created_at', '>=', now()->startOfDay())->count() }} پسوولەی دەرکراو</div>
        </div>
        <div class="metric-card" style="border-left: 3px solid #38bdf8;">
            <div class="label">کاشی وەرگیراو (کۆکراوە)</div>
            <div class="val" style="color: #38bdf8;">
                ${{ number_format($cashVanSales->where('created_at', '>=', now()->startOfDay())->sum('paid_amount'), 2) }}
            </div>
            <div class="sub">کاشی دەستی مەندوبەکان</div>
        </div>
        <div class="metric-card" style="border-left: 3px solid #ec4899;">
            <div class="label">فرۆشراو بە قەرز (Receivables)</div>
            <div class="val" style="color: #f472b6;">
                ${{ number_format($cashVanSales->where('created_at', '>=', now()->startOfDay())->sum('balance_due'), 2) }}
            </div>
            <div class="sub">قەرزی نوێی سەر مارکێتەکان</div>
        </div>
    </div>

    <!-- TABS: 1. FLEET & INVENTORY | 2. MOBILE POS | 3. ROUTES & STOPS | 4. RECONCILIATION -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.5rem; overflow-x: auto;">
        <button type="button" class="btn btn-sm btn-van-tab active" onclick="switchVanSubTab('tab-fleet', this)" id="btn-tab-fleet" style="border-radius: 9999px;">
            🚐 سەیارەکان و کۆگای ناو ڤان ({{ $cashVans->count() }})
        </button>
        <button type="button" class="btn btn-sm btn-van-tab" onclick="switchVanSubTab('tab-pos', this)" id="btn-tab-pos" style="border-radius: 9999px;">
            🛒 {{ __('Mobile POS & Sales') }} (فرۆشتنی ڕاستەوخۆ)
        </button>
        <button type="button" class="btn btn-sm btn-van-tab" onclick="switchVanSubTab('tab-routes', this)" id="btn-tab-routes" style="border-radius: 9999px;">
            📍 {{ __('Daily Route & Stops') }} (هێڵی گەڕان)
        </button>
        <button type="button" class="btn btn-sm btn-van-tab" onclick="switchVanSubTab('tab-recon', this)" id="btn-tab-recon" style="border-radius: 9999px;">
            💰 {{ __('End-of-Day Reconciliation') }} (تەحاسوبی سندوق)
        </button>
        <button type="button" class="btn btn-sm btn-van-tab" onclick="switchVanSubTab('tab-sales-history', this)" id="btn-tab-sales-history" style="border-radius: 9999px;">
            📜 مێژووی وەسڵەکان و گەڕاندنەوە ({{ $cashVanSales->count() }})
        </button>
    </div>

    <!-- SUB-TAB 1: FLEET & INVENTORY -->
    <div id="tab-fleet" class="van-subtab-pane">
        @if($cashVans->isEmpty())
            <div style="text-align: center; padding: 2.5rem; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px dashed var(--border-subtle);">
                <p style="color: var(--text-tertiary); margin-bottom: 1rem;">هیچ سەیارەیەکی کاش ڤان تۆمار نەکراوە بۆ ئەم کۆمپانیایە.</p>
                @if($role !== 'driver')
                <button type="button" class="btn btn-warning" onclick="toggleCashVanModal('modal-add-van')">+ تۆمارکردنی یەکەم کاش ڤان</button>
                @endif
            </div>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1.25rem;">
                @foreach($cashVans as $van)
                    <div style="background: rgba(16, 20, 29, 0.85); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <div>
                                <h4 style="color: #fff; font-size: 1.05rem; display: flex; align-items: center; gap: 0.4rem;">
                                    🚐 {{ $van->vehicle_name }}
                                </h4>
                                <span style="font-family: var(--font-mono); font-size: 0.8rem; color: #fbbf24; background: rgba(245,158,11,0.15); padding: 0.15rem 0.5rem; border-radius: 4px; display: inline-block; margin-top: 0.25rem;">
                                    تابلۆ: {{ $van->plate_number }}
                                </span>
                            </div>
                            <span class="badge {{ $van->is_active ? 'badge-success' : 'badge-danger' }}">
                                {{ $van->is_active ? 'چالاکە' : 'ڕاگیراوە' }}
                            </span>
                        </div>

                        <div style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 0.75rem;">
                            <div>👤 <strong>شۆفێر / مەندوب:</strong> {{ $van->driver?->name ?? 'دیاری نەکراوە' }}</div>
                            <div>⚙️ <strong>شێوازی کارکردن:</strong> 
                                @if($van->operating_mode === 'direct_sale') فرۆشتنی ڕاستەوخۆ (Direct Sale)
                                @elseif($van->operating_mode === 'order_taking') وەرگرتنی داواکاری (Order Taking)
                                @else هایبرید (فرۆشتن + داواکاری)
                                @endif
                            </div>
                            <div>📦 <strong>کاڵای ناو سەیارە:</strong> {{ $van->inventories->count() }} جۆر (کۆی بڕ: {{ $van->inventories->sum('quantity') }})</div>
                        </div>

                        <!-- ON-BOARD STOCK LIST -->
                        <div style="background: rgba(0,0,0,0.3); border-radius: 6px; padding: 0.5rem; margin-bottom: 1rem; max-height: 140px; overflow-y: auto;">
                            <table style="width: 100%; font-size: 0.76rem; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--text-tertiary);">
                                        <th style="text-align: right; padding: 0.25rem;">کاڵا</th>
                                        <th style="text-align: center; padding: 0.25rem;">مەخزەنی ڤان</th>
                                        <th style="text-align: left; padding: 0.25rem;">نرخی کۆمەڵ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($van->inventories as $inv)
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                            <td style="padding: 0.25rem;">{{ $inv->product?->title }}</td>
                                            <td style="text-align: center; padding: 0.25rem; font-weight: bold; color: {{ $inv->quantity > 0 ? '#34d399' : '#f87171' }};">
                                                {{ $inv->quantity }} {{ $inv->product?->unit ?? 'دانە' }}
                                            </td>
                                            <td style="text-align: left; padding: 0.25rem; font-family: var(--font-mono);">${{ number_format($inv->product?->wholesale_price ?? 0, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 0.5rem; color: var(--text-tertiary);">سەیارەکە بەتاڵە؛ مەواد باربکە لە خوارەوە.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- ACTIONS: LOAD STOCK, RETURN STOCK, EXPORT CSV -->
                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openLoadStockModal({{ $van->id }}, '{{ $van->vehicle_name }}')" style="font-size: 0.75rem;">
                                📥 بارکردنی مەواد بۆ ڤان
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="openReturnStockModal({{ $van->id }}, '{{ $van->vehicle_name }}')" style="font-size: 0.75rem;">
                                📤 گەڕاندنەوە بۆ کۆگا
                            </button>
                            <a href="{{ route('cash_van.export.sales', $van->id) }}" class="btn btn-sm btn-neutral" style="font-size: 0.75rem; text-decoration: none;" title="دابەزاندنی CSV">
                                📊 CSV
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- SUB-TAB 2: MOBILE POS (DIRECT SALES & ORDER TAKING) -->
    <div id="tab-pos" class="van-subtab-pane" style="display: none;">
        <div style="background: rgba(16, 20, 29, 0.95); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.5rem; max-width: 900px; margin: 0 auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.75rem;">
                <h4 style="color: #fbbf24; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    🛒 ڕووکاری فرۆشتنی خێرای کاش ڤان (Mobile Van POS)
                </h4>
                <span class="badge badge-info">سەردانی مەیدانی مارکێتەکان</span>
            </div>

            @if($cashVans->isEmpty())
                <p style="color: #f87171;">تکایە سەرەتا سەیارەیەک تۆماربکە بۆ دەستپێکردنی فرۆشتن.</p>
            @else
                <form id="cash-van-pos-form" action="" method="POST" onsubmit="return handleVanSaleSubmit(event)">
                    @csrf
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.35rem;">سەیارەی کاش ڤان *</label>
                            <select id="pos-van-select" name="van_id" required onchange="updatePosVanStock(this.value)" style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                                @if($cashVans->count() > 1)
                                    <option value="">-- سەیارەکە هەڵبژێرە --</option>
                                @endif
                                @foreach($cashVans as $v)
                                    <option value="{{ $v->id }}" {{ ($loop->first || $v->driver_user_id == auth()->id()) ? 'selected' : '' }}>
                                        {{ $v->vehicle_name }} ({{ $v->plate_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.35rem;">مارکێت / کڕیار *</label>
                            <select id="pos-retailer-select" name="retailer_company_id" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                                <option value="">-- مارکێتەکە هەڵبژێرە --</option>
                                @foreach($retailers as $ret)
                                    <option value="{{ $ret->id }}">{{ $ret->name }} ({{ $ret->city }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.35rem;">شێوازی فرۆشتن *</label>
                            <select name="sale_type" id="pos-sale-type" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                                <option value="direct_sale">فرۆشتنی ڕاستەوخۆ لەناو سەیارەکە (Direct Van Sale)</option>
                                <option value="order_taking">وەرگرتنی داواکاری بۆ دواتر (Order Taking)</option>
                            </select>
                        </div>
                    </div>

                    <!-- POS ITEMS BUILDER -->
                    <div style="background: rgba(0,0,0,0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem;">
                        <h5 style="color: #a5b4fc; margin-bottom: 0.75rem; font-size: 0.9rem;">کاڵاکانی داواکراو بۆ ئەم وەسڵە:</h5>
                        <div id="pos-items-container">
                            <!-- Dynamic Row -->
                            <div class="pos-item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                                <select name="items[0][product_id]" class="pos-product-select" required onchange="handlePosProductChange(this)" style="padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; font-size: 0.82rem;">
                                    <option value="">-- کاڵا هەڵبژێرە --</option>
                                    @foreach($supplierProducts as $cp)
                                        <option value="{{ $cp->id }}" data-price="{{ $cp->base_wholesale_price ?? $cp->wholesale_price ?? 0 }}">{{ $cp->title }} (کۆگا: ${{ number_format($cp->base_wholesale_price ?? $cp->wholesale_price ?? 0, 2) }})</option>
                                    @endforeach
                                </select>
                                <input type="number" step="1" min="1" name="items[0][quantity]" value="1" placeholder="بڕ" required oninput="recalcPosTotals()" style="padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; font-size: 0.82rem; text-align: center;">
                                <input type="text" class="pos-line-price" readonly placeholder="نرخ" style="padding: 0.45rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border-subtle); color: #94a3b8; border-radius: 6px; font-size: 0.82rem; text-align: center;">
                                <input type="text" class="pos-line-subtotal" readonly placeholder="کۆ" style="padding: 0.45rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border-subtle); color: #34d399; font-weight: bold; border-radius: 6px; font-size: 0.82rem; text-align: center;">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removePosRow(this)" style="padding: 0.45rem 0.65rem;">✕</button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-neutral" onclick="addPosRow()" style="margin-top: 0.5rem;">+ زیادکردنی دێڕی تر</button>
                    </div>

                    <!-- FINANCIAL SETTLEMENT -->
                    <div style="background: rgba(0,0,0,0.2); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                            <div>
                                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">کۆی سەرەتایی (Subtotal)</label>
                                <input type="text" id="pos-subtotal" readonly value="$0.00" style="width: 100%; padding: 0.45rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border-subtle); color: #fff; font-family: var(--font-mono); font-weight: bold; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">داشکاندن ($)</label>
                                <input type="number" step="0.01" min="0" name="discount_amount" id="pos-discount" value="0.00" oninput="recalcPosTotals()" style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">کۆی گشتی (Total Amount)</label>
                                <input type="text" id="pos-total" readonly value="$0.00" style="width: 100%; padding: 0.45rem; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; font-size: 1.1rem; font-family: var(--font-mono); font-weight: bold; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">شێوازی پارەدان *</label>
                                <select name="payment_method" id="pos-payment-method" required onchange="handlePaymentMethodChange(this.value)" style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                                    <option value="cash">کاش (هەمووی درا)</option>
                                    <option value="credit">قەرز (نەدراوە)</option>
                                    <option value="split">بەشەکی (کاش + قەرز)</option>
                                </select>
                            </div>
                            <div id="pos-paid-wrapper" style="display: none;">
                                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">کاشی وەرگیراو ($)</label>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="pos-paid-amount" value="0.00" oninput="recalcPosTotals()" style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="submit" class="btn btn-warning" style="background: #f59e0b; color: #000; font-weight: bold; padding: 0.6rem 2rem; font-size: 1rem;">
                            ✓ دەرکردنی وەسڵ و فرۆشتن
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- SUB-TAB 3: DAILY ROUTES & STOPS -->
    <div id="tab-routes" class="van-subtab-pane" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="color: #38bdf8; margin: 0;">📍 هێڵەکانی گەڕانی مەندوب بۆ مارکێتەکان</h4>
            <button type="button" class="btn btn-sm btn-primary" onclick="toggleCashVanModal('modal-add-route')">+ دروستکردنی هێڵی نوێ</button>
        </div>

        @if($cashVanRoutes->isEmpty())
            <div style="text-align: center; padding: 2rem; background: rgba(0,0,0,0.2); border-radius: 8px;">
                <p style="color: var(--text-tertiary);">هیچ هێڵێکی گەڕان دیاری نەکراوە بۆ ئەم ڤانانە.</p>
            </div>
        @else
            <div style="display: grid; gap: 1rem;">
                @foreach($cashVanRoutes as $route)
                    <div style="background: rgba(16, 20, 29, 0.85); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <div>
                                <strong style="color: #fff; font-size: 0.95rem;">{{ $route->route_name }}</strong>
                                <span style="font-size: 0.8rem; color: var(--text-secondary); margin-right: 0.5rem;">
                                    (ڤان: {{ $route->van?->vehicle_name }} | شۆفێر: {{ $route->driver?->name ?? 'نادیار' }})
                                </span>
                            </div>
                            <span class="badge badge-info">{{ $route->scheduled_date->format('Y-m-d') }}</span>
                        </div>

                        <!-- STOPS -->
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                            @foreach($route->stops as $stop)
                                <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.06); padding: 0.45rem 0.75rem; border-radius: 6px; font-size: 0.8rem;">
                                    <span style="color: #38bdf8; font-weight: bold;">#{{ $stop->stop_order }}</span> {{ $stop->retailer?->name }}
                                    <span style="font-size: 0.72rem; color: var(--text-tertiary);">({{ $stop->retailer?->city }})</span>
                                    <span class="badge" style="font-size: 0.68rem; margin-right: 0.3rem; 
                                        @if($stop->status === 'sale_completed') background: rgba(16,185,129,0.2); color:#34d399;
                                        @elseif($stop->status === 'order_taken') background: rgba(56,189,248,0.2); color:#38bdf8;
                                        @else background: rgba(255,255,255,0.1); color:#94a3b8;
                                        @endif">
                                        {{ $stop->status }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- SUB-TAB 4: RECONCILIATION -->
    <div id="tab-recon" class="van-subtab-pane" style="display: none;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
            <!-- FORM: CLOSE SHIFT -->
            <div style="background: rgba(16, 20, 29, 0.95); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 1.25rem;">
                <h4 style="color: #fbbf24; margin-bottom: 0.75rem;">💰 تەحاسوبی ئێواران و پاکتاوی سندوق</h4>
                <p style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    بەراوردکردنی بڕی کاشی وەرگیراوی ڕۆژەکە لەگەڵ کۆی خەرجییەکان و کاشی پێشبینیکراو پێش گەڕانەوەی شۆفێر بۆ کۆگا.
                </p>

                <form id="reconcile-shift-form" action="" method="POST" onsubmit="return handleReconcileSubmit(event)">
                    @csrf
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">کاش ڤان *</label>
                        <select name="van_id" id="recon-van-select" required style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                            <option value="">-- سەیارەکە هەڵبژێرە --</option>
                            @foreach($cashVans as $v)
                                <option value="{{ $v->id }}">{{ $v->vehicle_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">کاشی دەستپێک (Opening Cash) *</label>
                        <input type="number" step="0.01" min="0" name="opening_cash" id="recon-opening-cash" value="0.00" required style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">کاشی ڕادەستکراوی ڕاستەقینە (Actual Cash Handed) *</label>
                        <input type="number" step="0.01" min="0" name="actual_cash_handed" id="recon-actual-cash" value="0.00" required style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">تێبینی</label>
                        <textarea name="notes" placeholder="هەر تێبینییەک لەسەر جیاوازی یان سندوق..." rows="2" style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning" style="width: 100%; background: #f59e0b; color: #000; font-weight: bold;">
                        ✓ تۆمارکردنی تەحاسوب
                    </button>
                </form>
            </div>

            <!-- EXPENSE LOGGER -->
            <div style="background: rgba(16, 20, 29, 0.95); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 1.25rem;">
                <h4 style="color: #ec4899; margin-bottom: 0.75rem;">⛽ تۆمارکردنی خەرجییەکانی ڕێگا</h4>
                <p style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    بەنزین، چاککردنەوە، یان خەرجی ڕۆژانەی سەیارەکە لە پارەی کاشی سندوقەکە کەمدەکرێتەوە.
                </p>

                <form id="expense-form" action="" method="POST" onsubmit="return handleExpenseSubmit(event)">
                    @csrf
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">کاش ڤان *</label>
                        <select name="van_id" id="exp-van-select" required style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                            <option value="">-- سەیارەکە هەڵبژێرە --</option>
                            @foreach($cashVans as $v)
                                <option value="{{ $v->id }}">{{ $v->vehicle_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">جۆری خەرجی *</label>
                        <select name="expense_type" required style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                            <option value="fuel">بەنزین / سووتەمەنی (Fuel)</option>
                            <option value="maintenance">چاککردنەوە و پەنچەر (Maintenance)</option>
                            <option value="toll">پارەی ڕێگا / گومرگ (Toll)</option>
                            <option value="food">خواردنی مەندوب (Food)</option>
                            <option value="other">خەرجی تر (Other)</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">بڕ ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="بۆ نموونە: 25.00" style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">تێبینی</label>
                        <input type="text" name="notes" placeholder="وەسڵی بەنزینخانە..." style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    </div>
                    <button type="submit" class="btn btn-secondary" style="width: 100%; font-weight: bold;">
                        + تۆمارکردنی خەرجی
                    </button>
                </form>
            </div>
        </div>

        <!-- RECENT RECONCILIATIONS TABLE -->
        <div style="margin-top: 1.5rem;">
            <h5 style="color: #fff; margin-bottom: 0.5rem;">تەحاسوبە تۆمارکراوەکانی پێشوو:</h5>
            <table style="width: 100%; font-size: 0.82rem; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-subtle); color: var(--text-tertiary);">
                        <th style="padding: 0.5rem; text-align: right;">ژمارەی تەحاسوب</th>
                        <th style="padding: 0.5rem; text-align: right;">ڤان</th>
                        <th style="padding: 0.5rem; text-align: center;">بەروار</th>
                        <th style="padding: 0.5rem; text-align: left;">فرۆشی کاش</th>
                        <th style="padding: 0.5rem; text-align: left;">خەرجی</th>
                        <th style="padding: 0.5rem; text-align: left;">کاشی پێشبینیکراو</th>
                        <th style="padding: 0.5rem; text-align: left;">کاشی ڕادەستکراو</th>
                        <th style="padding: 0.5rem; text-align: left;">جیاوازی</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashVanReconciliations as $rec)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.5rem; font-family: var(--font-mono); color: #fbbf24;">{{ $rec->reconciliation_number }}</td>
                            <td style="padding: 0.5rem;">{{ $rec->van?->vehicle_name }}</td>
                            <td style="padding: 0.5rem; text-align: center;">{{ $rec->shift_date->format('Y-m-d') }}</td>
                            <td style="padding: 0.5rem; text-align: left; font-family: var(--font-mono); color: #34d399;">${{ number_format($rec->total_cash_sales, 2) }}</td>
                            <td style="padding: 0.5rem; text-align: left; font-family: var(--font-mono); color: #f87171;">${{ number_format($rec->total_expenses, 2) }}</td>
                            <td style="padding: 0.5rem; text-align: left; font-family: var(--font-mono);">${{ number_format($rec->expected_cash, 2) }}</td>
                            <td style="padding: 0.5rem; text-align: left; font-family: var(--font-mono); font-weight: bold; color: #fff;">${{ number_format($rec->actual_cash_handed, 2) }}</td>
                            <td style="padding: 0.5rem; text-align: left; font-family: var(--font-mono); font-weight: bold; color: {{ $rec->cash_discrepancy == 0 ? '#34d399' : '#f87171' }};">
                                ${{ number_format($rec->cash_discrepancy, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 1.5rem; color: var(--text-tertiary);">هیچ تەحاسوبێکی پێشوو تۆمار نەکراوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SUB-TAB 5: SALES HISTORY & RETURNS -->
    <div id="tab-sales-history" class="van-subtab-pane" style="display: none;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; font-size: 0.82rem; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-subtle); color: var(--text-tertiary);">
                        <th style="padding: 0.5rem; text-align: right;">ژمارەی وەسڵ</th>
                        <th style="padding: 0.5rem; text-align: right;">سەیارە / مەندوب</th>
                        <th style="padding: 0.5rem; text-align: right;">مارکێت</th>
                        <th style="padding: 0.5rem; text-align: center;">بەروار</th>
                        <th style="padding: 0.5rem; text-align: left;">کۆی گشتی</th>
                        <th style="padding: 0.5rem; text-align: center;">شێواز</th>
                        <th style="padding: 0.5rem; text-align: left;">دراو</th>
                        <th style="padding: 0.5rem; text-align: left;">قەرز</th>
                        <th style="padding: 0.5rem; text-align: center;">کردار</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashVanSales as $s)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.5rem; font-family: var(--font-mono); color: #fbbf24;">{{ $s->sale_number }}</td>
                            <td style="padding: 0.5rem;">{{ $s->van?->vehicle_name }} ({{ $s->driver?->name ?? 'مەندوب' }})</td>
                            <td style="padding: 0.5rem; font-weight: bold;">{{ $s->retailer?->name }}</td>
                            <td style="padding: 0.5rem; text-align: center;">{{ $s->created_at->format('Y-m-d H:i') }}</td>
                            <td style="padding: 0.5rem; text-align: left; font-family: var(--font-mono); font-weight: bold; color: #fff;">${{ number_format($s->total_amount, 2) }}</td>
                            <td style="padding: 0.5rem; text-align: center;">
                                <span class="badge" style="
                                    @if($s->payment_method === 'cash') background: rgba(16,185,129,0.2); color:#34d399;
                                    @elseif($s->payment_method === 'credit') background: rgba(236,72,153,0.2); color:#f472b6;
                                    @else background: rgba(56,189,248,0.2); color:#38bdf8;
                                    @endif">
                                    {{ $s->payment_method }}
                                </span>
                            </td>
                            <td style="padding: 0.5rem; text-align: left; font-family: var(--font-mono); color: #34d399;">${{ number_format($s->paid_amount, 2) }}</td>
                            <td style="padding: 0.5rem; text-align: left; font-family: var(--font-mono); color: #f87171;">${{ number_format($s->balance_due, 2) }}</td>
                            <td style="padding: 0.5rem; text-align: center;">
                                <button type="button" class="btn btn-sm btn-neutral" onclick="openThermalReceiptModal('{{ $s->sale_number }}', '{{ $s->retailer?->name }}', '{{ $s->total_amount }}', '{{ $s->payment_method }}', '{{ $s->paid_amount }}', '{{ $s->balance_due }}')" style="font-size: 0.72rem;">
                                    🖨️ پسوولە
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="openReturnModal({{ $s->id }}, '{{ $s->sale_number }}')" style="font-size: 0.72rem;">
                                    ↩️ گەڕاندنەوە
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 1.5rem; color: var(--text-tertiary);">هیچ فرۆشتنێکی کاش ڤان تۆمار نەکراوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================
     MODALS FOR CASH VAN OPERATIONS
========================================================= -->

<!-- MODAL: ADD VAN -->
@if($role !== 'driver')
<div id="modal-add-van" class="b2b-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #141a26; border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.5rem; width: 95%; max-width: 480px;">
        <h4 style="color: #fbbf24; margin-bottom: 1rem;">🚐 تۆمارکردنی سەیارەی نوێی کاش ڤان</h4>
        <form action="{{ route('cash_van.vehicles.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">ناوی سەیارە (بۆ نموونە: ڤانی ژمارە ١ - سلێمانی) *</label>
                <input type="text" name="vehicle_name" required placeholder="ڤانی دابەشکردن" style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
            </div>
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">ژمارەی تابلۆ *</label>
                <input type="text" name="plate_number" required placeholder="23 A 12345" style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
            </div>
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">مۆدێل / جۆری سەیارە</label>
                <input type="text" name="model" placeholder="Toyota HiAce 2024" style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
            </div>
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">شۆفێر / مەندوبی دیاریکراو</label>
                <select name="driver_user_id" style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    <option value="">-- دیاری نەکراوە --</option>
                    @foreach($cashVanDrivers as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->email }})</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">شێوازی کارکردن *</label>
                <select name="operating_mode" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    <option value="hybrid">هایبرید (فرۆشتنی ڕاستەوخۆ + وەرگرتنی داواکاری)</option>
                    <option value="direct_sale">تەنها فرۆشتنی ڕاستەوخۆ (Direct Sale)</option>
                    <option value="order_taking">تەنها وەرگرتنی داواکاری (Order Taking)</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-neutral" onclick="toggleCashVanModal('modal-add-van')">پەشیمانبوونەوە</button>
                <button type="submit" class="btn btn-warning" style="background: #f59e0b; color: #000; font-weight: bold;">تۆمارکردن</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- MODAL: LOAD STOCK TO VAN -->
<div id="modal-load-stock" class="b2b-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #141a26; border: 1px solid var(--border-subtle); border-radius: 14px; padding: 1.5rem; width: 95%; max-width: 620px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <h4 style="color: #38bdf8; margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                📥 بارکردنی مەواد بۆ ناو سەیارە
            </h4>
            <span id="load-stock-van-title" style="font-size: 0.82rem; color: #fbbf24; background: rgba(245,158,11,0.15); padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 700;"></span>
        </div>
        <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1rem;">
            دەتوانیت بە یەک جار چەندین کاڵا لە کۆگای سەرەکی کۆمپانیاکەت باربکەیتە سەر سەیارەکە بەبێ داخرانی لیستەکە.
        </p>

        <form id="load-stock-form" action="" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label style="font-size: 0.82rem; color: #e2e8f0; font-weight: 700;">لیستی کاڵاکان بۆ بارکردن:</label>
                    <button type="button" class="btn btn-sm" onclick="addLoadStockRow()" style="background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.4); color: #38bdf8; font-size: 0.78rem; font-weight: 700; border-radius: 6px; cursor: pointer; padding: 0.25rem 0.65rem;">
                        + زیادکردنی کاڵای تر
                    </button>
                </div>

                <div id="load-stock-items-container" style="max-height: 250px; overflow-y: auto; padding-right: 0.25rem;">
                    <div class="load-stock-item-row" style="display: grid; grid-template-columns: 2.3fr 1.1fr auto; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; background: rgba(0,0,0,0.25); padding: 0.45rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);">
                        <select name="items[0][product_id]" required style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; font-size: 0.82rem;">
                            <option value="">-- کاڵای کۆگا هەڵبژێرە --</option>
                            @foreach($supplierProducts as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->title }} (کۆگای سەرەکی: {{ $sp->current_stock }} {{ $sp->unit ?? 'دانە' }})</option>
                            @endforeach
                        </select>
                        <input type="number" step="1" min="1" name="items[0][quantity]" required placeholder="بڕ" style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; font-size: 0.82rem; text-align: center;">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeLoadStockRow(this)" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" title="سڕینەوە">✕</button>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">تێبینی بارکردن (ئارەزوومەندانە)</label>
                <input type="text" name="notes" placeholder="بۆ نموونە: بارکردنی بەیانیان بۆ گەڕی بازاڕ..." style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; font-size: 0.85rem;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-neutral" onclick="toggleCashVanModal('modal-load-stock')">پەشیمانبوونەوە</button>
                <button type="submit" class="btn btn-primary" style="font-weight: 700;">✓ بارکردنی هەموو کاڵاکان بۆ ڤان</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: RETURN STOCK TO WAREHOUSE -->
<div id="modal-return-stock" class="b2b-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #141a26; border: 1px solid var(--border-subtle); border-radius: 14px; padding: 1.5rem; width: 95%; max-width: 620px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <h4 style="color: #ec4899; margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                📤 گەڕاندنەوەی مەوادی ماوە بۆ کۆگای سەرەکی
            </h4>
            <span id="return-stock-van-title" style="font-size: 0.82rem; color: #fbbf24; background: rgba(245,158,11,0.15); padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 700;"></span>
        </div>
        <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1rem;">
            دەتوانیت کاڵاکانی نەفرۆشراو یان ماوەی ناو سەیارەکە بگەڕێنیتەوە بۆ کۆگای سەرەکی بەبێ داخرانی لیست.
        </p>

        <form id="return-stock-form" action="" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label style="font-size: 0.82rem; color: #e2e8f0; font-weight: 700;">لیستی کاڵاکان بۆ گەڕاندنەوە:</label>
                    <button type="button" class="btn btn-sm" onclick="addReturnStockRow()" style="background: rgba(236, 72, 153, 0.15); border: 1px solid rgba(236, 72, 153, 0.4); color: #ec4899; font-size: 0.78rem; font-weight: 700; border-radius: 6px; cursor: pointer; padding: 0.25rem 0.65rem;">
                        + زیادکردنی کاڵای تر
                    </button>
                </div>

                <div id="return-stock-items-container" style="max-height: 250px; overflow-y: auto; padding-right: 0.25rem;">
                    <div class="return-stock-item-row" style="display: grid; grid-template-columns: 2.3fr 1.1fr auto; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; background: rgba(0,0,0,0.25); padding: 0.45rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);">
                        <select name="items[0][product_id]" required style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; font-size: 0.82rem;">
                            <option value="">-- کاڵا هەڵبژێرە --</option>
                            @foreach($supplierProducts as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->title }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="1" min="1" name="items[0][quantity]" required placeholder="بڕ" style="width: 100%; padding: 0.45rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; font-size: 0.82rem; text-align: center;">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeReturnStockRow(this)" style="padding: 0.4rem 0.6rem; font-size: 0.8rem;" title="سڕینەوە">✕</button>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">تێبینی گەڕاندنەوە (ئارەزوومەندانە)</label>
                <input type="text" name="notes" placeholder="بۆ نموونە: تەواوبوونی شیفت یان گەڕانەوەی ئێوارە..." style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; font-size: 0.85rem;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-neutral" onclick="toggleCashVanModal('modal-return-stock')">پەشیمانبوونەوە</button>
                <button type="submit" class="btn btn-danger" style="font-weight: 700; background: #ec4899; color: #fff; border: none;">✓ گەڕاندنەوە بۆ کۆگا</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: THERMAL RECEIPT PREVIEW (PRINTABLE) -->
<div id="modal-thermal-receipt" class="b2b-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; color: #000; font-family: monospace; border-radius: 6px; padding: 1.5rem; width: 90%; max-width: 320px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: bold;">MarketLink B2B</h3>
            <p style="margin: 0.2rem 0; font-size: 0.8rem;">وەسڵی فرۆشتنی کاش ڤان</p>
            <p id="receipt-sale-number" style="margin: 0; font-size: 0.75rem; font-weight: bold;"></p>
        </div>
        <div style="font-size: 0.8rem; margin-bottom: 0.75rem; line-height: 1.4;">
            <div>کڕیار: <span id="receipt-retailer-name"></span></div>
            <div>بەروار: {{ now()->format('Y-m-d H:i') }}</div>
            <div>شێواز: <span id="receipt-payment-method"></span></div>
        </div>
        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 0.5rem 0; margin-bottom: 0.75rem; font-size: 0.85rem;">
            <div style="display: flex; justify-content: space-between;">
                <strong>کۆی گشتی:</strong>
                <span id="receipt-total" style="font-weight: bold;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span>دراو:</span>
                <span id="receipt-paid"></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span>قەرزی ماوە:</span>
                <span id="receipt-balance"></span>
            </div>
        </div>
        <div style="text-align: center; font-size: 0.75rem; margin-bottom: 1rem;">
            سوپاس بۆ مامەڵەکەتان لەگەڵ ئێمە!
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" onclick="window.print()" style="flex: 1; padding: 0.4rem; background: #000; color: #fff; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">🖨️ چاپکردن</button>
            <button type="button" onclick="toggleCashVanModal('modal-thermal-receipt')" style="flex: 1; padding: 0.4rem; background: #ccc; color: #000; border: none; cursor: pointer; border-radius: 4px;">داخستن</button>
        </div>
    </div>
</div>

<!-- MODAL: ADD ROUTE -->
<div id="modal-add-route" class="b2b-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #141a26; border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.5rem; width: 95%; max-width: 520px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="color: #38bdf8; margin: 0; display: flex; align-items: center; gap: 0.4rem;">
                📍 دروستکردنی هێڵی نوێی گەڕان (Add Route)
            </h4>
            <button type="button" onclick="toggleCashVanModal('modal-add-route')" style="background: transparent; border: none; color: var(--text-secondary); font-size: 1.4rem; cursor: pointer;">&times;</button>
        </div>
        <form id="add-route-form" action="" method="POST" onsubmit="return handleRouteSubmit(event)">
            @csrf
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">سەیارەی کاش ڤان *</label>
                <select name="van_id" id="route-van-select" required onchange="updateRouteFormAction(this.value)" style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    <option value="">-- سەیارەکە هەڵبژێرە --</option>
                    @foreach($cashVans as $v)
                        <option value="{{ $v->id }}">{{ $v->vehicle_name }} ({{ $v->plate_number }})</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">ناوی هێڵ (بۆ نموونە: گەڕانی بازاڕی بەختیاری) *</label>
                <input type="text" name="route_name" required placeholder="ناوی هێڵی گەڕان" style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
            </div>
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">بەرواری گەڕان *</label>
                <input type="date" name="scheduled_date" value="{{ date('Y-m-d') }}" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
            </div>
            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.35rem;">مارکێتەکانی ئەم هێڵە (دەتوانیت چەند دانەیەک دیاری بکەیت) *</label>
                <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-subtle); border-radius: 6px; padding: 0.75rem; max-height: 180px; overflow-y: auto;">
                    @forelse($retailers as $ret)
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0; font-size: 0.82rem; color: #fff; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.04);">
                            <input type="checkbox" name="retailer_ids[]" value="{{ $ret->id }}">
                            <span>🛒 {{ $ret->name }} <span style="font-size: 0.72rem; color: var(--text-tertiary);">({{ $ret->city }} - {{ $ret->address_line }})</span></span>
                        </label>
                    @empty
                        <div style="color: var(--text-tertiary); font-size: 0.8rem;">هیچ مارکێتێک بەردەست نییە.</div>
                    @endforelse
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-neutral" onclick="toggleCashVanModal('modal-add-route')">پەشیمانبوونەوە</button>
                <button type="submit" class="btn btn-primary" style="background: #38bdf8; border-color: #38bdf8; color: #000; font-weight: bold;">
                    ✓ دروستکردنی هێڵی گەڕان
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: RETURN SALE -->
<div id="modal-return-sale" class="b2b-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #141a26; border: 1px solid var(--border-subtle); border-radius: 12px; padding: 1.5rem; width: 95%; max-width: 500px;">
        <h4 style="color: #f87171; margin-bottom: 0.5rem;">↩️ گەڕاندنەوەی کاڵای فرۆشراو (Sales Return)</h4>
        <p id="return-sale-title" style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 1rem;"></p>
        <form id="return-sale-form" action="" method="POST">
            @csrf
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">کاڵای گەڕاوە *</label>
                <select name="items[0][product_id]" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    @foreach($supplierProducts as $cp)
                        <option value="{{ $cp->id }}">{{ $cp->title }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">بڕی گەڕێنراوە *</label>
                <input type="number" step="1" min="1" name="items[0][quantity]" value="1" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
            </div>
            <div style="margin-bottom: 0.75rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">شوێنی خستنەوەی کاڵا *</label>
                <select name="restock_destination" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
                    <option value="van">بگەڕێتەوە ناو سەیارەکە (Restock to Van)</option>
                    <option value="warehouse">بگەڕێتەوە کۆگای سەرەکی (Restock to Warehouse)</option>
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary); display: block; margin-bottom: 0.25rem;">هۆکاری گەڕاندنەوە *</label>
                <input type="text" name="return_reason" required placeholder="بەرواری بەسەرچوون، زیان، یان داواکاری کڕیار..." style="width: 100%; padding: 0.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-neutral" onclick="toggleCashVanModal('modal-return-sale')">پەشیمانبوونەوە</button>
                <button type="submit" class="btn btn-danger">✓ تۆمارکردنی گەڕاندنەوە</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleCashVanModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        const isHidden = modal.style.display === 'none' || modal.style.display === '';
        modal.style.display = isHidden ? 'flex' : 'none';
    }

    function switchVanSubTab(tabId, btn) {
        document.querySelectorAll('.van-subtab-pane').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.btn-van-tab').forEach(el => {
            el.classList.remove('active');
            el.style.background = 'transparent';
            el.style.color = 'var(--text-secondary)';
        });
        const target = document.getElementById(tabId);
        if (target) target.style.display = 'block';
        if (btn) {
            btn.classList.add('active');
            btn.style.background = '#f59e0b';
            btn.style.color = '#000';
            btn.style.fontWeight = 'bold';
        }
    }

    function openLoadStockModal(vanId, vanName) {
        const form = document.getElementById('load-stock-form');
        form.action = `/cash-van/${vanId}/load-stock`;
        document.getElementById('load-stock-van-title').innerText = `بۆ سەیارەی: ${vanName}`;
        toggleCashVanModal('modal-load-stock');
    }

    function openReturnStockModal(vanId, vanName) {
        const form = document.getElementById('return-stock-form');
        form.action = `/cash-van/${vanId}/return-stock`;
        document.getElementById('return-stock-van-title').innerText = `لە سەیارەی: ${vanName}`;
        toggleCashVanModal('modal-return-stock');
    }

    function openThermalReceiptModal(saleNum, retName, total, method, paid, balance) {
        document.getElementById('receipt-sale-number').innerText = saleNum;
        document.getElementById('receipt-retailer-name').innerText = retName;
        document.getElementById('receipt-total').innerText = '$' + parseFloat(total).toFixed(2);
        document.getElementById('receipt-payment-method').innerText = method;
        document.getElementById('receipt-paid').innerText = '$' + parseFloat(paid).toFixed(2);
        document.getElementById('receipt-balance').innerText = '$' + parseFloat(balance).toFixed(2);
        toggleCashVanModal('modal-thermal-receipt');
    }

    function handlePosProductChange(select) {
        const row = select.closest('.pos-item-row');
        const opt = select.options[select.selectedIndex];
        const price = parseFloat(opt.getAttribute('data-price') || 0);
        row.querySelector('.pos-line-price').value = '$' + price.toFixed(2);
        recalcPosTotals();
    }

    function recalcPosTotals() {
        let subtotal = 0;
        document.querySelectorAll('.pos-item-row').forEach(row => {
            const select = row.querySelector('.pos-product-select');
            const qtyInput = row.querySelector('input[type="number"]');
            const subtotalInput = row.querySelector('.pos-line-subtotal');
            const opt = select.options[select.selectedIndex];
            const price = parseFloat(opt ? opt.getAttribute('data-price') || 0 : 0);
            const qty = parseFloat(qtyInput.value || 0);
            const lineTotal = price * qty;
            subtotalInput.value = '$' + lineTotal.toFixed(2);
            subtotal += lineTotal;
        });

        document.getElementById('pos-subtotal').value = '$' + subtotal.toFixed(2);
        const discount = parseFloat(document.getElementById('pos-discount').value || 0);
        const total = Math.max(0, subtotal - discount);
        document.getElementById('pos-total').value = '$' + total.toFixed(2);

        const method = document.getElementById('pos-payment-method').value;
        if (method === 'cash') {
            document.getElementById('pos-paid-amount').value = total.toFixed(2);
        } else if (method === 'credit') {
            document.getElementById('pos-paid-amount').value = '0.00';
        }
    }

    function handlePaymentMethodChange(val) {
        const wrapper = document.getElementById('pos-paid-wrapper');
        if (val === 'split') {
            wrapper.style.display = 'block';
        } else {
            wrapper.style.display = 'none';
        }
        recalcPosTotals();
    }

    function addPosRow() {
        const container = document.getElementById('pos-items-container');
        const count = container.querySelectorAll('.pos-item-row').length;
        const firstRow = container.querySelector('.pos-item-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('.pos-product-select').name = `items[${count}][product_id]`;
        clone.querySelector('input[type="number"]').name = `items[${count}][quantity]`;
        clone.querySelector('input[type="number"]').value = '1';
        clone.querySelector('.pos-line-price').value = '';
        clone.querySelector('.pos-line-subtotal').value = '';
        container.appendChild(clone);
    }

    function removePosRow(btn) {
        const container = document.getElementById('pos-items-container');
        if (container.querySelectorAll('.pos-item-row').length > 1) {
            btn.closest('.pos-item-row').remove();
            recalcPosTotals();
        }
    }

    function updatePosVanStock(vanId) {
        const form = document.getElementById('cash-van-pos-form');
        if (vanId) {
            form.action = `/cash-van/${vanId}/sales`;
        }
    }

    function handleVanSaleSubmit(e) {
        const vanSelect = document.getElementById('pos-van-select');
        if (!vanSelect.value) {
            alert('تکایە سەرەتا سەیارەی کاش ڤان هەڵبژێرە.');
            e.preventDefault();
            return false;
        }
        document.getElementById('cash-van-pos-form').action = `/cash-van/${vanSelect.value}/sales`;
        return true;
    }

    function handleReconcileSubmit(e) {
        const vanSelect = document.getElementById('recon-van-select');
        if (!vanSelect.value) {
            alert('تکایە سەیارەی کاش ڤان هەڵبژێرە.');
            e.preventDefault();
            return false;
        }
        document.getElementById('reconcile-shift-form').action = `/cash-van/${vanSelect.value}/reconcile`;
        return true;
    }

    function handleExpenseSubmit(e) {
        const vanSelect = document.getElementById('exp-van-select');
        if (!vanSelect.value) {
            alert('تکایە سەیارەی کاش ڤان هەڵبژێرە.');
            e.preventDefault();
            return false;
        }
        document.getElementById('expense-form').action = `/cash-van/${vanSelect.value}/expenses`;
        return true;
    }

    function updateRouteFormAction(vanId) {
        const form = document.getElementById('add-route-form');
        if (vanId) {
            form.action = `/cash-van/${vanId}/routes`;
        }
    }

    function handleRouteSubmit(e) {
        const vanSelect = document.getElementById('route-van-select');
        if (!vanSelect.value) {
            alert('تکایە سەرەتا سەیارەی کاش ڤان هەڵبژێرە.');
            e.preventDefault();
            return false;
        }
        const checked = document.querySelectorAll('input[name="retailer_ids[]"]:checked');
        if (checked.length === 0) {
            alert('تکایە لانیکەم یەک مارکێت هەڵبژێرە بۆ ئەم هێڵە.');
            e.preventDefault();
            return false;
        }
        document.getElementById('add-route-form').action = `/cash-van/${vanSelect.value}/routes`;
        return true;
    }

    function openReturnModal(saleId, saleNum) {
        const form = document.getElementById('return-sale-form');
        form.action = `/cash-van/sales/${saleId}/returns`;
        document.getElementById('return-sale-title').innerText = `بۆ وەسڵی فرۆشتنی: ${saleNum}`;
        toggleCashVanModal('modal-return-sale');
    }

    function addLoadStockRow() {
        const container = document.getElementById('load-stock-items-container');
        const count = container.querySelectorAll('.load-stock-item-row').length;
        const firstRow = container.querySelector('.load-stock-item-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('select').name = `items[${count}][product_id]`;
        clone.querySelector('select').selectedIndex = 0;
        clone.querySelector('input[type="number"]').name = `items[${count}][quantity]`;
        clone.querySelector('input[type="number"]').value = '';
        container.appendChild(clone);
    }

    function removeLoadStockRow(btn) {
        const container = document.getElementById('load-stock-items-container');
        if (container.querySelectorAll('.load-stock-item-row').length > 1) {
            btn.closest('.load-stock-item-row').remove();
        }
    }

    function addReturnStockRow() {
        const container = document.getElementById('return-stock-items-container');
        const count = container.querySelectorAll('.return-stock-item-row').length;
        const firstRow = container.querySelector('.return-stock-item-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('select').name = `items[${count}][product_id]`;
        clone.querySelector('select').selectedIndex = 0;
        clone.querySelector('input[type="number"]').name = `items[${count}][quantity]`;
        clone.querySelector('input[type="number"]').value = '';
        container.appendChild(clone);
    }

    function removeReturnStockRow(btn) {
        const container = document.getElementById('return-stock-items-container');
        if (container.querySelectorAll('.return-stock-item-row').length > 1) {
            btn.closest('.return-stock-item-row').remove();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const vanSelect = document.getElementById('pos-van-select');
        if (vanSelect && vanSelect.value) {
            updatePosVanStock(vanSelect.value);
        }
    });
</script>
