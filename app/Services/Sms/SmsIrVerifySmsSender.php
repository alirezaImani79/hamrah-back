<?php

namespace App\Services\Sms;

use App\Contracts\OtpSmsSender;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsIrVerifySmsSender implements OtpSmsSender
{
    use NormalizesMobile;

    public function __construct(
        private string $apiKey,
        private int|string $templateId,
        private string $parameterName = 'Code',
        private string $endpoint = 'https://api.sms.ir/v1/send/verify',
    ) {}

    /**
     * Deliver the one-time code through the sms.ir verify (template) gateway.
     *
     * @throws RuntimeException When the gateway rejects the request.
     */
    public function sendCode(string $phoneNumber, string $code): void
    {
        $response = Http::asJson()
            ->acceptJson()
            ->withHeaders(['X-API-KEY' => $this->apiKey])
            ->post($this->endpoint, [
                'mobile' => $this->normalizeMobile($phoneNumber),
                'templateId' => (int) $this->templateId,
                'parameters' => [
                    ['name' => $this->parameterName, 'value' => $code],
                ],
            ]);

        // sms.ir returns HTTP 200 with a `status` of 1 on success; anything
        // else (transport error or a non-1 status) is treated as a failure so
        // the queued job can retry.
        if ($response->failed() || (int) $response->json('status') !== 1) {
            throw new RuntimeException(
                'sms.ir rejected the verification code: '.($response->json('message') ?? $response->status()),
            );
        }
    }
}
