<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Gender;
use App\Enums\IdentityVerificationStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'phone_number',
    'phone_verified_at',
    'newsletter_subscribed_at',
    'first_name',
    'last_name',
    'national_code',
    'birth_date',
    'gender',
    'province_id',
    'city_id',
    'address',
    'national_card_image_path',
    'face_image_path',
    'identity_status',
    'identity_verified_at',
    'identity_verification_result',
])]
#[Hidden(['password', 'remember_token', 'national_card_image_path', 'face_image_path', 'identity_verification_result'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'newsletter_subscribed_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'gender' => Gender::class,
            'identity_status' => IdentityVerificationStatus::class,
            'identity_verified_at' => 'datetime',
            'identity_verification_result' => 'array',
        ];
    }

    /**
     * Determine if the user currently receives SMS newsletter updates.
     */
    public function isSubscribedToNewsletter(): bool
    {
        return $this->newsletter_subscribed_at !== null;
    }

    /**
     * Determine if the user's identity has been verified.
     */
    public function isIdentityVerified(): bool
    {
        return $this->identity_status === IdentityVerificationStatus::Verified;
    }

    /**
     * The reason the last verification attempt was rejected, if any.
     */
    public function identityRejectionReason(): ?string
    {
        if ($this->identity_status !== IdentityVerificationStatus::Rejected) {
            return null;
        }

        $reason = $this->identity_verification_result['reason'] ?? null;

        return $reason !== null && $reason !== '' ? (string) $reason : null;
    }

    /**
     * The province component of the user's address.
     *
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    /**
     * The city component of the user's address.
     *
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Limit the query to users subscribed to the SMS newsletter.
     *
     * @param  Builder<User>  $query
     */
    public function scopeSubscribedToNewsletter(Builder $query): void
    {
        $query->whereNotNull('newsletter_subscribed_at');
    }
}
