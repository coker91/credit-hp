<?php

namespace Tests\Feature;

use App\Filament\Auth\CustomLogin;
use App\Filament\Pages\MatrixCicilanPage;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentSettlement;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccessAndSettlementTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);

        return $user;
    }

    private function payment(int $tenor = 1): Payment
    {
        $customer = Customer::create([
            'nik' => '3201000000000001', 'name' => 'Test Customer',
            'phone_number' => '081234567890', 'address' => 'Test Address',
        ]);
        $product = Product::create([
            'brand' => 'Test Phone', 'cost_price' => 1000000, 'selling_price' => 1200000,
        ]);
        $contract = Contract::create([
            'contract_number' => 'ACCESS-TEST', 'customer_id' => $customer->id,
            'product_id' => $product->id, 'total_price' => 1200000,
            'tenor' => $tenor, 'installment' => 1200000 / $tenor,
            'start_date' => today(), 'status' => 'active',
        ]);

        return $contract->payments()->create([
            'installment_number' => 1, 'due_date' => today(),
            'amount' => 1200000 / $tenor, 'status' => 'unpaid',
        ]);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create(['is_active' => false]);

        Livewire::test(CustomLogin::class)
            ->fillForm(['login' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasErrors(['data.login']);

        $this->assertGuest();
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_active_user_can_log_in_with_username(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create([
            'username' => 'testing', 'password_changed_at' => now(),
        ]);

        Livewire::test(CustomLogin::class)
            ->fillForm(['login' => 'testing', 'password' => 'password'])
            ->call('authenticate')->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_disabling_a_logged_in_user_blocks_panel_access(): void
    {
        $user = $this->userWithPermissions([]);
        $user->update(['is_active' => false]);

        $this->get('/admin')->assertForbidden();
    }

    public function test_matrix_requires_page_permission(): void
    {
        $this->userWithPermissions([]);

        $this->get(MatrixCicilanPage::getUrl())->assertForbidden();
    }

    public function test_read_only_matrix_user_cannot_settle_payment(): void
    {
        $this->userWithPermissions(['page_MatrixCicilanPage']);
        $payment = $this->payment();

        Livewire::test(MatrixCicilanPage::class)
            ->call('markAsPaid', $payment->id)
            ->assertForbidden();

        $this->assertSame('unpaid', $payment->fresh()->status);
    }

    public function test_read_only_matrix_user_cannot_send_whatsapp(): void
    {
        Http::fake();
        $this->userWithPermissions(['page_MatrixCicilanPage']);
        $payment = $this->payment();

        Livewire::test(MatrixCicilanPage::class)
            ->call('sendWaNotice', $payment->id)
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertNull($payment->fresh()->wa_reminder_sent_at);
    }

    public function test_matrix_settlement_completes_contract_and_is_idempotent(): void
    {
        $this->userWithPermissions(['page_MatrixCicilanPage', 'update_payment']);
        $payment = $this->payment();

        Livewire::test(MatrixCicilanPage::class)
            ->call('markAsPaid', $payment->id)
            ->assertHasNoErrors();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('completed', $payment->contract->fresh()->status);
        $paidAt = $payment->fresh()->paid_at;
        $this->assertFalse(app(PaymentSettlement::class)->settle($payment->id));
        $this->assertTrue($paidAt->equalTo($payment->fresh()->paid_at));
    }

    public function test_missing_schedule_does_not_complete_contract(): void
    {
        $this->userWithPermissions(['update_payment']);
        $payment = $this->payment(2);

        $this->assertTrue(app(PaymentSettlement::class)->settle($payment->id));
        $this->assertSame('active', $payment->contract->fresh()->status);
    }

    public function test_service_requires_payment_update_permission(): void
    {
        $this->userWithPermissions([]);
        $payment = $this->payment();

        try {
            app(PaymentSettlement::class)->settle($payment->id);
            $this->fail('Settlement should be denied.');
        } catch (AuthorizationException) {
            $this->assertSame('unpaid', $payment->fresh()->status);
        }
    }
}
