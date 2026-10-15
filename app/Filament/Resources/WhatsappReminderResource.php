<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappReminderResource\Pages\ListWhatsappReminders;
use App\Models\Payment;
use App\Models\WhatsappReminder;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WhatsappReminderResource extends Resource
{
    protected static ?string $model = WhatsappReminder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Pengingat WhatsApp';

    protected static ?string $pluralModelLabel = 'Riwayat Pengingat WhatsApp';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Payment::class) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('payment.contract.contract_number')->label('Kontrak')->searchable(),
            TextColumn::make('payment.contract.customer.name')->label('Nasabah')->searchable(),
            TextColumn::make('payment.installment_number')->label('Cicilan ke-'),
            TextColumn::make('kind')->label('Tahap')->formatStateUsing(fn (string $state): string => match ($state) {
                'before_due' => 'H-1', 'due' => 'Jatuh tempo', 'manual' => 'Manual', default => str_starts_with($state, 'manual_') ? 'Manual' : $state,
            }),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'sent' => 'Diterima gateway', 'failed' => 'Gagal', 'unknown' => 'Perlu diperiksa',
                    'sending' => 'Sedang dikirim', 'skipped' => 'Dilewati', default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'sent' => 'success', 'failed', 'unknown' => 'danger', 'sending' => 'warning', default => 'gray',
                }),
            TextColumn::make('attempts')->label('Percobaan'),
            TextColumn::make('error_code')->label('Keterangan')->placeholder('-'),
            TextColumn::make('attempted_at')->label('Percobaan terakhir')->dateTime('d/m/Y H:i')->timezone(config('whatsapp.reminders.timezone')),
            TextColumn::make('sent_at')->label('Diterima gateway')->dateTime('d/m/Y H:i')->timezone(config('whatsapp.reminders.timezone'))->placeholder('-'),
        ])->filters([
            SelectFilter::make('status')->options([
                'sent' => 'Diterima gateway', 'failed' => 'Gagal', 'unknown' => 'Perlu diperiksa',
                'sending' => 'Sedang dikirim', 'skipped' => 'Dilewati',
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListWhatsappReminders::route('/')];
    }
}
