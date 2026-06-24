<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identity Verification
    |--------------------------------------------------------------------------
    |
    | Settings for the automated identity verification flow. A user submits
    | their personal data alongside a national card photo and a selfie; a
    | vision-capable LLM then estimates how likely the data and documents
    | belong to the same person.
    |
    */

    // Vision-capable OpenAI model used to compare the documents and data.
    'model' => env('IDENTITY_VERIFICATION_MODEL', 'gpt-4o'),

    // Minimum match probability (0..1) required to mark a user as verified.
    'threshold' => (float) env('IDENTITY_VERIFICATION_THRESHOLD', 0.8),

    // Filesystem disk used to store the (sensitive) identity documents.
    'disk' => env('IDENTITY_VERIFICATION_DISK', 'local'),

    // Maximum accepted image upload size, in kilobytes.
    'max_image_kilobytes' => (int) env('IDENTITY_VERIFICATION_MAX_IMAGE_KB', 5120),

];
