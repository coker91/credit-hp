<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PaymentKanban extends Page
{
    protected static string $resource = PaymentResource::class;

    protected string $view = 'filament.resources.payment-resource.pages.payment-kanban';

    protected static ?string $title = 'Kanban Cicilan';

    public string $search = '';

    public function getBoardProperty(): array
    {
        $columns = [
            'upcoming' => [
                'label' => 'Akan Datang',
                'description' => 'Belum jatuh tempo',
                'color' => 'info',
                'icon' => 'heroicon-m-calendar-days',
                'payments' => collect(),
            ],
            'due_today' => [
                'label' => 'Jatuh Tempo Hari Ini',
                'description' => 'Perlu dikonfirmasi hari ini',
                'color' => 'warning',
                'icon' => 'heroicon-m-clock',
                'payments' => collect(),
            ],
            'overdue' => [
                'label' => 'Terlambat',
                'description' => 'Prioritas untuk ditagih',
                'color' => 'danger',
                'icon' => 'heroicon-m-exclamation-circle',
                'payments' => collect(),
            ],
            'paid' => [
                'label' => 'Lunas',
                'description' => 'Seret kartu ke kolom ini',
                'color' => 'success',
                'icon' => 'heroicon-m-check-circle',
                'payments' => collect(),
            ],
        ];

        $payments = Payment::query()
            ->with(['contract.customer', 'contract.product'])
            ->when(filled($this->search), function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('contract', fn (Builder $contract): Builder => $contract
                            ->where('contract_number', 'like', "%{$search}%"))
                        ->orWhereHas('contract.customer', fn (Builder $customer): Builder => $customer
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%"))
                        ->orWhereHas('contract.product', fn (Builder $product): Builder => $product
                            ->where('brand', 'like', "%{$search}%"));
                });
            })
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->get();

        foreach ($payments as $payment) {
            $column = match (true) {
                $payment->status === 'paid' => 'paid',
                $payment->due_date->isToday() => 'due_today',
                $payment->status === 'overdue' || $payment->due_date->isPast() => 'overdue',
                default => 'upcoming',
            };

            $columns[$column]['payments']->push($payment);
        }

        $columns['paid']['payments'] = $columns['paid']['payments']
            ->sortByDesc('paid_at')
            ->values();

        return $columns;
    }

    public function markAsPaid(int $paymentId): void
    {
        $updated = DB::transaction(function () use ($paymentId): bool {
            $payment = Payment::query()
                ->with('contract')
                ->lockForUpdate()
                ->findOrFail($paymentId);

            Gate::authorize('update', $payment);

            if ($payment->status === 'paid') {
                return false;
            }

            $payment->update([
                'paid_amount' => $payment->amount,
                'paid_at' => now(),
                'status' => 'paid',
            ]);

            $scheduleCount = $payment->contract->payments()->count();
            $hasCompleteSchedule = $scheduleCount >= (int) $payment->contract->tenor;

            if ($hasCompleteSchedule && $payment->contract->payments()->where('status', '!=', 'paid')->doesntExist()) {
                $payment->contract->update(['status' => 'completed']);
            }

            return true;
        });

        unset($this->board);

        Notification::make()
            ->title($updated ? 'Pembayaran berhasil dicatat' : 'Cicilan sudah lunas')
            ->body($updated ? 'Kartu telah dipindahkan ke kolom Lunas.' : 'Tidak ada perubahan yang perlu disimpan.')
            ->color($updated ? 'success' : 'gray')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table')
                ->label('Mode Tabel')
                ->icon('heroicon-m-table-cells')
                ->color('gray')
                ->url(PaymentResource::getUrl('index')),
        ];
    }
}
