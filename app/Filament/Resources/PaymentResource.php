<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\WaService; // Atau ganti ke App\Services\WaService sesuai class Anda
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Monitoring Tagihan';

    protected static ?string $pluralModelLabel = 'Monitoring Tagihan';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('No. Kontrak')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contract.customer.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('installment_number')
                    ->label('Cicilan Ke')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal Tagihan')
                    ->formatStateUsing(fn ($state) => 'Rp. ' . number_format((float) $state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Dibayar')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp. ' . number_format((float) $state, 0, ',', '.') : '-'),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Tanggal Pembayaran')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid'  => 'warning',
                        'paid'    => 'success',
                        'overdue' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('wa_reminder_sent_at')
                    ->label('WA Terkirim')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Belum')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'unpaid'  => 'Belum Dibayar',
                        'paid'    => 'Lunas',
                        'overdue' => 'Terlambat (Overdue)',
                    ]),
            ])
            ->actions([
                // Action Pelunasan
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Pelunasan')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->hidden(fn (Payment $record) => $record->status === 'paid')
                    ->form([
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Nominal Pembayaran')
                            ->numeric()
                            ->prefix('Rp.')
                            ->default(fn (Payment $record) => $record->amount)
                            ->required(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $record->update([
                            'paid_amount' => $data['paid_amount'],
                            'paid_at'     => $data['paid_at'],
                            'status'      => 'paid',
                        ]);

                        Notification::make()
                            ->title('Pembayaran Berhasil Dicatat')
                            ->success()
                            ->send();
                    }),

                // Action Kirim WA Manual
                Tables\Actions\Action::make('sendWaNotice')
                    ->label('Kirim WA Warning')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Tagihan WA')
                    ->modalDescription('Apakah Anda yakin ingin mengirim pesan penagihan ke nasabah ini?')
                    ->action(function (Payment $record) {
                        $customer = $record->contract->customer;
                        $dueDate = \Carbon\Carbon::parse($record->due_date)->translatedFormat('d F Y');
                        $nominal = 'Rp. ' . number_format((float) $record->amount, 0, ',', '.');

                        // Template Pesan Penagihan
                        $message = "Halo Bpk/Ibu *{$customer->name}*,\n\n"
                            . "Kami ingin mengingatkan bahwa angsuran cicilan HP Anda untuk No. Kontrak *{$record->contract->contract_number}* (Cicilan Ke-{$record->installment_number}) sebesar *{$nominal}* akan/telah jatuh tempo pada *{$dueDate}*.\n\n"
                            . "Mohon segera melakukan pembayaran. Abaikan pesan ini jika Anda sudah melakukan pelunasan.\n\n"
                            . "Terima kasih.";

                        $sent = WaService::sendMessage($customer->phone_number, $message);

                        if ($sent) {
                            $record->update(['wa_reminder_sent_at' => now()]);

                            Notification::make()
                                ->title('Pesan WA Berhasil Dikirim')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Mengirim WA')
                                ->body('Periksa log atau token WA Gateway Anda.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            // 🟢 WA BLAST MASAL (BULK ACTIONS)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('sendBulkWaNotice')
                        ->label('Kirim WA Blast (Terpilih)')
                        ->icon('heroicon-m-paper-airplane')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi WA Blast')
                        ->modalDescription('Pesan peringatan tagihan akan dikirimkan ke seluruh nasabah yang dicentang.')
                        ->action(function (Collection $records) {
                            $successCount = 0;

                            foreach ($records as $record) {
                                if ($record->status !== 'paid') {
                                    $customer = $record->contract->customer;
                                    $dueDate = \Carbon\Carbon::parse($record->due_date)->translatedFormat('d F Y');
                                    $nominal = 'Rp. ' . number_format((float) $record->amount, 0, ',', '.');

                                    $message = "Halo Bpk/Ibu *{$customer->name}*,\n\n"
                                        . "Pemberitahuan tagihan cicilan HP No. Kontrak *{$record->contract->contract_number}* sebesar *{$nominal}* jatuh tempo pada *{$dueDate}*.\n\n"
                                        . "Mohon segera melakukan pembayaran. Terima kasih.";

                                    if (WhatsAppService::sendMessage($customer->phone_number, $message)) {
                                        $record->update(['wa_reminder_sent_at' => now()]);
                                        $successCount++;
                                    }
                                }
                            }

                            Notification::make()
                                ->title("WA Blast Selesai")
                                ->body("Berhasil mengirim {$successCount} pesan WA.")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}