<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with(['order.customer', 'recorder'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(15)->withQueryString();
        return view('admin.payments.index', compact('payments'));
    }

    public function store(Request $request, Order $order)
    {
        $data = $request->validate(['amount' => ['required','numeric','decimal:0,2','min:0.01','max:9999999999'], 'method' => ['required','in:cash,bank_transfer,qris'], 'reference' => ['nullable','string','max:255'], 'notes' => ['nullable','string','max:1000']]);
        DB::transaction(function () use ($order, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (!$locked->amount_finalized_at) throw ValidationException::withMessages(['amount' => 'Tagihan final harus tersedia sebelum pembayaran dicatat.']);
            $received = (int) round((float) $locked->payments()->where('status', 'verified')->sum('amount') * 100);
            $pending = (int) round((float) $locked->payments()->where('status', 'pending')->sum('amount') * 100);
            $amount = (int) round((float) $data['amount'] * 100);
            $total = (int) round((float) $locked->total_amount * 100);
            if ($amount + $received + $pending > $total) throw ValidationException::withMessages(['amount' => 'Jumlah melebihi sisa tagihan setelah pembayaran menunggu verifikasi.']);
            $locked->payments()->create(['recorded_by' => auth()->id(), 'amount' => $data['amount'], 'method' => $data['method'], 'status' => 'pending', 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null]);
        });
        return back()->with('success', 'Pembayaran dicatat dan menunggu verifikasi admin.');
    }

    public function verify(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($locked->order_id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah diproses.']);
            $received = (int) round((float) $order->payments()->where('status', 'verified')->sum('amount') * 100);
            $amount = (int) round((float) $locked->amount * 100);
            $total = (int) round((float) $order->total_amount * 100);
            if (!$order->amount_finalized_at || $received + $amount > $total) throw ValidationException::withMessages(['payment' => 'Pembayaran tidak dapat diverifikasi karena melebihi tagihan final.']);
            $locked->update(['status' => 'verified', 'paid_at' => now()]);
        });
        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }
}
