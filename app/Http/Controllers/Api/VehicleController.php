<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    /**
     * List vehicles with pagination and optional filters
     */
public function index(Request $request)
{
    $query = Vehicle::with('verifier');

    

    // Search by plate number, VIN, or brand
    $query->when($request->filled('search'), function ($q) use ($request) {
        $search = $request->search;
        return $q->where(function($sub) use ($search) {
            $sub->where('plate_number', 'like', "%{$search}%")
                ->orWhere('vin', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%");
        });
    });

    // Filter by brand
    $query->when($request->filled('brand'), function ($q) use ($request) {
        return $q->where('brand', $request->brand);
    });

    // Filter by year
    $query->when($request->filled('year'), function ($q) use ($request) {
        return $q->where('year', $request->year);
    });

    // IMPORTANT: Check if user_id filter is accidentally nulling results
    $query->when($request->filled('user_id'), function ($q) use ($request) {
        return $q->where('user_id', $request->user_id);
    });

    $perPage = $request->input('per_page', 10);
    
    // Debugging Tip: Uncomment the line below to see the SQL in your network tab
    // return response()->json($query->toSql());

    $vehicles = $query->orderBy('created_at', 'desc')->paginate($perPage);

    return response()->json($vehicles);
}

/**
 * Show a single vehicle
 */
/**
 * Show a single vehicle
 */
public function show($id)
{
    $vehicle = Vehicle::with('images')->findOrFail($id);

    // Convert to array to manipulate
    $vehicleArray = $vehicle->toArray();

    // Format images with full URLs
    if (isset($vehicleArray['images'])) {
        $vehicleArray['images'] = array_map(function($image) {
            return [
                'id' => $image['id'],
                'url' => url('storage/' . $image['image_path']),
                'path' => $image['image_path']
            ];
        }, $vehicleArray['images']);
    }

    return response()->json($vehicleArray);
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
   /**
 * Update vehicle info
 */
/**
 * Update vehicle info
 */
/**
 * Update vehicle info
 */
public function update(Request $request, $id)
{
    $vehicle = Vehicle::findOrFail($id);

    $data = $request->validate([
        'plate_number' => [
            'sometimes',
            'string',
            \Illuminate\Validation\Rule::unique('vehicles', 'plate_number')->ignore($id)
        ],
        'vin' => [
            'nullable',
            \Illuminate\Validation\Rule::unique('vehicles', 'vin')->ignore($id)
        ],
        'brand'        => 'sometimes|string',
        'model'        => 'sometimes|string',
        'year'         => 'sometimes|digits:4',
        'color'        => 'nullable|string',
        'status'       => 'sometimes|in:pending,verified,rejected',
    ]);

    $vehicle->update($data);

    return response()->json($vehicle);
}
    /**
     * Delete a vehicle
     */
public function destroy($id)
{
    try {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json([
            'message' => 'Vehicle deleted successfully'
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to delete vehicle',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function uploadImages(Request $request, Vehicle $vehicle)
{
    $request->validate([
        'images' => 'required|array',
        'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $savedImages = [];

    foreach ($request->file('images') as $image) {
        $path = $image->store('vehicles', 'public');

        $savedImages[] = $vehicle->images()->create([
            'image_path' => $path
        ]);
    }

    return response()->json([
        'message' => 'Images uploaded successfully',
        'images' => $savedImages
    ], 201);
}

}
