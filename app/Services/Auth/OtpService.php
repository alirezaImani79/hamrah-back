<?php

namespace App\Services\Auth;

use App\Contracts\SmsSender;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(private SmsSender $smsSender) {}

    /**
     * Generate a one-time code for the phone number, persist its hash, and
     * dispatch it via the configured SMS sender. Returns the plain-text code
     * (used only for local/test debugging — never persisted in clear text).
     */
    public function request(string $phoneNumber): string
    {
        $this->ensureNotThrottled($phoneNumber);

        // Only the most recently issued code should remain valid.
        OtpCode::query()
            ->where('phone_number', $phoneNumber)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        OtpCode::create([
            'phone_number' => $phoneNumber,
            'code' => Hash::make($code),
            'expires_at' => now()->addSeconds((int) config('otp.ttl')),
        ]);

        $this->smsSender->send($phoneNumber, "Your verification code is: {$code}");

        return $code;
    }

    /**
     * Verify a submitted code against the latest unconsumed code for the phone.
     */
    public function verify(string $phoneNumber, string $code): bool
    {
        $otp = OtpCode::query()
            ->where('phone_number', $phoneNumber)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if ($otp === null || $otp->isExpired()) {
            return false;
        }

        if ($otp->attempts >= (int) config('otp.max_attempts')) {
            return false;
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return true;
    }

    /**
     * Prevent requesting codes faster than the configured cooldown.
     *
     * @throws ValidationException
     */
    private function ensureNotThrottled(string $phoneNumber): void
    {
        $throttle = (int) config('otp.throttle_seconds');

        if ($throttle <= 0) {
            return;
        }

        $recentlyRequested = OtpCode::query()
            ->where('phone_number', $phoneNumber)
            ->where('created_at', '>', now()->subSeconds($throttle))
            ->exists();

        if ($recentlyRequested) {
            throw ValidationException::withMessages([
                'phone_number' => ['Please wait before requesting another verification code.'],
            ]);
        }
    }

    /**
     * Generate a zero-padded numeric code of the configured length.
     */
    private function generateCode(): string
    {
        $length = (int) config('otp.length');
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
