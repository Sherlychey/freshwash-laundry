<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_order_without_known_weight(): void
    {
        $service = $this->service();

        $response = $this->post(route('orders.store'), $this->payload($service));
        $order = Order::firstOrFail();

        $response->assertRedirect(route('orders.confirmation', $order->order_code));
        $this->assertDatabaseHas('customers', ['phone' => '081234567890', 'name' => 'Rina Pelanggan']);
        $this->assertDatabaseHas('orders', [
            'order_code' => $order->order_code,
            'customer_id' => Customer::first()->id,
            'service_id' => $service->id,
            'source' => 'online',
            'weight_kg' => null,
            'unit_price' => '7000.00',
            'subtotal' => '0.00',
            'total_amount' => '0.00',
            'order_status' => 'pending_confirmation',
        ]);
        $this->assertSame(48, strlen($order->tracking_token));
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => 'pending_confirmation',
        ]);
        $this->assertDatabaseCount('payments', 0);
        $response->assertSessionHas('tracking_token', $order->tracking_token);
        $this->get(route('orders.confirmation', $order->order_code))
            ->assertOk()->assertSee($order->order_code)->assertSee('Lacak pesanan')->assertDontSee('@if');
    }

    public function test_weighted_order_uses_server_price_for_estimate(): void
    {
        $service = $this->service();
        $payload = $this->payload($service) + ['weight_kg' => '2.50'];

        $this->post(route('orders.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'service_id' => $service->id,
            'unit_price' => '7000.00',
            'weight_kg' => '2.50',
            'subtotal' => '17500.00',
            'total_amount' => '17500.00',
        ]);
    }

    public function test_item_service_requires_quantity_and_calculates_using_database_price(): void
    {
        $service = $this->service(['unit' => 'item', 'price' => '12500.00']);
        $payload = $this->payload($service) + ['quantity_items' => 3];

        $this->post(route('orders.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'service_id' => $service->id,
            'quantity_items' => 3,
            'weight_kg' => null,
            'subtotal' => '37500.00',
            'amount_finalized_at' => null,
        ]);
        $order = Order::firstOrFail();
        $this->get(route('orders.track', ['code' => $order->order_code, 'token' => $order->tracking_token]))
            ->assertOk()->assertSee('Menunggu konfirmasi tagihan')->assertSee('Perjalanan pesanan')->assertDontSee('Rp37.500');
    }

    public function test_invalid_submission_and_inactive_service_do_not_create_customer_or_order(): void
    {
        $inactive = $this->service(['is_active' => false]);

        $this->from(route('orders.create'))->post(route('orders.store'), [
            'name' => 'Test', 'phone' => '081234567890', 'address' => 'Alamat pelanggan yang cukup panjang',
            'service_id' => $inactive->id, 'delivery_method' => 'dropoff',
        ])->assertRedirect(route('orders.create'))->assertSessionHasErrors(['service_id']);

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_tracking_requires_both_credentials_and_does_not_expose_customer_data(): void
    {
        $service = $this->service();
        $this->post(route('orders.store'), $this->payload($service));
        $order = Order::firstOrFail();

        $this->get(route('orders.track', ['code' => $order->order_code, 'token' => $order->tracking_token]))
            ->assertOk()->assertSee($order->order_code)->assertSee($service->name)
            ->assertSee('Menunggu penimbangan')
            ->assertDontSee('081234567890')->assertDontSee('Alamat rahasia pelanggan')->assertDontSee('@if');

        $this->get(route('orders.track', ['code' => $order->order_code, 'token' => str_repeat('0', 48)]))
            ->assertOk()->assertSee('Kode pesanan atau token pelacakan tidak sesuai.')
            ->assertDontSee('081234567890');
    }

    public function test_admin_sees_online_order_and_can_accept_it_with_history(): void
    {
        $service = $this->service();
        $this->post(route('orders.store'), $this->payload($service));
        $order = Order::firstOrFail();
        $admin = User::create([
            'name' => 'Admin Test', 'email' => 'admin-order@test.local',
            'password' => 'secure-test-password', 'role' => 'admin', 'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()->assertSee($order->order_code)->assertSee('Rina Pelanggan')
            ->assertSee('081234567890')->assertSee('Online')->assertDontSee('@if')->assertDontSee('@foreach');
        $this->get(route('admin.orders.show', $order))
            ->assertOk()->assertSee('Informasi pelanggan')->assertSee('Riwayat status')->assertDontSee('@if');

        $this->actingAs($admin)->post(route('admin.orders.accept', $order))->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_status' => 'received']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id, 'user_id' => $admin->id,
            'from_status' => 'pending_confirmation', 'to_status' => 'received',
        ]);
    }

    public function test_admin_rejection_requires_a_reason_and_records_it_with_status_history(): void
    {
        $service = $this->service();
        $this->post(route('orders.store'), $this->payload($service));
        $order = Order::firstOrFail();
        $admin = User::create([
            'name' => 'Admin Test', 'email' => 'admin-reject@test.local',
            'password' => 'secure-test-password', 'role' => 'admin', 'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.orders.reject', $order), ['reason' => 'Alamat di luar area layanan'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id, 'order_status' => 'cancelled',
            'cancellation_reason' => 'Alamat di luar area layanan',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id, 'from_status' => 'pending_confirmation',
            'to_status' => 'cancelled', 'note' => 'Alamat di luar area layanan',
        ]);
    }

    public function test_admin_weighs_accepted_order_and_calculates_final_total_from_price_snapshot(): void
    {
        $service = $this->service();
        $this->post(route('orders.store'), $this->payload($service));
        $order = Order::firstOrFail();
        $admin = User::create([
            'name' => 'Admin Test', 'email' => 'admin-weigh@test.local',
            'password' => 'secure-test-password', 'role' => 'admin', 'is_active' => true,
        ]);
        $this->actingAs($admin);

        $this->post(route('admin.orders.accept', $order));
        $this->patch(route('admin.orders.status', $order), ['status' => 'awaiting_weighing']);
        $this->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'washing'])
            ->assertRedirect(route('admin.orders.show', $order))->assertSessionHasErrors('status');

        $this->patch(route('admin.orders.weigh', $order), ['weight_kg' => '2.25'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id, 'weight_kg' => '2.25', 'unit_price' => '7000.00',
            'subtotal' => '15750.00', 'total_amount' => '15750.00', 'order_status' => 'washing',
        ]);
        $this->assertNotNull($order->fresh()->amount_finalized_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id, 'from_status' => 'awaiting_weighing',
            'to_status' => 'washing', 'note' => 'Berat aktual 2.25 kg. Tagihan dihitung dari snapshot tarif.',
        ]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_confirms_actual_item_count_before_washing(): void
    {
        $service = $this->service(['unit' => 'item', 'price' => '12500.00']);
        $this->post(route('orders.store'), $this->payload($service) + ['quantity_items' => 3]);
        $order = Order::firstOrFail();
        $admin = User::create([
            'name' => 'Admin Test', 'email' => 'admin-item@test.local',
            'password' => 'secure-test-password', 'role' => 'admin', 'is_active' => true,
        ]);
        $this->actingAs($admin);
        $this->post(route('admin.orders.accept', $order));
        $this->patch(route('admin.orders.status', $order), ['status' => 'awaiting_weighing']);
        $this->patch(route('admin.orders.confirm-quantity', $order), ['quantity_items' => 4])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id, 'quantity_items' => 4, 'total_amount' => '50000.00',
            'order_status' => 'washing',
        ]);
        $this->assertNotNull($order->fresh()->amount_finalized_at);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id, 'from_status' => 'awaiting_weighing',
            'to_status' => 'washing', 'note' => 'Jumlah aktual 4 item. Tagihan dihitung dari snapshot tarif.',
        ]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_customer_with_existing_phone_is_reused_for_a_new_order(): void
    {
        $service = $this->service();
        $this->post(route('orders.store'), $this->payload($service));
        $this->post(route('orders.store'), $this->payload($service) + ['notes' => 'Pesanan kedua']);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseHas('customers', ['phone' => '081234567890', 'name' => 'Rina Pelanggan']);
    }

    public function test_tracking_form_and_admin_order_pages_are_routed(): void
    {
        $this->get(route('orders.create'))->assertOk()->assertSee('Pesan sekarang')->assertDontSee('@error')->assertDontSee('@if');
        $this->get(route('orders.track'))->assertOk()->assertSee('Token pelacakan');
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    }

    private function service(array $attributes = []): Service
    {
        return Service::create(array_merge([
            'name' => 'Cuci & Lipat', 'description' => 'Layanan cuci standar', 'unit' => 'kg',
            'price' => '7000.00', 'duration_days' => 3, 'is_active' => true,
        ], $attributes));
    }

    private function payload(Service $service): array
    {
        return [
            'name' => 'Rina Pelanggan', 'gender' => 'female', 'phone' => '081234567890',
            'email' => 'rina@example.test', 'address' => 'Alamat rahasia pelanggan, Jalan Melati 10',
            'service_id' => $service->id, 'delivery_method' => 'dropoff',
        ];
    }
}
