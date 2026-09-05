<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse; // Import RedirectResponse

class TenantLinkController extends Controller
{
    /**
     * Handle the request to link a user to a tenant using an invitation code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function link(Request $request): RedirectResponse
    {
        // Validate the incoming request data
        $request->validate([
            'invitation_code' => ['required', 'string', 'exists:tenants,invitation_code'],
            'email' => ['required', 'string', 'email', 'exists:users,email'],
        ]);

        // Find the tenant by the invitation code
        $tenant = Tenant::where('invitation_code', $request->invitation_code)->first();

        // Find the user by the provided email
        $user = User::where('email', $request->email)->first();

        // Security check: Ensure the currently authenticated user is the one whose email was provided.
        // This prevents a logged-in user from changing another user's tenant_id.
        if (Auth::id() !== $user->id) {
            throw ValidationException::withMessages([
                'email' => 'The provided email does not match your currently logged-in account.',
            ]);
        }

        // Check if the user is already part of this tenant
        if ($user->tenant_id === $tenant->id) {
            throw ValidationException::withMessages([
                'invitation_code' => 'You are already a member of this tenant.',
            ]);
        }

        // Update the user's tenant_id
        $user->tenant_id = $tenant->id;
        $user->save();

        // Logout the user to refresh their session with the new tenant_id.
        // This is important because the tenant_id is often loaded into the session.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to the login page with a success message
        return redirect()->route('login')->with('status', 'Successfully joined the tenant! Please log in again.');
    }
}

