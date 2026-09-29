@php
    $contract = $getRecord();
    $payments = $contract->payments->keyBy('installment_number');
    $tenor = max((int) ($contract->tenor ?? 0), $payments->count());
@endphp

@once
    <style>
        .payment-sticky {
            position: sticky !important;
            z-index: 10;
            box-sizing: border-box;
            background: #fff;
        }

        .fi-ta-header-cell.payment-sticky {
            z-index: 20;
            background: #f9fafb;
        }

        .payment-sticky-contract {
            left: 0;
            width: 9.5rem;
            min-width: 9.5rem;
            max-width: 9.5rem;
        }

        .payment-sticky-customer {
            left: 9.5rem;
            width: 13rem;
            min-width: 13rem;
            max-width: 13rem;
        }

        .payment-sticky-unit {
            left: 22.5rem;
            width: 12.5rem;
            min-width: 12.5rem;
            max-width: 12.5rem;
            box-shadow: 5px 0 10px -8px rgb(15 23 42 / 0.65);
        }

        .fi-ta-row:hover .payment-sticky {
            background: #f9fafb;
        }

        .installment-status-scroll {
            width: min(48rem, 52vw);
            max-width: 48rem;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            scrollbar-color: #94a3b8 transparent;
            scrollbar-width: thin;
            cursor: grab;
            user-select: none;
        }

        .installment-status-scroll.is-dragging {
            cursor: grabbing;
        }

        .installment-status-list {
            display: flex;
            align-items: stretch;
            gap: 0.5rem;
            min-width: max-content;
            padding-block: 0.375rem;
        }

        .installment-status-card {
            width: 7rem;
            flex: 0 0 7rem;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #fff;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
        }

        .installment-status-card.is-paid {
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        .installment-status-card.is-overdue {
            border-color: #fda4af;
            background: #fff1f2;
        }

        .installment-status-card.is-due-today {
            border-color: #fdba74;
            background: #fff7ed;
        }

        .installment-status-card.is-upcoming {
            border-color: #bae6fd;
            background: #f0f9ff;
        }

        .installment-status-card.is-missing {
            border-style: dashed;
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .installment-status-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.375rem;
            margin-bottom: 0.375rem;
        }

        .installment-status-number {
            color: #374151;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .installment-status-date,
        .installment-status-amount {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .installment-status-date {
            color: #374151;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .installment-status-amount {
            margin-top: 0.125rem;
            color: #6b7280;
            font-size: 0.65rem;
        }

        .dark .installment-status-card {
            border-color: #374151;
            background: #111827;
        }

        .dark .installment-status-card.is-paid {
            border-color: #065f46;
            background: rgb(2 44 34 / 0.75);
        }

        .dark .installment-status-card.is-overdue {
            border-color: #9f1239;
            background: rgb(76 5 25 / 0.75);
        }

        .dark .installment-status-card.is-due-today {
            border-color: #9a3412;
            background: rgb(67 20 7 / 0.75);
        }

        .dark .installment-status-card.is-upcoming {
            border-color: #075985;
            background: rgb(8 47 73 / 0.65);
        }

        .dark .installment-status-number,
        .dark .installment-status-date {
            color: #e5e7eb;
        }

        .dark .installment-status-amount {
            color: #9ca3af;
        }

        .dark .payment-sticky {
            background: #111827;
        }

        .dark .fi-ta-header-cell.payment-sticky,
        .dark .fi-ta-row:hover .payment-sticky {
            background: #1f2937;
        }

        @media (max-width: 1023px) {
            .payment-sticky {
                position: static !important;
                width: auto;
                min-width: 0;
                max-width: none;
                box-shadow: none;
            }

            .installment-status-scroll {
                width: min(44rem, calc(100vw - 4rem));
            }
        }
    </style>
@endonce

<div
    class="installment-status-scroll"
    x-data="{ dragging: false, startX: 0, initialScroll: 0 }"
    x-bind:class="{ 'is-dragging': dragging }"
    x-on:mousedown="
        dragging = true;
        startX = $event.pageX;
        initialScroll = $el.scrollLeft;
    "
    x-on:mousemove.window="
        if (! dragging) return;
        $event.preventDefault();
        $el.scrollLeft = initialScroll - ($event.pageX - startX);
    "
    x-on:mouseup.window="dragging = false"
    x-on:mouseleave="dragging = false"
    tabindex="0"
    aria-label="Geser untuk melihat seluruh rincian cicilan"
>
    <div class="installment-status-list" role="list" aria-label="Status cicilan">
    @forelse (range(1, max(1, $tenor)) as $installmentNumber)
        @php
            $payment = $payments->get($installmentNumber);
            $dueDate = $payment?->due_date ? \Carbon\Carbon::parse($payment->due_date) : null;

            if (! $payment) {
                $status = 'missing';
                $statusLabel = 'Belum dibuat';
                $statusColor = 'gray';
                $statusIcon = 'heroicon-m-minus-circle';
            } elseif ($payment->status === 'paid') {
                $status = 'paid';
                $statusLabel = 'Lunas';
                $statusColor = 'success';
                $statusIcon = 'heroicon-m-check-circle';
            } elseif ($payment->status === 'overdue' || ($dueDate?->isPast() && ! $dueDate->isToday())) {
                $status = 'overdue';
                $statusLabel = 'Terlambat';
                $statusColor = 'danger';
                $statusIcon = 'heroicon-m-exclamation-circle';
            } elseif ($dueDate?->isToday()) {
                $status = 'due-today';
                $statusLabel = 'Jatuh tempo';
                $statusColor = 'warning';
                $statusIcon = 'heroicon-m-clock';
            } else {
                $status = 'upcoming';
                $statusLabel = 'Menunggu';
                $statusColor = 'info';
                $statusIcon = 'heroicon-m-calendar-days';
            }

            $dueDateFormatted = $dueDate?->translatedFormat('d M Y') ?? 'Tanpa jadwal';
            $amountFormatted = $payment
                ? 'Rp '.number_format((float) $payment->amount, 0, ',', '.')
                : 'Nominal belum ada';
            $paidAtFormatted = $payment?->paid_at
                ? \Carbon\Carbon::parse($payment->paid_at)->translatedFormat('d M Y, H:i')
                : null;

            $tooltip = match ($status) {
                'paid' => "Cicilan ke-{$installmentNumber} lunas pada {$paidAtFormatted}",
                'overdue' => "Cicilan ke-{$installmentNumber} terlambat. Jatuh tempo {$dueDateFormatted}",
                'due-today' => "Cicilan ke-{$installmentNumber} jatuh tempo hari ini",
                'upcoming' => "Cicilan ke-{$installmentNumber} akan jatuh tempo {$dueDateFormatted}",
                default => "Jadwal cicilan ke-{$installmentNumber} belum dibuat",
            };
        @endphp

        <div
            role="listitem"
            title="{{ $tooltip }}"
            class="installment-status-card is-{{ $status }}"
        >
            <div class="installment-status-header">
                <span class="installment-status-number">
                    Ke-{{ $installmentNumber }}
                </span>

                <x-filament::badge :color="$statusColor" :icon="$statusIcon" size="sm">
                    {{ $statusLabel }}
                </x-filament::badge>
            </div>

            <div class="installment-status-date">
                {{ $dueDateFormatted }}
            </div>
            <div class="installment-status-amount">
                {{ $amountFormatted }}
            </div>
        </div>
    @empty
        <x-filament::badge color="gray" icon="heroicon-m-minus-circle">
            Belum ada jadwal cicilan
        </x-filament::badge>
    @endforelse
    </div>
</div>
