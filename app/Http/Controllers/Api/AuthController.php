<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\RegistrationRequest;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PartnerRegistrationRequestNotification;

class AuthController extends Controller
{
    // REGISTER - Only for Clients
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password, // model cast 'hashed' handles bcrypt
            'role'     => 'client',
            'status'   => 'active',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Client registered successfully',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // BUG 1 FIX: check credentials first, then status separately
        // This avoids leaking whether an email exists in the system
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Partner Registration Request - Submit request to join as partner
     */
    public function requestPartnerRegistration(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:registration_requests,email|unique:users,email',
            'company_name' => 'required|string|max:255',
            'phone'        => 'nullable|string',
            'reason'       => 'nullable|string|max:1000',
        ]);

        $registrationRequest = RegistrationRequest::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'company_name' => $request->company_name,
            'phone'        => $request->phone,
            'reason'       => $request->reason,
            'status'       => 'pending',
        ]);

        // Notify all admins about the new partner request
        $admins = User::where('role', 'admin')->get();
        Notification::send(
            $admins,
            new PartnerRegistrationRequestNotification($registrationRequest)
        );

        return response()->json([
            'message' => 'Registration request submitted. Admin will review and approve.',
            'request' => $registrationRequest,
        ], 201);
    }

    public function registerAdmin(Request $request)
    {
        // BUG WARN FIX: use config() instead of env() — env() returns null after config:cache
        if ($request->header('X-ADMIN-SECRET') !== config('app.admin_secret')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // BUG 2 FIX: use plain password — model cast 'hashed' handles bcrypt
        // Using bcrypt() directly would double-hash if the cast is present on the model
        $admin = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $token = $admin->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Admin registered successfully',
            'user'    => $admin,
            'token'   => $token,
        ], 201);
    }

    /**
     * Set password for approved partner account
     * Used after clicking password reset link
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email|exists:users,email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        // BUG 3 FIX: Password::verify() does not exist — correct method is Password::reset()
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // model cast handles hashing
                    'status'   => 'active',
                ])->save();

                // Revoke all existing tokens on password reset for security
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status), // returns translated Laravel status string
            ], 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        return response()->json([
            'message' => 'Password set successfully! You can now login.',
            'user'    => $user,
        ]);
    }
}
