<x-filament-panels::page>
    <style>
        .matrix-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        .dark .matrix-card {
            background-color: #111827;
            border-color: #1f2937;
        }

        .matrix-grid-4 {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .matrix-grid-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .matrix-filter-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
        }
        .dark .matrix-filter-box {
            background-color: #111827;
            border-color: #1f2937;
        }
        @media (min-width: 768px) {
            .matrix-filter-box {
                flex-direction: row;
            }
        }

        .matrix-table-wrapper {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow-x: auto;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        .dark .matrix-table-wrapper {
            background-color: #111827;
            border-color: #1f2937;
        }

        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
            text-align: left;
        }

        .matrix-table th, .matrix-table td {
            padding: 0.5rem 0.65rem;
            border: 1px solid #cbd5e1;
        }
        .dark .matrix-table th, .dark .matrix-table td {
            border-color: #1f2937;
        }

        .th-no, .th-barang, .th-pemilik {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 700;
        }
        .dark .th-no, .dark .th-barang, .dark .th-pemilik {
            background-color: #1f2937;
            color: #f3f4f6;
        }

        .th-cicilan {
            background-color: #0284c7;
            color: #ffffff;
            font-weight: 800;
            text-align: right;
        }

        .th-tenor {
            background-color: #e2e8f0;
            color: #334155;
            text-align: center;
            font-weight: 700;
        }
        .dark .th-tenor {
            background-color: #374151;
            color: #f9fafb;
        }

        .th-modal {
            background-color: #475569;
            color: #ffffff;
            font-weight: 800;
            text-align: right;
        }

        .th-laba {
            background-color: #16a34a;
            color: #ffffff;
            font-weight: 800;
            text-align: right;
        }

        .cell-black {
            background-color: #000000 !important;
        }
        .dark .cell-black {
            background-color: #030712 !important;
        }

        .badge-paid {
            background-color: #475569;
            color: #ffffff;
            padding: 0.3rem 0.4rem;
            border-radius: 0.25rem;
            font-weight: 600;
            text-align: center;
            font-size: 0.7rem;
            display: block;
        }

        .badge-overdue {
            background-color: #dc2626;
            color: #ffffff;
            padding: 0.3rem 0.4rem;
            border-radius: 0.25rem;
            font-weight: 700;
            text-align: center;
            font-size: 0.7rem;
            display: block;
            width: 100%;
            border: none;
            cursor: pointer;
        }
        .badge-overdue:hover {
            background-color: #b91c1c;
        }

        .badge-upcoming {
            background-color: #ffffff;
            color: #1f2937;
            border: 1px solid #d1d5db;
            padding: 0.3rem 0.4rem;
            border-radius: 0.25rem;
            font-weight: 500;
            text-align: center;
            font-size: 0.7rem;
            display: block;
            width: 100%;
            cursor: pointer;
        }
        .dark .badge-upcoming {
            background-color: #1f2937;
            color: #f3f4f6;
            border-color: #374151;
        }
        .badge-upcoming:hover {
            background-color: #f3f4f6;
        }
        .dark .badge-upcoming:hover {
            background-color: #374151;
        }

        .badge-missing {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px dashed #f59e0b;
            padding: 0.3rem 0.4rem;
            border-radius: 0.25rem;
            font-weight: 600;
            text-align: center;
            font-size: 0.7rem;
            display: block;
        }
        .dark .badge-missing {
            background-color: #451a03;
            color: #fde68a;
            border-color: #d97706;
        }

        .row-hover:hover {
            background-color: #f8fafc;
        }
        .dark .row-hover:hover {
            background-color: #1f2937;
        }

        .footer-total {
            background-color: #e2e8f0;
            font-weight: 800;
            font-size: 0.75rem;
        }
        .dark .footer-total {
            background-color: #1f2937;
            color: #f9fafb;
        }

        .input-style {
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            background-color: #ffffff;
            color: #111827;
        }
        .dark .input-style {
            background-color: #1f2937;
            color: #f9fafb;
            border-color: #374151;
        }
    </style>

    <div class="space-y-6">
        <!-- 📊 TOP KPI SUMMARY CARDS -->
        <div class="matrix-grid-4">
            <!-- Total Cicilan Perbulan -->
            <div class="matrix-card">
                <p style="font-size: 0.7rem; font-weight: 700; color: #0284c7; text-transform: uppercase; letter-spacing: 0.05em;">Total Cicilan per Bulan</p>
                <h3 style="font-size: 1.5rem; font-weight: 900; color: #0284c7; margin-top: 0.25rem;">
                    Rp {{ number_format($this->summaryStats['total_installment'], 0, ',', '.') }}
                </h3>
                <p style="font-size: 0.7rem; color: #64748b; margin-top: 0.25rem;">
                    Target penerimaan kas angsuran per bulan
                </p>
            </div>

            <!-- Total Modal Perbulan -->
            <div class="matrix-card">
                <p style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Jumlah Modal per Bulan</p>
                <h3 style="font-size: 1.5rem; font-weight: 900; color: #334155; margin-top: 0.25rem;" class="dark:text-slate-200">
                    Rp {{ number_format($this->summaryStats['total_modal'], 0, ',', '.') }}
                </h3>
                <p style="font-size: 0.7rem; color: #64748b; margin-top: 0.25rem;">
                    Alokasi pokok modal produk terpantau
                </p>
            </div>

            <!-- Total Laba Perbulan -->
            <div class="matrix-card" style="border-color: #86efac; background: linear-gradient(135deg, rgba(22, 163, 74, 0.08), transparent);">
                <p style="font-size: 0.7rem; font-weight: 700; color: #16a34a; text-transform: uppercase; letter-spacing: 0.05em;">Laba / Profit per Bulan</p>
                <h3 style="font-size: 1.5rem; font-weight: 900; color: #16a34a; margin-top: 0.25rem;">
                    Rp {{ number_format($this->summaryStats['total_laba'], 0, ',', '.') }}
                </h3>
                <p style="font-size: 0.7rem; color: #15803d; font-weight: 600; margin-top: 0.25rem;">
                    Clean Profit (Marginal {{ $this->summaryStats['margin_percent'] }}%)
                </p>
            </div>

            <!-- Total Kontrak Pantau -->
            <div class="matrix-card">
                <p style="font-size: 0.7rem; font-weight: 700; color: #d97706; text-transform: uppercase; letter-spacing: 0.05em;">Total Kontrak Pantau</p>
                <h3 style="font-size: 1.5rem; font-weight: 900; color: #d97706; margin-top: 0.25rem;">
                    {{ $this->summaryStats['active_count'] }} Unit / Customer
                </h3>
                <p style="font-size: 0.7rem; color: #64748b; margin-top: 0.25rem;">
                    Data aktif dalam tabel matrix
                </p>
            </div>
        </div>

        <!-- 🔍 FILTER BAR & LEGEND -->
        <div class="matrix-filter-box">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; width: 100%;">
                <!-- Search Input -->
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari Barang / Customer..." 
                    class="input-style"
                    style="min-width: 220px;"
                />

                <!-- Status Filter -->
                <select 
                    wire:model.live="filterStatus"
                    class="input-style"
                >
                    <option value="active">Status: Aktif (Berjalan)</option>
                    <option value="all">Status: Semua Kontrak</option>
                    <option value="completed">Status: Lunas</option>
                    <option value="defaulted">Status: Macet</option>
                </select>
            </div>

            <!-- Legend Badge Indicators -->
            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.4rem; font-size: 0.7rem;">
                <span class="badge-paid" style="padding: 0.2rem 0.5rem;">Lunas</span>
                <span class="badge-overdue" style="padding: 0.2rem 0.5rem; width: auto;">Terlambat</span>
                <span class="badge-upcoming" style="padding: 0.2rem 0.5rem; width: auto;">Belum Bayar</span>
                <span class="cell-black" style="padding: 0.2rem 0.5rem; color: #ffffff !important; border-radius: 0.25rem;">Luar Tenor</span>
            </div>
        </div>

        <!-- 📋 MATRIX SPREADSHEET TABLE -->
        <div class="matrix-table-wrapper">
            <table class="matrix-table">
                <thead>
                    <!-- Row 1 Header Header Group -->
                    <tr style="text-transform: uppercase; font-size: 0.7rem;">
                        <th class="th-no" rowspan="2" style="text-align: center; width: 40px;">No</th>
                        <th class="th-barang" rowspan="2" style="min-width: 140px;">Nama Barang</th>
                        <th class="th-pemilik" rowspan="2" style="min-width: 130px;">Nama Pemilik</th>
                        <th class="th-cicilan" rowspan="2" style="min-width: 130px;">
                            Cicilan Perbulan
                        </th>
                        <th class="th-tenor" colSpan="{{ $this->maxTenor }}">
                            Cicilan Ke-
                        </th>
                        <th class="th-modal" rowspan="2" style="min-width: 120px;">
                            Modal
                        </th>
                        <th class="th-laba" rowspan="2" style="min-width: 120px;">
                            Laba
                        </th>
                    </tr>

                    <!-- Row 2 Header Tenor Columns (1, 2, 3...) -->
                    <tr>
                        @for ($i = 1; $i <= $this->maxTenor; $i++)
                            <th class="th-tenor" style="min-width: 85px;">
                                {{ $i }}
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->contracts as $index => $contract)
                       @php
                            $monthlyInstallment = (float) $contract->installment;
                            $tenor = max(1, (int) $contract->tenor);

                            // 🟢 Ambil dari actual_cost_price (snapshot di kontrak), fallback ke product jika kosong
                            $actualCostTotal = (float) (
                                $contract->actual_cost_price
                                    ?? $contract->product?->actual_cost_price
                                    ?? $contract->product?->cost_price
                                    ?? 0
                            );

                            $modalPerBulan = $tenor > 0 ? ($actualCostTotal / $tenor) : 0;
                            $labaPerBulan = round($monthlyInstallment - $modalPerBulan);

                            $payments = $contract->payments->keyBy('installment_number');
                       @endphp
                        <tr class="row-hover">
                            <!-- No -->
                            <td style="text-align: center; font-weight: 600; color: #64748b;">
                                {{ $index + 1 }}
                            </td>

                            <!-- Nama Barang -->
                            <td style="font-weight: 700;" class="dark:text-slate-100">
                                {{ $contract->product?->brand ?? 'Produk N/A' }}
                            </td>

                            <!-- Nama Pemilik -->
                            <td style="font-weight: 600;" class="dark:text-slate-200">
                                {{ $contract->customer?->name ?? 'Nasabah N/A' }}
                                <span style="display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 400;">
                                    CTR: {{ $contract->contract_number }}
                                </span>
                            </td>

                            <!-- Cicilan Perbulan -->
                            <td style="text-align: right; font-weight: 900; color: #0284c7; background-color: rgba(2, 132, 199, 0.05);">
                                {{ number_format($monthlyInstallment, 0, ',', '.') }}
                            </td>

                            <!-- Cicilan Ke- 1..MaxTenor -->
                            @for ($i = 1; $i <= $this->maxTenor; $i++)
                                @if ($i <= $tenor)
                                    @php
                                        $payment = $payments->get($i);
                                        $status = $payment?->status ?? 'unpaid';
                                        $dueDateFormatted = $payment?->due_date 
                                            ? \Carbon\Carbon::parse($payment->due_date)->format('d M y') 
                                            : '-';
                                        
                                        $isOverdue = ($status === 'overdue' || ($status === 'unpaid' && $payment?->due_date && \Carbon\Carbon::parse($payment->due_date)->isPast()));
                                        $isPaid = ($status === 'paid');
                                    @endphp

                                    <td style="text-align: center; padding: 0.35rem 0.25rem;">
                                        @if (! $payment)
                                            <!-- MISSING PAYMENT SCHEDULE -->
                                            <span
                                                class="badge-missing"
                                                title="Jadwal cicilan ke-{{ $i }} belum dibuat"
                                            >
                                                Belum dibuat
                                            </span>
                                        @elseif ($isPaid)
                                            <!-- PAID BADGE -->
                                            <span 
                                                class="badge-paid"
                                                title="Lunas - Bayar: {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') : '' }}"
                                            >
                                                {{ $dueDateFormatted }}
                                            </span>
                                        @elseif ($isOverdue)
                                            <!-- OVERDUE BADGE -->
                                            <button 
                                                type="button"
                                                wire:click="markAsPaid({{ $payment->id }})"
                                                wire:confirm="Catat pelunasan untuk cicilan ke-{{ $i }} ({{ $contract->customer?->name }})?"
                                                class="badge-overdue"
                                                title="Klik untuk Catat Lunas | Belum Bayar (Jatuh Tempo: {{ $dueDateFormatted }})"
                                            >
                                                {{ $dueDateFormatted }}
                                            </button>
                                        @else
                                            <!-- UPCOMING BADGE -->
                                            <button 
                                                type="button"
                                                wire:click="markAsPaid({{ $payment->id }})"
                                                wire:confirm="Catat pelunasan untuk cicilan ke-{{ $i }} ({{ $contract->customer?->name }})?"
                                                class="badge-upcoming"
                                                title="Klik untuk Catat Lunas (Jatuh Tempo: {{ $dueDateFormatted }})"
                                            >
                                                {{ $dueDateFormatted }}
                                            </button>
                                        @endif
                                    </td>
                                @else
                                    <!-- OUT OF TENOR (BLACKED OUT CELL MATCHING EXCEL) -->
                                    <td class="cell-black"></td>
                                @endif
                            @endfor

                            <!-- Modal -->
                            <td style="text-align: right; font-weight: 700; color: #475569;" class="dark:text-slate-300">
                                {{ number_format($modalPerBulan, 0, ',', '.') }}
                            </td>

                            <!-- Laba -->
                            <td style="text-align: right; font-weight: 900; color: #16a34a; background-color: rgba(22, 163, 74, 0.05);">
                                {{ number_format($labaPerBulan, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + $this->maxTenor }}" style="text-align: center; padding: 2rem; color: #64748b;">
                                Tidak ada data kontrak cicilan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <!-- 🧮 TOTAL FOOTER ROW -->
                <tfoot>
                    <tr class="footer-total">
                        <td style="text-align: center; font-weight: 900; text-transform: uppercase;" colspan="3">
                            Total Overall Bulanan
                        </td>

                        <!-- Total Cicilan Perbulan -->
                        <td style="text-align: right; font-weight: 900; color: #0284c7; background-color: rgba(2, 132, 199, 0.15); font-size: 0.85rem;">
                            {{ number_format($this->summaryStats['total_installment'], 0, ',', '.') }}
                        </td>

                        <!-- Spacer across Tenor columns -->
                        <td colspan="{{ $this->maxTenor }}" style="background-color: rgba(100, 116, 139, 0.1);"></td>

                        <!-- Total Modal -->
                        <td style="text-align: right; font-weight: 900; color: #334155; background-color: rgba(71, 85, 105, 0.15); font-size: 0.85rem;" class="dark:text-slate-200">
                            {{ number_format($this->summaryStats['total_modal'], 0, ',', '.') }}
                        </td>

                        <!-- Total Laba -->
                        <td style="text-align: right; font-weight: 900; color: #16a34a; background-color: rgba(22, 163, 74, 0.2); font-size: 0.85rem;">
                            {{ number_format($this->summaryStats['total_laba'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-filament-panels::page>
