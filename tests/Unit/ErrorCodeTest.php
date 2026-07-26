<?php

use App\Support\ErrorCode;

it('exposes a unique, non-empty uppercase string for every case', function () {
    $values = array_map(fn (ErrorCode $code) => $code->value, ErrorCode::cases());

    expect($values)->not->toBeEmpty();

    foreach ($values as $value) {
        expect($value)->toMatch('/^[A-Z][A-Z0-9_]+$/');
    }

    expect(count($values))->toBe(count(array_unique($values)));
});

it('documents every code in the OpenAPI error schema', function () {
    // Resolved relative to this file so the test stays container-free.
    $docs = (string) file_get_contents(__DIR__.'/../../app/OpenApi/ApiDoc.php');

    foreach (ErrorCode::cases() as $code) {
        expect($docs)->toContain("'$code->value'");
    }
});
