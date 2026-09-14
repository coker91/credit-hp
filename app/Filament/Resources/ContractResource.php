<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\ContractResource\Pages;
use App\Models\Contract;
use App\Models\Product;
use App\Services\InstallmentService;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Carbon;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Kontrak Cicilan';

    protected static ?string $pluralModelLabel = 'Kontrak Cicilan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\TextInput::make('contract_number')
                            ->label('No. Kontrak')
                            ->default(function () {
                                $now = \Carbon\Carbon::now();
                                $year2Digit = $now->format('y');
                                $month2Digit = $now->format('m');
                                $code = 'CTR' . $year2Digit . $month2Digit;

                                $monthlyCount = \App\Models\Contract::whereYear('start_date', $now->year)
                                    ->whereMonth('start_date', $now->month)
                                    ->count() + 1;

                                $sequence = str_pad((string) $monthlyCount, 3, '0', STR_PAD_LEFT);

                                return "{$sequence}/{$code}/{$sequence}";
                            })
                            ->required()
                            ->readOnly()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $date = \Carbon\Carbon::parse($state);
                                    $year2Digit = $date->format('y');
                                    $month2Digit = $date->format('m');
                                    $code = 'CTR' . $year2Digit . $month2Digit;

                                    $monthlyCount = \App\Models\Contract::whereYear('start_date', $date->year)
                                        ->whereMonth('start_date', $date->month)
                                        ->count() + 1;

                                    $sequence = str_pad((string) $monthlyCount, 3, '0', STR_PAD_LEFT);

                                    $set('contract_number', "{$sequence}/{$code}/{$sequence}");
                                }
                            }),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->label('Customer')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nik')->required()->maxLength(16),
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('phone_number')->label('No. WhatsApp')->required(),
                                Forms\Components\Textarea::make('address')->required(),
                            ]),
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'brand')
                            ->label('Unit HP')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('total_price', (int) round((float) $product->selling_price));

                                        $realCost = $product->actual_cost_price !== null
                                            ? (float) $product->actual_cost_price
                                            : (float) $product->cost_price;

                                        $set('actual_cost_price', $realCost);

                                        self::syncInstallmentFromProduct($get, $set);
                                    }
                                }
                            }),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Skema Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga Kontrak')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateMonthlyInstallment($get, $set)),
                        Forms\Components\Select::make('tenor')
                            ->label('Tenor (Bulan)')
                            ->options([
                                3 => '3 Bulan',
                                6 => '6 Bulan',
                                9 => '9 Bulan',
                                12 => '12 Bulan',
                            ])
                            ->default(6)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::syncInstallmentFromProduct($get, $set)),
                        Forms\Components\TextInput::make('installment')
                            ->label('Angsuran per Bulan (Ditagih ke Customer)')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                            ->numeric()
                            ->prefix('Rp')
                            ->dehydrated()
                            ->minValue(0)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Aktif (Berjalan)',
                                'completed' => 'Lunas',
                                'defaulted' => 'Macet',
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\Hidden::make('actual_cost_price'),
                        Forms\Components\Placeholder::make('real_profit_preview')
                            ->label('Rincian Profit Internal / Bulan')
                            ->content(function (Get $get) {
                                $installment = (float) preg_replace('/[^\d]/', '', (string) ($get('installment') ?? 0));
                                $actualCostTotal = (float) ($get('actual_cost_price') ?? 0);
                                $tenor = (int) ($get('tenor') ?? 0);

                                if ($tenor <= 0) {
                                    return '-';
                                }

                                $modalPerBulan = round($actualCostTotal / $tenor);
                                $labaPerBulan = round($installment - $modalPerBulan);

                                return sprintf(
                                    'Cicilan/Bln: Rp %s  —  Modal/Bln: Rp %s  —  Laba/Bln: Rp %s',
                                    number_format($installment, 0, ',', '.'),
                                    number_format($modalPerBulan, 0, ',', '.'),
                                    number_format($labaPerBulan, 0, ',', '.')
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Sinkronkan `installment` dari `installment_reference` milik Product,
     * TAPI hanya kalau tenor kontrak sama persis dengan default_tenor produk.
     * Kalau tenor beda, hitung ulang pakai InstallmentService supaya tetap akurat.
     */
    public static function syncInstallmentFromProduct(Get $get, Set $set): void
    {
        $productId = $get('product_id');
        $tenor = (int) ($get('tenor') ?? 0);

        if (!$productId || $tenor <= 0) {
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        if (
            $product->installment_reference !== null
            && (int) $product->default_tenor === $tenor
        ) {
            $set('installment', (int) round((float) $product->installment_reference));
        } else {
            $set('installment', null);
            self::updateMonthlyInstallment($get, $set);
        }
    }

    public static function updateMonthlyInstallment(Get $get, Set $set): void
    {
        $currentInstallment = $get('installment');

        if (!empty($currentInstallment)) {
            return;
        }

        $totalPrice = (float) str_replace(',', '', (string) ($get('total_price') ?? 0));
        $downPayment = (float) str_replace(',', '', (string) ($get('down_payment') ?? 0));
        $tenor = (int) $get('tenor');

        if ($totalPrice > 0 && $tenor > 0) {
            $service = app(InstallmentService::class);
            $installment = $service->calculateMonthlyInstallment($totalPrice, $downPayment, $tenor);

            $cleanInstallment = (int) round($installment);

            $set('installment', $cleanInstallment);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('No. Kontrak')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.brand')
                    ->label('HP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('installment')
                    ->label('Cicilan/Bln')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('modal_perbulan')
                    ->label('Modal/Bln')
                    ->state(function (Contract $record) {
                        if ($record->tenor <= 0) {
                            return 0;
                        }
                        return round(((float) $record->actual_cost_price) / $record->tenor);
                    })
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('laba_perbulan')
                    ->label('Laba/Bln')
                    ->state(function (Contract $record) {
                        if ($record->tenor <= 0) {
                            return 0;
                        }
                        $modalPerBulan = round(((float) $record->actual_cost_price) / $record->tenor);
                        return round((float) $record->installment - $modalPerBulan);
                    })
                    ->money('IDR')
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('tenor')
                    ->label('Tenor')
                    ->formatStateUsing(fn($state) => "{$state} Bln")
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'primary',
                        'completed' => 'success',
                        'defaulted' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'completed' => 'Lunas',
                        'defaulted' => 'Macet',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}