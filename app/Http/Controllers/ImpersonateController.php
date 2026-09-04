<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Log in as the specified user (admin impersonation).
     */
    public function impersonate(Request $request, User $user)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        // Store the admin ID so we can return later
        $request->session()->put('impersonator_id', Auth::id());

        // Log in as the target user
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "You are now logged in as {$user->name}.");
    }

    /**
     * Stop impersonating and return to admin dashboard.
     */
    public function stopImpersonate(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($impersonatorId);

        if (! $admin) {
            return redirect()->route('dashboard');
        }

        Auth::login($admin);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Returned to admin dashboard.');
    }
}
