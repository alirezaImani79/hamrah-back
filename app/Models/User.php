<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone_number', 'phone_verified_at', 'newsletter_subscribed_at'])]
#[Hidden(['password', 'remember_token'])]
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
     * Limit the query to users subscribed to the SMS newsletter.
     *
     * @param  Builder<User>  $query
     */
    public function scopeSubscribedToNewsletter(Builder $query): void
    {
        $query->whereNotNull('newsletter_subscribed_at');
    }
}
