<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Partner;
use Illuminate\Support\Facades\Hash;

class PartnerController extends Controller
{
    /**
     * List all partners (paginated)
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $partners = Partner::paginate($perPage);

        return response()->json($partners);
    }

    /**
     * Show single partner
     */
    public function show(Partner $partner)
    {
        $partner->load('vehicles'); // vehicles verified by this partner
        return response()->json($partner);
    }

    /**
     * Create a new partner (admin only)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $data['role'] = 'source'; // force role
        $data['password'] = Hash::make($data['password']);

        $partner = Partner::create($data);

        return response()->json($partner, 201);
    }

    /**
     * Update partner info
     */
    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $partner->id,
            'password' => 'sometimes|string|min:6|confirmed',
        ]);

        if(isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $partner->update($data);

        return response()->json($partner);
    }

    /**
     * Delete partner (admin only)
     */
    public function destroy(Partner $partner)
    {
        $partner->delete();
        return response()->json(['message' => 'Partner deleted']);
    }
}
