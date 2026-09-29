<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Daftar Barang';

    protected static ?string $pluralModelLabel = 'Daftar Barang';

    public static function updateInstallmentReference(Get $get, Set $set): void
    {
        $current = $get('installment_reference');

        if (! empty($current)) {
            return;
        }

        $sellingPrice = (float) preg_replace('/[^\d]/', '', (string) ($get('selling_price') ?? 0));
        $tenor = (int) ($get('default_tenor') ?? 6);

        if ($sellingPrice > 0 && $tenor > 0) {
            $installment = round($sellingPrice / $tenor);
            $set('installment_reference', $installment);
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Produk & Kalkulasi Otomatis')
                    ->schema([
                        TextInput::make('brand')
                            ->label('Nama / Tipe HP')
                            ->placeholder('e.g. Samsung Galaxy A55 8/256GB')
                            ->required(),
                        TextInput::make('cost_price')
                            ->label('Harga Modal HP')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                            ->live(onBlur: true)
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 0, '', '') : null)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if (! $state) {
                                    return;
                                }

                                $costPrice = (float) $state;

                                $sellingPrice = $costPrice * 1.3;

                                $set('selling_price', round($sellingPrice, 2));
                            })
                            ->required(),
                        TextInput::make('selling_price')
                            ->label('Harga Jual HP (Modal + 30%)')
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)  // 🟢 tambahan
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 0, '', '') : null)
                            ->required()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                            ->helperText('Otomatis dihitung dari Harga Modal + Profit 30%. Ini yang jadi dasar cicilan customer.')
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateInstallmentReference($get, $set)),  // 🟢 tambahan
                    ])
                    ->columns(2),
                Section::make('Data Internal (Rahasia — Tidak Terlihat Customer)')
                    ->description('Isi jika ada modal sebenarnya yang berbeda dari Harga Modal resmi (misal: dapat voucher/diskon). Tidak memengaruhi harga jual ke customer.')
                    ->schema([
                        TextInput::make('actual_cost_price')
                            ->label('Modal Sebenarnya (Net)')
                            ->numeric()
                            ->prefix('Rp')
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                            ->live(onBlur: true)
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 0, '', '') : null)
                            ->helperText('Kosongkan jika modal sama dengan Harga Modal resmi di atas.'),
                        Select::make('default_tenor')
                            ->label('Tenor Acuan')
                            ->options([
                                3 => '3 Bulan',
                                6 => '6 Bulan',
                                7 => '7 Bulan',
                                12 => '12 Bulan',
                            ])
                            ->default(6)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::updateInstallmentReference($get, $set))
                            ->helperText('Tenor yang dipakai untuk hitung Cicilan/Bln referensi di bawah.'),
                        TextInput::make('installment_reference')
                            ->label('Cicilan/Bln (Referensi)')
                            ->numeric()
                            ->prefix('Rp')
                            ->live()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 0, '', '') : null)
                            ->helperText('Otomatis dihitung dari Harga Jual ÷ Tenor Acuan. Bisa diedit manual jika perlu.'),
                        Placeholder::make('real_margin_preview')
                            ->label('Rincian Margin Sebenarnya')
                            ->content(function (Get $get) {
                                $costPrice = (float) preg_replace('/[^\d]/', '', (string) ($get('cost_price') ?? 0));
                                $actualCostRaw = preg_replace('/[^\d]/', '', (string) ($get('actual_cost_price') ?? ''));
                                $installmentRef = (float) preg_replace('/[^\d]/', '', (string) ($get('installment_reference') ?? 0));
                                $tenor = (int) ($get('default_tenor') ?? 6);

                                $realCost = $actualCostRaw !== '' ? (float) $actualCostRaw : $costPrice;

                                if ($tenor <= 0) {
                                    return '-';
                                }

                                $modalPerBulan = round($realCost / $tenor);

                                $marginPerBulan = round($installmentRef - $modalPerBulan);
                                $marginTotal = round($marginPerBulan * $tenor);

                                return sprintf(
                                    'Margin Total (estimasi %d Bln): Rp %s  |  Modal/Bln: Rp %s  —  Margin/Bln: Rp %s',
                                    $tenor,
                                    number_format($marginTotal, 0, ',', '.'),
                                    number_format($modalPerBulan, 0, ',', '.'),
                                    number_format($marginPerBulan, 0, ',', '.')
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand')
                    ->label('Nama / Tipe HP')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('cost_price')
                    ->label('Harga Modal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('margin')
                    ->label('Margin Acuan')
                    ->money('IDR')
                    ->state(fn (Product $record) => $record->selling_price - $record->cost_price)
                    ->color('success'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
