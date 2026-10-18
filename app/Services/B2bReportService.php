<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Company;
use Symfony\Component\HttpFoundation\StreamedResponse;

class B2bReportService
{
    /**
     * Export Purchase Orders to CSV with UTF-8 BOM for Microsoft Excel compatibility
     */
    public function exportOrdersCsv(?int $supplierId = null, ?int $retailerId = null, ?string $status = null): StreamedResponse
    {
        $fileName = 'marketlink_orders_' . date('Y_m_d_His') . '.csv';

        $query = PurchaseOrder::with(['supplier', 'retailer', 'items'])->latest();

        if ($supplierId) {
            $query->where('supplier_company_id', $supplierId);
        }
        if ($retailerId) {
            $query->where('retailer_company_id', $retailerId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM to ensure Arabic and Kurdish characters display correctly in Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header Row
            fputcsv($handle, [
                'ژمارەی فەرمان (PO Number)',
                'ژمارەی پسوولەی بارکردن (Waybill)',
                'دابینکەر (Supplier)',
                'کڕیار / مارکێت (Retailer)',
                'دۆخی داواکاری (Status)',
                'کۆی بڕ بە دۆلار ($)',
                'بڕی پارەی دراو ($)',
                'قەرزی ماوە ($)',
                'دۆخی پارەدان (Payment Status)',
                'مەرجی پارەدان (Payment Terms)',
                'بەرواری دروستکردن (Created At)',
                'بەرواری گەیشتن (Delivered At)',
            ]);

            $query->chunk(100, function ($orders) use ($handle) {
                foreach ($orders as $po) {
                    $statusLabel = match ($po->status) {
                        'pending' => 'چاوەڕوان',
                        'approved' => 'پەسەندکراو',
                        'processing' => 'ئامادەکردن',
                        'dispatched' => 'لە ڕێگایە',
                        'delivered' => 'گەیەندراوە',
                        'completed' => 'تەواوبوو',
                        'cancelled' => 'هەڵوەشاوە',
                        default => $po->status,
                    };

                    fputcsv($handle, [
                        $po->po_number,
                        $po->waybill_number ?? '-',
                        $po->supplier?->name ?? 'دابینکەر',
                        $po->retailer?->name ?? 'مارکێت',
                        $statusLabel,
                        number_format($po->total_amount, 2, '.', ''),
                        number_format($po->paid_amount, 2, '.', ''),
                        number_format($po->remaining_debt, 2, '.', ''),
                        $po->payment_status,
                        $po->payment_terms ?? 'دیارینەکراوە',
                        $po->created_at->format('Y-m-d H:i'),
                        $po->received_at ? $po->received_at->format('Y-m-d H:i') : '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Financial Account Statement (کەشف حیساب) for a specific Company
     */
    public function exportAccountStatementCsv(int $companyId): StreamedResponse
    {
        $company = Company::findOrFail($companyId);
        $fileName = 'statement_' . $company->id . '_' . date('Y_m_d') . '.csv';

        $isSupplier = $company->business_type === 'supplier';
        $query = PurchaseOrder::with(['supplier', 'retailer'])
            ->where($isSupplier ? 'supplier_company_id' : 'retailer_company_id', $companyId)
            ->latest();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query, $company, $isSupplier) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['کەشف حیسابی فەرمی — ' . $company->name]);
            fputcsv($handle, ['جۆری کۆمپانیا: ' . ($isSupplier ? 'دابینکەری کۆمەڵ' : 'مارکێت / فرۆشیاری تاک')]);
            fputcsv($handle, ['بەرواری دەرچوون: ' . date('Y-m-d H:i')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'ژمارەی فەرمان',
                $isSupplier ? 'مارکێتی پەیوەندیدار' : 'دابینکەری پەیوەندیدار',
                'بەروار',
                'کۆی بڕ ($)',
                'پارەی دراو ($)',
                'قەرزی ماوە ($)',
                'دۆخی داواکاری',
            ]);

            $totalSum = 0;
            $totalPaid = 0;
            $totalDebt = 0;

            foreach ($query->get() as $po) {
                $totalSum += (float)$po->total_amount;
                $totalPaid += (float)$po->paid_amount;
                $totalDebt += (float)$po->remaining_debt;

                fputcsv($handle, [
                    $po->po_number,
                    $isSupplier ? ($po->retailer?->name ?? '-') : ($po->supplier?->name ?? '-'),
                    $po->created_at->format('Y-m-d'),
                    number_format($po->total_amount, 2, '.', ''),
                    number_format($po->paid_amount, 2, '.', ''),
                    number_format($po->remaining_debt, 2, '.', ''),
                    $po->status,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'کۆی گشتی:',
                '',
                '',
                number_format($totalSum, 2, '.', ''),
                number_format($totalPaid, 2, '.', ''),
                number_format($totalDebt, 2, '.', ''),
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
