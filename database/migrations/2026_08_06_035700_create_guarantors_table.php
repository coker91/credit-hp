<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('nik', 16)->unique();
            $table->string('name');
            $table->string('phone_number', 20);
            $table->text('address');
            $table->string('relationship')->nullable()
                ->comment('e.g. Orang Tua, Saudara Kandung, Suami/Istri, Teman');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};
