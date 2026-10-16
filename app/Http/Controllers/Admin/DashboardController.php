<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $revenue = (float) Payment::where('status', 'verified')->sum('amount');
        $billed = (float) Order::whereNotNull('amount_finalized_at')->sum('total_amount');
        $statistics = [
            'total' => Order::count(),
            'active' => Order::whereNotIn('order_status', ['completed', 'cancelled'])->count(),
            'completed' => Order::where('order_status', 'completed')->count(),
            'revenue' => $revenue,
            'outstanding' => max(0, $billed - $revenue),
        ];
        $recentOrders = Order::with(['customer', 'service'])->latest()->take(6)->get();
        return view('admin.dashboard', compact('statistics', 'recentOrders'));
    }
}
