<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_phone_unique');

        DB::statement('
        CREATE UNIQUE INDEX users_email_unique
        ON users (email)
        WHERE deleted_at IS NULL
    ');

        DB::statement('
        CREATE UNIQUE INDEX users_phone_unique
        ON users (phone)
        WHERE deleted_at IS NULL
    ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_unique');
        DB::statement('DROP INDEX IF EXISTS users_phone_unique');

        Schema::table('users', function ($table) {
            $table->unique('email');
            $table->unique('phone');
        });
    }
};
