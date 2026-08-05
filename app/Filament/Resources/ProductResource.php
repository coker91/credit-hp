<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Katalog HP';

    protected static ?string $pluralModelLabel = 'Katalog HP';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\Section::make('Detail Produk & Kalkulasi Otomatis')
                ->schema([
                    Forms\Components\TextInput::make('brand')
                        ->label('Merk/Brand')
                        ->placeholder('e.g. Samsung, Apple')
                        ->required(),

                    Forms\Components\TextInput::make('model_name')
                        ->label('Model / Tipe HP')
                        ->placeholder('e.g. Galaxy A55 8/256GB')
                        ->required(),

                    // 1. Input Harga Modal (Beli)
                    Forms\Components\TextInput::make('cost_price')
                        ->label('Harga Modal HP')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true)
                        ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 0, '', '') : null)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            if (!$state) return;

                            $costPrice = (float) $state;

                            // Rumus: (Harga Modal * 130%)
                            $sellingPrice = $costPrice * 1.3;

                            // Set nilai ke input selling_price otomatis
                            $set('selling_price', round($sellingPrice, 2));
                        })
                        ->required(),

                    // 2. Output Harga Jual HP (Modal + 30%)
                    Forms\Components\TextInput::make('selling_price')
                        ->label('Harga Jual HP (Modal + 30%)')
                        ->numeric()
                        ->prefix('Rp')
                        ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 0, '', '') : null)
                        ->required()
                        ->helperText('Otomatis dihitung dari Harga Modal + Profit 30%'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model_name')
                    ->label('Model HP')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Harga Modal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('margin')
                    ->label('Margin Acuan')
                    ->money('IDR')
                    ->state(fn (Product $record) => $record->selling_price - $record->cost_price)
                    ->color('success'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}