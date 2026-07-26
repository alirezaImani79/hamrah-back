<?php

namespace App\Services\Sms;

trait NormalizesMobile
{
    /**
     * sms.ir expects local Iranian numbers (e.g. 09120000000), so strip a
     * leading +98 / 98 country code back to the national 0-prefixed form.
     */
    protected function normalizeMobile(string $phoneNumber): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (str_starts_with((string) $digits, '98')) {
            $digits = '0'.substr((string) $digits, 2);
        }

        return (string) $digits;
    }
}
