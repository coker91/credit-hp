<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CashflowChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Grafik Penerimaan Kas Bulanan';

    protected static ?int $sort = 2;

    // 🟢 Bikin Tampilan Widget Penuh Memanjang Ke Samping (Full Span)
    protected int | string | array $columnSpan = 'full';

    protected function getFilters(): ?array
    {
        $currentYear = (int) Carbon::now()->format('Y');
        $years = [];

        for ($year = $currentYear - 2; $year <= $currentYear; $year++) {
            $years[$year] = 'Tahun ' . $year;
        }

        return $years;
    }

    protected function getData(): array
    {
        $selectedYear = $this->filter ?? Carbon::now()->year;

        $data = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        for ($month = 1; $month <= 12; $month++) {
            $sum = Payment::where('status', 'paid')
                ->whereYear('paid_at', $selectedYear)
                ->whereMonth('paid_at', $month)
                ->sum('paid_amount');

            $data[] = $sum;
        }

        return [
            'datasets' => [
                [
                    'label' => "Kas Masuk {$selectedYear} (Rp)",
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}