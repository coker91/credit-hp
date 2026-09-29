<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\PaymentResource\Pages\PaymentKanban;
use App\Models\Contract;
use App\Models\Payment;
use App\Services\WaService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PaymentResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Monitoring Tagihan';

    protected static ?string $pluralModelLabel = 'Monitoring Tagihan';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'product', 'payments']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('contract_number')
                    ->label('No. Kontrak')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Contract $record): string => $record->start_date ? 'Mulai: '.$record->start_date->format('d/m/Y') : '')
                    ->extraHeaderAttributes(['class' => 'payment-sticky payment-sticky-contract'])
                    ->extraCellAttributes(['class' => 'payment-sticky payment-sticky-contract']),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->description(fn (Contract $record): string => $record->customer?->phone_number ?? 'Nomor telepon belum tersedia')
                    ->extraHeaderAttributes(['class' => 'payment-sticky payment-sticky-customer'])
                    ->extraCellAttributes(['class' => 'payment-sticky payment-sticky-customer']),

                TextColumn::make('product.brand')
                    ->label('Unit HP')
                    ->searchable()
                    ->description(fn (Contract $record): string => 'Tenor '.($record->tenor ?? 6).' Bln @ Rp '.number_format((float) $record->installment, 0, ',', '.'))
                    ->extraHeaderAttributes(['class' => 'payment-sticky payment-sticky-unit'])
                    ->extraCellAttributes(['class' => 'payment-sticky payment-sticky-unit']),

                ViewColumn::make('installment_matrix')
                    ->label('Rincian Cicilan')
                    ->view('filament.tables.columns.installment-matrix'),

                TextColumn::make('progress_cicilan')
                    ->label('Progress Lunas')
                    ->badge()
                    ->state(function (Contract $record): string {
                        $total = max((int) $record->tenor, $record->payments->count());
                        $paid = $record->payments->where('status', 'paid')->count();

                        return "{$paid} dari {$total} lunas";
                    })
                    ->color(function (Contract $record): string {
                        $total = max((int) $record->tenor, $record->payments->count());
                        $paid = $record->payments->where('status', 'paid')->count();

                        if ($total > 0 && $paid === $total) {
                            return 'success';
                        }

                        return $paid > 0 ? 'info' : 'gray';
                    })
                    ->icon(function (Contract $record): string {
                        $total = max((int) $record->tenor, $record->payments->count());
                        $paid = $record->payments->where('status', 'paid')->count();

                        return $total > 0 && $paid === $total
                            ? 'heroicon-m-check-circle'
                            : 'heroicon-m-chart-bar-square';
                    })
                    ->description(function (Contract $record): string {
                        $total = max((int) $record->tenor, $record->payments->count());
                        $paid = $record->payments->where('status', 'paid')->count();
                        $percent = $total > 0 ? round(($paid / $total) * 100) : 0;

                        return "{$percent}% selesai";
                    }),

                TextColumn::make('remaining_balance')
                    ->label('Sisa Tagihan')
                    ->state(function (Contract $record): string {
                        $unpaidSum = $record->payments->where('status', '!=', 'paid')->sum('amount');

                        return 'Rp '.number_format((float) $unpaidSum, 0, ',', '.');
                    })
                    ->weight('semibold')
                    ->color(fn (Contract $record): string => $record->payments->where('status', '!=', 'paid')->isEmpty() ? 'success' : 'gray')
                    ->description(function (Contract $record): string {
                        $remaining = $record->payments->where('status', '!=', 'paid')->count();
                        $missing = max(0, (int) $record->tenor - $record->payments->count());

                        if ($missing > 0) {
                            return "{$remaining} tagihan · {$missing} jadwal belum dibuat";
                        }

                        return $remaining > 0 ? "{$remaining} tagihan tersisa" : 'Tidak ada tagihan';
                    }),

                TextColumn::make('status')
                    ->label('Status Kontrak')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Berjalan',
                        'completed' => 'Lunas',
                        'defaulted' => 'Macet',
                        default => ucfirst($state),
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-m-arrow-path',
                        'completed' => 'heroicon-m-check-circle',
                        'defaulted' => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'info',
                        'completed' => 'success',
                        'defaulted' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Kontrak')
                    ->options([
                        'active' => 'Aktif (Berjalan)',
                        'completed' => 'Lunas (Selesai)',
                        'defaulted' => 'Macet (Defaulted)',
                    ]),

                Filter::make('has_overdue')
                    ->label('Hanya yang Ada Tunggakan (Overdue)')
                    ->query(fn (Builder $query): Builder => $query->whereHas('payments', function ($q) {
                        $q->where('status', 'overdue')
                            ->orWhere(function ($q2) {
                                $q2->where('status', 'unpaid')
                                    ->where('due_date', '<', now()->toDateString());
                            });
                    })),
            ])
            ->recordActions([
                Action::make('markAsPaid')
                    ->label('Bayar Cicilan')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->hidden(fn (Contract $record) => $record->payments->where('status', '!=', 'paid')->isEmpty())
                    ->schema(function (Contract $record) {
                        $unpaidPayments = $record->payments->where('status', '!=', 'paid')->sortBy('installment_number');
                        $options = $unpaidPayments->mapWithKeys(function ($p) {
                            $dueDate = Carbon::parse($p->due_date)->translatedFormat('d M Y');
                            $amount = 'Rp '.number_format((float) $p->amount, 0, ',', '.');

                            return [$p->id => "Cicilan Ke-{$p->installment_number} ({$amount} - Jatuh Tempo: {$dueDate})"];
                        })->toArray();

                        $firstUnpaid = $unpaidPayments->first();

                        return [
                            Select::make('payment_id')
                                ->label('Pilih Cicilan yang Dibayar')
                                ->options($options)
                                ->default($firstUnpaid?->id)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state) {
                                        $payment = Payment::find($state);
                                        if ($payment) {
                                            $set('paid_amount', $payment->amount);
                                        }
                                    }
                                }),

                            TextInput::make('paid_amount')
                                ->label('Nominal Pembayaran')
                                ->numeric()
                                ->prefix('Rp')
                                ->default($firstUnpaid?->amount)
                                ->required(),

                            DateTimePicker::make('paid_at')
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
                                'paid_at' => $data['paid_at'],
                                'status' => 'paid',
                            ]);

                            // Selesaikan kontrak hanya jika jadwal lengkap dan seluruh cicilan sudah lunas.
                            $scheduleCount = $record->payments()->count();
                            $remaining = $record->payments()->where('status', '!=', 'paid')->count();
                            if ($scheduleCount >= (int) $record->tenor && $remaining === 0) {
                                $record->update(['status' => 'completed']);
                            }

                            Notification::make()
                                ->title("Cicilan Ke-{$payment->installment_number} Berhasil Dilunasi")
                                ->success()
                                ->send();
                        }
                    }),

                Action::make('sendWaNotice')
                    ->label('Kirim WA')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('warning')
                    ->hidden(fn (Contract $record) => $record->payments->where('status', '!=', 'paid')->isEmpty())
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Pesan Tagihan WhatsApp')
                    ->modalDescription(fn (Contract $record): string => "Kirimkan pengingat tagihan cicilan ke nomor {$record->customer?->phone_number} ({$record->customer?->name})?")
                    ->action(function (Contract $record) {
                        $nextUnpaid = $record->payments->where('status', '!=', 'paid')->sortBy('installment_number')->first();

                        if (! $nextUnpaid) {
                            Notification::make()
                                ->title('Semua cicilan pada kontrak ini sudah lunas.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $customer = $record->customer;
                        $dueDate = Carbon::parse($nextUnpaid->due_date)->translatedFormat('d F Y');
                        $nominal = 'Rp '.number_format((float) $nextUnpaid->amount, 0, ',', '.');

                        $message = "Halo Bpk/Ibu *{$customer->name}*,\n\n"
                            ."Kami ingin mengingatkan bahwa angsuran cicilan HP Anda untuk No. Kontrak *{$record->contract_number}* (Cicilan Ke-{$nextUnpaid->installment_number}) sebesar *{$nominal}* akan/telah jatuh tempo pada *{$dueDate}*.\n\n"
                            ."Mohon segera melakukan pembayaran. Abaikan pesan ini jika Anda sudah melakukan pelunasan.\n\n"
                            .'Terima kasih.';

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

                Action::make('viewContract')
                    ->label('Detail Kontrak')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Contract $record): string => ContractResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'kanban' => PaymentKanban::route('/kanban'),
        ];
    }
}
