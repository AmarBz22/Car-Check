<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    // List reports (with pagination)
    public function index(Request $request)
    {
        $query = Report::with('vehicle');

        // Filter by vehicle id
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $perPage = $request->input('per_page', 10);
        $reports = $query->orderBy('generated_at', 'desc')->paginate($perPage);

        return response()->json($reports);
    }




    // Show single report
    public function show(Report $report)
    {
        $report->load('vehicle');
        return response()->json($report);
    }





    // Create a new report for a vehicle
   public function store(Request $request)
{
    if (!$request->user()->isSource()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $data = $request->validate([
        'vehicle_id' => 'required|exists:vehicles,id',
        'risk_score' => 'nullable|integer|min:0|max:100',
    ]);

    $report = Report::create([
        'vehicle_id'   => $data['vehicle_id'],
        'risk_score'   => $data['risk_score'] ?? null,
        'generated_at' => now(),
        'status'       => 'pending',
        'partner_id'   => $request->user()->id,
    ]);

    return response()->json($report, 201);
}


public function verify(Request $request, Report $report)
{
   

    if ($report->status === 'verified') {
        return response()->json(['message' => 'Report already verified'], 400);
    }

    $report->status = 'verified';
    $report->verified_at = now(); // optional timestamp column
    $report->save();

    return response()->json([
        'message' => 'Report verified successfully',
        'report' => $report
    ]);
}



    // Delete a report
    public function destroy(Report $report)
    {
        if ($report->pdf_path && Storage::exists($report->pdf_path)) {
            Storage::delete($report->pdf_path);
        }

        $report->delete();

        return response()->json(['message' => 'Report deleted']);
    }
}
