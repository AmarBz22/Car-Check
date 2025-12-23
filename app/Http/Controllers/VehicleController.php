<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    /**
     * List vehicles with pagination and optional filters
     */
    public function index(Request $request)
    {
        $query = Vehicle::with('verifier'); // eager load verifier

        // Filter by status if provided (pending, verified, rejected)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by plate number or VIN
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        // Paginate results (default 10 per page)
        $perPage = $request->input('per_page', 10);
        $vehicles = $query->orderBy('created_at', 'desc')
                          ->paginate($perPage);

        return response()->json($vehicles);
    }

    /**
     * Show a single vehicle
     */
    public function show(Vehicle $vehicle)
    {
        $vehicle->load('verifier', 'reports'); // load related data
        return response()->json($vehicle);
    }

    /**
     * Create a new vehicle
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'plate_number' => 'required|unique:vehicles,plate_number',
            'vin'          => 'nullable|unique:vehicles,vin',
            'brand'        => 'required|string',
            'model'        => 'required|string',
            'year'         => 'required|digits:4',
            'color'        => 'nullable|string',
        ]);

        $vehicle = Vehicle::create($data);

        return response()->json($vehicle, 201);
    }

    /**
     * Update vehicle info
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'brand'  => 'sometimes|string',
            'model'  => 'sometimes|string',
            'year'   => 'sometimes|digits:4',
            'color'  => 'sometimes|string',
            'status' => 'sometimes|in:pending,verified,rejected',
        ]);

        $vehicle->update($data);

        return response()->json($vehicle);
    }

    /**
     * Delete a vehicle
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted']);
    }
}
