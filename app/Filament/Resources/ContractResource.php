<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\ContractResource\Pages;
use App\Models\Contract;
use App\Models\Product;
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
                                $now = Carbon::now();
                                $year2Digit = $now->format('y');
                                $month2Digit = $now->format('m');

                                $code = 'CTR' . $year2Digit . $month2Digit;

                                $monthlyCount = Contract::whereYear('created_at', $now->year)
                                    ->whereMonth('created_at', $now->month)
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
                            ->required(),
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
                            ->relationship('product', 'model_name')
                            ->label('Unit HP')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('total_price', $product->selling_price);
                                        self::updateMonthlyInstallment($get, $set);
                                    }
                                }
                            }),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Skema Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga Kontrak')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateMonthlyInstallment($get, $set)),
                        Forms\Components\Select::make('tenor_months')
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
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateMonthlyInstallment($get, $set)),
                        Forms\Components\TextInput::make('monthly_installment')
                            ->label('Angsuran per Bulan')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Aktif (Berjalan)',
                                'completed' => 'Lunas',
                                'defaulted' => 'Macet',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Helper method untuk menghitung angsuran bulanan secara live di form.
     */
    protected static function updateMonthlyInstallment(Get $get, Set $set): void
    {
        $totalPrice = (float) ($get('total_price') ?? 0);
        $dp = (float) ($get('down_payment') ?? 0);
        $tenor = (int) ($get('tenor_months') ?? 1);

        if ($tenor > 0) {
            $monthly = max(0, ($totalPrice - $dp) / $tenor);
            $set('monthly_installment', round($monthly, 2));
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
                Tables\Columns\TextColumn::make('product.model_name')
                    ->label('HP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_installment')
                    ->label('Cicilan/Bln')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('tenor_months')
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
