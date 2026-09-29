<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->icon('heroicon-m-rectangle-stack')
                ->badge(fn (): int => Contract::count())
                ->badgeColor('gray'),
            'overdue' => Tab::make('Perlu Ditagih')
                ->icon('heroicon-m-exclamation-circle')
                ->badge(fn (): int => Contract::whereHas('payments', fn (Builder $payments): Builder => $payments
                    ->where('status', 'overdue')
                    ->orWhere(fn (Builder $unpaid): Builder => $unpaid
                        ->where('status', 'unpaid')
                        ->whereDate('due_date', '<', today())))->count())
                ->badgeColor('danger')
                ->query(fn (Builder $query): Builder => $query->whereHas('payments', fn (Builder $payments): Builder => $payments
                    ->where('status', 'overdue')
                    ->orWhere(fn (Builder $unpaid): Builder => $unpaid
                        ->where('status', 'unpaid')
                        ->whereDate('due_date', '<', today())))),
            'active' => Tab::make('Berjalan')
                ->icon('heroicon-m-arrow-path')
                ->badge(fn (): int => Contract::where('status', 'active')->count())
                ->badgeColor('info')
                ->query(fn (Builder $query): Builder => $query->where('status', 'active')),
            'completed' => Tab::make('Sudah Lunas')
                ->icon('heroicon-m-check-circle')
                ->badge(fn (): int => Contract::where('status', 'completed')->count())
                ->badgeColor('success')
                ->query(fn (Builder $query): Builder => $query->where('status', 'completed')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Mode Kanban')
                ->icon('heroicon-m-view-columns')
                ->color('primary')
                ->url(PaymentResource::getUrl('kanban')),
        ];
    }
}
