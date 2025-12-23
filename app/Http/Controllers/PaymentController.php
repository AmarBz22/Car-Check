<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\Report;

class PaymentController extends Controller
{
    /**
     * Step 1: Create checkout and return payment URL to client
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $user = $request->user();
        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        $amount = 25000; // Example amount in DZD

        // Create checkout via Chargily API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . 'test_pk_fy5M8qnnFOMBeMx54BenzI4ltWOeYzS9jvnkhVNf',
        ])->post('https://pay.chargily.net/test/api/v2/checkouts' , [
            'amount' => $amount,
            'currency' => 'dzd',
            'description' => "Payment for vehicle report #{$vehicle->id}",
            'metadata' => [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
            ],
            'success_url' => route('reports.payment_back'),
            'failure_url' => route('reports.payment_back'),
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Failed to create payment'], 500);
        }

        $data = $response->json();

        return response()->json([
            'payment_url' => $data['payment_url'],
            'checkout_id' => $data['id'],
        ]);
    }

    /**
     * Step 2: Webhook - Called by Chargily when payment completes
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        if (!isset($payload['invoice_id'], $payload['status'], $payload['metadata'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $metadata = $payload['metadata'];

        $status = $payload['status'] === 'paid' ? 'paid' : 'failed';

        // Create Payment record only on webhook
        $payment = Payment::create([
            'user_id' => $metadata['user_id'] ?? null,
            'vehicle_id' => $metadata['vehicle_id'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? 'DZD',
            'chargily_payment_id' => $payload['invoice_id'],
            'status' => $status,
        ]);

        // Optionally grant report access if paid
        if ($status === 'paid') {
            // Logic: grant access to reports of this vehicle
            // e.g., mark reports as available for user
        }

        return response()->json(['message' => 'Payment processed', 'payment' => $payment]);
    }

    /**
     * Optional: Back page when user returns from Chargily
     * Shows payment status but does NOT process payment
     */
    public function paymentBack(Request $request)
    {
        return response()->json([
            'message' => 'You can now check your payment status',
            'checkout_id' => $request->input('checkout_id'),
        ]);
    }
}
