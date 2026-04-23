<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campos de tarifa a zonas comunes
        Schema::table('common_areas', function (Blueprint $table) {
            $table->enum('fee_type', ['none', 'per_person', 'per_time'])->default('none')->after('max_people');
            $table->decimal('fee_amount', 10, 2)->default(0)->after('fee_type');
        });

        // Agregar motivo de rechazo y tarifa calculada a reservas
        Schema::table('common_area_reservations', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('notes');
            $table->decimal('calculated_fee', 10, 2)->default(0)->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('common_areas', function (Blueprint $table) {
            $table->dropColumn(['fee_type', 'fee_amount']);
        });
        Schema::table('common_area_reservations', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'calculated_fee']);
        });
    }
};
