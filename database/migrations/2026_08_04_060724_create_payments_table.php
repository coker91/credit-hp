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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid');
            $table->timestamp('wa_reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
