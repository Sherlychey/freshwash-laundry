<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin-modules@test.local', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);
    }

    private function fixtureOrder(): Order
    {
        $customer = Customer::create(['name' => 'Dewi', 'phone' => '081234567890']);
        $service = Service::create(['name' => 'Cuci kiloan', 'unit' => 'kg', 'price' => '8000.00', 'duration_days' => 3, 'is_active' => true]);
        return Order::create(['order_code' => 'FW-TEST-001', 'tracking_token' => str_repeat('a', 48), 'customer_id' => $customer->id, 'service_id' => $service->id, 'source' => 'online', 'weight_kg' => '2.00', 'unit_price' => '7000.00', 'subtotal' => '14000.00', 'total_amount' => '14000.00', 'amount_finalized_at' => now(), 'order_status' => 'washing', 'delivery_method' => 'pickup_delivery']);
    }

    public function test_admin_can_manage_customers_and_services(): void
    {
        $this->actingAs($this->admin())->get(route('admin.customers.index'))->assertOk()->assertSee('Pelanggan');
        $this->post(route('admin.customers.store'), ['name' => 'Ayu', 'phone' => '081111111111'])->assertRedirect(route('admin.customers.index'));
        $this->assertDatabaseHas('customers', ['name' => 'Ayu']);

        $this->get(route('admin.services.create'))->assertOk()->assertSee('Tambah layanan');
        $this->post(route('admin.services.store'), ['name' => 'Cuci satuan', 'unit' => 'item', 'price' => '12000', 'duration_days' => 2, 'is_active' => 1])->assertRedirect(route('admin.services.index'));
        $service = Service::where('name', 'Cuci satuan')->firstOrFail();
        $this->put(route('admin.services.update', $service), ['name' => 'Cuci satuan', 'unit' => 'item', 'price' => '15000', 'duration_days' => 2, 'is_active' => 1])->assertRedirect(route('admin.services.index'));
        $this->assertSame('15000.00', $service->fresh()->price);
    }

    public function test_service_price_edit_does_not_change_order_price_snapshot_and_payment_requires_verification(): void
    {
        $order = $this->fixtureOrder();
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.services.update', $order->service), ['name' => 'Cuci kiloan', 'unit' => 'kg', 'price' => '10000', 'duration_days' => 3, 'is_active' => 1])->assertRedirect();
        $this->assertSame('7000.00', $order->fresh()->unit_price);

        $this->post(route('admin.payments.store', $order), ['amount' => '5000', 'method' => 'bank_transfer', 'reference' => 'TRX-1'])->assertRedirect();
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => '5000.00', 'status' => 'pending']);
        $payment = Payment::firstOrFail();
        $this->patch(route('admin.payments.verify', $payment))->assertRedirect();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'verified']);
        $this->post(route('admin.payments.store', $order), ['amount' => '9500', 'method' => 'cash'])->assertSessionHasErrors('amount');
    }

    public function test_pickup_schedule_and_report_pages_are_available(): void
    {
        $order = $this->fixtureOrder();
        $this->actingAs($this->admin())->get(route('admin.pickups.index'))->assertOk()->assertSee($order->order_code);
        $this->patch(route('admin.pickups.update', $order), ['delivery_address' => 'Jl. Mawar 1', 'pickup_schedule' => now()->addDay()->format('Y-m-d H:i:s'), 'delivery_schedule' => now()->addDays(3)->format('Y-m-d H:i:s')])->assertRedirect();
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Jumlah pesanan');
        $this->get(route('admin.reports.index', ['from' => now()->subDay()->toDateString(), 'to' => now()->addDay()->toDateString()]))->assertOk()->assertSee('Jumlah pesanan');
        $this->get(route('admin.orders.receipt', $order))->assertOk()->assertSee($order->order_code)->assertSee('FreshWash');
    }
}
