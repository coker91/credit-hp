<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\WhatsappReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentReminderService
{
    public function send(int $paymentId, bool $automatic = true): string
    {
        if (($automatic && ! config('whatsapp.reminders.enabled')) || ! WaService::isConfigured()) {
            return 'skipped';
        }

        $reminder = DB::transaction(function () use ($paymentId, $automatic): ?WhatsappReminder {
            $candidate = Payment::findOrFail($paymentId);
            Contract::query()->lockForUpdate()->findOrFail($candidate->contract_id);
            $payment = Payment::with(['contract.customer', 'contract.product'])->lockForUpdate()->findOrFail($paymentId);
            if (! $this->eligible($payment, $automatic)) {
                return null;
            }

            $reminder = WhatsappReminder::firstOrCreate(
                ['payment_id' => $paymentId, 'kind' => $this->kind($payment)], ['status' => 'pending'],
            );
            if (in_array($reminder->status, ['sending', 'sent', 'unknown', 'skipped'], true)
                || $reminder->attempts >= config('whatsapp.reminders.max_attempts')
                || ($reminder->status === 'failed' && (($automatic && ! $reminder->next_attempt_at) || $reminder->next_attempt_at?->isFuture()))) {
                return null;
            }

            // Persist the claim before making an external request. A crashed sender is not retried blindly.
            $reminder->update([
                'status' => 'sending', 'attempts' => $reminder->attempts + 1,
                'attempted_at' => now(), 'next_attempt_at' => null, 'error_code' => null,
            ]);

            return $reminder;
        });

        if (! $reminder) {
            return 'skipped';
        }

        $payment = Payment::with(['contract.customer', 'contract.product'])->find($paymentId);
        if (! $payment || ! $this->eligible($payment, $automatic) || $reminder->kind !== $this->kind($payment)) {
            $reminder->update(['status' => 'skipped', 'error_code' => 'payment_changed']);

            return 'skipped';
        }

        $customer = $payment->contract->customer;
        $product = $payment->contract->product?->brand ?? 'Barang sesuai kontrak';
        $date = $payment->due_date->locale('id')->translatedFormat('d F Y');
        $monthly = 'Rp '.number_format((float) $payment->contract->installment, 0, ',', '.');
        $intro = match ($reminder->kind) {
            'before_due' => 'Kami mengingatkan bahwa *besok* angsuran Anda jatuh tempo.',
            'due' => 'Kami mengingatkan bahwa *hari ini* angsuran Anda jatuh tempo.',
            default => 'Berikut informasi tagihan angsuran Anda.',
        };
        $message = "Halo Bapak/Ibu *{$customer->name}*,\n\n{$intro}\n\n"
            ."Barang: *{$product}*\n"
            ."Cicilan per bulan: *{$monthly}*\n"
            ."Cicilan ke-: {$payment->installment_number}\n"
            ."Tanggal jatuh tempo: *{$date}*\n"
            ."No. kontrak: {$payment->contract->contract_number}\n\n"
            ."Mohon melakukan pembayaran sesuai jadwal. Jika sudah membayar, abaikan pesan ini dan konfirmasikan kepada admin.\n\nTerima kasih.";

        $result = WaService::send((string) $customer->phone_number, $message);

        DB::transaction(function () use ($reminder, $payment, $result): void {
            $retry = $result->retryable && $reminder->attempts < config('whatsapp.reminders.max_attempts');
            $reminder->update([
                'status' => $result->status, 'error_code' => $result->errorCode,
                'provider_message_id' => $result->messageId,
                'sent_at' => $result->status === 'sent' ? now() : null,
                'next_attempt_at' => $retry ? now()->addMinutes(config('whatsapp.reminders.retry_minutes')) : null,
            ]);
            if ($result->status === 'sent') {
                $payment->update(['wa_reminder_sent_at' => now()]);
            }
        });

        // Never log recipient phone numbers, message bodies, keys, or provider responses.
        Log::info('WhatsApp installment reminder processed.', [
            'payment_id' => $paymentId, 'status' => $result->status, 'error_code' => $result->errorCode,
        ]);

        return $result->status;
    }

    private function kind(Payment $payment): string
    {
        $today = now(config('whatsapp.reminders.timezone'));
        if ($payment->due_date->toDateString() === $today->toDateString()) {
            return 'due';
        }
        if ($payment->due_date->toDateString() === $today->copy()->addDay()->toDateString()) {
            return 'before_due';
        }

        return 'manual_'.$today->format('Ymd');
    }

    private function eligible(Payment $payment, bool $automatic): bool
    {
        if (! in_array($payment->status, ['unpaid', 'overdue'], true)
            || $payment->wa_reminder_sent_at?->timezone(config('whatsapp.reminders.timezone'))->isSameDay(now(config('whatsapp.reminders.timezone')))
            || ! $payment->contract?->customer
            || $payment->contract->status === 'completed') {
            return false;
        }

        return ! $automatic || ($payment->contract->status === 'active'
            && in_array($payment->due_date->toDateString(), [
                now(config('whatsapp.reminders.timezone'))->toDateString(),
                now(config('whatsapp.reminders.timezone'))->addDay()->toDateString(),
            ], true));
    }
}
