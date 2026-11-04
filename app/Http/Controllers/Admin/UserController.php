<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Models\UserActionAudit;
use App\Models\UserActivityLog;
use App\Models\UserBonus;
use App\Models\UserRole;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');

        $users = User::query()
            ->with('role:id,name,slug')
            ->withCount('orders')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when($role !== '', fn ($q) => $q->whereHas('role', fn ($q) => $q->where('slug', $role)))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => [
                'data' => $users->getCollection()->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role?->name,
                    'is_active' => $user->is_active,
                    'orders_count' => $user->orders_count,
                    'last_login_at' => $user->last_login_at?->toIso8601String(),
                    'created_at' => $user->created_at?->toIso8601String(),
                ])->all(),
                'links' => $users->linkCollection()->all(),
                'total' => $users->total(),
            ],
            'roles' => UserRole::orderBy('id')->get(['slug', 'name'])->all(),
            'filters' => ['search' => $search, 'role' => $role],
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load(['role', 'bonus', 'subscription']);

        return Inertia::render('admin/users/edit', [
            'user' => [
                ...$user->only(['id', 'name', 'email', 'phone', 'role_id', 'is_active']),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'email_verified' => $user->email_verified_at !== null,
                'bonus' => (int) $user->bonus?->balance,
                'marketing_email' => (bool) $user->subscription?->marketing_email,
            ],
            'roles' => UserRole::orderBy('id')->get(['id', 'name'])->all(),
            'orders' => $user->orders()->latest()->limit(10)->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'total' => (float) $order->total,
                'created_at' => $order->created_at?->toIso8601String(),
            ])->all(),
            'cart' => $user->cartItems()->with('product:id,name,price')->get()->map(fn (CartItem $item) => [
                'id' => $item->id,
                'name' => $item->product?->name,
                'quantity' => $item->quantity,
            ])->all(),
            'activity' => $user->activityLogs()->limit(20)->get(['id', 'event_type', 'description', 'created_at'])->all(),
            'audits' => $user->actionAudits()->with('admin:id,name')->limit(20)->get()->map(fn (UserActionAudit $audit) => [
                'id' => $audit->id,
                'action_type' => $audit->action_type,
                'reason' => $audit->reason,
                'old_value' => $audit->old_value,
                'new_value' => $audit->new_value,
                'admin' => $audit->admin?->name,
                'created_at' => $audit->created_at?->toIso8601String(),
            ])->all(),
            'logins' => $user->loginLogs()->limit(10)->get(['id', 'ip_address', 'user_agent', 'login_time'])->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'integer', 'exists:user_roles,id'],
        ]);

        if ($user->is($request->user()) && (int) $data['role_id'] !== $user->role_id) {
            $this->toast('You cannot change your own role.', 'error');

            return back();
        }

        if ((int) $data['role_id'] !== $user->role_id) {
            $this->audit($user, $request->user(), 'role', 'Role changed from the user page', $user->role?->name, UserRole::find($data['role_id'])?->name);
        }

        $user->update($data);

        $this->toast('Customer updated.');

        return back();
    }

    public function block(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($user->is($request->user())) {
            $this->toast('You cannot block your own account.', 'error');

            return back();
        }

        $active = (bool) $data['is_active'];

        if ($user->is_active !== $active) {
            $user->update(['is_active' => $active]);
            $this->audit($user, $request->user(), $active ? 'unblock' : 'block', $data['reason'], $active ? 'blocked' : 'active', $active ? 'active' : 'blocked');
        }

        $this->toast($active ? 'Account unblocked.' : 'Account blocked.');

        return back();
    }

    public function bonus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'balance' => ['required', 'integer', 'min:0', 'max:100000000'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $old = (int) $user->bonus?->balance;

        DB::transaction(function () use ($request, $user, $data, $old) {
            UserBonus::updateOrCreate(['user_id' => $user->id], ['balance' => $data['balance']]);
            $this->audit($user, $request->user(), 'bonus', $data['reason'], (string) $old, (string) $data['balance']);
        });

        $this->toast('Bonus balance updated.');

        return back();
    }

    public function subscription(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['marketing_email' => ['required', 'boolean']]);

        UserSubscription::updateOrCreate(['user_id' => $user->id], ['marketing_email' => $data['marketing_email']]);

        $this->toast('Subscription updated.');

        return back();
    }

    public function clearActivity(User $user): RedirectResponse
    {
        UserActivityLog::where('user_id', $user->id)->delete();

        $this->toast('Activity log cleared.');

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            $this->toast('You cannot delete your own account.', 'error');

            return back();
        }

        $user->delete();

        $this->toast('Customer deleted.');

        return redirect('/admin/users');
    }

    private function audit(User $user, User $admin, string $type, string $reason, ?string $old, ?string $new): void
    {
        UserActionAudit::create([
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'action_type' => $type,
            'reason' => $reason,
            'old_value' => $old,
            'new_value' => $new,
        ]);
    }
}
