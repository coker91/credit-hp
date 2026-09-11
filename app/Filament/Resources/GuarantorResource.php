<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuarantorResource\Pages;
use App\Models\Guarantor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuarantorResource extends Resource
{
    protected static ?string $model = Guarantor::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Data Penjamin';

    protected static ?string $modelLabel = 'Penjamin';

    protected static ?string $pluralModelLabel = 'Data Penjamin';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Relasi Nasabah')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Nasabah yang Dijamin')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} (NIK: {$record->nik})")
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Data Identitas Penjamin')
                    ->schema([
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK (KTP) Penjamin')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->length(16)
                            ->numeric(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap Penjamin')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('No. WhatsApp Penjamin')
                            ->placeholder('081234567890')
                            ->required()
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Select::make('relationship')
                            ->label('Hubungan dengan Nasabah')
                            ->options([
                                'Orang Tua'       => 'Orang Tua',
                                'Anak'            => 'Anak',
                                'Saudara Kandung' => 'Saudara Kandung',
                                'Suami/Istri'     => 'Suami/Istri',
                                'Kerabat'         => 'Kerabat',
                                'Teman'           => 'Teman',
                                'Rekan Kerja'     => 'Rekan Kerja',
                                'Lainnya'         => 'Lainnya',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Domisili Penjamin')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Nama Nasabah')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => 'NIK Nasabah: ' . $record->customer?->nik),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Penjamin')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK Penjamin')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('No. WA Penjamin')
                    ->icon('heroicon-m-phone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('relationship')
                    ->label('Hubungan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Suami/Istri'     => 'success',
                        'Orang Tua', 'Anak', 'Saudara Kandung' => 'primary',
                        default           => 'gray',
                    }),

                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->address)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('relationship')
                    ->label('Hubungan')
                    ->options([
                        'Orang Tua'       => 'Orang Tua',
                        'Anak'            => 'Anak',
                        'Saudara Kandung' => 'Saudara Kandung',
                        'Suami/Istri'     => 'Suami/Istri',
                        'Kerabat'         => 'Kerabat',
                        'Teman'           => 'Teman',
                        'Rekan Kerja'     => 'Rekan Kerja',
                        'Lainnya'         => 'Lainnya',
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGuarantors::route('/'),
            'create' => Pages\CreateGuarantor::route('/create'),
            'edit'   => Pages\EditGuarantor::route('/{record}/edit'),
        ];
    }
}
