<?php

namespace Tests\Feature;

use App\Filament\Resources\ContractResource;
use App\Filament\Resources\ContractResource\Pages\CreateContract;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\GuarantorResource;
use App\Filament\Resources\GuarantorResource\Pages\CreateGuarantor;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\PaymentResource\Pages\PaymentKanban;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\Product;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResourceInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_resources_accept_test_input_and_render(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $actor = User::factory()->create(['password_changed_at' => now()]);
        foreach (['customer', 'product', 'guarantor', 'contract', 'payment', 'user', 'role'] as $resource) {
            foreach (['view_any', 'view', 'create', 'update'] as $action) {
                $actor->givePermissionTo(Permission::findOrCreate("{$action}_{$resource}", 'web'));
            }
        }
        $this->actingAs($actor);

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'testing_operator', 'guard_name' => 'web'])
            ->call('create')->assertHasNoFormErrors();
        $role = Role::where('name', 'testing_operator')->firstOrFail();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Testing Operator', 'username' => 'testing',
                'email' => 'testing@example.test', 'password' => 'TestOnly!2026',
                'roles' => [$role->id], 'is_active' => true,
            ])
            ->call('create')->assertHasNoFormErrors();
        $user = User::where('username', 'testing')->firstOrFail();
        $this->assertTrue(Hash::check('TestOnly!2026', $user->password));
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue($user->hasRole('testing_operator'));

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['is_active' => false])->call('save')->assertHasNoFormErrors();
        $this->assertFalse($user->fresh()->is_active);
        $this->actingAs($actor);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'nik' => '9999000000000001', 'name' => 'Testing Customer',
                'phone_number' => '080000000001', 'address' => 'Alamat testing',
            ])
            ->call('create')->assertSuccessful()->assertHasNoFormErrors();
        $customer = Customer::where('name', 'Testing Customer')->firstOrFail();
        $this->assertSame('9999000000000001', $customer->nik);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'brand' => 'Testing Phone', 'cost_price' => 1000000,
                'selling_price' => 1200000, 'actual_cost_price' => 1000000,
                'default_tenor' => 3, 'installment_reference' => 400000,
            ])
            ->call('create')->assertHasNoFormErrors();
        $product = Product::where('brand', 'Testing Phone')->firstOrFail();

        Livewire::test(CreateGuarantor::class)
            ->fillForm([
                'customer_id' => $customer->id, 'nik' => '9999000000000002',
                'name' => 'Testing Guarantor', 'phone_number' => '080000000002',
                'relationship' => 'Kerabat', 'address' => 'Alamat penjamin testing',
            ])
            ->call('create')->assertHasNoFormErrors();
        $guarantor = Guarantor::where('nik', '9999000000000002')->firstOrFail();

        Livewire::test(CreateContract::class)
            ->fillForm([
                'contract_number' => 'TEST-RESOURCE-001', 'start_date' => today()->toDateString(),
                'customer_id' => $customer->id, 'product_id' => $product->id,
                'total_price' => 1200000, 'tenor' => 3, 'installment' => 400000,
                'actual_cost_price' => 1000000, 'status' => 'active',
            ])
            ->call('create')->assertHasNoFormErrors();
        $contract = Contract::where('customer_id', $customer->id)->where('product_id', $product->id)->firstOrFail();
        $this->assertCount(3, $contract->payments);

        foreach ([
            CustomerResource::class => $customer,
            ProductResource::class => $product,
            GuarantorResource::class => $guarantor,
            ContractResource::class => $contract,
            UserResource::class => $user,
            RoleResource::class => $role,
        ] as $resource => $record) {
            $this->get($resource::getUrl('index'))->assertOk();
            $this->get($resource::getUrl('edit', ['record' => $record]))->assertOk();
        }
        $this->get(PaymentResource::getUrl('index'))->assertOk();
        $this->get(PaymentResource::getUrl('kanban'))->assertOk();

        $firstPayment = $contract->payments->first();
        Livewire::test(ListPayments::class)
            ->callAction(TestAction::make('markAsPaid')->table($contract), [
                'payment_id' => $firstPayment->id,
                'paid_amount' => $firstPayment->amount,
                'paid_at' => now()->toDateTimeString(),
            ])
            ->assertHasNoFormErrors();
        $this->assertSame('paid', $firstPayment->fresh()->status);

        foreach ($contract->payments as $payment) {
            Livewire::test(PaymentKanban::class)
                ->call('markAsPaid', $payment->id)->assertHasNoErrors();
            $this->assertSame('paid', $payment->fresh()->status);
        }
        $this->assertSame('completed', $contract->fresh()->status);
    }
}
