<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\PartnerApprovedMail;
use App\Models\RegistrationRequest;
use App\Models\User;

class PartnerRequestController extends Controller
{
    /**
     * Get all pending partner registration requests (Admin only)
     */
    public function getPendingRequests(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requests = RegistrationRequest::pending()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($requests);
    }

    /**
     * Approve partner registration request and create user (Admin only)
     */
public function approveRequest(Request $request, $requestId)
{
    if (!$request->user()->isAdmin()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $registrationRequest = RegistrationRequest::findOrFail($requestId);

    if ($registrationRequest->status !== 'pending') {
        return response()->json([
            'message' => 'Request already processed',
            'status' => $registrationRequest->status
        ], 400);
    }

    // Check if user already exists
    $existingUser = User::where('email', $registrationRequest->email)->first();
    if ($existingUser) {
        return response()->json([
            'message' => 'User with this email already exists',
            'email' => $registrationRequest->email
        ], 422);
    }

    // Create partner user with null password (user must set it)
    $partner = User::create([
        'name' => $registrationRequest->name,
        'email' => $registrationRequest->email,
        'password' => null,
        'role' => 'partner',
        'status' => 'approved',
    ]);

    // Generate password reset token
    $resetToken = Password::createToken($partner);

    // ✅ FIXED: Send approval email with password setup link
    Mail::to($partner->email)->send(new PartnerApprovedMail($partner, $resetToken));

    // Mark request as approved
    $registrationRequest->approve();

    return response()->json([
        'message' => 'Partner approved successfully. Email sent with password setup link.',
        'user' => $partner,
        'request' => $registrationRequest
    ], 201);
}

    /**
     * Reject partner registration request (Admin only)
     */
  public function rejectRequest(Request $request, $requestId)
{
    // 1. Double-check Authorization (peer-level security)
    if (!$request->user() || $request->user()->role !== 'admin') {
        return response()->json(['message' => 'Forbidden: Admins only'], 403);
    }

    // 2. Wrap in a Transaction for data integrity
    return \DB::transaction(function () use ($requestId) {
        
        // 3. Find with a Lock to prevent "Double-Click" race conditions
        $registrationRequest = RegistrationRequest::where('id', $requestId)
            ->lockForUpdate()
            ->firstOrFail();

        // 4. Validate State
        if ($registrationRequest->status !== 'pending') {
            return response()->json([
                'message' => 'This request has already been handled.',
                'current_status' => $registrationRequest->status
            ], 422);
        }

        // 5. Explicitly update the status
        // This is safer than calling $registrationRequest->reject() 
        // because it bypasses any potential bugs in that custom method.
        $registrationRequest->status = 'rejected';
        $registrationRequest->save();


        return response()->json([
            'message' => 'Registration request rejected successfully.',
            'request' => $registrationRequest->fresh() // Return the latest data
        ], 200);
    });
}

    /**
     * Get single registration request details (Admin only)
     */
    public function getRequestDetails(Request $request, $requestId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $registrationRequest = RegistrationRequest::findOrFail($requestId);

        return response()->json($registrationRequest);
    }

    /**
     * Create partner directly (Admin only - no registration request needed)
     * Can create with password (status=active) or without (status=approved, sends email)
     */
    public function createDirectPartner(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8', // Optional password
            'send_email' => 'nullable|boolean', // Whether to send password setup email (ignored if password provided)
        ]);

        try {
            // Determine if creating with password or without
            $withPassword = !empty($validated['password']);

            if ($withPassword) {
                // Create partner with password (status=active, no email)
                $partner = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => $validated['password'], // Mutator will bcrypt it
                    'role' => 'partner',
                    'status' => 'active', // Active immediately
                ]);

                return response()->json([
                    'message' => 'Partner created successfully. Password set - ready to login.',
                    'user' => $partner,
                    'company_name' => $validated['company_name'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'login_ready' => true,
                ], 201);

            } else {
                // Create partner without password (status=approved, send email with reset link)
                $partner = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'role' => 'partner',
                    'status' => 'approved',
                ]);

                // Explicitly set password to null
                $partner->update(['password' => null]);

                // Generate password reset token
                $resetToken = Password::createToken($partner);

                // Send approval email with password setup link
                Mail::to($partner->email)->send(new PartnerApprovedMail($partner, $resetToken));

                return response()->json([
                    'message' => 'Partner created successfully. Password setup email sent.',
                    'user' => $partner,
                    'company_name' => $validated['company_name'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'login_ready' => false,
                    'email_sent' => true,
                ], 201);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating partner: ' . $e->getMessage()
            ], 500);
        }
    }
}
