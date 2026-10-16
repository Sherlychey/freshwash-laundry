<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementController extends Controller
{
    private const TRANSITIONS = [
        'pending_confirmation' => ['received', 'cancelled'],
        'received' => ['awaiting_weighing', 'washing', 'cancelled'],
        'awaiting_weighing' => ['washing', 'cancelled'],
        'washing' => ['drying'],
        'drying' => ['ironing'],
        'ironing' => ['ready'],
        'ready' => ['delivering', 'completed'],
        'delivering' => ['completed'],
    ];

    public function index(Request $request)
    {
        $query = Order::with(['customer', 'service']);
        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('order_code', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%'));
            });
        }
        if ($status = $request->query('status')) {
            $query->where('order_status', $status);
        }

        $orders = $query->latest()->paginate(12)->withQueryString();
        $statuses = Order::query()->select('order_status')->distinct()->orderBy('order_status')->pluck('order_status');
        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'service', 'payments', 'statusHistories.user']);
        $transitions = self::TRANSITIONS[$order->order_status] ?? [];
        if ($order->amount_finalized_at === null) {
            $transitions = array_values(array_filter($transitions, fn ($status) => $status !== 'washing'));
        }
        return view('admin.orders.show', compact('order', 'transitions'));
    }

    public function weigh(Request $request, Order $order)
    {
        $data = $request->validate(['weight_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.1', 'max:500']]);

        DB::transaction(function () use ($order, $data) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedOrder->load('service');
            if ($lockedOrder->service->unit !== 'kg' || $lockedOrder->order_status !== 'awaiting_weighing' || $lockedOrder->amount_finalized_at !== null) {
                throw ValidationException::withMessages(['weight_kg' => 'Pesanan ini belum berada pada tahap yang dapat ditimbang.']);
            }

            $weightHundredths = $this->toMinorUnits((string) $data['weight_kg']);
            $unitPriceCents = $this->toMinorUnits((string) $lockedOrder->unit_price);
            $totalCents = intdiv(($weightHundredths * $unitPriceCents) + 50, 100);
            $amount = $this->fromMinorUnits($totalCents);
            $previousStatus = $lockedOrder->order_status;

            $lockedOrder->update([
                'weight_kg' => $data['weight_kg'],
                'subtotal' => $amount,
                'total_amount' => $amount,
                'amount_finalized_at' => now(),
                'order_status' => 'washing',
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'user_id' => auth()->id(),
                'from_status' => $previousStatus,
                'to_status' => 'washing',
                'note' => 'Berat aktual '.$data['weight_kg'].' kg. Tagihan dihitung dari snapshot tarif.',
            ]);
        });

        return back()->with('success', 'Berat tersimpan dan tagihan final telah dihitung.');
    }

    public function confirmQuantity(Request $request, Order $order)
    {
        $data = $request->validate(['quantity_items' => ['required', 'integer', 'min:1', 'max:1000']]);

        DB::transaction(function () use ($order, $data) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedOrder->load('service');
            if ($lockedOrder->service->unit !== 'item' || $lockedOrder->order_status !== 'awaiting_weighing' || $lockedOrder->amount_finalized_at !== null) {
                throw ValidationException::withMessages(['quantity_items' => 'Pesanan ini belum berada pada tahap pemeriksaan jumlah item.']);
            }

            $quantityHundredths = (int) $data['quantity_items'] * 100;
            $unitPriceCents = $this->toMinorUnits((string) $lockedOrder->unit_price);
            $totalCents = intdiv(($quantityHundredths * $unitPriceCents) + 50, 100);
            $amount = $this->fromMinorUnits($totalCents);
            $previousStatus = $lockedOrder->order_status;

            $lockedOrder->update([
                'quantity_items' => $data['quantity_items'],
                'subtotal' => $amount,
                'total_amount' => $amount,
                'amount_finalized_at' => now(),
                'order_status' => 'washing',
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'user_id' => auth()->id(),
                'from_status' => $previousStatus,
                'to_status' => 'washing',
                'note' => 'Jumlah aktual '.$data['quantity_items'].' item. Tagihan dihitung dari snapshot tarif.',
            ]);
        });

        return back()->with('success', 'Jumlah item tersimpan dan tagihan final telah dihitung.');
    }

    public function accept(Order $order)
    {
        $this->changeStatus($order, 'received', 'Pesanan dikonfirmasi oleh admin.');
        return back()->with('success', 'Pesanan diterima.');
    }

    public function reject(Request $request, Order $order)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->changeStatus($order, 'cancelled', $data['reason'], $data['reason']);
        return back()->with('success', 'Pesanan ditolak dan alasan pembatalan tersimpan.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', 'string', 'max:40'], 'note' => ['nullable', 'string', 'max:1000']]);
        $this->changeStatus($order, $data['status'], $data['note'] ?? null);
        return back()->with('success', 'Status pesanan diperbarui.');
    }

    private function changeStatus(Order $order, string $nextStatus, ?string $note, ?string $cancellationReason = null): void
    {
        DB::transaction(function () use ($order, $nextStatus, $note, $cancellationReason) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedOrder->load('service');
            $allowed = self::TRANSITIONS[$lockedOrder->order_status] ?? [];
            if (! in_array($nextStatus, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status ini tidak diizinkan. Muat ulang halaman dan periksa status terkini.']);
            }
            if ($nextStatus === 'washing' && $lockedOrder->amount_finalized_at === null) {
                throw ValidationException::withMessages(['status' => 'Konfirmasi berat atau jumlah aktual sebelum pesanan mulai dicuci.']);
            }

            $previous = $lockedOrder->order_status;
            $attributes = ['order_status' => $nextStatus];
            if ($cancellationReason !== null) {
                $attributes['cancellation_reason'] = $cancellationReason;
            }
            $lockedOrder->update($attributes);
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'user_id' => auth()->id(),
                'from_status' => $previous,
                'to_status' => $nextStatus,
                'note' => $note,
            ]);
        });
    }

    private function toMinorUnits(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromMinorUnits(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
