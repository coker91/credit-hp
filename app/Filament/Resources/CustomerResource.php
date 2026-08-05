<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Nasabah';

    protected static ?string $pluralModelLabel = 'Data Nasabah';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Profil Nasabah')
                    ->schema([
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK (KTP)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->length(16)
                            ->numeric(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('No. WhatsApp')
                            ->placeholder('081234567890')
                            ->required()
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Domisili')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Nasabah')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('No. WhatsApp')
                    ->icon('heroicon-m-phone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('contracts_count')
                    ->label('Total Kontrak')
                    ->counts('contracts')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}