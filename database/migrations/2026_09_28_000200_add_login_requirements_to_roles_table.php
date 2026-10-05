<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The two login requirements move onto the role, from the four `mfa_require_*` /
 * `mfa_protect_api` settings rows and the `recaptcha_enabled` row that used to
 * carry them. Both default to off, deliberately: switching a requirement on is a
 * decision an admin makes about their own people, and doing it silently on
 * upgrade would lock out everyone in that role who had not enrolled a second
 * factor yet. Nothing is backfilled here either — the old rows are removed by
 * their own migration, and a site that had a requirement switched on re-arms it
 * by flipping the same switch on the same role.
 *
 * A standalone migration rather than folded into
 * 2026_08_11_122625_create_permission_tables.php, for the same reason as
 * 2026_09_13_124746_add_status_to_roles_table.php: replaying that one under
 * migrate:fresh would wipe every other table too.
 */
return new class extends Migration
{
    /**
     * The rows the switches used to live in, deleted here so the settings table
     * does not keep two keys nothing reads any more. The values are not
     * translated into the role columns — see the class docblock.
     *
     * @var list<string>
     */
    private const RETIRED_SETTING_KEYS = [
        'mfa_require_admin',
        'mfa_require_vendor',
        'mfa_require_delivery',
        'mfa_protect_api',
        'recaptcha_enabled',
    ];

    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('mfa_enabled')->default(false)->after('status');
            $table->boolean('recaptcha_enabled')->default(false)->after('mfa_enabled');
        });

        DB::table('settings')->whereIn('key', self::RETIRED_SETTING_KEYS)->delete();
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['mfa_enabled', 'recaptcha_enabled']);
        });

        foreach (self::RETIRED_SETTING_KEYS as $key) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => $key === 'mfa_protect_api' ? '1' : '0',
                'type' => 'boolean',
                'group' => str_starts_with($key, 'mfa_') ? 'security' : 'other',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
