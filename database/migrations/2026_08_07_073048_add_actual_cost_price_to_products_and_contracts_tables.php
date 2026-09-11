<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table
                ->decimal('actual_cost_price', 15, 2)
                ->nullable()
                ->after('cost_price')
                ->comment('Modal sebenarnya setelah voucher/diskon tersembunyi. Jika null, dianggap sama dengan cost_price.');

            $table
                ->unsignedInteger('default_tenor')
                ->default(6)
                ->after('actual_cost_price')
                ->comment('Tenor acuan untuk menghitung cicilan per bulan referensi.');

            $table
                ->decimal('installment_reference', 15, 2)
                ->nullable()
                ->after('default_tenor')
                ->comment('Cicilan per bulan referensi (otomatis dihitung, bisa diedit manual).');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table
                ->decimal('actual_cost_price', 15, 2)
                ->nullable()
                ->after('total_price')
                ->comment('Snapshot modal sebenarnya dari produk saat kontrak dibuat, untuk kalkulasi profit internal.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['actual_cost_price', 'default_tenor', 'installment_reference']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('actual_cost_price');
        });
    }
};
