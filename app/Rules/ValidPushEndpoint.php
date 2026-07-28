<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidPushEndpoint implements ValidationRule
{
    /**
     * Known push service host suffixes.
     *
     * @var string[]
     */
    private const ALLOWED_HOST_SUFFIXES = [
        '.googleapis.com',       // Chrome / Google FCM
        '.mozilla.com',          // Firefox
        '.mozilla.net',          // Firefox legacy
        '.windows.com',          // Edge / Windows
        '.push.apple.com',       // Safari / iOS PWA
        '.push-apple.com.akadns.net', // Apple CDN variant
    ];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid push service endpoint URL.');

            return;
        }

        $parsed = parse_url($value);

        if ($parsed === false || ! isset($parsed['host'])) {
            $fail('The :attribute must be a valid push service endpoint URL.');

            return;
        }

        if (($parsed['scheme'] ?? '') !== 'https') {
            $fail('The :attribute must use the https scheme.');

            return;
        }

        $host = strtolower($parsed['host']);

        foreach (self::ALLOWED_HOST_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return;
            }
        }

        $fail('The :attribute must be a push service endpoint from a known provider (Google, Mozilla, Microsoft, Apple).');
    }
}
