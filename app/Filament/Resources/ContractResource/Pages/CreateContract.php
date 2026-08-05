<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Services\InstallmentService;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $installmentService = app(InstallmentService::class);
        
        $data['monthly_installment'] = $installmentService->calculateMonthlyInstallment(
            (float) $data['total_price'],
            (float) ($data['down_payment'] ?? 0),
            (int) $data['tenor_months']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $installmentService = app(InstallmentService::class);
        $installmentService->generatePaymentSchedules($this->record);
    }
}