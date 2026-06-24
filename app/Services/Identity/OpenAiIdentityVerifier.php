<?php

namespace App\Services\Identity;

use App\Contracts\IdentityVerifier;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

class OpenAiIdentityVerifier implements IdentityVerifier
{
    public function __construct(
        private string $model,
        private string $disk,
    ) {}

    /**
     * Ask a vision-capable model to judge whether the claimed personal data,
     * the national card photo and the selfie all describe the same person.
     *
     * @return array{probability: float, reason: string}
     */
    public function verify(User $user): array
    {
        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $this->claimedDataPrompt($user)],
                        ['type' => 'image_url', 'image_url' => ['url' => $this->imageDataUri($user->national_card_image_path)]],
                        ['type' => 'image_url', 'image_url' => ['url' => $this->imageDataUri($user->face_image_path)]],
                    ],
                ],
            ],
        ]);

        return $this->parse($response->choices[0]->message->content ?? '');
    }

    /**
     * Instructions describing the task and the exact JSON contract expected back.
     */
    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        You are an identity verification assistant. You are given a person's claimed
        personal details together with two images: the first is a photo of their
        national ID card, the second is a selfie of their face.

        Assess how likely it is that ALL of the following hold true together:
        - the claimed first name, last name, national code, birth date and gender
          match the information printed on the national card; and
        - the face in the selfie is the same person as the photo on the national card.

        Respond with a single JSON object and nothing else, shaped exactly as:
        {"probability": <number between 0 and 1>, "reason": "<short explanation>"}

        "probability" is your overall confidence (0 = certainly not a match,
        1 = certainly a match). Be conservative when images are unreadable,
        cropped, or the data cannot be confirmed.
        PROMPT;
    }

    /**
     * The user's claimed identity data, rendered for the model to compare against.
     */
    private function claimedDataPrompt(User $user): string
    {
        return implode("\n", [
            'Claimed personal details:',
            '- First name: '.(string) $user->first_name,
            '- Last name: '.(string) $user->last_name,
            '- National code: '.(string) $user->national_code,
            '- Birth date: '.($user->birth_date?->toDateString() ?? ''),
            '- Gender: '.($user->gender?->value ?? ''),
        ]);
    }

    /**
     * Build a base64 data URI for an image stored on the identity disk.
     */
    private function imageDataUri(?string $path): string
    {
        $disk = Storage::disk($this->disk);

        if ($path === null || ! $disk->exists($path)) {
            throw new RuntimeException('Identity document image is missing.');
        }

        $mime = $disk->mimeType($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path));
    }

    /**
     * Decode and normalize the model's JSON response into the result contract.
     *
     * @return array{probability: float, reason: string}
     */
    private function parse(string $content): array
    {
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($content, true);

        if (! is_array($decoded) || ! array_key_exists('probability', $decoded)) {
            throw new RuntimeException('The identity verification model returned an unexpected response.');
        }

        $probability = (float) $decoded['probability'];

        return [
            'probability' => max(0.0, min(1.0, $probability)),
            'reason' => (string) ($decoded['reason'] ?? ''),
        ];
    }
}
