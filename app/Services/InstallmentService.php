<?php


namespace App\Services;

use App\Models\Contract;
use App\Models\Payment;
use Carbon\Carbon;

class InstallmentService
{
    /**
     * Hitung nominal angsuran bulanan.
     */
    public function calculateMonthlyInstallment(float $totalPrice, float $downPayment, int $tenorMonths): float
    {
        $principal = $totalPrice - $downPayment;
        return round($principal / $tenorMonths, 2);
    }

    /**
     * Otomatis generate baris tagihan pada tabel `payments` berdasarkan tenor kontrak.
     */
    public function generatePaymentSchedules(Contract $contract): void
    {
        $startDate = Carbon::parse($contract->start_date);

        for ($i = 1; $i <= $contract->tenor_months; $i++) {
            // Jatuh tempo diset setiap bulan di tanggal yang sama dengan start_date
            $dueDate = $startDate->copy()->addMonths($i);

            Payment::create([
                'contract_id'        => $contract->id,
                'installment_number' => $i,
                'due_date'           => $dueDate->toDateString(),
                'amount'             => $contract->monthly_installment,
                'status'             => 'unpaid',
            ]);
        }
    }
}