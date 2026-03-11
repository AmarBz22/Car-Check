<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\RegistrationRequest;

class AuthController extends Controller
{
    // REGISTER - Only for Clients
public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $user = User::create([
    'name'     => $request->name,
    'email'    => $request->email,
    'password' => $request->password, // ← plain, cast hashes it
    'role'     => 'client',
    'status'   => 'active',
]);

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'message' => 'Client registered successfully',
        'user' => $user,
        'token' => $token
    ], 201);
}


    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:registration_requests,email|unique:users,email',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'reason' => 'nullable|string|max:1000',
        ]);

        $registrationRequest = RegistrationRequest::create([
            'name' => $request->name,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Registration request submitted. Admin will review and approve.',
            'request' => $registrationRequest
        ], 201);
    }

    public function registerAdmin(Request $request)
    {
        // 🔐 Temporary secret protection
        if ($request->header('X-ADMIN-SECRET') !== env('ADMIN_SECRET')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $token = $admin->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Admin registered successfully',
            'user' => $admin,
            'token' => $token
        ], 201);
    }

    /**
     * Set password for approved partner account
     * Used after clicking password reset link
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify password reset token
        $status = Password::verify(
            $request->only('email', 'password', 'token')
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Invalid or expired password reset token',
                'status' => $status
            ], 422);
        }

        // Find user and update password
        $user = User::where('email', $request->email)->firstOrFail();

        $user->update([
            'password' => bcrypt($request->password),
            'status' => 'active',
        ]);

        // Mark token as used
        Password::broker()->deleteToken($user);

        return response()->json([
            'message' => 'Password set successfully! You can now login.',
            'user' => $user
        ]);
    }

}


