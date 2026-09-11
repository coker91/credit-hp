<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Payment;
use Carbon\Carbon;

class InstallmentService
{
    public function calculateMonthlyInstallment(float $totalPrice, float $downPayment, int $tenorMonths): float
    {
        $principal = $totalPrice - $downPayment;
        return round($principal / $tenorMonths, 2);
    }

    public function generatePaymentSchedules(Contract $contract): void
    {
        $startDate = Carbon::parse($contract->start_date)->startOfDay();

        for ($i = 1; $i <= $contract->tenor; $i++) {
            $dueDate = $startDate->copy()->addMonthsNoOverflow($i - 1);

            Payment::create([
                'contract_id' => $contract->id,
                'installment_number' => $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $contract->installment,
                'status' => 'unpaid',
            ]);
        }
    }
}