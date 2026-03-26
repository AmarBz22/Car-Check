<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Payment;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * General dashboard stats (Admin only)
     */
    public function dashboard(Request $request)
    {
        return response()->json([
            'users'    => $this->getUserStats(),
            'vehicles' => $this->getVehicleStats(),
            'reports'  => $this->getReportStats(),
            'growth'   => $this->getGrowthStats(),
        ], 200);
    }

    /**
     * Payment dashboard stats (Admin only)
     */
    public function paymentStats(Request $request)
    {
        return response()->json([
            'payments' => $this->getPaymentStats(),
        ], 200);
    }

    // ─────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────

    private function getUserStats()
    {
        return [
            'total'    => User::count(),
            'admins'   => User::where('role', 'admin')->count(),
            'partners' => User::where('role', 'partner')->count(),
            'clients'  => User::where('role', 'client')->count(),
        ];
    }

    private function getVehicleStats()
    {
        return [
            'total' => Vehicle::count(),
        ];
    }

    private function getReportStats()
    {
        return [
            'total'   => Report::count(),
            'per_partner' => Report::select('partner_id')
                ->selectRaw('count(*) as total')
                ->with('partner:id,name,email')
                ->groupBy('partner_id')
                ->orderBy('total', 'desc')
                ->get(),
            'per_vehicle' => Report::select('vehicle_id')
                ->selectRaw('count(*) as total')
                ->with('vehicle:id,model,year')
                ->groupBy('vehicle_id')
                ->orderBy('total', 'desc')
                ->get(),
        ];
    }

 // ... other methods

private function getGrowthStats()
{
    $isSqlite = \DB::getDriverName() === 'sqlite';

    $monthFunc = $isSqlite ? "strftime('%m', created_at)" : "MONTH(created_at)";
    $yearFunc  = $isSqlite ? "strftime('%Y', created_at)" : "YEAR(created_at)";

    return [
        'users_per_month' => User::selectRaw("{$monthFunc} as month, {$yearFunc} as year, count(*) as total")
            ->groupByRaw("{$yearFunc}, {$monthFunc}") // ← groupByRaw instead of groupBy
            ->orderByRaw("{$yearFunc} asc, {$monthFunc} asc")
            ->get(),

        'reports_per_month' => Report::selectRaw("{$monthFunc} as month, {$yearFunc} as year, count(*) as total")
            ->groupByRaw("{$yearFunc}, {$monthFunc}")
            ->orderByRaw("{$yearFunc} asc, {$monthFunc} asc")
            ->get(),

        'vehicles_per_month' => Vehicle::selectRaw("{$monthFunc} as month, {$yearFunc} as year, count(*) as total")
            ->groupByRaw("{$yearFunc}, {$monthFunc}")
            ->orderByRaw("{$yearFunc} asc, {$monthFunc} asc")
            ->get(),
    ];
}

private function getPaymentStats()
{
    $isSqlite = \DB::getDriverName() === 'sqlite';
    $monthFunc = $isSqlite ? "strftime('%m', created_at)" : "MONTH(created_at)";
    $yearFunc  = $isSqlite ? "strftime('%Y', created_at)" : "YEAR(created_at)";

    return [
        'total_revenue' => Payment::where('status', 'paid')->sum('amount'),
        'paid_vs_unpaid' => [
            'paid'    => Payment::where('status', 'paid')->count(),
            'unpaid'  => Payment::where('status', 'unpaid')->count(),
            'pending' => Payment::where('status', 'pending')->count(),
        ],
        'revenue_per_partner' => Payment::where('status', 'paid')
            ->select('user_id')
            ->selectRaw('sum(amount) as total_revenue, count(*) as total_payments')
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderBy('total_revenue', 'desc')
            ->get(),
        'revenue_per_month' => Payment::where('status', 'paid')
            ->selectRaw("{$monthFunc} as month, {$yearFunc} as year, sum(amount) as total_revenue")
            ->groupByRaw("{$yearFunc}, {$monthFunc}") // ← fix here too
            ->orderByRaw("{$yearFunc} asc, {$monthFunc} asc")
            ->get(),
    ];
}
}

