<?php
// app/Http/Controllers/Api/PaymentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Notifications\PaymentConfirmedNotification;

class PaymentController extends Controller
{
    private string $apiKey;
    private string $baseUrl;
    private string $webhookSecret;

    public function __construct()
    {
        $this->apiKey        = config('services.chargily.secret_key');
        $this->baseUrl       = config('services.chargily.base_url');
        $this->webhookSecret = config('services.chargily.webhook_secret');
    }

    // ─────────────────────────────────────────────────────────────
    // STEP 1 — Initiate checkout
    // ─────────────────────────────────────────────────────────────

 public function createPayment(Request $request)
{
    $request->validate([
        'vehicle_id' => 'required|exists:vehicles,id',
    ]);

    $user    = $request->user();
    $vehicle = Vehicle::findOrFail($request->vehicle_id);

    // Block if user already has active access
    $existing = Payment::activeAccessFor($user->id, $vehicle->id)->first();
    if ($existing) {
        return response()->json([
            'message'    => 'You already have active access to this vehicle\'s reports.',
            'expires_at' => $existing->expires_at,
        ], 409);
    }

    // Get the latest report for this vehicle
    $report = Report::where('vehicle_id', $vehicle->id)
        ->latest()
        ->first();

    if (!$report) {
        return response()->json([
            'message' => 'No report is available for this vehicle yet.',
        ], 404);
    }

    $amount = config('services.chargily.report_price');

    // Create PENDING payment before redirecting user
    $payment = Payment::create([
        'user_id'    => $user->id,
        'vehicle_id' => $vehicle->id,
        'amount'     => $amount,
        'currency'   => 'dzd',
        'status'     => 'pending',
    ]);

    $response = Http::withHeaders([
        'Authorization' => "Bearer {$this->apiKey}",
    ])->withOptions([
        'verify' => false, // ⚠️ LOCAL TESTING ONLY — remove before production
    ])->post("{$this->baseUrl}/checkouts", [
        'amount'      => $amount,
        'currency'    => 'dzd',
        'description' => "Vehicle report – {$vehicle->chassis_number}",
        'metadata'    => [
            'payment_id' => $payment->id,
            'user_id'    => $user->id,
            'vehicle_id' => $vehicle->id,
            'report_id'  => $report->id,
        ],
        'success_url' => route('payment.back'),
        'failure_url' => route('payment.back'),
    ]);

    if ($response->failed()) {
        $payment->update(['status' => 'failed']);

        Log::error('Chargily checkout failed', [
            'payment_id' => $payment->id,
            'error'      => $response->json(),
        ]);

        return response()->json([
            'message' => 'Could not initiate payment. Please try again.',
        ], 502);
    }

    $data = $response->json();

    $payment->update(['chargily_payment_id' => $data['id']]);

    return response()->json([
        'payment_url' => $data['checkout_url'],
        'checkout_id' => $data['id'],
    ]);
}
    // ─────────────────────────────────────────────────────────────
    // STEP 2 — Webhook (Chargily → your server)
    // ─────────────────────────────────────────────────────────────

public function webhook(Request $request)
{
    if (!$this->verifyWebhookSignature($request)) {
        Log::warning('Webhook: invalid signature', ['ip' => $request->ip()]);
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $payload  = $request->json()->all();
    $event    = $payload['type'] ?? '';

    if ($event !== 'checkout.paid') {
        return response()->json(['message' => 'Event ignored'], 200);
    }

    $checkout = $payload['data'] ?? [];
    $metadata = $checkout['metadata'] ?? [];

    if (empty($metadata['payment_id']) || empty($metadata['report_id'])) {
        Log::error('Webhook: missing metadata', $payload);
        return response()->json(['message' => 'Invalid metadata'], 400);
    }

    $payment = Payment::find($metadata['payment_id']);

    if (!$payment) {
        Log::error('Webhook: payment not found', $metadata);
        return response()->json(['message' => 'Payment not found'], 404);
    }

    if ($payment->status === 'paid') {
        return response()->json(['message' => 'Already processed'], 200);
    }

    // Confirm payment and open 48h access window
    $payment->update([
        'status'     => 'paid',
        'expires_at' => now()->addHours(48),
    ]);

    // Link report to this payment
    Report::where('id', $metadata['report_id'])
        ->where('vehicle_id', $payment->vehicle_id)
        ->update(['payment_id' => $payment->id]);

    // Load relationships needed for notification
    $payment->load(['user', 'vehicle', 'report']);

    // Notify the client
    $payment->user->notify(new \App\Notifications\PaymentConfirmedNotification($payment));

    Log::info('Payment confirmed + report unlocked', [
        'payment_id' => $payment->id,
        'report_id'  => $metadata['report_id'],
        'expires_at' => $payment->expires_at,
    ]);

    return response()->json(['message' => 'Payment confirmed']);
}

    // ─────────────────────────────────────────────────────────────
    // STEP 3 — User lands back after Chargily redirect
    // ─────────────────────────────────────────────────────────────

public function paymentBack(Request $request)
{
    $checkoutId = $request->input('checkout_id');

    $payment = Payment::where('chargily_payment_id', $checkoutId)
        ->with('report')
        ->first();

    if (!$payment) {
        return response()->json(['message' => 'Payment not found.'], 404);
    }

    return response()->json([
        'status'     => $payment->status,
        'expires_at' => $payment->expires_at,
        'report_id'  => $payment->report?->id,
        'message'    => match ($payment->status) {
            'paid'    => 'Payment confirmed. You have 48 hours to download your report.',
            'pending' => 'Payment is still processing. Please check back shortly.',
            'failed'  => 'Payment failed. Please try again.',
            default   => 'Unknown status.',
        },
    ]);
}

    // ─────────────────────────────────────────────────────────────
    // DOWNLOAD — 48h access guard
    // ─────────────────────────────────────────────────────────────

    public function downloadReport(Request $request, Report $report)
    {
        $user = $request->user();

        // Load payment to check access
        $report->load('payment');

        if (!$report->isAccessibleBy($user->id)) {
            return response()->json([
                'message' => 'Access denied. Purchase the report or your 48-hour window has expired.',
            ], 403);
        }

        if (!$report->pdf_path || !Storage::disk('private')->exists($report->pdf_path)) {
            return response()->json(['message' => 'Report PDF not found.'], 404);
        }

        return Storage::disk('private')->download(
            $report->pdf_path,
            "vehicle-report-{$report->id}.pdf"
        );
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE — HMAC signature check
    // ─────────────────────────────────────────────────────────────

    private function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('signature');

        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    // ─────────────────────────────────────────────────────────────
// GET ALL PAYMENTS — Admin only
// ─────────────────────────────────────────────────────────────

public function index(Request $request)
{
    $query = Payment::with(['user', 'vehicle', 'report']);

    // Filter by status
    $query->when($request->filled('status'), function ($q) use ($request) {
        return $q->where('status', $request->status);
    });

    // Filter by user
    $query->when($request->filled('user_id'), function ($q) use ($request) {
        return $q->where('user_id', $request->user_id);
    });

    // Filter by vehicle
    $query->when($request->filled('vehicle_id'), function ($q) use ($request) {
        return $q->where('vehicle_id', $request->vehicle_id);
    });

    $payments = $query->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

    return response()->json($payments);
}

// ─────────────────────────────────────────────────────────────
// GET SINGLE PAYMENT — Admin only
// ─────────────────────────────────────────────────────────────

public function show(Payment $payment)
{
    $payment->load(['user', 'vehicle', 'report']);

    return response()->json([
        'payment'    => $payment,
        'has_access' => $payment->hasActiveAccess(),
    ]);
}
}

