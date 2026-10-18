<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\B2bConversation;
use App\Models\SecurityAuditLog;
use App\Models\CashVan;
use Illuminate\Http\Request;

class B2bTenantAuthorizationService
{
    /**
     * Resolve the current actor context strictly from Laravel Auth.
     * Never trust role, company_id, or active tenant IDs from Request, Query String, or Session.
     */
    public function getActorContext(Request $request): array
    {
        $user = auth()->user();

        if ($user) {
            return [
                'is_authenticated' => true,
                'user' => $user,
                'role' => $user->role,
                'company_id' => $user->company_id ? (int)$user->company_id : null,
                'is_platform_admin' => $user->isPlatformAdmin(),
            ];
        }

        return [
            'is_authenticated' => false,
            'user' => null,
            'role' => null,
            'company_id' => null,
            'active_supplier_id' => null,
            'active_retailer_id' => null,
            'is_platform_admin' => false,
        ];
    }

    /**
     * Authorize platform administrator actions (governance, registrations, system moderation).
     * Platform admin status is strictly verified via database user record.
     */
    public function authorizePlatformAdmin(Request $request): bool
    {
        $actor = $this->getActorContext($request);

        if (!$actor['is_authenticated']) {
            SecurityAuditLog::logViolation(
                'unauthenticated_access',
                "هەوڵی نەشیاوی دەستگەیشتن بە بەشی ئەدمینی پلاتفۆرم بەبێ چوونەژوورەوە.",
                $request,
                null,
                null,
                'critical'
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تکایە سەرەتا وەک ئەدمینی پلاتفۆرم بچۆ ژوورەوە.");
        }

        if (!$actor['is_platform_admin']) {
            SecurityAuditLog::logViolation(
                'unauthorized_platform_admin_access',
                "هەوڵی دەستگەیشتن بە بەشی ئەدمینی پلاتفۆرم لەلایەن بەکارهێنەری بێ مۆڵەتەوە.",
                $request,
                $actor['company_id'],
                $actor['user']->id,
                'critical'
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تەنها بەڕێوەبەری پلاتفۆرم (Platform Admin) مۆڵەتی ئەم کردارەی هەیە.");
        }

        return true;
    }

    /**
     * Authorize access to a Purchase Order, strictly preventing IDOR and unauthorized tenant actions.
     */
    public function authorizeOrderAccess(Request $request, PurchaseOrder $order, string $action): bool
    {
        $actor = $this->getActorContext($request);

        if (!$actor['is_authenticated']) {
            SecurityAuditLog::logViolation(
                'unauthenticated_access',
                "هەوڵی دەستگەیشتن بە داواکاری بەبێ چوونەژوورەوەی ڕاستەقینە.",
                $request,
                null,
                null,
                'critical',
                ['order_id' => $order->id, 'action' => $action]
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تکایە سەرەتا بچۆ ژوورەوە.");
        }

        // Platform Admins have full neutral oversight
        if ($actor['is_platform_admin']) {
            return true;
        }

        $user = $actor['user'];
        $userCompanyId = (int)$user->company_id;

        // Staff role restrictions (Staff cannot approve, dispatch, or cancel orders)
        if (in_array($action, ['approve', 'dispatch', 'cancel', 'complete_settle']) && $user->isCompanyStaff()) {
            SecurityAuditLog::logViolation(
                'role_permission_denied',
                "کارمەندی ئاسایی (Staff) مۆڵەتی ئەنجامدانی کرداری '{$action}' نییە.",
                $request,
                $userCompanyId,
                $user->id,
                'warning',
                ['order_id' => $order->id, 'action' => $action]
            );
            $this->denyAccess("ڕێگەپێنەدراوە: کارمەندی ئاسایی مۆڵەتی ئەم کردارەی نییە. تەنها خاوەن یان بەڕێوەبەری کۆمپانیا دەتوانن فەرمان پەسەند، ئامادە یان بەڕێ بکەن.");
        }

        // Receiver role: only receive and deliver_verify
        if ($user->isReceiver() && !in_array($action, ['receive', 'deliver_verify', 'view_invoice', 'audit_logs'])) {
            SecurityAuditLog::logViolation(
                'role_permission_denied',
                "وەرگری کاڵا (Receiver) تەنها دەتوانێت کاڵا وەربگرێت و پشتڕاستی بکاتەوە، ناتوانێت کرداری '{$action}' ئەنجام بدات.",
                $request,
                $userCompanyId,
                $user->id,
                'warning',
                ['order_id' => $order->id, 'action' => $action]
            );
            $this->denyAccess("ڕێگەپێنەدراوە: وەرگری کاڵا تەنها دەتوانێت کاڵا وەربگرێت و پشکنین بکات.");
        }

        // Driver access restriction: Drivers can ONLY access orders assigned to their user ID or unassigned ready shipments
        if ($user->isDriver()) {
            if (!in_array($action, ['view_invoice', 'audit_logs', 'deliver_verify', 'dispatch'])) {
                SecurityAuditLog::logViolation(
                    'role_permission_denied',
                    "شۆفێر مۆڵەتی ئەنجامدانی کرداری '{$action}' نییە.",
                    $request,
                    $userCompanyId,
                    $user->id,
                    'critical',
                    ['order_id' => $order->id, 'action' => $action]
                );
                $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: شۆفێر تەنها دەتوانێت گەیاندن یان بەڕێکەوتن پشتڕاست بکاتەوە.");
            }

            $isAssignedOrAvailable = empty($order->driver_id) || ((int)$order->driver_id === (int)$user->id);
            if (!$isAssignedOrAvailable) {
                SecurityAuditLog::logViolation(
                    'unauthorized_tenant_access',
                    "هەوڵی نەشیاوی دەستگەیشتنی شۆفێر بە داواکارییەکی تر کە بۆ ئەو دانەنراوە.",
                    $request,
                    $userCompanyId,
                    $user->id,
                    'critical',
                    ['order_id' => $order->id, 'action' => $action]
                );
                $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ئەم داواکارییە بۆ شۆفێرێکی تر دانراوە و مۆڵەتی بینین یان دەستکاریکردنیت نییە.");
            }
            return true;
        }

        // Tenant boundary checks based on action
        switch ($action) {
            case 'view_invoice':
            case 'audit_logs':
                $allowed = ($userCompanyId === (int)$order->retailer_company_id)
                    || ($userCompanyId === (int)$order->supplier_company_id);

                if (!$allowed) {
                    SecurityAuditLog::logViolation(
                        'idor_attempt',
                        "هەوڵی نەشیاوی دەستگەیشتن بە فاکتۆر یان زانیاری داواکاری کۆمپانیای تر (IDOR).",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['order_id' => $order->id, 'order_po' => $order->po_number]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە (IDOR Protection): تۆ مۆڵەتی بینینی زانیاری و پسوولەی داواکاری ئەم کۆمپانیایەت نییە.");
                }
                return true;

            case 'approve':
            case 'process':
            case 'dispatch':
            case 'complete_settle':
                if ($userCompanyId !== (int)$order->supplier_company_id) {
                    SecurityAuditLog::logViolation(
                        'unauthorized_tenant_access',
                        "هەوڵدرا لەلایەن کۆمپانیایەکەوە فەرمانی دابینکەرێکی تر پەسەند/ناردن/یەکلا بکرێتەوە.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['order_id' => $order->id, 'action' => $action]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تەنها دابینکەری خاوەن کاڵا دەتوانێت ئەم داواکارییە پەسەند یان بەڕێ بکات.");
                }
                return true;

            case 'receive':
            case 'settle_debt':
                if ($userCompanyId !== (int)$order->retailer_company_id) {
                    SecurityAuditLog::logViolation(
                        'unauthorized_tenant_access',
                        "هەوڵدرا لەلایەن کۆمپانیای ترەوە داواکاریی مارکێتی تر وەربگیرێت یان قەرز بدرێتەوە.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['order_id' => $order->id, 'action' => $action]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تەنها ئەو مارکێتەی کە داواکارییەکەی ناردووە دەتوانێت وەریگرێت یان قەرز بداتەوە.");
                }
                return true;

            case 'deliver_verify':
                $allowed = ($userCompanyId === (int)$order->retailer_company_id)
                    || ($user->isDriver() && (int)$order->driver_id === (int)$user->id);

                if (!$allowed) {
                    SecurityAuditLog::logViolation(
                        'unauthorized_tenant_access',
                        "هەوڵی پەسەندکردنی گەیاندنی داواکاری لەلایەن کەسی بێ مۆڵەتەوە.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['order_id' => $order->id, 'action' => $action]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تەنها مارکێتی داواکار یان شۆفێری سپێردراو دەتوانن ئەم گەیاندنە تەواو بکەن.");
                }
                return true;

            case 'cancel':
                $allowed = in_array($userCompanyId, [
                    (int)$order->retailer_company_id,
                    (int)$order->supplier_company_id,
                ]);

                if (!$allowed) {
                    SecurityAuditLog::logViolation(
                        'unauthorized_tenant_access',
                        "هەوڵی هەڵوەشاندنەوەی فەرمانی کۆمپانیای تر.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['order_id' => $order->id, 'action' => $action]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: مۆڵەتی هەڵوەشاندنەوەی ئەم داواکارییە بە تەنها لای لایەنە پەیوەندیدارەکانە.");
                }
                return true;
        }

        return true;
    }

    /**
     * Authorize conversation access and sending messages.
     * Enforces anti-IDOR: sender_company_id must strictly match auth user's company_id.
     */
    public function authorizeConversationAccess(Request $request, B2bConversation $conversation, int $senderCompanyId): bool
    {
        $actor = $this->getActorContext($request);

        if (!$actor['is_authenticated']) {
            SecurityAuditLog::logViolation(
                'unauthenticated_access',
                "هەوڵی ناردنی نامە لە گفتوگۆ بەبێ چوونەژوورەوە.",
                $request,
                $senderCompanyId,
                null,
                'critical',
                ['conversation_id' => $conversation->id]
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تکایە سەرەتا بچۆ ژوورەوە.");
        }

        if ($actor['is_platform_admin']) {
            return true;
        }

        $user = $actor['user'];
        $userCompanyId = (int)$user->company_id;

        // Anti-tampering check: Authenticated user cannot change their sender_company_id
        if ($userCompanyId !== (int)$senderCompanyId) {
            SecurityAuditLog::logViolation(
                'unauthorized_tenant_access',
                "هەوڵی ناردنی پەیام بە ناوی کۆمپانیای ترەوە بە گۆڕینی ID.",
                $request,
                $senderCompanyId,
                $user->id,
                'critical',
                [
                    'conversation_id' => $conversation->id,
                    'user_company_id' => $userCompanyId,
                    'attempted_sender_id' => $senderCompanyId,
                ]
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت بە ناوی کۆمپانیای ترەوە پەیام بنێریت.");
        }

        // Must be a participant in this conversation
        $isParticipant = in_array($userCompanyId, [
            (int)$conversation->supplier_company_id,
            (int)$conversation->retailer_company_id,
        ]);

        if (!$isParticipant) {
            SecurityAuditLog::logViolation(
                'unauthorized_tenant_access',
                "هەوڵی ناردنی نامە یان چات لە گفتوگۆیەک کە ئەم کۆمپانیایە بەشدار نییە تێیدا.",
                $request,
                $userCompanyId,
                $user->id,
                'critical',
                ['conversation_id' => $conversation->id, 'sender_id' => $senderCompanyId]
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ئەم گفتوگۆیە تایبەتە و تۆ بەشدار نیت تێیدا.");
        }

        return true;
    }

    /**
     * Authorize company-level management (adding products, updating stock).
     */
    public function authorizeCompanyManagement(Request $request, int $targetCompanyId): bool
    {
        $actor = $this->getActorContext($request);

        if (!$actor['is_authenticated']) {
            SecurityAuditLog::logViolation(
                'unauthenticated_access',
                "هەوڵی بەڕێوەبردنی کۆمپانیا بەبێ چوونەژوورەوە.",
                $request,
                $targetCompanyId,
                null,
                'critical'
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تکایە سەرەتا بچۆ ژوورەوە.");
        }

        if ($actor['is_platform_admin']) {
            return true;
        }

        $user = $actor['user'];
        if (!$user->canAccessCompany($targetCompanyId) || $user->isCompanyStaff()) {
            SecurityAuditLog::logViolation(
                'role_permission_denied',
                "هەوڵی دەستکاریکردنی کاڵاکانی کۆمپانیای تر یان لەلایەن کارمەندی بێ مۆڵەتەوە.",
                $request,
                (int)$user->company_id,
                $user->id,
                'critical',
                ['target_company_id' => $targetCompanyId]
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: مۆڵەتی بەڕێوەبردنی کاڵاکانی ئەم کۆمپانیایەت نییە.");
        }

        return true;
    }

    /**
     * Authorize financial report and account statement downloads.
     */
    public function authorizeReportAccess(Request $request, ?int $targetCompanyId = null): bool
    {
        $actor = $this->getActorContext($request);

        if (!$actor['is_authenticated']) {
            SecurityAuditLog::logViolation(
                'unauthenticated_access',
                "هەوڵی دەرهێنانی ڕاپۆرت بەبێ چوونەژوورەوە.",
                $request,
                $targetCompanyId,
                null,
                'critical'
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تکایە سەرەتا بچۆ ژوورەوە.");
        }

        if ($actor['is_platform_admin']) {
            return true;
        }

        $user = $actor['user'];
        if ($targetCompanyId !== null && (int)$user->company_id !== (int)$targetCompanyId) {
            SecurityAuditLog::logViolation(
                'idor_attempt',
                "هەوڵی دەرهێنانی ڕاپۆرتی حیسابی کۆمپانیای تر (IDOR).",
                $request,
                (int)$user->company_id,
                $user->id,
                'critical',
                ['target_company_id' => $targetCompanyId]
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت ڕاپۆرتی دارایی و حیسابی کۆمپانیای تر داببەزێنیت.");
        }

        return true;
    }

    /**
     * Authorize supplier company operations on a Cash Van (owner, manager, supervisor).
     */
    public function authorizeSupplierVanAccess(Request $request, CashVan $van): bool
    {
        $actor = $this->getActorContext($request);

        if (!$actor['is_authenticated']) {
            SecurityAuditLog::logViolation(
                'unauthenticated_access',
                "هەوڵی دەستگەیشتن بە کاش ڤان بەبێ چوونەژوورەوە.",
                $request,
                $van->company_id,
                null,
                'critical'
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تکایە سەرەتا بچۆ ژوورەوە.");
        }

        if ($actor['is_platform_admin']) {
            return true;
        }

        $user = $actor['user'];

        // If user is the driver directly assigned to this van, allow access
        if ($van->driver_user_id !== null && (int)$van->driver_user_id === (int)$user->id) {
            return true;
        }

        if ((int)$user->company_id !== (int)$van->company_id) {
            SecurityAuditLog::logViolation(
                'idor_attempt',
                "هەوڵی بەڕێوەبردنی کاش ڤانی کۆمپانیای تر (IDOR).",
                $request,
                (int)$user->company_id,
                $user->id,
                'critical',
                ['target_van_id' => $van->id, 'van_company_id' => $van->company_id]
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت کاش ڤانی کۆمپانیایەکی تر بەڕێوەببەیت.");
        }

        return true;
    }

    /**
     * Authorize driver operations on a Cash Van (driver assigned to van, or company driver/owner/manager).
     */
    public function authorizeDriverVanAccess(Request $request, CashVan $van): bool
    {
        $this->authorizeSupplierVanAccess($request, $van);

        return true;
    }

    /**
     * Authorize unified financial ledger operations (Opening Balance, Safe Clearance, Voucher Verification, Receipt Vouchers).
     */
    public function authorizeFinancialAction(Request $request, string $action, ?int $supplierCompanyId = null, ?int $retailerCompanyId = null): bool
    {
        $actor = $this->getActorContext($request);

        if (!$actor['is_authenticated']) {
            SecurityAuditLog::logViolation(
                'unauthenticated_access',
                "هەوڵی دەستگەیشتن بە کرداری دارایی بەبێ چوونەژوورەوە.",
                $request,
                $supplierCompanyId,
                null,
                'critical'
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تکایە سەرەتا بچۆ ژوورەوە.");
        }

        if ($actor['is_platform_admin']) {
            return true;
        }

        $user = $actor['user'];
        $userCompanyId = (int)$user->company_id;

        // Driver role is strictly barred from managerial finance actions: opening balance, safe clearance, voucher verification into safe
        if ($user->isDriver() && in_array($action, ['opening_balance', 'safe_clearance', 'verify_voucher'])) {
            SecurityAuditLog::logViolation(
                'role_permission_denied',
                "شۆفێر مۆڵەتی دەستکاریکردنی باڵانسی سەرەتایی، پاکتاوی خەزێنە یان جێگیرکردنی پسوولەی نییە ({$action}).",
                $request,
                $userCompanyId,
                $user->id,
                'critical',
                ['action' => $action]
            );
            $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: شۆفێر مۆڵەتی ئەنجامدانی کردارە داراییە کارگێڕییەکانی نییە.");
        }

        switch ($action) {
            case 'opening_balance':
                if (!$user->isFinanceApprover()) {
                    SecurityAuditLog::logViolation(
                        'role_permission_denied',
                        "تەنها خاوەن، بەڕێوەبەر یان ژمێریار دەتوانن باڵانسی سەرەتایی دابنێن.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'warning'
                    );
                    $this->denyAccess("ڕێگەپێنەدراوە: تەنها خاوەن، بەڕێوەبەر یان بەرپرسی دارایی دەتوانن باڵانسی سەرەتایی دابنێن.");
                }
                if ($supplierCompanyId !== null && $userCompanyId !== (int)$supplierCompanyId) {
                    SecurityAuditLog::logViolation(
                        'idor_financial_tampering',
                        "هەوڵی نەشیاوی دانانی باڵانسی سەرەتایی بۆ کۆمپانیای تر (IDOR).",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['target_supplier_id' => $supplierCompanyId]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت باڵانسی سەرەتایی بۆ کۆمپانیای تر دابنێیت.");
                }
                break;

            case 'safe_clearance':
                if (!$user->isFinanceApprover()) {
                    SecurityAuditLog::logViolation(
                        'role_permission_denied',
                        "مۆڵەتی پاکتاوکردنی خەزێنە تەنها لای بەڕێوەبەر یان ژمێریارە.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'warning'
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تەنها خاوەن، بەڕێوەبەر یان ژمێریاری کۆمپانیا دەتوانن پارەی شۆفێر پاکتاو بکەن.");
                }
                if ($supplierCompanyId !== null && $userCompanyId !== (int)$supplierCompanyId) {
                    SecurityAuditLog::logViolation(
                        'idor_financial_tampering',
                        "هەوڵی نەشیاوی پاکتاوکردنی قاسەی کۆمپانیای تر.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['target_supplier_id' => $supplierCompanyId]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت قاسەی کۆمپانیای تر پاکتاو بکەیت.");
                }
                break;

            case 'verify_voucher':
                if (!$user->isFinanceApprover()) {
                    SecurityAuditLog::logViolation(
                        'role_permission_denied',
                        "مۆڵەتی پەسەندکردنی وەسڵ بۆ خەزێنە تەنها لای بەڕێوەبەر یان ژمێریارە.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'warning'
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: مۆڵەتی پەسەندکردنی ئەم پسوولەیەت نییە.");
                }
                if ($supplierCompanyId !== null && $userCompanyId !== (int)$supplierCompanyId) {
                    SecurityAuditLog::logViolation(
                        'idor_financial_tampering',
                        "هەوڵی پەسەندکردنی وەسڵی کۆمپانیای تر.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['target_supplier_id' => $supplierCompanyId]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت پسوولەی کۆمپانیای تر پەسەند بکەیت.");
                }
                break;

            case 'record_voucher':
                if ($user->isDriver()) {
                    if ($supplierCompanyId !== null && $userCompanyId !== (int)$supplierCompanyId) {
                        SecurityAuditLog::logViolation(
                            'idor_financial_tampering',
                            "شۆفێر ناتوانێت بۆ کۆمپانیای تر پسوولە تۆمار بکات.",
                            $request,
                            $userCompanyId,
                            $user->id,
                            'critical'
                        );
                        $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت بۆ کۆمپانیای تر پسوولە تۆمار بکەیت.");
                    }
                    if ($request->input('custody_status') === 'deposited_to_safe') {
                        SecurityAuditLog::logViolation(
                            'role_permission_denied',
                            "شۆفێر ناتوانێت پارە ڕاستەوخۆ بخاتە خەزێنەی کۆمپانیا بەبێ پاکتاوکردن.",
                            $request,
                            $userCompanyId,
                            $user->id,
                            'warning'
                        );
                        $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: پارەی وەرگیراوی شۆفێر دەبێت لە ئەمانەتی شۆفێردا بێت (in_driver_custody) تا لەلایەن ژمێریارییەوە پاکتاو دەکرێت.");
                    }
                } else {
                    $isParticipant = ($supplierCompanyId !== null && $userCompanyId === (int)$supplierCompanyId)
                        || ($retailerCompanyId !== null && $userCompanyId === (int)$retailerCompanyId);
                    if (!$isParticipant) {
                        SecurityAuditLog::logViolation(
                            'idor_financial_tampering',
                            "هەوڵی تۆمارکردنی پسوولە بە ناوی کۆمپانیای تر (IDOR).",
                            $request,
                            $userCompanyId,
                            $user->id,
                            'critical'
                        );
                        $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: مۆڵەتی تۆمارکردنی پسوولەی ئەم کۆمپانیایەت نییە.");
                    }
                }
                break;
            case 'reverse_voucher':
                if (!$user->isFinanceApprover()) {
                    SecurityAuditLog::logViolation(
                        'role_permission_denied',
                        "مۆڵەتی پێچەوانەکردنەوەی وەسڵ تەنها لای بەڕێوەبەر یان ژمێریارە.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'warning'
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تەنها خاوەن، بەڕێوەبەر یان ژمێریار دەتوانن پسوولە پێچەوانە بکەنەوە.");
                }
                if ($supplierCompanyId !== null && $userCompanyId !== (int)$supplierCompanyId) {
                    SecurityAuditLog::logViolation(
                        'idor_financial_tampering',
                        "هەوڵی پێچەوانەکردنەوەی وەسڵی کۆمپانیای تر.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical',
                        ['target_supplier_id' => $supplierCompanyId]
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت پسوولەی کۆمپانیای تر پێچەوانە بکەیتەوە.");
                }
                break;

            case 'manage_installments':
                if (!$user->isFinanceApprover()) {
                    SecurityAuditLog::logViolation(
                        'role_permission_denied',
                        "مۆڵەتی دانانی پلانی قیست تەنها لای بەڕێوەبەر یان ژمێریارە.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'warning'
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: تەنها خاوەن، بەڕێوەبەر یان ژمێریار دەتوانن پلانی قیست دابنێن.");
                }
                if ($supplierCompanyId !== null && $userCompanyId !== (int)$supplierCompanyId) {
                    SecurityAuditLog::logViolation(
                        'idor_financial_tampering',
                        "هەوڵی دانانی پلانی قیست بۆ کۆمپانیای تر.",
                        $request,
                        $userCompanyId,
                        $user->id,
                        'critical'
                    );
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: ناتوانیت بۆ کۆمپانیای تر پلانی قیست دابنێیت.");
                }
                break;

            case 'view_integrity':
                if ($user->isDriver()) {
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: شۆفێر مۆڵەتی پشکنینی هاوسەنگی دارایی نییە.");
                }
                $isParticipant = ($supplierCompanyId !== null && $userCompanyId === (int)$supplierCompanyId)
                    || ($retailerCompanyId !== null && $userCompanyId === (int)$retailerCompanyId);
                if (!$isParticipant) {
                    $this->denyAccess("دەستگەیشتن ڕەتکرایەوە: مۆڵەتی بینینی هاوسەنگی ئەم کۆمپانیایەت نییە.");
                }
                break;
        }

        return true;
    }

    /**
     * Terminate unauthorized access with clean 403 response
     */
    protected function denyAccess(string $message): void
    {
        abort(403, $message);
    }
}

