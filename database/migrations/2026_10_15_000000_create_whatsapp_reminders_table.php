<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->index(['due_date', 'status']));
        Schema::create('whatsapp_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->unique(['payment_id', 'kind']);
            $table->string('status', 20)->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('error_code')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_reminders');
        Schema::table('payments', fn (Blueprint $table) => $table->dropIndex(['due_date', 'status']));
    }
};
