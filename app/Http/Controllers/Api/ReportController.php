<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class ReportController extends Controller
{
/**
 * Create a new report (Partner only)
 * Can create scanner, mechanic, or auto body technician reports
 */
public function store(Request $request)
{
    // Check if user is a partner
    // if (!$request->user()->isPartner()) {
    //     return response()->json([
    //         'message' => 'Only partners can create reports'
    //     ], 403);
    // }

    // Validate request data
    $validated = $request->validate([
        'vehicle_id'  => 'required|exists:vehicles,id',
        'report_type' => 'required|in:scanner,mechanic,auto_body_technician',
        'findings'    => 'required|array',
        'kilometrage' => 'nullable|integer|min:0',
        'payment_id'  => 'nullable|exists:payments,id',
        'status'      => 'sometimes|in:draft,submitted',
    ]);

    // Verify vehicle exists
    $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

    // Create report with partner's chosen status, defaulting to draft
    $report = Report::create([
        'vehicle_id'  => $validated['vehicle_id'],
        'partner_id'  => $request->user()->id,
        'report_type' => $validated['report_type'],
        'findings'    => $validated['findings'],
        'kilometrage' => $validated['kilometrage'] ?? null,
        'payment_id'  => $validated['payment_id'] ?? null,
        'status'      => $validated['status'] ?? 'draft',
        'report_date' => now(),
    ]);

    return response()->json([
        'message' => 'Report created successfully',
        'report'  => $report->load('vehicle', 'partner'),
    ], 201);
}
    /**
     * Get all reports for the authenticated partner
     */
    public function getPartnerReports(Request $request)
    {
        // if (!$request->user()->isPartner()) {
        //     return response()->json([
        //         'message' => 'Only partners can view reports'
        //     ], 403);
        // }

        $status = $request->query('status'); // draft, submitted, approved, rejected

        $query = Report::where('partner_id', $request->user()->id)
            ->with(['vehicle', 'payment']);

        if ($status) {
            $query->where('status', $status);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($reports);
    }

    /**
     * Get a specific report details (Partner can view own, Admin can view all)
     */
    public function show(Request $request, $reportId)
    {
        $report = Report::with(['vehicle', 'partner', 'payment'])->findOrFail($reportId);

        // Check authorization: partner can view own, admin can view all
        // if ($request->user()->isPartner() && $report->partner_id !== $request->user()->id) {
        //     return response()->json([
        //         'message' => 'Unauthorized'
        //     ], 403);
        // }

        return response()->json($report, 200);
    }

    /**
     * Update a draft report (Partner only)
     */
    public function update(Request $request, $reportId)
    {
        // if (!$request->user()->isPartner()) {
        //     return response()->json([
        //         'message' => 'Only partners can update reports'
        //     ], 403);
        // }

        $report = Report::findOrFail($reportId);

        // Check if report belongs to partner
        if ($report->partner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Can only update draft reports
        if ($report->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot update submitted or approved reports'
            ], 400);
        }

        // Validate request data
        $validated = $request->validate([
            'report_type' => 'sometimes|in:scanner,mechanic,auto_body_technician',
            'findings' => 'sometimes|array',
            'kilometrage' => 'nullable|integer|min:0',
        ]);

        $report->update($validated);

        return response()->json([
            'message' => 'Report updated successfully',
            'report' => $report->load('vehicle', 'partner'),
        ], 200);
    }

    /**
     * Submit a report (change status from draft to submitted)
     */
    public function submit(Request $request, $reportId)
    {
        // if (!$request->user()->isPartner()) {
        //     return response()->json([
        //         'message' => 'Only partners can submit reports'
        //     ], 403);
        // }

        $report = Report::findOrFail($reportId);

        // Check if report belongs to partner
        if ($report->partner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Can only submit draft reports
        if ($report->status !== 'draft') {
            return response()->json([
                'message' => 'Report is already ' . $report->status
            ], 400);
        }

        $report->update([
            'status' => 'submitted',
        ]);

        return response()->json([
            'message' => 'Report submitted successfully',
            'report' => $report->load('vehicle', 'partner'),
        ], 200);
    }

    /**
     * Delete a draft report (Partner only)
     */
    public function destroy(Request $request, $reportId)
    {
        // if (!$request->user()->isPartner()) {
        //     return response()->json([
        //         'message' => 'Only partners can delete reports'
        //     ], 403);
        // }

        $report = Report::findOrFail($reportId);

        // Check if report belongs to partner
        if ($report->partner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Can only delete draft reports
        if ($report->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot delete submitted or approved reports'
            ], 400);
        }

        $report->delete();

        return response()->json([
            'message' => 'Report deleted successfully'
        ], 200);
    }

    /**
     * Get all pending reports (Admin only)
     */
    public function getPendingReports(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can view pending reports'
            ], 403);
        }

        $reports = Report::pending()
            ->with(['vehicle', 'partner', 'payment'])
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return response()->json($reports);
    }

    /**
     * Approve a report (Admin only)
     */
    public function approveReport(Request $request, $reportId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can approve reports'
            ], 403);
        }

        $validated = $request->validate([
            'risk_score' => 'sometimes|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $report = Report::findOrFail($reportId);

        if ($report->status !== 'submitted') {
            return response()->json([
                'message' => 'Can only approve submitted reports'
            ], 400);
        }

        $report->update([
            'status' => 'approved',
            'risk_score' => $validated['risk_score'] ?? $report->risk_score,
        ]);

        return response()->json([
            'message' => 'Report approved successfully',
            'report' => $report->load('vehicle', 'partner'),
        ], 200);
    }

    /**
     * Reject a report (Admin only)
     */
    public function rejectReport(Request $request, $reportId)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can reject reports'
            ], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $report = Report::findOrFail($reportId);

        if ($report->status !== 'submitted') {
            return response()->json([
                'message' => 'Can only reject submitted reports'
            ], 400);
        }

        $report->update([
            'status' => 'rejected',
        ]);

        return response()->json([
            'message' => 'Report rejected',
            'reason' => $validated['rejection_reason'],
            'report' => $report->load('vehicle', 'partner'),
        ], 200);
    }
}
