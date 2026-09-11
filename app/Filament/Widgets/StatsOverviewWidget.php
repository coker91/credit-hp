<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now = Carbon::now();

        $cashInThisMonth = Payment::where('status', 'paid')
            ->whereYear('paid_at', $now->year)
            ->whereMonth('paid_at', $now->month)
            ->sum('paid_amount');

        $dueThisMonth = Payment::whereYear('due_date', $now->year)
            ->whereMonth('due_date', $now->month)
            ->where('status', 'unpaid')
            ->sum('amount');

        $overdueCount = Payment::where('due_date', '<', $now->toDateString())
            ->where('status', 'unpaid')
            ->count();

        $activeContractsCount = Contract::where('status', 'active')->count();

        return [
            Stat::make('Kas Masuk Bulan Ini', 'Rp. ' . number_format($cashInThisMonth, 0, ',', '.'))
                ->description('Total pelunasan cicilan diterima')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Tagihan Bulan Ini', 'Rp. ' . number_format($dueThisMonth, 0, ',', '.'))
                ->description('Target penerimaan bulan berjalan')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make('Cicilan Menunggak', $overdueCount . ' Tagihan')
                ->description('Membutuhkan tindakan / WA Warning')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Kontrak Aktif', $activeContractsCount . ' Kontrak')
                ->description('Nasabah sedang berjalan')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}