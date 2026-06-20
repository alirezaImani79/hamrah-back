<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsIrSmsSender implements SmsSender
{
    public function __construct(
        private string $apiKey,
        private string $lineNumber,
        private string $endpoint = 'https://api.sms.ir/v1/send/bulk',
    ) {}

    /**
     * Deliver an SMS through the sms.ir bulk send gateway.
     *
     * @throws RuntimeException When the gateway rejects the request.
     */
    public function send(string $phoneNumber, string $message): void
    {
        $response = Http::asJson()
            ->acceptJson()
            ->withHeaders(['X-API-KEY' => $this->apiKey])
            ->post($this->endpoint, [
                'lineNumber' => (int) $this->lineNumber,
                'messageText' => $message,
                'mobiles' => [$this->normalize($phoneNumber)],
            ]);

        // sms.ir returns HTTP 200 with a `status` of 1 on success; anything
        // else (transport error or a non-1 status) is treated as a failure so
        // the queued job can retry.
        if ($response->failed() || (int) $response->json('status') !== 1) {
            throw new RuntimeException(
                'sms.ir rejected the message: '.($response->json('message') ?? $response->status()),
            );
        }
    }

    /**
     * sms.ir expects local Iranian numbers (e.g. 09120000000), so strip a
     * leading +98 / 98 country code back to the national 0-prefixed form.
     */
    private function normalize(string $phoneNumber): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (str_starts_with((string) $digits, '98')) {
            $digits = '0'.substr((string) $digits, 2);
        }

        return (string) $digits;
    }
}
