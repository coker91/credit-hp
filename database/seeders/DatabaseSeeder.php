<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        if (!User::where('email', 'admin@admin.com')->exists()) {
            User::factory()->create([
                'name' => 'Admin Credit HP',
                'email' => 'admin@admin.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Clean existing transaction data for fresh matrix demo
        Payment::query()->delete();
        Contract::query()->delete();
        Customer::query()->delete();
        Product::query()->delete();

        $rows = [
            [
                'product' => 'Selis',
                'brand' => 'Selis',
                'customer' => 'Kokom',
                'phone' => '081234567801',
                'monthly' => 750000,
                'modal_monthly' => 586983,
                'tenor' => 6,
                'dates' => ['2026-01-18', '2026-02-28', '2026-04-06', '2026-06-14', '2026-07-12', '2026-08-15'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'paid', 'overdue'],
            ],
            [
                'product' => 'Kulkas Sharp',
                'brand' => 'Sharp',
                'customer' => 'Warteg',
                'phone' => '081234567802',
                'monthly' => 620000,
                'modal_monthly' => 464198,
                'tenor' => 6,
                'dates' => ['2026-01-23', '2026-02-26', '2026-03-30', '2026-04-29', '2026-05-27', '2026-06-28'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'paid', 'paid'],
            ],
            [
                'product' => 'Vivo Y19sGT',
                'brand' => 'Vivo',
                'customer' => 'Suetik',
                'phone' => '081234567803',
                'monthly' => 430000,
                'modal_monthly' => 256117,
                'tenor' => 6,
                'dates' => ['2026-02-08', '2026-03-10', '2026-04-07', '2026-05-08', '2026-06-10', '2026-07-08'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'paid', 'paid'],
            ],
            [
                'product' => 'Samsung A56',
                'brand' => 'Samsung',
                'customer' => 'Ica',
                'phone' => '081234567804',
                'monthly' => 1170000,
                'modal_monthly' => 839759,
                'tenor' => 6,
                'dates' => ['2026-02-02', '2026-02-28', '2026-03-29', '2026-04-28', '2026-05-28', '2026-06-28'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'paid', 'paid'],
            ],
            [
                'product' => 'Infinix Smart 10',
                'brand' => 'Infinix',
                'customer' => 'Mita',
                'phone' => '081234567805',
                'monthly' => 300000,
                'modal_monthly' => 199050,
                'tenor' => 6,
                'dates' => ['2026-02-05', '2026-03-17', '2026-04-18', '2026-05-19', '2026-06-19', '2026-07-20'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'paid', 'overdue'],
            ],
            [
                'product' => 'Oppo A5i',
                'brand' => 'Oppo',
                'customer' => 'Aat',
                'phone' => '081234567806',
                'monthly' => 370000,
                'modal_monthly' => 255517,
                'tenor' => 6,
                'dates' => ['2026-02-06', '2026-03-19', '2026-04-13', '2026-05-25', '2026-07-01', '2026-08-01'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'paid', 'overdue'],
            ],
            [
                'product' => 'Vivo Y19sGT',
                'brand' => 'Vivo',
                'customer' => 'Angga',
                'phone' => '081234567807',
                'monthly' => 430000,
                'modal_monthly' => 302033,
                'tenor' => 6,
                'dates' => ['2026-02-13', '2026-03-13', '2026-04-18', '2026-05-27', '2026-06-16', '2026-07-15'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'paid', 'paid'],
            ],
            [
                'product' => 'Vivo Y19s',
                'brand' => 'Vivo',
                'customer' => 'Putri',
                'phone' => '081234567808',
                'monthly' => 400000,
                'modal_monthly' => 254137,
                'tenor' => 5,
                'dates' => ['2026-02-25', '2026-03-31', '2026-05-08', '2026-06-07', '2026-07-08'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'paid'],
            ],
            [
                'product' => 'Vivo Y19s',
                'brand' => 'Vivo',
                'customer' => 'Desi',
                'phone' => '081234567809',
                'monthly' => 400000,
                'modal_monthly' => 287218,
                'tenor' => 6,
                'dates' => ['2026-03-02', '2026-04-26', '2026-05-31', '2026-07-08', '2026-08-01', '2026-09-01'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'overdue', 'unpaid'],
            ],
            [
                'product' => 'Poco X7 Pro',
                'brand' => 'Poco',
                'customer' => 'Reza',
                'phone' => '081234567810',
                'monthly' => 1080000,
                'modal_monthly' => 752535,
                'tenor' => 6,
                'dates' => ['2026-03-25', '2026-04-28', '2026-05-28', '2026-06-25', '2026-07-24', '2026-08-25'],
                'statuses' => ['paid', 'paid', 'paid', 'paid', 'unpaid', 'unpaid'],
            ],
            [
                'product' => 'Redmi A7Pro',
                'brand' => 'Xiaomi',
                'customer' => 'Eha',
                'phone' => '081234567811',
                'monthly' => 400000,
                'modal_monthly' => 263150,
                'tenor' => 3,
                'dates' => ['2026-05-06', '2026-06-06', '2026-07-08'],
                'statuses' => ['paid', 'paid', 'paid'],
            ],
            [
                'product' => 'iPhone 17',
                'brand' => 'Apple',
                'customer' => 'Nabila',
                'phone' => '081234567812',
                'monthly' => 3800000,
                'modal_monthly' => 2611275,
                'tenor' => 1,
                'dates' => ['2026-07-02'],
                'statuses' => ['paid'],
            ],
            [
                'product' => 'Redmi 15',
                'brand' => 'Xiaomi',
                'customer' => 'Suetik',
                'phone' => '081234567813',
                'monthly' => 630000,
                'modal_monthly' => 474030,
                'tenor' => 1,
                'dates' => ['2026-07-11'],
                'statuses' => ['paid'],
            ],
            [
                'product' => 'Samsung A57 5G',
                'brand' => 'Samsung',
                'customer' => 'Deden',
                'phone' => '081234567814',
                'monthly' => 1430000,
                'modal_monthly' => 1091303,
                'tenor' => 1,
                'dates' => ['2026-07-11'],
                'statuses' => ['paid'],
            ],
        ];

        $ctrCount = 1;
        foreach ($rows as $item) {
            $costPriceTotal = $item['modal_monthly'] * $item['tenor'];
            $sellingPriceTotal = $item['monthly'] * $item['tenor'];

            $product = Product::create([
                'brand' => $item['brand'],
                'model_name' => $item['product'],
                'cost_price' => $costPriceTotal,
                'selling_price' => $sellingPriceTotal,
            ]);

            $customer = Customer::create([
                'nik' => '3201' . sprintf('%012d', $ctrCount),
                'name' => $item['customer'],
                'phone_number' => $item['phone'],
                'address' => 'Jl. Merdeka No. ' . $ctrCount,
            ]);

            $contractNumber = sprintf('CTR2608%03d', $ctrCount);

            $contract = Contract::create([
                'contract_number' => $contractNumber,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'down_payment' => 0,
                'total_price' => $sellingPriceTotal,
                'tenor_months' => $item['tenor'],
                'monthly_installment' => $item['monthly'],
                'start_date' => $item['dates'][0],
                'status' => 'active',
            ]);

            foreach ($item['dates'] as $idx => $dueDate) {
                $status = $item['statuses'][$idx] ?? 'unpaid';

                Payment::create([
                    'contract_id' => $contract->id,
                    'installment_number' => $idx + 1,
                    'due_date' => $dueDate,
                    'amount' => $item['monthly'],
                    'paid_amount' => $status === 'paid' ? $item['monthly'] : null,
                    'paid_at' => $status === 'paid' ? Carbon::parse($dueDate)->addHours(10) : null,
                    'status' => $status,
                ]);
            }

            $ctrCount++;
        }
    }
}
