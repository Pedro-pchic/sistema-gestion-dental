<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_matching_number_and_email_reveal_only_public_order_status(): void
    {
        $customer = Customer::factory()->create(['email' => 'cliente@example.test']);
        $order = Order::factory()->for($customer)->create([
            'status' => OrderStatus::Confirmed,
            'notes' => 'Dato interno que no debe mostrarse.',
            'total' => '999.00',
        ]);

        $this->post(route('seguimiento.show'), [
            'number' => $order->number,
            'email' => 'CLIENTE@example.test',
        ])
            ->assertOk()
            ->assertSeeText($order->number)
            ->assertSeeText('Confirmado')
            ->assertDontSeeText('Dato interno que no debe mostrarse.')
            ->assertDontSeeText('999.00');
    }

    public function test_order_cannot_be_viewed_with_another_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'propietario@example.test']);
        $order = Order::factory()->for($customer)->create();

        $this->from(route('seguimiento.create'))
            ->post(route('seguimiento.show'), [
                'number' => $order->number,
                'email' => 'otra-persona@example.test',
            ])
            ->assertRedirectToRoute('seguimiento.create')
            ->assertSessionHasErrors([
                'number' => 'No encontramos un pedido con esos datos.',
            ]);

        $this->get(route('seguimiento.create'))
            ->assertOk()
            ->assertDontSeeText($order->number);
    }
}
