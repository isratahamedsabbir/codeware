<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Delivery boy used to be a users.is_delivery_boy flag; it's now a Spatie role
 * ('delivery_boy'), exactly like 'vendor'. Moves every flagged user onto the
 * role, then drops the column. Standalone rather than editing the users
 * create migration for the same reason as
 * 2026_09_18_000000_add_is_delivery_boy_to_users_table.php — replaying it
 * via migrate:fresh would wipe real data.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'delivery_boy')->where('guard_name', 'web')->value('id')
            ?? DB::table('roles')->insertGetId([
                'name' => 'delivery_boy',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('users')->where('is_delivery_boy', true)->pluck('id')
            ->each(fn (int $userId) => DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]));

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_delivery_boy');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_delivery_boy')->default(false)->after('is_blocked');
        });

        $roleId = DB::table('roles')->where('name', 'delivery_boy')->where('guard_name', 'web')->value('id');

        if ($roleId) {
            $userIds = DB::table('model_has_roles')->where('role_id', $roleId)->where('model_type', 'App\\Models\\User')->pluck('model_id');
            DB::table('users')->whereIn('id', $userIds)->update(['is_delivery_boy' => true]);
        }
    }
};
