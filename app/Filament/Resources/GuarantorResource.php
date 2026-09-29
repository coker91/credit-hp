<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuarantorResource\Pages\CreateGuarantor;
use App\Filament\Resources\GuarantorResource\Pages\EditGuarantor;
use App\Filament\Resources\GuarantorResource\Pages\ListGuarantors;
use App\Models\Guarantor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GuarantorResource extends Resource
{
    protected static ?string $model = Guarantor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Data Penjamin';

    protected static ?string $modelLabel = 'Penjamin';

    protected static ?string $pluralModelLabel = 'Data Penjamin';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Relasi Nasabah')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Nasabah yang Dijamin')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} (NIK: {$record->nik})")
                            ->columnSpanFull(),
                    ]),

                Section::make('Data Identitas Penjamin')
                    ->schema([
                        TextInput::make('nik')
                            ->label('NIK (KTP) Penjamin')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->length(16)
                            ->numeric(),

                        TextInput::make('name')
                            ->label('Nama Lengkap Penjamin')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone_number')
                            ->label('No. WhatsApp Penjamin')
                            ->placeholder('081234567890')
                            ->required()
                            ->tel()
                            ->maxLength(20),

                        Select::make('relationship')
                            ->label('Hubungan dengan Nasabah')
                            ->options([
                                'Orang Tua' => 'Orang Tua',
                                'Anak' => 'Anak',
                                'Saudara Kandung' => 'Saudara Kandung',
                                'Suami/Istri' => 'Suami/Istri',
                                'Kerabat' => 'Kerabat',
                                'Teman' => 'Teman',
                                'Rekan Kerja' => 'Rekan Kerja',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->searchable()
                            ->required(),

                        Textarea::make('address')
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
                TextColumn::make('customer.name')
                    ->label('Nama Nasabah')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => 'NIK Nasabah: '.$record->customer?->nik),

                TextColumn::make('name')
                    ->label('Nama Penjamin')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('nik')
                    ->label('NIK Penjamin')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone_number')
                    ->label('No. WA Penjamin')
                    ->icon('heroicon-m-phone')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('relationship')
                    ->label('Hubungan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Suami/Istri' => 'success',
                        'Orang Tua', 'Anak', 'Saudara Kandung' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->address)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('relationship')
                    ->label('Hubungan')
                    ->options([
                        'Orang Tua' => 'Orang Tua',
                        'Anak' => 'Anak',
                        'Saudara Kandung' => 'Saudara Kandung',
                        'Suami/Istri' => 'Suami/Istri',
                        'Kerabat' => 'Kerabat',
                        'Teman' => 'Teman',
                        'Rekan Kerja' => 'Rekan Kerja',
                        'Lainnya' => 'Lainnya',
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

    public static function getPages(): array
    {
        return [
            'index' => ListGuarantors::route('/'),
            'create' => CreateGuarantor::route('/create'),
            'edit' => EditGuarantor::route('/{record}/edit'),
        ];
    }
}
