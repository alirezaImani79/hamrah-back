<?php

use App\Enums\Gender;
use App\Models\User;
use App\Services\Identity\OpenAiIdentityVerifier;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Resources\Chat;
use OpenAI\Responses\Chat\CreateResponse;

function userWithDocuments(array $overrides = []): User
{
    Storage::fake('local');
    Storage::disk('local')->put('identity/1/card.jpg', 'card-bytes');
    Storage::disk('local')->put('identity/1/face.jpg', 'face-bytes');

    return (new User)->forceFill(array_merge([
        'first_name' => 'Ali',
        'last_name' => 'Imani',
        'national_code' => '0012345678',
        'birth_date' => '1990-05-21',
        'gender' => Gender::Male,
        'national_card_image_path' => 'identity/1/card.jpg',
        'face_image_path' => 'identity/1/face.jpg',
    ], $overrides));
}

it('sends both images to the model and parses the probability', function () {
    $user = userWithDocuments();

    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                ['message' => ['content' => '{"probability": 0.91, "reason": "All details match."}']],
            ],
        ]),
    ]);

    $result = (new OpenAiIdentityVerifier('gpt-4o', 'local'))->verify($user);

    expect($result['probability'])->toBe(0.91)
        ->and($result['reason'])->toBe('All details match.');

    OpenAI::assertSent(Chat::class, function (string $method, array $parameters): bool {
        return $method === 'create'
            && $parameters['model'] === 'gpt-4o'
            && count($parameters['messages'][1]['content']) === 3;
    });
});

it('clamps an out-of-range probability into the 0..1 range', function () {
    $user = userWithDocuments();

    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                ['message' => ['content' => '{"probability": 1.4, "reason": "x"}']],
            ],
        ]),
    ]);

    $result = (new OpenAiIdentityVerifier('gpt-4o', 'local'))->verify($user);

    expect($result['probability'])->toBe(1.0);
});

it('throws when the model returns an unparseable response', function () {
    $user = userWithDocuments();

    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                ['message' => ['content' => 'not json']],
            ],
        ]),
    ]);

    expect(fn () => (new OpenAiIdentityVerifier('gpt-4o', 'local'))->verify($user))
        ->toThrow(RuntimeException::class);
});
