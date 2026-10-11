<?php

namespace Tests\Feature;

use App\Enums\CustomerInquiryStatus;
use App\Models\CustomerInquiry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CustomerInquiryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_contact_payload_creates_pending_inquiry(): void
    {
        $response = $this->post(route('contacto.store'), [
            'name' => 'Ana López',
            'email' => 'ana@example.test',
            'subject' => 'Productos',
            'message' => 'Quisiera confirmar la disponibilidad de una talla.',
            'status' => CustomerInquiryStatus::Closed->value,
        ]);

        $response
            ->assertRedirectToRoute('contacto.create')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('customer_inquiries', [
            'name' => 'Ana López',
            'email' => 'ana@example.test',
            'subject' => 'Productos',
            'status' => CustomerInquiryStatus::Pending->value,
        ]);
    }

    public function test_invalid_contact_payload_is_rejected(): void
    {
        $this->from(route('contacto.create'))
            ->post(route('contacto.store'), [
                'name' => '',
                'email' => 'correo-invalido',
                'subject' => 'Descuento inventado',
                'message' => 'Breve',
            ])
            ->assertRedirectToRoute('contacto.create')
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertDatabaseCount('customer_inquiries', 0);
    }

    public function test_user_without_commercial_permission_cannot_administer_inquiries(): void
    {
        $inquiry = CustomerInquiry::factory()->create();
        $user = $this->userWithRole(Role::WAREHOUSE);

        $this->actingAs($user)
            ->get(route('consultas.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('consultas.update', $inquiry), ['status' => CustomerInquiryStatus::Closed->value])
            ->assertForbidden();
    }

    public function test_sales_user_can_view_and_update_an_inquiry(): void
    {
        $inquiry = CustomerInquiry::factory()->create([
            'name' => 'Cliente portal',
            'message' => 'Necesito información del pedido.',
        ]);
        $user = $this->userWithRole(Role::SALES);

        $this->actingAs($user)
            ->get(route('consultas.show', $inquiry))
            ->assertOk()
            ->assertSeeText('Cliente portal')
            ->assertSeeText('Necesito información del pedido.');

        $this->actingAs($user)
            ->patch(route('consultas.update', $inquiry), [
                'status' => CustomerInquiryStatus::InProgress->value,
            ])
            ->assertRedirectToRoute('consultas.show', $inquiry)
            ->assertSessionHas('status');

        $this->assertDatabaseHas('customer_inquiries', [
            'id' => $inquiry->id,
            'status' => CustomerInquiryStatus::InProgress->value,
        ]);
    }

    public function test_admin_detail_escapes_customer_message(): void
    {
        $inquiry = CustomerInquiry::factory()->create([
            'message' => '<script>alert("xss")</script>',
        ]);
        $user = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($user)
            ->get(route('consultas.show', $inquiry))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
