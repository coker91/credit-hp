<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Jadwal & Riwayat Angsuran';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('installment_number')
                    ->label('Cicilan Ke-')
                    ->required()
                    ->numeric(),

                Forms\Components\DatePicker::make('due_date')
                    ->label('Jatuh Tempo')
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal Tagihan')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'unpaid'  => 'Belum Dibayar',
                        'paid'    => 'Lunas',
                        'overdue' => 'Terlambat',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('installment_number')
                    ->label('Angsuran Ke')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal Tagihan')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Jumlah Dibayar')
                    ->money('IDR')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Tgl Bayar')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid'  => 'warning',
                        'paid'    => 'success',
                        'overdue' => 'danger',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Catat Pelunasan')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->hidden(fn ($record) => $record->status === 'paid')
                    ->form([
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Nominal Pembayaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn ($record) => $record->amount)
                            ->required(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Waktu Pembayaran')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'paid_amount' => $data['paid_amount'],
                            'paid_at'     => $data['paid_at'],
                            'status'      => 'paid',
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }
}