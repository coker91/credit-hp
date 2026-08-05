<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class LatestOverdueWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '⚠️ Tagihan Menunggak (Butuh Follow-up)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->where('due_date', '<', Carbon::now()->toDateString())
                    ->where('status', 'unpaid')
                    ->orderBy('due_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('No. Kontrak')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contract.customer.name')
                    ->label('Nama Customer'),

                Tables\Columns\TextColumn::make('contract.customer.phone_number')
                    ->label('No. WA')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('installment_number')
                    ->label('Cicilan Ke')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp. ' . number_format((float) $state, 0, ',', '.')),
            ])
            ->actions([
                Tables\Actions\Action::make('sendWaNotice')
                    ->label('Kirim WA Warning')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('warning')
                    ->button(),
            ]);
    }
}