<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // pastikan kolom nullable dulu
        DB::statement("
            ALTER TABLE services
            ALTER COLUMN start_date DROP NOT NULL
        ");

        DB::statement("
            ALTER TABLE services
            ALTER COLUMN end_date DROP NOT NULL
        ");

        // drop constraint lama
        DB::statement("
            ALTER TABLE services
            DROP CONSTRAINT IF EXISTS services_status_check
        ");

        // add constraint baru
        DB::statement("
            ALTER TABLE services
            ADD CONSTRAINT services_status_check
            CHECK (status IN (
                'pending',
                'active',
                'expired',
                'suspended',
                'cancelled',
                'unpaid',
                'paid'
            ))
        ");

        // set default
        DB::statement("
            ALTER TABLE services
            ALTER COLUMN status SET DEFAULT 'active'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            DB::statement("
            ALTER TABLE services
            DROP CONSTRAINT IF EXISTS services_status_check
        ");

            DB::statement("
            ALTER TABLE services
            ADD CONSTRAINT services_status_check
            CHECK (status IN ('active'))
        ");

            DB::statement("
            ALTER TABLE services
            ALTER COLUMN status SET DEFAULT 'active'
        ");
        });
    }
};
