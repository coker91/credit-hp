<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ForceChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.auth.force-change-password';

    protected static ?string $slug = 'auth/force-change-password';

    // Gunakan layout simple (tanpa sidebar) seperti halaman login
    protected static string $layout = 'filament-panels::components.layout.simple';

    // Sembunyikan dari navigasi sidebar
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Ganti Password';
    }

    public function getHeading(): string
    {
        return 'Password Anda Sudah Kedaluwarsa';
    }

    public function getSubheading(): ?string
    {
        return 'Demi keamanan, password harus diganti setiap 3 bulan. Silakan buat password baru untuk melanjutkan.';
    }

    public function mount(): void
    {
        $user = Auth::user();

        // Jika password belum expired, redirect ke dashboard
        if ($user && ! $user->isPasswordExpired()) {
            $this->redirect(filament()->getUrl());

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label('Password Saat Ini')
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword()
                    ->extraInputAttributes(['tabindex' => 1]),

                TextInput::make('new_password')
                    ->label('Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::min(8)->mixedCase()->numbers())
                    ->different('current_password')
                    ->extraInputAttributes(['tabindex' => 2]),

                TextInput::make('new_password_confirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('new_password')
                    ->extraInputAttributes(['tabindex' => 3]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Password Baru')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-lock-closed')
                ->extraAttributes(['tabindex' => 4, 'class' => 'w-full']),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = Auth::user();

        $user->update([
            'password' => $data['new_password'],
            'password_changed_at' => Carbon::now(),
        ]);

        Notification::make()
            ->title('Password berhasil diperbarui!')
            ->body('Anda akan diarahkan ke dashboard.')
            ->success()
            ->send();

        $this->redirect(filament()->getUrl());
    }
}
