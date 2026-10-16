<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function create()
    {
        return view('orders.create', [
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'weight_kg' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.1', 'max:500'],
            'quantity_items' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'delivery_method' => ['required', Rule::in(['dropoff', 'pickup_delivery'])],
            'delivery_address' => ['nullable', 'required_if:delivery_method,pickup_delivery', 'string', 'max:2000'],
            'pickup_schedule' => ['nullable', 'required_if:delivery_method,pickup_delivery', 'date', 'after_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'service_id.exists' => 'Layanan tidak tersedia atau sudah dinonaktifkan. Silakan pilih layanan lain.',
            'delivery_address.required_if' => 'Alamat penjemputan wajib diisi untuk layanan antar-jemput.',
            'pickup_schedule.required_if' => 'Jadwal penjemputan wajib dipilih untuk layanan antar-jemput.',
            'pickup_schedule.after_or_equal' => 'Jadwal penjemputan harus sekarang atau setelahnya.',
        ]);

        $service = Service::whereKey($data['service_id'])->where('is_active', true)->first();
        if (! $service) {
            throw ValidationException::withMessages(['service_id' => 'Layanan tidak tersedia. Silakan muat ulang halaman dan pilih layanan aktif.']);
        }

        if (! in_array($service->unit, ['kg', 'item'], true)) {
            throw ValidationException::withMessages(['service_id' => 'Satuan layanan ini belum dapat dipesan secara online.']);
        }
        if ($service->unit === 'item' && empty($data['quantity_items'])) {
            throw ValidationException::withMessages(['quantity_items' => 'Jumlah item wajib diisi untuk layanan satuan item.']);
        }
        if ($service->unit === 'kg' && ! empty($data['quantity_items'])) {
            throw ValidationException::withMessages(['quantity_items' => 'Layanan ini menggunakan satuan kilogram.']);
        }

        $order = DB::transaction(function () use ($data, $service) {
            $customer = Customer::where('phone', $data['phone'])->lockForUpdate()->first();
            $customer ??= new Customer();
            $customer->fill([
                'name' => $data['name'], 'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'], 'email' => $data['email'] ?? null,
                'address' => $data['address'],
            ]);
            $customer->save();

            $hasAmount = $service->unit === 'item' || isset($data['weight_kg']);
            $unitPriceCents = $this->toMinorUnits((string) $service->price);
            $quantityHundredths = $service->unit === 'item'
                ? ((int) $data['quantity_items'] * 100)
                : $this->toMinorUnits((string) ($data['weight_kg'] ?? '0'));
            $subtotalCents = $hasAmount
                ? intdiv(($unitPriceCents * $quantityHundredths) + 50, 100)
                : 0;
            $subtotal = $this->fromMinorUnits($subtotalCents);

            do {
                $orderCode = 'FW-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
            } while (Order::where('order_code', $orderCode)->exists());

            $order = Order::create([
                'order_code' => $orderCode,
                'tracking_token' => bin2hex(random_bytes(24)),
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'created_by' => null,
                'source' => 'online',
                'weight_kg' => $service->unit === 'kg' ? ($data['weight_kg'] ?? null) : null,
                'quantity_items' => $service->unit === 'item' ? $data['quantity_items'] : null,
                'unit_price' => $service->price,
                'subtotal' => $subtotal,
                'delivery_method' => $data['delivery_method'],
                'delivery_address' => $data['delivery_method'] === 'pickup_delivery' ? $data['delivery_address'] : null,
                'pickup_schedule' => $data['pickup_schedule'] ?? null,
                'delivery_fee' => 0,
                'total_amount' => $subtotal,
                'order_status' => 'pending_confirmation',
                'estimated_completion_at' => now()->addDays($service->duration_days),
                'notes' => $data['notes'] ?? null,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending_confirmation',
                'note' => 'Pesanan diterima melalui website.',
            ]);

            return $order;
        });

        return redirect()->route('orders.confirmation', $order->order_code)
            ->with('tracking_token', $order->tracking_token);
    }

    public function confirmation(string $orderCode)
    {
        $order = Order::with('service')->where('order_code', $orderCode)->firstOrFail();
        return view('orders.confirmation', compact('order'));
    }

    public function track(Request $request)
    {
        $code = $request->query('code');
        $token = $request->query('token');
        $order = null;
        $error = null;

        if ($code !== null || $token !== null) {
            $request->validate(['code' => ['required', 'string', 'max:30'], 'token' => ['required', 'string', 'size:48']]);
            $candidate = Order::with([
                'service',
                'payments',
                'statusHistories' => fn ($query) => $query->select(['id', 'order_id', 'to_status', 'created_at'])->orderBy('created_at'),
            ])->where('order_code', $code)->first();
            if (! $candidate || ! hash_equals($candidate->tracking_token, $token)) {
                $error = 'Kode pesanan atau token pelacakan tidak sesuai.';
            } else {
                $order = $candidate;
            }
        }

        return view('orders.track', compact('order', 'error', 'code', 'token'));
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
