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
            'by_status' => [
                'draft'     => Report::where('status', 'draft')->count(),
                'submitted' => Report::where('status', 'submitted')->count(),
                'approved'  => Report::where('status', 'approved')->count(),
                'rejected'  => Report::where('status', 'rejected')->count(),
            ],
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

    private function getGrowthStats()
    {
        return [
            'users_per_month' => User::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, count(*) as total')
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get(),
            'reports_per_month' => Report::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, count(*) as total')
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get(),
            'vehicles_per_month' => Vehicle::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, count(*) as total')
    ->groupByRaw('YEAR(created_at), MONTH(created_at)')
    ->orderBy('year', 'asc')
    ->orderBy('month', 'asc')
    ->get(),
        ];
    }

    private function getPaymentStats()
    {
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
                ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, sum(amount) as total_revenue')
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get(),
        ];
    }
}

