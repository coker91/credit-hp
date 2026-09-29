<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages\CreateContract;
use App\Filament\Resources\ContractResource\Pages\EditContract;
use App\Filament\Resources\ContractResource\Pages\ListContracts;
use App\Filament\Resources\ContractResource\RelationManagers\PaymentsRelationManager;
use App\Models\Contract;
use App\Models\Product;
use App\Services\InstallmentService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Kontrak Cicilan';

    protected static ?string $pluralModelLabel = 'Kontrak Cicilan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Transaksi')
                    ->schema([
                        TextInput::make('contract_number')
                            ->label('No. Kontrak')
                            ->default(function () {
                                $now = \Carbon\Carbon::now();
                                $year2Digit = $now->format('y');
                                $month2Digit = $now->format('m');
                                $code = 'CTR'.$year2Digit.$month2Digit;

                                $monthlyCount = Contract::whereYear('start_date', $now->year)
                                    ->whereMonth('start_date', $now->month)
                                    ->count() + 1;

                                $sequence = str_pad((string) $monthlyCount, 3, '0', STR_PAD_LEFT);

                                return "{$sequence}/{$code}/{$sequence}";
                            })
                            ->required()
                            ->readOnly()
                            ->unique(ignoreRecord: true),
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $date = \Carbon\Carbon::parse($state);
                                    $year2Digit = $date->format('y');
                                    $month2Digit = $date->format('m');
                                    $code = 'CTR'.$year2Digit.$month2Digit;

                                    $monthlyCount = Contract::whereYear('start_date', $date->year)
                                        ->whereMonth('start_date', $date->month)
                                        ->count() + 1;

                                    $sequence = str_pad((string) $monthlyCount, 3, '0', STR_PAD_LEFT);

                                    $set('contract_number', "{$sequence}/{$code}/{$sequence}");
                                }
                            }),
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->label('Customer')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('nik')->required()->maxLength(16),
                                TextInput::make('name')->required(),
                                TextInput::make('phone_number')->label('No. WhatsApp')->required(),
                                Textarea::make('address')->required(),
                            ]),
                        Select::make('product_id')
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
                Section::make('Skema Pembayaran')
                    ->schema([
                        TextInput::make('total_price')
                            ->label('Total Harga Kontrak')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateMonthlyInstallment($get, $set)),
                        Select::make('tenor')
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
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::syncInstallmentFromProduct($get, $set)),
                        TextInput::make('installment')
                            ->label('Angsuran per Bulan (Ditagih ke Customer)')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                            ->numeric()
                            ->prefix('Rp')
                            ->dehydrated()
                            ->minValue(0)
                            ->required(),
                        Select::make('status')
                            ->options([
                                'active' => 'Aktif (Berjalan)',
                                'completed' => 'Lunas',
                                'defaulted' => 'Macet',
                            ])
                            ->default('active')
                            ->required(),
                        Hidden::make('actual_cost_price'),
                        Placeholder::make('real_profit_preview')
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

        if (! $productId || $tenor <= 0) {
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
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

        if (! empty($currentInstallment)) {
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
                TextColumn::make('contract_number')
                    ->label('No. Kontrak')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer.name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.brand')
                    ->label('HP')
                    ->searchable(),
                TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('installment')
                    ->label('Cicilan/Bln')
                    ->money('IDR'),
                TextColumn::make('modal_perbulan')
                    ->label('Modal/Bln')
                    ->state(function (Contract $record) {
                        if ($record->tenor <= 0) {
                            return 0;
                        }

                        return round(((float) $record->actual_cost_price) / $record->tenor);
                    })
                    ->money('IDR'),
                TextColumn::make('laba_perbulan')
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
                TextColumn::make('tenor')
                    ->label('Tenor')
                    ->formatStateUsing(fn ($state) => "{$state} Bln")
                    ->alignCenter(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'primary',
                        'completed' => 'success',
                        'defaulted' => 'danger',
                    }),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'completed' => 'Lunas',
                        'defaulted' => 'Macet',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListContracts::route('/'),
            'create' => CreateContract::route('/create'),
            'edit' => EditContract::route('/{record}/edit'),
        ];
    }
}
