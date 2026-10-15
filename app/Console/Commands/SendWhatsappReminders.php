<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\WhatsappReminder;
use App\Services\PaymentReminderService;
use App\Services\WaService;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsappReminders extends Command
{
    protected $signature = 'whatsapp:reminders {--dry-run : Count eligible H-1 and due-date reminders without sending or writing data} {--limit=100 : Maximum payments per run (1-100)}';

    protected $description = 'Send H-1 and due-date WhatsApp reminders for unpaid installments after the configured local time';

    public function handle(PaymentReminderService $sender): int
    {
        $timezone = config('whatsapp.reminders.timezone');
        $time = config('whatsapp.reminders.time');
        if (! in_array($timezone, DateTimeZone::listIdentifiers(), true)
            || ! preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            $this->error('Invalid WA_REMINDER_TIMEZONE or WA_REMINDER_TIME (HH:MM).');

            return self::FAILURE;
        }
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        if ($limit === false) {
            $this->error('--limit must be between 1 and 100.');

            return self::FAILURE;
        }
        $dryRun = (bool) $this->option('dry-run');
        if (! $dryRun && ! config('whatsapp.reminders.enabled')) {
            $this->info('Automatic WhatsApp reminders are disabled.');

            return self::SUCCESS;
        }
        if (! $dryRun && ! WaService::isConfigured()) {
            $this->error('WhatsApp gateway is not configured; no messages were sent.');

            return self::FAILURE;
        }
        $localNow = now($timezone);
        if (! $dryRun && $localNow->format('H:i') < $time) {
            $this->info('Before the configured sending time; no messages were sent.');

            return self::SUCCESS;
        }

        if (! $dryRun && ($senderStatus = WaService::senderStatus(fresh: true)) !== 'ready') {
            $this->error('WhatsApp sender is not ready: '.$senderStatus.'. No messages were sent.');

            return self::FAILURE;
        }

        if (! $dryRun) {
            // A lost process may have reached the provider. Require review instead of resending.
            WhatsappReminder::where('status', 'sending')->where('attempted_at', '<', now()->subMinutes(30))
                ->update(['status' => 'unknown', 'error_code' => 'sender_interrupted', 'updated_at' => now()]);
        }

        $payments = Payment::query()
            ->whereIn('status', ['unpaid', 'overdue'])
            ->where(function (Builder $q) use ($localNow): void {
                $q->whereNull('wa_reminder_sent_at')
                    ->orWhere('wa_reminder_sent_at', '<', $localNow->copy()->startOfDay()->utc());
            })
            ->whereHas('contract', fn (Builder $q) => $q->where('status', 'active'))
            ->where(function (Builder $q) use ($localNow): void {
                foreach (['due' => $localNow->toDateString(), 'before_due' => $localNow->copy()->addDay()->toDateString()] as $kind => $date) {
                    $q->orWhere(function (Builder $stage) use ($kind, $date): void {
                        $stage->whereDate('due_date', $date)
                            ->where(function (Builder $available) use ($kind): void {
                                $available->whereDoesntHave('whatsappReminders', fn (Builder $r) => $r->where('kind', $kind))
                                    ->orWhereHas('whatsappReminders', fn (Builder $r) => $r->where('kind', $kind)
                                        ->where('status', 'failed')
                                        ->where('attempts', '<', config('whatsapp.reminders.max_attempts'))
                                        ->whereNotNull('next_attempt_at')->where('next_attempt_at', '<=', now()));
                            });
                    });
                }
            });
        if ($dryRun) {
            $this->info('Eligible H-1 / due-date reminders: '.$payments->count().'. No messages sent or data changed.');

            return self::SUCCESS;
        }

        $counts = ['sent' => 0, 'failed' => 0, 'unknown' => 0, 'skipped' => 0];
        foreach ($payments->orderBy('id')->limit($limit)->pluck('id') as $paymentId) {
            try {
                $counts[$sender->send($paymentId)]++;
            } catch (Throwable) {
                Log::error('WhatsApp reminder processing interrupted.', ['payment_id' => $paymentId]);
                $counts['unknown']++;
            }
        }
        $this->info("Sent: {$counts['sent']}; failed: {$counts['failed']}; unknown: {$counts['unknown']}; skipped: {$counts['skipped']}.");

        return $counts['failed'] || $counts['unknown'] ? self::FAILURE : self::SUCCESS;
    }
}
