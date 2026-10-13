<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PaymentSettlement
{
    public function settle(int $paymentId, ?string $paidAmount = null, ?string $paidAt = null): bool
    {
        return DB::transaction(function () use ($paymentId, $paidAmount, $paidAt): bool {
            $candidate = Payment::findOrFail($paymentId);
            abort_unless(auth()->user()?->is_active, 403);
            Gate::authorize('update', $candidate);

            // Serialize settlements across all installments of the same contract.
            $contract = Contract::query()->lockForUpdate()->findOrFail($candidate->contract_id);
            $payment = $contract->payments()->lockForUpdate()->findOrFail($paymentId);

            if ($payment->status === 'paid') {
                return false;
            }

            $payment->update([
                'paid_amount' => $paidAmount ?? $payment->amount,
                'paid_at' => $paidAt ?? now(),
                'status' => 'paid',
            ]);

            if ($contract->payments()->count() >= (int) $contract->tenor
                && $contract->payments()->where('status', '!=', 'paid')->doesntExist()) {
                $contract->update(['status' => 'completed']);
            }

            return true;
        });
    }
}
