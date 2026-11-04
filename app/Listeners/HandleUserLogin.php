<?php

namespace App\Listeners;

use App\Models\CartItem;
use App\Models\CompareItem;
use App\Models\LoginLog;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Support\Shopper;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * After a successful login: record it and merge the guest cart / compare list into the account.
 */
class HandleUserLogin
{
    public function __construct(private readonly Request $request) {}

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        UserActivityLog::record($user->id, 'login', 'Logged in', ['ip' => $this->request->ip()]);

        LoginLog::create([
            'user_id' => $user->id,
            'ip_address' => $this->request->ip(),
            'user_agent' => (string) $this->request->userAgent(),
            'login_time' => now(),
        ]);

        if ($this->request->hasSession()) {
            $this->mergeGuestData($user, (string) $this->request->session()->get(Shopper::SESSION_KEY));
        }
    }

    private function mergeGuestData(User $user, string $token): void
    {
        if ($token === '') {
            return;
        }

        CartItem::query()->whereNull('user_id')->where('session_id', $token)->get()->each(function (CartItem $guestItem) use ($user) {
            $existing = CartItem::query()
                ->where('user_id', $user->id)
                ->where('product_id', $guestItem->product_id)
                ->get()
                ->first(fn (CartItem $item) => $item->selected_options == $guestItem->selected_options);

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $user->id, 'session_id' => null]);
            }
        });

        CompareItem::query()->whereNull('user_id')->where('session_id', $token)->get()->each(function (CompareItem $guestItem) use ($user) {
            $exists = CompareItem::query()->where('user_id', $user->id)->where('product_id', $guestItem->product_id)->exists();

            $exists
                ? $guestItem->delete()
                : $guestItem->update(['user_id' => $user->id, 'session_id' => null]);
        });
    }
}
