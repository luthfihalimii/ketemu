<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FULLTEXT hanya didukung MySQL/MariaDB; SQLite (test) dilewati agar
        // suite tetap jalan tanpa driver khusus.
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE items ADD FULLTEXT INDEX items_fulltext_idx (title, description, color, brand)');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE items DROP INDEX items_fulltext_idx');
    }
};
