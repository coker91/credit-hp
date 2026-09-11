<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\Payment;
use App\Services\WaService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class MatrixCicilanPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationGroup = 'Laporan & Analytics';

    protected static ?string $navigationLabel = 'Matrix Cicilan & Laba';

    protected static ?string $title = 'Matrix Monitoring Cicilan, Modal & Laba';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.matrix-cicilan-page';

    public ?string $search = '';

    public string $filterStatus = 'active';

    public function getContractsProperty()
    {
        return Contract::with(['customer', 'product', 'payments'])
            ->when($this->filterStatus !== 'all', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q
                        ->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('product', fn($p) => $p
                            ->where('model_name', 'like', "%{$this->search}%")
                            ->orWhere('brand', 'like', "%{$this->search}%"))
                        ->orWhere('contract_number', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getMaxTenorProperty(): int
    {
        $contracts = $this->contracts;
        if ($contracts->isEmpty()) {
            return 7;
        }

        return max(7, (int) $contracts->max('tenor'));
    }

    public function getSummaryStatsProperty(): array
    {
        $contracts = $this->contracts;

        $totalMonthlyInstallment = 0;
        $totalMonthlyModal = 0;
        $totalMonthlyLaba = 0;

        foreach ($contracts as $contract) {
            $monthlyInstallment = (float) $contract->installment;
            $tenor = max(1, (int) $contract->tenor);

            $actualCostTotal = (float) (
                $contract->actual_cost_price
                    ?? $contract->product?->actual_cost_price
                    ?? $contract->product?->cost_price
                    ?? 0
            );

            $modalPerBulan = $tenor > 0 ? ($actualCostTotal / $tenor) : 0;
            $labaPerBulan = round($monthlyInstallment - $modalPerBulan);

            $totalMonthlyInstallment += $monthlyInstallment;
            $totalMonthlyModal += $modalPerBulan;
            $totalMonthlyLaba += $labaPerBulan;
        }

        $marginPercent = $totalMonthlyInstallment > 0
            ? round(($totalMonthlyLaba / $totalMonthlyInstallment) * 100, 1)
            : 0;

        return [
            'total_installment' => $totalMonthlyInstallment,
            'total_modal' => $totalMonthlyModal,
            'total_laba' => $totalMonthlyLaba,
            'margin_percent' => $marginPercent,
            'active_count' => $contracts->count(),
        ];
    }

    public function markAsPaid(int $paymentId): void
    {
        $payment = Payment::find($paymentId);
        if ($payment && $payment->status !== 'paid') {
            $payment->update([
                'paid_amount' => $payment->amount,
                'paid_at' => now(),
                'status' => 'paid',
            ]);

            Notification::make()
                ->title("Cicilan Ke-{$payment->installment_number} Berhasil Diberikan Status Lunas")
                ->success()
                ->send();
        }
    }

    public function sendWaNotice(int $paymentId): void
    {
        $payment = Payment::with('contract.customer')->find($paymentId);
        if ($payment) {
            $customer = $payment->contract->customer;
            $dueDate = Carbon::parse($payment->due_date)->translatedFormat('d F Y');
            $nominal = 'Rp ' . number_format((float) $payment->amount, 0, ',', '.');

            $message = "Halo Bpk/Ibu *{$customer->name}*,\n\n"
                . "Kami ingin mengingatkan angsuran cicilan HP Anda untuk No. Kontrak *{$payment->contract->contract_number}* (Cicilan Ke-{$payment->installment_number}) sebesar *{$nominal}* akan/telah jatuh tempo pada *{$dueDate}*.\n\n"
                . "Mohon segera melakukan pembayaran. Abaikan jika sudah lunas.\n\n"
                . 'Terima kasih.';

            $sent = WaService::sendMessage($customer->phone_number, $message);

            if ($sent) {
                $payment->update(['wa_reminder_sent_at' => now()]);

                Notification::make()
                    ->title('Pesan WA Warning Berhasil Dikirim')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Gagal Mengirim Pesan WA')
                    ->danger()
                    ->send();
            }
        }
    }
}
