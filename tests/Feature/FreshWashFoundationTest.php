<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreshWashFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('Pakaian bersih,')->assertSee('FreshWash')
            ->assertSee('Cara kerja')->assertSee('Lacak pesanan')->assertSee('css/readability.css');
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_login_form_and_authenticated_guest_redirect_use_admin_routes(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('action="'.route('login.store').'"', false);
        $admin = User::create([
            'name' => 'Admin Uji', 'email' => 'redirect@test.local',
            'password' => 'safe-test-password', 'role' => 'admin', 'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('login'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_all_admin_sections_are_protected_and_load_for_an_authenticated_admin(): void
    {
        $protectedUrls = [
            route('admin.dashboard'), route('admin.customers.index'), route('admin.services.index'),
            route('admin.orders.index'), route('admin.payments.index'), route('admin.pickups.index'), route('admin.reports.index'),
        ];
        foreach ($protectedUrls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $customer = Customer::create(['name' => 'Pelanggan Uji', 'phone' => '081234567890']);
        $service = Service::create(['name' => 'Cuci Uji', 'unit' => 'kg', 'price' => '7000.00', 'duration_days' => 3, 'is_active' => true]);
        $order = Order::create([
            'order_code' => 'FW-ROUTE-TEST', 'tracking_token' => str_repeat('b', 48),
            'customer_id' => $customer->id, 'service_id' => $service->id, 'source' => 'online',
            'unit_price' => '7000.00', 'subtotal' => '0.00', 'total_amount' => '0.00',
            'delivery_method' => 'pickup_delivery', 'order_status' => 'pending_confirmation',
        ]);
        $admin = User::create([
            'name' => 'Admin Uji', 'email' => 'sections@test.local',
            'password' => 'safe-test-password', 'role' => 'admin', 'is_active' => true,
        ]);

        $this->actingAs($admin);
        foreach ($protectedUrls as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee($order->order_code);
    }

    public function test_active_admin_can_sign_in_and_open_dashboard(): void
    {
        User::create([
            'name' => 'Admin Uji', 'email' => 'admin@test.local',
            'password' => 'safe-test-password', 'role' => 'admin', 'is_active' => true,
        ]);

        $this->post(route('login.store'), ['email' => 'admin@test.local', 'password' => 'safe-test-password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Pesanan terbaru')->assertSee('Data langsung')->assertSee('css/readability.css');
    }

    public function test_inactive_admin_cannot_sign_in(): void
    {
        User::create([
            'name' => 'Nonaktif', 'email' => 'inactive@test.local',
            'password' => 'safe-test-password', 'role' => 'admin', 'is_active' => false,
        ]);

        $this->from(route('login'))->post(route('login.store'), ['email' => 'inactive@test.local', 'password' => 'safe-test-password'])
            ->assertRedirect(route('login'))->assertSessionHasErrors('email');
    }
}
