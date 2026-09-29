<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Jadwal & Riwayat Angsuran';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('installment_number')
                    ->label('Cicilan Ke-')
                    ->required()
                    ->numeric(),

                DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->required(),

                TextInput::make('amount')
                    ->label('Nominal Tagihan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Select::make('status')
                    ->options([
                        'unpaid' => 'Belum Dibayar',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('installment_number')
                    ->label('Angsuran Ke')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Nominal Tagihan')
                    ->money('IDR'),

                TextColumn::make('paid_amount')
                    ->label('Jumlah Dibayar')
                    ->money('IDR')
                    ->placeholder('-'),

                TextColumn::make('paid_at')
                    ->label('Tgl Bayar')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                    }),
            ])
            ->recordActions([
                Action::make('markAsPaid')
                    ->label('Catat Pelunasan')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->hidden(fn ($record) => $record->status === 'paid')
                    ->schema([
                        TextInput::make('paid_amount')
                            ->label('Nominal Pembayaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn ($record) => $record->amount)
                            ->required(),
                        DateTimePicker::make('paid_at')
                            ->label('Waktu Pembayaran')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'paid_amount' => $data['paid_amount'],
                            'paid_at' => $data['paid_at'],
                            'status' => 'paid',
                        ]);
                    }),

                EditAction::make(),
            ]);
    }
}
