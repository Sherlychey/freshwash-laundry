<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['customer','service'])->where('delivery_method', 'pickup_delivery')->when($request->filled('status'), fn ($q) => $q->where('order_status', $request->query('status')))->latest()->paginate(15)->withQueryString();
        return view('admin.pickups.index', compact('orders'));
    }
    public function update(Request $request, Order $order)
    {
        abort_unless($order->delivery_method === 'pickup_delivery', 404);
        $data = $request->validate(['delivery_address' => ['required','string','max:2000'], 'pickup_schedule' => ['required','date'], 'delivery_schedule' => ['nullable','date','after_or_equal:pickup_schedule']]);
        $order->update($data);
        return back()->with('success', 'Alamat dan jadwal antar-jemput diperbarui.');
    }
}
