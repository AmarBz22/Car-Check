<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Get all users with filters and pagination (Admin only)
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);

        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->query('role'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        // Search by name or email
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->query('search') . '%')
                  ->orWhere('email', 'like', '%' . $request->query('search') . '%');
            });
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($users, 200);
    }

    /**
     * Get a specific user by ID (Admin only)
     */
    public function show(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json($user, 200);
    }

    /**
     * Create a new user (Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:admin,partner,client',
            'status'   => 'sometimes|in:active,inactive,approved',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'status'   => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user'    => $user,
        ], 201);
    }

    /**
     * Update a user (Admin only)
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'role'     => 'sometimes|in:admin,partner,client',
            'status'   => 'sometimes|in:active,inactive,approved',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => $user,
        ], 200);
    }

    /**
     * Delete a user (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Prevent admin from deleting themselves
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }
}




