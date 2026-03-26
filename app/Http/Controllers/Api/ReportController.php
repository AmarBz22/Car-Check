<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Vehicle;
use App\Notifications\ReportGeneratedNotification;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Create a new report (Partner only)
     * Reports from trusted partners are auto-approved immediately.
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'vehicle_id'  => 'required|exists:vehicles,id',
        'report_type' => 'required|in:scanner,mechanic,auto_body_technician',
        'findings'    => 'required|array',
        'kilometrage' => 'nullable|integer|min:0',
        'payment_id'  => 'nullable|exists:payments,id',
    ]);

    Vehicle::findOrFail($validated['vehicle_id']);

    $report = Report::create([
        'vehicle_id'  => $validated['vehicle_id'],
        'partner_id'  => $request->user()->id,
        'report_type' => $validated['report_type'],
        'findings'    => $validated['findings'],
        'kilometrage' => $validated['kilometrage'] ?? null,
        'payment_id'  => $validated['payment_id'] ?? null,
        'report_date' => now(),
        // ❌ removed 'status' => 'approved'
    ]);

    $report->load(['vehicle', 'partner']);

    // Notify vehicle owner the report is available
    $this->notifyReportGenerated($report);

    return response()->json([
        'message' => 'Report created successfully',
        'report'  => $report,
    ], 201);
}

    /**
     * Get all reports for the authenticated partner
     */
    public function getPartnerReports(Request $request)
    {
        $reports = Report::where('partner_id', $request->user()->id)
            ->with(['vehicle', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($reports);
    }

    /**
     * Get a specific report
     */
    public function show(Request $request, $reportId)
    {
        $report = Report::with(['vehicle', 'partner', 'payment'])->findOrFail($reportId);

        return response()->json($report, 200);
    }

    /**
     * Update a report (Partner only)
     */
    public function update(Request $request, $reportId)
    {
        $report = Report::findOrFail($reportId);

        if ($report->partner_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'report_type' => 'sometimes|in:scanner,mechanic,auto_body_technician',
            'findings'    => 'sometimes|array',
            'kilometrage' => 'nullable|integer|min:0',
        ]);

        $report->update($validated);

        return response()->json([
            'message' => 'Report updated successfully',
            'report'  => $report->load('vehicle', 'partner'),
        ], 200);
    }

    /**
     * Delete a report (Partner only)
     */
    public function destroy(Request $request, $reportId)
    {
        $report = Report::findOrFail($reportId);

        if ($report->partner_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $report->delete();

        return response()->json(['message' => 'Report deleted successfully'], 200);
    }

    /**
     * Get all reports (Admin only)
     */
    public function getAllReports(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Report::with(['vehicle', 'partner', 'payment'])
            ->orderBy('created_at', 'desc');

        if ($request->query('type')) {
            $query->where('report_type', $request->query('type'));
        }

        $reports = $query->paginate(15);

        return response()->json($reports);
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE — Send report notifications
    // ─────────────────────────────────────────────────────────────

    private function notifyReportGenerated(Report $report): void
    {
        // Notify vehicle owner (client)
        $client = $report->vehicle->user;
        if ($client) {
            $client->notify(new ReportGeneratedNotification($report));
        }
    }
    /**
 * Get all reports for a specific vehicle
 */
public function getVehicleReports(Request $request, $vehicleId)
{
    Vehicle::findOrFail($vehicleId); // 404 if vehicle doesn't exist

    $reports = Report::where('vehicle_id', $vehicleId)
        ->with(['partner', 'payment'])
        ->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

    return response()->json($reports);
}
}
