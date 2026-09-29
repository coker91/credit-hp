<x-filament-panels::page>
    @php
        $canUpdatePayments = auth()->user()?->can('update_payment') ?? false;
    @endphp

    @once
        <style>
            .payment-kanban-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 1rem;
                padding: 1rem;
                border: 1px solid #e5e7eb;
                border-radius: 0.875rem;
                background: #fff;
            }

            .payment-kanban-search {
                width: min(100%, 26rem);
            }

            .payment-kanban-hint {
                max-width: 34rem;
                color: #6b7280;
                font-size: 0.8rem;
                line-height: 1.4;
                text-align: right;
            }

            .payment-kanban-board {
                display: grid;
                grid-template-columns: repeat(4, minmax(17rem, 1fr));
                align-items: start;
                gap: 1rem;
                overflow-x: auto;
                padding: 0.125rem 0.125rem 1rem;
            }

            .payment-kanban-column {
                min-height: 31rem;
                padding: 0.75rem;
                border: 1px solid #e5e7eb;
                border-radius: 1rem;
                background: #f9fafb;
                transition: border-color 150ms ease, background-color 150ms ease, box-shadow 150ms ease;
            }

            .payment-kanban-column.is-drop-target {
                border-color: #10b981;
                background: #ecfdf5;
                box-shadow: 0 0 0 3px rgb(16 185 129 / 0.15);
            }

            .payment-kanban-column-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.75rem;
                margin-bottom: 0.75rem;
                padding: 0.25rem;
            }

            .payment-kanban-column-title {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: #111827;
                font-size: 0.9rem;
                font-weight: 700;
            }

            .payment-kanban-column-description {
                margin-top: 0.2rem;
                color: #6b7280;
                font-size: 0.7rem;
            }

            .payment-kanban-cards {
                display: flex;
                flex-direction: column;
                gap: 0.65rem;
            }

            .payment-kanban-card {
                padding: 0.8rem;
                border: 1px solid #e5e7eb;
                border-left-width: 4px;
                border-radius: 0.75rem;
                background: #fff;
                box-shadow: 0 1px 2px rgb(0 0 0 / 0.04);
                transition: transform 120ms ease, box-shadow 120ms ease, opacity 120ms ease;
            }

            .payment-kanban-card.is-draggable {
                cursor: grab;
            }

            .payment-kanban-card.is-draggable:hover {
                transform: translateY(-1px);
                box-shadow: 0 5px 12px rgb(0 0 0 / 0.08);
            }

            .payment-kanban-card.is-draggable:active {
                cursor: grabbing;
                opacity: 0.7;
            }

            .payment-kanban-card.is-upcoming {
                border-left-color: #0ea5e9;
            }

            .payment-kanban-card.is-due_today {
                border-left-color: #f97316;
            }

            .payment-kanban-card.is-overdue {
                border-left-color: #f43f5e;
            }

            .payment-kanban-card.is-paid {
                border-left-color: #10b981;
            }

            .payment-kanban-card-top {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .payment-kanban-customer {
                color: #111827;
                font-size: 0.875rem;
                font-weight: 700;
                line-height: 1.25;
            }

            .payment-kanban-contract {
                margin-top: 0.2rem;
                color: #6b7280;
                font-size: 0.7rem;
            }

            .payment-kanban-meta {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
                margin-top: 0.75rem;
                padding-top: 0.65rem;
                border-top: 1px solid #f3f4f6;
            }

            .payment-kanban-meta-label {
                color: #9ca3af;
                font-size: 0.62rem;
                font-weight: 600;
                letter-spacing: 0.03em;
                text-transform: uppercase;
            }

            .payment-kanban-meta-value {
                margin-top: 0.125rem;
                color: #374151;
                font-size: 0.75rem;
                font-weight: 600;
            }

            .payment-kanban-contact {
                margin-top: 0.6rem;
                color: #6b7280;
                font-size: 0.7rem;
            }

            .payment-kanban-actions {
                margin-top: 0.7rem;
            }

            .payment-kanban-empty {
                display: grid;
                min-height: 8rem;
                place-items: center;
                padding: 1rem;
                border: 1px dashed #d1d5db;
                border-radius: 0.75rem;
                color: #9ca3af;
                font-size: 0.75rem;
                text-align: center;
            }

            .payment-kanban-saving {
                position: fixed;
                z-index: 50;
                right: 1.5rem;
                bottom: 1.5rem;
                padding: 0.65rem 0.9rem;
                border-radius: 0.75rem;
                background: #111827;
                color: #fff;
                font-size: 0.75rem;
                font-weight: 600;
                box-shadow: 0 10px 25px rgb(0 0 0 / 0.2);
            }

            .dark .payment-kanban-toolbar,
            .dark .payment-kanban-card {
                border-color: #374151;
                background: #111827;
            }

            .dark .payment-kanban-column {
                border-color: #374151;
                background: #0b1120;
            }

            .dark .payment-kanban-column.is-drop-target {
                border-color: #10b981;
                background: rgb(2 44 34 / 0.65);
            }

            .dark .payment-kanban-column-title,
            .dark .payment-kanban-customer {
                color: #f3f4f6;
            }

            .dark .payment-kanban-column-description,
            .dark .payment-kanban-contract,
            .dark .payment-kanban-contact,
            .dark .payment-kanban-hint {
                color: #9ca3af;
            }

            .dark .payment-kanban-meta {
                border-top-color: #1f2937;
            }

            .dark .payment-kanban-meta-value {
                color: #d1d5db;
            }

            .dark .payment-kanban-empty {
                border-color: #4b5563;
                color: #6b7280;
            }

            @media (max-width: 768px) {
                .payment-kanban-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .payment-kanban-search {
                    width: 100%;
                }

                .payment-kanban-hint {
                    text-align: left;
                }
            }
        </style>
    @endonce

    <div
        x-data="{ draggedPaymentId: null }"
    >
        <div class="payment-kanban-toolbar">
            <div class="payment-kanban-search">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Cari customer, nomor kontrak, HP, atau telepon..."
                    />
                </x-filament::input.wrapper>
            </div>

            <p class="payment-kanban-hint">
                @if ($canUpdatePayments)
                    Seret kartu dari kolom mana pun ke <strong>Lunas</strong>. Konfirmasi akan muncul sebelum pembayaran disimpan.
                @else
                    Anda memiliki akses lihat saja. Izin update pembayaran diperlukan untuk memindahkan kartu.
                @endif
            </p>
        </div>

        <div class="payment-kanban-board">
            @foreach ($this->board as $columnKey => $column)
                <section
                    class="payment-kanban-column"
                    @if ($columnKey === 'paid' && $canUpdatePayments)
                        x-bind:class="{ 'is-drop-target': draggedPaymentId !== null }"
                        x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                        x-on:drop.prevent="
                            const paymentId = Number($event.dataTransfer.getData('text/plain') || draggedPaymentId);
                            if (paymentId && window.confirm('Tandai cicilan ini sebagai lunas?')) {
                                $wire.markAsPaid(paymentId);
                            }
                            draggedPaymentId = null;
                        "
                    @endif
                >
                    <header class="payment-kanban-column-header">
                        <div>
                            <div class="payment-kanban-column-title">
                                <x-filament::icon
                                    :icon="$column['icon']"
                                    style="width: 1.15rem; height: 1.15rem;"
                                />
                                {{ $column['label'] }}
                            </div>
                            <p class="payment-kanban-column-description">
                                {{ $column['description'] }}
                            </p>
                        </div>

                        <x-filament::badge :color="$column['color']">
                            {{ $column['payments']->count() }}
                        </x-filament::badge>
                    </header>

                    <div class="payment-kanban-cards">
                        @forelse ($column['payments'] as $payment)
                            @php
                                $isDraggable = $columnKey !== 'paid' && $canUpdatePayments;
                                $customer = $payment->contract?->customer;
                                $product = $payment->contract?->product;
                            @endphp

                            <article
                                wire:key="payment-kanban-{{ $payment->id }}"
                                class="payment-kanban-card is-{{ $columnKey }} {{ $isDraggable ? 'is-draggable' : '' }}"
                                draggable="{{ $isDraggable ? 'true' : 'false' }}"
                                @if ($isDraggable)
                                    x-on:dragstart="
                                        draggedPaymentId = {{ $payment->id }};
                                        $event.dataTransfer.effectAllowed = 'move';
                                        $event.dataTransfer.setData('text/plain', '{{ $payment->id }}');
                                    "
                                    x-on:dragend="draggedPaymentId = null"
                                @endif
                            >
                                <div class="payment-kanban-card-top">
                                    <div>
                                        <div class="payment-kanban-customer">
                                            {{ $customer?->name ?? 'Customer tidak tersedia' }}
                                        </div>
                                        <div class="payment-kanban-contract">
                                            {{ $payment->contract?->contract_number ?? '-' }}
                                            · {{ $product?->brand ?? 'Unit tidak tersedia' }}
                                        </div>
                                    </div>

                                    <x-filament::badge :color="$column['color']" size="sm">
                                        Ke-{{ $payment->installment_number }}
                                    </x-filament::badge>
                                </div>

                                <div class="payment-kanban-meta">
                                    <div>
                                        <div class="payment-kanban-meta-label">
                                            {{ $columnKey === 'paid' ? 'Dibayar' : 'Jatuh tempo' }}
                                        </div>
                                        <div class="payment-kanban-meta-value">
                                            {{ ($columnKey === 'paid' ? $payment->paid_at : $payment->due_date)?->translatedFormat('d M Y') ?? '-' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="payment-kanban-meta-label">Nominal</div>
                                        <div class="payment-kanban-meta-value">
                                            Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="payment-kanban-contact">
                                    {{ $customer?->phone_number ?? 'Nomor telepon belum tersedia' }}
                                </div>

                                @if ($isDraggable)
                                    <div class="payment-kanban-actions" draggable="false">
                                        <x-filament::button
                                            size="xs"
                                            color="success"
                                            icon="heroicon-m-check-circle"
                                            wire:click="markAsPaid({{ $payment->id }})"
                                            wire:confirm="Tandai cicilan ke-{{ $payment->installment_number }} milik {{ $customer?->name }} sebagai lunas?"
                                            wire:loading.attr="disabled"
                                            wire:target="markAsPaid({{ $payment->id }})"
                                        >
                                            Catat lunas
                                        </x-filament::button>
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="payment-kanban-empty">
                                Tidak ada cicilan pada kolom ini.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <div
            class="payment-kanban-saving"
            wire:loading.flex
            wire:target="markAsPaid"
        >
            Menyimpan pembayaran...
        </div>
    </div>
</x-filament-panels::page>
