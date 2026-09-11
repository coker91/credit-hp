@php
    $contract = $getRecord();
    $payments = $contract->payments->sortBy('installment_number');
    $tenor = max($contract->tenor ?? 6, $payments->count());
@endphp

<div class="flex flex-wrap items-center gap-1.5 py-1">
    @if($payments->isEmpty())
        <span class="text-xs text-gray-400 italic">Belum ada jadwal cicilan</span>
    @else
        @foreach($payments as $payment)
            @php
                $isPaid = $payment->status === 'paid';
                $isOverdue = $payment->status === 'overdue' || (!$isPaid && \Carbon\Carbon::parse($payment->due_date)->isPast() && !\Carbon\Carbon::parse($payment->due_date)->isToday());
                
                $dueDateFormatted = \Carbon\Carbon::parse($payment->due_date)->translatedFormat('d M Y');
                $amountFormatted = 'Rp ' . number_format((float) $payment->amount, 0, ',', '.');
                $paidAmountFormatted = $payment->paid_amount ? 'Rp ' . number_format((float) $payment->paid_amount, 0, ',', '.') : '-';
                $paidAtFormatted = $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->translatedFormat('d M Y H:i') : '-';

                $tooltip = "Cicilan Ke-{$payment->installment_number}: " . 
                    ($isPaid ? "LUNAS ({$paidAmountFormatted} pada {$paidAtFormatted})" : 
                    ($isOverdue ? "TERLAMBAT! Jatuh Tempo: {$dueDateFormatted} ({$amountFormatted})" : "BELUM BAYAR (Jatuh Tempo: {$dueDateFormatted})"));
            @endphp

            <div 
                title="{{ $tooltip }}"
                class="group relative inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-help
                    @if($isPaid)
                        bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/60
                    @elseif($isOverdue)
                        bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/60 animate-pulse
                    @else
                        bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800/60 dark:text-gray-400 dark:border-gray-700
                    @endif"
            >
                <span class="font-bold">#{{ $payment->installment_number }}</span>

                @if($isPaid)
                    {{-- Icon Ceklis Hijau --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-emerald-600 dark:text-emerald-400">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                    </svg>
                @elseif($isOverdue)
                    {{-- Icon Silang Merah (Overdue) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-rose-600 dark:text-rose-400">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                    </svg>
                @else
                    {{-- Icon Silang Abu-abu / Netral (Unpaid) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400 dark:text-gray-500">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        @endforeach
    @endif
</div>
