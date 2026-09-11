<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Contract;
use App\Models\Payment;
use App\Services\WaService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PaymentResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Monitoring Tagihan';

    protected static ?string $pluralModelLabel = 'Monitoring Tagihan';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'product', 'payments']))
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('No. Kontrak')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Contract $record): string => $record->start_date ? 'Mulai: ' . $record->start_date->format('d/m/Y') : ''),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Contract $record): string => $record->customer?->phone_number ? '📞 ' . $record->customer->phone_number : '-'),

                Tables\Columns\TextColumn::make('product.model_name')
                    ->label('Unit HP')
                    ->searchable()
                    ->description(fn (Contract $record): string => 'Tenor ' . ($record->tenor ?? 6) . ' Bln @ Rp ' . number_format((float) $record->installment, 0, ',', '.')),

                Tables\Columns\ViewColumn::make('installment_matrix')
                    ->label('Status Cicilan (1 - 6)')
                    ->view('filament.tables.columns.installment-matrix'),

                Tables\Columns\TextColumn::make('progress_cicilan')
                    ->label('Progress Lunas')
                    ->badge()
                    ->state(function (Contract $record): string {
                        $total = $record->payments->count();
                        $paid = $record->payments->where('status', 'paid')->count();
                        $percent = $total > 0 ? round(($paid / $total) * 100) : 0;
                        return "{$paid}/{$total} ({$percent}%)";
                    })
                    ->color(function (Contract $record): string {
                        $hasOverdue = $record->payments->contains(function ($payment) {
                            return $payment->status === 'overdue' || ($payment->status === 'unpaid' && Carbon::parse($payment->due_date)->isPast() && !Carbon::parse($payment->due_date)->isToday());
                        });

                        if ($hasOverdue) return 'danger';

                        $total = $record->payments->count();
                        $paid = $record->payments->where('status', 'paid')->count();
                        if ($total > 0 && $paid === $total) return 'success';

                        return 'warning';
                    }),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Sisa Tagihan')
                    ->state(function (Contract $record): string {
                        $unpaidSum = $record->payments->where('status', '!=', 'paid')->sum('amount');
                        return 'Rp ' . number_format((float) $unpaidSum, 0, ',', '.');
                    })
                    ->weight('semibold')
                    ->color(fn (Contract $record): string => $record->payments->where('status', '!=', 'paid')->isEmpty() ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'    => 'primary',
                        'completed' => 'success',
                        'defaulted' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Kontrak')
                    ->options([
                        'active'    => 'Aktif (Berjalan)',
                        'completed' => 'Lunas (Selesai)',
                        'defaulted' => 'Macet (Defaulted)',
                    ]),

                Tables\Filters\Filter::make('has_overdue')
                    ->label('Hanya yang Ada Tunggakan (Overdue)')
                    ->query(fn (Builder $query): Builder => $query->whereHas('payments', function ($q) {
                        $q->where('status', 'overdue')
                          ->orWhere(function ($q2) {
                              $q2->where('status', 'unpaid')
                                 ->where('due_date', '<', now()->toDateString());
                          });
                    })),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Bayar Cicilan')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->hidden(fn (Contract $record) => $record->payments->where('status', '!=', 'paid')->isEmpty())
                    ->form(function (Contract $record) {
                        $unpaidPayments = $record->payments->where('status', '!=', 'paid')->sortBy('installment_number');
                        $options = $unpaidPayments->mapWithKeys(function ($p) {
                            $dueDate = Carbon::parse($p->due_date)->translatedFormat('d M Y');
                            $amount = 'Rp ' . number_format((float) $p->amount, 0, ',', '.');
                            return [$p->id => "Cicilan Ke-{$p->installment_number} ({$amount} - Jatuh Tempo: {$dueDate})"];
                        })->toArray();

                        $firstUnpaid = $unpaidPayments->first();

                        return [
                            Forms\Components\Select::make('payment_id')
                                ->label('Pilih Cicilan yang Dibayar')
                                ->options($options)
                                ->default($firstUnpaid?->id)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if ($state) {
                                        $payment = Payment::find($state);
                                        if ($payment) {
                                            $set('paid_amount', $payment->amount);
                                        }
                                    }
                                }),

                            Forms\Components\TextInput::make('paid_amount')
                                ->label('Nominal Pembayaran')
                                ->numeric()
                                ->prefix('Rp')
                                ->default($firstUnpaid?->amount)
                                ->required(),

                            Forms\Components\DateTimePicker::make('paid_at')
                                ->label('Waktu Pembayaran')
                                ->default(now())
                                ->required(),
                        ];
                    })
                    ->action(function (Contract $record, array $data) {
                        $payment = Payment::find($data['payment_id']);
                        if ($payment) {
                            $payment->update([
                                'paid_amount' => $data['paid_amount'],
                                'paid_at'     => $data['paid_at'],
                                'status'      => 'paid',
                            ]);

                            // Jika semua cicilan pada kontrak ini sudah lunas, update status kontrak menjadi completed
                            $remaining = $record->payments()->where('status', '!=', 'paid')->count();
                            if ($remaining === 0) {
                                $record->update(['status' => 'completed']);
                            }

                            Notification::make()
                                ->title("Cicilan Ke-{$payment->installment_number} Berhasil Dilunasi")
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('sendWaNotice')
                    ->label('Kirim WA')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('warning')
                    ->hidden(fn (Contract $record) => $record->payments->where('status', '!=', 'paid')->isEmpty())
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Pesan Tagihan WhatsApp')
                    ->modalDescription(fn (Contract $record): string => "Kirimkan pengingat tagihan cicilan ke nomor {$record->customer?->phone_number} ({$record->customer?->name})?")
                    ->action(function (Contract $record) {
                        $nextUnpaid = $record->payments->where('status', '!=', 'paid')->sortBy('installment_number')->first();

                        if (!$nextUnpaid) {
                            Notification::make()
                                ->title('Semua cicilan pada kontrak ini sudah lunas.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $customer = $record->customer;
                        $dueDate = Carbon::parse($nextUnpaid->due_date)->translatedFormat('d F Y');
                        $nominal = 'Rp ' . number_format((float) $nextUnpaid->amount, 0, ',', '.');

                        $message = "Halo Bpk/Ibu *{$customer->name}*,\n\n"
                            . "Kami ingin mengingatkan bahwa angsuran cicilan HP Anda untuk No. Kontrak *{$record->contract_number}* (Cicilan Ke-{$nextUnpaid->installment_number}) sebesar *{$nominal}* akan/telah jatuh tempo pada *{$dueDate}*.\n\n"
                            . "Mohon segera melakukan pembayaran. Abaikan pesan ini jika Anda sudah melakukan pelunasan.\n\n"
                            . "Terima kasih.";

                        $sent = WaService::sendMessage($customer->phone_number, $message);

                        if ($sent) {
                            $nextUnpaid->update(['wa_reminder_sent_at' => now()]);

                            Notification::make()
                                ->title('Pesan WA Berhasil Dikirim')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Mengirim WA')
                                ->body('Periksa pengaturan gateway WhatsApp Anda.')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('viewContract')
                    ->label('Detail Kontrak')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Contract $record): string => ContractResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}