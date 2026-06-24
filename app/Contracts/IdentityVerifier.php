<?php

namespace App\Contracts;

use App\Models\User;

interface IdentityVerifier
{
    /**
     * Compare the user's submitted identity data against their uploaded
     * national card and selfie, returning how likely they belong together.
     *
     * @return array{probability: float, reason: string}
     */
    public function verify(User $user): array;
}
