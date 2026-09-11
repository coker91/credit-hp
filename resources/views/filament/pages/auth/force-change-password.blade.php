<div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6">
    <div class="w-full max-w-md">
        {{-- Logo / Brand --}}
        <div class="flex justify-center mb-8">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-warning-500 shadow-lg">
                    <x-heroicon-o-shield-exclamation class="w-7 h-7 text-white" />
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ config('app.name') }}
                </span>
            </div>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl bg-white shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-8">

            {{-- Header --}}
            <div class="mb-6 text-center">
                <div class="flex justify-center mb-4">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-warning-100 dark:bg-warning-500/20">
                        <x-heroicon-o-lock-closed class="w-8 h-8 text-warning-600 dark:text-warning-400" />
                    </div>
                </div>
                <h1 class="text-xl font-bold text-gray-950 dark:text-white">
                    {{ $this->getHeading() }}
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->getSubheading() }}
                </p>
            </div>

            {{-- Form --}}
            <form wire:submit="save">
                {{ $this->form }}

                <div class="mt-6 flex flex-col gap-3">
                    @foreach ($this->getFormActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </form>
        </div>

        {{-- Logout link --}}
        <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
            Tidak mau ganti password sekarang?
            <a
                href="{{ filament()->getLogoutUrl() }}"
                onclick="event.preventDefault(); document.getElementById('force-logout-form').submit();"
                class="text-primary-600 hover:underline dark:text-primary-400 font-medium"
            >
                Keluar dari aplikasi
            </a>
        </div>

        <form id="force-logout-form" action="{{ filament()->getLogoutUrl() }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</div>
