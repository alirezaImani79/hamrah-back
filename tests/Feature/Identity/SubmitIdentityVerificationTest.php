<?php

use App\Enums\IdentityVerificationStatus;
use App\Jobs\VerifyUserIdentity;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * A complete, valid submission payload. Files are regenerated on each call
 * because UploadedFile instances are consumed once sent. A real province/city
 * pair is created so the address fields pass validation.
 *
 * @return array<string, mixed>
 */
function identityPayload(array $overrides = []): array
{
    $city = City::factory()->create();

    return array_merge([
        'first_name' => 'Ali',
        'last_name' => 'Imani',
        'national_code' => '0012345678',
        'birth_date' => '1990-05-21',
        'gender' => 'male',
        'province_id' => $city->province_id,
        'city_id' => $city->id,
        'address' => 'خیابان ولیعصر، کوچه ۵، پلاک ۱۲',
        'national_card_image' => UploadedFile::fake()->image('card.jpg', 600, 400),
        'face_image' => UploadedFile::fake()->image('face.jpg', 400, 400),
    ], $overrides);
}

it('stores the submission, marks the user verifying, and queues the job', function () {
    Queue::fake();
    Storage::fake(config('identity.disk'));

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->post('/api/v1/identity/verify', identityPayload());

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.identity_status', 'verifying')
        ->assertJsonPath('data.is_identity_verified', false)
        ->assertJsonPath('data.first_name', 'Ali')
        ->assertJsonPath('data.national_code', '0012345678')
        ->assertJsonPath('data.address', 'خیابان ولیعصر، کوچه ۵، پلاک ۱۲')
        ->assertJsonPath('data.province.id', fn ($id): bool => $id !== null)
        ->assertJsonPath('data.city.id', fn ($id): bool => $id !== null);

    $user->refresh();

    expect($user->identity_status)->toBe(IdentityVerificationStatus::Verifying)
        ->and($user->province_id)->not->toBeNull()
        ->and($user->city_id)->not->toBeNull()
        ->and($user->address)->toBe('خیابان ولیعصر، کوچه ۵، پلاک ۱۲')
        ->and($user->national_card_image_path)->not->toBeNull()
        ->and($user->face_image_path)->not->toBeNull();

    Storage::disk(config('identity.disk'))->assertExists($user->national_card_image_path);
    Storage::disk(config('identity.disk'))->assertExists($user->face_image_path);

    Queue::assertPushed(VerifyUserIdentity::class, fn (VerifyUserIdentity $job): bool => $job->user->is($user));
});

it('normalizes Persian digits in the national code', function () {
    Queue::fake();
    Storage::fake(config('identity.disk'));

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->post('/api/v1/identity/verify', identityPayload(['national_code' => '۰۰۱۲۳۴۵۶۷۸']))
        ->assertStatus(202)
        ->assertJsonPath('data.national_code', '0012345678');
});

it('requires every identity field', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/identity/verify', [])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['first_name', 'last_name', 'national_code', 'birth_date', 'gender', 'province_id', 'city_id', 'address', 'national_card_image', 'face_image']);
});

it('rejects a non-existent province', function () {
    Storage::fake(config('identity.disk'));

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->post('/api/v1/identity/verify', identityPayload(['province_id' => 999999]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['province_id']);
});

it('rejects a city that does not belong to the given province', function () {
    Storage::fake(config('identity.disk'));

    $otherProvinceCity = City::factory()->create();
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // city_id from a different province than the one supplied in province_id.
    $this->withToken($token)
        ->post('/api/v1/identity/verify', identityPayload(['city_id' => $otherProvinceCity->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['city_id']);
});

it('rejects a national code already used by another user', function () {
    Storage::fake(config('identity.disk'));

    User::factory()->create(['national_code' => '0012345678']);
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->post('/api/v1/identity/verify', identityPayload(['national_code' => '0012345678']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['national_code']);
});

it('blocks a new submission while one is already in progress', function () {
    Queue::fake();
    Storage::fake(config('identity.disk'));

    $user = User::factory()->identityVerifying()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->post('/api/v1/identity/verify', identityPayload())
        ->assertStatus(409)
        ->assertJsonPath('success', false);

    Queue::assertNothingPushed();
});

it('blocks a new submission once the user is verified', function () {
    Queue::fake();
    Storage::fake(config('identity.disk'));

    $user = User::factory()->identityVerified()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->post('/api/v1/identity/verify', identityPayload())
        ->assertStatus(409);

    Queue::assertNothingPushed();
});

it('rejects unauthenticated submissions', function () {
    $this->postJson('/api/v1/identity/verify', [])
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});
