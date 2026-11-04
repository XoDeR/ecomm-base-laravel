<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Identifies the current shopper for cart / compare list ownership.
 *
 * Guests are tracked by a token kept in the session. The token is stored as
 * session data (not the session id) because the session id is regenerated on login,
 * and the guest cart must survive that to be merged into the user's account.
 */
class Shopper
{
    public const SESSION_KEY = 'shopper_token';

    public static function token(?Request $request = null): string
    {
        $session = ($request ?? request())->session();

        if (! $session->has(self::SESSION_KEY)) {
            $session->put(self::SESSION_KEY, (string) Str::uuid());
        }

        return (string) $session->get(self::SESSION_KEY);
    }

    public static function userId(?Request $request = null): ?int
    {
        return ($request ?? request())->user()?->id;
    }
}
