<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->merge([
            'from' => $request->query('from', now()->startOfMonth()->toDateString()),
            'to' => $request->query('to', now()->toDateString()),
        ]);
        $data = $request->validate(['from' => ['required','date'], 'to' => ['required','date','after_or_equal:from']]);
        $from = $data['from']; $to = $data['to'];
        $orders = Order::with(['customer','service','payments'])->whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])->latest()->get();
        $ids = $orders->pluck('id');
        $payments = $ids->isEmpty() ? 0 : (float) \App\Models\Payment::whereIn('order_id', $ids)->where('status','verified')->sum('amount');
        $billed = (float) $orders->whereNotNull('amount_finalized_at')->sum('total_amount');
        $outstanding = max(0, $billed - $payments);
        $popularServices = $orders->groupBy('service_id')->map(fn ($items) => ['name' => optional($items->first()->service)->name ?? 'Layanan dihapus', 'count' => $items->count()])->sortByDesc('count')->take(8);
        return view('admin.reports.index', compact('orders','from','to','payments','billed','outstanding','popularServices'));
    }
    public function receipt(Order $order)
    {
        $order->load(['customer','service','payments' => fn ($q) => $q->orderBy('created_at')]);
        return view('admin.orders.receipt', compact('order'));
    }
}
