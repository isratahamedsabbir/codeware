<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A standalone migration rather than folded into
 * 0001_01_01_000000_create_users_table.php (this project's usual "one
 * migration per table" convention) — replaying that migration via
 * migrate:fresh would wipe every other table too, discarding real data
 * already created through the admin panel. An additive column here avoids
 * that, same reasoning as 2026_09_13_144421_add_is_blocked_to_users_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('email');
        });

        // Backfill existing users — the column is nullable to keep the unique
        // index happy for rows we're about to fill, and each row gets its own
        // USR-XXXXXXXX code (see App\Concerns\HasUniqueCode).
        DB::table('users')->select('id')->orderBy('id')->eachById(function ($user) {
            do {
                $code = 'USR-'.strtoupper(Str::random(8));
            } while (DB::table('users')->where('code', $code)->exists());

            DB::table('users')->where('id', $user->id)->update(['code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
