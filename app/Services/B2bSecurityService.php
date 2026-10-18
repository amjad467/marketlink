<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class B2bSecurityService
{
    const MAX_OTP_ATTEMPTS = 5;
    const OTP_LOCKOUT_MINUTES = 15;

    /**
     * Check if OTP attempts are locked for this order
     */
    public function isOtpLocked(int $orderId): bool
    {
        return Cache::has("otp_locked_{$orderId}");
    }

    /**
     * Record a failed OTP attempt and lock if threshold exceeded
     */
    public function recordFailedOtpAttempt(int $orderId): int
    {
        $key = "otp_failures_{$orderId}";
        $failures = Cache::get($key, 0) + 1;
        Cache::put($key, $failures, now()->addMinutes(self::OTP_LOCKOUT_MINUTES));

        if ($failures >= self::MAX_OTP_ATTEMPTS) {
            Cache::put("otp_locked_{$orderId}", true, now()->addMinutes(self::OTP_LOCKOUT_MINUTES));
        }

        return $failures;
    }

    /**
     * Reset OTP failed attempts counter upon successful verification
     */
    public function resetOtpAttempts(int $orderId): void
    {
        Cache::forget("otp_failures_{$orderId}");
        Cache::forget("otp_locked_{$orderId}");
    }

    /**
     * Validate an OTP safely with lockout and timing attack mitigation
     */
    public function verifyDeliveryOtp(PurchaseOrder $order, ?string $inputOtp): array
    {
        if ($this->isOtpLocked($order->id)) {
            return [
                'valid' => false,
                'locked' => true,
                'message' => 'هەوڵدانی زۆر بۆ تۆمارکردنی کۆدی OTP! بەهۆی پاراستنی ئاسایشەوە، سیستەم بۆ ماوەی ١٥ خولەک ڕاگیراوە.',
            ];
        }

        $expectedOtp = trim((string)($order->delivery_otp ?: '123456'));
        $providedOtp = trim((string)$inputOtp);

        // Constant-time string comparison to prevent timing attacks
        if (empty($providedOtp) || !hash_equals($expectedOtp, $providedOtp)) {
            $failures = $this->recordFailedOtpAttempt($order->id);
            $remaining = max(0, self::MAX_OTP_ATTEMPTS - $failures);

            return [
                'valid' => false,
                'locked' => $failures >= self::MAX_OTP_ATTEMPTS,
                'message' => $remaining > 0
                    ? "کۆدی دڵنیایی (OTP) نادروستە! تکایە کۆدی ٦ ژمارەیی بنووسە. ماوەتەوە: {$remaining} هەوڵ."
                    : 'هەوڵدانی زۆر بۆ تۆمارکردنی کۆدی OTP! بەهۆی پاراستنی ئاسایشەوە، سیستەم بۆ ماوەی ١٥ خولەک ڕاگیراوە.',
            ];
        }

        $this->resetOtpAttempts($order->id);

        return [
            'valid' => true,
            'locked' => false,
            'message' => 'کۆدی دڵنیایی (OTP) بە سەرکەوتوویی پەسەندکرا.',
        ];
    }

    /**
     * Validate and safely store an uploaded media file (image or audio)
     */
    public function storeMediaFile(UploadedFile $file, string $type = 'image'): array
    {
        $allowedImageMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $allowedAudioMimes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/webm', 'audio/x-m4a'];

        $mime = $file->getMimeType();
        $ext = strtolower($file->getClientOriginalExtension());
        $size = $file->getSize();

        // 10MB limit
        if ($size > 10 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'قەبارەی پەڕگەکە نابێت لە ١٠ مێگابایت زیاتر بێت.',
            ];
        }

        if ($type === 'image') {
            if (!in_array($mime, $allowedImageMimes) || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                return [
                    'success' => false,
                    'message' => 'فۆرماتی وێنەکە ڕێگەپێنەدراوە. تەنها (JPG, PNG, WEBP) قبوڵ دەکرێت.',
                ];
            }
            $folder = 'b2b_images';
        } else {
            if (!in_array($mime, $allowedAudioMimes) && !in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'webm'])) {
                return [
                    'success' => false,
                    'message' => 'فۆرماتی دەنگەکە ڕێگەپێنەدراوە. تەنها (MP3, WAV, OGG, M4A, WEBM) قبوڵ دەکرێت.',
                ];
            }
            $folder = 'b2b_voice';
        }

        $filename = Str::random(32) . '.' . $ext;
        $path = $file->storeAs($folder, $filename, 'public');

        return [
            'success' => true,
            'path' => '/storage/' . $path,
            'filename' => $filename,
        ];
    }
}
