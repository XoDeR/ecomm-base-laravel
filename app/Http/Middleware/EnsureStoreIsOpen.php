<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows the maintenance message to visitors while the store is closed.
 *
 * The admin panel and the authentication pages stay reachable so administrators can sign in and reopen the store.
 */
class EnsureStoreIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('store_status', 'open') !== 'closed' || $this->isExempt($request)) {
            return $next($request);
        }

        $message = Setting::get('maintenance_message', 'The store is temporarily closed for maintenance.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 503);
        }

        return response()->view('maintenance', ['message' => $message], 503)->header('Retry-After', '3600');
    }

    private function isExempt(Request $request): bool
    {
        return $request->user()?->canAccessAdmin()
            || $request->is('admin', 'admin/*', 'login', 'logout', 'two-factor-challenge', 'forgot-password', 'reset-password/*', 'up', 'uploads/*', 'build/*', 'user/*', 'passkeys/*', '.well-known/*');
    }
}
