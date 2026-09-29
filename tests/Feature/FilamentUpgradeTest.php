<?php

namespace Tests\Feature;

use App\Filament\Pages\MatrixCicilanPage;
use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\PaymentResource\Pages\PaymentKanban;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FilamentUpgradeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_login_page_renders_on_filament_five(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Username / Email');
    }

    public function test_matrix_page_handles_a_missing_payment_schedule(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $unique = substr(hash('sha256', uniqid('', true)), 0, 16);

        $customer = Customer::create([
            'nik' => $unique,
            'name' => 'Nasabah Tanpa Jadwal',
            'phone_number' => '080000000000',
            'address' => 'Alamat pengujian',
        ]);

        $product = Product::create([
            'brand' => 'Produk Pengujian',
            'cost_price' => 1_000_000,
            'selling_price' => 1_200_000,
        ]);

        Contract::create([
            'contract_number' => "TEST-{$unique}",
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'total_price' => 1_200_000,
            'tenor' => 2,
            'installment' => 600_000,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        Livewire::test(MatrixCicilanPage::class)
            ->assertOk()
            ->assertSee('Belum dibuat');
    }

    public function test_payment_status_cards_use_clear_indonesian_labels(): void
    {
        $contract = new Contract(['tenor' => 5]);
        $contract->setRelation('payments', collect([
            new Payment([
                'installment_number' => 1,
                'due_date' => today()->subMonth(),
                'amount' => 600_000,
                'paid_amount' => 600_000,
                'paid_at' => now()->subMonth(),
                'status' => 'paid',
            ]),
            new Payment([
                'installment_number' => 2,
                'due_date' => today()->subDay(),
                'amount' => 600_000,
                'status' => 'unpaid',
            ]),
            new Payment([
                'installment_number' => 3,
                'due_date' => today(),
                'amount' => 600_000,
                'status' => 'unpaid',
            ]),
            new Payment([
                'installment_number' => 4,
                'due_date' => today()->addMonth(),
                'amount' => 600_000,
                'status' => 'unpaid',
            ]),
        ]));

        $html = Blade::render(
            "@include('filament.tables.columns.installment-matrix')",
            ['getRecord' => static fn (): Contract => $contract],
        );

        $this->assertStringContainsString('Lunas', $html);
        $this->assertStringContainsString('Terlambat', $html);
        $this->assertStringContainsString('Jatuh tempo', $html);
        $this->assertStringContainsString('Menunggu', $html);
        $this->assertStringContainsString('Belum dibuat', $html);
        $this->assertStringContainsString('installment-status-scroll', $html);
    }

    public function test_payments_page_has_quick_status_tabs(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo(Permission::findOrCreate('view_any_contract', 'web'));

        $this->actingAs($user);

        Livewire::test(ListPayments::class)
            ->assertOk()
            ->assertSee('Semua')
            ->assertSee('Perlu Ditagih')
            ->assertSee('Berjalan')
            ->assertSee('Sudah Lunas')
            ->assertSeeHtml('payment-sticky-unit');
    }

    public function test_payment_can_be_marked_as_paid_from_the_kanban(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo([
            Permission::findOrCreate('view_any_contract', 'web'),
            Permission::findOrCreate('update_payment', 'web'),
        ]);

        $this->actingAs($user);

        $unique = substr(hash('sha256', uniqid('kanban', true)), 0, 16);
        $customer = Customer::create([
            'nik' => $unique,
            'name' => 'Customer Kanban',
            'phone_number' => '081234567890',
            'address' => 'Alamat pengujian Kanban',
        ]);
        $product = Product::create([
            'brand' => 'HP Kanban',
            'cost_price' => 1_000_000,
            'selling_price' => 1_200_000,
        ]);
        $contract = Contract::create([
            'contract_number' => "KANBAN-{$unique}",
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'total_price' => 1_200_000,
            'tenor' => 1,
            'installment' => 1_200_000,
            'start_date' => today(),
            'status' => 'active',
        ]);
        $payment = Payment::create([
            'contract_id' => $contract->id,
            'installment_number' => 1,
            'due_date' => today()->addWeek(),
            'amount' => 1_200_000,
            'status' => 'unpaid',
        ]);

        Livewire::test(PaymentKanban::class)
            ->assertOk()
            ->assertSee('Customer Kanban')
            ->assertSee('Akan Datang')
            ->call('markAsPaid', $payment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'paid_amount' => 1_200_000,
        ]);
        $this->assertSame('completed', $contract->refresh()->status);
    }
}
