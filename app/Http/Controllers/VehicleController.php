<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    // List all vehicles
    public function index()
    {
        $vehicles = Vehicle::with('user')->get();
        return response()->json($vehicles);
    }

    // Create a vehicle
    public function store(Request $request)
{
    $request->validate([
        'chassis' => 'required|string|unique:vehicles,chassis',
        'brand' => 'nullable|string',
        'model' => 'nullable|string',
        'year' => 'nullable|integer',
        'engine' => 'nullable|string',
    ]);

    // Use the authenticated user ID
    $vehicle = \App\Models\Vehicle::create([
        'user_id' => $request->user()->id, // <-- set automatically
        'chassis' => $request->chassis,
        'brand' => $request->brand,
        'model' => $request->model,
        'year' => $request->year,
        'engine' => $request->engine,
    ]);

    return response()->json($vehicle, 201);
}


    // Show a vehicle
    public function show($id)
    {
        $vehicle = Vehicle::with('user')->findOrFail($id);
        return response()->json($vehicle);
    }

    // Update a vehicle
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $request->validate([
            'chassis' => 'unique:vehicles,chassis,' . $id . '|max:17',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'engine' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $vehicle->update($request->all());

        return response()->json($vehicle);
    }

    // Delete a vehicle
    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully']);
    }
}
