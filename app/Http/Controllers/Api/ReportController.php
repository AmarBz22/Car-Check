<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\ReportGeneratedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Create a new report (Partner only)
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
        ]);

        $report->load(['vehicle', 'partner']);

        // Generate PDF immediately after creation
        $pdfPath = $this->generateReportPdf($report);
        $report->update(['pdf_path' => $pdfPath]);

        // Notify admins + vehicle owner
        $this->notifyReportGenerated($report);

        return response()->json([
            'message' => 'Report created successfully',
            'report'  => $report->fresh(['vehicle', 'partner']),
        ], 201);
    }

    /**
     * Get all reports for the authenticated partner
     */
    public function getPartnerReports(Request $request)
    {
        $query = Report::where('partner_id', $request->user()->id)
            ->with(['vehicle', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate(15)
        );
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

        // Regenerate PDF with updated data
        $report->load(['vehicle', 'partner']);
        $pdfPath = $this->generateReportPdf($report);
        $report->update(['pdf_path' => $pdfPath]);

        return response()->json([
            'message' => 'Report updated successfully',
            'report'  => $report->fresh(['vehicle', 'partner']),
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

        // Delete PDF file if exists
        if ($report->pdf_path && Storage::disk('private')->exists($report->pdf_path)) {
            Storage::disk('private')->delete($report->pdf_path);
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

        if ($request->filled('type')) {
            $query->where('report_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Get all reports for a specific vehicle
     */
    public function getVehicleReports(Request $request, $vehicleId)
    {
        Vehicle::findOrFail($vehicleId);

        $reports = Report::where('vehicle_id', $vehicleId)
            ->with(['partner', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return response()->json($reports);
    }

    /**
     * Download report PDF (client with active payment)
     */
    public function downloadPdf(Request $request, $reportId)
    {
        $report = Report::findOrFail($reportId);
        $user   = $request->user();

        $hasAccess = \App\Models\Payment::activeAccessFor($user->id, $report->vehicle_id)->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Access denied. Purchase the report or your 48-hour window has expired.',
            ], 403);
        }

        if (!$report->pdf_path || !Storage::disk('private')->exists($report->pdf_path)) {
            return response()->json(['message' => 'PDF not found.'], 404);
        }

        return Storage::disk('private')->download(
            $report->pdf_path,
            "vincheck-report-{$report->id}.pdf"
        );
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE — Generate PDF
    // ─────────────────────────────────────────────────────────────

    private function generateReportPdf(Report $report): string
    {
        $pdf  = Pdf::loadView('pdf.report', ['report' => $report]);
        $path = "reports/report-{$report->id}.pdf";

        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE — Notify
    // ─────────────────────────────────────────────────────────────

    private function notifyReportGenerated(Report $report): void
    {
        // Notify all admins
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new ReportGeneratedNotification($report));

        // Notify vehicle owner (client)
        $client = $report->vehicle->user;
        if ($client) {
            $client->notify(new ReportGeneratedNotification($report));
        }
    }
}
