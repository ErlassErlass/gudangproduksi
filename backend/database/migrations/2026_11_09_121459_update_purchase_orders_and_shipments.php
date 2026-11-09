<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->change();
            $table->string('supplier_name', 160)->nullable()->after('supplier_id');
            $table->string('supplier_phone', 48)->nullable()->after('supplier_name');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('source_location_id')->nullable()->change();
            $table->foreignId('destination_location_id')->nullable()->change();
            $table->string('recipient_name', 160)->nullable()->after('tracking_reference');
            $table->text('notes')->nullable()->after('recipient_name');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['recipient_name', 'notes']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['supplier_name', 'supplier_phone']);
        });
    }
};
