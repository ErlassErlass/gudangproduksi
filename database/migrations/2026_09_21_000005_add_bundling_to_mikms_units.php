<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah type finish_goods agar fleksibel mendukung 'bundle'
        Schema::table('mikms_finish_goods', function (Blueprint $table) {
            $table->string('type', 30)->default('direct')->change();
        });

        // Update tipe ROBO menjadi 'bundle'
        DB::table('mikms_finish_goods')->where('code', 'ROBO')->update([
            'type' => 'bundle',
            'description' => 'Paket Bundle Robotic Explorer. Dikemas dalam boks packaging luar khusus (1 Unit MLK + 1 Unit Jimu Trackbot).',
        ]);

        // 2. Tambah relasi parent_unit_id dan fleksibilitas status pada mikms_units
        Schema::table('mikms_units', function (Blueprint $table) {
            $table->foreignId('parent_unit_id')->nullable()->after('product_id')
                  ->constrained('mikms_units')->nullOnDelete();
            $table->string('status', 30)->default('qc_pending')->change();
            $table->string('source_type', 30)->default('direct_input')->change();
        });
    }

    public function down(): void
    {
        Schema::table('mikms_units', function (Blueprint $table) {
            $table->dropForeign(['parent_unit_id']);
            $table->dropColumn('parent_unit_id');
        });

        DB::table('mikms_finish_goods')->where('code', 'ROBO')->update([
            'type' => 'assembled',
        ]);
    }
};
